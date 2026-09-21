@extends('layouts.app')
@section('title', 'Shift List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Shift Management</h3>
        <a href="{{ route('shifts.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Shift') !!}>
            <i class="fas fa-plus mr-2"></i> Add Shift
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-business-time mr-2"></i>Shift List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Teachers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td class="fw-semibold">{{ $shift->title }}</td>
                                <td>{{ $shift->department->name ?? '—' }}</td>
                                <td class="text-center">{{ timeFormat($shift->in_time, 'h:i A') }}</td>
                                <td class="text-center">{{ timeFormat($shift->out_time, 'h:i A') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $shift->teachers_count }}</span>
                                </td>
                                <td>{!! showStatus($shift->status) !!}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('shifts.edit', $shift->id) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Shift') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Shift') !!} class="action-btn text-danger delete-data"
                                        data-id="delete-shift-{{ $shift->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-shift-{{ $shift->id }}"
                                        action="{{ route('shifts.destroy', $shift->id) }}" method="POST">
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
                {!! $shifts->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
