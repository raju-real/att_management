@extends('layouts.app')
@section('title', 'User-wise Attendance Summary')

@section('content')

    @php
        $filterOpen = request()->hasAny([
            'user_type','from_date','to_date','student_id','teacher_no'
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
                    <form method="GET" action="{{ route('month-wise-user-summery') }}">
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

                            <!-- Student ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Student ID/Device ID</label>
                                    <input type="search"
                                           name="student_id"
                                           class="form-control"
                                           value="{{ request('student_id') }}" placeholder="Student ID">
                                </div>
                            </div>
                            <!-- Teacher ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Teacher ID</label>
                                    <input type="search"
                                           name="teacher_no"
                                           class="form-control"
                                           value="{{ request('teacher_no') }}" placeholder="Student ID">
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
                                           value="{{ $from_date }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('from_date').value='';"><i class="fa fa-calendar"></i></span>
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
                                           value="{{ $to_date ?? '' }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('to_date').value='';"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Display Type</label>
                                    <select name="display_type" class="form-control">
                                        <option
                                            value="show_data" {{ request('display_type')=='show_data'?'selected':'' }}>
                                            Show Data
                                        </option>
                                        <option
                                            value="download_as_xl" {{ request('display_type')=='download_as_xl'?'selected':'' }}>
                                            Download as XL
                                        </option>
                                        <option
                                            value="download_as_pdf" {{ request('display_type')=='download_as_pdf'?'selected':'' }}>
                                            Download as PDF
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
                                    <a href="{{ route('date-wise-present-report') }}"
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
            <h5 class="card-title">
                <i class="fas fa-user-clock mr-2"></i> Monthly Summary
                ({{ dateFormat($from_date,'d M, y') }} to {{ dateFormat($to_date,'d M, y') ?? dateFormat($from_date,'d M, y') }})
            </h5>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered table-sm text-center">
                <thead class="thead-light">
                <tr>
                    <th>User Type</th>
                    <th>ID</th>
                    <th>First In</th>
                    <th>Last Out</th>
                    <th>Early In</th>
                    <th>Late In</th>
                    <th>Early Out</th>
                    <th>Late Out</th>
                    <th>Total Hours</th>
                </tr>
                </thead>
                <tbody class="table-bordered">
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
