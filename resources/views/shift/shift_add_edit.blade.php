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
            <div class="alert alert-info py-2 small">
                <i class="fas fa-info-circle mr-1"></i>
                A shift sets the standard <strong>In Time</strong> and <strong>Out Time</strong>. You assign it to one or more
                departments. A teacher in those departments counts as <span class="text-danger font-weight-bold">Late In</span>
                after the in time and <span class="text-danger font-weight-bold">Early Out</span> before the out time.
            </div>
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($shift)
                    @method('PUT')
                @endisset

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Title {!! starSign() !!}</label>
                            <input type="text" name="title" value="{{ old('title') ?? ($shift->title ?? '') }}"
                                class="form-control {{ hasError('title') }}" placeholder="e.g. Morning Shift">
                            @error('title')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">In Time {!! starSign() !!}</label>
                            <input type="text" name="in_time"
                                value="{{ old('in_time') ?? (isset($shift) ? timeFormat($shift->in_time, 'h:i:s A') : '') }}"
                                class="form-control {{ hasError('in_time') }} flat_timepicker" placeholder="08:00 AM">
                            @error('in_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Out Time {!! starSign() !!}</label>
                            <input type="text" name="out_time"
                                value="{{ old('out_time') ?? (isset($shift) ? timeFormat($shift->out_time, 'h:i:s A') : '') }}"
                                class="form-control {{ hasError('out_time') }} flat_timepicker" placeholder="02:00 PM">
                            @error('out_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ (old('status') ?? ($shift->status ?? 'active')) === $status->value ? 'selected' : '' }}>
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
