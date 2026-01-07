@extends('layouts.app')
@section('title','Device Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($device) ? 'Edit' : 'Add' }} Device</h3>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Device Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($device)
                    @method('PUT')
                @endisset
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name') ?? $device->name ?? '' }}"
                                   class="form-control {{ hasError('name') }}"
                                   placeholder="Name">
                            @error('name')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Serial No. {!! starSign() !!}</label>
                            <input type="text" name="serial_no"
                                   value="{{ old('serial_no') ?? $device->serial_no ?? '' }}"
                                   class="form-control {{ hasError('serial_no') }}"
                                   placeholder="Serial No.">
                            @error('serial_no')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Device IP Address {!! starSign() !!}</label>
                            <input type="text" name="ip_address"
                                   value="{{ old('ip_address') ?? $device->ip_address ?? '' }}"
                                   class="form-control {{ hasError('ip_address') }}"
                                   placeholder="Device IP Address">
                            @error('ip_address')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Device Port {!! starSign() !!}</label>
                            <input type="number" name="device_port"
                                   value="{{ old('device_port') ?? $device->device_port ?? '' }}"
                                   class="form-control {{ hasError('device_port') }}"
                                   placeholder="Device Port">
                            @error('device_port')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Communication Key(Comm Key) {!! starSign() !!}</label>
                            <input type="number" name="comm_key"
                                   value="{{ old('comm_key') ?? $device->comm_key ?? '' }}"
                                   class="form-control {{ hasError('comm_key') }}"
                                   placeholder="Communication Key(Comm Key)">
                            @error('comm_key')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach(getStatus() as $status)
                                    <option
                                        value="{{ $status->value }}" {{ (old('status') === $status->value || (isset($device) && $device->status === $status->value && empty(old('status')))) ? 'selected' : '' }}>{{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="device_for" class="form-control {{ hasError('device_for') }}">
                                @foreach(getDeviceFor() as $device_for)
                                    <option
                                        value="{{ $device_for->value }}" {{ (old('device_for') === $device_for->value || (isset($device) && $device->device_for === $device_for->value && empty(old('device_for')))) ? 'selected' : '' }}>{{ $device_for->title }}</option>
                                @endforeach
                            </select>
                            @error('device_for')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button/>
                </div>
            </form>
        </div>
    </div>
@endsection

