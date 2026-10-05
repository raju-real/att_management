@extends('layouts.app')
@section('title', 'Dashboard')

@push('css')
<style>
/* ═══════════════════════════════════════════════
   PREMIUM DASHBOARD DESIGN
═══════════════════════════════════════════════ */

/* ── Stat Cards ── */
.dash-stat-card {
    border-radius: 18px;
    padding: 22px 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    border: none;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    transition: transform .25s ease, box-shadow .25s ease;
    min-height: 130px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.dash-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.18);
}
.dash-stat-card .card-glow {
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    top: -60px; right: -60px;
    background: rgba(255,255,255,0.12);
    pointer-events: none;
}
.dash-stat-card .card-glow-2 {
    position: absolute;
    width: 100px; height: 100px;
    border-radius: 50%;
    bottom: -40px; left: 20px;
    background: rgba(255,255,255,0.07);
    pointer-events: none;
}
.dash-stat-card .stat-label {
    font-size: 11px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    opacity: .85;
}
.dash-stat-card .stat-value {
    font-size: 44px; font-weight: 800; line-height: 1;
    margin: 6px 0 4px;
    font-variant-numeric: tabular-nums;
}
.dash-stat-card .stat-sub {
    font-size: 12px; opacity: .78;
    display: flex; align-items: center; gap: 5px;
}
.dash-stat-card .stat-icon {
    position: absolute; right: 22px; bottom: 18px;
    font-size: 56px; opacity: .15;
}
.stat-card-blue   { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
.stat-card-green  { background: linear-gradient(135deg, #059669 0%, #0891b2 100%); }
.stat-card-amber  { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
.stat-card-rose   { background: linear-gradient(135deg, #dc2626 0%, #e11d48 100%); }

/* ── Section Headers ── */
.section-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(90deg,rgba(99,102,241,.12),rgba(139,92,246,.08));
    border: 1px solid rgba(99,102,241,.2);
    border-radius: 30px; padding: 5px 14px; font-size: 13px;
    font-weight: 700; color: #4f46e5; margin-bottom: 16px;
}

/* ═══════════════════════════════════════════════
   TEACHER STATUS BOARD (sliding)
═══════════════════════════════════════════════ */
.tb-board {
    --tb-bg1: #0b1026; --tb-bg2: #1e1b4b; --tb-gold: #f5c76b;
    --tb-green: #10b981; --tb-red: #ef4444; --tb-gray: #94a3b8;
    position: relative; border-radius: 22px; overflow: hidden; color: #e2e8f0;
    background: radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.35), transparent 60%),
                radial-gradient(900px 400px at 110% 120%, rgba(245,199,107,.18), transparent 60%),
                linear-gradient(135deg, var(--tb-bg1), var(--tb-bg2));
    box-shadow: 0 20px 60px rgba(15,23,42,.35);
    margin-bottom: 28px;
}
.tb-board:fullscreen { border-radius: 0; display: flex; flex-direction: column; }
.tb-board:fullscreen .tb-viewport { flex: 1; }
.tb-head { padding: 22px 26px 12px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
.tb-title { font-size: 21px; font-weight: 700; letter-spacing: .3px; color: #fff; margin: 0; }
.tb-title i { color: var(--tb-gold); }
.tb-date { font-size: 13px; color: #a5b4fc; margin-top: 2px; }
.tb-clock { font-size: 30px; font-weight: 700; font-variant-numeric: tabular-nums; color: #fff; letter-spacing: 1px; line-height: 1; }
.tb-clock small { font-size: 13px; color: var(--tb-gold); margin-left: 4px; }
.tb-live { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #6ee7b7; text-transform: uppercase; letter-spacing: 1px; }
.tb-live::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,.7); animation: tb-pulse 1.8s infinite; }
@keyframes tb-pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,.6); } 70% { box-shadow: 0 0 0 9px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }

.tb-bar { padding: 0 26px 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
.tb-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.tb-tab { cursor: pointer; user-select: none; border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06);
    color: #cbd5e1; border-radius: 12px; padding: 7px 14px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all .2s; }
.tb-tab b { font-size: 16px; color: #fff; font-variant-numeric: tabular-nums; }
.tb-tab:hover { background: rgba(255,255,255,.12); }
.tb-tab.active { background: #fff; color: #1e1b4b; border-color: #fff; }
.tb-tab.active b { color: #1e1b4b; }
.tb-tab .dot { width: 8px; height: 8px; border-radius: 50%; }
.tb-controls { display: flex; gap: 6px; align-items: center; }
.tb-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.07);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; }
.tb-btn:hover { background: rgba(255,255,255,.18); }
.tb-date-input { width: 130px; height: 36px; border-radius: 10px; border: 1px solid rgba(255,255,255,.15) !important;
    background: rgba(255,255,255,.07) !important; color: #fff !important; font-size: 13px; padding: 0 10px; }

.tb-viewport { position: relative; overflow: hidden; padding: 6px 0 18px; min-height: 260px; }
.tb-track { display: flex; transition: transform .8s cubic-bezier(.65,.05,.25,1); will-change: transform; }
.tb-slide { flex: 0 0 100%; padding: 0 26px; display: grid; gap: 14px; align-content: start; }

.tb-card { position: relative; border-radius: 16px; padding: 14px 10px 12px; text-align: center;
    background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
    border: 1px solid rgba(255,255,255,.10); backdrop-filter: blur(6px);
    opacity: 0; transform: translateY(14px) scale(.98); transition: transform .25s, box-shadow .25s, border-color .25s; }
.tb-slide.is-active .tb-card { animation: tb-in .55s cubic-bezier(.2,.7,.3,1) forwards; }
@keyframes tb-in { to { opacity: 1; transform: none; } }
.tb-card:hover { transform: translateY(-4px) !important; box-shadow: 0 14px 30px rgba(0,0,0,.35); border-color: rgba(255,255,255,.25); }
.tb-card::after { content: ''; position: absolute; left: 16px; right: 16px; top: 0; height: 3px; border-radius: 0 0 4px 4px; }
.tb-card.on_time::after { background: var(--tb-green); }
.tb-card.late::after    { background: var(--tb-red); }
.tb-card.absent::after  { background: #475569; }
.tb-card.late { background: linear-gradient(180deg, rgba(239,68,68,.20), rgba(239,68,68,.06)); border-color: rgba(239,68,68,.35); }

.tb-photo { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 9px; position: relative; padding: 3px;
    background: conic-gradient(from 210deg, var(--ring1), var(--ring2), var(--ring1)); }
.tb-card.on_time .tb-photo { --ring1: #10b981; --ring2: #6ee7b7; }
.tb-card.late .tb-photo    { --ring1: #ef4444; --ring2: #fca5a5; }
.tb-card.absent .tb-photo  { --ring1: #475569; --ring2: #94a3b8; }
.tb-photo img, .tb-photo .ph { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #0f172a; display: block; }
.tb-photo .ph { display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, #4f46e5, #7c3aed); }
.tb-card.absent .tb-photo img { filter: grayscale(1); opacity: .65; }
.tb-name { font-size: 13.5px; font-weight: 700; color: #fff; line-height: 1.25; min-height: 34px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tb-no { display: inline-block; margin-top: 4px; font-family: SFMono-Regular, Consolas, monospace; font-size: 11px;
    color: var(--tb-gold); background: rgba(245,199,107,.12); border: 1px solid rgba(245,199,107,.3); padding: 0 8px; border-radius: 6px; }
.tb-dept { font-size: 11px; color: #94a3b8; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tb-time { margin-top: 8px; font-size: 19px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .5px; line-height: 1.1; }
.tb-card.on_time .tb-time { color: #34d399; }
.tb-card.late .tb-time    { color: #f87171; }
.tb-card.absent .tb-time  { color: #64748b; font-size: 14px; letter-spacing: 2px; text-transform: uppercase; }
.tb-tag { display: inline-block; margin-top: 5px; font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; letter-spacing: .3px; }
.tb-card.on_time .tb-tag { background: rgba(16,185,129,.16); color: #6ee7b7; }
.tb-card.late .tb-tag    { background: #ef4444; color: #fff; }
.tb-card.absent .tb-tag  { background: rgba(148,163,184,.15); color: #94a3b8; }

.tb-foot { display: flex; align-items: center; justify-content: space-between; padding: 0 26px 18px; gap: 12px; }
.tb-dots { display: flex; gap: 6px; flex-wrap: wrap; }
.tb-dot { width: 8px; height: 8px; border-radius: 8px; background: rgba(255,255,255,.25); cursor: pointer; transition: all .3s; border: 0; padding: 0; }
.tb-dot.active { width: 26px; background: var(--tb-gold); }
.tb-page { font-size: 12px; color: #a5b4fc; font-variant-numeric: tabular-nums; white-space: nowrap; }
.tb-progress { position: absolute; left: 0; bottom: 0; height: 3px; width: 0; background: linear-gradient(90deg, var(--tb-gold), #f59e0b); }
.tb-empty { padding: 50px 20px; text-align: center; color: #94a3b8; }
.tb-empty i { font-size: 40px; display: block; margin-bottom: 12px; color: var(--tb-gold); opacity: .8; }
.tb-loader { position: absolute; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(11,16,38,.55); z-index: 5; }
.tb-loader.show { display: flex; }
.tb-spin { width: 36px; height: 36px; border-radius: 50%; border: 4px solid rgba(255,255,255,.15); border-top-color: var(--tb-gold); animation: tb-spin .8s linear infinite; }
@keyframes tb-spin { to { transform: rotate(360deg); } }
@media (max-width: 575px) {
    .tb-head, .tb-bar, .tb-foot { padding-left: 14px; padding-right: 14px; }
    .tb-slide { padding: 0 14px; gap: 10px; }
    .tb-clock { font-size: 22px; }
    .tb-photo { width: 60px; height: 60px; }
}
@media (prefers-reduced-motion: reduce) {
    .tb-track { transition: none; }
    .tb-slide.is-active .tb-card { animation: none; opacity: 1; transform: none; }
}

/* ── Today Attendance Table ── */
.att-table thead th {
    background: linear-gradient(90deg,#4f46e5,#6366f1);
    color: #fff; font-size: 12px; font-weight: 700;
    letter-spacing: .5px; padding: 10px 12px; border: none;
}
.att-table tbody tr { transition: background .12s ease; }
.att-table tbody tr:hover td { background: rgba(99,102,241,.05); }
.att-table td { padding: 9px 12px; font-size: .88em; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.att-badge-student { background:#ede9fe; color:#7c3aed; font-size:.75em; padding:2px 8px; border-radius:10px; font-weight:600; }
.att-badge-teacher { background:#d1fae5; color:#065f46; font-size:.75em; padding:2px 8px; border-radius:10px; font-weight:600; }
.att-badge-other   { background:#f1f5f9; color:#64748b; font-size:.75em; padding:2px 8px; border-radius:10px; font-weight:600; }
.att-id { font-family:monospace; font-size:.82em; color:#6366f1; background:#eef2ff; padding:1px 6px; border-radius:4px; }
.att-late    { color: #dc2626; font-weight: 700; }
.att-normal  { color: #059669; font-weight: 600; }
.att-punch   { font-weight:600; color:#4f46e5; }
</style>
@endpush

@section('content')


    {{-- ═══════════════════════════════════════════
         STAT CARDS ROW
    ═══════════════════════════════════════════ --}}
    <div class="row mb-4">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dash-stat-card stat-card-blue">
                <div class="card-glow"></div><div class="card-glow-2"></div>
                <div class="stat-label"><i class="fas fa-users mr-1"></i>Total Students</div>
                <div class="stat-value">{{ number_format($total_students ?? 0) }}</div>
                <div class="stat-sub"><i class="fas fa-circle" style="font-size:7px;color:#a5b4fc"></i> Active Students</div>
                <i class="fas fa-user-graduate stat-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dash-stat-card stat-card-green">
                <div class="card-glow"></div><div class="card-glow-2"></div>
                <div class="stat-label"><i class="fas fa-chalkboard-teacher mr-1"></i>Total Teachers</div>
                <div class="stat-value">{{ number_format($total_teachers ?? 0) }}</div>
                <div class="stat-sub"><i class="fas fa-circle" style="font-size:7px;color:#6ee7b7"></i> Active Teachers</div>
                <i class="fas fa-chalkboard-teacher stat-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dash-stat-card stat-card-amber">
                <div class="card-glow"></div><div class="card-glow-2"></div>
                <div class="stat-label"><i class="fas fa-fingerprint mr-1"></i>Active Devices</div>
                <div class="stat-value">{{ number_format($total_devices ?? 0) }}</div>
                <div class="stat-sub"><i class="fas fa-circle" style="font-size:7px;color:#fde68a"></i> ZkTeco Devices</div>
                <i class="fas fa-microchip stat-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dash-stat-card stat-card-rose">
                <div class="card-glow"></div><div class="card-glow-2"></div>
                <div class="stat-label"><i class="fas fa-check-circle mr-1"></i>Today Present</div>
                <div class="stat-value">{{ number_format($today_present ?? 0) }}</div>
                <div class="stat-sub"><i class="fas fa-circle" style="font-size:7px;color:#fda4af"></i> Unique Persons Today</div>
                <i class="fas fa-user-check stat-icon"></i>
            </div>
        </div>

    </div>


    {{-- ═══════════════════════════════════════════
         TEACHER STATUS BOARD (auto-sliding)
    ═══════════════════════════════════════════ --}}
    <div class="tb-board" id="tbBoard">
        <div class="tb-head">
            <div>
                <h4 class="tb-title"><i class="fas fa-crown mr-2"></i>Teacher Attendance Board</h4>
                <div class="tb-date"><span id="tbDateLabel">{{ \Carbon\Carbon::today()->format('l, d M Y') }}</span>
                    <span class="tb-live ml-2" id="tbLive">Live</span></div>
            </div>
            <div class="text-right">
                <div class="tb-clock" id="tbClock">--:--<small>--</small></div>
            </div>
        </div>

        <div class="tb-bar">
            <div class="tb-tabs" id="tbTabs">
                <div class="tb-tab active" data-filter="all"><span class="dot" style="background:#a5b4fc"></span>All <b id="tbCntAll">0</b></div>
                <div class="tb-tab" data-filter="present"><span class="dot" style="background:#60a5fa"></span>Present <b id="tbCntPresent">0</b></div>
                <div class="tb-tab" data-filter="on_time"><span class="dot" style="background:#10b981"></span>On Time <b id="tbCntOnTime">0</b></div>
                <div class="tb-tab" data-filter="late"><span class="dot" style="background:#ef4444"></span>Late <b id="tbCntLate">0</b></div>
                <div class="tb-tab" data-filter="absent"><span class="dot" style="background:#64748b"></span>Absent <b id="tbCntAbsent">0</b></div>
            </div>
            <div class="tb-controls">
                <input type="text" id="tbDate" class="tb-date-input flat_datepicker" value="{{ \Carbon\Carbon::today()->toDateString() }}" readonly>
                <button type="button" class="tb-btn" id="tbPrev" title="Previous"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="tb-btn" id="tbPlay" title="Pause"><i class="fas fa-pause"></i></button>
                <button type="button" class="tb-btn" id="tbNext" title="Next"><i class="fas fa-chevron-right"></i></button>
                <button type="button" class="tb-btn" id="tbFull" title="Full screen"><i class="fas fa-expand"></i></button>
            </div>
        </div>

        <div class="tb-viewport" id="tbViewport">
            <div class="tb-loader show" id="tbLoader"><div class="tb-spin"></div></div>
            <div class="tb-track" id="tbTrack"></div>
        </div>

        <div class="tb-foot">
            <div class="tb-dots" id="tbDots"></div>
            <div class="tb-page" id="tbPage"></div>
        </div>
        <div class="tb-progress" id="tbProgress"></div>
    </div>

    {{-- ═══════════════════════════════════════════
         TODAY'S ATTENDANCE (latest 10)
    ═══════════════════════════════════════════ --}}
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-alt mr-2"></i>Today's Attendance
                <small class="text-muted ml-1" style="font-size:13px">{{ \Carbon\Carbon::today()->format('d M, Y') }}</small>
            </h5>
            <a href="{{ route('attendance.logs') }}" class="btn btn-primary-admin btn-sm text-white">
                View All Logs <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table att-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:45px">#</th>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Work Hours</th>
                            <th class="text-center">Punches</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hm = fn ($m) => \App\Services\AttendanceReportService::minutesToHm((int) $m); @endphp
                        @forelse($today_logs as $row)
                            <tr>
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    @if($row->user_type === 'student')
                                        <span class="att-badge-student"><i class="fas fa-user-graduate mr-1"></i>Student</span>
                                    @elseif($row->user_type === 'teacher')
                                        <span class="att-badge-teacher"><i class="fas fa-chalkboard-teacher mr-1"></i>Teacher</span>
                                    @else
                                        <span class="att-badge-other">Unknown</span>
                                    @endif
                                </td>
                                <td><span class="att-id">{{ $row->user_no }}</span></td>
                                <td class="fw-semibold">{{ $row->name }}</td>
                                <td>{{ $row->department ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="{{ $row->is_late ? 'att-late' : 'att-normal' }}">
                                        <i class="fas fa-sign-in-alt mr-1" style="font-size:.8em"></i>{{ timeFormat($row->in_time, 'h:i A') }}
                                    </span>
                                    @if($row->is_late)
                                        <br><small class="text-danger" style="font-size:.72em">Late {{ $hm($row->late_minutes) }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row->out_time)
                                        <span class="{{ $row->is_early_out ? 'att-late' : 'att-normal' }}">
                                            <i class="fas fa-sign-out-alt mr-1" style="font-size:.8em"></i>{{ timeFormat($row->out_time, 'h:i A') }}
                                        </span>
                                        @if($row->is_early_out)
                                            <br><small class="text-danger" style="font-size:.72em">Early Out</small>
                                        @endif
                                    @else
                                        <span class="text-muted font-weight-bold">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row->out_time)
                                        <span style="font-weight:600;color:#059669">{{ $hm($row->work_minutes) }}</span>
                                    @else
                                        <span class="text-muted font-weight-bold">-</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="att-punch">{{ $row->punches }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x d-block mb-3 text-muted" style="opacity:.35"></i>
                                    No attendance records for today yet.
                                    <br><small>Records appear automatically when students/teachers scan their fingerprint.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    const STATUS_URL   = "{{ route('dashboard.teacher-status') }}";
    const SLIDE_MS     = 7000;   // time each slide stays on screen
    const REFRESH_MS   = 60000;  // live data refresh (today only)

    const el = id => document.getElementById(id);
    const board = el('tbBoard'), track = el('tbTrack'), viewport = el('tbViewport');
    const dotsBox = el('tbDots'), pageLbl = el('tbPage'), progress = el('tbProgress');
    const dateInput = el('tbDate');

    let all = [], filter = 'all', slides = 0, current = 0, playing = true;
    let timer = null, refreshTimer = null, progressStart = 0, rafId = null;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const todayStr = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); };

    // ── Clock ──
    function tick() {
        const d = new Date();
        let h = d.getHours(); const ap = h >= 12 ? 'PM' : 'AM'; h = (h % 12) || 12;
        el('tbClock').innerHTML = String(h).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') +
            ':' + String(d.getSeconds()).padStart(2,'0') + '<small>' + ap + '</small>';
    }
    tick(); setInterval(tick, 1000);

    // ── Layout: how many cards fit per slide ──
    function layout() {
        const w = viewport.clientWidth - (window.innerWidth < 576 ? 28 : 52);
        const minCard = window.innerWidth < 576 ? 135 : 150;
        const cols = Math.max(2, Math.min(10, Math.floor((w + 14) / (minCard + 14))));
        let rows = window.innerWidth < 576 ? 3 : 2;
        if (document.fullscreenElement === board) {
            const h = window.innerHeight - 230;
            rows = Math.max(2, Math.floor(h / 235));
        }
        return { cols, rows, per: cols * rows };
    }

    function filtered() {
        if (filter === 'all') return all;
        if (filter === 'present') return all.filter(t => t.status !== 'absent');
        return all.filter(t => t.status === filter);
    }

    function card(t, i) {
        const photo = t.image
            ? `<img src="${esc(t.image)}" alt="" loading="lazy">`
            : `<div class="ph">${esc(t.initial)}</div>`;
        let time, tag;
        if (t.status === 'absent') {
            time = 'Absent'; tag = `<span class="tb-tag">${t.shift_in ? 'Shift ' + esc(t.shift_in) : 'No punch'}</span>`;
        } else if (t.status === 'late') {
            time = esc(t.in_time); tag = `<span class="tb-tag"><i class="fas fa-exclamation-circle mr-1"></i>Late ${esc(t.late_by)}</span>`;
        } else {
            time = esc(t.in_time); tag = `<span class="tb-tag"><i class="fas fa-check mr-1"></i>On Time</span>`;
        }
        return `<div class="tb-card ${t.status}" style="animation-delay:${(i * 0.045).toFixed(3)}s" title="${esc(t.name)}${t.out_time ? ' | Out ' + esc(t.out_time) : ''}">
                    <div class="tb-photo">${photo}</div>
                    <div class="tb-name">${esc(t.name)}</div>
                    <span class="tb-no">#${esc(t.teacher_no)}</span>
                    <div class="tb-dept">${esc(t.department || t.designation || '')}&nbsp;</div>
                    <div class="tb-time">${time}</div>
                    ${tag}
                </div>`;
    }

    function render(keepSlide) {
        const list = filtered();
        const { cols, per } = layout();
        slides = Math.max(1, Math.ceil(list.length / per));
        if (!keepSlide || current >= slides) current = 0;

        if (list.length === 0) {
            const msg = { late: 'No late arrivals. Everyone is on time!', absent: 'No absentees. Full attendance!', on_time: 'Nobody has arrived on time yet.', present: 'No one has punched in yet.' }[filter] || 'No teachers found.';
            track.innerHTML = `<div class="tb-slide is-active" style="grid-template-columns:1fr"><div class="tb-empty"><i class="fas fa-award"></i>${msg}</div></div>`;
        } else {
            let html = '';
            for (let s = 0; s < slides; s++) {
                const chunk = list.slice(s * per, (s + 1) * per);
                html += `<div class="tb-slide" style="grid-template-columns:repeat(${cols},minmax(0,1fr))">${chunk.map(card).join('')}</div>`;
            }
            track.innerHTML = html;
        }

        dotsBox.innerHTML = slides > 1
            ? Array.from({ length: slides }, (_, i) => `<button type="button" class="tb-dot" data-i="${i}" aria-label="Slide ${i + 1}"></button>`).join('')
            : '';
        go(current, true);
    }

    function go(i, instant) {
        current = (i + slides) % slides;
        if (instant) { track.style.transition = 'none'; }
        track.style.transform = `translateX(-${current * 100}%)`;
        if (instant) { void track.offsetWidth; track.style.transition = ''; }

        track.querySelectorAll('.tb-slide').forEach((s, idx) => {
            s.classList.remove('is-active');
            if (idx === current) { void s.offsetWidth; s.classList.add('is-active'); }
        });
        dotsBox.querySelectorAll('.tb-dot').forEach((d, idx) => d.classList.toggle('active', idx === current));

        const n = filtered().length, per = layout().per;
        pageLbl.textContent = n ? `Showing ${current * per + 1}–${Math.min(n, (current + 1) * per)} of ${n}  ·  Slide ${current + 1}/${slides}` : '';
        restartAuto();
    }

    // ── Autoplay with progress bar ──
    function restartAuto() {
        clearTimeout(timer); cancelAnimationFrame(rafId);
        progress.style.width = '0';
        if (!playing || slides < 2) return;
        progressStart = performance.now();
        const step = now => {
            progress.style.width = Math.min(100, (now - progressStart) / SLIDE_MS * 100) + '%';
            if (now - progressStart < SLIDE_MS) rafId = requestAnimationFrame(step);
        };
        rafId = requestAnimationFrame(step);
        timer = setTimeout(() => go(current + 1), SLIDE_MS);
    }

    function setPlaying(p) {
        playing = p;
        el('tbPlay').innerHTML = p ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
        el('tbPlay').title = p ? 'Pause' : 'Play';
        restartAuto();
    }

    // ── Data ──
    function load(keepSlide) {
        el('tbLoader').classList.add('show');
        return fetch(STATUS_URL + '?' + new URLSearchParams({ date: dateInput.value }), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                all = data.teachers || [];
                const s = data.summary || {};
                el('tbDateLabel').textContent = data.date;
                el('tbCntAll').textContent = s.total ?? 0;
                el('tbCntPresent').textContent = s.present ?? 0;
                el('tbCntOnTime').textContent = s.on_time ?? 0;
                el('tbCntLate').textContent = s.late ?? 0;
                el('tbCntAbsent').textContent = s.absent ?? 0;
                el('tbLive').style.display = dateInput.value === todayStr() ? '' : 'none';
                render(keepSlide);
            })
            .catch(err => console.error('Teacher board load failed:', err))
            .finally(() => el('tbLoader').classList.remove('show'));
    }

    function scheduleRefresh() {
        clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            if (dateInput.value === todayStr() && !document.hidden) load(true);
        }, REFRESH_MS);
    }

    // ── Events ──
    el('tbTabs').addEventListener('click', e => {
        const tab = e.target.closest('.tb-tab'); if (!tab) return;
        el('tbTabs').querySelectorAll('.tb-tab').forEach(t => t.classList.toggle('active', t === tab));
        filter = tab.dataset.filter;
        render(false);
    });
    el('tbPrev').addEventListener('click', () => go(current - 1));
    el('tbNext').addEventListener('click', () => go(current + 1));
    el('tbPlay').addEventListener('click', () => setPlaying(!playing));
    dotsBox.addEventListener('click', e => { const d = e.target.closest('.tb-dot'); if (d) go(+d.dataset.i); });
    el('tbFull').addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen();
        else if (board.requestFullscreen) board.requestFullscreen();
    });
    document.addEventListener('fullscreenchange', () => {
        el('tbFull').innerHTML = document.fullscreenElement ? '<i class="fas fa-compress"></i>' : '<i class="fas fa-expand"></i>';
        setTimeout(() => render(false), 150);
    });
    dateInput.addEventListener('change', () => load(false));

    // Pause while the pointer is over the cards
    viewport.addEventListener('mouseenter', () => { if (playing) { clearTimeout(timer); cancelAnimationFrame(rafId); } });
    viewport.addEventListener('mouseleave', () => restartAuto());

    // Swipe on touch screens
    let touchX = null;
    viewport.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    viewport.addEventListener('touchend', e => {
        if (touchX === null) return;
        const dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) go(current + (dx < 0 ? 1 : -1));
        touchX = null;
    });

    let resizeT = null, lastPer = 0;
    window.addEventListener('resize', () => {
        clearTimeout(resizeT);
        resizeT = setTimeout(() => { const p = layout().per; if (p !== lastPer) { lastPer = p; render(true); } }, 200);
    });

    document.addEventListener('DOMContentLoaded', () => {
        lastPer = layout().per;
        load(false);
        scheduleRefresh();
    });
})();
</script>
@endpush
