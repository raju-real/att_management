@extends('layouts.app')
@section('title', 'Institute List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Institute Management</h3>
        <a href="{{ route('institutes.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add New Institute') !!}>
            <i class="fas fa-plus mr-2"></i> Add New
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-university mr-2"></i>Institute List</h5>
            <form method="GET" action="{{ route('institutes.index') }}" class="form-inline">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm mr-1"
                       placeholder="Name, email, mobile, EIIN">
                <select name="status" class="form-control form-control-sm mr-1">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button class="btn btn-sm btn-primary-admin text-white mr-1"><i class="fas fa-search"></i></button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('institutes.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Logo</th>
                            <th>Institute</th>
                            <th>Login Email</th>
                            <th>Mobile</th>
                            <th>EIIN</th>
                            <th class="text-center">Teachers/Staff</th>
                            <th class="text-center">Students</th>
                            <th class="text-center">Devices</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($institutes as $institute)
                            <tr>
                                <td>{{ $institutes->firstItem() + $loop->index }}</td>
                                <td>
                                    @if($institute->logo && file_exists($institute->logo))
                                        <img src="{{ asset($institute->logo) }}" alt="" style="height:34px;max-width:60px;object-fit:contain">
                                    @else
                                        <i class="fas fa-university text-muted fa-lg"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $institute->name }}</div>
                                    @if($institute->address)<small class="text-muted">{{ $institute->address }}</small>@endif
                                </td>
                                <td>{{ $institute->email }}</td>
                                <td>{{ $institute->mobile ?? '-' }}</td>
                                <td>{{ $institute->eiin ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('teachers.index', ['institute_id' => $institute->id]) }}" class="badge badge-secondary">{{ $institute->teachers_count }}</a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('students.index', ['institute_id' => $institute->id]) }}" class="badge badge-secondary">{{ $institute->students_count }}</a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('devices.index', ['institute_id' => $institute->id]) }}"
                                       class="badge badge-{{ $institute->institute_devices_count ? 'info' : 'warning' }}">{{ $institute->institute_devices_count }}</a>
                                </td>
                                <td>{!! showStatus($institute->status) !!}</td>
                                <td><small>{{ $institute->last_login_at ? \Carbon\Carbon::parse($institute->last_login_at)->format('d M Y h:i A') : 'Never' }}</small></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('institutes.edit', $institute->id) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Institute') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Institute') !!} class="action-btn text-danger delete-data"
                                        data-id="delete-institute-{{ $institute->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-institute-{{ $institute->id }}"
                                        action="{{ route('institutes.destroy', $institute->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">
                {!! $institutes->appends(request()->all())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
