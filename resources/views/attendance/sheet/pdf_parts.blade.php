{{--
    PDF pieces of the Teacher/Staff Attendance Report, written to mPDF in small
    chunks (see AttendanceController::monthSheetPdf). Short CSS classes
    instead of inline styles keep each chunk well under pcre.backtrack_limit.
    School details come from Site Settings (schoolInfo()).

    @param string $part     'css' | 'header' | 'table' | 'legend' | 'signatures' | 'footer'
    @param array  $sheet    MonthlySheetService::buildRange()
    @param array  $section  current shift section          (header/table)
    @param array  $teachers slice of $section['teachers']  (table)
--}}
@if($part === 'css')
    body { font-family: sans-serif; font-size: 8pt; color: #000; }
    table { border-collapse: collapse; }
    .lh { width: 100%; }
    .lh td { padding: 1px 4px; }
    .sn { font-size: 16pt; font-weight: bold; text-align: center; }
    .ad { font-size: 8.5pt; text-align: center; color: #333; }
    .ct { font-size: 7.5pt; text-align: center; color: #555; }
    .rt { width: 100%; border-top: 1.5px solid #1e1b4b; margin-bottom: 4px; }
    .rt td { padding: 1px 4px; font-size: 8pt; }
    .ti { font-size: 11pt; font-weight: bold; text-align: center; padding-top: 4px; }
    .st { text-align: center; color: #555; font-size: 7.5pt; padding-bottom: 3px; }
    .g { width: 100%; font-size: 6.5pt; }
    .g th, .g td { border: 1px solid #000; padding: 1px 2px; text-align: center; vertical-align: middle; }
    .g th { font-weight: bold; }
    .g .l { text-align: left; }
    .g .w { width: 30mm; }
    .g .d { width: 6.1mm; }
    .g .mm { color: #4338ca; font-size: 5.5pt; }
    .g .h { background-color: #e5e7eb; color: #6b7280; }
    .g .r { color: #dc2626; font-weight: bold; }
    .g .b { font-weight: bold; }
    .g .tt { white-space: nowrap; width: 11mm; }
    .lg { font-size: 7pt; color: #555; margin-top: 4px; }
    .sg { width: 100%; margin-top: 30px; }
    .sg td { text-align: center; font-size: 8pt; }
    .sg .ln { width: 24%; border-top: 1px solid #000; padding-top: 2px; }
    .sg .gp { width: 14%; }
    .g .c-sl { width: 6mm; } .g .c-id { width: 11mm; } .g .c-tot { width: 13mm; } .g .c-pc { width: 17mm; }
    .ft { width: 100%; font-size: 7pt; color: #666; border-top: 0.5px solid #999; }
@elseif($part === 'header')
    @php $school = schoolInfo(); @endphp
    <table class="lh">
        <tr>
            <td style="width:18%;" rowspan="3">@if($school->logo_path)<img src="{{ $school->logo_path }}" style="height:52px;">@endif</td>
            <td style="width:64%;" class="sn">{{ $school->name }}</td>
            <td style="width:18%;" rowspan="3"></td>
        </tr>
        <tr><td class="ad">{{ $school->address }}</td></tr>
        <tr><td class="ct">{{ $school->contact_line }}</td></tr>
    </table>
    <table class="rt">
        <tr><td colspan="4" class="ti">Teacher/Staff Attendance Report</td></tr>
        <tr><td colspan="4" class="st">Date wise In / Out Time Summary @if(!$school->is_institute && isSuperAdmin()) &nbsp;·&nbsp; All Institutes @endif</td></tr>
        <tr>
            <td><b>Shift:</b> {{ $section['title'] }}@if($section['timing']) ({{ $section['timing'] }})@endif</td>
            <td>@if($sheet['filter_label'])<b>{{ \Illuminate\Support\Str::before($sheet['filter_label'], ':') }}:</b>{{ \Illuminate\Support\Str::after($sheet['filter_label'], ':') }}@endif</td>
            <td style="text-align:right;">@if($sheet['is_full_month'])<b>Month:</b> {{ $sheet['month_name'] }} {{ $sheet['year'] }} &nbsp;&nbsp;@endif<b>Period:</b> {{ $sheet['period_label'] }} ({{ $sheet['day_count'] }} days)</td>
            <td style="text-align:right;width:16%;"><b>Working days:</b> {{ $sheet['working_days'] }}</td>
        </tr>
    </table>
@elseif($part === 'table')
    <table class="g">
        <thead>
            <tr>
                <th class="c-sl">SL<br>No</th><th class="c-id">ID<br>No</th><th class="l w">Teacher/Staff Name</th><th class="tt">Time<br>Table</th>
                @foreach($sheet['days'] as $day)<th class="{{ $sheet['day_count'] >= 28 ? 'd' : '' }}{{ $day['holiday'] ? ' h' : '' }}">@if($day['month_mark'])<span class="mm">{{ $day['month_mark'] }}</span><br>@endif{{ $day['day'] }}<br>{{ $day['abbr'] }}</th>@endforeach
                <th class="c-tot">Total<br>Present</th><th class="c-tot">Total<br>Absent</th><th class="c-tot">Total<br>Late</th><th class="c-tot">Over<br>Time</th><th class="c-pc">Attendance<br>Percentage %</th>
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
@elseif($part === 'signatures')
    <table class="sg">
        <tr>
            <td class="ln">Prepared By</td><td class="gp"></td>
            <td class="ln">Checked By</td><td class="gp"></td>
            <td class="ln">Head Teacher / Principal</td>
        </tr>
    </table>
@elseif($part === 'footer')
    <table class="ft">
        <tr>
            <td style="width:40%;">{{ schoolInfo()->name }} &nbsp;·&nbsp; Teacher/Staff Attendance Report &nbsp;·&nbsp; {{ $sheet['period_label'] }}</td>
            <td style="width:35%;text-align:center;">Generated {{ now()->format('d M Y, h:i A') }}@if(auth()->user()) by {{ auth()->user()->name }}@endif</td>
            <td style="width:25%;text-align:right;">Page {PAGENO} of {nbpg}</td>
        </tr>
    </table>
@endif
