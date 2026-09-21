@extends('layouts.app')
@section('title', 'Shift Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($shift) ? 'Edit' : 'Add' }} Shift</h3>
        <a href="{{ route('shifts.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Shift Information</h5>
        </div>
        <div class="card-body">
            @if($departments->isEmpty())
                <div class="alert alert-warning">
                    You need at least one active <a href="{{ route('departments.create') }}">Department</a> before creating a shift.
                </div>
            @endif
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($shift)
                    @method('PUT')
                @endisset

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Department {!! starSign() !!}</label>
                            <select name="department_id" class="form-control {{ hasError('department_id') }}">
                                <option value="">-- Select Department --</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ (old('department_id') ?? ($shift->department_id ?? '')) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Title {!! starSign() !!}</label>
                            <input type="text" name="title" value="{{ old('title') ?? ($shift->title ?? '') }}"
                                class="form-control {{ hasError('title') }}" placeholder="e.g. Morning Shift">
                            @error('title')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">In Time {!! starSign() !!}</label>
                            <input type="text" name="in_time"
                                value="{{ old('in_time') ?? ($shift->in_time ?? '') }}"
                                class="form-control {{ hasError('in_time') }} flat_timepicker" placeholder="In Time">
                            @error('in_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Out Time {!! starSign() !!}</label>
                            <input type="text" name="out_time"
                                value="{{ old('out_time') ?? ($shift->out_time ?? '') }}"
                                class="form-control {{ hasError('out_time') }} flat_timepicker" placeholder="Out Time">
                            @error('out_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('status') === $status->value || (isset($shift) && $shift->status === $status->value && empty(old('status'))) ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection
