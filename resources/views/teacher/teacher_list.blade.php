@extends('layouts.app')
@section('title', 'Teacher List')

@push('css')
<style>
    /* ── Teacher Avatar in Table ── */
    .teacher-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(99,102,241,0.35);
        box-shadow: 0 2px 8px rgba(99,102,241,0.15);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .teacher-avatar:hover {
        transform: scale(1.12);
        box-shadow: 0 4px 16px rgba(99,102,241,0.3);
    }
    .teacher-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
        border: 2px solid rgba(99,102,241,0.3);
        box-shadow: 0 2px 8px rgba(99,102,241,0.15);
        font-weight: 600;
        letter-spacing: 0;
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Teacher Management</h3>
        <div>
            <a href="{{ route('teachers.push-to-device') }}" class="btn btn-warning text-white mr-2" {!! tooltip('Push to Device') !!}>
                <i class="fas fa-upload mr-2"></i> Push to Device
            </a>
            <a href="{{ route('teachers.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Teacher') !!}>
                <i class="fas fa-plus mr-2"></i> Add Teacher
            </a>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-chalkboard-teacher mr-2"></i>Teacher List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th style="width:60px">Photo</th>
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
                                <td>
                                    @if($teacher->image && file_exists($teacher->image))
                                        <img src="{{ asset($teacher->image) }}"
                                             alt="{{ $teacher->name }}"
                                             class="teacher-avatar"
                                             title="{{ $teacher->name }}">
                                    @else
                                        <div class="teacher-avatar-placeholder"
                                             title="{{ $teacher->name }}">
                                            {{ strtoupper(substr($teacher->name ?? 'T', 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $teacher->teacher_no ?? '' }}</td>
                                <td>{{ $teacher->name ?? '' }}</td>
                                <td>{{ $teacher->email ?? '' }}</td>
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
