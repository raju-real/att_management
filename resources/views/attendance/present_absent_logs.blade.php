@extends('layouts.app')
@section('title', 'Attendance Logs')
@push('css')
@endpush

@section('content')

    @php
        $filterOpen = request()->hasAny([
            'user_type','user_no','student_id','from_date','to_date'
        ]);
    @endphp

    <div class="accordion mb-3" id="attendanceFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button
                    class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button"
                    data-toggle="collapse"
                    data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}"
                    aria-controls="filterCollapse">
                            <span>
                                <i class="fas fa-filter mr-1"></i> Filter Attendance
                            </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div id="filterCollapse"
                 class="collapse {{ $filterOpen ? 'show' : '' }}"
                 aria-labelledby="filterHeading"
                 data-parent="#attendanceFilterAccordion">

                <div class="card-body">
                    <form method="GET" action="{{ route('attendance-summery') }}">
                        <div class="row g-2">

                            <!-- User Type -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">User Type</label>
                                    <select name="user_type" class="form-control">
                                        <option value="">All</option>
                                        <option value="student" {{ request('user_type')=='student'?'selected':'' }}>
                                            Student
                                        </option>
                                        <option value="teacher" {{ request('user_type')=='teacher'?'selected':'' }}>
                                            Teacher
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- From Date -->
                            <div class="col-md-4">
                                <div class="form-group input-clearable">
                                    <label class="form-label">From Date</label>
                                    <input type="text"
                                           name="from_date"
                                           id="from_date"
                                           class="form-control flat_datepicker"
                                           value="{{ request('from_date') }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('from_date').value='';">X</span>
                                </div>
                            </div>

                            <!-- To Date -->
                            <div class="col-md-4">
                                <div class="form-group input-clearable">
                                    <label class="form-label">To Date</label>
                                    <input type="text"
                                           name="to_date"
                                           id="to_date"
                                           class="form-control flat_datepicker"
                                           value="{{ request('to_date') ?? '' }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('to_date').value='';">X</span>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="present" {{ request('status')=='present'?'selected':'' }}>
                                            Present
                                        </option>
                                        <option value="absent" {{ request('status')=='absent'?'selected':'' }}>
                                            Absent
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-1 mt-2">
                                <div class="form-group">
                                    <label class="form-label"></label>
                                    <button class="btn btn-primary mr-2 w-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-1 mt-2">
                                <label class="form-label"></label>
                                <div class="form-group">
                                    <a href="{{ route('attendance-summery') }}"
                                       class="btn btn-secondary w-100">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Attendance Summery of --
                {{ dateFormat($from_date, 'd M, y') }}
                @isset($to_date)
                    to {{ dateFormat($to_date, 'd M, y') }}
                @endisset
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-hover table-striped text-left">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>User Type</th>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Work Hour</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($attendance_logs as $attendance)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $attendance['attendance_date'] }}</td>
                            <td>{{ ucFirst($attendance['user_type']) }}</td>
                            <td>
                                @if($attendance['user_type'] == 'student')
                                    {{ $attendance['student_no'] ?? '' }}
                                @elseif($attendance['user_type'] == 'teacher')
                                    {{ $attendance['teacher_no'] ?? '' }}
                                @endif
                            </td>
                            <td>{{ $attendance['student_id'] ?? '' }}</td>
                            <td>{{ $attendance['name'] ?? '' }}</td>
                            <td>
                                <span class="badge badge-{{ $attendance['status'] == 'Present' ? 'primary' : 'danger' }}">{{ $attendance['status'] ?? '' }}</span>
                            </td>
                            <td>{{ timeFormat($attendance['in_time'], 'h:i a')  }}</td>
                            <td>{{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                            <td>{{ hourCount($attendance['out_time'], $attendance['in_time']) }}</td>
                        </tr>
                    @empty
                        <x-no-data-found/>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $attendance_logs->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
