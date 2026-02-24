<table>
    <thead>
        <tr>
            <th colspan="10" style="background-color:#E9F2FF; text-align:center; font-size:17px;">
                Monthly Attendance Report
            </th>
        </tr>
        <tr>
            <th colspan="10" style="background-color:#E9F2FF; text-align:center;">
                Date Range: {{ dateFormat($from_date, 'd M, Y') }} to {{ dateFormat($to_date, 'd M, Y') }}
            </th>
        </tr>
        @if (isset($user_type))
            <tr>
                <th colspan="6" style="background-color:#E9F2FF; text-align:center;">
                    User : {{ ucfirst($user_type) }}
                </th>
            </tr>
        @endif

        <tr>
            <th colspan="10"></th>
        </tr>

        <tr>
            <th style="background-color:#E9F2FF;text-align: left">User Type</th>
            <th style="background-color:#E9F2FF;text-align: left">ID</th>
            <th style="background-color:#E9F2FF;text-align: left">Name</th>
            <th style="background-color:#E9F2FF;text-align: left">Earliest In</th>
            <th style="background-color:#E9F2FF;text-align: left">Late In</th>
            <th style="background-color:#E9F2FF;text-align: left">Earliest Out</th>
            <th style="background-color:#E9F2FF;text-align: left">Late Out</th>
            <th style="background-color:#E9F2FF;text-align: left">Total Present (in days)</th>
            <th style="background-color:#E9F2FF;text-align: left">Total Absent (in days)</th>
            <th style="background-color:#E9F2FF;text-align: left">Total Working Hour</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report as $row)
            <tr>
                <td style="text-align: left;">{{ ucfirst($row['user_type']) }}</td>
                <td style="text-align: left;">{{ $row['user_no'] }}</td>
                <td style="text-align: left;">{{ $row['name'] }}</td>
                <td style="text-align: left;">{{ timeFormat($row['earliest_in'], 'h:i a') }}</td>
                <td style="text-align: left;">{{ timeFormat($row['latest_late_in'], 'h:i a') }}</td>
                <td style="text-align: left;">{{ timeFormat($row['earliest_out'], 'h:i a') }}</td>
                <td style="text-align: left;">{{ timeFormat($row['latest_late_out'], 'h:i a') }}</td>
                <td style="text-align: left;">{{ $row['total_present_days'] }}</td>
                <td style="text-align: left;">{{ $row['total_absent_days'] }}</td>
                <td style="text-align: left;">{{ $row['total_working_hours'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
