@extends('layouts.app')
@section('title', 'Institute Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($institute) ? 'Edit' : 'Add' }} Institute</h3>
        <a href="{{ route('institutes.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <form action="{{ $route }}" id="prevent-form" method="POST" enctype="multipart/form-data">
        @csrf
        @isset($institute)
            @method('PUT')
        @endisset

        <div class="card admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-university mr-2"></i> Institute Information
                    <small class="text-muted">(shown as the heading of every report of this institute)</small></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Institute Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name', $institute->name ?? '') }}"
                                class="form-control {{ hasError('name') }}" placeholder="e.g. Ideal High School">
                            @error('name') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">EIIN</label>
                            <input type="text" name="eiin" value="{{ old('eiin', $institute->eiin ?? '') }}"
                                class="form-control {{ hasError('eiin') }}">
                            @error('eiin') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('status', $institute->status ?? 'active') === $status->value ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">An inactive institute cannot log in.</small>
                            @error('status') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" value="{{ old('address', $institute->address ?? '') }}"
                                class="form-control {{ hasError('address') }}">
                            @error('address') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $institute->mobile ?? '') }}"
                                class="form-control {{ hasError('mobile') }}" placeholder="017XXXXXXXX">
                            @error('mobile') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" value="{{ old('website', $institute->website ?? '') }}"
                                class="form-control {{ hasError('website') }}" placeholder="www.example.edu.bd">
                            @error('website') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Logo <small class="text-muted">(jpg/png/webp, max 1MB)</small></label>
                            <input type="file" name="logo" accept="image/*" class="form-control {{ hasError('logo') }}">
                            @error('logo') {!! displayError($message) !!} @enderror
                            @if(isset($institute) && $institute->logo && file_exists($institute->logo))
                                <div class="mt-2 d-flex align-items-center">
                                    <img src="{{ asset($institute->logo) }}" alt="" style="height:50px" class="mr-3">
                                    <label class="mb-0"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-card mt-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-key mr-2"></i> Login</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Login Email {!! starSign() !!}</label>
                            <input type="email" name="email" value="{{ old('email', $institute->email ?? '') }}"
                                class="form-control {{ hasError('email') }}" autocomplete="off">
                            @error('email') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Password @unless(isset($institute)) {!! starSign() !!} @endunless</label>
                            <input type="password" name="password" class="form-control {{ hasError('password') }}" autocomplete="new-password">
                            @isset($institute)<small class="text-muted">Leave blank to keep the current password.</small>@endisset
                            @error('password') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mt-3">
            <x-submit-button />
        </div>
    </form>
@endsection
