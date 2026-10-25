{{--
    Teacher/Staff Profile sheet — the same markup for the web page and the
    A4 PDF (mPDF), so both always show the same thing.

    @param \App\Models\Teacher $teacher
    @param array  $attendance  TeacherProfileService::yearly()
    @param object $school      schoolInfo($teacher->institute_id)
    @param string $mode        'web' | 'pdf'
--}}
@php
    $pdf   = $mode === 'pdf';
    $logo  = $pdf ? $school->logo_path : $school->logo_url;
    $photo = $teacher->image && file_exists($teacher->image) ? ($pdf ? public_path($teacher->image) : asset($teacher->image)) : null;
    $d     = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('d-m-Y') : '';
    $v     = fn ($value) => filled($value) ? e($value) : '&nbsp;';
    $rows  = fn ($list) => array_values(array_filter((array) $list));
@endphp
<style>
    .tp { font-family: {{ $pdf ? 'dejavusanscondensed, sans-serif' : 'inherit' }}; color: #111; font-size: {{ $pdf ? '8.6pt' : '14px' }}; }
    .tp table { width: 100%; border-collapse: collapse; }
    @if($pdf) .tp td, .tp th { font-family: dejavusanscondensed, sans-serif; font-size: 8.6pt; } @endif
    .tp .hd td { vertical-align: middle; }
    .tp .sn { font-size: {{ $pdf ? '16pt' : '22px' }}; font-weight: bold; text-align: center; }
    .tp .ad { text-align: center; color: #333; }
    .tp .ct { text-align: center; color: #555; font-size: {{ $pdf ? '8pt' : '12px' }}; }
    .tp .ti { margin: 5px auto 0; width: auto; }
    .tp .ti td { border: 1px solid #1e1b4b; padding: 3px 18px; font-weight: bold; text-align: center;
        font-size: {{ $pdf ? '11pt' : '16px' }}; letter-spacing: .5px; background: #eef2ff; }
    .tp .ph { width: 26mm; height: 30mm; border: 1px solid #555; text-align: center; vertical-align: middle; color: #888; font-size: 8pt; }
    .tp .sec { background: #1e1b4b; color: #fff; font-weight: bold; padding: {{ $pdf ? '3px 7px' : '4px 8px' }}; margin-top: {{ $pdf ? '7px' : '10px' }}; font-size: {{ $pdf ? '9.5pt' : '15px' }}; }
    .tp .kv td { border: 1px solid #bbb; padding: {{ $pdf ? '2px 5px' : '4px 6px' }}; }
    .tp .kv .k { width: 22%; background: #f3f4f6; font-weight: bold; }
    .tp .g th { border: 1px solid #888; background: #e5e7eb; padding: {{ $pdf ? '2px' : '4px' }}; font-size: {{ $pdf ? '8pt' : '13px' }}; }
    .tp .g td { border: 1px solid #aaa; padding: {{ $pdf ? '1.5px 4px' : '3px 4px' }}; text-align: center; }
    .tp .g td.l { text-align: left; }
    .tp .g tr.tot td { font-weight: bold; background: #f3f4f6; }
    .tp .muted { color: #999; }
    .tp .sg { margin-top: {{ $pdf ? '14px' : '36px' }}; }
    .tp .sg td { text-align: center; font-size: 9pt; }
    .tp .sg .ln { border-top: 1px solid #000; width: 30%; padding-top: 2px; }
</style>

<div class="tp">
    {{-- ── Letterhead + photo ── --}}
    <table class="hd">
        <tr>
            <td style="width:20%;">@if($logo)<img src="{{ $logo }}" style="height:{{ $pdf ? '50px' : '70px' }};max-width:120px;">@endif</td>
            <td style="width:56%;">
                <div class="sn">{{ $school->name }}</div>
                @if($school->address)<div class="ad">{{ $school->address }}</div>@endif
                @if($school->contact_line)<div class="ct">{{ $school->contact_line }}</div>@endif
                <table class="ti"><tr><td>Teacher/Staff Profile</td></tr></table>
            </td>
            <td style="width:24%;text-align:right;">
                <table style="width:auto;margin-left:auto;"><tr>
                    <td class="ph">@if($photo)<img src="{{ $photo }}" style="width:24mm;height:28mm;">@else Photo @endif</td>
                </tr></table>
            </td>
        </tr>
    </table>

    {{-- ── Personal information ── --}}
    <div class="sec">Personal Information</div>
    <table class="kv">
        <tr><td class="k">Name</td><td colspan="3"><b>{!! $v($teacher->name) !!}</b></td></tr>
        <tr><td class="k">ID No (Device)</td><td>{!! $v($teacher->teacher_no) !!}</td><td class="k">Designation</td><td>{!! $v($teacher->designation) !!}</td></tr>
        <tr><td class="k">Department</td><td>{!! $v($teacher->department->name ?? null) !!}</td><td class="k">Shift</td><td>{!! $v($teacher->department->shift->title ?? null) !!}</td></tr>
        <tr><td class="k">Date of Birth</td><td>{!! $v($d($teacher->date_of_birth)) !!}</td><td class="k">Blood Group</td><td>{!! $v($teacher->blood_group) !!}</td></tr>
        <tr><td class="k">Gender</td><td>{!! $v($teacher->gender) !!}</td><td class="k">Religion</td><td>{!! $v($teacher->religion) !!}</td></tr>
        <tr><td class="k">Mobile</td><td>{!! $v($teacher->mobile) !!}</td><td class="k">Email</td><td>{!! $v($teacher->email) !!}</td></tr>
        <tr><td class="k">Subject Teacher</td><td colspan="3">{!! $v($teacher->subject) !!}</td></tr>
        <tr><td class="k">Present Address</td><td colspan="3">{!! $v($teacher->present_address) !!}</td></tr>
        <tr><td class="k">Permanent Address</td><td colspan="3">{!! $v($teacher->permanent_address) !!}</td></tr>
    </table>

    {{-- ── Education ── --}}
    <div class="sec">Education Qualification</div>
    <table class="g">
        <thead><tr><th style="width:8%">SL</th>@foreach(\App\Models\Teacher::EDUCATION_COLUMNS as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows($teacher->education) as $i => $row)
                <tr><td>{{ $i + 1 }}</td>@foreach(array_keys(\App\Models\Teacher::EDUCATION_COLUMNS) as $k)<td class="{{ $k === 'institute' ? 'l' : '' }}">{!! $v($row[$k] ?? null) !!}</td>@endforeach</tr>
            @empty
                <tr><td colspan="5" class="muted">No education information added.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Job information ── --}}
    <div class="sec">Job Information</div>
    <table class="kv">
        <tr><td class="k">Advertisement Newspaper &amp; Date</td><td colspan="3">{!! $v($teacher->newspaper_ad) !!}</td></tr>
        <tr><td class="k">Joining Date of this Institution</td><td>{!! $v($d($teacher->joining_date)) !!}</td><td class="k">Joining Post</td><td>{!! $v($teacher->joining_post) !!}</td></tr>
        <tr><td class="k">Time Scale</td><td>{!! $v($teacher->time_scale) !!}</td><td class="k">M.P.O / Index Number</td><td>{!! $v($teacher->mpo_index) !!}</td></tr>
    </table>

    {{-- ── Class / subject teacher ── --}}
    @foreach(['class_teacher' => 'Class Teacher', 'subject_teacher' => 'Subject Teacher'] as $field => $heading)
        <div class="sec">{{ $heading }}</div>
        <table class="g">
            <thead><tr><th style="width:8%">SL</th>@foreach(\App\Models\Teacher::CLASS_COLUMNS as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows($teacher->{$field}) as $i => $row)
                    <tr><td>{{ $i + 1 }}</td>@foreach(array_keys(\App\Models\Teacher::CLASS_COLUMNS) as $k)<td>{!! $v($row[$k] ?? null) !!}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="6" class="muted">Not assigned.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- ── Attendance of the year ── --}}
    <div class="sec" style="page-break-inside:avoid">Attendance Report - {{ $attendance['year'] }}</div>
    <table class="g" style="page-break-inside:avoid">
        <thead>
            <tr>
                <th style="text-align:left;width:16%">Month</th><th style="width:12%">Total Present</th><th style="width:12%">Total Absent</th><th style="width:12%">Total Late</th>
                <th style="width:12%">Late Present</th><th style="width:12%">Over Time</th><th style="width:12%">Leave</th><th style="width:12%">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendance['months'] as $m)
                <tr>
                    <td class="l">{{ $m['name'] }}</td>
                    @if($m['started'])
                        <td>{{ $m['present'] }}</td>
                        <td style="{{ $m['absent'] ? 'color:#dc2626;font-weight:bold;' : '' }}">{{ $m['absent'] }}</td>
                        <td>{{ $m['late'] }}</td>
                        <td style="{{ $m['late_days'] ? 'color:#dc2626;' : '' }}">{{ $m['late_days'] }}</td>
                        <td>{{ $m['overtime'] }}</td>
                        <td>-</td>
                        <td>{{ $m['percent'] === null ? '-' : $m['percent'] . '%' }}</td>
                    @else
                        <td colspan="7" class="muted">&nbsp;</td>
                    @endif
                </tr>
            @endforeach
            <tr class="tot">
                <td class="l">Total</td>
                <td>{{ $attendance['total']['present'] }}</td>
                <td>{{ $attendance['total']['absent'] }}</td>
                <td>{{ $attendance['total']['late'] }}</td>
                <td>{{ $attendance['total']['late_days'] }}</td>
                <td>{{ $attendance['total']['overtime'] }}</td>
                <td>-</td>
                <td>{{ $attendance['total']['percent'] === null ? '-' : $attendance['total']['percent'] . '%' }}</td>
            </tr>
        </tbody>
    </table>
    <div style="font-size:{{ $pdf ? '6.8pt' : '12px' }};color:#666;margin-top:2px;">
        Absent = working days without attendance (holidays, future days and days before joining not counted) &middot;
        Total Late = late time (h:mm) &middot; Late Present = days late &middot; Over Time = worked after shift out time
    </div>

    <table class="sg">
        <tr>
            <td class="ln">Teacher/Staff Signature</td><td style="width:40%"></td><td class="ln">Head Teacher / Principal</td>
        </tr>
    </table>
</div>
