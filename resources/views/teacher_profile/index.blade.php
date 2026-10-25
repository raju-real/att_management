@extends('layouts.app')
@section('title', 'Teacher/Staff Profile')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Teacher/Staff Profile</h3>
        <a href="{{ route('teachers.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add Teacher/Staff') !!}>
            <i class="fas fa-plus mr-1"></i> Add Teacher/Staff
        </a>
    </div>

    <div class="card admin-card mb-3">
        <div class="card-body pb-1">
            <form method="GET" action="{{ route('teacher-profiles.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Institute</label>
                            @include('partials.institute_select', ['autoSubmit' => true])
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">All Departments</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ (string) request('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Search</label>
                            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, ID, mobile, designation">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-group d-flex w-100" style="gap:6px">
                            <button class="btn btn-primary flex-fill"><i class="fas fa-search"></i></button>
                            <a href="{{ route('teacher-profiles.index') }}" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-id-card mr-2"></i>Teachers/Staff <small class="text-muted">({{ $teachers->total() }})</small></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Mobile</th>
                            @if (isSuperAdmin())<th>Institute</th>@endif
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $t)
                            <tr>
                                <td>{{ $teachers->firstItem() + $loop->index }}</td>
                                <td>
                                    @if($t->image && file_exists($t->image))
                                        <img src="{{ asset($t->image) }}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle text-muted"
                                              style="width:36px;height:36px;font-weight:600">{{ strtoupper(mb_substr($t->name ?? 'T', 0, 1)) }}</span>
                                    @endif
                                </td>
                                <td><code>{{ $t->teacher_no }}</code></td>
                                <td class="fw-semibold">{{ $t->name }}</td>
                                <td>{{ $t->designation ?? '—' }}</td>
                                <td>{{ $t->department->name ?? '—' }}</td>
                                <td>{{ $t->mobile ?? '—' }}</td>
                                @if (isSuperAdmin())<td><small>{{ $t->institute->name ?? '—' }}</small></td>@endif
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('teacher-profiles.show', $t->id) }}" class="btn btn-sm btn-primary-admin text-white">
                                        <i class="fas fa-id-card mr-1"></i> View Profile
                                    </a>
                                    <a href="{{ route('teacher-profiles.show', [$t->id, 'export' => 'pdf']) }}" target="_blank"
                                       class="btn btn-sm btn-outline-danger" {!! tooltip('Profile PDF') !!}><i class="fas fa-file-pdf"></i></a>
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-2">
                {!! $teachers->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
