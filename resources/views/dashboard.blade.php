@extends('layouts.app')
@section('title', 'Dashboard')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/attendance-board.css') }}?v={{ @filemtime(public_path('assets/css/attendance-board.css')) }}">
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


    {{-- Teacher status board (shared: attendance/board/_board) --}}
    @include('attendance.board._board', [
        'theme' => 'dark',
        'link'  => ['url' => route('attendance-board'), 'icon' => 'fas fa-external-link-alt', 'title' => 'Open full-page board', 'blank' => true],
    ])

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
<script src="{{ asset('assets/js/attendance-board.js') }}?v={{ @filemtime(public_path('assets/js/attendance-board.js')) }}"></script>
@endpush
