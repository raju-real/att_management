@extends('layouts.app')
@section('title', 'Attendance Logs')
@push('css')
@endpush

@section('content')

    @php
        $filterOpen = request()->hasAny([
            'user_type','from_date','to_date'
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
                    <form method="GET" action="{{ route('month-wise-present-report') }}">
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
                                           value="{{ $from_date ?? '' }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('from_date').value='';"><i
                                            class="fa fa-calendar"></i></span>
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
                                          onclick="document.getElementById('to_date').value='';"><i
                                            class="fa fa-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Data Type</label>
                                    <select name="data_type" class="form-control">
                                        <option value="all_days" {{ request('data_type')=='all_days'?'selected':'' }}>
                                            All Days
                                        </option>
                                        <option
                                            value="working_days" {{ request('data_type')=='working_days'?'selected':'' }}>
                                            Working Days
                                        </option>
                                        <option value="of_days" {{ request('data_type')=='of_days'?'selected':'' }}>Off
                                            Days
                                        </option>
                                    </select>
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
                                    <a href="{{ route('month-wise-present-report') }}"
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
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Attendance Report of --
                {{ dateFormat($from_date, 'd M, y') }}
                @isset($to_date)
                    to {{ dateFormat($to_date, 'd M, y') }}
                @endisset
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($attendance_reports as $attendance_date => $records)
                    <div class="col-xl-6 col-lg-6 col-md-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-{{ count($records) ? 'light' : 'danger' }} py-2">
                                <strong>
                                    <i class="fas fa-calendar-day mr-1"></i>
                                    {{ dateFormat($attendance_date, 'd M, Y') }} ({{ dayNameFromDate($attendance_date) }})
                                </strong>
                                <span class="badge badge-primary ml-2">
                                    {{ count($records) }} Present
                                </span>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0 text-left">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>In</th>
                                            <th>Out</th>
                                            <th>Hr</th>
                                        </tr>
                                        </thead>
                                        <tbody class="table-bordered">
                                        @foreach($records as $record)
                                            <tr>
                                                <td>{{ $record['user_no'] }}</td>
                                                <td>{{ $record['name'] }}</td>
                                                <td class="text-nowrap {{ isLateIn($record['in_time']) ? 'text-danger' : '' }}">{{ timeFormat($record['in_time'], 'h:i a') }}</td>
                                                <td class="text-nowrap {{ isEarlyOut($record['out_time']) ? 'text-danger' : '' }}">{{ timeFormat($record['out_time'], 'h:i a') ?? '-' }}</td>
                                                <td class="text-nowrap">{{ hourCount($record['out_time'], $record['in_time']) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <x-no-data-found/>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
@endsection

@push('js')
@endpush
