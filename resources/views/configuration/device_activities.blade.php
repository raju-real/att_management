@extends('layouts.app')
@section('title', 'Device Commands')
@push('css')
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Device Commands</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">

                        {{-- USERS SYNC --}}
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-primary text-white">
                                    User Sync
                                </div>
                                <div class="card-body">
                                    @if(session()->has('user_sync'))
                                        <p class="alert alert-info">{{ session()->get('user_sync') }}</p>
                                    @endif
                                    <form method="POST" action="{{ route('commands.sync.users') }}"
                                          id="prevent-form">
                                        @csrf

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Employee ID</label>
                                                <input type="text" name="employee_id" class="form-control" placeholder="Employee ID">
                                            </div>
                                            {{-- Direction --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    Sync Direction
                                                </label>
                                                <select name="direction" class="form-select" required>
                                                    <option value="db_to_device">Database ➜ Device</option>
                                                    <option value="device_to_db">Device ➜ Database</option>
                                                    <option value="both">Both</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- From Date --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    From Date
                                                </label>
                                                <div class="form-group input-clearable position-relative">
                                                    <input type="text"
                                                           name="from_date"
                                                           id="from_date"
                                                           class="form-control datepicker"
                                                           value="{{ request()->from_date ?? '' }}"
                                                           placeholder="Select start date"
                                                           autocomplete="off"
                                                           readonly>
                                                    <span class="clear-btn"
                                                          onclick="document.getElementById('from_date').value='';">×</span>
                                                </div>
                                            </div>

                                            {{-- To Date --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">
                                                    To Date
                                                </label>
                                                <div class="form-group input-clearable position-relative">
                                                    <input type="text"
                                                           name="to_date"
                                                           id="to_date"
                                                           class="form-control datepicker"
                                                           value="{{ request()->to_date ?? '' }}"
                                                           placeholder="Select end date"
                                                           autocomplete="off"
                                                           readonly>
                                                    <span class="clear-btn"
                                                          onclick="document.getElementById('to_date').value='';">×</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- Device --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label fw-semibold">Device (Optional)</label>
                                                <select name="device" class="form-select">
                                                    <option value="">All Devices</option>
                                                    @foreach(activeDevices() as $device)
                                                        <option value="{{ $device->serial_no }}">
                                                            {{ $device->name ?? 'Device' }} ({{ $device->serial_no }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mx-auto">
                                                <button class="btn btn-primary w-100 mt-3 submit-button">
                                                    <i class="fa fa-sync"></i> Sync Users
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        {{-- ATTENDANCE SYNC --}}
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-success text-white">
                                    Attendance Sync
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('commands.sync.attendance') }}">
                                        @csrf

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Employee ID</label>
                                                <input type="text" name="employee_id" class="form-control" required>
                                            </div>
                                            {{-- Single Date --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Date (leave empty for
                                                    today)</label>
                                                <input type="date" name="date" class="form-control">
                                            </div>
                                        </div>

                                        {{-- From / To Range --}}
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">From</label>
                                                <input type="date" name="from" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">To</label>
                                                <input type="date" name="to" class="form-control">
                                            </div>
                                        </div>

                                        <div class="row">
                                            {{-- Device --}}
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label fw-semibold">Device (Optional)</label>
                                                <select name="device" class="form-select">
                                                    <option value="">All Devices</option>
                                                    @foreach(activeDevices() as $device)
                                                        <option value="{{ $device->serial_no }}">
                                                            {{ $device->name ?? 'Device' }} ({{ $device->serial_no }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Submit Button --}}
                                        <div class="row">
                                            <div class="col-md-6 mx-auto">
                                                <button class="btn btn-success w-100 mt-3">
                                                    <i class="fa fa-sync"></i> Sync Attendance
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        {{-- CLEAR ATTENDANCE --}}
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-danger text-white">
                                    Clear Device Attendance (NB:: ITS SHOULD SAME AS Attendance Sync)
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('commands.clear.attendance') }}">
                                        @csrf

                                        <div class="row">
                                            {{-- Employee ID --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Employee ID</label>
                                                <input type="text" name="employee_id" class="form-control" required>
                                            </div>

                                            {{-- Device --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Select Device</label>
                                                <select name="device" class="form-select" required>
                                                    @foreach(activeDevices() as $device)
                                                        <option value="{{ $device->serial_no }}">
                                                            {{ $device->name ?? 'Device' }} ({{ $device->serial_no }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Submit Button --}}
                                        <div class="row">
                                            <div class="col-md-6 mx-auto">
                                                <button class="btn btn-danger w-100 mt-3"
                                                        onclick="return confirm('Are you sure? This will delete all attendance from the device.')">
                                                    <i class="fa fa-eraser"></i> Clear Attendance
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        {{-- DELETE USER --}}
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-warning">
                                    Remove User from Device (NB:: ITS SHOULD SAME AS Attendance Sync)
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('commands.delete.user') }}">
                                        @csrf

                                        <div class="row">
                                            {{-- Employee ID --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Employee ID</label>
                                                <input type="text" name="employee_id" class="form-control" required>
                                            </div>

                                            {{-- Device --}}
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Device</label>
                                                <select name="device" class="form-select" required>
                                                    @foreach(activeDevices() as $device)
                                                        <option value="{{ $device->serial_no }}">
                                                            {{ $device->name ?? 'Device' }} ({{ $device->serial_no }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Submit Button --}}
                                        <div class="row">
                                            <div class="col-md-6 mx-auto">
                                                <button class="btn btn-warning w-100 mt-3"
                                                        onclick="return confirm('Are you sure you want to remove this user from the device?')">
                                                    <i class="fa fa-user-times"></i> Remove User
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>


                    </div>

                </div>
            </div>
        </div>
@endsection

