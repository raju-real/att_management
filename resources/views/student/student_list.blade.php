@extends('layouts.app')
@section('title', 'Student List')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Student Management</h3>
        <div>
            <a href="{{ route('students.sync') }}" class="btn btn-info text-white mr-2" {!! tooltip('Sync Student') !!}>
                <i class="fas fa-sync mr-2"></i> Sync Student
            </a>
            <a href="{{ route('students.push-to-device') }}" class="btn btn-warning text-white mr-2" {!! tooltip('Push to Device') !!}>
                <i class="fas fa-upload mr-2"></i> Push to Device
            </a>
            <a href="{{ route('students.import') }}" class="btn btn-success text-white mr-2" {!! tooltip('Import Student') !!}>
                <i class="fas fa-file-excel mr-2"></i> Import Student
            </a>
            <a href="{{ route('students.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Student') !!}>
                <i class="fas fa-plus mr-2"></i> Add Student
            </a>
        </div>
    </div>

    @php
        $filterOpen = request()->hasAny(['name', 'student_id', 'student_no', 'class']);
    @endphp

    <div class="accordion mb-3" id="studentFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                    <span>
                        <i class="fas fa-filter mr-1"></i> Filter Students
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div id="filterCollapse" class="collapse {{ $filterOpen ? 'show' : '' }}" aria-labelledby="filterHeading"
                data-parent="#studentFilterAccordion">

                <div class="card-body">
                    <form method="GET" action="{{ route('students.index') }}">
                        <div class="row g-2">
                            <!-- Name -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Name</label>
                                    <input type="search" name="name" class="form-control" value="{{ request('name') }}"
                                        placeholder="Student Name">
                                </div>
                            </div>

                            <!-- Student ID -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Student ID</label>
                                    <input type="search" name="student_id" class="form-control"
                                        value="{{ request('student_id') }}" placeholder="Student ID">
                                </div>
                            </div>

                            <!-- Student No / Device ID -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Device ID / Student No</label>
                                    <input type="search" name="student_no" class="form-control"
                                        value="{{ request('student_no') }}" placeholder="Student No">
                                </div>
                            </div>

                            <!-- Class -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">Class</label>
                                    <select name="class" class="form-control">
                                        <option value="">All</option>
                                        @foreach (getClassList() as $className)
                                            @if ($className)
                                                <option value="{{ $className }}"
                                                    {{ request('class') == $className ? 'selected' : '' }}>
                                                    {{ $className }}</option>
                                            @endif
                                        @endforeach
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
                                    <a href="{{ route('students.index') }}" class="btn btn-secondary w-100">
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
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Student List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Device ID</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Nick Name</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Shift</th>
                            <th>Group</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $student->student_no ?? '' }}</td>
                                <td>{{ $student->student_id ?? '' }} X</td>
                                <td>{{ showStudentFullName($student->firstname, $student->middlname, $student->lastname) ?? '' }}
                                </td>
                                <td>{{ $student->nickname ?? '' }}</td>
                                <td>{{ $student->class ?? '' }}</td>
                                <td>{{ $student->section ?? '' }}</td>
                                <td>{{ $student->shift ?? '' }}</td>
                                <td>{{ $student->medium ?? '' }}</td>
                                <td>
                                    <a href="{{ route('students.show', $student->student_no) }}"
                                        class="action-btn text-info" {!! tooltip('Show Details of Student') !!}><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('students.edit', $student->id) }}" class="action-btn text-primary"
                                        {!! tooltip('Edit Student') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete From List and Device') !!} class="action-btn text-danger delete-data"
                                        data-id="{{ 'delete-student-' . $student->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-student-{{ $student->id }}"
                                        action="{{ route('students.destroy', $student->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $students->appends(request()->all())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
