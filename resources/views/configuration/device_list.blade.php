@extends('layouts.app')
@section('title', 'Device List')

@push('css')
<style>
    .badge-push    { background:#198754;color:#fff;font-size:.72em; }
    .badge-tcp     { background:#6c757d;color:#fff;font-size:.72em; }
    .badge-online  { background:#0d6efd;color:#fff;font-size:.72em; }
    .badge-offline { background:#dc3545;color:#fff;font-size:.72em; }
    .last-seen     { font-size:.78em;color:#6c757d; }

    /* ── Per-device action buttons panel ── */
    .device-actions-row td { background: #f8faff; border-top: none !important; }
    .device-action-btn {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 10px; border-radius:5px; font-size:.82em;
        border:1px solid transparent; cursor:pointer; text-decoration:none;
        transition:all .15s ease;
    }
    .device-action-btn:hover { opacity:.85; transform:translateY(-1px); }
    .btn-push-students  { background:#4f46e5;color:#fff;border-color:#4f46e5; }
    .btn-push-teachers  { background:#0891b2;color:#fff;border-color:#0891b2; }
    .btn-pull-att       { background:#059669;color:#fff;border-color:#059669; }
    .btn-test-conn      { background:#f59e0b;color:#fff;border-color:#f59e0b; }
    .btn-clear-users    { background:#dc2626;color:#fff;border-color:#dc2626; }

    /* ── Test connection result badge ── */
    .conn-result { font-size:.80em; padding:3px 8px; border-radius:4px; display:none; }
    .conn-result.ok   { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
    .conn-result.fail { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
    .conn-result.loading { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }

    .row-toggle { cursor:pointer; }
    .row-toggle:hover td { background:#f0f4ff !important; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Device Management</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('devices.setup-guide') }}" class="btn btn-info text-white" {!! tooltip('Setup & Configuration Guide') !!}>
                <i class="fas fa-book mr-1"></i> Setup Guide
            </a>
            <a href="{{ route('devices.create') }}" class="btn btn-primary-admin text-white" {!! tooltip('Add New Device') !!}>
                <i class="fas fa-plus mr-1"></i> Add New
            </a>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-microchip mr-2"></i>Device List
                <small class="text-muted ms-2" style="font-size:.78em;">Click a row to expand actions</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width:35px">#</th>
                            <th>Name</th>
                            <th>Serial No</th>
                            <th>Subnet / Location</th>
                            <th>Mode / Status</th>
                            <th>Device For</th>
                            <th>Status</th>
                            <th class="text-right">Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            {{-- ── Main row ── --}}
                            <tr class="row-toggle" data-toggle-target="device-row-{{ $device->id }}"
                                onclick="toggleDeviceRow({{ $device->id }}, this)">
                                <td>{{ $loop->index + 1 }}</td>
                                <td class="fw-semibold">{{ $device->name ?? '' }}</td>
                                <td><code>{{ $device->serial_no ?? '' }}</code></td>
                                <td>
                                    @if($device->subnet_label)
                                        <span class="badge bg-light text-dark border">{{ $device->subnet_label }}</span>
                                    @endif
                                    @if($device->gateway_ip)
                                        <br><small class="text-muted">GW: {{ $device->gateway_ip }}</small>
                                    @endif
                                    @if($device->location_note)
                                        <br><small class="text-muted">{{ $device->location_note }}</small>
                                    @endif
                                    @if(!$device->subnet_label && !$device->gateway_ip && !$device->location_note)
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($device->use_push_mode)
                                        <span class="badge badge-push">PUSH</span>
                                        @if($device->last_seen_at)
                                            @if($device->is_online)
                                                <span class="badge badge-online ms-1">Online</span>
                                            @else
                                                <span class="badge badge-offline ms-1">Offline</span>
                                            @endif
                                            <br><span class="last-seen">{{ $device->last_seen_at->diffForHumans() }}</span>
                                        @else
                                            <span class="badge badge-offline ms-1">Never Connected</span>
                                            <br><span class="last-seen text-warning" style="font-size:.75em;">Configure Cloud Server on device</span>
                                        @endif
                                    @else
                                        <span class="badge badge-tcp">TCP/UDP</span>
                                        <span class="last-seen ms-1">{{ $device->ip_address ?? 'No IP' }}:{{ $device->device_port ?? 4370 }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($device->device_for == 'student_teacher') <span class="badge bg-secondary">Student &amp; Teacher</span>
                                    @elseif($device->device_for == 'student') <span class="badge bg-info">Student</span>
                                    @elseif($device->device_for == 'teacher') <span class="badge bg-warning text-dark">Teacher</span>
                                    @endif
                                </td>
                                <td>{!! showStatus($device->status) !!}</td>
                                <td class="text-right text-nowrap" onclick="event.stopPropagation()">
                                    {{-- Inline quick links --}}
                                    <a href="{{ route('devices.show', $device->id) }}" class="action-btn text-info" {!! tooltip('Details') !!}><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('devices.edit', $device->slug) }}" class="action-btn" {!! tooltip('Edit') !!}><i class="fas fa-edit"></i></a>
                                    <a href="javascript:void(0)" class="action-btn text-danger delete-data"
                                       data-id="delete-device-{{ $device->id }}" {!! tooltip('Delete') !!}><i class="fas fa-trash-alt"></i></a>
                                    <i class="fas fa-chevron-down text-muted ms-2" id="chevron-{{ $device->id }}" style="font-size:.75em;transition:transform .2s"></i>

                                    <form id="delete-device-{{ $device->id }}" action="{{ route('devices.destroy', $device->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                    </form>
                                </td>
                            </tr>

                            {{-- ── Expanded actions row ── --}}
                            <tr id="device-row-{{ $device->id }}" class="device-actions-row" style="display:none">
                                <td colspan="8" class="px-4 py-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">

                                        {{-- Test Connection (AJAX) --}}
                                        <button type="button"
                                            class="device-action-btn btn-test-conn"
                                            onclick="testConnection({{ $device->id }}, this)"
                                            id="test-btn-{{ $device->id }}">
                                            <i class="fas {{ $device->use_push_mode ? 'fa-heartbeat' : 'fa-wifi' }}"></i>
                                            {{ $device->use_push_mode ? 'Check Heartbeat' : 'Test TCP Connection' }}
                                        </button>
                                        <span id="conn-result-{{ $device->id }}" class="conn-result"></span>

                                        <span class="text-muted mx-1">|</span>

                                        {{-- Push Students --}}
                                        @if($device->device_for !== 'teacher')
                                        <form action="{{ route('devices.push-students', $device->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="device-action-btn btn-push-students"
                                                onclick="return confirm('Push ALL students to [{{ $device->name }}]?')">
                                                <i class="fas fa-user-graduate"></i>
                                                Push Students
                                            </button>
                                        </form>
                                        @endif

                                        {{-- Push Teachers --}}
                                        @if($device->device_for !== 'student')
                                        <form action="{{ route('devices.push-teachers', $device->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="device-action-btn btn-push-teachers"
                                                onclick="return confirm('Push ALL teachers to [{{ $device->name }}]?')">
                                                <i class="fas fa-chalkboard-teacher"></i>
                                                Push Teachers
                                            </button>
                                        </form>
                                        @endif

                                        {{-- Pull Attendance --}}
                                        <form action="{{ route('devices.pull-attendance', $device->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="from" value="{{ date('Y-m-d') }}">
                                            <button type="submit" class="device-action-btn btn-pull-att">
                                                <i class="fas fa-cloud-download-alt"></i>
                                                Pull Today's Attendance
                                            </button>
                                        </form>

                                        <span class="text-muted mx-1">|</span>

                                        {{-- Remove All Users --}}
                                        <form action="{{ route('devices.remove-users', $device->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="device-action-btn btn-clear-users"
                                                onclick="return confirm('Remove ALL users from [{{ $device->name }}]? This cannot be undone!')">
                                                <i class="fas fa-eraser"></i>
                                                Clear All Users
                                            </button>
                                        </form>

                                        {{-- Show Users / Pull Users (TCP only — push mode has no live list) --}}
                                        @unless($device->use_push_mode)
                                        <a href="{{ route('devices.users', $device->id) }}" class="device-action-btn" style="background:#6366f1;color:#fff">
                                            <i class="fas fa-users"></i> View Device Users
                                        </a>
                                        <form action="{{ route('devices.pull-users', $device->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="device-action-btn" style="background:#7c3aed;color:#fff"
                                                onclick="return confirm('Pull all users already enrolled on [{{ $device->name }}] and create/update matching Student or Teacher records?')">
                                                <i class="fas fa-file-import"></i> Pull Users (device → DB)
                                            </button>
                                        </form>
                                        @endunless

                                        {{-- Mode badge --}}
                                        <span class="ms-auto text-muted" style="font-size:.78em">
                                            @if($device->use_push_mode)
                                                <i class="fas fa-info-circle"></i> Push Mode: Commands are queued and sent on next device poll (~30 sec)
                                            @else
                                                <i class="fas fa-info-circle"></i> TCP Mode: Commands execute immediately via socket
                                            @endif
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><x-no-data-found /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3 px-3 pb-2">
                {!! $devices->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
// Toggle expanded row
function toggleDeviceRow(id, row) {
    const target   = document.getElementById('device-row-' + id);
    const chevron  = document.getElementById('chevron-' + id);
    const isOpen   = target.style.display !== 'none';
    target.style.display = isOpen ? 'none' : 'table-row';
    if (chevron) chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
}

// AJAX test connection
function testConnection(id, btn) {
    const resultEl = document.getElementById('conn-result-' + id);
    resultEl.className = 'conn-result loading';
    resultEl.textContent = 'Testing…';
    resultEl.style.display = 'inline-block';
    btn.disabled = true;

    fetch("{{ url('test-connection-json') }}/" + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        resultEl.className = 'conn-result ' + (data.success ? 'ok' : 'fail');
        resultEl.textContent = data.message;
        btn.disabled = false;
    })
    .catch(() => {
        resultEl.className = 'conn-result fail';
        resultEl.textContent = 'Request failed. Server error.';
        btn.disabled = false;
    });
}
</script>
@endpush
