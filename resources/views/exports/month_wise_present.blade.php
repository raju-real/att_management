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
        <tr>
            <th>User Type</th>
            <th>User No</th>
            <th>Name</th>
            <th>In Time</th>
            <th>Out Time</th>
            <th>Hours</th>
        </tr>

        <!-- Records -->
        @foreach($records as $record)
            <tr>
                <td>{{ ucfirst($record['user_type']) }}</td>
                <td>{{ $record['user_no'] }}</td>
                <td>{{ $record['name'] }}</td>
                <td>{{ timeFormat($record['in_time'], 'h:i a') }}</td>
                <td>{{ timeFormat($record['out_time'], 'h:i a') ?? '-' }}</td>
                <td>{{ hourCount($record['out_time'], $record['in_time']) }}</td>
            </tr>
        @endforeach

        <tr></tr> <!-- space after each date -->
    @endforeach
    </tbody>
</table>
