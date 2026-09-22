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

    /* ── Mode tabs ── */
    .mode-tabs { border-bottom: none; gap: 8px; margin-bottom: 0; }
    .mode-tabs .nav-link {
        border-radius: 10px 10px 0 0; font-weight: 700; padding: 12px 22px;
        color: #64748b; background: #f1f5f9; border: 1.5px solid #e2e8f0; border-bottom: none;
    }
    .mode-tabs .nav-link.active { color: #fff; background: linear-gradient(90deg,#4f46e5,#6366f1); border-color: #4f46e5; }
    .mode-pane { border: 1.5px solid #e0e7ef; border-radius: 0 12px 12px 12px; padding: 22px; background: #fff; }

    /* ── URL test box ── */
    .url-test-box { background:#0f172a; border-radius:10px; padding:14px 18px; }
    .url-test-box code { color:#a5f3fc; word-break: break-all; }
    .url-test-result { font-family:monospace; font-size:.85em; margin-top:10px; padding:10px 14px; border-radius:6px; white-space:pre-wrap; display:none; }
    .url-test-result.ok   { display:block; background:#052e1c; color:#6ee7b7; border:1px solid #059669; }
    .url-test-result.fail { display:block; background:#3f0d0d; color:#fca5a5; border:1px solid #dc2626; }
    .url-test-result.loading { display:block; background:#1e293b; color:#fbbf24; border:1px solid #f59e0b; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="fas fa-book-open mr-2 text-primary"></i>Device Setup Guide</h3>
    <a href="{{ route('devices.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to Devices
    </a>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- INTRO — One standard method --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header indigo">
            <span class="step-num">★</span> One Standard Way to Connect a Device — Push Mode (ADMS)
        </div>
        <div class="card-body">
            <p class="mb-2">Every device — whether you're testing on your laptop or it's installed at the school
                talking to your live server — connects the <strong>same way</strong>: the device calls your server
                over HTTP every ~30 seconds and delivers attendance as it happens. You never configure the device
                differently per building or per environment; only the <strong>Server Address</strong> changes
                between Development and Production below.</p>
            <div class="row">
                <div class="col-md-8">
                    <ul class="mb-0">
                        <li><span class="tick">✓</span> No router port-forwarding, no VPN, no exposing the device to the internet.</li>
                        <li><span class="tick">✓</span> Works the same whether the device is next to the server or in a different building.</li>
                        <li><span class="tick">✓</span> If the server is briefly unreachable, the device just keeps its data and retries — nothing is lost.</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <div class="note-box mb-0">
                        <i class="fas fa-info-circle text-warning mr-1"></i>
                        Direct TCP/UDP mode still exists for same-LAN testing only (used by the per-device
                        Test/Pull buttons later on this page) — it's not what you configure on the device itself
                        for day-to-day attendance capture.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- STEP 1 — Ethernet --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header green">
            <span class="step-num">1</span> Step 1 — Device Network Settings (Do This First, Any Environment)
        </div>
        <div class="card-body">
            <p class="text-muted small">The device needs a working network connection before it can reach any server.</p>
            <p><span class="menu-path">MENU → COMM → Ethernet</span> &nbsp; (some models: <span class="menu-path">MENU → COMM → Network</span>)</p>

            @php $exampleIp = $devices->first()->ip_address ?? '192.168.0.201'; @endphp
            <div class="warn-box">
                <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                <strong>The screen and IP below are a made-up example</strong> to show which field is which — don't
                type <code>{{ $exampleIp }}</code> anywhere unless it's genuinely what's already on your device's own
                screen. Whatever your device currently shows as its IP address is the real one; if you're not sure,
                check the device's own Ethernet menu directly, not this page.
            </div>

            <div class="row mt-2">
                <div class="col-md-5">
                    <div class="device-screen">
                        <div>[ Ethernet Settings ]</div>
                        <div class="active-item">&gt; IP Address    {{ $exampleIp }}</div>
                        <div>&nbsp; Subnet Mask  255.255.255.0</div>
                        <div>&nbsp; Gateway      192.168.0.1</div>
                        <div>&nbsp; DNS Server   8.8.8.8</div>
                        <div>&nbsp; DHCP         Off</div>
                        <div class="cursor">_</div>
                    </div>
                    <p class="text-muted small mt-2 text-center">Illustration only — not a value to copy</p>
                </div>
                <div class="col-md-7">
                    <table class="table table-sm table-bordered config-table">
                        <thead class="table-dark"><tr><th>Setting</th><th>What to Enter</th></tr></thead>
                        <tbody>
                            <tr><td>IP Address</td><td>A <strong>fixed IP</strong> already on your device's own screen — pick any free address on your LAN if you're assigning one for the first time. This is the device's own address, and is <strong>not</strong> the same as your PC's/server's address used later in this guide.</td></tr>
                            <tr><td>Subnet Mask</td><td><code>255.255.255.0</code></td></tr>
                            <tr class="highlight"><td>Gateway ⚠️</td><td>Your router's IP, e.g. <code>192.168.0.1</code><br><small class="text-danger">Must be correct — without it the device can't reach anything outside its own subnet.</small></td></tr>
                            <tr><td>DNS</td><td><code>8.8.8.8</code></td></tr>
                            <tr><td>DHCP</td><td><span class="cross">OFF</span> — a fixed IP keeps working after a reboot</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- STEP 2 — Cloud Server basics --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header blue">
            <span class="step-num">2</span> Step 2 — Find the Cloud Server / ADMS Menu
        </div>
        <div class="card-body">
            <p><span class="menu-path">MENU → COMM → Cloud Server</span> &nbsp; (some models: <span class="menu-path">MENU → COMM → ADMS</span> or <span class="menu-path">Server</span>)</p>
            <div class="info-box">
                <i class="fas fa-info-circle text-info mr-1"></i>
                <strong>Firmware varies — you may not see every field below, and that's fine.</strong> Some
                devices show a separate <code>Enable: ON/OFF</code> switch next to a fixed "ADMS" label. Others
                (yours included) only show <code>Server Mode: [Disable / ADMS]</code> — picking <strong>ADMS</strong>
                there <em>is</em> the enable switch; there's nothing else to turn on. Same with "Enable Domain
                Name": if your device doesn't have that field at all, it simply always expects an IP address in
                Server Address, which is exactly what the tabs below give you.
            </div>
            <table class="table table-sm table-bordered config-table mb-0">
                <thead class="table-dark"><tr><th>Setting</th><th>What it means</th></tr></thead>
                <tbody>
                    <tr><td>Enable / Server Mode</td><td>Whichever form your firmware has, the result must be: push mode is turned on (e.g. <code>Server Mode: ADMS</code>, not <code>Disable</code>)</td></tr>
                    <tr><td>Enable Domain Name</td><td>If present: <span class="tick">ON</span> lets you type a domain instead of an IP. If absent, just type an IP in Server Address — see the tabs below.</td></tr>
                    <tr class="highlight"><td>Server Address / Port</td><td>Exact values depend on Development vs Production — see below</td></tr>
                    <tr><td>Proxy Server</td><td><span class="cross">Always OFF</span> — never <code>0.0.0.0</code>, it blocks the connection</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- MODE TABS: Development / Production --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <ul class="nav mode-tabs" id="modeTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="dev-tab" data-toggle="tab" href="#devPane" role="tab">
                <i class="fas fa-laptop-code mr-1"></i> Development (Local)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="prod-tab" data-toggle="tab" href="#prodPane" role="tab">
                <i class="fas fa-server mr-1"></i> Production (VPS)
            </a>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ═══ DEVELOPMENT ═══ --}}
        <div class="tab-pane fade show active mode-pane" id="devPane" role="tabpanel">
            <div class="info-box">
                <i class="fas fa-terminal text-info mr-1"></i>
                <strong>How you run this app locally</strong> — from the project folder:
                <div class="config-box mt-2 mb-0">php artisan serve --host={{ $localIp }} --port={{ $appPort }}</div>
                <span class="small text-muted">Binding to your LAN IP (not <code>127.0.0.1</code>) is what lets the
                device — a separate physical machine — reach this server. Keep this terminal window open; the app
                is only reachable while it's running.</span>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <h6><i class="fas fa-microchip mr-1"></i>Cloud Server Settings on Device</h6>
                    <table class="table table-sm table-bordered config-table">
                        <tr><td>Enable Domain Name</td><td><span class="cross">OFF</span> (or leave)</td></tr>
                        <tr class="highlight"><td>Server Address</td><td><strong>{{ $localIp }}</strong></td></tr>
                        <tr class="highlight"><td>Server Port</td><td><strong>{{ $appPort }}</strong></td></tr>
                        <tr><td>HTTPS</td><td><span class="cross">OFF</span></td></tr>
                    </table>

                    <h6 class="mt-3"><i class="fas fa-shield-alt mr-1"></i>Windows Firewall (one-time)</h6>
                    <p class="small text-muted mb-1">Run in <strong>PowerShell as Administrator</strong> — this is the #1 reason a device "won't connect" locally:</p>
                    <div class="config-box small">New-NetFirewallRule -DisplayName "ZkTeco {{ $appPort }}" -Direction Inbound -Protocol TCP -LocalPort {{ $appPort }} -Action Allow -Profile Any</div>
                    <p class="small text-muted mt-2 mb-0">Ping succeeding does <strong>not</strong> mean this port is open — Windows Firewall filters ping and TCP ports separately.</p>
                </div>

                <div class="col-md-6">
                    <h6><i class="fas fa-satellite-dish mr-1"></i>Test This URL — No Manual Browsing Needed</h6>
                    <p class="small text-muted">This is exactly what the device calls every 30 seconds. Click to test it right now instead of pasting it into a browser tab.</p>
                    <div class="url-test-box">
                        <code id="devTestUrl">http://{{ $localIp }}:{{ $appPort }}/iclock/getrequest</code>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-warning text-white" onclick="testIclockUrl(document.getElementById('devTestUrl').textContent, 'devTestResult', this)">
                                <i class="fas fa-play mr-1"></i> Test Now
                            </button>
                        </div>
                        <div id="devTestResult" class="url-test-result"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        This button tests from <em>your browser's</em> network position. If it succeeds here but the
                        device still can't connect, the problem is almost always the firewall rule above (your
                        browser is on this same PC, so it never has to cross that rule — the device does).
                    </p>
                </div>
            </div>

            <hr>
            <h6><i class="fas fa-question-circle text-warning mr-1"></i>Still Not Connecting? Check in This Order</h6>
            <ol class="small mb-0">
                <li><code>php artisan serve --host={{ $localIp }} --port={{ $appPort }}</code> must be running right now — it stops the moment that terminal window closes.</li>
                <li>Click <strong>Test Now</strong> above — if it fails, the app itself isn't reachable even from this PC; fix that first.</li>
                <li>If Test Now succeeds but the device still shows offline: run the firewall command above <strong>as Administrator</strong> — a normal (non-admin) window silently fails.</li>
                <li>Device Gateway (<span class="menu-path">Ethernet → Gateway</span>) must be your router's IP.</li>
                <li>After saving settings on the device, it reconnects within ~30–60 seconds — wait, then check the Live Device Status table further down this page.</li>
                <li><strong>Using TCP/Pull buttons and getting a timeout?</strong> Ping the device's IP first. If ping works but the app still can't connect on port 4370, the device's own menu likely has TCP/IP communication disabled while ADMS/Cloud Server mode is on — some firmware only speaks one protocol at a time.</li>
            </ol>
        </div>

        {{-- ═══ PRODUCTION ═══ --}}
        <div class="tab-pane fade mode-pane" id="prodPane" role="tabpanel">
            <div class="warn-box">
                <i class="fas fa-network-wired text-danger mr-1"></i>
                <strong>Use a VPS, not shared hosting.</strong> Shared hosting puts many customer sites behind one
                IP, routed by domain name — a device that can't send the exact right domain (many can't) gets your
                host's generic error page instead of your app, and looks like "not connecting." A VPS gives you a
                <strong>dedicated IP</strong>, so this problem doesn't exist: the bare IP reaches your app directly,
                no domain lookup required.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-microchip mr-1"></i>Cloud Server Settings on Device</h6>
                    <table class="table table-sm table-bordered config-table">
                        <tr><td>Server Address</td><td><strong>yourdomain.com</strong> <span class="text-muted">or the VPS's IP — both work</span></td></tr>
                        <tr class="highlight"><td>Server Port</td><td><strong>80</strong> <span class="text-muted">(or 443 only if the device genuinely supports HTTPS)</span></td></tr>
                        <tr><td>HTTPS</td><td>ON only if the device has real HTTPS support</td></tr>
                    </table>
                    <div class="info-box mb-0">
                        <i class="fas fa-mobile-alt text-info mr-1"></i>
                        No "domain name" field on your firmware? Run <code>nslookup yourdomain.com</code> and enter
                        the IP it returns instead — same port, same result.
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-satellite-dish mr-1"></i>Test Your Live Endpoint</h6>
                    <p class="small text-muted">Once deployed, test the exact URL the device will call:</p>
                    <div class="url-test-box">
                        <input type="text" id="prodTestUrl" class="form-control form-control-sm mb-2"
                               value="{{ rtrim($appUrl, '/') }}/iclock/getrequest"
                               style="background:#1e293b;color:#a5f3fc;border-color:#334155;">
                        <button type="button" class="btn btn-sm btn-warning text-white" onclick="testIclockUrl(document.getElementById('prodTestUrl').value, 'prodTestResult', this)">
                            <i class="fas fa-play mr-1"></i> Test Now
                        </button>
                        <div id="prodTestResult" class="url-test-result"></div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">The URL is pre-filled from this app's own current address
                        — replace it if you're checking from a different environment than the one serving this page.</p>
                </div>
            </div>

            <hr>
            <h6><i class="fas fa-list-ol mr-1"></i>Deploying to a VPS</h6>
            <p class="small text-muted mb-1">Any provider works (DigitalOcean, Vultr, Linode, Hetzner, a local BD provider). Ubuntu 22.04 LTS, 1–2 GB RAM is plenty.</p>
            <div class="config-box small">
sudo apt update && sudo apt upgrade -y<br>
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-cli php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-sockets unzip git<br>
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
            </div>
            <div class="warn-box mt-2">
                <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                <strong><code>php8.1-sockets</code> is not optional.</strong> Without it, TCP-mode actions (Test
                Connection, Pull Attendance, Push/Pull Teachers &amp; Students against a same-LAN device) silently
                report "sockets not available." Push Mode doesn't need it, but install it anyway.
            </div>

            <div class="config-box small">
sudo mysql -e "CREATE DATABASE att_management CHARACTER SET utf8mb4;"<br>
sudo mysql -e "CREATE USER 'att_user'@'localhost' IDENTIFIED BY 'a-strong-password';"<br>
sudo mysql -e "GRANT ALL PRIVILEGES ON att_management.* TO 'att_user'@'localhost'; FLUSH PRIVILEGES;"<br>
<br>
cd /var/www && sudo git clone &lt;your-repo-url&gt; att_management && cd att_management<br>
composer install --no-dev --optimize-autoloader<br>
cp .env.example .env && php artisan key:generate<br>
ln -s ../assets public/assets<br>
sudo chown -R www-data:www-data storage bootstrap/cache assets<br>
sudo chmod -R 775 storage bootstrap/cache assets
            </div>
            <p class="small text-muted">Edit <code>.env</code>: <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>,
                <code>APP_URL=https://yourdomain.com</code> (no port), and the DB credentials above. Then
                <code>php artisan migrate --force</code>.</p>

            <h6 class="mt-3">Nginx (document root = <code>public/</code>, standard Laravel)</h6>
            <div class="config-box small">
server {'{'}<br>
&nbsp;&nbsp;listen 80;<br>
&nbsp;&nbsp;server_name yourdomain.com;<br>
&nbsp;&nbsp;root /var/www/att_management/public;<br>
&nbsp;&nbsp;index index.php;<br><br>
&nbsp;&nbsp;location / {'{'} try_files $uri $uri/ /index.php?$query_string; {'}'}<br>
&nbsp;&nbsp;location ~ \.php$ {'{'}<br>
&nbsp;&nbsp;&nbsp;&nbsp;include snippets/fastcgi-php.conf;<br>
&nbsp;&nbsp;&nbsp;&nbsp;fastcgi_pass unix:/run/php/php8.1-fpm.sock;<br>
&nbsp;&nbsp;{'}'}<br>
&nbsp;&nbsp;location ~ /\.(?!well-known).* {'{'} deny all; {'}'}<br>
{'}'}
            </div>
            <p class="small text-muted">Prefer Apache? Same idea: <code>DocumentRoot</code> → <code>public/</code>, <code>AllowOverride All</code>.</p>

            <div class="row">
                <div class="col-md-6">
                    <h6>Free SSL (Let's Encrypt)</h6>
                    <div class="config-box small">sudo apt install -y certbot python3-certbot-nginx<br>sudo certbot --nginx -d yourdomain.com</div>
                    <p class="small text-muted mb-0">Auto-renews. Only matters if your device genuinely supports HTTPS.</p>
                </div>
                <div class="col-md-6">
                    <h6>Firewall</h6>
                    <div class="config-box small">sudo ufw allow OpenSSH<br>sudo ufw allow 'Nginx Full'<br>sudo ufw enable</div>
                </div>
            </div>

            <div class="info-box mb-0">
                <i class="fas fa-info-circle text-info mr-1"></i>
                <strong>No cron/queue setup needed</strong> — this app runs everything synchronously, no background
                workers. <strong>Do</strong> set up nightly backups: <code>mysqldump</code> of the database plus the
                <code>assets/files/</code> folder (uploaded teacher/student photos live there, outside git).
            </div>

            <hr>
            <h6><i class="fas fa-sitemap mr-1"></i>Multiple Buildings / Different Subnets in Production</h6>
            <p class="small text-muted">
                Every device — no matter which building it's in — points at the <strong>same</strong> Server
                Address/Port (your VPS). Only each device's own <strong>Gateway</strong> setting changes, so its
                local router knows how to route back to your server.
            </p>
            <div class="subnet-diagram mb-3">
<span class="hl">Your VPS</span>  <span class="ip">yourdomain.com</span><br>
│<br>
├── Building A  Device IP: <span class="ip">192.168.0.201</span> <span class="arrow">→</span> Gateway <span class="ip">192.168.0.1</span> <span class="arrow">→</span> Internet <span class="arrow">→</span> VPS<br>
├── Building B  Device IP: <span class="ip">192.168.1.201</span> <span class="arrow">→</span> Gateway <span class="ip">192.168.1.1</span> <span class="arrow">→</span> Internet <span class="arrow">→</span> VPS<br>
└── Building C  Device IP: <span class="ip">192.168.2.201</span> <span class="arrow">→</span> Gateway <span class="ip">192.168.2.1</span> <span class="arrow">→</span> Internet <span class="arrow">→</span> VPS
            </div>
            <div class="info-box mb-3">
                <i class="fas fa-lightbulb text-info mr-1"></i>
                Unlike a local multi-building LAN, each building's internet router almost always already knows how
                to reach the internet by default — there's usually no special inter-VLAN routing to configure, since
                every building is really just talking to the internet, not to each other.
            </div>

            <h6>Your Devices — Subnet Worksheet</h6>
            <p class="text-muted small">Fill in <strong>Subnet / Building Label</strong>, <strong>Gateway IP</strong> and
                <strong>Location Note</strong> on each device's edit page — this table stays accurate as you add devices.</p>
            @if($devices->isEmpty())
                <div class="alert alert-warning mb-0">No active devices yet. <a href="{{ route('devices.create') }}">Add your first device</a>.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered config-table mb-0">
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
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- How attendance reliably gets from device to DB --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header red">
            <span class="step-num"><i class="fas fa-database"></i></span> How Attendance Data Gets Into Your Database
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-cloud-upload-alt text-success mr-1"></i>What happens on every scan</h6>
                    <ol class="small">
                        <li>The device saves the punch in its own memory the instant a finger is scanned — server reachable or not.</li>
                        <li>Every ~30 seconds it sends new punches to <code>/iclock/cdata</code>.</li>
                        <li>The server saves each one and replies <code>OK:&lt;count&gt;</code>. Only then does the device consider them delivered.</li>
                        <li><strong>If the server is briefly down</strong>, the device just keeps buffering and retries next poll — nothing is lost.</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-shield-alt text-primary mr-1"></i>Built-in safeguards</h6>
                    <ul class="small mb-0">
                        <li><strong>No duplicates:</strong> every row is uniquely keyed by device + time + person.</li>
                        <li><strong>No wrong-person guesses:</strong> a device PIN is matched only against the exact
                            Student/Teacher number you assigned — never an internal database ID.</li>
                        <li><strong>Unrecognized PINs are never guessed</strong> — they land in
                            <a href="{{ route('attendance.unmatched') }}">Unmatched Attendance</a> until you classify
                            them; past punches re-attach automatically once you do.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- Push & Pull Reference --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header purple">
            <span class="step-num"><i class="fas fa-exchange-alt"></i></span> What Each Button Does
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-dark">
                        <tr><th>Button</th><th>What it does</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-user-graduate text-primary mr-1"></i><strong>Push Students / Teachers</strong></td>
                            <td>Sends your Student/Teacher list to the device so they can scan. Queued for the device to pick up on its next poll (~30s).</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-cloud-download-alt text-success mr-1"></i><strong>Pull Attendance</strong></td>
                            <td>Shows attendance already saved for this device — it's captured automatically the moment someone scans; this doesn't wait for a click.</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-file-import text-purple mr-1"></i><strong>Pull Users (device → DB)</strong></td>
                            <td>Reads who's already enrolled on the device and creates/updates matching records in your DB. On a device shared by students and teachers, a brand-new PIN is listed for you to classify rather than guessed.</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-question-circle text-danger mr-1"></i><strong>Unmatched Attendance</strong></td>
                            <td>Attendance menu → <a href="{{ route('attendance.unmatched') }}">Unmatched Attendance</a> — punches whose PIN didn't match anyone yet. Classify once, past punches re-attach automatically.</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-heartbeat text-warning mr-1"></i><strong>Test / Check Heartbeat</strong></td>
                            <td>Confirms the device has actually contacted this server recently (see Live Device Status below).</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-eraser text-danger mr-1"></i><strong>Clear All Users</strong></td>
                            <td>Removes every enrolled user from the device — queued for its next poll.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- Live Device Status + Test --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="guide-section">
    <div class="guide-card card">
        <div class="card-header red">
            <span class="step-num"><i class="fas fa-heartbeat"></i></span> Live Device Status — Test Connection Now
        </div>
        <div class="card-body">
            @if($devices->isEmpty())
                <div class="alert alert-warning">No active devices. <a href="{{ route('devices.create') }}">Add a device</a>.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                        <tr><th>Name</th><th>Serial</th><th>Server Address</th><th>Status</th><th>Last Seen</th><th>Test</th></tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td><code>{{ $device->serial_no }}</code></td>
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

/**
 * Tests an /iclock/getrequest URL directly from the browser — this is
 * exactly the request a device makes, minus the SN= parameter. Replaces
 * "paste this into your browser" with a one-click check.
 */
function testIclockUrl(url, resultElId, btn) {
    var resultEl = document.getElementById(resultElId);
    resultEl.className = 'url-test-result loading';
    resultEl.textContent = 'Testing ' + url + ' …';
    btn.disabled = true;

    var testUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'SN=GUIDE-TEST';

    fetch(testUrl, { cache: 'no-store' })
        .then(function (r) {
            return r.text().then(function (text) { return { status: r.status, text: text }; });
        })
        .then(function (res) {
            if (res.status === 200 && res.text.indexOf('OK') !== -1) {
                resultEl.className = 'url-test-result ok';
                resultEl.textContent = '✓ Reached the server — got back:\n' + res.text.trim();
            } else {
                resultEl.className = 'url-test-result fail';
                resultEl.textContent = '✗ Server responded but not as expected (HTTP ' + res.status + '):\n' + res.text.substring(0, 300);
            }
            btn.disabled = false;
        })
        .catch(function (err) {
            resultEl.className = 'url-test-result fail';
            resultEl.textContent = '✗ Could not reach this URL at all from your browser.\nThis usually means: the server isn\'t running, the port is wrong, or a firewall is blocking it.\n(' + err.message + ')';
            btn.disabled = false;
        });
}
</script>
@endpush
