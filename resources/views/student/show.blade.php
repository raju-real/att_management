@extends('layouts.app')
@section('title', 'Student Details')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Student Management</h3>
        <a href="{{ route('students.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Back to
            List</a>
    </div>
    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-user mr-2"></i>Student Details:
                {{ showStudentFullName($student->firstname, $student->middlname, $student->lastname) }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">Student ID (Device)</th>
                                    <td>{{ $student->student_no }}</td>
                                </tr>
                                <tr>
                                    <th>Student ID (School)</th>
                                    <td>{{ $student->student_id }}</td>
                                </tr>
                                <tr>
                                    <th>Full Name</th>
                                    <td>{{ showStudentFullName($student->firstname, $student->middlname, $student->lastname) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nick Name</th>
                                    <td>{{ $student->nickname }}</td>
                                </tr>
                                <tr>
                                    <th>Class</th>
                                    <td>{{ $student->class }}</td>
                                </tr>
                                <tr>
                                    <th>Section</th>
                                    <td>{{ $student->section }}</td>
                                </tr>
                                <tr>
                                    <th>Shift</th>
                                    <td>{{ $student->shift }}</td>
                                </tr>
                                <tr>
                                    <th>Group</th>
                                    <td>{{ $student->medium }}</td>
                                </tr>
                                <tr>
                                    <th>Session</th>
                                    <td>{{ $student->session }}</td>
                                </tr>
                                <tr>
                                    <th>Mobile No</th>
                                    <td>{{ $student->mobile_no }}</td>
                                </tr>
                                <tr>
                                    <th>Alternative Mobile No</th>
                                    <td>{{ $student->alt_mobile_no }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">Birth Date</th>
                                    <td>{{ $student->birthdate }}</td>
                                </tr>
                                <tr>
                                    <th>Blood Group</th>
                                    <td>{{ $student->bloodgroup }}</td>
                                </tr>
                                <tr>
                                    <th>Religion</th>
                                    <td>{{ $student->religion }}</td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td>{{ $student->gender }}</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td>{{ $student->address }}</td>
                                </tr>
                                <tr>
                                    <th>Father's Name</th>
                                    <td>{{ $student->fathers_name }}</td>
                                </tr>
                                <tr>
                                    <th>Mother's Name</th>
                                    <td>{{ $student->mothers_name }}</td>
                                </tr>
                                <tr>
                                    <th>Guardian's Name</th>
                                    <td>{{ $student->guadian_name }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $student->created_at->format('d M, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $student->updated_at->format('d M, Y h:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
