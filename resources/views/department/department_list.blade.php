@extends('layouts.app')
@section('title', 'Department List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Department Management</h3>
        <div>
            <a href="{{ route('shifts.index') }}" class="btn btn-secondary mr-1" {!! tooltip('Shifts') !!}>
                <i class="fas fa-business-time mr-1"></i> Shifts
            </a>
            <a href="{{ route('departments.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add New Department') !!}>
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-building mr-2"></i>Department List</h5>
            <form method="GET" action="{{ route('departments.index') }}" class="form-inline">
                <select name="shift_id" class="form-control form-control-sm mr-1" onchange="this.form.submit()">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ (string) request('shift_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->title }} ({{ timeFormat($s->in_time, 'h:i A') }} - {{ timeFormat($s->out_time, 'h:i A') }})
                        </option>
                    @endforeach
                </select>
                @if(request()->filled('shift_id'))
                    <a href="{{ route('departments.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Shift</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Teachers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td>{{ $departments->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $department->name }}</td>
                                <td>
                                    @if($department->shift)
                                        {{ $department->shift->title }}
                                        @if($department->shift->status !== 'active')
                                            <span class="badge badge-warning">inactive</span>
                                        @endif
                                    @else
                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Not set</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $department->shift ? timeFormat($department->shift->in_time, 'h:i A') : '-' }}</td>
                                <td class="text-center">{{ $department->shift ? timeFormat($department->shift->out_time, 'h:i A') : '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('teachers.index', ['department_id' => $department->id]) }}"
                                       class="badge badge-secondary">{{ $department->teachers_count }}</a>
                                </td>
                                <td>{!! showStatus($department->status) !!}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('departments.edit', $department->id) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Department') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Department') !!} class="action-btn text-danger delete-data"
                                        data-id="delete-department-{{ $department->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-department-{{ $department->id }}"
                                        action="{{ route('departments.destroy', $department->id) }}" method="POST">
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
                {!! $departments->appends(request()->all())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
