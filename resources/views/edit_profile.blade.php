@extends('layouts.app')
@section('title','Edit Profile')
@push('css') @endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Edit Profile</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('update-profile') }}" method="POST" id="prevent-form" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Name {!! starSign() !!}</label>
                                    <input type="text" name="name" value="{{ authUser()->name ?? '' }}"
                                           class="form-control {{ hasError('name') }}"
                                           placeholder="Name" readonly>
                                    @error('name')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Email {!! starSign() !!}</label>
                                    <input type="text" name="email" value="{{ authUser()->email ?? '' }}"
                                           class="form-control {{ hasError('email') }}"
                                           placeholder="Email" readonly>
                                    @error('email')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Mobile {!! starSign() !!}</label>
                                    <input type="text" name="mobile" value="{{ authUser()->mobile ?? '' }}"
                                           class="form-control {{ hasError('mobile') }}"
                                           placeholder="Mobile" readonly>
                                    @error('mobile')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label d-flex align-items-center justify-content-between">
                                        <span>Photo (Type: jpg, jpeg, png, Max: 1MB)</span>
                                        @if(isset(authUser()->image) && file_exists(authUser()->image))
                                            <button type="button"
                                                    class="custom-badge badge-info view-image"
                                                    data-image-url="{{ asset(authUser()->image) }}"
                                                    title="View Image">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        @endif
                                    </label>
                                    <input type="file" name="image" class="form-control {{ hasError('image') }}" accept=".jpg,.jpeg,.png">
                                    @error('image')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>

                        </div>
                        <div>
                            <x-submit-button></x-submit-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js') @endpush
