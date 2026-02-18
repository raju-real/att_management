@extends('layouts.app')
@section('title', 'Teacher List')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Teacher Management</h3>
        <div>
            <a href="{{ route('teachers.push-to-device') }}" class="btn btn-warning text-white mr-2">
                <i class="fas fa-upload mr-2"></i> Push to Device
            </a>
            <a href="{{ route('teachers.create') }}" class="btn btn-primary-admin text-white">
                <i class="fas fa-plus mr-2"></i> Add Teacher
            </a>
        </div>
    </div>
    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Teacher List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Device ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Designation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $teacher->teacher_no ?? '' }}</td>
                                <td>{{ $teacher->name ?? '' }}</td>
                                <td>{{ $teacher->email ?? '' }} X</td>
                                <td>{{ $teacher->mobile ?? '' }}</td>
                                <td>{{ $teacher->designation ?? '' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('teachers.edit', $teacher->teacher_no) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Teacher') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Teacher') !!} class="action-btn text-danger delete-data"
                                        data-id="{{ 'delete-teacher-' . $teacher->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-teacher-{{ $teacher->id }}"
                                        action="{{ route('teachers.destroy', $teacher->id) }}" method="POST">
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
                {!! $teachers->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
