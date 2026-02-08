<table>
    <thead>
    <tr>
        <th colspan="6" style="background-color:#E9F2FF; text-align:center; font-size:16px;">
            Attendance Report
        </th>
    </tr>
    <tr>
        <th colspan="6" style="background-color:#E9F2FF; text-align:center;">
            Date Range: {{ dateFormat($from_date,'d M, Y') }} to {{ dateFormat($to_date,'d M, Y') }}
        </th>
    </tr>
    @if($user_type)
        <tr>
            <th colspan="6" style="background-color:#E9F2FF; text-align:center;">
                User : {{ ucfirst($user_type) }}
            </th>
        </tr>
    @endif
    <tr></tr> <!-- empty row for spacing -->
    </thead>

    <tbody>
    @foreach($attendance_reports as $date => $records)
        <!-- Date Header -->
        <tr>
            <td colspan="6" style="background-color:{{ count($records) ? '#DDEBF7' : '#BD5642' }}; font-weight:bold;">
                {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}
                ({{ count($records) }} Present)
            </td>
        </tr>

        <!-- Column headers -->
        <tr style="text-align: left;">
            <th>User Type</th>
            <th>User No</th>
            <th>Name</th>
            <th>In Time</th>
            <th>Out Time</th>
            <th>Hours</th>
        </tr>

        <!-- Records -->
        @foreach($records as $record)
            <tr style="text-align: left;">
                <td style="text-align: left;">{{ ucfirst($record['user_type']) }}</td>
                <td style="text-align: left;">{{ $record['user_no'] }}</td>
                <td style="text-align: left;">{{ $record['name'] }}</td>
                <td style="text-align: left;">{{ timeFormat($record['in_time'], 'h:i a') }}</td>
                <td style="text-align: left;">{{ timeFormat($record['out_time'], 'h:i a') ?? '-' }}</td>
                <td style="text-align: left;">{{ hourCount($record['out_time'], $record['in_time']) }}</td>
            </tr>
        @endforeach

        <tr></tr> <!-- space after each date -->
    @endforeach
    </tbody>
</table>
