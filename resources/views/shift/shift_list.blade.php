@extends('layouts.app')
@section('title', 'Shift List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Shift Management</h3>
        <div>
            <a href="{{ route('departments.index') }}" class="btn btn-secondary mr-1" {!! tooltip('Departments') !!}>
                <i class="fas fa-building mr-1"></i> Departments
            </a>
            <a href="{{ route('shifts.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add New Shift') !!}>
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>
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
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Late after</th>
                            <th class="text-center">Early out before</th>
                            <th class="text-center">Duration</th>
                            <th class="text-center">Departments</th>
                            <th class="text-center">Teachers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                            @php
                                $mins = \App\Models\Shift::durationMinutes($shift->in_time, $shift->out_time);
                            @endphp
                            <tr>
                                <td>{{ $shifts->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $shift->title }}</td>
                                <td class="text-center"><span class="badge badge-light border">{{ timeFormat($shift->in_time, 'h:i A') }}</span></td>
                                <td class="text-center"><span class="badge badge-light border">{{ timeFormat($shift->out_time, 'h:i A') }}</span>
                                    @if($shift->is_overnight)<span class="badge badge-dark ml-1" {!! tooltip('Night shift: out time is on the next day') !!}><i class="fas fa-moon"></i> +1</span>@endif</td>
                                <td class="text-center">{{ timeFormat($shift->late_count_time ?: $shift->in_time, 'h:i A') }}</td>
                                <td class="text-center">{{ timeFormat($shift->early_out_count_time ?: $shift->out_time, 'h:i A') }}</td>
                                <td class="text-center">{{ intdiv($mins, 60) }}h {{ str_pad($mins % 60, 2, '0', STR_PAD_LEFT) }}m</td>
                                <td class="text-center">
                                    <a href="{{ route('departments.index', ['shift_id' => $shift->id]) }}"
                                       class="badge badge-info" {!! tooltip('View departments on this shift') !!}>{{ $shift->departments_count }}</a>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary">{{ $shift->teachers_count }}</span>
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
