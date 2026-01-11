@extends('layouts.app')
@section('title', 'Dashboard')
@push('css')
@endpush

@section('content')
    <h2 class="mb-2">Dashboard Overview</h2>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students
                            </div>
                            <div class="stats-number">{{ $total_students ?? 0 }}</div>
                            <div class="text-muted">Active Students</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users stats-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Teachers
                            </div>
                            <div class="stats-number">{{ $total_teachers ?? 0 }}</div>
                            <div class="text-muted">Active Teachers</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus stats-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Devices
                            </div>
                            <div class="stats-number">{{ $total_devices ?? 0 }}</div>
                            <div class="text-muted">Active Devices</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus stats-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Today Present
                            </div>
                            <div class="stats-number">{{ $today_present ?? 0 }}</div>
                            <div class="text-muted">Awaiting processing</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock stats-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title"><i class="fas fa-shopping-cart"></i> Today Attendance</h5>
                    <a href="{{ route('attendance-logs') }}" class="btn btn-primary-admin btn-sm text-white">
                        Show Logs
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">

                        <table class="table table-hover table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>User Type</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th class="text-center">In Time</th>
                                <th class="text-center">Out Time</th>
                                <th class="text-center">Punch Count</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($today_logs as $attendance)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ ucFirst($attendance['user_type']) }}</td>
                                    <td>{{ $attendance['user_no'] ?? '' }}</td>
                                    <td>{{ $attendance['name'] ?? '' }}</td>
                                    <td class="text-center">{{ timeFormat($attendance['in_time'], 'h:i a')  }}</td>
                                    <td class="text-center">{{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                                    <td class="text-center">{{ $attendance['total_punches'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <x-no-data-found/>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {!! $today_logs->links('pagination::bootstrap-4') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
