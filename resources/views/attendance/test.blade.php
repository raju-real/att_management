@extends('layouts.app')
@section('title', 'Test Attendance')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Test Attendance Recording</h4>
                </div>
                <div class="card-body">
                    <form id="testAttendanceForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select class="form-select" id="employee_id" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->employee_id }}">
                                                {{ $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="device_id" class="form-label">Device</label>
                                    <select class="form-select" id="device_id" name="device_id" required>
                                        <option value="">Select Device</option>
                                        @foreach (DB::table('devices')->get() as $device)
                                            <option value="{{ $device->serial_no }}">
                                                {{ $device->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="punch_time" class="form-label">Punch Time</label>
                                    <input type="datetime-local" class="form-control" id="punch_time" name="punch_time"
                                           value="{{ now()->format('Y-m-d\TH:i') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="attendance_by" class="form-label">Type</label>
                                    <select class="form-select" id="attendance_by" name="attendance_by">
                                        <option value="fingerprint">Fingerprint</option>
                                        <option value="card">Card</option>
                                        <option value="face">Face</option>
                                        <option value="pin">PIN</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="direction" class="form-label">Direction</label>
                                    <select class="form-select" id="direction" name="direction">
                                        <option value="in">Check In</option>
                                        <option value="out">Check Out</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Quick Actions</label>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                onclick="setTime('now')">Now
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="setTime('today_morning')">Today 9:00 AM
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="setTime('today_evening')">Today 5:00 PM
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Record Test Attendance</button>
                        <button type="button" class="btn btn-success" onclick="recordMultiple()">Record Multiple
                            Entries
                        </button>
                    </form>

                    <div id="result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Recent Test Entries</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Punch Time</th>
                                <th>Type</th>
                                <th>Direction</th>
                                <th>Device</th>
                            </tr>
                            </thead>
                            <tbody id="recentEntries">
                            @foreach ($recentEntries as $entry)
                                <tr>
                                    <td>{{ $entry->employee->name }}</td>
                                    <td>{{ $entry->punch_time->format('Y-m-d H:i:s') }}</td>
                                    <td>{{ $entry->attendance_by }}</td>
                                    <td>{{ $entry->direction }}</td>
                                    <td>{{ $entry->device->name ?? 'Test Device' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        function setTime(type) {
            const now = new Date();
            let dateTime;

            switch (type) {
                case 'now':
                    dateTime = now.toISOString().slice(0, 16);
                    break;
                case 'today_morning':
                    dateTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 9, 0).toISOString().slice(0, 16);
                    break;
                case 'today_evening':
                    dateTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 17, 0).toISOString().slice(0, 16);
                    break;
            }

            document.getElementById('punch_time').value = dateTime;
        }

        function recordMultiple() {
            const employeeId = document.getElementById('employee_id').value;
            const deviceId = document.getElementById('device_id').value;
            if (!employeeId) {
                alert('Please select an employee first');
                return;
            }
            if (!deviceId) {
                alert('Please select an device first');
                return;
            }

            const baseTime = document.getElementById('punch_time').value || new Date().toISOString().slice(0, 16);
            const baseDate = new Date(baseTime);

            // Record check-in (9:00 AM)
            const checkInTime = new Date(baseDate);
            checkInTime.setHours(9, 0, 0);
            recordSingleEntry(employeeId, checkInTime, 'in');

            // Record check-out (5:00 PM)
            const checkOutTime = new Date(baseDate);
            checkOutTime.setHours(17, 0, 0);
            setTimeout(() => recordSingleEntry(employeeId, checkOutTime, 'out'), 1000);
        }

        function recordSingleEntry(employeeId, punchTime, direction) {
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('device_id', deviceId);
            formData.append('punch_time', punchTime.toISOString().slice(0, 16));
            formData.append('attendance_by', document.getElementById('attendance_by').value);
            formData.append('direction', direction);
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            fetch('{{ route('attendance.test') }}', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult(`✅ ${direction.toUpperCase()} recorded successfully!`, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showResult('❌ Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    showResult('❌ Network error: ' + error, 'danger');
                });
        }

        document.getElementById('testAttendanceForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('{{ route('attendance.test') }}', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult('✅ Attendance recorded successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showResult('❌ Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    showResult('❌ Network error: ' + error, 'danger');
                });
        });

        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }
    </script>
@endpush
