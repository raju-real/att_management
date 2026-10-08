@extends('layouts.app')
@section('title', 'Monthly Attendance Summary')

@push('css')
    @include('attendance._styles')
@endpush

@php
    $hm      = fn ($m) => \App\Services\AttendanceReportService::minutesToHm((int) $m);
    $today   = \Carbon\Carbon::today();
    $isMonth = !request()->filled('from_date');
    $month   = request('month', \Carbon\Carbon::parse($from_date)->format('Y-m'));
    $keep    = request()->except(['from_date', 'to_date', 'month', 'page', 'export']);
    $months  = collect(range(0, 5))->map(fn ($i) => $today->copy()->startOfMonth()->subMonthsNoOverflow($i));
    $typeLabels = ['teacher' => ['Teachers', 'fa-chalkboard-teacher', 'green'], 'student' => ['Students', 'fa-user-graduate', 'accent'], 'unknown' => ['Unknown', 'fa-question', 'amber']];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h3 class="mb-2">Monthly Attendance Summary</h3>
        <div class="mb-2 text-muted">
            <i class="far fa-calendar-alt mr-1"></i>
            {{ \Carbon\Carbon::parse($from_date)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($to_date)->format('d M Y') }}
            &middot; <strong>{{ $working_days }}</strong> working day(s)
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="rpt-filter p-3 mb-3">
        <div class="rpt-chips mb-3">
            @foreach($months as $m)
                <a href="{{ route('attendance.monthly-summary', array_merge($keep, ['month' => $m->format('Y-m')])) }}"
                   class="rpt-chip {{ $isMonth && $month === $m->format('Y-m') ? 'active' : '' }}">{{ $m->format('M Y') }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('attendance.monthly-summary') }}">
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
                        <label class="form-label">Shift</label>
                        <select name="shift_id" class="form-control">
                            <option value="">All Shifts</option>
                            @foreach($shifts as $sh)
                                <option value="{{ $sh->id }}" {{ (string) request('shift_id') === (string) $sh->id ? 'selected' : '' }}>
                                    {{ $sh->title }} ({{ \App\Services\AttendanceReportService::shiftClock($sh->in_time) }} - {{ \App\Services\AttendanceReportService::shiftClock($sh->out_time, $sh->in_time) }})
                                </option>
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
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-control">
                            <option value="name" {{ request('sort', 'name') === 'name' ? 'selected' : '' }}>Name</option>
                            <option value="user_no" {{ request('sort') === 'user_no' ? 'selected' : '' }}>ID</option>
                            <option value="late_desc" {{ request('sort') === 'late_desc' ? 'selected' : '' }}>Most late</option>
                            <option value="hours_desc" {{ request('sort') === 'hours_desc' ? 'selected' : '' }}>Most working hours</option>
                            <option value="present_desc" {{ request('sort') === 'present_desc' ? 'selected' : '' }}>Most present</option>
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
                <div class="col-lg-5 col-md-8 col-12 d-flex align-items-end">
                    <div class="form-group w-100 d-flex" style="gap:6px">
                        <button class="btn btn-primary flex-fill"><i class="fas fa-search mr-1"></i> Search</button>
                        <a href="{{ route('attendance.monthly-summary') }}" class="btn btn-secondary" {!! tooltip('Reset') !!}><i class="fas fa-undo"></i></a>
                        <a href="{{ route('attendance.monthly-summary', array_merge(request()->query(), ['export' => 'csv'])) }}"
                           class="btn btn-success" {!! tooltip('Export CSV (Excel)') !!}><i class="fas fa-file-excel"></i></a>
                        <button type="button" class="btn btn-dark" onclick="window.print()" {!! tooltip('Print') !!}><i class="fas fa-print"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- ── User type wise totals ── --}}
    <div class="row mb-3">
        @forelse($totals as $type => $t)
            @php [$label, $icon, $color] = $typeLabels[$type] ?? [ucfirst($type), 'fa-user', 'accent']; @endphp
            <div class="col-lg-6 mb-2">
                <div class="rpt-kpi {{ $color }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="k-label"><i class="fas {{ $icon }} mr-1"></i>{{ $label }}</div>
                        <a href="{{ route('attendance.monthly-summary', array_merge(request()->except('page'), ['user_type' => $type === 'unknown' ? null : $type])) }}"
                           class="small no-print">View only {{ strtolower($label) }} &rarr;</a>
                    </div>
                    <div class="row text-center">
                        <div class="col"><div class="k-value">{{ number_format($t->users) }}</div><div class="k-sub">Persons</div></div>
                        <div class="col"><div class="k-value">{{ number_format($t->present_days) }}</div><div class="k-sub">Present days</div></div>
                        <div class="col"><div class="k-value text-danger">{{ number_format($t->late_days) }}</div><div class="k-sub">Late in</div></div>
                        <div class="col"><div class="k-value" style="color:#b45309">{{ number_format($t->early_out_days) }}</div><div class="k-sub">Early out</div></div>
                        <div class="col"><div class="k-value" style="font-size:18px;padding-top:5px">{{ $hm($t->work_minutes) }}</div><div class="k-sub">Work hours</div></div>
                    </div>
                </div>
            </div>
        @empty
        @endforelse
    </div>

    {{-- ── Per-person summary ── --}}
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-calendar-check mr-2"></i>Person-wise Summary</h5>
            <small class="text-muted">{{ number_format($summary->total()) }} person(s)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table rpt-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Department / Shift</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center" title="Days the first punch was at or before the late count time">On Time</th>
                            <th class="text-center" title="Days arrived after shift in-time">Late In</th>
                            <th class="text-center">Total Late</th>
                            <th class="text-center">Early Out</th>
                            <th class="text-center" title="Days with a missing in or out punch">Missing Punch</th>
                            <th class="text-center">Earliest In</th>
                            <th class="text-center">Avg In</th>
                            <th class="text-center">Working Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $row)
                            @php
                                $absent  = max(0, $working_days - (int) $row->present_days);
                                // offset from shift start, so it also works for night shifts (00:10 is late for 22:00)
                                $avgLate = $row->std_in && $row->avg_in && ($off = \App\Models\Shift::secondsAfter($row->std_in, $row->avg_in)) > 0 && $off < 43200;
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $summary->firstItem() + $loop->index }}</td>
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
                                        <span class="rpt-sub">{{ $row->shift_title ?? 'Default' }}: {{ \App\Services\AttendanceReportService::shiftClock($row->std_in) }} - {{ \App\Services\AttendanceReportService::shiftClock($row->std_out, $row->std_in) }}</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="rpt-pill blue">{{ $row->present_days }}</span></td>
                                <td class="text-center"><span class="rpt-pill {{ $absent ? 'red' : 'gray' }}">{{ $absent }}</span></td>
                                <td class="text-center"><span class="rpt-pill green">{{ (int) $row->early_in_days }}</span></td>
                                <td class="text-center"><span class="rpt-pill {{ $row->late_days ? 'red' : 'gray' }}">{{ (int) $row->late_days }}</span></td>
                                <td class="text-center text-nowrap {{ $row->late_minutes ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $hm($row->late_minutes) }}</td>
                                <td class="text-center">
                                    <span class="rpt-pill {{ $row->early_out_days ? 'amber' : 'gray' }}">{{ (int) $row->early_out_days }}</span>
                                    @if($row->early_out_minutes)<span class="rpt-sub">{{ $hm($row->early_out_minutes) }}</span>@endif
                                </td>
                                <td class="text-center"><span class="rpt-pill gray">{{ (int) $row->single_punch_days }}</span>
                                    @if((int) $row->outside_punches > 0)<span class="rpt-sub bad" title="Punches outside the shift window">{{ $row->outside_punches }} outside</span>@endif</td>
                                <td class="text-center text-nowrap rpt-time ok">{{ timeFormat($row->earliest_in, 'h:i A') }}</td>
                                <td class="text-center text-nowrap rpt-time {{ $avgLate ? 'bad' : 'ok' }}">{{ timeFormat($row->avg_in, 'h:i A') }}</td>
                                <td class="text-center text-nowrap">
                                    <span class="font-weight-bold">{{ $hm($row->work_minutes) }}</span>
                                    @php $outDays = (int) $row->present_days - (int) $row->single_punch_days; @endphp
                                    @if($outDays > 0)
                                        <span class="rpt-sub">avg {{ $hm(intdiv((int) $row->work_minutes, $outDays)) }}/day</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x d-block mb-3" style="opacity:.3"></i>
                                    No attendance found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center pt-3">
                {!! $summary->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
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
