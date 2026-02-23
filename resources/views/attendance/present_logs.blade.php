@extends('layouts.app')
@section('title', 'Attendance Logs')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Attendance Management</h3>

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
                    <form method="GET" action="{{ route('present-logs') }}">
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

                            <!-- User No -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">User No</label>
                                    <input type="search" name="user_no" class="form-control"
                                        value="{{ request('user_no') }}" placeholder="100001 / 101">
                                </div>
                            </div>

                            <!-- Student ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Student ID</label>
                                    <input type="search" name="student_id" class="form-control"
                                        value="{{ request('student_id') }}" placeholder="Student ID">
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
                                    <a href="{{ route('present-logs') }}" class="btn btn-secondary w-100">
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
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Preset Attendance Logs of --
                {{ dateFormat($from_date, 'd M, y') }}
                @isset($to_date)
                    to {{ dateFormat($to_date, 'd M, y') }}
                @endisset
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">User Type</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Work Hour</th>
                            <th class="text-center">Punch Count</th>
                        </tr>
                    </thead>
                    <tbody class="table-bordered">
                        @forelse($attendance_logs as $attendance)
                            <tr>
                                <td class="text-center">{{ $loop->index + 1 }}</td>
                                <td class="text-center">{{ ucFirst($attendance['user_type']) }}</td>
                                <td>{{ $attendance['user_no'] ?? '' }}</td>
                                <td>{{ $attendance['name'] ?? '' }}</td>
                                <td class="text-center {{ isLateIn($attendance['in_time']) ? 'text-danger' : '' }}">
                                    {{ timeFormat($attendance['in_time'], 'h:i a') }}</td>
                                <td class="text-center {{ isEarlyOut($attendance['out_time']) ? 'text-danger' : '' }}">
                                    {{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                                <td class="text-center">{{ hourCount($attendance['out_time'], $attendance['in_time']) }}
                                </td>
                                <td class="text-center">{{ $attendance['total_punches'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $attendance_logs->appends(request()->all())->links('pagination::bootstrap-4') !!}
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
