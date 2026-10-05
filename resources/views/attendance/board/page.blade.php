<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Board · {{ siteSettings()->site_name ?? 'School' }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/common/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/common/flatpicker/flatpicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/attendance-board.css') }}?v={{ @filemtime(public_path('assets/css/attendance-board.css')) }}">
    <style>
        html, body { background: #fff; }
        body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', sans-serif; color: #0f172a; min-height: 100vh;
            background:
                radial-gradient(900px 300px at 0% 0%, rgba(99,102,241,.06), transparent 70%),
                radial-gradient(900px 300px at 100% 100%, rgba(124,58,237,.05), transparent 70%),
                #fff; }
        .ab-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 18px 0 14px; }
        .ab-brand { display: flex; align-items: center; gap: 12px; text-decoration: none !important; }
        .ab-crest { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; font-size: 20px; box-shadow: 0 8px 20px rgba(79,70,229,.3); }
        .ab-school { font-size: 18px; font-weight: 700; color: #0f172a; line-height: 1.2; }
        .ab-sub { font-size: 12px; color: #64748b; letter-spacing: .4px; text-transform: uppercase; font-weight: 600; }
        .ab-actions .btn { border-radius: 10px; font-size: 13px; font-weight: 600; }
        .ab-legend { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12px; color: #64748b; padding: 0 4px 18px; }
        .ab-legend span::before { content: ''; display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; background: var(--c); }
        .ab-foot { text-align: center; font-size: 12px; color: #94a3b8; padding: 6px 0 22px; }
        .tb-board.tb-light { margin-bottom: 14px; }
        @media (max-width: 575px) {
            .ab-school { font-size: 15px; }
            .ab-actions .btn span { display: none; }
        }
    </style>
</head>
<body>
    <div class="container-fluid px-3 px-md-4">
        <header class="ab-top">
            <a class="ab-brand" href="{{ route('attendance-board') }}">
                <div class="ab-crest"><i class="fas fa-school"></i></div>
                <div>
                    <div class="ab-school">{{ siteSettings()->site_name ?? 'Attendance Management' }}</div>
                    <div class="ab-sub">Daily Attendance Monitor</div>
                </div>
            </a>
            <div class="ab-actions">
                <a href="{{ route('attendance.logs') }}" class="btn btn-light border"><i class="fas fa-history mr-1"></i><span>Logs</span></a>
                <a href="{{ route('dashboard') }}" class="btn btn-primary" style="background:#4f46e5;border-color:#4f46e5"><i class="fas fa-th-large mr-1"></i><span>Dashboard</span></a>
            </div>
        </header>

        @include('attendance.board._board', [
            'theme'    => 'light',
            'detailed' => true,
            'keys'     => 1,
            'date'     => $date,
        ])

        <div class="ab-legend">
            <span style="--c:#10b981">On time (in at or before shift start)</span>
            <span style="--c:#ef4444">Late in (after shift start)</span>
            <span style="--c:#f59e0b">Early out (before shift end)</span>
            <span style="--c:#94a3b8">Absent / no out punch shown as "-"</span>
            <span class="d-none d-md-inline" style="--c:transparent"><i class="far fa-keyboard mr-1"></i>&larr; &rarr; to slide, Space to pause</span>
        </div>

        <div class="ab-foot">&copy; {{ date('Y') }} {{ siteSettings()->site_name ?? 'Attendance Management' }}</div>
    </div>

    <script src="{{ asset('assets/common/flatpicker/flatpicker.min.js') }}"></script>
    <script src="{{ asset('assets/js/attendance-board.js') }}?v={{ @filemtime(public_path('assets/js/attendance-board.js')) }}"></script>
</body>
</html>
