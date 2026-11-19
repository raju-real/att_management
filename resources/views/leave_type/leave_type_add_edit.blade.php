@extends('layouts.app')
@section('title', 'Leave Type Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($type) ? 'Edit' : 'Add' }} Leave Type</h3>
        <a href="{{ route('leave-types.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-tags mr-2"></i> Leave Type Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($type)
                    @method('PUT')
                @endisset

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Institute {!! starSign() !!}</label>
                            @include('partials.institute_select', ['required' => true, 'selected' => $type->institute_id ?? null])
                            @error('institute_id') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? ($type->name ?? '') }}"
                                   class="form-control {{ hasError('name') }}" placeholder="e.g. Casual Leave" maxlength="100">
                            @error('name') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Short Code</label>
                            <input type="text" name="code" value="{{ old('code') ?? ($type->code ?? '') }}"
                                   class="form-control {{ hasError('code') }}" placeholder="e.g. CL" maxlength="10" style="text-transform:uppercase">
                            @error('code') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Days per Year</label>
                            <input type="number" name="days_per_year" min="0" max="365" value="{{ old('days_per_year') ?? ($type->days_per_year ?? '') }}"
                                   class="form-control {{ hasError('days_per_year') }}" placeholder="e.g. 10 (optional)">
                            @error('days_per_year') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}" {{ (old('status') ?? ($type->status ?? 'active')) === $status->value ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status') {!! displayError($message) !!} @enderror
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
