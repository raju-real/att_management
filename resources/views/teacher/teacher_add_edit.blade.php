@extends('layouts.app')
@section('title', 'Teacher Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($teacher) ? 'Edit' : 'Add' }} Teacher</h3>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Teacher Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($teacher)
                    @method('PUT')
                @endisset
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? ($teacher->name ?? '') }}"
                                class="form-control {{ hasError('name') }}" placeholder="Name">
                            @error('name')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Email {!! starSign() !!}</label>
                            <input type="text" name="email" value="{{ old('email') ?? ($teacher->email ?? '') }}"
                                class="form-control {{ hasError('email') }}" placeholder="Email">
                            @error('email')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Mobile {!! starSign() !!}</label>
                            <input type="text" name="mobile" value="{{ old('mobile') ?? ($teacher->mobile ?? '') }}"
                                class="form-control {{ hasError('mobile') }}" placeholder="Mobile">
                            @error('mobile')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Designation {!! starSign() !!}</label>
                            <input type="text" name="designation"
                                value="{{ old('designation') ?? ($teacher->designation ?? '') }}"
                                class="form-control {{ hasError('designation') }}" placeholder="Designation">
                            @error('designation')
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
