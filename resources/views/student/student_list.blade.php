@extends('layouts.app')
@section('title', 'Student List')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Student Management</h3>
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
                        <th>Std Sl no</th>
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
                            <td>{{ $student->std_no ?? '' }} X</td>
                            <td>{{ $student->student_id ?? '' }} X</td>
                            <td>{{ showStudentFullName($student->firstname, $student->middlname, $student->lastname) ?? '' }}</td>
                            <td>{{ $student->nickname ?? '' }}</td>
                            <td>{{ $student->class ?? '' }}</td>
                            <td>{{ $student->section ?? '' }}</td>
                            <td>{{ $student->shift ?? '' }}</td>
                            <td>{{ $student->medium ?? '' }}</td>
                            <td>
                                <a href="{{ route('students.show', $student->student_id) }}"
                                   class="action-btn text-info" {!! tooltip('Show Details of Student') !!}><i class="fas fa-eye"></i></a>
                                <a {!! tooltip('Delete Device') !!}
                                   class="action-btn text-danger delete-data"
                                   data-id="{{ 'delete-device-' . $student->id }}" href="javascript:void(0);">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <form id="delete-device-{{ $student->id }}"
                                      action="{{ route('devices.destroy', $student->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-no-data-found/>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $students->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
