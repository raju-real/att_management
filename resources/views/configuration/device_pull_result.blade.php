@extends('layouts.app')
@section('title', 'Pull Users Result')

@push('css')
<style>
    .pull-stat { border-radius: 12px; padding: 16px 18px; color: #fff; text-align: center; }
    .pull-stat .val { font-size: 28px; font-weight: 800; }
    .pull-stat .lbl { font-size: 12px; opacity: .85; text-transform: uppercase; letter-spacing: .5px; }
    .stat-created  { background: linear-gradient(135deg,#059669,#10b981); }
    .stat-updated  { background: linear-gradient(135deg,#0891b2,#06b6d4); }
    .stat-skipped  { background: linear-gradient(135deg,#6b7280,#9ca3af); }
    .stat-unclass  { background: linear-gradient(135deg,#dc2626,#ef4444); }
    .unmatched-pin { font-family: monospace; font-weight: 700; color: #b91c1c; background: #fee2e2; padding: 2px 10px; border-radius: 6px; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Pull Users — {{ $device->name }}</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary" {!! tooltip('Back to Devices') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back to Devices
        </a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        Read {{ $totalOnDevice }} user(s) already enrolled on <strong>{{ $device->name }}</strong>
        ({{ $device->serial_no }}) and matched them against your Student/Teacher lists by PIN.
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="pull-stat stat-created">
                <div class="val">{{ $createdStudents + $createdTeachers }}</div>
                <div class="lbl">Created ({{ $createdStudents }} student, {{ $createdTeachers }} teacher)</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="pull-stat stat-updated">
                <div class="val">{{ $updatedTeachers }}</div>
                <div class="lbl">Names Updated</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="pull-stat stat-skipped">
                <div class="val">{{ $skipped }}</div>
                <div class="lbl">Already Up To Date</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="pull-stat stat-unclass">
                <div class="val">{{ count($unclassified) }}</div>
                <div class="lbl">Needs Your Review</div>
            </div>
        </div>
    </div>

    @if(count($unclassified))
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-question-circle mr-2"></i>Unclassified — this device serves both students &amp; teachers, so a brand-new PIN's type can't be guessed</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>PIN</th>
                                <th>Name on Device</th>
                                <th>Why</th>
                                <th>Resolve</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unclassified as $row)
                                <tr>
                                    <td><span class="unmatched-pin">{{ $row['pin'] }}</span></td>
                                    <td>{{ $row['name'] ?: '—' }}</td>
                                    <td><small class="text-muted">{{ $row['reason'] }}</small></td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('teachers.create', ['pin' => $row['pin'], 'name' => $row['name']]) }}"
                                           class="btn btn-sm btn-info text-white" {!! tooltip('Add as Teacher') !!}>
                                            <i class="fas fa-chalkboard-teacher"></i> Teacher
                                        </a>
                                        <a href="{{ route('students.create', ['pin' => $row['pin'], 'name' => $row['name']]) }}"
                                           class="btn btn-sm btn-primary" {!! tooltip('Add as Student') !!}>
                                            <i class="fas fa-user-graduate"></i> Student
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
