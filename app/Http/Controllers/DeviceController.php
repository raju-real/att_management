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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    protected ZkTecoService $zkService;

    public function __construct(ZkTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    // ─── CRUD ──────────────────────────────────────────────────────────────────

    public function index()
    {
        $devices = Device::latest()->paginate(20);
        return view('configuration.device_list', compact('devices'));
    }

    public function create()
    {
        $route = route('devices.store');
        return view('configuration.device_add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        $this->validateDevice($request);

        $device = new Device();
        $this->fillDevice($device, $request);
        $device->created_by = Auth::id();
        $device->save();

        return redirect()->route('devices.index')->with(successMessage());
    }

    public function edit($slug)
    {
        $device = Device::whereSlug($slug)->firstOrFail();
        $route = route('devices.update', $device->id);
        return view('configuration.device_add_edit', compact('device', 'route'));
    }

    public function update(Request $request, $id)
    {
        $this->validateDevice($request, $id);

        $device = Device::findOrFail($id);
        $this->fillDevice($device, $request);
        $device->updated_by = Auth::id();
        $device->save();

        return redirect()->route('devices.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->deleted_by = Auth::id();
        $device->save();
        $device->delete();
        return redirect()->route('devices.index')->with(deleteMessage());
    }

    public function show($id)
    {
        $device = Device::findOrFail($id);
        return view('configuration.device_show', compact('device'));
    }

    // ─── Setup Guide ───────────────────────────────────────────────────────────

    public function setupGuide()
    {
        $localIp   = $this->getServerLocalIp();
        $appUrl    = config('app.url');
        $appDomain = parse_url($appUrl, PHP_URL_HOST);
        $appPort   = parse_url($appUrl, PHP_URL_PORT) ?: (str_starts_with($appUrl, 'https') ? 443 : 80);
        $devices   = Device::where('status', 'active')->get();
        return view('configuration.device_setup_guide', compact('localIp', 'appUrl', 'appDomain', 'appPort', 'devices'));
    }

    // ─── Device Actions ────────────────────────────────────────────────────────

    /**
     * Test connection — redirect version (for non-JS fallback).
     */
    public function testConnection($id)
    {
        $device = Device::findOrFail($id);
        $result = $this->zkService->testConnection($device);

        if ($result['success']) {
            return redirect()->back()->with(successMessage('success', $result['message']));
        }
        return redirect()->back()->with(dangerMessage('danger', $result['message']));
    }

    /**
     * Test connection — JSON/AJAX version (called from the device list button).
     */
    public function testConnectionJson($id)
    {
        $device = Device::findOrFail($id);
        $result = $this->zkService->testConnection($device);

        return response()->json([
            'success'    => $result['success'],
            'message'    => $result['message'],
            'mode'       => $device->use_push_mode ? 'push' : 'tcp',
            'last_seen'  => $device->last_seen_at?->diffForHumans(),
            'is_online'  => $device->is_online ?? false,
        ]);
    }

    /**
     * Push ALL students to a specific device.
     * Push mode → queues commands. TCP mode → direct socket.
     */
    public function pushStudents($deviceId)
    {
        $device   = Device::findOrFail($deviceId);
        $students = Student::all();
        $count    = 0;

        if ($device->use_push_mode) {
            foreach ($students as $student) {
                $name = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? '')) ?: 'Student';
                DeviceCommand::queue($device->id, DeviceCommand::setUserCommand((string) $student->student_no, $name));
                $count++;
            }
            return redirect()->back()->with(successMessage('success',
                "{$count} student(s) queued for push to device [{$device->name}]. Device will sync within 30 seconds."));
        }

        // TCP mode
        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return redirect()->back()->with(dangerMessage('danger', 'Cannot connect to device via TCP. Check IP and network.'));
        }
        foreach ($students as $student) {
            try {
                $name = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? '')) ?: 'Student';
                $this->zkService->pushUser($zk, (string) $student->student_no, $name);
                $count++;
            } catch (\Throwable $e) { /* skip failed */ }
        }
        $this->zkService->disconnect($zk);
        return redirect()->back()->with(successMessage('success', "{$count} student(s) pushed to device [{$device->name}] successfully."));
    }

    /**
     * Push ALL teachers to a specific device.
     */
    public function pushTeachers($deviceId)
    {
        $device   = Device::findOrFail($deviceId);
        $teachers = Teacher::all();
        $count    = 0;

        if ($device->use_push_mode) {
            foreach ($teachers as $teacher) {
                DeviceCommand::queue($device->id, DeviceCommand::setUserCommand((string) $teacher->teacher_no, $teacher->name ?? 'Teacher'));
                $count++;
            }
            return redirect()->back()->with(successMessage('success',
                "{$count} teacher(s) queued for push to device [{$device->name}]. Device will sync within 30 seconds."));
        }

        // TCP mode
        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return redirect()->back()->with(dangerMessage('danger', 'Cannot connect to device via TCP. Check IP and network.'));
        }
        foreach ($teachers as $teacher) {
            try {
                $this->zkService->pushUser($zk, (string) $teacher->teacher_no, $teacher->name ?? 'Teacher');
                $count++;
            } catch (\Throwable $e) { /* skip failed */ }
        }
        $this->zkService->disconnect($zk);
        return redirect()->back()->with(successMessage('success', "{$count} teacher(s) pushed to device [{$device->name}] successfully."));
    }

    /**
     * Pull attendance from a specific device.
     * Push mode → counts records already in DB. TCP mode → fetches live.
     */
    public function pullAttendance(Request $request, $deviceId)
    {
        $device = Device::findOrFail($deviceId);
        $from   = $request->from ? Carbon::parse($request->from)->toDateString() : Carbon::today()->toDateString();
        $to     = $request->to   ? Carbon::parse($request->to)->toDateString()   : $from;
        $saved  = 0;

        if ($device->use_push_mode) {
            $saved = AttendanceLog::where('device_serial', $device->serial_no)
                ->whereBetween('punch_time', [
                    Carbon::parse($from)->startOfDay(),
                    Carbon::parse($to)->endOfDay(),
                ])->count();
            return redirect()->back()->with(successMessage('success',
                "Push Mode: {$saved} attendance record(s) already in database for [{$device->name}] ({$from} → {$to})."));
        }

        // TCP mode
        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return redirect()->back()->with(dangerMessage('danger', 'Cannot connect to device via TCP.'));
        }
        $logs         = $this->zkService->getAttendance($zk);
        $filteredLogs = $this->zkService->filterAttendance($logs, $from, $to);
        $this->zkService->disconnect($zk);

        foreach ($filteredLogs as $log) {
            $punchTime = Carbon::parse($log['timestamp']);
            $pin       = (string) ($log['id'] ?? '');
            $resolved  = \App\Services\UserResolver::resolve($pin, $device);

            $criteria = \App\Services\UserResolver::attendanceLogFields($pin, $resolved, $device->serial_no, $punchTime);

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

        return redirect()->back()->with(successMessage('success', "{$saved} attendance record(s) pulled from device [{$device->name}]."));
    }

    /**
     * Pull the user list ALREADY enrolled on a device and create/update the
     * matching Student or Teacher record in the project DB — the reverse
     * direction of "Push Students/Teachers". TCP mode only: Push/iClock mode
     * devices never send a full user list on demand (only attendance and
     * command-delivery polls), so new push-mode users are instead picked up
     * automatically the first time they punch attendance (see Unmatched
     * Attendance for anyone not yet recognized).
     *
     * device_for = 'student' or 'teacher' → unambiguous, always safe to
     * create/update directly by PIN.
     * device_for = 'student_teacher'      → a brand-new PIN could belong to
     * either group, so we only auto-update PINs that already match an
     * existing student_no/teacher_no, and report anything new as
     * "unclassified" for you to add manually (one click, pre-filled).
     */
    public function pullUsers($deviceId)
    {
        $device = Device::findOrFail($deviceId);

        if ($device->use_push_mode) {
            return redirect()->back()->with(dangerMessage('danger',
                'Push Mode devices don\'t support a live "Pull Users" — the device never sends its full enrolled-user list on demand. New push-mode users are picked up automatically the first time they punch; unrecognized PINs show up under Unmatched Attendance for you to classify.'));
        }

        $zk = $this->zkService->connect($device);
        if (! $zk) {
            return redirect()->back()->with(dangerMessage('danger', 'Cannot connect to device via TCP.'));
        }
        $deviceUsers = $this->zkService->getUsers($zk);
        $this->zkService->disconnect($zk);

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

            // Mixed device: only auto-touch PINs that already exist in one
            // table. A brand-new PIN's type can't be guessed safely.
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

        return view('configuration.device_pull_result', [
            'device'           => $device,
            'createdStudents'  => $createdStudents,
            'createdTeachers'  => $createdTeachers,
            'updatedTeachers'  => $updatedTeachers,
            'skipped'          => $skipped,
            'unclassified'     => $unclassified,
            'totalOnDevice'    => count($deviceUsers),
        ]);
    }

    /**
     * List users stored on device (TCP mode only — push mode doesn't have live user list).
     */
    public function getUsers(Request $request, $id)
    {
        $device = Device::findOrFail($id);

        if ($device->use_push_mode) {
            $paginatedUsers = new \Illuminate\Pagination\LengthAwarePaginator(
                [], 0, 50, 1,
                ['path' => url()->current(), 'query' => request()->query()]
            );
            return view('configuration.device_users', [
                'device'         => $device,
                'paginatedUsers' => $paginatedUsers,
                'pushMode'       => true,
            ]);
        }

        try {
            $zk = $this->zkService->connect($device);
            if (! $zk) {
                return redirect()->route('devices.index')
                    ->with(dangerMessage('danger', 'Device not connected or connection failed!'));
            }
            $usersArray = $this->zkService->getUsers($zk);
            $this->zkService->disconnect($zk);
        } catch (\Throwable $e) {
            return redirect()->route('devices.index')
                ->with(dangerMessage('danger', 'Connection Failed! Device not connected.'));
        }

        $users = collect($usersArray);
        if ($request->userid) {
            $users = $users->filter(fn($i) => str_contains(strtolower($i['userid'] ?? ''), strtolower($request->userid)));
        }
        if ($request->name) {
            $users = $users->filter(fn($i) => str_contains(strtolower($i['name'] ?? ''), strtolower($request->name)));
        }
        if ($request->role !== null && $request->role !== '') {
            $users = $users->filter(fn($i) => (string) ($i['role'] ?? '') === (string) $request->role);
        }

        $perPage        = 50;
        $page           = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $paginatedUsers = new \Illuminate\Pagination\LengthAwarePaginator(
            $users->forPage($page, $perPage)->values(), $users->count(), $perPage, $page,
            ['path' => url()->current(), 'query' => request()->query()]
        );

        return view('configuration.device_users', compact('device', 'paginatedUsers'));
    }

    /**
     * Remove all users from device.
     */
    public function removeUsers($id): \Illuminate\Http\RedirectResponse
    {
        $device = Device::findOrFail($id);

        if ($device->use_push_mode) {
            DeviceCommand::queue($device->id, 'DATA CLEAR USERINFO');
            return redirect()->route('devices.index')
                ->with(successMessage('success', 'Command queued: all users will be removed from device on next sync.'));
        }

        try {
            $zk = $this->zkService->connect($device);
            if (! $zk) {
                return redirect()->route('devices.index')
                    ->with(dangerMessage('danger', 'Device not connected or connection failed!'));
            }
            $zk->clearUsers();
            $this->zkService->disconnect($zk);
            return redirect()->route('devices.index')
                ->with(successMessage('success', 'All users removed from device successfully.'));
        } catch (\Throwable $e) {
            return redirect()->route('devices.index')
                ->with(dangerMessage('danger', 'Connection Failed! Device not connected.'));
        }
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function validateDevice(Request $request, $ignoreId = null): void
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('devices')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'serial_no' => [
                'required', 'string', 'max:255',
                Rule::unique('devices')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'ip_address' => [
                'nullable', 'string', 'max:100',
                Rule::unique('devices')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'device_port'    => 'nullable|numeric|between:1,65535',
            'subnet_label'   => 'nullable|string|max:100',
            'gateway_ip'     => 'nullable|string|max:45',
            'location_note'  => 'nullable|string|max:255',
            'comm_key'       => 'nullable|numeric|between:0,65535',
            'status'         => 'required|in:active,inactive',
            'device_for'     => 'required|in:student_teacher,student,teacher',
            'use_push_mode'  => 'sometimes|boolean',
        ]);
    }

    protected function fillDevice(Device $device, Request $request): void
    {
        $device->name          = $request->name;
        $device->slug          = Str::slug($request->name);
        $device->serial_no     = $request->serial_no;
        $device->ip_address    = $request->ip_address;
        $device->device_port   = $request->device_port ?: 4370;
        $device->subnet_label  = $request->subnet_label;
        $device->gateway_ip    = $request->gateway_ip;
        $device->location_note = $request->location_note;
        $device->comm_key      = $request->comm_key ?? 0;
        $device->device_for    = $request->device_for;
        $device->status        = $request->status;
        $device->use_push_mode = $request->boolean('use_push_mode');
    }

    protected function getServerLocalIp(): string
    {
        try {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($sock, '8.8.8.8', 80);
            socket_getsockname($sock, $ip);
            socket_close($sock);
            return $ip;
        } catch (\Throwable) {
            return gethostbyname(gethostname()) ?: '127.0.0.1';
        }
    }
}
