@extends('layouts.app')
@section('title', 'Device Users')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Users for Device: {{ $device->name }} ({{ $device->serial_no }})</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary text-white">
            <i class="fas fa-arrow-left mr-2"></i> Back to Devices
        </a>
    </div>

    @php
        $filterOpen = request()->hasAny(['name', 'userid', 'role']);
    @endphp

    <div class="accordion mb-3" id="userFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                    <span>
                        <i class="fas fa-filter mr-1"></i> Filter Users
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div id="filterCollapse" class="collapse {{ $filterOpen ? 'show' : '' }}" aria-labelledby="filterHeading"
                data-parent="#userFilterAccordion">

                <div class="card-body">
                    <form method="GET" action="{{ route('devices.users', $device->id) }}">
                        <div class="row g-2">
                            <!-- Name -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <input type="search" name="name" class="form-control" value="{{ request('name') }}"
                                        placeholder="User Name">
                                </div>
                            </div>

                            <!-- User ID -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">User ID</label>
                                    <input type="search" name="userid" class="form-control"
                                        value="{{ request('userid') }}" placeholder="e.g. 10001">
                                </div>
                            </div>

                            <!-- Role -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-control">
                                        <option value="">All</option>
                                        <option value="0" {{ request('role') == '0' ? 'selected' : '' }}>Normal User
                                            (0)</option>
                                        <option value="14" {{ request('role') == '14' ? 'selected' : '' }}>Admin (14)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-1 mt-2">
                                <div class="form-group">
                                    <label class="form-label"></label>
                                    <button class="btn btn-primary mr-2 w-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-1 mt-2">
                                <label class="form-label"></label>
                                <div class="form-group">
                                    <a href="{{ route('devices.users', $device->id) }}" class="btn btn-secondary w-100">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-users mr-2"></i>Device Users List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th># (UID)</th>
                            <th>User ID / Card No</th>
                            <th>Name</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedUsers as $u)
                            <tr>
                                <td>{{ $u['uid'] ?? '' }}</td>
                                <td>{{ $u['userid'] ?? '' }}</td>
                                <td>{{ $u['name'] ?? '' }}</td>
                                <td>
                                    @php $r = $u['role'] ?? 0; @endphp
                                    @if ($r == 14 || $r == 255)
                                        <span class="badge badge-danger">Admin ({{ $r }})</span>
                                    @else
                                        <span class="badge badge-info">User ({{ $r }})</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3">
                {!! $paginatedUsers->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
