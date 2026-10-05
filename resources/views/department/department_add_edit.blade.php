@extends('layouts.app')
@section('title', 'Department Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($department) ? 'Edit' : 'Add' }} Department</h3>
        <a href="{{ route('departments.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Department Information</h5>
        </div>
        <div class="card-body">
            @if($shifts->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    A department needs a shift. <a href="{{ route('shifts.create') }}" class="font-weight-bold">Create a shift</a> first.
                </div>
            @endif
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($department)
                    @method('PUT')
                @endisset

                @php $selectedShift = old('shift_id') ?? ($department->shift_id ?? ''); @endphp
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? ($department->name ?? '') }}"
                                class="form-control {{ hasError('name') }}" placeholder="e.g. Science Department">
                            @error('name')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Shift {!! starSign() !!}</label>
                            <select name="shift_id" id="shiftSelect" class="form-control {{ hasError('shift_id') }}">
                                <option value="">-- Select Shift --</option>
                                @foreach ($shifts as $shift)
                                    <option value="{{ $shift->id }}"
                                        data-timing="{{ timeFormat($shift->in_time, 'h:i A') }} - {{ timeFormat($shift->out_time, 'h:i A') }}"
                                        {{ (string) $selectedShift === (string) $shift->id ? 'selected' : '' }}>
                                        {{ $shift->title }} ({{ timeFormat($shift->in_time, 'h:i A') }} - {{ timeFormat($shift->out_time, 'h:i A') }}){{ $shift->status !== 'active' ? ' [inactive]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Teachers in this department use this shift's in/out time for late &amp; early-out.</small>
                            @error('shift_id')
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
                                        {{ (old('status') ?? ($department->status ?? 'active')) === $status->value ? 'selected' : '' }}>
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
