<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Teacher;
use App\Services\ZkTecoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTeachersToDeviceJob implements ShouldQueue
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
        Log::info("Job: SyncTeachersToDeviceJob started.");

        $devices = Device::query()
            ->where('status', 'active')
            ->whereIn('device_for', ['teacher', 'student_teacher'])
            ->get();

        if ($devices->isEmpty()) {
            Log::warning("Job: No active devices found for teachers.");
            return;
        }

        // Fetch all teachers - adjust query if needed (e.g. only active)
        $teachers = Teacher::all(); // Assuming all teachers should be synced

        foreach ($devices as $device) {
            Log::info("Syncing teachers to device: {$device->name} ({$device->ip_address})");

            $zk = $zkService->connect($device);
            if (!$zk) {
                Log::error("Failed to connect to device: {$device->name}");
                continue;
            }

            foreach ($teachers as $teacher) {
                try {
                    $uid = (int)$teacher->teacher_no;
                    $userId = (string)$teacher->teacher_no;
                    $name = $teacher->name ?? 'Unknown';

                    // setUser(uid, userid, name, password, role)
                    $zk->setUser($uid, $userId, $name, '', 0);
                } catch (\Exception $e) {
                    Log::error("Failed to push teacher {$teacher->teacher_no} to {$device->name}: " . $e->getMessage());
                }
            }

            $zkService->disconnect($zk); // Ensure clean disconnect
            Log::info("Finished syncing teachers to device: {$device->name}");
        }

        Log::info("Job: SyncTeachersToDeviceJob finished.");
    }
}
