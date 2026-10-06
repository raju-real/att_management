<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\User;
use Jmrashed\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Log;

class SyncZkUsers extends Command
{
    /**
     * php artisan zkteco:sync-users
     * php artisan zkteco:sync-users --direction=device
     * php artisan zkteco:sync-users --direction=db
     * Scheduler (Background Sync) app/Console/Kernel.php
     * $schedule->command('zkteco:sync-users')->everyTenMinutes()->withoutOverlapping()->runInBackground();
     * * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1 (on server)
     */
    protected $signature = 'zkteco:sync-users {--direction=both} {--user-type= : student, teacher, or leave empty for both}';
    protected $description = 'Sync users between ZKTeco devices and database';
    protected $device_for = '';

    public function handle()
    {
        $direction = $this->option('direction'); // db, device, both
        $userType  = $this->option('user-type');  // student, teacher, or null = both

        $devices = Device::active()->get();

        if ($devices->isEmpty()) {
            $this->warn('No active devices found');
            return;
        }

        foreach ($devices as $device) {
            $this->info("🔄 Processing Device: {$device->serial_no}");

            try {
                $zk = new ZKTeco($device->ip_address, $device->device_port);

                if (!$zk->connect()) {
                    $this->error("❌ Cannot connect to {$device->serial_no}");
                    continue;
                }

                if ($direction === 'device' || $direction === 'both') {
                    $this->syncFromDeviceToDb($zk);
                }

                if ($direction === 'db' || $direction === 'both') {
                    $this->syncFromDbToDevice($zk, $device->device_for, $userType);
                }

                $zk->disconnect();
            } catch (\Exception $e) {
                Log::error('ZKTeco Sync Error', [
                    'device' => $device->serial_no,
                    'error'  => $e->getMessage(),
                ]);
                $this->error("❌ Error on device {$device->serial_no}: " . $e->getMessage());
            }
        }

        $this->info('✅ ZKTeco user sync completed');
    }

    /**
     * Sync users FROM DEVICE → DATABASE
     */
    protected function syncFromDeviceToDb(ZKTeco $zk)
    {
        $deviceUsers = $zk->getUser();
        dd($deviceUsers);
        foreach ($deviceUsers as $dUser) {
            if (empty($dUser['userid'])) {
                continue;
            }
            dd($dUser);
            $userData = [
                'name' => $dUser['name'] ?? 'Unknown',
                'email' => $dUser['userid'] . '@mail.com',
                'password_plain' => 'Pa$$w0rd!',
                'password' => bcrypt('Pa$$w0rd!'),
                'status' => 'active',
                'created_by' => 0
            ];

            User::firstOrCreate(['employee_id' => $dUser['userid']], $userData);
        }

        $this->info('   ✔ Synced users FROM device to DB');
    }

    /**
     * Sync users FROM DATABASE → DEVICE (INSERT AND UPDATE)
     */
    protected function syncFromDbToDevice(ZKTeco $zk, $device_for, ?string $userType = null)
    {
        // Get current users already on the device for insert/update detection
        $deviceUsers = collect($zk->getUser() ?? [])->keyBy('userid');

        // ---------------------------
        // 1️⃣ Sync Students
        // ---------------------------
        $pushStudents = ($userType === null || $userType === 'student')
            && ($device_for === 'student_teacher' || $device_for === 'student');

        if ($pushStudents) {
            $students = Student::select('student_no', 'firstname', 'middlename', 'lastname')->get();

            foreach ($students as $student) {
                $deviceUserId = (string)$student->student_no;
                $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);
                $zk->setUser((int)$student->student_no, $deviceUserId, $name, '', 0);

                $this->info($deviceUsers->has($deviceUserId)
                    ? "   🔄 Updated student {$deviceUserId} on device"
                    : "   ➕ Added student {$deviceUserId} to device");
            }
            $this->info('   ✔ Students synced to device');
        }

        // ---------------------------
        // 2️⃣ Sync Teachers
        // ---------------------------
        $pushTeachers = ($userType === null || $userType === 'teacher')
            && ($device_for === 'student_teacher' || $device_for === 'teacher');

        if ($pushTeachers) {
            $teachers = Teacher::select('teacher_no', 'name')->get();

            foreach ($teachers as $teacher) {
                $deviceUserId = (string)$teacher->teacher_no;
                $zk->setUser((int)$teacher->teacher_no, $deviceUserId, $teacher->name ?? 'Unknown', '', 0);

                $this->info($deviceUsers->has($deviceUserId)
                    ? "   🔄 Updated teacher {$deviceUserId} on device"
                    : "   ➕ Added teacher {$deviceUserId} to device");
            }
            $this->info('   ✔ Teachers synced to device');
        }
    }

    /**
     * Sync users FROM DATABASE → DEVICE (INSERT ONLY)
     */
    protected function syncFromDbToDeviceInsertOnly(ZKTeco $zk)
    {
        $deviceUsers = collect($zk->getUser() ?? [])
            ->pluck('userid')
            ->toArray();

        $students = Student::select('std_no', 'student_id', 'firstname', 'middlename', 'lastname')
            ->whereIn('std_no', [10001, 10002, 10003, 10004, 10005, 10006, 10007, 10008, 10009, 10010])
            ->get();

        foreach ($students as $student) {

            // Already exists on device
            if (in_array((string)$student->std_no, $deviceUsers)) {
                continue;
            }

            $name = showStudentFullName(
                $student->firstname,
                $student->middlename,
                $student->lastname
            );
            $deviceUserId = 'S' . $student->std_no;
            $zk->setUser(
                $student->std_no,  // UID (must be numeric & unique)
                $deviceUserId,  // USERID (string OK) 'S' for student and 'T' for teacher
                $name,
                '',
                0
            );

            $this->info("   ➕ Added student {$student->std_no} to device");
        }

        $this->info('   ✔ Synced users FROM DB to device');
    }
}
