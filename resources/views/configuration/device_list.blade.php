@extends('layouts.app')
@section('title', 'Device List')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Device Management</h3>
        <a href="{{ route('devices.create') }}" class="btn btn-primary-admin text-white">
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
                        <th>IP Address</th>
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
                            <td>{{ $device->serial_no ?? '' }} X</td>
                            <td>{{ $device->ip_address ?? '' }}</td>
                            <td>{{ $device->device_port ?? '' }}</td>
                            <td>{!! ucFirst($device->device_for) !!}</td>
                            <td>{!! showStatus($device->status) !!}</td>
                            <td>
                                <a href="{{ route('devices.edit', $device->slug) }}"
                                   class="action-btn" {!! tooltip('Edit Device') !!}><i class="fas fa-edit"></i></a>
                                <a {!! tooltip('Delete Device') !!}
                                   class="action-btn text-danger delete-data"
                                   data-id="{{ 'delete-device-' . $device->id }}" href="javascript:void(0);">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <form id="delete-device-{{ $device->id }}"
                                      action="{{ route('devices.destroy', $device->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-no-data-found/>
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
