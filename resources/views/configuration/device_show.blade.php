@extends('layouts.app')
@section('title', 'Device Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Device Management</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}><i
                class="fas fa-arrow-left mr-1"></i> Back to
            List</a>
    </div>
    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Device Details: {{ $device->name }}</h5>
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
                                    <td>{{ $device->serial_no }}</td>
                                </tr>
                                <tr>
                                    <th>IP Address</th>
                                    <td>{{ $device->ip_address }}</td>
                                </tr>
                                <tr>
                                    <th>Port</th>
                                    <td>{{ $device->device_port }}</td>
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

            {{-- <div class="mt-4">
                <h5 class="mb-3">Actions</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('devices.test-connection', $device->id) }}" class="btn btn-info text-white">
                        <i class="fas fa-network-wired mr-1"></i> Test Connection
                    </a>
                </div>
            </div> --}}
        </div>
    </div>
@endsection
