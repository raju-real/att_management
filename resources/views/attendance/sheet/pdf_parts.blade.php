{{--
    PDF pieces of the Month & Day Wise Summary, written to mPDF in small
    chunks (see AttendanceController::monthSheetPdf). Short CSS classes
    instead of inline styles keep each chunk well under pcre.backtrack_limit.

    @param string $part     'css' | 'header' | 'table' | 'legend'
    @param array  $sheet    MonthlySheetService::build()
    @param array  $section  current shift section          (header/table)
    @param array  $teachers slice of $section['teachers']  (table)
--}}
@if($part === 'css')
    body { font-family: sans-serif; font-size: 8pt; color: #000; }
    table { border-collapse: collapse; }
    .hd { width: 100%; margin-bottom: 5px; font-size: 9pt; }
    .sn { font-size: 15pt; font-weight: bold; text-align: center; }
    .g { width: 100%; font-size: 6.5pt; }
    .g th, .g td { border: 1px solid #000; padding: 1px 2px; text-align: center; vertical-align: middle; }
    .g th { font-weight: bold; }
    .g .l { text-align: left; }
    .g .w { width: 30mm; }
    .g .d { width: 6.1mm; }
    .g .h { background-color: #e5e7eb; color: #6b7280; }
    .g .r { color: #dc2626; font-weight: bold; }
    .g .b { font-weight: bold; }
    .g .tt { white-space: nowrap; width: 11mm; }
    .lg { font-size: 7pt; color: #555; margin-top: 4px; }
@elseif($part === 'header')
    @php
        $logo = siteSettings()->logo ?? null;
        $logoPath = $logo && is_file(public_path($logo)) ? public_path($logo) : null;
    @endphp
    <table class="hd">
        <tr>
            <td style="width:30%;vertical-align:bottom;">@if($logoPath)<img src="{{ $logoPath }}" style="height:48px;">@endif</td>
            <td style="width:40%;vertical-align:bottom;" class="sn">{{ siteSettings()->site_name ?? 'School Name' }}</td>
            <td style="width:30%;"></td>
        </tr>
        <tr>
            <td colspan="2">Monthly Teacher's Attendance Report<br>
                <b>{{ $section['title'] }}</b>@if($section['timing']) <span style="color:#555;">({{ $section['timing'] }})</span>@endif</td>
            <td style="text-align:right;">Month: {{ $sheet['month_name'] }} &nbsp;&nbsp; Year : {{ $sheet['year'] }}<br>{{ $sheet['range_label'] }}</td>
        </tr>
    </table>
@elseif($part === 'table')
    <table class="g">
        <thead>
            <tr>
                <th>SL<br>No</th><th>ID<br>No</th><th class="l w">Teacher's Name</th><th>Time<br>Table</th>
                @foreach($sheet['days'] as $day)<th class="d{{ $day['holiday'] ? ' h' : '' }}">{{ $day['day'] }}<br>{{ $day['abbr'] }}</th>@endforeach
                <th>Total<br>Present</th><th>Total<br>Absent</th><th>Total<br>Late</th><th>Over<br>Time</th><th>Attendance<br>Percentage %</th>
            </tr>
        </thead>
        <tbody>
        @foreach($teachers as $t)
            <tr>
                <td rowspan="2">{{ $t['sl'] }}</td><td rowspan="2">{{ $t['no'] }}</td><td rowspan="2" class="l">{{ $t['name'] }}</td><td class="tt">In Time</td>
                @foreach($sheet['days'] as $d => $day)@php $c = $t['cells'][$d]; @endphp@if($c['type'] === 'present')<td class="{{ $c['late'] ? 'r' : '' }}{{ $day['holiday'] ? ' h' : '' }}">{{ $c['in'] }}</td>@elseif($c['type'] === 'absent')<td class="r">A</td>@elseif($c['type'] === 'holiday')<td class="h">H</td>@else<td></td>@endif @endforeach
                <td rowspan="2" class="b">{{ $t['present'] }}</td>
                <td rowspan="2" class="b{{ $t['absent'] ? ' r' : '' }}">{{ $t['absent'] }}</td>
                <td rowspan="2" class="b{{ $t['late'] ? ' r' : '' }}">{{ $t['late'] }}</td>
                <td rowspan="2">{{ $t['overtime'] }}</td>
                <td rowspan="2" class="b">{{ $t['percent'] === null ? '-' : $t['percent'] . '%' }}</td>
            </tr>
            <tr>
                <td class="tt">Out Time</td>
                @foreach($sheet['days'] as $d => $day)@php $c = $t['cells'][$d]; @endphp@if($c['type'] === 'present')<td class="{{ $c['early_out'] ? 'r' : '' }}{{ $day['holiday'] ? ' h' : '' }}">{{ $c['out'] }}</td>@elseif($c['type'] === 'holiday')<td class="h"></td>@else<td></td>@endif @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@elseif($part === 'legend')
    <div class="lg">A = Absent &nbsp;·&nbsp; H = Holiday &nbsp;·&nbsp; <span style="color:#dc2626;font-weight:bold;">Red</span> = Late in / Early out
        &nbsp;·&nbsp; - = Missing punch &nbsp;·&nbsp; + = Next day (night shift) &nbsp;·&nbsp; Working days: {{ $sheet['working_days'] }}
        &nbsp;·&nbsp; Over Time = worked after shift out time (h:mm)</div>
@endif
