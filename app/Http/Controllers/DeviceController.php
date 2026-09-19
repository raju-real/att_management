<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\ZkTecoService;
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

    // ─── Device Actions ────────────────────────────────────────────────────────

    /**
     * Test device connection.
     *
     * Push mode  → checks last_seen_at (last heartbeat from device).
     * TCP mode   → tries UDP socket connect to device IP.
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
     * List users stored on device.
     *
     * Push mode  → users are in the attendance_logs / device_employee pivot (no live fetch).
     * TCP mode   → fetches from device in real-time.
     */
    public function getUsers(Request $request, $id)
    {
        $device = Device::findOrFail($id);

        // ── Push mode: users are managed via DB, not fetched live ────────────
        if ($device->use_push_mode) {
            $paginatedUsers = collect();
            return view('configuration.device_users', [
                'device'         => $device,
                'paginatedUsers' => $paginatedUsers,
                'pushMode'       => true,
            ]);
        }

        // ── TCP mode: fetch from device ───────────────────────────────────────
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
            $users = $users->filter(fn($item) =>
                str_contains(strtolower($item['userid'] ?? ''), strtolower($request->userid))
            );
        }

        if ($request->name) {
            $users = $users->filter(fn($item) =>
                str_contains(strtolower($item['name'] ?? ''), strtolower($request->name))
            );
        }

        if ($request->role !== null && $request->role !== '') {
            $users = $users->filter(fn($item) =>
                (string) ($item['role'] ?? '') === (string) $request->role
            );
        }

        $perPage = 50;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;

        $paginatedUsers = new \Illuminate\Pagination\LengthAwarePaginator(
            $users->forPage($page, $perPage)->values(),
            $users->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => request()->query()]
        );

        return view('configuration.device_users', compact('device', 'paginatedUsers'));
    }

    /**
     * Clear all users from device.
     *
     * Push mode  → queues a CLEAR USERINFO command.
     * TCP mode   → clears immediately via socket.
     */
    public function removeUsers($id): \Illuminate\Http\RedirectResponse
    {
        $device = Device::findOrFail($id);

        // ── Push mode ────────────────────────────────────────────────────────
        if ($device->use_push_mode) {
            DeviceCommand::queue($device->id, 'DATA CLEAR USERINFO');
            return redirect()->route('devices.index')
                ->with(successMessage('success', 'Command queued: all users will be removed from device on next sync.'));
        }

        // ── TCP mode ─────────────────────────────────────────────────────────
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
        $device->comm_key      = $request->comm_key ?? 0;
        $device->device_for    = $request->device_for;
        $device->status        = $request->status;
        $device->use_push_mode = $request->boolean('use_push_mode');
    }
}
