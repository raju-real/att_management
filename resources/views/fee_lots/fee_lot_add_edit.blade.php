@extends('layouts.app')
@section('title', 'Fee Lot Add/Edit')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($feeLot) ? 'Edit' : 'Add' }} Fee Collect Lot</h3>
        <a href="{{ route('fee-lots.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        @isset($feeLot)
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Note:</strong> Only title can be edited. No changes will be made to the students' fees.
            </div>
        @endisset

        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Fee Collect Lot</h5>
        </div>
        
        <div class="card-body">
            <form action="{{ $route }}" method="POST" id="prevent-form">
                @csrf
                @if (isset($feeLot))
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Lot Title {!! starSign() !!}</label>
                            <input type="text" name="title" class="form-control {{ hasError('title') }}"
                                placeholder="e.g. {{ now()->subMonth()->format('F - Y') }}"
                                value="{{ $feeLot->title ?? (old('title') ?? '') }}">
                            @error('title')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    {{-- <div class="col-md-6">
                        <div class="form-group">
                            <label>Class</label>
                            <select name="class_name" class="form-control {{ hasError('class_name') }}">
                                <option value="">All Classes</option>
                                @foreach (getClassList() as $class)
                                    <option value="{{ $class }}" {{ old('class_name') == $class ? 'selected' : '' }}>
                                        {{ $class }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_name')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div> --}}

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="text" name="start_date"
                                class="form-control flat_datepicker {{ hasError('start_date') }}"
                                placeholder="{{ dateFormat(today()) }}"
                                value="{{ isset($feeLot) ? dateFormat($feeLot->start_date) : old('start_date') }}">
                            @error('start_date')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <input type="text" name="end_date"
                                class="form-control flat_datepicker {{ hasError('end_date') }}"
                                placeholder="{{ dateFormat(today()) }}"
                                value="{{ isset($feeLot) ? dateFormat($feeLot->end_date) : old('end_date') }}">
                            @error('end_date')
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

@push('js')
@endpush
