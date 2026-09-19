<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ZkTecoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeviceActivityController extends Controller
{
    protected ZkTecoService $zkService;

    public function __construct(ZkTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    // ─── Dashboard ─────────────────────────────────────────────────────────────

    /**
     * Activities dashboard
     */
    public function index()
    {
        return view('devices.activities', [
            'devices' => Device::where('status', 'active')->get(),
        ]);
    }

    /* ==========================================================
     | USERS SYNC
     |========================================================== */

    /**
     * Sync users between DB and device(s).
     * direction = device_to_db | db_to_device | both
     *
     * Push mode : queues iClock SET USER commands (db_to_device)
     *             or reads from attendance_logs user data (device_to_db N/A for push)
     * TCP mode  : direct socket communication
     */
    public function syncUsers(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|exists:users,employee_id',
            'from_date'   => 'nullable|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'direction'   => 'required|in:device_to_db,db_to_device,both',
        ]);

        if (! $request->from_date && $request->to_date) {
            return back()->withErrors('From date is required when using To date');
        }

        $devices = $this->getDevices($request->device);
        $queued  = 0;
        $synced  = 0;

        foreach ($devices as $device) {

            // ── PUSH MODE ────────────────────────────────────────────────────
            if ($device->use_push_mode) {
                if (in_array($request->direction, ['db_to_device', 'both'])) {
                    $queued += $this->queueUsersToDevice($device, $request);
                }
                // device_to_db in push mode: users are already stored when device
                // pushes OPERLOG/USERINFO records via /iclock/cdata.
                // For now this direction is a no-op (data is already in DB from push).
                continue;
            }

            // ── TCP MODE ─────────────────────────────────────────────────────
            $zk = $this->zkService->connect($device);
            if (! $zk) continue;

            if (in_array($request->direction, ['device_to_db', 'both'])) {
                $synced += $this->syncUsersFromDevice($zk);
            }

            if (in_array($request->direction, ['db_to_device', 'both'])) {
                $this->syncUsersToDevice($zk, $request);
            }

            $this->zkService->disconnect($zk);
        }

        $message = 'User sync completed successfully.';
        if ($queued > 0) {
            $message .= " {$queued} command(s) queued for push-mode device(s).";
        }

        return back()->with('user_sync', $message);
    }

    /** Pull all users from device into DB (TCP mode) */
    protected function syncUsersFromDevice($zk): int
    {
        $count = 0;
        foreach ($this->zkService->getUsers($zk) as $u) {
            // Try to match student first
            $pin  = $u['userid'] ?? '';
            $name = $u['name'] ?? '';

            // Update or create student by student_no
            if (! Student::where('student_no', $pin)->exists()
                && ! Teacher::where('teacher_no', $pin)->exists()) {
                // Unknown user — we can't create without knowing user type
                // Log for review instead
                \Log::info("ZkTeco device returned unknown user PIN={$pin} Name={$name}");
            }

            $count++;
        }
        return $count;
    }

    /** Push DB users to device via TCP/UDP socket */
    protected function syncUsersToDevice($zk, Request $request): void
    {
        // Students
        $studentsQuery = Student::query()
            ->when($request->from_date, function ($q) use ($request) {
                if (! $request->to_date) {
                    $q->whereDate('created_at', $request->from_date);
                } else {
                    $q->whereBetween('created_at', [
                        Carbon::parse($request->from_date)->startOfDay(),
                        Carbon::parse($request->to_date)->endOfDay(),
                    ]);
                }
            });

        foreach ($studentsQuery->get() as $student) {
            $name = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
            $this->zkService->pushUser($zk, (string) $student->student_no, $name ?: 'Student');
        }

        // Teachers
        $teachersQuery = Teacher::query()
            ->when($request->from_date, function ($q) use ($request) {
                if (! $request->to_date) {
                    $q->whereDate('created_at', $request->from_date);
                } else {
                    $q->whereBetween('created_at', [
                        Carbon::parse($request->from_date)->startOfDay(),
                        Carbon::parse($request->to_date)->endOfDay(),
                    ]);
                }
            });

        foreach ($teachersQuery->get() as $teacher) {
            $this->zkService->pushUser($zk, (string) $teacher->teacher_no, $teacher->name ?? 'Teacher');
        }
    }

    /**
     * Queue SET USER commands for push-mode devices.
     * Returns number of commands queued.
     */
    protected function queueUsersToDevice(Device $device, Request $request): int
    {
        $count = 0;

        // Students
        $studentsQuery = Student::query()
            ->when($request->from_date, function ($q) use ($request) {
                if (! $request->to_date) {
                    $q->whereDate('created_at', $request->from_date);
                } else {
                    $q->whereBetween('created_at', [
                        Carbon::parse($request->from_date)->startOfDay(),
                        Carbon::parse($request->to_date)->endOfDay(),
                    ]);
                }
            });

        foreach ($studentsQuery->get() as $student) {
            $name = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
            DeviceCommand::queue(
                $device->id,
                DeviceCommand::setUserCommand((string) $student->student_no, $name ?: 'Student')
            );
            $count++;
        }

        // Teachers
        $teachersQuery = Teacher::query()
            ->when($request->from_date, function ($q) use ($request) {
                if (! $request->to_date) {
                    $q->whereDate('created_at', $request->from_date);
                } else {
                    $q->whereBetween('created_at', [
                        Carbon::parse($request->from_date)->startOfDay(),
                        Carbon::parse($request->to_date)->endOfDay(),
                    ]);
                }
            });

        foreach ($teachersQuery->get() as $teacher) {
            DeviceCommand::queue(
                $device->id,
                DeviceCommand::setUserCommand((string) $teacher->teacher_no, $teacher->name ?? 'Teacher')
            );
            $count++;
        }

        return $count;
    }


    /* ==========================================================
     | ATTENDANCE SYNC
     |========================================================== */

    /**
     * Sync attendance from device.
     * Default: today.
     *
     * Push mode : reads from attendance_logs already stored by iClock push.
     * TCP mode  : pulls all logs from device, filters, stores.
     */
    public function syncAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable',
            'from'        => 'nullable|date',
            'to'          => 'nullable|date|after_or_equal:from',
            'device'      => 'nullable|string',
        ]);

        if (! $request->from && $request->to) {
            return back()->withErrors('From date is required when using To date');
        }

        $from    = $request->from ? Carbon::parse($request->from)->toDateString() : Carbon::today()->toDateString();
        $to      = $request->to   ? Carbon::parse($request->to)->toDateString()   : $from;
        $devices = $this->getDevices($request->device);
        $saved   = 0;

        foreach ($devices as $device) {

            // ── PUSH MODE ────────────────────────────────────────────────────
            // Attendance is already in the DB (pushed by device via /iclock/cdata).
            // "Sync" here just means counting what we have for this date range.
            if ($device->use_push_mode) {
                $count = AttendanceLog::where('device_serial', $device->serial_no)
                    ->whereBetween('punch_time', [
                        Carbon::parse($from)->startOfDay(),
                        Carbon::parse($to)->endOfDay(),
                    ])
                    ->count();
                $saved += $count;
                continue;
            }

            // ── TCP MODE ─────────────────────────────────────────────────────
            $zk = $this->zkService->connect($device);
            if (! $zk) continue;

            $logs         = $this->zkService->getAttendance($zk);
            $filteredLogs = $this->zkService->filterAttendance($logs, $from, $to, $request->employee_id);

            foreach ($filteredLogs as $log) {
                $punchTime = Carbon::parse($log['timestamp']);
                $userId    = (string) ($log['id'] ?? '');

                // Identify student or teacher
                $student = Student::where('student_no', $userId)->first();
                $teacher = ! $student ? Teacher::where('teacher_no', $userId)->first() : null;

                $studentNo = $student ? (string) $student->student_no : null;
                $teacherNo = $teacher ? (string) $teacher->teacher_no : null;
                $userType  = $student ? 'student' : ($teacher ? 'teacher' : null);
                $name      = $student ? trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''))
                           : ($teacher ? $teacher->name : null);

                $punchType = $this->mapPunchType($log['type'] ?? null);

                // Build unique criteria
                $criteria = [
                    'device_serial' => $device->serial_no,
                    'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
                ];
                if ($studentNo) {
                    $criteria['student_no'] = $studentNo;
                } elseif ($teacherNo) {
                    $criteria['teacher_no'] = $teacherNo;
                } else {
                    $criteria['student_no'] = $userId; // unknown fallback
                }

                AttendanceLog::firstOrCreate($criteria, [
                    'user_type'     => $userType,
                    'student_no'    => $studentNo,
                    'teacher_no'    => $teacherNo,
                    'name'          => $name,
                    'device_id'     => $device->id,
                    'device_serial' => $device->serial_no,
                    'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
                    'attendance_by' => 'fingerprint',
                    'punch_type'    => $punchType,
                ]);
                $saved++;
            }

            $this->zkService->disconnect($zk);
        }

        return back()->with('success', "Attendance synced successfully. {$saved} record(s) processed.");
    }


    /* ==========================================================
     | DEVICE COMMANDS
     |========================================================== */

    /**
     * Clear all attendance logs from device.
     *
     * Push mode  → queues a CLEAR ATTLOG command.
     * TCP mode   → clears immediately via socket.
     */
    public function clearAttendance(Request $request)
    {
        $request->validate(['device' => 'required|string']);

        $device = Device::where('serial_no', $request->device)->firstOrFail();

        // ── Push mode ────────────────────────────────────────────────────────
        if ($device->use_push_mode) {
            $this->zkService->queueClearAttendance($device);
            return back()->with('warning', 'Command queued: device will clear attendance on next sync.');
        }

        // ── TCP mode ─────────────────────────────────────────────────────────
        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return back()->withErrors('Device not reachable');
        }

        $this->zkService->clearAttendance($zk);
        $this->zkService->disconnect($zk);

        return back()->with('warning', 'All attendance cleared from device.');
    }

    /**
     * Remove a user from device.
     *
     * Push mode  → queues a DELETE USERINFO command.
     * TCP mode   → removes immediately via socket.
     */
    public function deleteUserFromDevice(Request $request)
    {
        $request->validate([
            'pin'    => 'required|string',   // student_no or teacher_no
            'device' => 'required|string',
        ]);

        $device = Device::where('serial_no', $request->device)->firstOrFail();

        // ── Push mode ────────────────────────────────────────────────────────
        if ($device->use_push_mode) {
            $this->zkService->queueDeleteUser($device, $request->pin);
            return back()->with('info', 'Command queued: user will be removed from device on next sync.');
        }

        // ── TCP mode ─────────────────────────────────────────────────────────
        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return back()->withErrors('Device not reachable');
        }

        $uid = $this->zkService->findUidByUserId($zk, $request->pin);

        if ($uid !== null) {
            $this->zkService->deleteUser($zk, $uid);
            $this->zkService->disconnect($zk);
            return back()->with('info', 'User removed from device.');
        }

        $this->zkService->disconnect($zk);
        return back()->with('warning', 'User not found on device.');
    }

    /* ==========================================================
     | HELPERS
     |========================================================== */

    protected function getDevices(?string $serial)
    {
        return Device::query()
            ->when($serial, fn($q) => $q->where('serial_no', $serial))
            ->where('status', 'active')
            ->get();
    }

    protected function mapPunchType($type): string
    {
        return match ((int) $type) {
            0       => 'IN',
            1       => 'OUT',
            default => 'UNKNOWN',
        };
    }
}
