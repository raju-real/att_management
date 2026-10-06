<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DeviceUser;
use App\Models\Student;
use App\Models\Teacher;
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

        return ['success' => true, 'queued' => false, 'count' => $count,
            'message' => "{$count} user(s) read from [{$device->name}]; {$this->lastTeachersCreated} new teacher(s) created (existing teachers unchanged)."];
    }

    /**
     * Parse a user upload from a push device and save it.
     * Returns the number of users saved (0 if the body held no user lines).
     */
    public function storeFromUpload(Device $device, string $body): int
    {
        $this->lastTeachersCreated = 0;
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

        $this->lastTeachersCreated = $this->createMissingTeachers($device, $pins);

        if ($saved) {
            Log::channel('adms')->info('USERS SAVED', [
                'sn' => $device->serial_no, 'count' => $saved, 'teachers_created' => $this->lastTeachersCreated,
            ]);
        }

        return $saved;
    }

    /** Teachers created by the most recent store() call. */
    public int $lastTeachersCreated = 0;

    /** New teachers get this department until an admin assigns one ("Not set" in the list). */
    public const UNASSIGNED_DEPARTMENT = 0;

    /**
     * Create a teacher (teacher_no = PIN, name = device name) for every
     * device user that is not a teacher yet. Existing teachers — including
     * soft-deleted ones, teacher_no is unique in the DB — are never changed.
     *
     * Skipped: student-only devices, PINs used by a student (a PIN on both
     * would make punches ambiguous) and non-numeric PINs.
     */
    protected function createMissingTeachers(Device $device, array $pins): int
    {
        if ($device->device_for === 'student' || ! $pins) {
            return 0;
        }

        $pins = array_values(array_unique(array_filter($pins, fn ($p) => preg_match('/^[0-9]{1,20}$/', $p))));
        if (! $pins) {
            return 0;
        }

        $teacherNos = Teacher::withTrashed()->whereIn('teacher_no', $pins)->pluck('teacher_no')->map(fn ($v) => (string) $v)->all();
        $studentNos = Student::withTrashed()->whereIn('student_no', $pins)->pluck('student_no')->map(fn ($v) => (string) $v)->all();
        $new = array_diff($pins, $teacherNos, $studentNos);
        if (! $new) {
            return 0;
        }

        $names = DeviceUser::where('device_id', $device->id)->whereIn('pin', $new)->pluck('name', 'pin');

        $created = 0;
        foreach ($new as $pin) {
            try {
                $teacher = new Teacher();
                $teacher->teacher_no    = $pin;
                $teacher->name          = trim((string) ($names[$pin] ?? '')) ?: "Teacher {$pin}";
                $teacher->department_id = self::UNASSIGNED_DEPARTMENT;
                $teacher->save();
                $created++;
            } catch (\Throwable $e) {
                // e.g. created at the same moment by another request — skip it
                Log::channel('adms')->warning('TEACHER CREATE SKIPPED', ['sn' => $device->serial_no, 'pin' => $pin, 'error' => $e->getMessage()]);
            }
        }

        return $created;
    }
}
