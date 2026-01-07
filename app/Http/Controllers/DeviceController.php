<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
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
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('devices')
                    ->whereNull('deleted_at'),
            ],
            'serial_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('devices')->whereNull('deleted_at'),
            ],
            'ip_address' => [
                'required',
                'string',
                'max:100',
                Rule::unique('devices')
                    ->whereNull('deleted_at'),
            ],
            'device_port' => 'required|numeric|between:1,65535',
            'comm_key' => 'required|numeric|between:0,65535',
            'status' => 'required|in:active,inactive',
            'device_for' => 'required|in:student,teacher',
        ]);

        $device = new Device();
        $device->name = $request->name;
        $device->slug = Str::slug($request->name);
        $device->serial_no = $request->serial_no;
        $device->ip_address = $request->ip_address;
        $device->device_port = $request->device_port;
        $device->comm_key = $request->comm_key;
        $device->device_for = $request->device_for;
        $device->status = $request->status;
        $device->created_by = Auth::id();
        $device->save();

        return redirect()->route('devices.index')->with(successMessage());
    }

    public function edit($slug)
    {
        $device = Device::whereSlug($slug)->first();
        $route = route('devices.update', $device->id);
        return view('configuration.device_add_edit', compact('device', 'route'));
    }


    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('devices')->whereNull('deleted_at')->ignore($id),
            ],
            'serial_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('devices')
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
            'ip_address' => [
                'required',
                'string',
                'max:100',
                Rule::unique('devices')
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
            'device_port' => 'required|numeric|between:1,65535',
            'comm_key' => 'required|numeric|between:0,65535',
            'status' => 'required|in:active,inactive',
            'device_for' => 'required|in:student,teacher',
        ]);

        $device = Device::findOrFail($id);
        $device->name = $request->name;
        $device->slug = Str::slug($request->name);
        $device->serial_no = $request->serial_no;
        $device->ip_address = $request->ip_address;
        $device->device_port = $request->device_port;
        $device->comm_key = $request->comm_key;
        $device->device_for = $request->device_for;
        $device->status = $request->status;
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
}
