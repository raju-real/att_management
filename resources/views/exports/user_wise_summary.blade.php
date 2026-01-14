<table>
    <thead>
        <tr style="background-color:#4e73df; color:white;">
            <th colspan="10" style="text-align:center;">
                Attendance Summary
                ({{ $from_date }} to {{ $to_date ?? $from_date }})
            </th>
        </tr>
        <tr style="background-color:#f2f2f2;">
            <th>User Type</th>
            <th>User No</th>
            <th>First In</th>
            <th>Last Out</th>
            <th>Early In</th>
            <th>Late In</th>
            <th>Early Out</th>
            <th>Late Out</th>
            <th>Total Hours</th>
            <th>Total Punches</th>
        </tr>
    </thead>
    <tbody>
        @foreach($report as $row)
            <tr>
                <td>{{ ucfirst($row['user_type']) }}</td>
                <td>{{ $row['user_no'] }}</td>
                <td>{{ $row['first_in'] }}</td>
                <td>{{ $row['last_out'] }}</td>
                <td>{{ $row['early_in'] }}</td>
                <td>{{ $row['late_in'] }}</td>
                <td>{{ $row['early_out'] }}</td>
                <td>{{ $row['late_out'] }}</td>
                <td>{{ $row['total_hours'] }}</td>
                <td>{{ $row['total_punch'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
