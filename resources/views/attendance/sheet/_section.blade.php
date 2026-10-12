{{--
    One shift section of the Month & Day Wise Summary (header + table).
    Shared by the web page, the PDF and the Excel export, so all three
    always show the same numbers.

    @param array  $sheet    MonthlySheetService::build()
    @param array  $section  one of $sheet['sections']
    @param string $mode     'web' | 'pdf' | 'xlsx'
--}}
@php
    $mode    = $mode ?? 'web';
    $logo    = siteSettings()->logo ?? null;
    $logoSrc = null;
    if ($logo && $mode !== 'xlsx' && is_file(public_path($logo))) {
        $logoSrc = $mode === 'pdf' ? public_path($logo) : asset($logo);
    }
    $b   = 'border:1px solid #000;';
    $th  = $b . 'padding:2px 3px;font-weight:bold;text-align:center;vertical-align:middle;';
    $td  = $b . 'padding:1px 2px;text-align:center;vertical-align:middle;';
    $hol = 'background-color:#e5e7eb;';
    $fs  = $mode === 'pdf' ? 'font-size:6.5pt;' : 'font-size:11px;';
    // Equal width for every day column (wide enough for "06:30+").
    $dayW = $mode === 'pdf' ? 'width:6.1mm;' : ($mode === 'web' ? 'min-width:46px;' : '');
@endphp

{{-- ── Report header (as on the printed sheet) ── --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:6px;{{ $mode === 'pdf' ? 'font-size:9pt;' : '' }}">
    <tr>
        <td style="width:30%;vertical-align:bottom;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="" style="height:{{ $mode === 'pdf' ? '48px' : '60px' }};max-width:120px;">
            @endif
        </td>
        <td style="width:40%;text-align:center;vertical-align:bottom;">
            <div style="font-size:{{ $mode === 'pdf' ? '15pt' : '20px' }};font-weight:bold;">{{ siteSettings()->site_name ?? 'School Name' }}</div>
        </td>
        <td style="width:30%;"></td>
    </tr>
    <tr>
        <td colspan="2" style="padding-top:4px;">
            Monthly Teacher's Attendance Report<br>
            <b>{{ $section['title'] }}</b>@if($section['timing']) <span style="color:#555;">({{ $section['timing'] }})</span>@endif
        </td>
        <td style="text-align:right;padding-top:4px;">
            Month: {{ $sheet['month_name'] }} &nbsp;&nbsp; Year : {{ $sheet['year'] }}<br>
            {{ $sheet['range_label'] }}
        </td>
    </tr>
</table>

{{-- ── Attendance grid ── --}}
<table class="ms-table" style="width:100%;border-collapse:collapse;{{ $fs }}">
    <thead>
        <tr>
            <th style="{{ $th }}">SL<br>No</th>
            <th style="{{ $th }}">ID<br>No</th>
            <th style="{{ $th }}text-align:left;{{ $mode === 'pdf' ? 'width:30mm;' : 'min-width:150px;' }}">Teacher's Name</th>
            <th style="{{ $th }}">Time<br>Table</th>
            @foreach($sheet['days'] as $day)
                <th style="{{ $th }}{{ $dayW }}{{ $day['holiday'] ? $hol : '' }}">{{ $day['day'] }}<br>{{ $day['abbr'] }}</th>
            @endforeach
            <th style="{{ $th }}">Total<br>Present</th>
            <th style="{{ $th }}">Total<br>Absent</th>
            <th style="{{ $th }}">Total<br>Late</th>
            <th style="{{ $th }}">Over<br>Time</th>
            <th style="{{ $th }}">Attendance<br>Percentage %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($section['teachers'] as $t)
            {{-- In Time row --}}
            <tr>
                <td style="{{ $td }}" rowspan="2">{{ $t['sl'] }}</td>
                <td style="{{ $td }}" rowspan="2">{{ $t['no'] }}</td>
                <td style="{{ $td }}text-align:left;" rowspan="2">{{ $t['name'] }}</td>
                <td style="{{ $td }}white-space:nowrap;">In Time</td>
                @foreach($sheet['days'] as $d => $day)
                    @php $c = $t['cells'][$d]; @endphp
                    @if($c['type'] === 'present')
                        <td style="{{ $td }}{{ $c['late'] ? 'color:#dc2626;font-weight:bold;' : '' }}{{ $day['holiday'] ? $hol : '' }}">{{ $c['in'] }}</td>
                    @elseif($c['type'] === 'absent')
                        <td style="{{ $td }}color:#dc2626;font-weight:bold;">A</td>
                    @elseif($c['type'] === 'holiday')
                        <td style="{{ $td }}{{ $hol }}color:#6b7280;">H</td>
                    @else
                        <td style="{{ $td }}"></td>
                    @endif
                @endforeach
                <td style="{{ $td }}font-weight:bold;" rowspan="2">{{ $t['present'] }}</td>
                <td style="{{ $td }}font-weight:bold;{{ $t['absent'] ? 'color:#dc2626;' : '' }}" rowspan="2">{{ $t['absent'] }}</td>
                <td style="{{ $td }}font-weight:bold;{{ $t['late'] ? 'color:#dc2626;' : '' }}" rowspan="2">{{ $t['late'] }}</td>
                <td style="{{ $td }}" rowspan="2">{{ $t['overtime'] }}</td>
                <td style="{{ $td }}font-weight:bold;" rowspan="2">{{ $t['percent'] === null ? '-' : $t['percent'] . '%' }}</td>
            </tr>
            {{-- Out Time row --}}
            <tr>
                <td style="{{ $td }}white-space:nowrap;">Out Time</td>
                @foreach($sheet['days'] as $d => $day)
                    @php $c = $t['cells'][$d]; @endphp
                    @if($c['type'] === 'present')
                        <td style="{{ $td }}{{ $c['early_out'] ? 'color:#dc2626;font-weight:bold;' : '' }}{{ $day['holiday'] ? $hol : '' }}">{{ $c['out'] }}</td>
                    @elseif($c['type'] === 'holiday')
                        <td style="{{ $td }}{{ $hol }}"></td>
                    @else
                        <td style="{{ $td }}"></td>
                    @endif
                @endforeach
            </tr>
        @empty
            <tr><td style="{{ $td }}padding:12px;" colspan="{{ 9 + count($sheet['days']) }}">No teachers in this shift.</td></tr>
        @endforelse
    </tbody>
</table>
<div style="{{ $mode === 'pdf' ? 'font-size:7pt;' : 'font-size:11px;' }}color:#555;margin-top:4px;">
    A = Absent &nbsp;·&nbsp; H = Holiday &nbsp;·&nbsp; <span style="color:#dc2626;font-weight:bold;">Red</span> = Late in / Early out
    &nbsp;·&nbsp; - = Missing punch &nbsp;·&nbsp; + = Next day (night shift) &nbsp;·&nbsp; Working days: {{ $sheet['working_days'] }}
    &nbsp;·&nbsp; Over Time = worked after shift out time (h:mm)
</div>
