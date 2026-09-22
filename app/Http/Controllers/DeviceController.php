<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\DeviceActivityService;
use App\Services\ZkTecoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    protected ZkTecoService $zkService;
    protected DeviceActivityService $activity;

    public function __construct(ZkTecoService $zkService, DeviceActivityService $activity)
    {
        $this->zkService = $zkService;
        $this->activity  = $activity;
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

    // ─── Device Actions (all delegate to DeviceActivityService) ───────────────

    /**
     * Test connection — redirect version (for non-JS fallback).
     */
    public function testConnection($id)
    {
        $device = Device::findOrFail($id);
        $result = $this->activity->testConnection($device);

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
        $result = $this->activity->testConnection($device);

        return response()->json([
            'success'    => $result['success'],
            'message'    => $result['message'],
            'mode'       => $device->use_push_mode ? 'push' : 'tcp',
            'last_seen'  => $device->last_seen_at?->diffForHumans(),
            'is_online'  => $device->is_online ?? false,
        ]);
    }

    /** Push ALL students to a specific device. */
    public function pushStudents($deviceId)
    {
        $device = Device::findOrFail($deviceId);
        $result = $this->activity->pushStudents($device);

        return redirect()->back()->with($result['success']
            ? successMessage('success', $result['message'])
            : dangerMessage('danger', $result['message']));
    }

    /** Push ALL teachers to a specific device. */
    public function pushTeachers($deviceId)
    {
        $device = Device::findOrFail($deviceId);
        $result = $this->activity->pushTeachers($device);

        return redirect()->back()->with($result['success']
            ? successMessage('success', $result['message'])
            : dangerMessage('danger', $result['message']));
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

        $result = $this->activity->pullAttendance($device, $from, $to);

        return redirect()->back()->with($result['success']
            ? successMessage('success', $result['message'])
            : dangerMessage('danger', $result['message']));
    }

    /**
     * Pull the user list ALREADY enrolled on a device and create/update the
     * matching Student or Teacher record in the project DB — the reverse
     * direction of "Push Students/Teachers". See DeviceActivityService::pullUsers().
     */
    public function pullUsers($deviceId)
    {
        $device = Device::findOrFail($deviceId);
        $result = $this->activity->pullUsers($device);

        if (! $result['success']) {
            return redirect()->back()->with(dangerMessage('danger', $result['message']));
        }

        return view('configuration.device_pull_result', [
            'device'          => $device,
            'createdStudents' => $result['createdStudents'],
            'createdTeachers' => $result['createdTeachers'],
            'updatedTeachers' => $result['updatedTeachers'],
            'skipped'         => $result['skipped'],
            'unclassified'    => $result['unclassified'],
            'totalOnDevice'   => $result['totalOnDevice'],
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

    /** Remove all users from device. */
    public function removeUsers($id): \Illuminate\Http\RedirectResponse
    {
        $device = Device::findOrFail($id);
        $result = $this->activity->removeAllUsers($device);

        return redirect()->route('devices.index')->with($result['success']
            ? successMessage('success', $result['message'])
            : dangerMessage('danger', $result['message']));
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
        // Push Mode (ADMS) is the only supported connection mode — fixed
        // server-side regardless of any request input, not just hidden in
        // the form. See the Setup Guide for why.
        $device->use_push_mode = true;
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
