<style>
    .rpt-filter { background:#fff; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 2px 12px rgba(15,23,42,.04); }
    .rpt-filter .form-label { font-size:12px; font-weight:600; color:#475569; margin-bottom:4px; text-transform:uppercase; letter-spacing:.4px; }
    .rpt-filter .form-control { font-size:13px; border-radius:8px; }
    .rpt-chips { display:flex; flex-wrap:wrap; gap:6px; }
    .rpt-chip { font-size:12px; font-weight:600; padding:4px 12px; border-radius:20px; border:1px solid #c7d2fe; color:#4338ca; background:#eef2ff; text-decoration:none !important; transition:all .15s; }
    .rpt-chip:hover, .rpt-chip.active { background:#4f46e5; color:#fff; border-color:#4f46e5; }
    .rpt-table thead th { background:#1e1b4b; color:#e0e7ff; font-size:11.5px; font-weight:600; letter-spacing:.4px; text-transform:uppercase; border:none; padding:10px 10px; white-space:nowrap; position:sticky; top:0; z-index:1; }
    .rpt-table td { font-size:13px; vertical-align:middle; padding:8px 10px; border-color:#f1f5f9; }
    .rpt-table tbody tr:hover td { background:#f8fafc; }
    .rpt-avatar { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid #e0e7ff; flex-shrink:0; }
    .rpt-avatar-ph { width:34px; height:34px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; font-weight:700; font-size:13px; flex-shrink:0; }
    .rpt-id { font-family:SFMono-Regular,Consolas,monospace; font-size:12px; color:#4338ca; background:#eef2ff; padding:1px 7px; border-radius:5px; }
    .rpt-type { font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px; text-transform:capitalize; }
    .rpt-type.teacher { background:#d1fae5; color:#065f46; }
    .rpt-type.student { background:#ede9fe; color:#6d28d9; }
    .rpt-time { font-weight:600; font-variant-numeric:tabular-nums; }
    .rpt-time.ok { color:#047857; }
    .rpt-time.bad { color:#dc2626; }
    .rpt-sub { display:block; font-size:11px; color:#94a3b8; }
    .rpt-sub.bad { color:#ef4444; font-weight:600; }
    .rpt-dash { color:#94a3b8; font-weight:700; }
    .rpt-kpi { border-radius:14px; padding:14px 16px; background:#fff; border:1px solid #e5e7eb; height:100%; }
    .rpt-kpi .k-label { font-size:11px; text-transform:uppercase; letter-spacing:.6px; color:#64748b; font-weight:700; }
    .rpt-kpi .k-value { font-size:24px; font-weight:800; color:#0f172a; font-variant-numeric:tabular-nums; line-height:1.2; }
    .rpt-kpi .k-sub { font-size:12px; color:#64748b; }
    .rpt-kpi.accent { border-left:4px solid #4f46e5; }
    .rpt-kpi.red { border-left:4px solid #dc2626; }
    .rpt-kpi.green { border-left:4px solid #059669; }
    .rpt-kpi.amber { border-left:4px solid #d97706; }
    .rpt-pill { display:inline-block; min-width:28px; text-align:center; font-weight:700; font-size:12px; padding:2px 8px; border-radius:10px; }
    .rpt-pill.red { background:#fee2e2; color:#b91c1c; }
    .rpt-pill.green { background:#dcfce7; color:#15803d; }
    .rpt-pill.amber { background:#fef3c7; color:#b45309; }
    .rpt-pill.gray { background:#f1f5f9; color:#475569; }
    .rpt-pill.blue { background:#e0e7ff; color:#3730a3; }
    @media print {
        .navbar, .footer, .rpt-filter, .no-print, .pagination, .background-animation { display:none !important; }
        .rpt-table thead th { background:#1e1b4b !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .card { box-shadow:none !important; border:none !important; }
    }
</style>
