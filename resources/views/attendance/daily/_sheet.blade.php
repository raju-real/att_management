{{--
    Daily Report sheet — the same markup for the page and the A4 PDF (mPDF).

    @param array  $report  DailyReportService::build()
    @param object $school  schoolInfo($instituteId): the selected institute's own name / logo
    @param string $mode    'web' | 'pdf'
--}}
@php
    $pdf  = $mode === 'pdf';
    $logo = $pdf ? $school->logo_path : $school->logo_url;
    $t    = $report['total'];
    $showUpcoming = $t['upcoming'] > 0;
    $showHoliday  = $t['holiday'] > 0;
@endphp
<style>
    .dr { font-family: {{ $pdf ? 'dejavusanscondensed, sans-serif' : 'inherit' }}; color: #111; font-size: {{ $pdf ? '9.5pt' : '14px' }}; }
    .dr table { width: 100%; border-collapse: collapse; }
    @if($pdf) .dr td, .dr th { font-family: dejavusanscondensed, sans-serif; } @endif
    .dr .hd td { vertical-align: middle; }
    .dr .sn { font-size: {{ $pdf ? fitFontSize($school->name, 17, 120) . 'pt' : '24px' }}; font-weight: bold; text-align: center; white-space: nowrap; }
    .dr .ad { text-align: center; color: #333; font-weight: bold; }
    .dr .ct { text-align: center; color: #555; font-size: {{ $pdf ? '8pt' : '12px' }}; }
    .dr .ti { text-align: center; font-size: {{ $pdf ? '13pt' : '19px' }}; font-weight: bold; margin-top: 4px; }
    .dr .st { color: #dc2626; font-size: {{ $pdf ? '12pt' : '18px' }}; font-weight: bold; }
    .dr .g th { border: 1px solid #333; background: #f3f4f6; padding: {{ $pdf ? '5px 4px' : '8px 6px' }}; text-align: center; }
    .dr .g th.l, .dr .g td.l { text-align: left; }
    .dr .g td { border: 1px solid #555; padding: {{ $pdf ? '5px 4px' : '8px 6px' }}; text-align: center; }
    .dr .g tr.tot td { font-weight: bold; background: #f3f4f6; }
    .dr .r { color: #dc2626; font-weight: bold; }
    .dr .note { font-size: {{ $pdf ? '7.5pt' : '12px' }}; color: #666; margin-top: 4px; }
    .dr .hol { border: 1px solid #c4b5fd; background: #f5f3ff; color: #6d28d9; padding: 6px 10px; margin: 6px 0; font-weight: bold; text-align: center; }
    .dr .sg { margin-top: {{ $pdf ? '100px' : '90px' }}; } /* room to sign above the lines */
    .dr .sg td { text-align: center; font-size: {{ $pdf ? '9pt' : '13px' }}; }
    .dr .sg .ln { border-top: 1px solid #000; width: 26%; padding-top: 2px; }
</style>

<div class="dr">
    {{-- ── Letterhead (the selected institute's own logo, name and address) ── --}}
    <table class="hd">
        <tr>
            <td style="width:18%;">@if($logo)<img src="{{ $logo }}" style="height:{{ $pdf ? '62px' : '78px' }};max-width:130px;">@endif</td>
            <td style="width:64%;">
                <div class="sn {{ $pdf ? '' : 'fit-one-line' }}">{{ $school->name }}</div>
                @if($school->address)<div class="ad">{{ $school->address }}</div>@endif
                @if($school->contact_line)<div class="ct">{{ $school->contact_line }}</div>@endif
                <div class="ti">Employee Report</div>
            </td>
            <td style="width:18%;"></td>
        </tr>
    </table>
    <table style="margin:6px 0 8px;">
        <tr>
            <td style="width:30%;"></td>
            <td style="width:40%;text-align:center;" class="st">Daily{{ $report['is_today'] ? ' / Today' : '' }} Report</td>
            <td style="width:30%;text-align:right;font-weight:bold;">Date: {{ $report['date_label'] }}</td>
        </tr>
    </table>

    @if($report['is_holiday'])
        <div class="hol">Holiday of this institute: nobody is counted absent.</div>
    @endif

    <table class="g">
        <thead>
            <tr>
                <th style="width:6%">Sl</th>
                <th class="l">Department</th>
                <th>Total Employee</th>
                <th>Total Present</th>
                <th>Total Absent</th>
                <th>Total Late Present</th>
                <th>Total Late</th>
                <th>Total Leave</th>
                @if($showUpcoming)<th>Shift Not Started</th>@endif
                @if($showHoliday)<th>Holiday</th>@endif
                <th>Present %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['sl'] }}</td>
                    <td class="l">{{ $row['department'] }}</td>
                    <td>{{ $row['employees'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td class="{{ $row['absent'] ? 'r' : '' }}">{{ $row['absent'] }}</td>
                    <td class="{{ $row['late_present'] ? 'r' : '' }}">{{ $row['late_present'] }}</td>
                    <td>{{ $row['late_time'] }}</td>
                    <td>-</td>
                    @if($showUpcoming)<td>{{ $row['upcoming'] }}</td>@endif
                    @if($showHoliday)<td>{{ $row['holiday'] }}</td>@endif
                    <td>{{ $row['percent'] === null ? '-' : $row['percent'] . '%' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 9 + ($showUpcoming ? 1 : 0) + ($showHoliday ? 1 : 0) }}" style="padding:14px;color:#888;">No departments yet.</td></tr>
            @endforelse
            <tr class="tot">
                <td colspan="2" class="l">Total</td>
                <td>{{ $t['employees'] }}</td>
                <td>{{ $t['present'] }}</td>
                <td>{{ $t['absent'] }}</td>
                <td>{{ $t['late_present'] }}</td>
                <td>{{ $t['late_time'] }}</td>
                <td>-</td>
                @if($showUpcoming)<td>{{ $t['upcoming'] }}</td>@endif
                @if($showHoliday)<td>{{ $t['holiday'] }}</td>@endif
                <td>{{ $t['percent'] === null ? '-' : $t['percent'] . '%' }}</td>
            </tr>
        </tbody>
    </table>
    <div class="note">
        Total Late Present = present but late &middot; Total Late = total late time (h:mm) &middot; Leave: no leave module yet
        @if($showUpcoming) &middot; Shift Not Started = shift begins later today (not yet absent)@endif
    </div>

    <table class="sg">
        <tr>
            <td class="ln">Prepared By</td><td style="width:11%"></td>
            <td class="ln">Checked By</td><td style="width:11%"></td>
            <td class="ln">Head Teacher / Principal</td>
        </tr>
    </table>
</div>
