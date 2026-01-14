@extends('layouts.app')
@section('title', 'User-wise Attendance Summary')

@section('content')
<div class="card admin-card">
    <div class="card-header">
        <h5 class="card-title">
            <i class="fas fa-user-clock mr-2"></i> Attendance Summary
            ({{ $from_date }} to {{ $to_date ?? $from_date }})
        </h5>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-striped table-bordered table-sm">
            <thead class="thead-light">
                <tr>
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
                @forelse($attendance_reports as $row)
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
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No attendance found for selected filters</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
