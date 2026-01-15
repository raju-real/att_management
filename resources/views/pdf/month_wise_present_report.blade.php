<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .date-range {
            text-align: center;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .date-header {
            background-color: #DDEBF7;
            font-weight: bold;
            padding: 6px;
        }

        th {
            background-color: #f2f2f2;
            padding: 8px 6px;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        td {
            padding: 6px;
            border: 1px solid #ddd;
        }

        .day-total {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .space-row td {
            height: 5px;
            border: none;
        }

        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f8f8;
        }

        .summary-row {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="report-title">Attendance Report</div>
    <div class="date-range">
        {{ dateFormat($from_date,'d M, Y') }} to {{ dateFormat($to_date,'d M, Y') }}
    </div>

    <table>
        @php
            $totalDays = count($attendance_reports);
            $totalPresent = 0;
            $totalHours = 0;
        @endphp

        @foreach($attendance_reports as $date => $records)
            @php
                $dayPresent = count($records);
                $totalPresent += $dayPresent;
                $dayHours = 0;
            @endphp

            <tr>
                <td colspan="6" class="date-header">
                    {{ \Carbon\Carbon::parse($date)->format('d M, Y') }} ({{ $dayPresent }} Present)
                </td>
            </tr>

            <tr>
                <th>User Type</th>
                <th>User No</th>
                <th>Name</th>
                <th>In Time</th>
                <th>Out Time</th>
                <th>Hours</th>
            </tr>

            @foreach($records as $record)
                @php
                    $hours = hourCount($record['out_time'], $record['in_time']);
                    $dayHours += floatval(str_replace('h', '', $hours));
                @endphp
                <tr>
                    <td>{{ ucfirst($record['user_type']) }}</td>
                    <td>{{ $record['user_no'] }}</td>
                    <td>{{ $record['name'] }}</td>
                    <td>{{ timeFormat($record['in_time'], 'h:i a') }}</td>
                    <td>{{ timeFormat($record['out_time'], 'h:i a') ?? '-' }}</td>
                    <td>{{ $hours }}</td>
                </tr>
            @endforeach

            @php
                $totalHours += $dayHours;
            @endphp

            <tr class="day-total">
                <td colspan="5" style="text-align: right;">Day Total:</td>
                <td>{{ number_format($dayHours, 1) }}h</td>
            </tr>

            @if(!$loop->last)
                <tr class="space-row"><td colspan="6"></td></tr>
            @endif
        @endforeach
    </table>

    <div class="summary">
        <div class="summary-row"><strong>Report Period:</strong> {{ dateFormat($from_date,'d M, Y') }} to {{ dateFormat($to_date,'d M, Y') }}</div>
        <div class="summary-row"><strong>Total Days:</strong> {{ $totalDays }}</div>
        <div class="summary-row"><strong>Total Present:</strong> {{ $totalPresent }}</div>
        <div class="summary-row"><strong>Total Hours:</strong> {{ number_format($totalHours, 1) }}</div>
    </div>
</body>
</html>
