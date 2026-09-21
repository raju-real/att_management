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

/* ── Teacher Status Cards ── */
.ts-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.teacher-status-card {
    width: 130px; border-radius: 16px; padding: 16px 10px 12px;
    text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,.07);
    border: 1px solid rgba(200,200,220,.3); transition: all .25s ease;
    position: relative; overflow: hidden; background: rgba(255,255,255,.9);
}
.teacher-status-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:4px; border-radius:16px 16px 0 0;
}
.teacher-status-card.absent::before  { background: linear-gradient(90deg,#ef4444,#f87171); }
.teacher-status-card.late::before    { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.teacher-status-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,.12); }
.ts-avatar {
    width: 62px; height: 62px; border-radius: 50%;
    object-fit: cover; border: 3px solid #e5e7eb;
    box-shadow: 0 3px 12px rgba(0,0,0,.12); margin-bottom: 8px;
}
.ts-avatar-placeholder {
    width: 62px; height: 62px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 700; color: #fff; margin: 0 auto 8px;
    box-shadow: 0 3px 12px rgba(0,0,0,.15);
}
.absent  .ts-avatar-placeholder { background: linear-gradient(135deg,#ef4444,#dc2626); border: 3px solid #fca5a5; }
.late    .ts-avatar-placeholder { background: linear-gradient(135deg,#f59e0b,#d97706); border: 3px solid #fcd34d; }
.ts-name { font-size: 12px; font-weight: 600; color: #1e293b; line-height: 1.3; margin-bottom: 5px; }
.ts-status-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; display: inline-block; }
.absent  .ts-status-badge { background: #fee2e2; color: #dc2626; }
.late    .ts-status-badge { background: #fef3c7; color: #d97706; }
.ts-time { font-size: 10px; color: #6b7280; margin-top: 3px; }
.ts-count-badge {
    font-size: 11px; font-weight: 700; padding: 2px 8px;
    border-radius: 20px; color: #fff; margin-left: 6px;
}
.ts-count-badge.absent  { background: linear-gradient(135deg,#ef4444,#dc2626); }
.ts-count-badge.late    { background: linear-gradient(135deg,#f59e0b,#d97706); }

/* ── Filter Bar ── */
.ts-filter-bar {
    background: rgba(255,255,255,.8); border-radius: 14px;
    padding: 12px 18px; display: flex; align-items: center;
    gap: 10px; flex-wrap: wrap; border: 1px solid rgba(200,200,220,.3);
    margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.ts-filter-bar label { font-size: 13px; font-weight: 600; color: #4b5563; margin: 0; white-space: nowrap; }
.ts-filter-bar .form-control { max-width: 175px; border-radius: 10px; font-size: 13px; border: 1.5px solid #d1d5db; padding: 6px 12px; }
.ts-filter-bar .btn { border-radius: 10px; padding: 6px 16px; font-size: 13px; font-weight: 600; }

/* ── Loader ── */
.ts-loader-overlay { position:absolute; inset:0; background:rgba(255,255,255,.7); display:flex; align-items:center; justify-content:center; border-radius:16px; z-index:10; }
.ts-spinner { width:32px; height:32px; border:4px solid #e5e7eb; border-top-color:#6366f1; border-radius:50%; animation:ts-spin .7s linear infinite; }
@keyframes ts-spin { to { transform: rotate(360deg); } }
#teacherStatusSection { position:relative; min-height:100px; }
.ts-empty { text-align:center; padding:20px 0 8px; color:#9ca3af; font-size:13px; }
.ts-empty i { font-size:30px; display:block; margin-bottom:8px; }
.ts-divider { border:none; border-top:2px dashed #e5e7eb; margin:20px 0; }
#statusDateLabel { font-weight:700; color:#6366f1; }

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
         TEACHER ATTENDANCE STATUS
    ═══════════════════════════════════════════ --}}
    <div class="card admin-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-chalkboard-teacher mr-2"></i>Teacher Attendance Status
                <small class="text-muted ml-2" style="font-size:13px">— <span id="statusDateLabel">{{ \Carbon\Carbon::today()->format('d M, Y') }}</span></small>
            </h5>
        </div>
        <div class="card-body">

            <div class="ts-filter-bar">
                <label><i class="fas fa-calendar-alt mr-1"></i> Date:</label>
                <div class="input-clearable" style="position:relative">
                    <input type="text" id="teacherStatusDate" class="form-control flat_datepicker"
                           placeholder="{{ dateFormat(today()) }}"
                           value="{{ \Carbon\Carbon::today()->toDateString() }}"
                           autocomplete="off" readonly>
                    <span class="clear-btn" onclick="resetTeacherStatusFilter()">
                        <i class="fa fa-calendar"></i>
                    </span>
                </div>
                <button type="button" class="btn btn-primary" onclick="loadTeacherStatus()">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetTeacherStatusFilter()">
                    <i class="fas fa-undo mr-1"></i> Today
                </button>
            </div>

            <div id="teacherStatusSection">
                <div class="ts-loader-overlay" id="tsLoader" style="display:none">
                    <div class="ts-spinner"></div>
                </div>

                {{-- Absent --}}
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-user-times" style="color:#ef4444;font-size:17px;margin-right:7px"></i>
                    <span style="font-weight:700;color:#ef4444;font-size:14px">Absent Teachers</span>
                    <span class="ts-count-badge absent" id="absentCount">0</span>
                </div>
                <div class="ts-grid" id="absentTeachersGrid">
                    <div class="ts-empty" id="absentEmptyMsg">
                        <i class="fas fa-check-circle" style="color:#22c55e"></i>
                        All teachers are present today!
                    </div>
                </div>

                <hr class="ts-divider">

                {{-- Late --}}
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-clock" style="color:#f59e0b;font-size:17px;margin-right:7px"></i>
                    <span style="font-weight:700;color:#d97706;font-size:14px">Late Arrival Teachers</span>
                    <span class="ts-count-badge late" id="lateCount">0</span>
                </div>
                <div class="ts-grid" id="lateTeachersGrid">
                    <div class="ts-empty" id="lateEmptyMsg">
                        <i class="fas fa-smile" style="color:#6366f1"></i>
                        No late arrivals today!
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         TODAY'S ATTENDANCE TABLE
    ═══════════════════════════════════════════ --}}
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-alt mr-2"></i>Today's Attendance
                <small class="text-muted ml-1" style="font-size:13px">{{ \Carbon\Carbon::today()->format('d M, Y') }}</small>
            </h5>
            <a href="{{ route('attendance-summery') }}" class="btn btn-primary-admin btn-sm text-white">
                View All Reports <i class="fas fa-arrow-right ml-1"></i>
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
                            <th class="text-center">In Time</th>
                            <th class="text-center">Out Time</th>
                            <th class="text-center">Work Hours</th>
                            <th class="text-center">Punches</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($today_logs as $attendance)
                            <tr>
                                <td class="text-center text-muted">{{ $loop->index + 1 }}</td>
                                <td>
                                    @php $type = strtolower($attendance['user_type'] ?? ''); @endphp
                                    @if($type === 'student')
                                        <span class="att-badge-student"><i class="fas fa-user-graduate mr-1"></i>Student</span>
                                    @elseif($type === 'teacher')
                                        <span class="att-badge-teacher"><i class="fas fa-chalkboard-teacher mr-1"></i>Teacher</span>
                                    @else
                                        <span class="att-badge-other">{{ ucfirst($type ?: 'Unknown') }}</span>
                                    @endif
                                </td>
                                <td><span class="att-id">{{ $attendance['user_no'] ?? '—' }}</span></td>
                                <td class="fw-semibold">
                                    {{ $attendance['name'] ?? '—' }}
                                </td>
                                <td class="text-center">
                                    @if(!empty($attendance['in_time']))
                                        <span class="{{ isLateIn($attendance['in_time'], $attendance['shift_in_time'] ?? null) ? 'att-late' : 'att-normal' }}">
                                            <i class="fas fa-sign-in-alt mr-1" style="font-size:.8em"></i>
                                            {{ timeFormat($attendance['in_time'], 'h:i a') }}
                                        </span>
                                        @if(isLateIn($attendance['in_time'], $attendance['shift_in_time'] ?? null))
                                            <br><small class="text-danger" style="font-size:.72em">Late</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(!empty($attendance['out_time']))
                                        <span class="{{ isEarlyOut($attendance['out_time'], $attendance['shift_out_time'] ?? null) ? 'att-late' : 'att-normal' }}">
                                            <i class="fas fa-sign-out-alt mr-1" style="font-size:.8em"></i>
                                            {{ timeFormat($attendance['out_time'], 'h:i a') }}
                                        </span>
                                        @if(isEarlyOut($attendance['out_time'], $attendance['shift_out_time'] ?? null))
                                            <br><small class="text-danger" style="font-size:.72em">Early Out</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php $hrs = hourCount($attendance['out_time'] ?? null, $attendance['in_time'] ?? null); @endphp
                                    @if($hrs)
                                        <span style="font-weight:600;color:#059669">{{ $hrs }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="att-punch">{{ $attendance['total_punches'] ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x d-block mb-3 text-muted" style="opacity:.35"></i>
                                    No attendance records for today yet.
                                    <br><small>Records appear automatically when students/teachers scan their fingerprint.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3 pb-2">
                {!! $today_logs->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>

@endsection

@push('js')
<script>
const TEACHER_STATUS_URL = "{{ route('dashboard.teacher-status') }}";

function buildTeacherCard(teacher, type) {
    const avatarHtml = teacher.image
        ? `<img src="${teacher.image}" alt="${teacher.name}" class="ts-avatar">`
        : `<div class="ts-avatar-placeholder">${teacher.initial}</div>`;

    const badgeHtml = type === 'absent'
        ? `<span class="ts-status-badge">Absent</span>`
        : `<span class="ts-status-badge">Late</span><div class="ts-time"><i class="fas fa-clock mr-1"></i>${teacher.in_time}</div>`;

    return `<div class="teacher-status-card ${type}">
                ${avatarHtml}
                <div class="ts-name">${teacher.name}</div>
                ${badgeHtml}
            </div>`;
}

function loadTeacherStatus() {
    const dateInput = document.getElementById('teacherStatusDate');
    const date = dateInput ? dateInput.value : '';
    document.getElementById('tsLoader').style.display = 'flex';

    fetch(TEACHER_STATUS_URL + '?' + new URLSearchParams({ date }), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('statusDateLabel').textContent = data.date;

        // Absent
        document.getElementById('absentCount').textContent = data.absent_teachers.length;
        const absentGrid = document.getElementById('absentTeachersGrid');
        absentGrid.innerHTML = data.absent_teachers.length === 0
            ? `<div class="ts-empty"><i class="fas fa-check-circle" style="color:#22c55e"></i>All teachers are present on this date!</div>`
            : data.absent_teachers.map(t => buildTeacherCard(t, 'absent')).join('');

        // Late
        document.getElementById('lateCount').textContent = data.late_teachers.length;
        const lateGrid = document.getElementById('lateTeachersGrid');
        lateGrid.innerHTML = data.late_teachers.length === 0
            ? `<div class="ts-empty"><i class="fas fa-smile" style="color:#6366f1"></i>No late arrivals on this date!</div>`
            : data.late_teachers.map(t => buildTeacherCard(t, 'late')).join('');
    })
    .catch(err => console.error('Teacher status load failed:', err))
    .finally(() => {
        document.getElementById('tsLoader').style.display = 'none';
    });
}

function resetTeacherStatusFilter() {
    const today = new Date().toISOString().slice(0, 10);
    const input = document.getElementById('teacherStatusDate');
    if (input) {
        input.value = today;
        if (input._flatpickr) input._flatpickr.setDate(today, false);
    }
    loadTeacherStatus();
}

document.addEventListener('DOMContentLoaded', function () {
    loadTeacherStatus();
    const dateInput = document.getElementById('teacherStatusDate');
    if (dateInput) {
        dateInput.addEventListener('change', loadTeacherStatus);
    }
});
</script>
@endpush
