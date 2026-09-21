@extends('layouts.app')
@section('title', 'Device Setup Guide')

@push('css')
<style>
    /* ── Layout ── */
    .guide-section         { margin-bottom: 2.2rem; }
    .guide-card            { border-radius: 12px; border: 1.5px solid #e0e7ef; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
    .guide-card .card-header { padding: 14px 20px; font-size: 1.05em; font-weight: 600; display: flex; align-items: center; }
    .guide-card .card-header.indigo { background: linear-gradient(90deg,#4f46e5,#6366f1); color:#fff; }
    .guide-card .card-header.green  { background: linear-gradient(90deg,#059669,#10b981); color:#fff; }
    .guide-card .card-header.blue   { background: linear-gradient(90deg,#0891b2,#06b6d4); color:#fff; }
    .guide-card .card-header.orange { background: linear-gradient(90deg,#d97706,#f59e0b); color:#1c1c1c; }
    .guide-card .card-header.purple { background: linear-gradient(90deg,#7c3aed,#a78bfa); color:#fff; }
    .guide-card .card-header.teal   { background: linear-gradient(90deg,#0d9488,#14b8a6); color:#fff; }
    .guide-card .card-header.red    { background: linear-gradient(90deg,#dc2626,#ef4444); color:#fff; }

    .step-num {
        display:inline-flex; align-items:center; justify-content:center;
        width:30px; height:30px; border-radius:50%;
        background:rgba(255,255,255,.25); font-weight:700; font-size:.9em; margin-right:10px; flex-shrink:0;
    }

    /* ── Tables ── */
    .config-table { font-size:.9em; }
    .config-table td:first-child { font-weight:600; white-space:nowrap; width:175px; background:#f8faff; }
    .config-table td:last-child  { font-family:monospace; }
    .config-table tr.highlight td { background:#fef3c7 !important; }

    /* ── Boxes ── */
    .config-box  { background:#f8faff; border:1px solid #c7d2fe; border-radius:8px; padding:14px 18px; font-family:monospace; font-size:.88em; line-height:1.8; }
    .note-box    { background:#fef9c3; border-left:4px solid #f59e0b; padding:10px 14px; border-radius:0 6px 6px 0; font-size:.87em; margin-bottom:.8rem; }
    .warn-box    { background:#fee2e2; border-left:4px solid #ef4444; padding:10px 14px; border-radius:0 6px 6px 0; font-size:.87em; margin-bottom:.8rem; }
    .info-box    { background:#e0f2fe; border-left:4px solid #0ea5e9; padding:10px 14px; border-radius:0 6px 6px 0; font-size:.87em; margin-bottom:.8rem; }
    .success-box { background:#d1fae5; border-left:4px solid #059669; padding:10px 14px; border-radius:0 6px 6px 0; font-size:.87em; margin-bottom:.8rem; }

    .tick  { color:#059669; font-weight:700; }
    .cross { color:#dc2626; font-weight:700; }

    /* ── Subnet diagram ── */
    .subnet-diagram {
        background:#1e1e2e; color:#cdd6f4; border-radius:10px;
        padding:18px 22px; font-family:'Courier New',monospace; font-size:.84em; line-height:2;
    }
    .subnet-diagram .hl    { color:#a6e3a1; font-weight:600; }
    .subnet-diagram .ip    { color:#89b4fa; }
    .subnet-diagram .arrow { color:#fab387; }
    .subnet-diagram .note  { color:#f38ba8; font-size:.9em; }

    /* ── Menu path pills ── */
    .menu-path { display:inline-block; background:#1e1e2e; color:#a6e3a1; border-radius:6px; padding:3px 10px; font-family:monospace; font-size:.88em; letter-spacing:.5px; }

    /* ── Screen mockup ── */
    .device-screen {
        background:#111; color:#0f0; border-radius:8px; border:3px solid #555;
        padding:12px 16px; font-family:monospace; font-size:.82em; line-height:1.9;
        max-width:300px;
    }
    .device-screen .cursor { animation: blink 1s step-end infinite; }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
    .device-screen .active-item { background:#0f0; color:#000; padding:0 4px; }

    /* ── Status badges ── */
    .badge-push    { background:#198754; color:#fff; font-size:.72em; padding:3px 7px; border-radius:4px; }
    .badge-tcp     { background:#6c757d; color:#fff; font-size:.72em; padding:3px 7px; border-radius:4px; }
    .badge-online  { background:#0d6efd; color:#fff; font-size:.72em; padding:3px 7px; border-radius:4px; }
    .badge-offline { background:#dc3545; color:#fff; font-size:.72em; padding:3px 7px; border-radius:4px; }
    .online-dot { display:inline-block; width:9px; height:9px; border-radius:50%; background:#22c55e; margin-right:5px; animation:pulse 1.5s infinite; }
    .offline-dot { display:inline-block; width:9px; height:9px; border-radius:50%; background:#ef4444; margin-right:5px; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

    /* ── conn test ── */
    .conn-result { font-size:.80em; padding:3px 8px; border-radius:4px; display:none; }
    .conn-result.ok      { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; display:inline-block; }
    .conn-result.fail    { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; display:inline-block; }
    .conn-result.loading { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; display:inline-block; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="fas fa-book-open mr-2 text-primary"></i>ZkTeco Device Setup Guide</h3>
    <a href="{{ route('devices.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to Devices
    </a>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 0 — Quick Mode Comparison --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header indigo">
            <span class="step-num">★</span> Two Connection Modes — Which Should You Use?
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-right">
                    <h6 class="text-danger"><i class="fas fa-network-wired mr-1"></i> TCP/UDP Mode (Direct)</h6>
                    <p class="text-muted small mb-1">Server <strong>→</strong> Device (via UDP port 4370)</p>
                    <ul class="small mb-0">
                        <li><span class="tick">✓</span> Instant — no delay</li>
                        <li><span class="tick">✓</span> Works on LAN, same subnet</li>
                        <li><span class="cross">✗</span> Fails on <strong>shared hosting</strong></li>
                        <li><span class="cross">✗</span> Fails across <strong>different subnets</strong> without L3 routing</li>
                        <li><span class="cross">✗</span> Blocked if firewall/NAT is present</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success"><i class="fas fa-cloud-upload-alt mr-1"></i> Push Mode / iClock (Recommended)</h6>
                    <p class="text-muted small mb-1">Device <strong>→</strong> Server (HTTP every 30 sec)</p>
                    <ul class="small mb-0">
                        <li><span class="tick">✓</span> <strong>Works on shared hosting</strong></li>
                        <li><span class="tick">✓</span> <strong>Works across subnets &amp; internet</strong></li>
                        <li><span class="tick">✓</span> Works with HTTPS (port 443)</li>
                        <li><span class="tick">✓</span> Attendance auto-saved when finger scanned</li>
                        <li><span class="tick">✓</span> Users pushed via database command queue</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 1 — Device Ethernet Settings (FIRST STEP) --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header green">
            <span class="step-num">1</span> Step 1 — Configure Device Ethernet / Network (Do This First)
        </div>
        <div class="card-body">
            <p class="text-muted small">Before setting the Cloud Server, the device must have correct network settings so it can reach your server.</p>
            <p><span class="menu-path">MENU → COMM → Ethernet</span> &nbsp; (some models: <span class="menu-path">MENU → COMM → Network</span>)</p>

            <div class="row mt-2">
                <div class="col-md-5">
                    {{-- Device screen mockup --}}
                    <div class="device-screen">
                        <div>[ Ethernet Settings ]</div>
                        <div class="active-item">&gt; IP Address    192.168.0.201</div>
                        <div>&nbsp; Subnet Mask  255.255.255.0</div>
                        <div>&nbsp; Gateway      192.168.0.1</div>
                        <div>&nbsp; DNS Server   8.8.8.8</div>
                        <div>&nbsp; DHCP         Off</div>
                        <div class="cursor">_</div>
                    </div>
                    <p class="text-muted small mt-2 text-center">Example ZkTeco Ethernet screen</p>
                </div>
                <div class="col-md-7">
                    <table class="table table-sm table-bordered config-table">
                        <thead class="table-dark"><tr><th>Setting</th><th>What to Enter</th></tr></thead>
                        <tbody>
                            <tr><td>IP Address</td><td>A <strong>fixed IP</strong> for this device, e.g. <code>192.168.0.201</code></td></tr>
                            <tr><td>Subnet Mask</td><td><code>255.255.255.0</code> (standard /24 network)</td></tr>
                            <tr class="highlight"><td>Gateway ⚠️</td><td>Your router's IP, e.g. <code>192.168.0.1</code><br><small class="text-danger">Must be correct for internet / cross-subnet access!</small></td></tr>
                            <tr><td>DNS</td><td><code>8.8.8.8</code> (Google DNS)</td></tr>
                            <tr><td>DHCP</td><td><span class="cross">OFF</span> — Use static IP so device IP doesn't change</td></tr>
                        </tbody>
                    </table>
                    <div class="warn-box">
                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                        <strong>Gateway is critical.</strong> Without the correct gateway IP, the device can't communicate with any server outside its subnet — not even on the same LAN if subnets differ.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 2 — Cloud Server / ADMS Settings --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header blue">
            <span class="step-num">2</span> Step 2 — Cloud Server / ADMS Settings (Push Mode)
        </div>
        <div class="card-body">
            <p><span class="menu-path">MENU → COMM → Cloud Server</span> &nbsp; (some models: <span class="menu-path">MENU → COMM → ADMS</span> or <span class="menu-path">Server</span>)</p>

            <div class="row mt-2">
                <div class="col-md-5">
                    <div class="device-screen">
                        <div>[ Cloud Server ]</div>
                        <div class="active-item">&gt; Enable       ON</div>
                        <div>&nbsp; Domain Mode  ON</div>
                        <div>&nbsp; Server Addr  {{ $appDomain ?: 'your-domain.com' }}</div>
                        <div>&nbsp; Server Port  {{ str_starts_with($appUrl, 'https') ? 443 : 80 }}</div>
                        <div>&nbsp; HTTPS        {{ str_starts_with($appUrl, 'https') ? 'ON' : 'OFF' }}</div>
                        <div>&nbsp; Proxy Server OFF</div>
                        <div class="cursor">_</div>
                    </div>
                    <p class="text-muted small mt-2 text-center">Example — using this app's own domain</p>
                </div>
                <div class="col-md-7">
                    <table class="table table-sm table-bordered config-table">
                        <thead class="table-dark"><tr><th>Setting</th><th>Value</th></tr></thead>
                        <tbody>
                            <tr><td>Enable</td><td><span class="tick">ON</span> — must be ON</td></tr>
                            <tr><td>Enable Domain Name</td><td><span class="tick">ON</span> — lets you type a domain name<br><small class="text-muted">If not present, device only accepts IP addresses</small></td></tr>
                            <tr class="highlight"><td>Server Address ⚠️</td><td>See scenarios below</td></tr>
                            <tr class="highlight"><td>Server Port ⚠️</td><td>See scenarios below</td></tr>
                            <tr><td>HTTPS</td><td>ON if using https:// (port 443)</td></tr>
                            <tr><td>Proxy Server</td><td><span class="cross">OFF</span> — <strong>Always OFF!</strong><br><small class="text-danger">Do not set 0.0.0.0 — it blocks connection</small></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 3 — Local Development --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header green">
            <span class="step-num">3</span> Scenario A — Local Development (XAMPP, Same WiFi/LAN)
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-desktop text-success mr-1"></i>Your PC / Server Details</h6>
                    <div class="config-box mb-3">
                        <div>PC Local IP:   <strong class="text-primary">{{ $localIp }}</strong></div>
                        <div>App URL:       <strong>{{ $appUrl }}</strong></div>
                        <div>iClock URL:    <strong>http://{{ $localIp }}:{{ $appPort }}/iclock/getrequest</strong></div>
                    </div>

                    <div class="note-box">
                        <i class="fas fa-info-circle text-warning mr-1"></i>
                        <strong>PC IP changes?</strong> If your router assigns IP via DHCP, your PC IP may change on reboot.
                        Fix: In router admin, set DHCP reservation to always give your PC's MAC address the same IP.
                    </div>
                    <div class="info-box mb-0">
                        <i class="fas fa-sitemap text-info mr-1"></i>
                        <strong>Why port {{ $appPort }}?</strong> This XAMPP instance serves several projects from the
                        same <code>htdocs</code> folder on port 80. This app gets its own dedicated
                        <code>VirtualHost</code> on port {{ $appPort }} so it doesn't collide with (or need to change)
                        anything the other projects rely on. On real shared hosting you get the whole domain, so no
                        custom port is needed there — see Scenario C below.
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-microchip mr-1"></i>Cloud Server Settings on Device</h6>
                    <table class="table table-sm table-bordered config-table">
                        <tr><td>Enable</td><td><span class="tick">ON</span></td></tr>
                        <tr><td>Enable Domain Name</td><td><span class="cross">OFF</span> (or leave)</td></tr>
                        <tr class="highlight"><td>Server Address</td><td><strong>{{ $localIp }}</strong></td></tr>
                        <tr class="highlight"><td>Server Port</td><td><strong>{{ $appPort }}</strong></td></tr>
                        <tr><td>HTTPS</td><td><span class="cross">OFF</span></td></tr>
                        <tr><td>Proxy Server</td><td><span class="cross">OFF</span></td></tr>
                    </table>

                    <h6 class="mt-3"><i class="fas fa-shield-alt mr-1"></i>Windows Firewall</h6>
                    <p class="small text-muted">If device can't connect, run this in <strong>PowerShell as Admin</strong>:</p>
                    <div class="config-box small">netsh advfirewall firewall add rule name="XAMPP ZkTeco" dir=in action=allow protocol=TCP localport={{ $appPort }}</div>
                </div>
            </div>

            <hr>
            <h6><i class="fas fa-question-circle text-warning mr-1"></i>Troubleshooting — Device Shows "Offline" After Setup</h6>
            <div class="row">
                <div class="col-md-6">
                    <ol class="small">
                        <li>Open browser on your PC: <code>http://{{ $localIp }}:{{ $appPort }}/iclock/getrequest</code><br>
                            <span class="tick">✓</span> Must show: <code>OK</code></li>
                        <li>Device Gateway (<span class="menu-path">MENU → COMM → Ethernet → Gateway</span>) must be your router IP (e.g. <code>192.168.0.1</code>)</li>
                        <li>Proxy Server must be <strong>OFF</strong></li>
                        <li>After saving, device reconnects in ~30–60 seconds — wait and refresh page</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <ol class="small" start="5">
                        <li>Test from another device on same WiFi: <code>http://{{ $localIp }}:{{ $appPort }}/iclock/getrequest</code> — if it loads, firewall is fine</li>
                        <li>Check XAMPP Apache is actually running (green in XAMPP control panel)</li>
                        <li>This app's routes handle <code>/iclock/*</code> directly (<code>IClockController</code>) — nothing separate to check under <code>htdocs</code>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 4 — Different Subnets (Multiple Buildings) --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header orange">
            <span class="step-num">4</span> Scenario B — Same Network, Different Subnets (Multiple Buildings)
        </div>
        <div class="card-body">
            <p class="small text-muted">
                If your server is on <code>192.168.0.x</code> and a device is on <code>192.168.1.x</code>, they are on <strong>different subnets</strong>.
                Since devices use Push Mode (they call the server), they just need a <strong>route</strong> to reach the server's IP.
                That route is provided by the <strong>Gateway</strong> setting on the device.
            </p>

            {{-- Network diagram --}}
            <div class="subnet-diagram mb-3">
<span class="hl">Your Server / PC</span>  IP: <span class="ip">192.168.0.102</span>  (Building A — Main Office)<br>
│<br>
└─── <span class="hl">Main Router/Switch</span>  Gateway: <span class="ip">192.168.0.1</span>  (handles all inter-subnet traffic)<br>
     │<br>
     ├── Building A  Subnet: <span class="ip">192.168.0.0/24</span><br>
     │    Device IP: <span class="ip">192.168.0.201</span>  <span class="arrow">→</span> Gateway: <span class="ip">192.168.0.1</span>  <span class="arrow">→</span> Server: <span class="ip">192.168.0.102</span>  <span class="note">← same subnet, no routing needed</span><br>
     │<br>
     ├── Building B  Subnet: <span class="ip">192.168.1.0/24</span><br>
     │    Device IP: <span class="ip">192.168.1.201</span>  <span class="arrow">→</span> Gateway: <span class="ip">192.168.1.1</span>  <span class="arrow">→</span> Router routes to <span class="ip">192.168.0.x</span>  <span class="arrow">→</span> Server<br>
     │<br>
     └── Building C  Subnet: <span class="ip">192.168.2.0/24</span><br>
          Device IP: <span class="ip">192.168.2.201</span>  <span class="arrow">→</span> Gateway: <span class="ip">192.168.2.1</span>  <span class="arrow">→</span> Router routes to <span class="ip">192.168.0.x</span>  <span class="arrow">→</span> Server
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6>Ethernet Settings (per building device):</h6>
                    <table class="table table-sm table-bordered config-table">
                        <thead class="table-dark">
                            <tr><th>Setting</th><th>Building A</th><th>Building B</th><th>Building C</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>IP Address</td><td>192.168.0.201</td><td>192.168.1.201</td><td>192.168.2.201</td></tr>
                            <tr><td>Subnet Mask</td><td colspan="3">255.255.255.0</td></tr>
                            <tr class="highlight"><td>Gateway ⚠️</td><td>192.168.0.1</td><td>192.168.1.1</td><td>192.168.2.1</td></tr>
                            <tr><td>DNS</td><td colspan="3">8.8.8.8</td></tr>
                        </tbody>
                    </table>

                    <div class="info-box">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        The <strong>Gateway</strong> is what changes per building. It must be a router/switch port on that subnet that can <em>route traffic to</em> the <code>192.168.0.x</code> subnet.
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Cloud Server Settings (ALL buildings — same for all!):</h6>
                    <table class="table table-sm table-bordered config-table">
                        <tr><td>Enable</td><td><span class="tick">ON</span></td></tr>
                        <tr class="highlight"><td>Server Address</td><td><strong>{{ $localIp }}</strong> <small class="text-muted">(your server PC IP)</small></td></tr>
                        <tr class="highlight"><td>Server Port</td><td><strong>{{ $appPort }}</strong></td></tr>
                        <tr><td>Proxy Server</td><td><span class="cross">OFF</span></td></tr>
                    </table>

                    <h6 class="mt-3">Router requirement:</h6>
                    <div class="note-box">
                        <i class="fas fa-route text-warning mr-1"></i>
                        The routers connecting buildings need <strong>inter-VLAN routing</strong> or <strong>static routes</strong> configured.
                        A Layer 3 (L3) managed switch or a router with multiple interfaces handles this automatically.
                        Consumer-grade WiFi routers typically cannot route between subnets.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 4B — Your Devices' Subnet Worksheet (live, from DB) --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header orange">
            <span class="step-num"><i class="fas fa-sitemap"></i></span> Your Devices — Subnet Worksheet
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Scaling to more devices is just repeating Steps 1–2 per device, with a fixed IP, correct gateway,
                and the same Server Address/Port on every one of them. Fill in <strong>Subnet / Building Label</strong>,
                <strong>Gateway IP</strong> and <strong>Location Note</strong> on each device's edit page so this
                worksheet stays accurate as you add devices — it's the single source of truth instead of an external
                spreadsheet.
            </p>
            @if($devices->isEmpty())
                <div class="alert alert-warning mb-0">No active devices yet. <a href="{{ route('devices.create') }}">Add your first device</a>.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered config-table">
                        <thead class="table-dark">
                            <tr><th>Device</th><th>Serves</th><th>Subnet / Building</th><th>Gateway IP</th><th>Location</th><th>Mode</th></tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $d)
                                <tr>
                                    <td>{{ $d->name }} <br><small class="text-muted">{{ $d->serial_no }}</small></td>
                                    <td>
                                        @if($d->device_for === 'student_teacher') Student &amp; Teacher
                                        @else {{ ucfirst($d->device_for) }}
                                        @endif
                                    </td>
                                    <td>{{ $d->subnet_label ?: '—' }}</td>
                                    <td>{{ $d->gateway_ip ?: '—' }}</td>
                                    <td>{{ $d->location_note ?: '—' }}</td>
                                    <td>
                                        @if($d->use_push_mode) <span class="badge-push">PUSH</span>
                                        @else <span class="badge-tcp">TCP</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="info-box mb-0">
                    <i class="fas fa-lightbulb text-info mr-1"></i>
                    <strong>Smart tip:</strong> every Push Mode device — regardless of subnet or building — points at
                    the <em>same</em> Server Address/Port (this server). Only the device's own <strong>Ethernet
                    Gateway</strong> changes per subnet. You never need a different server configuration per building;
                    you only need each device's own router to know how to route back here.
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 5 — Shared Hosting / HTTPS --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header teal">
            <span class="step-num">5</span> Scenario C — Shared Hosting Deployment
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="success-box mb-2">
                        <i class="fas fa-check-circle text-success mr-1"></i>
                        <strong>Great news:</strong> Push Mode works perfectly on shared hosting!
                        The device makes outgoing HTTP/HTTPS requests — no special server configuration needed
                        beyond deploying this Laravel app normally, the same as any other page on the site.
                    </div>
                    <div class="config-box">
                        <div>Domain:       <strong>{{ $appDomain ?: 'your-domain.com' }}</strong></div>
                        <div>Protocol:     <strong>{{ str_starts_with($appUrl, 'https') ? 'HTTPS' : 'HTTP' }}</strong></div>
                        <div>Port:         <strong>{{ str_starts_with($appUrl, 'https') ? 443 : 80 }}</strong></div>
                        <div>iClock URL:   <strong>{{ rtrim($appUrl, '/') }}/iclock/getrequest</strong></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Cloud Server Settings on Device:</h6>
                    <table class="table table-sm table-bordered config-table">
                        <tr><td>Enable</td><td><span class="tick">ON</span></td></tr>
                        <tr><td>Enable Domain Name</td><td><span class="tick">ON</span></td></tr>
                        <tr class="highlight"><td>Server Address</td><td><strong>{{ $appDomain ?: 'your-domain.com' }}</strong></td></tr>
                        <tr class="highlight"><td>Server Port</td><td><strong>{{ str_starts_with($appUrl, 'https') ? 443 : 80 }}</strong></td></tr>
                        <tr class="highlight"><td>HTTPS</td><td><span class="tick">{{ str_starts_with($appUrl, 'https') ? 'ON' : 'OFF' }}</span></td></tr>
                        <tr><td>Proxy Server</td><td><span class="cross">OFF</span></td></tr>
                    </table>
                    <div class="note-box">
                        <i class="fas fa-mobile-alt text-warning mr-1"></i>
                        <strong>Device only accepts IP, not domain name?</strong><br>
                        Run <code>nslookup {{ $appDomain ?: 'your-domain.com' }}</code> in Command Prompt to get the server IP. Enter that IP with the same port and HTTPS setting.
                    </div>
                </div>
            </div>

            <hr>
            <h6>Deploying to cPanel / Shared Hosting:</h6>
            <div class="row">
                <div class="col-md-12">
                    <p class="small text-muted">
                        <code>/iclock/getrequest</code> and <code>/iclock/cdata</code> are handled by this app's own
                        routes (<code>IClockController</code>) — there are <strong>no separate PHP files to upload</strong>.
                        Just deploy the whole Laravel project like normal:
                    </p>
                    <ol class="small">
                        <li>Upload the project to your hosting account (e.g. via Git or file manager).</li>
                        <li>Point the domain's document root to the project's <code>public/</code> folder (cPanel: "Application Root"/"Document Root", or use a symlink).</li>
                        <li>Set <code>.env</code> on the server: <code>APP_URL</code> to your real domain, and the <code>DB_*</code> values to your cPanel database.</li>
                        <li>Run <code>composer install --no-dev</code> and <code>php artisan migrate --force</code> on the server.</li>
                    </ol>
                </div>
            </div>

            <div class="warn-box">
                <i class="fas fa-database text-danger mr-1"></i>
                <strong>Important:</strong> Update <code>.env</code> on the hosting server with your real <strong>cPanel database</strong> credentials
                (<code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>) — do not reuse the local XAMPP values.
            </div>

            <div class="info-box">
                <i class="fas fa-exclamation-circle text-info mr-1"></i>
                <strong>Older ZkTeco firmware without HTTPS support?</strong> Try port <strong>80</strong> with HTTPS OFF —
                most shared hosting servers still accept HTTP on port 80 even when the domain has SSL.
                The traffic won't be encrypted but the data will still arrive correctly.
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 6 — How attendance reliably gets from device to DB --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header red">
            <span class="step-num">6</span> How Attendance Data Reliably Gets From Device to Database
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-cloud-upload-alt text-success mr-1"></i>Push Mode (recommended)</h6>
                    <ol class="small">
                        <li>Device buffers every punch in its own memory the instant a finger is scanned — this
                            happens whether or not the server is reachable.</li>
                        <li>Every ~30 seconds the device POSTs new punches to <code>/iclock/cdata</code>.</li>
                        <li>The server saves each one and replies <code>OK:&lt;count&gt;</code>. Only after getting
                            that reply does the device consider those records delivered.</li>
                        <li><strong>If the server or network is down</strong>, the device simply keeps buffering
                            and retries automatically on its next poll — no admin action needed, no data lost
                            (device memory permitting; very old records are pruned per your device's memory model,
                            so don't leave a device offline for weeks at a time).</li>
                    </ol>
                    <div class="success-box mb-0">
                        <i class="fas fa-check-circle text-success mr-1"></i>
                        This is why Push Mode is recommended: delivery is retried by the device itself, automatically,
                        with zero admin intervention.
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-shield-alt text-primary mr-1"></i>Built-in safeguards in this app</h6>
                    <ul class="small">
                        <li><strong>No duplicates:</strong> every attendance row is uniquely keyed by
                            device + punch time + person, so re-delivered or re-pulled punches never create a
                            second row.</li>
                        <li><strong>No wrong-person guesses:</strong> a device PIN is matched only against the
                            exact Student/Teacher number you assigned — never a database row ID — so a teacher
                            numbered as low as <code>1</code> can never be silently attributed to a student
                            record that happens to share that internal ID.</li>
                        <li><strong>Mixed devices are handled carefully:</strong> on a device serving both students
                            and teachers, a PIN that doesn't (yet) match either list is <em>never</em> guessed —
                            it's held as <a href="{{ route('attendance.unmatched') }}">Unmatched Attendance</a> until
                            you classify it. Once you add that PIN as a Student or Teacher, every past punch under
                            it re-attaches automatically.</li>
                        <li><strong>TCP Mode has no such buffer:</strong> since the server pulls from the device
                            on demand, a punch made while nobody clicks "Pull Attendance" simply waits in the
                            device's own memory until the next pull — still not lost, just not live. This is the
                            main reason Push Mode is preferred whenever the device can reach the server over HTTP.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 7 — Push & Pull Reference --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header purple">
            <span class="step-num">7</span> Push &amp; Pull — What Each Button Does
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Button</th>
                            <th>Push Mode (iClock)</th>
                            <th>TCP Mode (Direct)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-user-graduate text-primary mr-1"></i><strong>Push Students</strong></td>
                            <td>Queues <code>DATA UPDATE USERINFO PIN=…</code> commands → device downloads within 30 sec</td>
                            <td>Connects via socket and uploads all students immediately</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-chalkboard-teacher text-info mr-1"></i><strong>Push Teachers</strong></td>
                            <td>Same — queues commands for all teachers</td>
                            <td>Connects via socket and uploads all teachers immediately</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-cloud-download-alt text-success mr-1"></i><strong>Pull Attendance</strong></td>
                            <td>Reports records already in DB (automatically saved when finger scanned)</td>
                            <td>Live fetch all logs from device memory</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-file-import text-purple mr-1"></i><strong>Pull Users (device → DB)</strong></td>
                            <td>N/A — push-mode devices never send a full user list on demand</td>
                            <td>Reads the device's enrolled PIN+name list and creates/updates the matching
                                Student or Teacher in your DB. On a mixed student+teacher device, a brand-new PIN
                                is reported for you to classify rather than guessed.</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-question-circle text-danger mr-1"></i><strong>Unmatched Attendance</strong></td>
                            <td colspan="2">Attendance menu → <a href="{{ route('attendance.unmatched') }}">Unmatched Attendance</a> —
                                punches whose PIN didn't match any Student/Teacher (works the same for both modes).
                                Classify a PIN once and its past punches re-attach automatically.</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-heartbeat text-warning mr-1"></i><strong>Check Heartbeat</strong></td>
                            <td>Shows last_seen_at — online if pinged within <strong>10 minutes</strong></td>
                            <td>N/A — use "Test TCP"</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-wifi text-success mr-1"></i><strong>Test TCP</strong></td>
                            <td>N/A — use "Check Heartbeat"</td>
                            <td>Opens live UDP socket to device IP:Port</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-eraser text-danger mr-1"></i><strong>Clear All Users</strong></td>
                            <td>Queues <code>DATA CLEAR USERINFO</code> → cleared on next poll</td>
                            <td>Immediately clears all users from device memory</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECTION 8 — Live Device Status + Test --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header red">
            <span class="step-num">8</span> Live Device Status — Test Connection Now
        </div>
        <div class="card-body">
            @if($devices->isEmpty())
                <div class="alert alert-warning">No active devices. <a href="{{ route('devices.create') }}">Add a device</a>.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr><th>Name</th><th>Serial</th><th>Mode</th><th>IP / Domain</th><th>Status</th><th>Last Seen</th><th>Test</th></tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td><code>{{ $device->serial_no }}</code></td>
                            <td>
                                @if($device->use_push_mode)
                                    <span class="badge-push">PUSH</span>
                                @else
                                    <span class="badge-tcp">TCP</span>
                                @endif
                            </td>
                            <td>{{ $device->use_push_mode ? ($appDomain ?? 'Push') : ($device->ip_address ?? '—') }}</td>
                            <td>
                                @if($device->use_push_mode)
                                    @if($device->online_status === 'online')
                                        <span class="online-dot"></span><span class="text-success fw-bold">Online</span>
                                    @elseif($device->online_status === 'offline')
                                        <span class="offline-dot"></span><span class="text-danger">Offline</span>
                                    @else
                                        <span class="badge-offline">Never Connected</span>
                                    @endif
                                @else
                                    <span class="text-muted">Test manually →</span>
                                @endif
                            </td>
                            <td class="small">
                                {{ $device->last_seen_at ? $device->last_seen_at->format('d M, H:i') : '—' }}
                                @if($device->last_seen_at)
                                    <br><small class="text-muted">{{ $device->last_seen_at->diffForHumans() }}</small>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning text-white"
                                    onclick="testConn({{ $device->id }}, this)" id="gtest-btn-{{ $device->id }}">
                                    <i class="fas fa-wifi"></i> Test
                                </button>
                                <div id="gtest-result-{{ $device->id }}" class="conn-result mt-1"></div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
function testConn(id, btn) {
    var resultEl = document.getElementById('gtest-result-' + id);
    resultEl.className = 'conn-result loading';
    resultEl.textContent = 'Testing…';
    resultEl.style.display = 'inline-block';
    btn.disabled = true;

    fetch("{{ url('test-connection-json') }}/" + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        resultEl.className = 'conn-result ' + (data.success ? 'ok' : 'fail');
        resultEl.textContent = data.message;
        btn.disabled = false;
    })
    .catch(function() {
        resultEl.className = 'conn-result fail';
        resultEl.textContent = 'Request error.';
        btn.disabled = false;
    });
}
</script>
@endpush
