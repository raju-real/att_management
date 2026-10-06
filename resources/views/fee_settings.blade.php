@extends('layouts.app')
@section('title', 'Fee Settings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Fee Settings</h3>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i> Class Wise Fees</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('update-fee-settings') }}" id="prevent-form" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- Late Fee Section --}}
                    <div class="col-md-12 mb-4">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label font-weight-bold text-danger">Late Fee</label>
                            <div class="col-sm-4">
                                <div class="input-group">
                                    <input type="number" name="late_fee" value="{{ $lateFee }}" class="form-control"
                                        placeholder="Enter Late Fee" min="0" step="0.01">
                                    <div class="input-group-append">
                                        <span class="input-group-text">Tk</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>

                    @forelse($classes as $class)
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label font-weight-bold">{{ $class }}</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="number" name="fees[{{ $class }}]"
                                            value="{{ $classFees[$class] ?? '' }}" class="form-control"
                                            placeholder="Fee for {{ $class }}" min="0" step="0.01">
                                        <div class="input-group-append">
                                            <span class="input-group-text">Tk</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning">
                                No classes found in the student database. Please add students with classes first.
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="text-right mt-3">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection
