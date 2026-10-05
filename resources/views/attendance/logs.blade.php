@extends('layouts.app')
@section('title', 'Attendance Logs')

@push('css')
    @include('attendance._styles')
@endpush

@php
    $hm = fn ($m) => \App\Services\AttendanceReportService::minutesToHm((int) $m);
    $today     = \Carbon\Carbon::today();
    $presets   = [
        'Today'      => [$today->toDateString(), $today->toDateString()],
        'Yesterday'  => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
        'This Week'  => [$today->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->toDateString(), $today->toDateString()],
        'This Month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
        'Last Month' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
    ];
    $keep = request()->except(['from_date', 'to_date', 'date', 'month', 'page', 'export']);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h3 class="mb-2">Attendance Logs</h3>
        <div class="mb-2 no-print">
            <button type="button" class="btn btn-success text-white" data-toggle="modal" data-target="#syncAttendanceModal">
                <i class="fas fa-cloud-download-alt mr-1"></i> Pull from Device
            </button>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="rpt-filter p-3 mb-3">
        <div class="rpt-chips mb-3">
            @foreach($presets as $label => [$pf, $pt])
                <a href="{{ route('attendance.logs', array_merge($keep, ['from_date' => $pf, 'to_date' => $pt])) }}"
                   class="rpt-chip {{ $from_date === $pf && $to_date === $pt ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('attendance.logs') }}" id="logFilterForm">
            <div class="row">
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">From Date</label>
                        <input type="text" name="from_date" class="form-control flat_datepicker" value="{{ $from_date }}" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">To Date</label>
                        <input type="text" name="to_date" class="form-control flat_datepicker" value="{{ $to_date }}" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">User Type</label>
                        <select name="user_type" class="form-control">
                            <option value="">All</option>
                            <option value="teacher" {{ request('user_type') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="student" {{ request('user_type') === 'student' ? 'selected' : '' }}>Student</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_no" class="form-control select2-filter">
                            <option value="">All Teachers</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->teacher_no }}" {{ (string) request('teacher_no') === (string) $t->teacher_no ? 'selected' : '' }}>
                                    {{ $t->name }} ({{ $t->teacher_no }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">All Departments</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ (string) request('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">ID / Student No</label>
                        <input type="search" name="user_no" class="form-control" value="{{ request('user_no') }}" placeholder="101 / 10001">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search name">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-control">
                            <option value="date_desc" {{ request('sort', 'date_desc') === 'date_desc' ? 'selected' : '' }}>Newest date</option>
                            <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Oldest date</option>
                            <option value="in_asc" {{ request('sort') === 'in_asc' ? 'selected' : '' }}>Earliest in-time</option>
                            <option value="late_desc" {{ request('sort') === 'late_desc' ? 'selected' : '' }}>Most late</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-1 col-md-4 col-6">
                    <div class="form-group">
                        <label class="form-label">Rows</label>
                        <select name="per_page" class="form-control">
                            @foreach([25, 50, 100, 200] as $n)
                                <option value="{{ $n }}" {{ (int) request('per_page', 50) === $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-8 col-12 d-flex align-items-end">
                    <div class="form-group w-100 d-flex" style="gap:6px">
                        <button class="btn btn-primary flex-fill"><i class="fas fa-search mr-1"></i> Search</button>
                        <a href="{{ route('attendance.logs') }}" class="btn btn-secondary" {!! tooltip('Reset') !!}><i class="fas fa-undo"></i></a>
                        <a href="{{ route('attendance.logs', array_merge(request()->query(), ['export' => 'csv', 'from_date' => $from_date, 'to_date' => $to_date])) }}"
                           class="btn btn-success" {!! tooltip('Export CSV (Excel)') !!}><i class="fas fa-file-excel"></i></a>
                        <button type="button" class="btn btn-dark" onclick="window.print()" {!! tooltip('Print') !!}><i class="fas fa-print"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Table ── --}}
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fas fa-history mr-2"></i>
                {{ \Carbon\Carbon::parse($from_date)->format('d M Y') }}
                @if($from_date !== $to_date) &ndash; {{ \Carbon\Carbon::parse($to_date)->format('d M Y') }} @endif
            </h5>
            <small class="text-muted">{{ number_format($logs->total()) }} record(s)
                &middot; <span class="text-danger font-weight-bold">red</span> = late in / early out</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table rpt-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Date</th>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Department / Shift</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Working Hours</th>
                            <th class="text-center">Punches</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $row)
                            <tr>
                                <td class="text-center text-muted">{{ $logs->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($row->att_date)->format('d M Y') }}
                                    <span class="rpt-sub">{{ \Carbon\Carbon::parse($row->att_date)->format('l') }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center" style="gap:8px">
                                        @if($row->image && file_exists($row->image))
                                            <img src="{{ asset($row->image) }}" class="rpt-avatar" alt="">
                                        @else
                                            <span class="rpt-avatar-ph">{{ strtoupper(mb_substr($row->name ?? '?', 0, 1)) }}</span>
                                        @endif
                                        <div>
                                            <div class="font-weight-bold" style="line-height:1.2">{{ $row->name }}</div>
                                            <span class="rpt-type {{ $row->user_type }}">{{ $row->user_type ?? 'unknown' }}</span>
                                            @if($row->designation)<small class="text-muted ml-1">{{ $row->designation }}</small>@endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="rpt-id">{{ $row->user_no }}</span></td>
                                <td>
                                    {{ $row->department ?? '-' }}
                                    @if($row->std_in)
                                        <span class="rpt-sub">{{ $row->shift_title ?? 'Default' }}: {{ timeFormat($row->std_in, 'h:i A') }} - {{ timeFormat($row->std_out, 'h:i A') }}</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="rpt-time {{ $row->is_late ? 'bad' : 'ok' }}">{{ timeFormat($row->in_time, 'h:i A') }}</span>
                                    @if($row->is_late)
                                        <span class="rpt-sub bad">Late {{ $hm($row->late_minutes) }}</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($row->out_time)
                                        <span class="rpt-time {{ $row->is_early_out ? 'bad' : 'ok' }}">{{ timeFormat($row->out_time, 'h:i A') }}</span>
                                        @if($row->is_early_out)
                                            <span class="rpt-sub bad">Early {{ $hm($row->early_out_minutes) }}</span>
                                        @endif
                                    @else
                                        <span class="rpt-dash">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row->out_time)
                                        <span class="font-weight-bold">{{ $hm($row->work_minutes) }}</span>
                                    @else
                                        <span class="rpt-dash">-</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="rpt-pill gray">{{ $row->punches }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x d-block mb-3" style="opacity:.3"></i>
                                    No attendance found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center pt-3">
                {!! $logs->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>

    @include('attendance._pull_modal')
@endsection

@push('js')
<script>
    $(function () {
        if ($.fn.select2) {
            $('.select2-filter').select2({ width: '100%', placeholder: 'All Teachers', allowClear: true });
        }
    });
</script>
@endpush
