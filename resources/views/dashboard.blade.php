@extends('layouts.app')
@section('title', 'Dashboard')
@push('css')
<style>
/* ─────────────────────────────────────────
   Teacher Status Section Styles
───────────────────────────────────────── */

/* Section header pill */
.ts-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}
.ts-section-title {
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin: 0;
}
.ts-count-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    color: #fff;
}
.ts-count-badge.absent  { background: linear-gradient(135deg,#ef4444,#dc2626); }
.ts-count-badge.late    { background: linear-gradient(135deg,#f59e0b,#d97706); }

/* Teacher Card */
.teacher-status-card {
    background: rgba(255,255,255,0.85);
    border-radius: 16px;
    padding: 18px 12px 14px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    border: 1px solid rgba(200,200,220,0.3);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 16px;
}
.teacher-status-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    border-radius: 16px 16px 0 0;
}
.teacher-status-card.absent::before  { background: linear-gradient(90deg,#ef4444,#f87171); }
.teacher-status-card.late::before    { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.teacher-status-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

/* Avatar in card */
.ts-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
    margin-bottom: 10px;
}
.ts-avatar-placeholder {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    margin: 0 auto 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}
.absent  .ts-avatar-placeholder { background: linear-gradient(135deg,#ef4444,#dc2626); border: 3px solid #fca5a5; }
.late    .ts-avatar-placeholder { background: linear-gradient(135deg,#f59e0b,#d97706); border: 3px solid #fcd34d; }

.ts-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    line-height: 1.3;
}
.ts-status-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
}
.absent  .ts-status-badge { background: #fee2e2; color: #dc2626; }
.late    .ts-status-badge { background: #fef3c7; color: #d97706; }

.ts-time {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
}

/* Date Filter Bar */
.ts-filter-bar {
    background: rgba(255,255,255,0.8);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid rgba(200,200,220,0.3);
    margin-bottom: 24px;
}
.ts-filter-bar label {
    font-size: 13px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0;
    white-space: nowrap;
}
.ts-filter-bar .form-control {
    max-width: 180px;
    border-radius: 10px;
    font-size: 13px;
    border: 1.5px solid #d1d5db;
    padding: 7px 12px;
}
.ts-filter-bar .btn {
    border-radius: 10px;
    padding: 7px 18px;
    font-size: 13px;
    font-weight: 600;
}

/* Loader overlay */
.ts-loader-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    z-index: 10;
}
.ts-spinner {
    width: 36px;
    height: 36px;
    border: 4px solid #e5e7eb;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: ts-spin 0.7s linear infinite;
}
@keyframes ts-spin { to { transform: rotate(360deg); } }

#teacherStatusSection { position: relative; min-height: 120px; }

/* Empty state */
.ts-empty {
    text-align: center;
    padding: 30px 0 10px;
    color: #9ca3af;
    font-size: 14px;
}
.ts-empty i { font-size: 36px; display: block; margin-bottom: 10px; }

/* Divider between absent / late */
.ts-divider {
    border: none;
    border-top: 2px dashed #e5e7eb;
    margin: 24px 0;
}

/* Make card header date label nice */
#statusDateLabel {
    font-weight: 700;
    color: #6366f1;
}
</style>
@endpush

@section('content')
    <h2 class="mb-2">Dashboard Overview</h2>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Students
                            </div>
                            <div class="stats-number">{{ $total_students ?? 0 }}</div>
                            <div class="text-muted">Active Students</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users stats-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Teachers
                            </div>
                            <div class="stats-number">{{ $total_teachers ?? 0 }}</div>
                            <div class="text-muted">Active Teachers</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus stats-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Devices
                            </div>
                            <div class="stats-number">{{ $total_devices ?? 0 }}</div>
                            <div class="text-muted">Active Devices</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-fingerprint stats-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Today Present
                            </div>
                            <div class="stats-number">{{ $today_present ?? 0 }}</div>
                            <div class="text-muted">Awaiting processing</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock stats-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         TEACHER ATTENDANCE STATUS — Absent & Late
    ════════════════════════════════════════════════════════ -->
    <div class="card admin-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">
                <i class="fas fa-chalkboard-teacher mr-2"></i>
                Teacher Attendance Status
                <small class="text-muted ml-2" style="font-size:13px;">— <span id="statusDateLabel">{{ \Carbon\Carbon::today()->format('d M, Y') }}</span></small>
            </h5>
        </div>
        <div class="card-body">

            <!-- Date Filter Bar -->
            <div class="ts-filter-bar">
                <label><i class="fas fa-calendar-alt mr-1"></i> Filter by Date:</label>
                <div class="input-clearable" style="position:relative;">
                    <input type="text" id="teacherStatusDate" class="form-control flat_datepicker"
                           placeholder="{{ dateFormat(today()) }}"
                           value="{{ \Carbon\Carbon::today()->toDateString() }}"
                           autocomplete="off" readonly>
                    <span class="clear-btn" onclick="document.getElementById('teacherStatusDate').value='';loadTeacherStatus();">
                        <i class="fa fa-calendar"></i>
                    </span>
                </div>
                <button type="button" class="btn btn-primary" id="teacherStatusFilterBtn" onclick="loadTeacherStatus()">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetTeacherStatusFilter()">
                    <i class="fas fa-undo mr-1"></i> Today
                </button>
            </div>

            <!-- Results Section -->
            <div id="teacherStatusSection">
                <!-- Loader (hidden initially) -->
                <div class="ts-loader-overlay" id="tsLoader" style="display:none;">
                    <div class="ts-spinner"></div>
                </div>

                <!-- ── ABSENT TEACHERS ── -->
                <div class="ts-section-header">
                    <i class="fas fa-user-times" style="color:#ef4444;font-size:18px;"></i>
                    <h6 class="ts-section-title" style="color:#ef4444;">Absent Teachers</h6>
                    <span class="ts-count-badge absent" id="absentCount">0</span>
                </div>
                <div class="row" id="absentTeachersGrid">
                    <div class="col-12">
                        <div class="ts-empty" id="absentEmptyMsg">
                            <i class="fas fa-check-circle" style="color:#22c55e;"></i>
                            All teachers are present today!
                        </div>
                    </div>
                </div>

                <hr class="ts-divider">

                <!-- ── LATE TEACHERS ── -->
                <div class="ts-section-header">
                    <i class="fas fa-clock" style="color:#f59e0b;font-size:18px;"></i>
                    <h6 class="ts-section-title" style="color:#d97706;">Late Arrival Teachers</h6>
                    <span class="ts-count-badge late" id="lateCount">0</span>
                </div>
                <div class="row" id="lateTeachersGrid">
                    <div class="col-12">
                        <div class="ts-empty" id="lateEmptyMsg">
                            <i class="fas fa-smile" style="color:#6366f1;"></i>
                            No late arrivals today!
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Recent Attendance Logs -->
    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title"><i class="fas fa-list-alt mr-2"></i> Today Attendance</h5>
                    <a href="{{ route('attendance-summery') }}" class="btn btn-primary-admin btn-sm text-white"
                        {!! tooltip('Show Logs') !!}>
                        Show Logs
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">User Type</th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th class="text-center">In Time</th>
                                    <th class="text-center">Out Time</th>
                                    <th class="text-center">Work Hour</th>
                                    <th class="text-center">Punch Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($today_logs as $attendance)
                                    <tr>
                                        <td class="text-center">{{ $loop->index + 1 }}</td>
                                        <td class="text-center">{{ ucFirst($attendance['user_type']) }}</td>
                                        <td>{{ $attendance['user_no'] ?? '' }}</td>
                                        <td>{{ $attendance['name'] ?? '' }}</td>
                                        <td class="text-center {{ isLateIn($attendance['in_time']) ? 'text-danger' : '' }}">
                                            {{ timeFormat($attendance['in_time'], 'h:i a') }}</td>
                                        <td class="text-center {{ isEarlyOut($attendance['out_time']) ? 'text-danger' : '' }}">
                                            {{ timeFormat($attendance['out_time'], 'h:i a') ?? '-' }}</td>
                                        <td class="text-center">
                                            {{ hourCount($attendance['out_time'], $attendance['in_time']) }} </td>
                                        <td class="text-center">{{ $attendance['total_punches'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <x-no-data-found />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {!! $today_logs->links('pagination::bootstrap-4') !!}
                    </div>
                </div>
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

    return `
        <div class="col-md-2 col-sm-3 col-4">
            <div class="teacher-status-card ${type}">
                ${avatarHtml}
                <div class="ts-name">${teacher.name}</div>
                ${badgeHtml}
            </div>
        </div>`;
}

function loadTeacherStatus() {
    const dateInput = document.getElementById('teacherStatusDate');
    const date      = dateInput ? dateInput.value : '';

    // Show loader
    document.getElementById('tsLoader').style.display = 'flex';

    const params = new URLSearchParams({ date: date });

    fetch(TEACHER_STATUS_URL + '?' + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        // Update date label
        document.getElementById('statusDateLabel').textContent = data.date;

        // Update absent count badge
        document.getElementById('absentCount').textContent = data.absent_teachers.length;

        // Render absent teachers
        const absentGrid = document.getElementById('absentTeachersGrid');
        if (data.absent_teachers.length === 0) {
            absentGrid.innerHTML = `
                <div class="col-12">
                    <div class="ts-empty">
                        <i class="fas fa-check-circle" style="color:#22c55e;"></i>
                        All teachers are present on this date!
                    </div>
                </div>`;
        } else {
            absentGrid.innerHTML = data.absent_teachers
                .map(t => buildTeacherCard(t, 'absent'))
                .join('');
        }

        // Update late count badge
        document.getElementById('lateCount').textContent = data.late_teachers.length;

        // Render late teachers
        const lateGrid = document.getElementById('lateTeachersGrid');
        if (data.late_teachers.length === 0) {
            lateGrid.innerHTML = `
                <div class="col-12">
                    <div class="ts-empty">
                        <i class="fas fa-smile" style="color:#6366f1;"></i>
                        No late arrivals on this date!
                    </div>
                </div>`;
        } else {
            lateGrid.innerHTML = data.late_teachers
                .map(t => buildTeacherCard(t, 'late'))
                .join('');
        }
    })
    .catch(err => {
        console.error('Teacher status load failed:', err);
    })
    .finally(() => {
        document.getElementById('tsLoader').style.display = 'none';
    });
}

function resetTeacherStatusFilter() {
    const today = new Date().toISOString().slice(0, 10);
    const input = document.getElementById('teacherStatusDate');
    if (input) {
        input.value = today;
        // Trigger flatpickr update if available
        if (input._flatpickr) {
            input._flatpickr.setDate(today, false);
        }
    }
    loadTeacherStatus();
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', function () {
    loadTeacherStatus();
});

// Also reload when flatpickr date changes (flat_datepicker instances fire a change event)
document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('teacherStatusDate');
    if (dateInput) {
        dateInput.addEventListener('change', function () {
            loadTeacherStatus();
        });
    }
});
</script>
@endpush
