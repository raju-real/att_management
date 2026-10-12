<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Teacher's Attendance Report - {{ $sheet['month_name'] }} {{ $sheet['year'] }}</title>
    <style>
        body { font-family: sans-serif; font-size: 8pt; color: #000; }
        table { border-collapse: collapse; }
        .ms-table thead { display: table-header-group; } /* repeat header on each page */
    </style>
</head>
<body>
    @forelse($sheet['sections'] as $i => $section)
        @if($i > 0)
            <pagebreak />
        @endif
        @include('attendance.sheet._section', ['sheet' => $sheet, 'section' => $section, 'mode' => 'pdf'])
    @empty
        <p>No teachers found for the selected filters.</p>
    @endforelse
</body>
</html>
