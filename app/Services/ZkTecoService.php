<?php

namespace App\Services;

use App\Models\Device;
use Carbon\Carbon;
use Jmrashed\Zkteco\Lib\ZKTeco;

class ZkTecoService
{
    public function connect(Device $device): ?ZKTeco
    {
        $ip = trim($device->ip_address);
        if (empty($ip)) {
            return null;
        }
        $zk = new ZKTeco($ip, $device->device_port);
        return $zk->connect() ? $zk : null;
    }

    public function disconnect(ZKTeco $zk): void
    {
        $zk->disconnect();
    }

    /* ================= USERS ================= */

    public function getUsers(ZKTeco $zk): array
    {
        return $zk->getUser() ?? [];
    }

    public function findUidByUserId(ZKTeco $zk, string $userId): ?int
    {
        $users = $zk->getUser() ?? [];

        foreach ($users as $user) {
            if (($user['userid'] ?? null) === $userId) {
                return (int)$user['uid'];
            }
        }

        return null;
    }


    public function pushUser(ZKTeco $zk, string $employeeId, string $name): void
    {
        $zk->setUser($employeeId, $employeeId, $name, '', 0);
    }

    /**
     * Soft delete = overwrite user
     */
    public function deleteUser(ZKTeco $zk, int $uid): bool
    {
        return $zk->removeUser($uid);
    }


    /* ================= ATTENDANCE ================= */

    public function getAttendance(ZKTeco $zk): array
    {
        return $zk->getAttendance() ?? [];
    }

    public function clearAttendance(ZKTeco $zk): void
    {
        $zk->clearAttendance();
    }

    /**
     * Filter attendance logs by date & employee
     */
    public function filterAttendance(array $logs, string $from, string $to, ?string $employeeId = null): array
    {
        return array_filter($logs, function ($log) use ($from, $to, $employeeId) {
            $date = Carbon::parse($log['timestamp'])->toDateString();

            if ($date < $from || $date > $to) {
                return false;
            }

            if ($employeeId && $log['id'] != $employeeId) {
                return false;
            }

            return true;
        });
    }
}
