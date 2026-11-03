@extends('layouts.app')
@section('title', 'Institute Users')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Institute Users</h3>
        <a href="{{ route('institute-users.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add User') !!}>
            <i class="fas fa-plus mr-2"></i> Add User
        </a>
    </div>

    <div class="alert alert-info small py-2">
        <i class="fas fa-info-circle mr-1"></i>
        Users work inside their institute only and see only the menus ticked for them. The institute account itself always has every menu.
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="fas fa-users-cog mr-2"></i>User List</h5>
            <form method="GET" action="{{ route('institute-users.index') }}" class="form-inline">
                @if (isSuperAdmin())
                    <div class="mr-1" style="min-width:220px">
                        @include('partials.institute_select', ['class' => 'form-control form-control-sm'])
                    </div>
                @endif
                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm mr-1" placeholder="Name, email, mobile" {!! tooltip('Name, email, mobile') !!}>
                <button class="btn btn-sm btn-primary-admin text-white mr-1"><i class="fas fa-search"></i></button>
                @if (request()->hasAny(['search', 'institute_id']))
                    <a href="{{ route('institute-users.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Login Email</th>
                            <th>Mobile</th>
                            @if (isSuperAdmin())<th>Institute</th>@endif
                            <th>Menu Permissions</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $users->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->mobile ?? '-' }}</td>
                                @if (isSuperAdmin())<td><small>{{ $user->parentInstitute->name ?? '-' }}</small></td>@endif
                                <td style="max-width:360px">
                                    @foreach ((array) $user->permissions as $p)
                                        <span class="badge badge-light border mb-1">{{ \App\Support\Permissions::label($p) }}</span>
                                    @endforeach
                                </td>
                                <td>{!! showStatus($user->status) !!}</td>
                                <td><small>{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d M Y h:i A') : 'Never' }}</small></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('institute-users.edit', $user->id) }}" class="action-btn text-info" {!! tooltip('Edit User') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete User') !!} class="action-btn text-danger delete-data" data-id="delete-user-{{ $user->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-user-{{ $user->id }}" action="{{ route('institute-users.destroy', $user->id) }}" method="POST">
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
                {!! $users->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
