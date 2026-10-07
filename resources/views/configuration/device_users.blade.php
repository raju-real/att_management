@extends('layouts.app')
@section('title', 'Device Users')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h3 class="mb-2">Device Users: {{ $device->name }} <small class="text-muted">({{ $device->serial_no }})</small></h3>
        <div class="mb-2 d-flex" style="gap:6px">
            <form action="{{ route('devices.fetch-users', $device->id) }}" method="POST" id="prevent-form">
                @csrf
                <button type="submit" class="btn btn-primary submit-button"
                    {!! tooltip($device->use_push_mode ? 'Ask the device to send its user list (arrives on its next check-in)' : 'Read the user list from the device now') !!}>
                    <i class="fas fa-sync-alt mr-1"></i> Fetch Users from Device
                </button>
            </form>
            <a href="{{ route('devices.index') }}" class="btn btn-secondary" {!! tooltip('Back to Devices') !!}>
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <div class="alert {{ $waiting ? 'alert-warning' : 'alert-info' }} small py-2">
        <i class="fas fa-info-circle mr-1"></i>
        @if($waiting)
            <b>Waiting for the device…</b> The user list was requested and will arrive on the device's next check-in
            (usually within a minute). <a href="{{ request()->fullUrl() }}">Refresh</a> to see it.
        @else
            {{ number_format($total) }} user(s) stored for this device.
            Last received: <b>{{ $lastFetched ? \Carbon\Carbon::parse($lastFetched)->format('d M Y, h:i A') . ' (' . \Carbon\Carbon::parse($lastFetched)->diffForHumans() . ')' : 'never' }}</b>.
            Click <b>Fetch Users from Device</b> to update the list.
        @endif
        @if($device->use_push_mode && !$device->last_seen_at)
            <br><span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>This device has never contacted the server, so it cannot answer yet. Check its ADMS / Cloud Server settings.</span>
        @endif
    </div>

    <div class="card admin-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('devices.users', $device->id) }}" class="row">
                <div class="col-md-4 mb-2">
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search PIN or name">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All users</option>
                        <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Registered (teacher / student)</option>
                        <option value="unregistered" {{ request('status') === 'unregistered' ? 'selected' : '' }}>Not registered in the system</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex" style="gap:6px">
                    <button class="btn btn-primary flex-fill"><i class="fas fa-search"></i></button>
                    <a href="{{ route('devices.users', $device->id) }}" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-users mr-2"></i>Users on device</h5>
            <small class="text-muted">{{ number_format($users->total()) }} shown</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>PIN <i class="fas fa-question-circle text-muted" {!! tooltip('User ID on the device; must equal the Teacher No / Student No') !!}></i></th>
                            <th>Name on device</th>
                            <th>Role</th>
                            <th>Card</th>
                            <th>In the system</th>
                            <th>Last received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td class="text-muted">{{ $users->firstItem() + $loop->index }}</td>
                                <td><span class="badge badge-light border" style="font-family:monospace;font-size:.9em">{{ $u->pin }}</span></td>
                                <td class="font-weight-bold">{{ $u->name ?: '-' }}</td>
                                <td>{!! $u->privilege >= 14 ? '<span class="badge badge-warning">Admin</span>' : '<span class="text-muted">User</span>' !!}</td>
                                <td>{{ $u->card ?: '-' }}</td>
                                <td>
                                    @if($u->teacher_id)
                                        <span class="badge badge-success">Teacher</span>
                                        @if($u->teacher_name && $u->name && strcasecmp(trim($u->teacher_name), trim($u->name)) !== 0)
                                            <small class="text-muted d-block">as "{{ $u->teacher_name }}"</small>
                                        @endif
                                    @elseif($u->student_id)
                                        <span class="badge badge-info">Student</span>
                                    @else
                                        <span class="badge badge-danger">Not registered</span>
                                        <a href="{{ route('teachers.create', ['pin' => $u->pin, 'name' => $u->name]) }}" class="small ml-1"
                                           {!! tooltip('Open the Add Teacher form pre-filled with this PIN and name') !!}>Add as teacher</a>
                                    @endif
                                </td>
                                <td>
                                    @if($u->received_at)
                                        <small class="text-muted">{{ $u->received_at->format('d M Y, h:i A') }}</small>
                                    @else
                                        <span class="badge badge-light border" {!! tooltip('Pushed from this system; waiting for the device to confirm') !!}>Pushed, awaiting device</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-users fa-2x d-block mb-2" style="opacity:.3"></i>
                                    No users stored for this device yet. Click <b>Fetch Users from Device</b>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center pt-3">
                {!! $users->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection
