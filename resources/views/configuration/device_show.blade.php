@extends('layouts.app')
@section('title', 'Device Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Device Management</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>
    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-microchip mr-2"></i>Device Details: {{ $device->name }}
                @if($device->use_push_mode)
                    <span class="badge bg-success ms-2">PUSH MODE</span>
                    @if($device->is_online)
                        <span class="badge bg-primary">Online</span>
                    @else
                        <span class="badge bg-danger">Offline</span>
                    @endif
                @else
                    <span class="badge bg-secondary ms-2">TCP/UDP</span>
                @endif
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">Name</th>
                                    <td>{{ $device->name }}</td>
                                </tr>
                                <tr>
                                    <th>Serial No</th>
                                    <td><code>{{ $device->serial_no }}</code></td>
                                </tr>
                                <tr>
                                    <th>Connection Mode</th>
                                    <td>
                                        @if($device->use_push_mode)
                                            <span class="text-success fw-bold">
                                                <i class="fas fa-cloud-upload-alt"></i> Push Mode (iClock / ADMS HTTP)
                                            </span>
                                        @else
                                            <span class="text-secondary fw-bold">
                                                <i class="fas fa-network-wired"></i> TCP/UDP Direct
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $device->ip_address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Port</th>
                                    <td>{{ $device->device_port ?? '4370' }}</td>
                                </tr>
                                <tr>
                                    <th>Comm Key</th>
                                    <td>{{ $device->comm_key }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th>Status</th>
                                    <td>{!! showStatus($device->status) !!}</td>
                                </tr>
                                <tr>
                                    <th>Device For</th>
                                    <td>
                                        @if ($device->device_for == 'student_teacher')
                                            Student and Teacher
                                        @elseif($device->device_for == 'student')
                                            Student
                                        @elseif($device->device_for == 'teacher')
                                            Teacher
                                        @endif
                                    </td>
                                </tr>
                                @if($device->use_push_mode)
                                <tr>
                                    <th>Last Heartbeat</th>
                                    <td>
                                        @if($device->last_seen_at)
                                            {{ $device->last_seen_at->format('d M, Y h:i A') }}
                                            <small class="text-muted">({{ $device->last_seen_at->diffForHumans() }})</small>
                                        @else
                                            <span class="text-warning">Never — configure Cloud Server on device</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Last Synced</th>
                                    <td>{{ $device->last_synced_at ? $device->last_synced_at->format('d M, Y h:i A') : '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $device->created_at->format('d M, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $device->updated_at->format('d M, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Push Mode Setup Guide --}}
            @if($device->use_push_mode)
            <div class="mt-3">
                <div class="card border-info">
                    <div class="card-header bg-info text-white py-2">
                        <i class="fas fa-cog mr-1"></i>
                        <strong>Device Setup — Cloud Server / ADMS Configuration</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Configure the following on the ZkTeco device:</p>
                        <p><strong>Path:</strong> <code>MENU → COMM → Cloud Server (or ADMS)</code></p>
                        <table class="table table-sm table-bordered w-auto">
                            <tr><th>Enable</th><td><strong>ON</strong></td></tr>
                            <tr><th>Server Address</th><td><code>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</code></td></tr>
                            <tr><th>Server Port</th><td><code>80</code> (or <code>443</code> for HTTPS)</td></tr>
                        </table>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle"></i>
                            The server URL for this device is:
                            <code>{{ config('app.url') }}/iclock/getrequest</code>
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-3 d-flex gap-2">
                <a href="{{ route('devices.test-connection', $device->id) }}" class="btn btn-info text-white">
                    <i class="fas {{ $device->use_push_mode ? 'fa-heartbeat' : 'fa-wifi' }} mr-1"></i>
                    {{ $device->use_push_mode ? 'Check Heartbeat' : 'Test Connection' }}
                </a>
                <a href="{{ route('devices.edit', $device->slug) }}" class="btn btn-warning">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
            </div>
        </div>
    </div>
@endsection
