@extends('layouts.app')
@section('title', 'Device Monitor')

@push('css')
<style>
    .dm-dev { border-radius: 14px; border: 1px solid #e5e7eb; background: #fff; padding: 16px; height: 100%; position: relative; }
    .dm-dev.online  { border-left: 5px solid #10b981; }
    .dm-dev.offline { border-left: 5px solid #ef4444; }
    .dm-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
    .dm-dot.on  { background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,.6); animation: dm-pulse 1.6s infinite; }
    .dm-dot.off { background: #ef4444; }
    @keyframes dm-pulse { 70% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
    .dm-kv { font-size: 12.5px; margin: 2px 0; color: #475569; }
    .dm-kv b { color: #0f172a; font-weight: 600; }
    .dm-num { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; }
    .dm-feed td { font-size: 13px; vertical-align: middle; }
    .dm-feed tr.dm-new td { animation: dm-flash 2.5s ease; }
    @keyframes dm-flash { 0% { background: #dcfce7; } 100% { background: transparent; } }
    .dm-log { background: #0f172a; color: #cbd5e1; font-family: SFMono-Regular, Consolas, monospace; font-size: 12px;
        border-radius: 10px; padding: 12px; max-height: 320px; overflow: auto; white-space: pre-wrap; word-break: break-all; }
    .dm-log .ok { color: #6ee7b7; }
    .dm-log .warn { color: #fcd34d; }
    .dm-id { font-family: monospace; font-size: 12px; background: #eef2ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <h3 class="mb-2">Device Monitor</h3>
        <div class="mb-2 d-flex align-items-center" style="gap:8px">
            <small class="text-muted">Server time: <b id="dmServerTime">-</b> &middot; refreshes every 10s</small>
            <button class="btn btn-sm btn-secondary" id="dmToggle" {!! tooltip('Pause / resume auto refresh') !!}><i class="fas fa-pause"></i></button>
            <button class="btn btn-sm btn-primary" id="dmRefresh" {!! tooltip('Refresh now') !!}><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <div class="alert alert-info small py-2">
        <i class="fas fa-info-circle mr-1"></i>
        <b>How to tell push is working:</b> the device shows <span class="text-success font-weight-bold">Online</span>
        (it checks in every few seconds), and when someone punches, a new row appears in <b>Incoming punches</b>
        within a few seconds with its <b>Received at</b> time. A device that stays <span class="text-danger font-weight-bold">Offline</span>
        is not reaching this server: check its <i>Menu → COMM → Cloud Server / ADMS</i> address and port.
    </div>

    <div class="row mb-3" id="dmDevices">
        <div class="col-12 text-muted">Loading devices…</div>
    </div>

    <div class="card admin-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-stream mr-2"></i>Incoming punches <small class="text-muted">(latest 50 saved)</small></h5>
            <a href="{{ route('attendance.logs') }}" class="btn btn-sm btn-outline-primary">Attendance Logs</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm dm-feed mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Device</th>
                            <th>Punch time <i class="fas fa-question-circle text-muted" {!! tooltip('Time on the fingerprint device clock') !!}></i></th>
                            <th>Received at <i class="fas fa-question-circle text-muted" {!! tooltip('When the server saved it') !!}></i></th>
                            <th>Delay</th>
                        </tr>
                    </thead>
                    <tbody id="dmFeed"><tr><td colspan="7" class="text-center text-muted py-4">Loading…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-terminal mr-2"></i>Device requests log <small class="text-muted">(today, newest first)</small></h5>
        </div>
        <div class="card-body">
            <div class="dm-log" id="dmLog">Loading…</div>
            <small class="text-muted d-block mt-2">
                File on the server: <code>storage/logs/adms-{{ now()->format('Y-m-d') }}.log</code>.
                Follow it live with <code>tail -f storage/logs/adms-$(date +%F).log</code>
            </small>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    const URL_DATA = "{{ route('devices-monitor.data') }}";
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let timer = null, paused = false, lastTopId = null;

    function deviceCard(d) {
        const cmd = d.commands;
        return `<div class="col-lg-4 col-md-6 mb-3"><div class="dm-dev ${d.online ? 'online' : 'offline'}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="font-weight-bold">${esc(d.name)}</div>
                    <div class="dm-kv">SN <b>${esc(d.serial_no)}</b> · ${esc(d.mode)}${d.status !== 'active' ? ' · <span class="text-danger">inactive</span>' : ''}</div>
                </div>
                <span class="badge ${d.online ? 'badge-success' : 'badge-danger'}"><span class="dm-dot ${d.online ? 'on' : 'off'}"></span>${d.online ? 'Online' : 'Offline'}</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="dm-kv">Last check-in: <b>${d.last_seen ? esc(d.last_seen_ago) : 'never'}</b></div>
                    <div class="dm-kv">Last punch saved: <b>${d.last_received ? esc(d.last_received_ago) : 'none yet'}</b></div>
                    <div class="dm-kv">IP: <b>${esc(d.ip || '-')}</b></div>
                    <div class="dm-kv">Commands: <b>${cmd.pending}</b> waiting · <b>${cmd.sent}</b> sent · <b class="${cmd.failed ? 'text-danger' : ''}">${cmd.failed}</b> failed</div>
                </div>
                <div class="text-right"><div class="dm-num">${d.today_punches}</div><small class="text-muted">punches today</small></div>
            </div>
        </div></div>`;
    }

    function render(data) {
        document.getElementById('dmServerTime').textContent = data.server_time;

        document.getElementById('dmDevices').innerHTML = data.devices.length
            ? data.devices.map(deviceCard).join('')
            : '<div class="col-12"><div class="alert alert-warning mb-0">No devices registered yet. A push device appears here automatically the first time it contacts the server.</div></div>';

        const top = data.feed.length ? data.feed[0].id : null;
        document.getElementById('dmFeed').innerHTML = data.feed.length
            ? data.feed.map(r => `<tr class="${lastTopId !== null && r.id > lastTopId ? 'dm-new' : ''}">
                    <td class="text-muted">${r.id}</td>
                    <td>${r.name ? esc(r.name) : '<span class="text-danger">Not registered</span>'}
                        <small class="text-muted ml-1">${esc(r.user_type || '')}</small></td>
                    <td><span class="dm-id">${esc(r.user_no || '-')}</span></td>
                    <td>${esc(r.device)}</td>
                    <td>${esc(r.punch_time)}</td>
                    <td class="font-weight-bold">${esc(r.received_at)}</td>
                    <td><small class="text-muted">${esc(r.delay || '-')}</small></td>
                </tr>`).join('')
            : '<tr><td colspan="7" class="text-center text-muted py-4">No punches saved yet.</td></tr>';
        lastTopId = top;

        document.getElementById('dmLog').innerHTML = data.log.length
            ? data.log.map(l => {
                const cls = /saved":[1-9]/.test(l) ? 'ok' : (/"saved":0|failed|Return=-/.test(l) ? 'warn' : '');
                return `<div class="${cls}">${esc(l)}</div>`;
            }).join('')
            : 'No device requests logged today yet.';
    }

    function load() {
        return fetch(URL_DATA, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(render)
            .catch(e => console.error('Monitor refresh failed', e));
    }

    function schedule() { clearInterval(timer); if (!paused) timer = setInterval(() => { if (!document.hidden) load(); }, 10000); }

    document.getElementById('dmRefresh').addEventListener('click', load);
    document.getElementById('dmToggle').addEventListener('click', function () {
        paused = !paused;
        this.innerHTML = paused ? '<i class="fas fa-play"></i>' : '<i class="fas fa-pause"></i>';
        schedule();
    });

    load();
    schedule();
})();
</script>
@endpush
