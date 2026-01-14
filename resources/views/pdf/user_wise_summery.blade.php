<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User-wise Attendance Summary</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .company-logo {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .report-title {
            color: #2c3e50;
            margin: 5px 0;
            font-size: 22px;
            font-weight: bold;
        }

        .report-period {
            color: #7f8c8d;
            font-size: 14px;
            margin: 5px 0;
        }

        .report-date {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
            font-size: 11px;
        }

        .table-container {
            width: 100%;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #2c3e50;
            font-size: 11px;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #f1f8ff;
        }

        .summary-row {
            background-color: #e8f4f8 !important;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #666;
            font-size: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        .generated-info {
            text-align: right;
            font-size: 10px;
            color: #999;
            margin-bottom: 10px;
        }

        .highlight {
            color: #e74c3c;
            font-weight: bold;
        }

        .positive {
            color: #27ae60;
            font-weight: bold;
        }

        .total-section {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-label {
            font-weight: bold;
            color: #2c3e50;
        }

        .total-value {
            font-weight: bold;
            color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="generated-info">
        Generated on: {{ date('d M, Y h:i A') }}
    </div>

    <div class="header">
        <!-- Replace with your company logo or name -->
        <div class="company-name" style="font-size: 24px; font-weight: bold; color: #2c3e50;">
            COMPANY NAME
        </div>
        <div class="report-title">
            <i class="fas fa-user-clock"></i> User-wise Attendance Summary
        </div>
        <div class="report-period">
            Period: {{ $from_date }} to {{ $to_date ?? $from_date }}
        </div>
    </div>

    <div class="report-date">
        Report Date: {{ date('d M, Y') }}
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 15%;">User Type</th>
                    <th style="width: 15%;">User No</th>
                    <th style="width: 12%;">First In</th>
                    <th style="width: 12%;">Last Out</th>
                    <th style="width: 8%;" class="highlight">Early In</th>
                    <th style="width: 8%;" class="highlight">Late In</th>
                    <th style="width: 8%;" class="highlight">Early Out</th>
                    <th style="width: 8%;" class="highlight">Late Out</th>
                    <th style="width: 12%;" class="positive">Total Hours</th>
                    <th style="width: 12%;">Total Punches</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $counter = 1;
                    $totalEarlyIn = 0;
                    $totalLateIn = 0;
                    $totalEarlyOut = 0;
                    $totalLateOut = 0;
                    $totalHours = 0;
                    $totalPunches = 0;
                @endphp

                @forelse($attendance_reports as $row)
                    @php
                        $totalEarlyIn += intval($row['early_in']);
                        $totalLateIn += intval($row['late_in']);
                        $totalEarlyOut += intval($row['early_out']);
                        $totalLateOut += intval($row['late_out']);
                        $totalHours += floatval(str_replace('h', '', $row['total_hours']));
                        $totalPunches += intval($row['total_punch']);
                    @endphp

                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>{{ ucfirst($row['user_type']) }}</td>
                        <td>{{ $row['user_no'] }}</td>
                        <td>{{ $row['first_in'] ?? '-' }}</td>
                        <td>{{ $row['last_out'] ?? '-' }}</td>
                        <td class="highlight">{{ $row['early_in'] }}</td>
                        <td class="highlight">{{ $row['late_in'] }}</td>
                        <td class="highlight">{{ $row['early_out'] }}</td>
                        <td class="highlight">{{ $row['late_out'] }}</td>
                        <td class="positive">{{ $row['total_hours'] }}</td>
                        <td>{{ $row['total_punch'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 20px; color: #666;">
                            No attendance records found for the selected period
                        </td>
                    </tr>
                @endforelse

                @if(count($attendance_reports) > 0)
                <tr class="summary-row">
                    <td colspan="5" style="text-align: right; font-weight: bold;">TOTALS:</td>
                    <td class="highlight">{{ $totalEarlyIn }}</td>
                    <td class="highlight">{{ $totalLateIn }}</td>
                    <td class="highlight">{{ $totalEarlyOut }}</td>
                    <td class="highlight">{{ $totalLateOut }}</td>
                    <td class="positive">{{ number_format($totalHours, 1) }}h</td>
                    <td>{{ $totalPunches }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if(count($attendance_reports) > 0)
    <div class="total-section">
        <div class="total-row">
            <span class="total-label">Total Employees:</span>
            <span class="total-value">{{ count($attendance_reports) }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Total Early In Count:</span>
            <span class="total-value">{{ $totalEarlyIn }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Total Late In Count:</span>
            <span class="total-value">{{ $totalLateIn }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Total Early Out Count:</span>
            <span class="total-value">{{ $totalEarlyOut }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Total Late Out Count:</span>
            <span class="total-value">{{ $totalLateOut }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Average Hours per Employee:</span>
            <span class="total-value">{{ count($attendance_reports) > 0 ? number_format($totalHours / count($attendance_reports), 1) : 0 }}h</span>
        </div>
    </div>
    @endif

    <div class="footer">
        <div>--- End of Report ---</div>
        <div style="margin-top: 10px;">
            <strong>Confidential Document</strong> - For authorized personnel only
        </div>
        <div style="margin-top: 5px;">
            Page 1 of 1
        </div>
    </div>
</body>
</html>
