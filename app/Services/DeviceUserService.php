<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DeviceUser;
use Illuminate\Support\Facades\Log;

/**
 * Reads the users (PIN + name) enrolled on a fingerprint device into
 * device_users.
 *
 *  - Push (ADMS) devices: we queue "DATA QUERY USERINFO"; the device picks
 *    it up on its next poll and uploads lines like
 *      USER PIN=101<TAB>Name=Abdul Karim<TAB>Pri=0<TAB>Card=...
 *    to /iclock/cdata (table OPERLOG / USERINFO / USER), which
 *    ZktecoAdmsController hands to storeFromUpload().
 *  - TCP devices: read live over the network.
 *
 * Only device_users is written — teachers/students are never changed here.
 */
class DeviceUserService
{
    public const USER_QUERY = 'DATA QUERY USERINFO';

    /** Tables a device may use when uploading user records. */
    public const UPLOAD_TABLES = ['OPERLOG', 'USERINFO', 'USER'];

    public function __construct(protected ZkTecoService $zk)
    {
    }

    /** @return array{success: bool, message: string, queued: bool, count: int} */
    public function fetch(Device $device): array
    {
        if ($device->use_push_mode) {
            $waiting = DeviceCommand::where('device_id', $device->id)
                ->whereIn('status', ['pending', 'sent'])
                ->where('command_text', self::USER_QUERY)
                ->exists();
            if (! $waiting) {
                DeviceCommand::queue($device->id, self::USER_QUERY);
            }

            $contact = $device->last_seen_at
                ? 'last contact ' . $device->last_seen_at->diffForHumans()
                : 'this device has never contacted the server, check its ADMS / Cloud Server address';

            return [
                'success' => true,
                'queued'  => true,
                'count'   => 0,
                'message' => ($waiting ? 'A user request is already waiting' : 'User list requested')
                    . " from [{$device->name}]. It is sent on the device's next check-in (usually within a minute; {$contact}). Refresh this page to see the users.",
            ];
        }

        $zk = $this->zk->connect($device);
        if (! $zk) {
            return ['success' => false, 'queued' => false, 'count' => 0, 'message' => "Cannot connect to [{$device->name}] via TCP."];
        }
        $users = $this->zk->getUsers($zk);
        $this->zk->disconnect($zk);

        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                'pin'       => trim((string) ($u['userid'] ?? '')),
                'name'      => $u['name'] ?? null,
                'privilege' => (int) ($u['role'] ?? 0),
                'card'      => $u['cardno'] ?? null,
            ];
        }
        $count = $this->store($device, $rows, true);

        return ['success' => true, 'queued' => false, 'count' => $count, 'message' => "{$count} user(s) read from [{$device->name}]."];
    }

    /**
     * Parse a user upload from a push device and save it.
     * Returns the number of users saved (0 if the body held no user lines).
     */
    public function storeFromUpload(Device $device, string $body): int
    {
        $rows = [];
        foreach (preg_split('/\r\n|\n|\r/', $body) as $line) {
            if ($row = self::parseUserLine($line)) {
                $rows[] = $row;
            }
        }

        return $rows ? $this->store($device, $rows, false) : 0;
    }

    /**
     * "USER PIN=101<TAB>Name=Abdul Karim<TAB>Pri=0<TAB>Card=123" → row,
     * or null for anything that is not a user record (FP, BIODATA, OPLOG...).
     */
    public static function parseUserLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }
        if (preg_match('/^(USER|USERINFO)\s+/i', $line)) {
            $line = preg_replace('/^(USER|USERINFO)\s+/i', '', $line);
        } elseif (! str_starts_with(strtoupper($line), 'PIN=')) {
            return null; // FP / BIODATA / FACE / OPLOG / ... lines
        }

        $fields = [];
        foreach (explode("\t", $line) as $part) {
            $pos = strpos($part, '=');
            if ($pos !== false) {
                $fields[strtolower(trim(substr($part, 0, $pos)))] = trim(substr($part, $pos + 1));
            }
        }

        $pin = $fields['pin'] ?? '';
        if ($pin === '' || strlen($pin) > 50) {
            return null;
        }

        return [
            'pin'       => $pin,
            'name'      => $fields['name'] ?? null,
            'privilege' => (int) ($fields['pri'] ?? $fields['privilege'] ?? 0),
            'card'      => ($fields['card'] ?? '') !== '' ? $fields['card'] : null,
        ];
    }

    /**
     * Upsert rows for the device. With $fullList (TCP read = the complete
     * list) users no longer on the device are removed from device_users.
     */
    protected function store(Device $device, array $rows, bool $fullList): int
    {
        $now = now();
        $saved = 0;
        $pins = [];

        foreach ($rows as $r) {
            $pin = trim((string) $r['pin']);
            if ($pin === '') {
                continue;
            }
            $name = isset($r['name']) ? trim(mb_convert_encoding((string) $r['name'], 'UTF-8', 'UTF-8')) : null;

            DeviceUser::updateOrCreate(
                ['device_id' => $device->id, 'pin' => $pin],
                [
                    'name'        => $name !== '' ? mb_substr($name, 0, 191) : null,
                    'privilege'   => (int) ($r['privilege'] ?? 0),
                    'card'        => isset($r['card']) && $r['card'] !== '' ? mb_substr((string) $r['card'], 0, 50) : null,
                    'received_at' => $now,
                ]
            );
            $pins[] = $pin;
            $saved++;
        }

        if ($fullList) {
            DeviceUser::where('device_id', $device->id)->whereNotIn('pin', $pins ?: ['__none__'])->delete();
        }

        if ($saved) {
            Log::channel('adms')->info('USERS SAVED', ['sn' => $device->serial_no, 'count' => $saved]);
        }

        return $saved;
    }
}
