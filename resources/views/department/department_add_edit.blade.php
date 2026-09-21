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
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($department)
                    @method('PUT')
                @endisset

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? ($department->name ?? '') }}"
                                class="form-control {{ hasError('name') }}" placeholder="e.g. Science Department">
                            @error('name')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('status') === $status->value || (isset($department) && $department->status === $status->value && empty(old('status'))) ? 'selected' : '' }}>
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
