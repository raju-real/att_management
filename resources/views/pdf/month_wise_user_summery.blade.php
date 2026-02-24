<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User-wise Attendance Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 15px;
        }

        .report-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .date-range {
            text-align: center;
            font-size: 12px;
            margin-bottom: 3px;
            color: #555;
        }

        .generated {
            text-align: right;
            font-size: 10px;
            color: #999;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #DDEBF7;
            padding: 7px 6px;
            border: 1px solid #b0c4de;
            font-weight: bold;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .totals-row td {
            background-color: #E9F2FF;
            font-weight: bold;
        }

        .present-badge {
            color: #1a7d34;
            font-weight: bold;
        }

        .absent-badge {
            color: #c0392b;
            font-weight: bold;
        }

        .hours-badge {
            color: #1a5fa8;
            font-weight: bold;
        }

        .summary {
            margin-top: 15px;
            padding: 10px 14px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .summary-row {
            margin-bottom: 4px;
            font-size: 11px;
        }

        .footer {
            margin-top: 25px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>

<body>

    <div class="generated">Generated: {{ date('d M, Y h:i A') }}</div>

    <div class="report-title">User-wise Attendance Summary</div>
    <div class="date-range">Period: {{ dateFormat($from_date, 'd M, Y') }} &mdash; {{ dateFormat($to_date, 'd M, Y') }}
    </div>
    <br>

    @php
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalHours = 0.0;
        $counter = 1;
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:8%">Type</th>
                <th style="width:9%">ID</th>
                <th style="width:18%">Name</th>
                <th style="width:10%">Earliest In</th>
                <th style="width:10%">Latest In</th>
                <th style="width:10%">Earliest Out</th>
                <th style="width:10%">Latest Out</th>
                <th style="width:8%" class="present-badge">Present</th>
                <th style="width:7%" class="absent-badge">Absent</th>
                <th style="width:8%" class="hours-badge">Work Hrs</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendance_reports as $row)
                @php
                    $totalPresent += (int) $row['total_present_days'];
                    $totalAbsent += (int) $row['total_absent_days'];
                    $totalHours += (float) $row['total_working_hours'];
                @endphp
                <tr>
                    <td>{{ $counter++ }}</td>
                    <td>{{ ucfirst($row['user_type']) }}</td>
                    <td>{{ $row['user_no'] }}</td>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td style="color: {{ isLateIn($row['earliest_in']) ? '#c0392b' : '#1a7d34' }}; font-weight:bold;">
                        {{ timeFormat($row['earliest_in'], 'h:i a') }}
                    </td>
                    <td style="color:#c0392b; font-weight:bold;">
                        {{ timeFormat($row['latest_late_in'], 'h:i a') }}
                    </td>
                    <td style="color:#c0392b; font-weight:bold;">
                        {{ timeFormat($row['earliest_out'], 'h:i a') }}
                    </td>
                    <td style="color: {{ isEarlyOut($row['latest_late_out']) ? '#c0392b' : '#333' }};">
                        {{ timeFormat($row['latest_late_out'], 'h:i a') }}
                    </td>
                    <td class="present-badge">{{ $row['total_present_days'] }}</td>
                    <td class="absent-badge">{{ $row['total_absent_days'] }}</td>
                    <td class="hours-badge">{{ number_format((float) $row['total_working_hours'], 2) }}h</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; padding:18px; color:#888;">
                        No attendance records found for the selected period.
                    </td>
                </tr>
            @endforelse

            @if ($attendance_reports->count() > 0)
                <tr class="totals-row">
                    <td colspan="8" style="text-align:right;">TOTALS:</td>
                    <td class="present-badge">{{ $totalPresent }}</td>
                    <td class="absent-badge">{{ $totalAbsent }}</td>
                    <td class="hours-badge">{{ number_format($totalHours, 2) }}h</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if ($attendance_reports->count() > 0)
        <div class="summary">
            <div class="summary-row"><strong>Report Period:</strong> {{ dateFormat($from_date, 'd M, Y') }} to
                {{ dateFormat($to_date, 'd M, Y') }}</div>
            <div class="summary-row"><strong>Total Users:</strong> {{ $attendance_reports->count() }}</div>
            <div class="summary-row"><strong>Total Present Days:</strong> {{ $totalPresent }}</div>
            <div class="summary-row"><strong>Total Absent Days:</strong> {{ $totalAbsent }}</div>
            <div class="summary-row"><strong>Total Working Hours:</strong> {{ number_format($totalHours, 2) }}h</div>
            <div class="summary-row"><strong>Avg. Hours / User:</strong>
                {{ $attendance_reports->count() > 0 ? number_format($totalHours / $attendance_reports->count(), 2) : '0.00' }}h
            </div>
        </div>
    @endif

    <div class="footer">
        &mdash; End of Report &mdash;&nbsp;&nbsp;
        Confidential &mdash; For authorised personnel only
    </div>

</body>

</html>
