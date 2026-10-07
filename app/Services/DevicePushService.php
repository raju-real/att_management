<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DeviceUser;
use Illuminate\Support\Collection;

/**
 * Pushes teachers/students (PIN + name) to fingerprint devices and removes
 * them — the single place every push goes through (list buttons, the
 * push modal, and the automatic sync on teacher/student save).
 *
 * Push rules (per device):
 *   - PIN already on the device with the same name → skipped (nothing sent)
 *   - PIN on the device with a different name       → only the name is updated;
 *                                                      privilege/card re-sent as the
 *                                                      device reported them
 *   - PIN not on the device                         → added
 *   Password, card and fingerprints are never sent or reset.
 *
 * "On the device" = device_users (from "Fetch Users", kept up to date by
 * every push/remove). Fetch Users first for the most accurate skip list.
 *
 * Push mode (ADMS): commands are queued and applied on the device's next
 * check-in. TCP mode: done live; existing users are never modified.
 */
class DevicePushService
{
    public function __construct(protected ZkTecoService $zk)
    {
    }

    /**
     * Active devices that serve this group, optionally only one of them.
     *
     * @param 'teacher'|'student' $group
     */
    public function devices(string $group, ?int $onlyDeviceId = null): Collection
    {
        return Device::where('status', 'active')
            ->whereIn('device_for', [$group, 'student_teacher'])
            ->when($onlyDeviceId, fn ($q) => $q->where('id', $onlyDeviceId))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param 'teacher'|'student'   $group
     * @param array<string, string> $people pin => name
     * @return array{devices: int, lines: string[], added: int, renamed: int, skipped: int}
     */
    public function push(string $group, array $people, ?int $onlyDeviceId = null): array
    {
        $people = $this->clean($people);
        $result = ['devices' => 0, 'lines' => [], 'added' => 0, 'renamed' => 0, 'skipped' => 0];

        foreach ($this->devices($group, $onlyDeviceId) as $device) {
            $result['devices']++;
            $r = $device->use_push_mode ? $this->pushQueued($device, $people) : $this->pushTcp($device, $people);

            foreach (['added', 'renamed', 'skipped'] as $k) {
                $result[$k] += $r[$k];
            }
            $result['lines'][] = $r['line'];
        }

        return $result;
    }

    /**
     * @param 'teacher'|'student' $group
     * @param string[]            $pins
     * @return array{devices: int, lines: string[], removed: int}
     */
    public function remove(string $group, array $pins, ?int $onlyDeviceId = null): array
    {
        $pins   = array_values(array_unique(array_filter(array_map(fn ($p) => trim((string) $p), $pins), 'strlen')));
        $result = ['devices' => 0, 'lines' => [], 'removed' => 0];

        foreach ($this->devices($group, $onlyDeviceId) as $device) {
            $result['devices']++;

            if ($device->use_push_mode) {
                foreach ($pins as $pin) {
                    $this->queueOnce($device, DeviceCommand::deleteUserCommand($pin));
                }
                DeviceUser::where('device_id', $device->id)->whereIn('pin', $pins)->delete();
                $result['removed'] += count($pins);
                $result['lines'][] = "[{$device->name}] " . count($pins) . ' user(s) queued for removal (applied on the next check-in).';
                continue;
            }

            try {
                $conn = $this->zk->connect($device);
                if (! $conn) {
                    $result['lines'][] = "[{$device->name}] unreachable via TCP, skipped.";
                    continue;
                }
                $removed = 0;
                foreach ($pins as $pin) {
                    $uid = $this->zk->findUidByUserId($conn, $pin);
                    if ($uid !== null && $this->zk->deleteUser($conn, $uid)) {
                        $removed++;
                    }
                }
                $this->zk->disconnect($conn);
                DeviceUser::where('device_id', $device->id)->whereIn('pin', $pins)->delete();
                $result['removed'] += $removed;
                $result['lines'][] = "[{$device->name}] {$removed} user(s) removed.";
            } catch (\Throwable $e) {
                $result['lines'][] = "[{$device->name}] remove failed: " . $e->getMessage();
            }
        }

        return $result;
    }

    // ─── Push mode ────────────────────────────────────────────────────────────

    protected function pushQueued(Device $device, array $people): array
    {
        $known = DeviceUser::where('device_id', $device->id)
            ->whereIn('pin', array_keys($people))
            ->get()
            ->keyBy('pin');

        $added = $renamed = $skipped = 0;

        foreach ($people as $pin => $name) {
            $onDevice = $known->get((string) $pin);

            if ($onDevice && $this->sameName($onDevice->name, $name)) {
                $skipped++;
                continue;
            }

            if ($onDevice) {
                // Rename only — keep the device's own privilege/card.
                $this->queueOnce($device, DeviceCommand::userInfoCommand($pin, $name, (int) $onDevice->privilege, $onDevice->card));
                $onDevice->update(['name' => $name]);
                $renamed++;
                continue;
            }

            $this->queueOnce($device, DeviceCommand::userInfoCommand($pin, $name));
            // Expected on the device; received_at stays empty until the device confirms it
            // (and the row is dropped again if the device rejects the command).
            DeviceUser::firstOrCreate(['device_id' => $device->id, 'pin' => (string) $pin], ['name' => $name, 'privilege' => 0]);
            $added++;
        }

        return [
            'added' => $added, 'renamed' => $renamed, 'skipped' => $skipped,
            'line'  => "[{$device->name}] {$added} new, {$renamed} name update(s), {$skipped} already on device (skipped). Queued: applied on the device's next check-in.",
        ];
    }

    // ─── TCP mode ─────────────────────────────────────────────────────────────

    protected function pushTcp(Device $device, array $people): array
    {
        $zero = ['added' => 0, 'renamed' => 0, 'skipped' => 0];

        try {
            $conn = $this->zk->connect($device);
            if (! $conn) {
                return $zero + ['line' => "[{$device->name}] unreachable via TCP, skipped."];
            }

            $onDevice = collect($this->zk->getUsers($conn))->keyBy(fn ($u) => (string) ($u['userid'] ?? ''));
            $added = $skipped = $differs = 0;

            foreach ($people as $pin => $name) {
                if ($onDevice->has((string) $pin)) {
                    // Never modify an existing TCP user (uid, password, card, templates stay as they are).
                    $this->sameName($onDevice[(string) $pin]['name'] ?? '', $name) ? $skipped++ : $differs++;
                    continue;
                }
                $this->zk->pushUser($conn, (string) $pin, $name);
                $added++;
            }
            $this->zk->disconnect($conn);

            return ['added' => $added, 'renamed' => 0, 'skipped' => $skipped + $differs,
                'line' => "[{$device->name}] {$added} new, {$skipped} already on device"
                    . ($differs ? ", {$differs} on device under a different name (left unchanged)" : '') . '.'];
        } catch (\Throwable $e) {
            return $zero + ['line' => "[{$device->name}] push failed: " . $e->getMessage()];
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Queue a command unless the identical one is already waiting for this device. */
    protected function queueOnce(Device $device, string $command): void
    {
        $waiting = DeviceCommand::where('device_id', $device->id)
            ->where('status', 'pending')
            ->where('command_text', $command)
            ->exists();

        if (! $waiting) {
            DeviceCommand::queue($device->id, $command);
        }
    }

    /** @return array<string, string> pin => name, blanks removed */
    protected function clean(array $people): array
    {
        $out = [];
        foreach ($people as $pin => $name) {
            $pin = trim((string) $pin);
            if ($pin !== '') {
                // Collapse spaces (an empty middle name gives "Rahim  Uddin").
                $name      = trim(preg_replace('/\s+/u', ' ', (string) $name));
                $out[$pin] = mb_substr($name !== '' ? $name : 'User ' . $pin, 0, 191);
            }
        }
        return $out;
    }

    /**
     * $onDevice vs $ours, ignoring case/extra spaces. Devices keep only
     * ~24 characters of a name, so a truncated device name still matches.
     */
    protected function sameName(?string $onDevice, ?string $ours): bool
    {
        $norm = fn ($s) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $s)));
        $a = $norm($onDevice);
        $b = $norm($ours);

        return $a === $b || (mb_strlen($a) >= 20 && str_starts_with($b, $a));
    }
}
