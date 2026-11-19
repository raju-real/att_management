@extends('layouts.app')
@section('title', 'Leave Types')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Leave Types</h3>
        <div>
            @if (userCan('leaves'))
                <a href="{{ route('leaves.index') }}" class="btn btn-secondary mr-1">
                    <i class="fas fa-plane-departure mr-1"></i> Leave Entry
                </a>
            @endif
            <a href="{{ route('leave-types.create') }}" class="btn btn-primary-admin text-white">
                <i class="fas fa-plus mr-2"></i> Add New
            </a>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-tags mr-2"></i>Leave Type List</h5>
            @if (isSuperAdmin())
                <form method="GET" action="{{ route('leave-types.index') }}" class="form-inline">
                    <div class="mr-1" style="min-width:240px">
                        @include('partials.institute_select', ['autoSubmit' => true, 'class' => 'form-control form-control-sm'])
                    </div>
                    @if (request()->filled('institute_id'))
                        <a href="{{ route('leave-types.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></a>
                    @endif
                </form>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Code</th>
                            @if (isSuperAdmin())<th>Institute</th>@endif
                            <th class="text-center">Days / Year</th>
                            <th class="text-center">Leaves</th>
                            <th class="text-center">Days Taken</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                            <tr>
                                <td>{{ $types->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $type->name }}</td>
                                <td>@if($type->code)<span class="badge badge-warning">{{ $type->code }}</span>@else - @endif</td>
                                @if (isSuperAdmin())<td><small>{{ $type->institute->name ?? '—' }}</small></td>@endif
                                <td class="text-center">{{ $type->days_per_year ?? '-' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('leaves.index', ['leave_type_id' => $type->id] + (isSuperAdmin() ? ['institute_id' => $type->institute_id] : [])) }}"
                                       class="badge badge-secondary">{{ $type->leaves_count }}</a>
                                </td>
                                <td class="text-center">{{ (int) $type->days_taken }}</td>
                                <td>{!! showStatus($type->status) !!}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('leave-types.edit', $type->id) }}" class="action-btn text-info" {!! tooltip('Edit Leave Type') !!}>
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a {!! tooltip('Delete Leave Type') !!} class="action-btn text-danger delete-data"
                                       data-id="delete-leave-type-{{ $type->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-leave-type-{{ $type->id }}" action="{{ route('leave-types.destroy', $type->id) }}" method="POST">
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
                {!! $types->appends(request()->all())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
