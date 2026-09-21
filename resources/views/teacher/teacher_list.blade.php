@extends('layouts.app')
@section('title', 'Teacher List')

@push('css')
<style>
    .teacher-avatar {
        width: 44px; height: 44px; border-radius: 50%;
        object-fit: cover; border: 2px solid rgba(99,102,241,.35);
        box-shadow: 0 2px 8px rgba(99,102,241,.15);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .teacher-avatar:hover { transform: scale(1.12); box-shadow: 0 4px 16px rgba(99,102,241,.3); }
    .teacher-avatar-placeholder {
        width: 44px; height: 44px; border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: #fff; border: 2px solid rgba(99,102,241,.3);
        box-shadow: 0 2px 8px rgba(99,102,241,.15); font-weight: 600;
    }
    .device-id-badge {
        display: inline-block; background: #f0f0ff; color: #4f46e5;
        border: 1px solid #c7d2fe; border-radius: 5px;
        padding: 1px 7px; font-size: .78em; font-family: monospace; font-weight: 600;
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Teacher Management</h3>
        <div>
            <a href="{{ route('teachers.push-to-device') }}" class="btn btn-warning text-white mr-2"
               onclick="return confirm('Push ALL teachers to all active devices now?')"
               {!! tooltip('Push all teachers to fingerprint devices') !!}>
                <i class="fas fa-upload mr-1"></i> Push to Device
            </a>
            <a href="{{ route('teachers.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Teacher') !!}>
                <i class="fas fa-plus mr-1"></i> Add Teacher
            </a>
        </div>
    </div>

    {{-- ── Search / Filter ── --}}
    @php $filterOpen = request()->hasAny(['name','teacher_no','designation']); @endphp
    <div class="accordion mb-3" id="teacherFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                    <span><i class="fas fa-filter mr-1"></i> Filter Teachers</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div id="filterCollapse" class="collapse {{ $filterOpen ? 'show' : '' }}"
                 aria-labelledby="filterHeading" data-parent="#teacherFilterAccordion">
                <div class="card-body">
                    <form method="GET" action="{{ route('teachers.index') }}">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <input type="search" name="name" class="form-control"
                                           value="{{ request('name') }}" placeholder="Teacher Name">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Device ID</label>
                                    <input type="search" name="teacher_no" class="form-control"
                                           value="{{ request('teacher_no') }}" placeholder="Device ID / No">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Designation</label>
                                    <input type="search" name="designation" class="form-control"
                                           value="{{ request('designation') }}" placeholder="Designation">
                                </div>
                            </div>
                            <div class="col-md-1 mt-2">
                                <label class="form-label"></label>
                                <div class="form-group">
                                    <button class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-1 mt-2">
                                <label class="form-label"></label>
                                <div class="form-group">
                                    <a href="{{ route('teachers.index') }}" class="btn btn-secondary w-100">
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
            <h5 class="card-title"><i class="fas fa-chalkboard-teacher mr-2"></i>Teacher List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th style="width:60px">Photo</th>
                            <th>Device ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Designation</th>
                            <th>Department / Shift</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    @if($teacher->image && file_exists($teacher->image))
                                        <img src="{{ asset($teacher->image) }}" alt="{{ $teacher->name }}"
                                             class="teacher-avatar" title="{{ $teacher->name }}">
                                    @else
                                        <div class="teacher-avatar-placeholder" title="{{ $teacher->name }}">
                                            {{ strtoupper(substr($teacher->name ?? 'T', 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="device-id-badge">{{ $teacher->teacher_no ?? '—' }}</span>
                                </td>
                                <td class="fw-semibold">{{ $teacher->name ?? '' }}</td>
                                <td>{{ $teacher->email ?? '—' }}</td>
                                <td>{{ $teacher->mobile ?? '—' }}</td>
                                <td>{{ $teacher->designation ?? '—' }}</td>
                                <td>
                                    @if($teacher->department)
                                        {{ $teacher->department->name }}
                                        @if($teacher->shift)
                                            <br><small class="text-muted">{{ $teacher->shift->title }} ({{ timeFormat($teacher->shift->in_time, 'h:i A') }} - {{ timeFormat($teacher->shift->out_time, 'h:i A') }})</small>
                                        @endif
                                    @else
                                        <span class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Not set</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('teachers.edit', $teacher->teacher_no) }}"
                                       class="action-btn text-info" {!! tooltip('Edit Teacher') !!}>
                                       <i class="fas fa-edit"></i>
                                    </a>
                                    <a {!! tooltip('Delete Teacher') !!} class="action-btn text-danger delete-data"
                                       data-id="delete-teacher-{{ $teacher->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-teacher-{{ $teacher->id }}"
                                          action="{{ route('teachers.destroy', $teacher->id) }}" method="POST">
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
                {!! $teachers->appends(request()->all())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
