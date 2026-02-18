<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Student;
use App\Services\ZkTecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStudentsToDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZkTecoService $zkService)
    {
        Log::info("Job: SyncStudentsToDeviceJob started.");

        $devices = Device::query()
            ->where('status', 'active')
            ->whereIn('device_for', ['student', 'student_teacher'])
            ->get();

        if ($devices->isEmpty()) {
            Log::warning("Job: No active devices found for students.");
            return;
        }

        // Fetch all students - adjust query if needed (e.g. only active)
        $students = Student::all();

        foreach ($devices as $device) {
            Log::info("Syncing students to device: {$device->name} ({$device->ip_address})");

            $zk = $zkService->connect($device);
            if (!$zk) {
                Log::error("Failed to connect to device: {$device->name}");
                continue;
            }

            foreach ($students as $student) {
                try {
                    $uid = (int)$student->student_no;
                    $userId = (string)$student->student_no;
                    $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);

                    // setUser(uid, userid, name, password, role)
                    $zk->setUser($uid, $userId, $name, '', 0);
                } catch (\Exception $e) {
                    Log::error("Failed to push student {$student->student_no} to {$device->name}: " . $e->getMessage());
                }
            }

            $zkService->disconnect($zk); // Ensure clean disconnect
            Log::info("Finished syncing students to device: {$device->name}");
        }

        Log::info("Job: SyncStudentsToDeviceJob finished.");
    }
}
