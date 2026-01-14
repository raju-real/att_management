@foreach($attendance_reports as $attendance_date => $records)
    <div class="card">
        <div class="card-header">{{ $attendance_date }}</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>User Type</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Working Hour</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $record['user_type'] ?? '' }}</td>
                            <td>{{ $record['user_no'] ?? '' }}</td>
                            <td>{{ $record['name'] ?? '' }}</td>
                            <td>{{ timeFormat($record['in_time'], 'h:i a')  }}</td>
                            <td>{{ timeFormat($record['in_time'], 'h:i a')  }}</td>
                            <td>{{ hourCount($record['out_time'], $record['in_time']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
