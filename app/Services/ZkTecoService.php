<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use Carbon\Carbon;
use Jmrashed\Zkteco\Lib\ZKTeco;

/**
 * ZkTecoService — Dual-Mode Device Communication
 *
 * Mode 1 — TCP/UDP (Direct connect, LAN only)
 *   Works only when the web server has a direct UDP socket path to the device.
 *   Fails on shared hosting (sockets blocked) and across subnets without routing.
 *
 * Mode 2 — iClock / PUSH (HTTP, device connects to server)
 *   Device polls /iclock/getrequest, pushes attendance via /iclock/cdata.
 *   Works on shared hosting, across subnets, over the internet.
 *   Commands are queued in the device_commands table.
 */
class ZkTecoService
{
    // ─── Mode Detection ────────────────────────────────────────────────────────

    /**
     * True when the device should use TCP/UDP direct connect.
     * Returns false if push mode is enabled OR if sockets are unavailable.
     */
    public function canUseTcp(Device $device): bool
    {
        if ($device->use_push_mode) {
            return false;
        }

        // Check if PHP sockets are available (disabled on shared hosting)
        if (! function_exists('socket_create')) {
            return false;
        }

        if (empty(trim($device->ip_address ?? ''))) {
            return false;
        }

        return true;
    }

    // ─── TCP/UDP Connect ───────────────────────────────────────────────────────

    /**
     * Connect to the device via TCP/UDP.
     * Returns null if connection fails or sockets are unavailable.
     *
     * The underlying library hardcodes a 60.5s socket receive timeout, which
     * makes an unreachable device hang every "Test Connection" click (and any
     * other TCP action) for a full minute before failing. We override it to
     * something reasonable right after the socket is created — short for a
     * quick reachability check, a bit longer for real data pulls that may
     * need to wait on a larger response.
     */
    public function connect(Device $device, int $timeoutSeconds = 8): ?ZKTeco
    {
        if (! $this->canUseTcp($device)) {
            return null;
        }

        try {
            $ip = trim($device->ip_address);
            $port = (int) ($device->device_port ?: 4370);
            $zk = new ZKTeco($ip, $port);
            if (isset($zk->_zkclient) && $zk->_zkclient) {
                socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeoutSeconds, 'usec' => 0]);
            }
            return $zk->connect() ? $zk : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function disconnect(ZKTeco $zk): void
    {
        try {
            $zk->disconnect();
        } catch (\Throwable) {
            // ignore
        }
    }

    // ─── Users (TCP mode) ──────────────────────────────────────────────────────

    public function getUsers(ZKTeco $zk): array
    {
        return $zk->getUser() ?? [];
    }

    public function findUidByUserId(ZKTeco $zk, string $userId): ?int
    {
        foreach ($zk->getUser() ?? [] as $user) {
            if (($user['userid'] ?? null) === $userId) {
                return (int) $user['uid'];
            }
        }
        return null;
    }

    public function pushUser(ZKTeco $zk, string $employeeId, string $name): void
    {
        $zk->setUser($employeeId, $employeeId, $name, '', 0);
    }

    public function deleteUser(ZKTeco $zk, int $uid): bool
    {
        return $zk->removeUser($uid);
    }

    // ─── Users (Push/iClock mode) ──────────────────────────────────────────────

    /**
     * Queue a "set user" command for push-mode device.
     * The device will pick it up on its next /iclock/getrequest poll.
     */
    public function queueSetUser(Device $device, string $pin, string $name, string $card = ''): void
    {
        DeviceCommand::queue(
            $device->id,
            DeviceCommand::setUserCommand($pin, $name, $card)
        );
    }

    /**
     * Queue a "delete user" command for push-mode device.
     */
    public function queueDeleteUser(Device $device, string $pin): void
    {
        DeviceCommand::queue(
            $device->id,
            DeviceCommand::deleteUserCommand($pin)
        );
    }

    /**
     * Queue a "clear attendance" command for push-mode device.
     */
    public function queueClearAttendance(Device $device): void
    {
        DeviceCommand::queue($device->id, 'CLEAR ATTLOG');
    }

    // ─── Attendance (TCP mode) ─────────────────────────────────────────────────

    public function getAttendance(ZKTeco $zk): array
    {
        return $zk->getAttendance() ?? [];
    }

    public function clearAttendance(ZKTeco $zk): void
    {
        $zk->clearAttendance();
    }

    /**
     * Filter attendance logs by date range and optional employee ID.
     * (Device returns ALL logs — filtering happens here in PHP.)
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

    // ─── Connection Test ───────────────────────────────────────────────────────

    /**
     * Test the connection for both modes.
     * Returns ['success' => bool, 'message' => string]
     */
    public function testConnection(Device $device): array
    {
        // ── Push mode: check last heartbeat ──────────────────────────────────
        if ($device->use_push_mode) {
            if (! $device->last_seen_at) {
                return [
                    'success' => false,
                    'message' => 'Device has never contacted the server. Configure the Cloud Server / ADMS settings on the device.',
                ];
            }

            $minutesAgo = $device->last_seen_at->diffInMinutes(now());

            if ($minutesAgo <= 5) {
                return [
                    'success' => true,
                    'message' => "Device is online. Last heartbeat: {$device->last_seen_at->diffForHumans()}.",
                ];
            }

            return [
                'success' => false,
                'message' => "Device last seen {$device->last_seen_at->diffForHumans()}. It may be offline or misconfigured.",
            ];
        }

        // ── TCP mode: try UDP socket connect ─────────────────────────────────
        if (! function_exists('socket_create')) {
            return [
                'success' => false,
                'message' => 'TCP/UDP sockets are not available on this server. Enable "Push Mode" for this device.',
            ];
        }

        $start = microtime(true);
        $zk = $this->connect($device, 4); // short timeout — this is just a reachability check
        $elapsed = round(microtime(true) - $start, 1);

        if ($zk) {
            $this->disconnect($zk);
            return ['success' => true, 'message' => "Device connected successfully via TCP/UDP ({$elapsed}s)."];
        }

        return [
            'success' => false,
            'message' => "Connection failed after {$elapsed}s. The device didn't answer on {$device->ip_address}:" . ($device->device_port ?: 4370)
                . '. Check: device is powered on, IP/port are correct, and the device menu has TCP/IP communication enabled (some firmware disables it while in ADMS/Cloud Server mode).',
        ];
    }
}
