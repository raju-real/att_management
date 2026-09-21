<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;

/**
 * Keeps ONE student/teacher's PIN+name in sync with every device that
 * should have them, right when that record is created, edited, or deleted —
 * so an admin never has to remember to click "Push to Device" again after a
 * single edit. Bulk push (all students/teachers to all devices) still exists
 * separately for first-time setup or recovery.
 *
 * Push Mode  → queues a command; the device picks it up on its next poll.
 * TCP Mode   → attempted immediately over the socket, best-effort (a device
 *              that's offline or unreachable is skipped, never fatal).
 */
class DeviceSyncService
{
    public function __construct(protected ZkTecoService $zk)
    {
    }

    /**
     * @param string $group 'student' or 'teacher'
     * @return string[] one status line per device touched
     */
    public function pushOne(string $group, string $pin, string $name): array
    {
        $pin = trim($pin);
        if ($pin === '') {
            return [];
        }

        $results = [];

        foreach ($this->devicesFor($group) as $device) {
            if ($device->use_push_mode) {
                DeviceCommand::queue($device->id, DeviceCommand::setUserCommand($pin, $name ?: ucfirst($group)));
                $results[] = "{$device->name}: queued (syncs within ~30s)";
                continue;
            }

            try {
                $conn = $this->zk->connect($device);
                if (! $conn) {
                    $results[] = "{$device->name}: unreachable, skipped";
                    continue;
                }
                $this->zk->pushUser($conn, $pin, $name ?: ucfirst($group));
                $this->zk->disconnect($conn);
                $results[] = "{$device->name}: pushed";
            } catch (\Throwable) {
                $results[] = "{$device->name}: push failed, skipped";
            }
        }

        return $results;
    }

    /**
     * @param string $group 'student' or 'teacher'
     * @return string[] one status line per device touched
     */
    public function deleteOne(string $group, string $pin): array
    {
        $pin = trim($pin);
        if ($pin === '') {
            return [];
        }

        $results = [];

        foreach ($this->devicesFor($group) as $device) {
            if ($device->use_push_mode) {
                $this->zk->queueDeleteUser($device, $pin);
                $results[] = "{$device->name}: delete queued";
                continue;
            }

            try {
                $conn = $this->zk->connect($device);
                if (! $conn) {
                    $results[] = "{$device->name}: unreachable, skipped";
                    continue;
                }
                $uid = $this->zk->findUidByUserId($conn, $pin);
                if ($uid !== null) {
                    $this->zk->deleteUser($conn, $uid);
                    $results[] = "{$device->name}: deleted";
                } else {
                    $results[] = "{$device->name}: not found on device";
                }
                $this->zk->disconnect($conn);
            } catch (\Throwable) {
                $results[] = "{$device->name}: delete failed, skipped";
            }
        }

        return $results;
    }

    protected function devicesFor(string $group)
    {
        return Device::where('status', 'active')
            ->whereIn('device_for', [$group, 'student_teacher'])
            ->get();
    }
}
