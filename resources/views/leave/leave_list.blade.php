@extends('layouts.app')
@section('title', 'Leave Entry')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Leave Entry</h3>
        <div>
            @if (userCan('leave-types'))
                <a href="{{ route('leave-types.index') }}" class="btn btn-secondary mr-1">
                    <i class="fas fa-tags mr-1"></i> Leave Types
                </a>
            @endif
            <a href="{{ route('leaves.create') }}" class="btn btn-primary-admin text-white">
                <i class="fas fa-plus mr-2"></i> Add Leave
            </a>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="card admin-card mb-3">
        <div class="card-body pb-1">
            <form method="GET" action="{{ route('leaves.index') }}">
                <div class="row">
                    @if (isSuperAdmin())
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Institute</label>
                                @include('partials.institute_select', ['autoSubmit' => true])
                            </div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Teacher/Staff</label>
                            <select name="teacher_id" class="form-control leave-teacher-filter" data-placeholder="All Teachers/Staff">
                                <option value="">All Teachers/Staff</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}" {{ (string) request('teacher_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->teacher_no }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Leave Type</label>
                            <select name="leave_type_id" class="form-control">
                                <option value="">All Leave Types</option>
                                @foreach ($types as $lt)
                                    <option value="{{ $lt->id }}" {{ (string) request('leave_type_id') === (string) $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control {{ hasError('from_date') }}" placeholder="From date">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control {{ hasError('to_date') }}" placeholder="To date">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-group w-100 d-flex" style="gap:6px">
                            <button class="btn btn-primary flex-grow-1"><i class="fas fa-search mr-1"></i> Search</button>
                            <a href="{{ route('leaves.index') }}" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-plane-departure mr-2"></i>Leave List</h5>
            <small class="text-muted">Days = working days in the range; the institute's holidays are not counted.</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Teacher/Staff</th>
                            <th>ID</th>
                            <th>Department</th>
                            @if (isSuperAdmin())<th>Institute</th>@endif
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th class="text-center">Days</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            <tr>
                                <td>{{ $leaves->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $leave->teacher->name ?? '(deleted)' }}</td>
                                <td><span class="device-id-badge">{{ $leave->teacher->teacher_no ?? '-' }}</span></td>
                                <td>{{ $leave->teacher->department->name ?? '-' }}</td>
                                @if (isSuperAdmin())<td><small>{{ $leave->institute->name ?? '—' }}</small></td>@endif
                                <td>
                                    {{ $leave->type->name ?? '-' }}
                                    @if ($leave->type?->code)<span class="badge badge-warning ml-1">{{ $leave->type->code }}</span>@endif
                                </td>
                                <td class="text-nowrap">{{ $leave->from_date->format('d M Y') }}</td>
                                <td class="text-nowrap">{{ $leave->to_date->format('d M Y') }}</td>
                                <td class="text-center"><span class="badge badge-pill badge-info" style="font-size:.85em">{{ $leave->days }}</span></td>
                                <td style="max-width:260px"><small>{{ $leave->reason ?: '-' }}</small></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('leaves.edit', $leave->id) }}" class="action-btn text-info" {!! tooltip('Edit Leave') !!}>
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a {!! tooltip('Delete Leave') !!} class="action-btn text-danger delete-data"
                                       data-id="delete-leave-{{ $leave->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-leave-{{ $leave->id }}" action="{{ route('leaves.destroy', $leave->id) }}" method="POST">
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
                {!! $leaves->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('.leave-teacher-filter').select2({ width: '100%', placeholder: 'All Teachers/Staff', allowClear: true });
    }
</script>
@endpush
