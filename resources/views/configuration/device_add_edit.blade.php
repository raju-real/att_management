@extends('layouts.app')
@section('title', 'Device Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($device) ? 'Edit' : 'Add' }} Device</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Device Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($device)
                    @method('PUT')
                @endisset

                {{-- ═══ CONNECTION MODE — locked to Push Mode, the only supported mode ═══ --}}
                <input type="hidden" name="use_push_mode" value="1">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white d-flex align-items-center py-2">
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Connection Mode: Push Mode (iClock / ADMS)</strong>
                                <span class="badge bg-light text-success ms-2">Only supported mode</span>
                            </div>
                            <div class="card-body py-3">
                                @php
                                    $curAppUrl  = config('app.url');
                                    $curAppHost = parse_url($curAppUrl, PHP_URL_HOST);
                                    $curAppPort = parse_url($curAppUrl, PHP_URL_PORT) ?: (str_starts_with($curAppUrl, 'https') ? 443 : 80);
                                @endphp
                                <p class="mb-2 small text-muted">
                                    Every device connects this same way — the device calls this server, not the
                                    other way around. This works identically in local development and on a VPS in
                                    production, so there's nothing to choose here.
                                </p>
                                <div class="alert alert-success mt-2 mb-0 py-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <strong>Configure on the device</strong> (<code>MENU → COMM → Cloud Server / ADMS</code>):
                                    <ul class="mb-1 mt-1">
                                        <li>Server Mode / Enable: whichever your firmware calls it — pick <strong>ADMS</strong>, not Disabled</li>
                                        <li>Server Address: <strong>{{ $curAppHost ?: 'this server\'s address' }}</strong></li>
                                        <li>Server Port: <strong>{{ $curAppPort }}</strong></li>
                                        <li>Proxy Server: <strong>OFF</strong></li>
                                    </ul>
                                    <a href="{{ route('devices.setup-guide') }}" target="_blank">Full Setup Guide with a live test button →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══ DEVICE DETAILS ═══ --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? ($device->name ?? '') }}"
                                class="form-control {{ hasError('name') }}" placeholder="Device Name">
                            @error('name')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Serial No. {!! starSign() !!}</label>
                            <input type="text" name="serial_no"
                                value="{{ old('serial_no') ?? ($device->serial_no ?? '') }}"
                                class="form-control {{ hasError('serial_no') }}" placeholder="e.g. XXXXXXXXXXXX">
                            @error('serial_no')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4" id="ip-field">
                        <div class="form-group">
                            <label class="form-label">
                                Device's Own IP Address
                                <small class="text-muted">(optional — diagnostics only)</small>
                            </label>
                            <input type="text" name="ip_address"
                                value="{{ old('ip_address') ?? ($device->ip_address ?? '') }}"
                                class="form-control {{ hasError('ip_address') }}" placeholder="e.g. 192.168.1.201">
                            <small class="text-muted">
                                Copy this from the device's own screen (<span class="text-monospace">MENU → COMM → Ethernet</span>) —
                                <strong>not</strong> an example from any guide, and not this server's own address.
                                Not needed for normal operation; only used by the diagnostic Test/Pull-Users buttons
                                on the same LAN.
                            </small>
                            @error('ip_address')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4" id="port-field">
                        <div class="form-group">
                            <label class="form-label">Device Port</label>
                            <input type="number" name="device_port"
                                value="{{ old('device_port') ?? ($device->device_port ?? '4370') }}"
                                class="form-control {{ hasError('device_port') }}" placeholder="4370">
                            @error('device_port')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Communication Key (Comm Key)</label>
                            <input type="number" name="comm_key" value="{{ old('comm_key') ?? ($device->comm_key ?? '0') }}"
                                class="form-control {{ hasError('comm_key') }}" placeholder="0">
                            <small class="text-muted">Default: 0 | MENU → COMM → Comm Key</small>
                            @error('comm_key')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Device For {!! starSign() !!}</label>
                            <select name="device_for" class="form-control {{ hasError('device_for') }}">
                                @foreach (getDeviceFor() as $device_for)
                                    <option value="{{ $device_for->value }}"
                                        {{ old('device_for') === $device_for->value || (isset($device) && $device->device_for === $device_for->value && empty(old('device_for'))) ? 'selected' : '' }}>
                                        {{ $device_for->title }}</option>
                                @endforeach
                            </select>
                            @error('device_for')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('status') === $status->value || (isset($device) && $device->status === $status->value && empty(old('status'))) ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ═══ NETWORK / SUBNET INFO (optional, for multi-building deployments) ═══ --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-secondary">
                            <div class="card-header py-2 d-flex align-items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-secondary"></i>
                                <strong>Network / Location Notes</strong>
                                <small class="text-muted ml-2">(optional — helpful once you have devices in more than one building/subnet)</small>
                            </div>
                            <div class="card-body py-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Subnet / Building Label</label>
                                            <input type="text" name="subnet_label"
                                                value="{{ old('subnet_label') ?? ($device->subnet_label ?? '') }}"
                                                class="form-control {{ hasError('subnet_label') }}"
                                                placeholder="e.g. Building A / 192.168.0.0/24">
                                            @error('subnet_label')
                                                {!! displayError($message) !!}
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Gateway IP for this Device</label>
                                            <input type="text" name="gateway_ip"
                                                value="{{ old('gateway_ip') ?? ($device->gateway_ip ?? '') }}"
                                                class="form-control {{ hasError('gateway_ip') }}"
                                                placeholder="e.g. 192.168.0.1">
                                            <small class="text-muted">The router port on the device's own subnet that routes to this server.</small>
                                            @error('gateway_ip')
                                                {!! displayError($message) !!}
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Location Note</label>
                                            <input type="text" name="location_note"
                                                value="{{ old('location_note') ?? ($device->location_note ?? '') }}"
                                                class="form-control {{ hasError('location_note') }}"
                                                placeholder="e.g. Main gate, 1st floor">
                                            @error('location_note')
                                                {!! displayError($message) !!}
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection
