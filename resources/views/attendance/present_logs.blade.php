@extends('layouts.app')
@section('title', 'Attendance Logs')
@push('css')
@endpush

@section('content')
{{--    <div class="d-flex justify-content-between align-items-center mb-2">--}}
{{--        <h3>Attendance Management</h3>--}}

{{--        <div class="btn-group">--}}
{{--            <a href="{{ route('present-logs') }}"--}}
{{--               class="btn btn-info text-white">--}}
{{--                <i class="fas fa-list mr-1"></i> Detail Log--}}
{{--            </a>--}}

{{--            <a href="{{ route('present-logs') }}"--}}
{{--               class="btn btn-success text-white">--}}
{{--                <i class="fas fa-chart-bar mr-1 ml-1"></i> Reports--}}
{{--            </a>--}}
{{--        </div>--}}
{{--    </div>--}}

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
                    <form method="GET" action="{{ route('present-logs') }}">
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

                            <!-- User No -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">User No</label>
                                    <input type="search"
                                           name="user_no"
                                           class="form-control"
                                           value="{{ request('user_no') }}"
                                           placeholder="100001 / 101">
                                </div>
                            </div>

                            <!-- Student ID -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Student ID</label>
                                    <input type="search"
                                           name="student_id"
                                           class="form-control"
                                           value="{{ request('student_id') }}" placeholder="Student ID">
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
                                           placeholder="{{ dateFormat(today()) }}"
                                           value="{{ request('from_date') }}">
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
                                           value="{{ request('to_date') ?? '' }}"
                                           placeholder="{{ dateFormat(today()) }}">
                                    <span class="clear-btn"
                                          onclick="document.getElementById('to_date').value='';"><i class="fa fa-calendar"></i></span>
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
                                    <a href="{{ route('present-logs') }}"
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
                            <td class="text-center {{ isLateIn($attendance['in_time']) ? 'text-danger' : '' }}">{{ timeFormat($attendance['in_time'], 'h:i a')  }}</td>
                            <td class="text-center {{ isEarlyOut($attendance['out_time']) ? 'text-danger' : '' }}">{{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                            <td class="text-center">{{ hourCount($attendance['out_time'], $attendance['in_time']) }} </td>
                            <td class="text-center">{{ $attendance['total_punches'] ?? '-' }}</td>
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
