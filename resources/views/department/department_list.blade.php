@extends('layouts.app')
@section('title', 'Department List')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Department Management</h3>
        <a href="{{ route('departments.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Department') !!}>
            <i class="fas fa-plus mr-2"></i> Add Department
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-building mr-2"></i>Department List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th class="text-center">Teachers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td class="fw-semibold">{{ $department->name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $department->teachers_count }}</span>
                                </td>
                                <td>{!! showStatus($department->status) !!}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('departments.edit', $department->id) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Department') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Department') !!} class="action-btn text-danger delete-data"
                                        data-id="delete-department-{{ $department->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-department-{{ $department->id }}"
                                        action="{{ route('departments.destroy', $department->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">
                {!! $departments->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
