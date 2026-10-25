<table style="width:100%;font-size:7pt;color:#666;border-top:0.5px solid #999;">
    <tr>
        <td style="width:45%;">{{ $school->name }} &nbsp;·&nbsp; Teacher/Staff Profile &nbsp;·&nbsp; {{ $teacher->name }} ({{ $teacher->teacher_no }})</td>
        <td style="width:35%;text-align:center;">Generated {{ now()->format('d M Y, h:i A') }}@if(auth()->user()) by {{ auth()->user()->name }}@endif</td>
        <td style="width:20%;text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
