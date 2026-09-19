@extends('layouts.app')
@section('title', 'Device List')
@push('css')
<style>
    .badge-push  { background-color: #198754; color: #fff; font-size: .72em; }
    .badge-tcp   { background-color: #6c757d; color: #fff; font-size: .72em; }
    .badge-online { background-color: #0d6efd; color: #fff; font-size: .72em; }
    .badge-offline { background-color: #dc3545; color: #fff; font-size: .72em; }
    .last-seen { font-size: .78em; color: #6c757d; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Device Management</h3>
        <a href="{{ route('devices.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add New Device') !!}>
            <i class="fas fa-plus mr-2"></i> Add New
        </a>
    </div>
    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Device List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Serial No</th>
                            <th>Mode / IP</th>
                            <th>Port</th>
                            <th>Device For</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $device->name ?? '' }}</td>
                                <td><code>{{ $device->serial_no ?? '' }}</code></td>
                                <td>
                                    @if($device->use_push_mode)
                                        <span class="badge badge-push me-1">PUSH</span>
                                        {{-- Online / offline based on last heartbeat --}}
                                        @if($device->last_seen_at)
                                            @if($device->is_online)
                                                <span class="badge badge-online">Online</span>
                                            @else
                                                <span class="badge badge-offline">Offline</span>
                                            @endif
                                            <br>
                                            <span class="last-seen">Last seen {{ $device->last_seen_at->diffForHumans() }}</span>
                                        @else
                                            <span class="badge badge-offline">Never Connected</span>
                                            <br>
                                            <span class="last-seen text-warning">Configure Cloud Server on device</span>
                                        @endif
                                    @else
                                        <span class="badge badge-tcp me-1">TCP/UDP</span>
                                        <span class="last-seen">{{ $device->ip_address ?? 'No IP' }}</span>
                                    @endif
                                </td>
                                <td>{{ $device->device_port ?? '4370' }}</td>
                                <td>
                                    @if ($device->device_for == 'student_teacher')
                                        Student &amp; Teacher
                                    @elseif($device->device_for == 'student')
                                        Student
                                    @elseif($device->device_for == 'teacher')
                                        Teacher
                                    @endif
                                </td>
                                <td>{!! showStatus($device->status) !!}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('devices.show', $device->id) }}" class="action-btn text-info"
                                        {!! tooltip('Show Details') !!}><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('devices.edit', $device->slug) }}" class="action-btn"
                                        {!! tooltip('Edit Device') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Device') !!} class="action-btn text-danger delete-data"
                                        data-id="{{ 'delete-device-' . $device->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    {{-- Test Connection --}}
                                    <a href="{{ route('devices.test-connection', $device->id) }}"
                                        class="action-btn {{ $device->use_push_mode ? 'text-primary' : 'text-success' }}"
                                        {!! tooltip($device->use_push_mode ? 'Check Heartbeat' : 'Test TCP Connection') !!}>
                                        <i class="fas {{ $device->use_push_mode ? 'fa-heartbeat' : 'fa-wifi' }}"></i>
                                    </a>
                                    {{-- Show Users (TCP only) --}}
                                    @unless($device->use_push_mode)
                                    <a href="{{ route('devices.users', $device->id) }}" class="action-btn text-primary"
                                        {!! tooltip('Show Device Users') !!}><i class="fas fa-users"></i></a>
                                    @endunless
                                    {{-- Remove All Users --}}
                                    <a {!! tooltip($device->use_push_mode ? 'Queue: Remove All Users' : 'Remove Device Users') !!}
                                        class="action-btn text-danger delete-data"
                                        data-id="{{ 'delete-device-user-' . $device->id }}" href="javascript:void(0);">
                                        <i class="fas fa-eraser"></i>
                                    </a>

                                    <form id="delete-device-{{ $device->id }}"
                                        action="{{ route('devices.destroy', $device->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                    </form>

                                    <form id="delete-device-user-{{ $device->id }}"
                                        action="{{ route('devices.remove-users', $device->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $devices->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
