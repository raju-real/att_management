{{--
    Teacher attendance board — ONE markup for every board.
    Driven by public/assets/js/attendance-board.js, styled by public/assets/css/attendance-board.css.

    @param string      $theme     'dark' (dashboard) | 'light' (standalone page)
    @param bool        $detailed  show shift, in/out and early-out on each card
    @param string|null $date      initial date (Y-m-d), defaults to today
    @param int|null    $refreshSeconds  auto refresh interval, default 60
    @param array|null  $link      optional extra control: ['url' => .., 'icon' => .., 'title' => ..]
--}}
@php
    $theme    = $theme ?? 'dark';
    $detailed = $detailed ?? false;
    $date     = $date ?? \Carbon\Carbon::today()->toDateString();
    $perPage  = \App\Services\AttendanceReportService::boardPerPage();
    $tabs = [
        'all'       => ['All', '#a5b4fc', 'Every teacher'],
        'present'   => ['Present', '#60a5fa', 'Punched in at least once on this date'],
        'on_time'   => ['On Time', '#10b981', 'First punch at or before the shift in-time'],
        'late'      => ['Late In', '#ef4444', 'First punch after the shift in-time'],
        'early_out' => ['Early Out', '#f59e0b', 'Last punch before the shift out-time'],
        'absent'    => ['Absent', '#64748b', 'No punch on this date'],
        'upcoming'  => ['Upcoming', '#38bdf8', 'Shift has not started yet (today only)'],
    ];
@endphp
<div class="tb-board {{ $theme === 'light' ? 'tb-light' : '' }}"
     data-tb-board
     data-url="{{ route('attendance-board.data') }}"
     data-detailed="{{ $detailed ? 1 : 0 }}"
     data-keys="{{ $keys ?? 0 }}"
     data-refresh-ms="{{ ($refreshSeconds ?? 60) * 1000 }}"
     data-card-min="{{ $detailed ? 190 : 150 }}"
     data-per-page="{{ $perPage }}">

    <div class="tb-head">
        <div>
            <h4 class="tb-title"><i class="fas fa-crown mr-2"></i>{{ $title ?? 'Teacher Attendance Board' }}</h4>
            <div class="tb-date">
                <span data-tb="date-label">{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</span>
                <span class="tb-live ml-2" data-tb="live">Live</span>
                <span class="tb-updated ml-2" data-tb="updated"></span>
            </div>
        </div>
        <div class="text-right">
            <div class="tb-clock" data-tb="clock">--:--<small>--</small></div>
        </div>
    </div>

    <div class="tb-bar">
        <div class="tb-tabs" data-tb="tabs">
            @foreach($tabs as $key => [$label, $color, $hint])
                <div class="tb-tab {{ $key === 'all' ? 'active' : '' }}" data-filter="{{ $key }}" title="{{ $hint }}" role="button" tabindex="0">
                    <span class="dot" style="background:{{ $color }}"></span>{{ $label }} <b data-tb-count="{{ $key }}">0</b>
                </div>
            @endforeach
        </div>
        <div class="tb-controls">
            @php
                $boardDepts  = \App\Models\Department::orderBy('name')->get(['id', 'name', 'shift_id']);
                $boardShifts = \App\Models\Shift::orderBy('in_time')->get(['id', 'title', 'in_time', 'out_time']);
            @endphp
            <select class="tb-size-select" data-tb="department" aria-label="Department" title="Filter by department">
                <option value="">All departments</option>
                @foreach($boardDepts as $bd)
                    <option value="{{ $bd->id }}" data-shift="{{ $bd->shift_id }}">{{ $bd->name }}</option>
                @endforeach
            </select>
            <select class="tb-size-select" data-tb="shift" aria-label="Shift" title="Filter by shift (works with or without a department)">
                <option value="">All shifts</option>
                @foreach($boardShifts as $bs)
                    <option value="{{ $bs->id }}">{{ $bs->title }} ({{ \App\Services\AttendanceReportService::shiftClock($bs->in_time) }} - {{ \App\Services\AttendanceReportService::shiftClock($bs->out_time, $bs->in_time) }})</option>
                @endforeach
            </select>
            <input type="text" class="tb-date-input" data-tb="date" value="{{ $date }}" readonly aria-label="Date"
                   title="Pick a date to view its attendance">
            <select class="tb-size-select" data-tb="per-page" aria-label="Teachers per slide"
                    title="Teachers per slide (saved for your account)">
                @foreach(\App\Services\AttendanceReportService::BOARD_PAGE_SIZES as $size)
                    <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }} / slide</option>
                @endforeach
            </select>
            <button type="button" class="tb-btn" data-tb="prev" title="Previous"><i class="fas fa-chevron-left"></i></button>
            <button type="button" class="tb-btn" data-tb="play" title="Pause"><i class="fas fa-pause"></i></button>
            <button type="button" class="tb-btn" data-tb="next" title="Next"><i class="fas fa-chevron-right"></i></button>
            <button type="button" class="tb-btn" data-tb="full" title="Full screen"><i class="fas fa-expand"></i></button>
            @isset($link)
                <a href="{{ $link['url'] }}" class="tb-btn" title="{{ $link['title'] }}" @if(!empty($link['blank'])) target="_blank" rel="noopener" @endif>
                    <i class="{{ $link['icon'] }}"></i>
                </a>
            @endisset
        </div>
    </div>

    <div class="tb-viewport" data-tb="viewport">
        <div class="tb-loader show" data-tb="loader"><div class="tb-spin"></div></div>
        <div class="tb-track" data-tb="track"></div>
    </div>

    <div class="tb-foot">
        <div class="tb-dots" data-tb="dots"></div>
        <div class="tb-page" data-tb="page"></div>
    </div>
    <div class="tb-progress" data-tb="progress"></div>
</div>
