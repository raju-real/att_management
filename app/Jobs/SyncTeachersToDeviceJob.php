<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceCommand;
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
        $teachers = Teacher::all();

        foreach ($devices as $device) {
            Log::info("Syncing teachers to device: {$device->name}");

            // ── Push Mode ──────────────────────────────────────────────────
            if ($device->use_push_mode) {
                $queued = 0;
                foreach ($teachers as $teacher) {
                    $name = $teacher->name ?: 'Teacher';
                    DeviceCommand::queue(
                        $device->id,
                        DeviceCommand::setUserCommand((string) $teacher->teacher_no, $name)
                    );
                    $queued++;
                }
                Log::info("Queued {$queued} SET USER commands for push-mode device: {$device->name}");
                continue;
            }

            // ── TCP Mode ───────────────────────────────────────────────────
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

            $zkService->disconnect($zk);
            Log::info("Finished syncing teachers to device (TCP): {$device->name}");
        }

        Log::info("Job: SyncTeachersToDeviceJob finished.");
    }
}
