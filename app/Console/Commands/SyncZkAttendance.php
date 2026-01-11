<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\User;
use Jmrashed\Zkteco\Lib\ZKTeco;
use Carbon\Carbon;

class SyncZkAttendance extends Command
{
    /**
     * php artisan zk:sync-attendance
     * php artisan zk:sync-attendance --date=2025-01-15
     * php artisan zk:sync-attendance --from=2025-01-01 --to=2025-01-31
     * php artisan zk:sync-attendance --device=ZK-123456
     * php artisan zk:sync-attendance --device=ZK-123456 --date=2025-01-15
     */
    protected $signature = 'zk:sync-attendance
                            {--date= : YYYY-MM-DD}
                            {--from= : YYYY-MM-DD}
                            {--to= : YYYY-MM-DD}
                            {--device= : Device serial number}';

    protected $description = 'Sync attendance from ZKTeco devices';

    public function handle()
    {
        $this->info('🔄 Starting ZKTeco attendance sync...');

        $date = $this->option('date');
        $from = $this->option('from');
        $to = $this->option('to');
        $serial = $this->option('device');

        // Default = today
        if (!$date && !$from && !$to) {
            $from = $to = Carbon::today()->toDateString();
        }

        if ($date) {
            $from = $to = $date;
        }

        $devices = Device::query()
            ->when($serial, fn($q) => $q->where('serial_no', $serial))
            ->active()
            ->get();

        if ($devices->isEmpty()) {
            $this->error('❌ No devices found');
            return Command::FAILURE;
        }

        foreach ($devices as $device) {
            $this->syncDevice($device, $from, $to);
        }

        $this->info('✅ Attendance sync completed.');
        return Command::SUCCESS;
    }

    protected function syncDevice(Device $device, string $from, string $to)
    {
        $this->line("📡 Connecting device: {$device->serial_no}");

        try {
            $zk = new ZKTeco($device->ip_address, $device->device_port);

            if (!$zk->connect()) {
                $this->error("❌ Failed to connect {$device->serial_no}");
                return;
            }

            $logs = $zk->getAttendance();
            $zk->disconnect();

            if (!$logs) {
                $this->warn("⚠ No logs from {$device->serial_no}");
                return;
            }

            foreach ($logs as $log) {

                $punchTime = Carbon::parse($log['timestamp']);
                $userId = $log['id'] ?? null; // S10001
                if (!$userId) {
                    continue;
                }

                // Filter date range
                if ($punchTime->toDateString() < $from || $punchTime->toDateString() > $to) {
                    continue;
                }

                $data = [
                    'device_id' => $device->id,
                    'device_serial' => $device->serial_no,
                    'punch_time' => $punchTime,
                    'attendance_by' => 'fingerprint',
                    'client_ip' => $device->ip_address ?? '',
                    'punch_type' => $this->mapPunchType($log['type'] ?? null),
                ];

                // ----------------------
                // STUDENT
                // PREVIOUS if (str_starts_with($userId, 'S'))
                // ----------------------
                if ($userId > 1000) {

                    $studentNo = $userId; // 10001

                    $student = Student::where('student_no', $studentNo)->first();

                    if (!$student) {
                        $this->warn("⚠ Student not found: {$studentNo}");
                        continue;
                    }

                    AttendanceLog::firstOrCreate(
                        [
                            'student_no' => $student->student_no,
                            'student_id' => $student->student_id,
                            'name' => showStudentFullName($student->firstname, $student->middlename, $student->lastname) ?? null,
                            'device_id' => $device->id,
                            'punch_time' => $punchTime,
                        ],
                        $data + [
                            'user_type' => 'student',
                        ]
                    );

                    continue;
                }

                // ----------------------
                // TEACHER
                // PREVIOUS  if (str_starts_with($userId, 'T'))
                // ----------------------
                if ($userId < 1000) {

                    $teacherNo = $userId; // 101
                    $teacher = Teacher::whereTeacherNo($teacherNo)->first();
                    AttendanceLog::firstOrCreate(
                        [
                            'teacher_no' => $teacherNo,
                            'name' => $teacher->name ?? null,
                            'device_id' => $device->id,
                            'punch_time' => $punchTime,
                        ],
                        $data + [
                            'user_type' => 'teacher',
                        ]
                    );
                }
            }

            $this->info("✔ Synced device {$device->serial_no}");

        } catch (\Throwable $e) {
            $this->error("❌ {$device->serial_no}: {$e->getMessage()}");
        }
    }


    protected function mapPunchType($type): string
    {
        return match ((int)$type) {
            0 => 'IN',
            1 => 'OUT',
            default => 'UNKNOWN',
        };
    }
}
