{{--
    Standard report letterhead + report info strip (web page and Excel).
    Everything comes from Site Settings via schoolInfo() and from the
    report filters — nothing is hard-coded.

    @param array  $sheet    MonthlySheetService::buildRange()
    @param array  $section  current shift section
    @param string $mode     'web' | 'xlsx'
--}}
@php
    $school = schoolInfo();
    $xlsx   = $mode === 'xlsx';
    $cell   = 'padding:2px 6px;';
@endphp
<table style="width:100%;border-collapse:collapse;margin-bottom:4px;">
    <tr>
        <td style="width:18%;vertical-align:middle;{{ $cell }}" rowspan="3">
            @if(!$xlsx && $school->logo_url)
                <img src="{{ $school->logo_url }}" alt="" style="height:64px;max-width:130px;">
            @endif
        </td>
        <td style="width:64%;text-align:center;{{ $cell }}font-size:{{ $xlsx ? '16' : '22px' }};font-weight:bold;">{{ $school->name }}</td>
        <td style="width:18%;" rowspan="3"></td>
    </tr>
    <tr>
        <td style="text-align:center;{{ $cell }}color:#333;">{{ $school->address }}</td>
    </tr>
    <tr>
        <td style="text-align:center;{{ $cell }}color:#555;font-size:{{ $xlsx ? '9' : '12px' }};">{{ $school->contact_line }}</td>
    </tr>
</table>
<table style="width:100%;border-collapse:collapse;border-top:2px solid #1e1b4b;margin-bottom:6px;">
    <tr>
        <td colspan="4" style="text-align:center;padding:6px 0 2px;font-size:{{ $xlsx ? '13' : '16px' }};font-weight:bold;letter-spacing:.5px;">
            Teacher/Staff Attendance Report
        </td>
    </tr>
    <tr>
        <td colspan="4" style="text-align:center;padding:0 0 6px;color:#555;">
            Date wise In / Out Time Summary @if(!$school->is_institute && isSuperAdmin()) &nbsp;·&nbsp; All Institutes @endif
        </td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><b>Shift:</b> {{ $section['title'] }}@if($section['timing']) ({{ $section['timing'] }})@endif</td>
        <td style="{{ $cell }}">@if($sheet['filter_label'])<b>{{ \Illuminate\Support\Str::before($sheet['filter_label'], ':') }}:</b>{{ \Illuminate\Support\Str::after($sheet['filter_label'], ':') }}@endif</td>
        <td style="{{ $cell }}text-align:right;">
            @if($sheet['is_full_month'])<b>Month:</b> {{ $sheet['month_name'] }} {{ $sheet['year'] }} &nbsp;&nbsp;@endif
            <b>Period:</b> {{ $sheet['period_label'] }} ({{ $sheet['day_count'] }} days)
        </td>
        <td style="{{ $cell }}text-align:right;width:16%;"><b>Working days:</b> {{ $sheet['working_days'] }}</td>
    </tr>
</table>
