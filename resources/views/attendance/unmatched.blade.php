@extends('layouts.app')
@section('title', 'Unmatched Attendance')

@push('css')
<style>
    .unmatched-pin { font-family: monospace; font-size: 1.05em; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 2px 10px; border-radius: 6px; }
    .reason-badge { font-size: .78em; color: #92400e; background: #fef3c7; padding: 2px 8px; border-radius: 10px; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Unmatched Attendance</h3>
        <a href="{{ route('present-logs') }}" class="btn btn-secondary" {!! tooltip('Back to Attendance') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back to Attendance
        </a>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        These are fingerprint punches whose device PIN doesn't match any Student or Teacher yet — either a brand
        new person enrolled directly on the device, or (on a mixed student+teacher device) the same PIN happens
        to exist in both lists. They are <strong>never silently guessed</strong> as one or the other; classify
        each PIN below by adding it as a Student or Teacher with that exact PIN — past punches will
        automatically re-attach once you do.
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-question-circle mr-2"></i>Unrecognized PINs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>PIN</th>
                            <th>Device</th>
                            <th class="text-center">Punches</th>
                            <th>First Seen</th>
                            <th>Last Seen</th>
                            <th>Resolve</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><span class="unmatched-pin">{{ $row->unmatched_pin }}</span></td>
                                <td>{{ $row->device_serial }}</td>
                                <td class="text-center">{{ $row->punch_count }}</td>
                                <td>{{ dateFormat($row->first_seen, 'd M, Y h:i A') }}</td>
                                <td>{{ dateFormat($row->last_seen, 'd M, Y h:i A') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('teachers.create', ['pin' => $row->unmatched_pin]) }}"
                                       class="btn btn-sm btn-info text-white" {!! tooltip('Add as Teacher') !!}>
                                        <i class="fas fa-chalkboard-teacher"></i> Teacher
                                    </a>
                                    <a href="{{ route('students.create', ['pin' => $row->unmatched_pin]) }}"
                                       class="btn btn-sm btn-primary" {!! tooltip('Add as Student') !!}>
                                        <i class="fas fa-user-graduate"></i> Student
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-2">
                {!! $rows->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
