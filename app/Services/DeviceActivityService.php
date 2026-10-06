<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;

/**
 * Single home for every device activity the UI exposes: test connection,
 * pull attendance, push/pull teachers, push/pull students, and clearing a
 * device's users. Controllers just call into this and turn the result into
 * a redirect/response — no device protocol logic lives in a controller.
 *
 * Every method here works for BOTH connection modes:
 *   Push Mode (ADMS) → queues a command / reads what the device already
 *                       pushed into the DB. No live socket needed.
 *   TCP/UDP Mode      → talks to the device directly over the socket,
 *                       right now, via ZkTecoService.
 */
class DeviceActivityService
{
    public function __construct(protected ZkTecoService $zk)
    {
    }

    // ─── Test Connection ───────────────────────────────────────────────────────

    public function testConnection(Device $device): array
    {
        return $this->zk->testConnection($device);
    }

    // ─── Push (DB → device) ────────────────────────────────────────────────────

    /** @return array{success: bool, count: int, message: string} */
    public function pushTeachers(Device $device): array
    {
        return $this->pushBatch($device, Teacher::all()->map(fn (Teacher $t) => [
            'pin'  => (string) $t->teacher_no,
            'name' => $t->name ?: 'Teacher',
        ]), 'teacher');
    }

    /** @return array{success: bool, count: int, message: string} */
    public function pushStudents(Device $device): array
    {
        return $this->pushBatch($device, Student::all()->map(fn (Student $s) => [
            'pin'  => (string) $s->student_no,
            'name' => showStudentFullName($s->firstname, $s->middlename, $s->lastname) ?: 'Student',
        ]), 'student');
    }

    /**
     * @param \Illuminate\Support\Collection<int, array{pin: string, name: string}> $people
     */
    protected function pushBatch(Device $device, $people, string $label): array
    {
        $count = 0;

        if ($device->use_push_mode) {
            foreach ($people as $p) {
                DeviceCommand::queue($device->id, DeviceCommand::setUserCommand($p['pin'], $p['name']));
                $count++;
            }
            return [
                'success' => true,
                'count'   => $count,
                'message' => "{$count} {$label}(s) queued for push to [{$device->name}]. Device will sync within ~30 seconds.",
            ];
        }

        $zk = $this->zk->connect($device);
        if (! $zk) {
            return ['success' => false, 'count' => 0, 'message' => "Cannot connect to [{$device->name}] via TCP. Check IP, port, and that the device is powered on and reachable."];
        }
        foreach ($people as $p) {
            try {
                $this->zk->pushUser($zk, $p['pin'], $p['name']);
                $count++;
            } catch (\Throwable) {
                // skip individual failures, keep going
            }
        }
        $this->zk->disconnect($zk);

        return ['success' => true, 'count' => $count, 'message' => "{$count} {$label}(s) pushed to [{$device->name}] successfully."];
    }

    /**
     * Push teachers/students to every active device that serves that group.
     * @return array{total: int, messages: string[]}
     */
    public function pushTeachersToAllDevices(): array
    {
        return $this->pushToAllDevices('teacher', fn (Device $d) => $this->pushTeachers($d));
    }

    public function pushStudentsToAllDevices(): array
    {
        return $this->pushToAllDevices('student', fn (Device $d) => $this->pushStudents($d));
    }

    protected function pushToAllDevices(string $group, callable $pushOne): array
    {
        $devices = Device::where('status', 'active')
            ->whereIn('device_for', [$group, 'student_teacher'])
            ->get();

        $total    = 0;
        $messages = [];
        foreach ($devices as $device) {
            $result = $pushOne($device);
            $total += $result['count'];
            $messages[] = ($result['success'] ? '✓ ' : '✗ ') . $result['message'];
        }

        return ['total' => $total, 'messages' => $messages, 'deviceCount' => $devices->count()];
    }

    // ─── Pull Attendance (device → DB) ─────────────────────────────────────────

    /** @return array{success: bool, saved: int, message: string} */
    public function pullAttendance(Device $device, string $from, string $to): array
    {
        if ($device->use_push_mode) {
            $saved = AttendanceLog::where('device_serial', $device->serial_no)
                ->whereBetween('punch_time', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->count();

            return [
                'success' => true,
                'saved'   => $saved,
                'message' => "Push Mode: {$saved} attendance record(s) already in database for [{$device->name}] ({$from} → {$to}).",
            ];
        }

        $zk = $this->zk->connect($device);
        if (! $zk) {
            return ['success' => false, 'saved' => 0, 'message' => "Cannot connect to [{$device->name}] via TCP."];
        }

        $logs         = $this->zk->getAttendance($zk);
        $filteredLogs = $this->zk->filterAttendance($logs, $from, $to);
        $this->zk->disconnect($zk);

        $saved = 0;
        foreach ($filteredLogs as $log) {
            $punchTime = Carbon::parse($log['timestamp']);
            $pin       = (string) ($log['id'] ?? '');
            $resolved  = UserResolver::resolve($pin, $device);
            $criteria  = UserResolver::attendanceLogFields($pin, $resolved, $device->serial_no, $punchTime);

            AttendanceLog::firstOrCreate($criteria, [
                'user_type'     => $resolved['user_type'],
                'student_no'    => $resolved['student_no'],
                'teacher_no'    => $resolved['teacher_no'],
                'name'          => $resolved['name'],
                'device_id'     => $device->id,
                'device_serial' => $device->serial_no,
                'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
                'attendance_by' => 'fingerprint',
                'punch_type'    => match ((int) ($log['type'] ?? 0)) { 0 => 'IN', 1 => 'OUT', default => 'UNKNOWN' },
            ]);
            $saved++;
        }

        return ['success' => true, 'saved' => $saved, 'message' => "{$saved} attendance record(s) pulled from [{$device->name}]."];
    }

    /** @return array{total: int, messages: string[]} */
    public function pullAttendanceFromAllDevices(string $from, string $to, ?int $onlyDeviceId = null): array
    {
        $query = Device::where('status', 'active');
        if ($onlyDeviceId) {
            $query->where('id', $onlyDeviceId);
        }
        $devices = $query->get();

        $total    = 0;
        $messages = [];
        foreach ($devices as $device) {
            $result = $this->pullAttendance($device, $from, $to);
            $total += $result['saved'];
            $messages[] = ($result['success'] ? '✓ ' : '✗ ') . $result['message'];
        }

        return ['total' => $total, 'messages' => $messages, 'deviceCount' => $devices->count()];
    }

    // ─── Pull Users (device → DB, TCP only) ────────────────────────────────────

    /**
     * Reads the users already enrolled on the device and creates/updates the
     * matching Student or Teacher record — the reverse of Push. Push Mode
     * devices never send a full user list on demand, so this is TCP-only;
     * push-mode users are instead picked up automatically the first time
     * they punch (unrecognized PINs land in Unmatched Attendance).
     *
     * device_for = 'student' or 'teacher' → unambiguous PIN, always safe.
     * device_for = 'student_teacher'      → only auto-touches PINs that
     * already match an existing student_no/teacher_no; anything new is
     * reported "unclassified" for manual, one-click resolution.
     */
    public function pullUsers(Device $device): array
    {
        if ($device->use_push_mode) {
            return [
                'success' => false,
                'message' => 'Push Mode devices don\'t support a live "Pull Users" — the device never sends its full enrolled-user list on demand. New push-mode users are picked up automatically the first time they punch; unrecognized PINs show up under Unmatched Attendance for you to classify.',
            ];
        }

        $zk = $this->zk->connect($device);
        if (! $zk) {
            return ['success' => false, 'message' => "Cannot connect to [{$device->name}] via TCP."];
        }
        $deviceUsers = $this->zk->getUsers($zk);
        $this->zk->disconnect($zk);

        $createdStudents = 0;
        $createdTeachers = 0;
        $updatedTeachers = 0;
        $skipped         = 0;
        $unclassified    = [];

        foreach ($deviceUsers as $u) {
            $pin  = trim((string) ($u['userid'] ?? ''));
            $name = trim((string) ($u['name'] ?? ''));
            if ($pin === '') {
                continue;
            }

            $scope = $device->device_for;

            if ($scope === 'student') {
                if (! Student::where('student_no', $pin)->exists()) {
                    $student = new Student();
                    $student->student_no = $pin;
                    $student->firstname  = $name ?: "Student {$pin}";
                    $student->save();
                    $createdStudents++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if ($scope === 'teacher') {
                $teacher = Teacher::where('teacher_no', $pin)->first();
                if (! $teacher) {
                    $teacher = new Teacher();
                    $teacher->teacher_no = $pin;
                    $teacher->name       = $name ?: "Teacher {$pin}";
                    $teacher->save();
                    $createdTeachers++;
                } elseif ($name && $teacher->name !== $name) {
                    $teacher->name = $name;
                    $teacher->save();
                    $updatedTeachers++;
                } else {
                    $skipped++;
                }
                continue;
            }

            // Mixed device: only auto-touch PINs that already exist in one table.
            $existingStudent = Student::where('student_no', $pin)->first();
            $existingTeacher = Teacher::where('teacher_no', $pin)->first();

            if ($existingStudent && $existingTeacher) {
                $unclassified[] = ['pin' => $pin, 'name' => $name, 'reason' => 'PIN exists as BOTH a student and a teacher — needs manual review'];
                continue;
            }
            if ($existingStudent) {
                $skipped++;
                continue;
            }
            if ($existingTeacher) {
                if ($name && $existingTeacher->name !== $name) {
                    $existingTeacher->name = $name;
                    $existingTeacher->save();
                    $updatedTeachers++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $unclassified[] = ['pin' => $pin, 'name' => $name, 'reason' => 'New PIN not yet in your Student or Teacher list'];
        }

        return [
            'success'          => true,
            'createdStudents'  => $createdStudents,
            'createdTeachers'  => $createdTeachers,
            'updatedTeachers'  => $updatedTeachers,
            'skipped'          => $skipped,
            'unclassified'     => $unclassified,
            'totalOnDevice'    => count($deviceUsers),
        ];
    }

    // ─── Remove all users from a device ────────────────────────────────────────

    public function removeAllUsers(Device $device): array
    {
        if ($device->use_push_mode) {
            DeviceCommand::queue($device->id, 'DATA CLEAR USERINFO');
            return ['success' => true, 'message' => 'Command queued: all users will be removed from device on next sync.'];
        }

        $zk = $this->zk->connect($device);
        if (! $zk) {
            return ['success' => false, 'message' => 'Device not connected or connection failed!'];
        }
        try {
            $zk->clearUsers();
            $this->zk->disconnect($zk);
            return ['success' => true, 'message' => 'All users removed from device successfully.'];
        } catch (\Throwable) {
            return ['success' => false, 'message' => 'Connection failed while clearing users.'];
        }
    }
}
