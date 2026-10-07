<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Support\Carbon;

/**
 * Server → device commands for ZKTeco push (ADMS / iClock) devices, plus the
 * "Pull from Device" date-range pull.
 *
 * Push-mode devices can't be read directly, so a pull is a request:
 *   1. we queue  "DATA QUERY ATTLOG StartTime=..<TAB>EndTime=.."
 *   2. the device picks it up on its next GET /iclock/getrequest poll
 *      (delivered as "C:<id>:<command>")
 *   3. it re-uploads those punches to POST /iclock/cdata?table=ATTLOG, which
 *      AdmsService already saves (its firstOrCreate skips duplicates)
 *   4. it confirms on POST /iclock/devicecmd ("ID=<id>&Return=0&CMD=DATA")
 *
 * AdmsService is intentionally not modified; ZktecoAdmsController calls this
 * service around it.
 */
class AdmsCommandService
{
    /** A command sent but never confirmed within this time is marked failed. */
    public const SENT_TIMEOUT_MINUTES = 30;

    /** Max commands handed to a device per poll. */
    public const BATCH = 10;

    public function __construct(protected DeviceActivityService $activity)
    {
    }

    // ───────────────────────────── Pull from Device ─────────────────────────────

    /**
     * Pull attendance for a date range from every active device (or one).
     * Push-mode devices get a re-upload request; TCP devices are read live.
     *
     * @return array{deviceCount: int, pulled: int, requested: int, messages: string[]}
     */
    public function pullAttendance(string $from, string $to, ?int $onlyDeviceId = null): array
    {
        $devices = Device::where('status', 'active')
            ->when($onlyDeviceId, fn ($q) => $q->where('id', $onlyDeviceId))
            ->get();

        $pulled = 0;
        $requested = 0;
        $messages = [];

        foreach ($devices as $device) {
            if ($device->use_push_mode) {
                $r = $this->requestAttendance($device, $from, $to);
                $requested++;
            } else {
                $r = $this->activity->pullAttendance($device, $from, $to);
                $pulled += $r['saved'];
            }
            $messages[] = ($r['success'] ? '✓ ' : '✗ ') . $r['message'];
        }

        return ['deviceCount' => $devices->count(), 'pulled' => $pulled, 'requested' => $requested, 'messages' => $messages];
    }

    /**
     * Queue a date-range re-upload for a push-mode device (no duplicate
     * request while the same range is still waiting).
     *
     * @return array{success: bool, message: string}
     */
    public function requestAttendance(Device $device, string $from, string $to): array
    {
        $command = self::attlogQueryCommand($from, $to);

        $waiting = DeviceCommand::where('device_id', $device->id)
            ->whereIn('status', ['pending', 'sent'])
            ->where('command_text', $command)
            ->exists();

        if (! $waiting) {
            DeviceCommand::queue($device->id, $command);
        }

        $inDb = AttendanceLog::where('device_serial', $device->serial_no)
            ->where('punch_time', '>=', Carbon::parse($from)->startOfDay())
            ->where('punch_time', '<=', Carbon::parse($to)->endOfDay())
            ->count();

        $contact = $device->last_seen_at
            ? 'last contact ' . Carbon::parse($device->last_seen_at)->diffForHumans()
            : 'WARNING: this device has never contacted the server, check its ADMS / Cloud Server address';

        return [
            'success' => true,
            'message' => "[{$device->name}] " . ($waiting ? 'Request already waiting' : 'Request sent')
                . " for {$from} → {$to}. The device uploads these punches on its next check-in"
                . " (usually within a minute; {$contact}). Records currently saved for this range: {$inDb}.",
        ];
    }

    public static function attlogQueryCommand(string $from, string $to): string
    {
        $start = Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:s');

        return "DATA QUERY ATTLOG StartTime={$start}\tEndTime={$end}";
    }

    // ───────────────────────────── Delivery (device polls) ─────────────────────────────

    /**
     * Pending commands for GET /iclock/getrequest in the protocol format
     * "C:<id>:<command>" (one per line), or null when there is nothing to send.
     */
    public function deliver(?Device $device): ?string
    {
        if (! $device) {
            return null;
        }

        // Expire commands the device took but never confirmed.
        DeviceCommand::where('device_id', $device->id)
            ->where('status', 'sent')
            ->where('updated_at', '<', now()->subMinutes(self::SENT_TIMEOUT_MINUTES))
            ->update(['status' => 'failed']);

        $commands = DeviceCommand::where('device_id', $device->id)
            ->pending()
            ->orderBy('id')
            ->limit(self::BATCH)
            ->get();

        if ($commands->isEmpty()) {
            return null;
        }

        DeviceCommand::whereIn('id', $commands->pluck('id'))->update(['status' => 'sent', 'updated_at' => now()]);

        return $commands->map(fn (DeviceCommand $c) => "C:{$c->id}:{$c->command_text}")->implode("\n") . "\n";
    }

    /**
     * POST /iclock/devicecmd — one result per line, e.g. "ID=12&Return=0&CMD=DATA".
     * Return >= 0 is success. Only this device's own commands are updated.
     */
    public function applyResults(?Device $device, string $body): int
    {
        if (! $device || trim($body) === '') {
            return 0;
        }

        $updated = 0;
        foreach (preg_split('/\r\n|\n|\r/', trim($body)) as $line) {
            parse_str(trim($line), $result);
            if (empty($result['ID']) || ! ctype_digit((string) $result['ID'])) {
                continue;
            }
            $ok      = ((int) ($result['Return'] ?? -1)) >= 0;
            $changed = DeviceCommand::where('id', (int) $result['ID'])
                ->where('device_id', $device->id)
                ->update([
                    'status'      => $ok ? 'done' : 'failed',
                    'executed_at' => now(),
                ]);
            $updated += $changed;

            if ($changed) {
                $this->syncDeviceUser($device, (int) $result['ID'], $ok);
            }
        }

        return $updated;
    }

    /**
     * Keep device_users in step with a user-push result: confirmed → mark it
     * received; rejected → drop the row if the device never confirmed that
     * user (it was only expected there because we queued it).
     */
    protected function syncDeviceUser(Device $device, int $commandId, bool $ok): void
    {
        $text = (string) DeviceCommand::whereKey($commandId)->value('command_text');
        if (! preg_match('/^DATA UPDATE USERINFO PIN=([^\t]+)/', $text, $m)) {
            return;
        }

        $row = \App\Models\DeviceUser::where('device_id', $device->id)->where('pin', $m[1]);
        if ($ok) {
            $row->update(['received_at' => now()]);
        } else {
            $row->whereNull('received_at')->delete();
        }
    }
}
