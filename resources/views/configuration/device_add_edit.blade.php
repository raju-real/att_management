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

                {{-- ═══ CONNECTION MODE ═══ --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white d-flex align-items-center py-2">
                                <i class="fas fa-network-wired mr-2"></i>
                                <strong>Connection Mode</strong>
                            </div>
                            <div class="card-body py-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="use_push_mode"
                                           name="use_push_mode" value="1"
                                           {{ old('use_push_mode', $device->use_push_mode ?? false) ? 'checked' : '' }}
                                           onchange="toggleConnectionMode(this.checked)">
                                    <label class="form-check-label fw-semibold" for="use_push_mode">
                                        Use Push Mode (HTTP / iClock)
                                        <span class="badge bg-success ms-1">Recommended for Shared Hosting &amp; Multi-Subnet</span>
                                    </label>
                                </div>

                                {{-- TCP Mode hint --}}
                                <div id="tcp-hint" class="alert alert-warning mt-2 mb-0 py-2" style="{{ old('use_push_mode', $device->use_push_mode ?? false) ? 'display:none' : '' }}">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>TCP/UDP Mode:</strong>
                                    The server directly connects to the device via UDP port 4370.
                                    This <strong>requires direct network access</strong> from the server to the device.
                                    It <strong>will NOT work</strong> on shared hosting or across different subnets without a routed gateway.
                                </div>

                                {{-- Push Mode hint --}}
                                <div id="push-hint" class="alert alert-success mt-2 mb-0 py-2" style="{{ old('use_push_mode', $device->use_push_mode ?? false) ? '' : 'display:none' }}">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <strong>Push Mode (iClock / ADMS):</strong>
                                    The <strong>device connects to this server</strong> via HTTP — works on shared hosting and across subnets.<br>
                                    <strong>Configure on device:</strong>
                                    <code>MENU → COMM → Cloud Server / ADMS</code>
                                    <ul class="mb-0 mt-1">
                                        <li>Enable: <strong>ON</strong></li>
                                        <li>Server Address: <strong>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</strong></li>
                                        <li>Server Port: <strong>80</strong> (or 443 for HTTPS)</li>
                                        <li>Server Path: <strong>/iclock</strong></li>
                                    </ul>
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
                                Device IP Address
                                <span id="ip-required-star" class="text-danger" style="{{ old('use_push_mode', $device->use_push_mode ?? false) ? 'display:none' : '' }}">*</span>
                                <small id="ip-optional-note" class="text-muted" style="{{ old('use_push_mode', $device->use_push_mode ?? false) ? '' : 'display:none' }}">(optional in push mode)</small>
                            </label>
                            <input type="text" name="ip_address"
                                value="{{ old('ip_address') ?? ($device->ip_address ?? '') }}"
                                class="form-control {{ hasError('ip_address') }}" placeholder="e.g. 192.168.1.201">
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

                <div class="text-right mt-2">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
function toggleConnectionMode(isPush) {
    document.getElementById('tcp-hint').style.display  = isPush ? 'none' : '';
    document.getElementById('push-hint').style.display = isPush ? '' : 'none';
    document.getElementById('ip-required-star').style.display  = isPush ? 'none' : '';
    document.getElementById('ip-optional-note').style.display  = isPush ? '' : 'none';
}
</script>
@endpush
