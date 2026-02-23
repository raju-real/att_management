@extends('layouts.app')
@section('title', 'Attendance Logs')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Attendance Summary</h3>

        <div>
            <button type="button" class="btn btn-warning text-white" data-toggle="modal" data-target="#syncAttendanceModal">
                <i class="fas fa-sync mr-1"></i> Sync Background
            </button>
        </div>
    </div>
    @php
        $filterOpen = request()->hasAny(['user_type', 'user_no', 'student_id', 'from_date', 'to_date']);
    @endphp

    <div class="accordion mb-3" id="attendanceFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                    <span>
                        <i class="fas fa-filter mr-1"></i> Filter Attendance
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div id="filterCollapse" class="collapse {{ $filterOpen ? 'show' : '' }}" aria-labelledby="filterHeading"
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
                                        <option value="student" {{ request('user_type') == 'student' ? 'selected' : '' }}>
                                            Student
                                        </option>
                                        <option value="teacher" {{ request('user_type') == 'teacher' ? 'selected' : '' }}>
                                            Teacher
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Student ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Student ID/Device ID</label>
                                    <input type="search" name="student_id" class="form-control"
                                        value="{{ request('student_id') }}" placeholder="Student ID">
                                </div>
                            </div>
                            <!-- Teacher ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Teacher ID</label>
                                    <input type="search" name="teacher_no" class="form-control"
                                        value="{{ request('teacher_no') }}" placeholder="Student ID">
                                </div>
                            </div>

                            <!-- From Date -->
                            <div class="col-md-4">
                                <div class="form-group input-clearable">
                                    <label class="form-label">From Date</label>
                                    <input type="text" name="from_date" id="from_date"
                                        class="form-control flat_datepicker" placeholder="{{ dateFormat(today()) }}"
                                        value="{{ request('from_date') }}">
                                    <span class="clear-btn" onclick="document.getElementById('from_date').value='';"><i
                                            class="fa fa-calendar"></i></span>
                                </div>
                            </div>

                            <!-- To Date -->
                            <div class="col-md-4">
                                <div class="form-group input-clearable">
                                    <label class="form-label">To Date</label>
                                    <input type="text" name="to_date" id="to_date" class="form-control flat_datepicker"
                                        value="{{ request('to_date') ?? '' }}" placeholder="{{ dateFormat(today()) }}">
                                    <span class="clear-btn" onclick="document.getElementById('to_date').value='';"><i
                                            class="fa fa-calendar"></i></span>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>
                                            Present
                                        </option>
                                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>
                                            Absent
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Attendance Type</label>
                                    <select name="attendance_type" class="form-control">
                                        <option value="">All</option>
                                        <option value="late-in" {{ request('status') == 'late-in' ? 'selected' : '' }}>
                                            Late In
                                        </option>
                                        <option value="early-out" {{ request('status') == 'early-out' ? 'selected' : '' }}>
                                            Early Out
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
                                    <a href="{{ route('attendance-summery') }}" class="btn btn-secondary w-100">
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
                            <th class="text-center">#</th>
                            <th>Date</th>
                            <th>User Type</th>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Work Hour</th>
                        </tr>
                    </thead>
                    <tbody class="table-bordered">
                        @forelse($attendance_logs as $attendance)
                            <tr>
                                <td class="text-center">{{ $loop->index + 1 }}</td>
                                <td>{{ $attendance['attendance_date'] }}</td>
                                <td>{{ ucFirst($attendance['user_type']) }}</td>
                                <td>
                                    @if ($attendance['user_type'] == 'student')
                                        {{ $attendance['student_no'] ?? '-' }}
                                    @elseif($attendance['user_type'] == 'teacher')
                                        {{ $attendance['teacher_no'] ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $attendance['student_id'] ?? '-' }}</td>
                                <td>{{ $attendance['name'] ?? '' }}</td>
                                <td>
                                    <span
                                        class="badge badge-{{ $attendance['status'] == 'Present' ? 'primary' : 'danger' }}">{{ $attendance['status'] ?? '' }}</span>
                                </td>
                                <td class="text-center {{ isLateIn($attendance['in_time']) ? 'text-danger' : '' }}">
                                    {{ timeFormat($attendance['in_time'], 'h:i a') }}</td>
                                <td class="text-center {{ isEarlyOut($attendance['out_time']) ? 'text-danger' : '' }}">
                                    {{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                                <td class="text-center">{{ hourCount($attendance['out_time'], $attendance['in_time']) }}
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {!! $attendance_logs->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="syncAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form action="{{ route('attendance.sync.background') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="syncModalLabel"><i class="fas fa-sync"></i> Sync Attendance</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="text" name="sync_from_date" class="form-control flat_datepicker"
                                placeholder="{{ dateFormat(today()) }}">
                        </div>
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="text" name="sync_to_date" class="form-control flat_datepicker"
                                placeholder="{{ dateFormat(today()) }}">
                        </div>
                        <small class="text-muted">Leave empty to sync today's attendance only.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Start Sync</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
@endpush
