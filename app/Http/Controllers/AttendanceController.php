<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Teacher;
use App\Services\AttendanceReportService as Report;
use App\Services\DeviceActivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(protected DeviceActivityService $activity)
    {
    }

    public function syncBackground(Request $request)
    {
        $request->validate([
            'device_id'      => 'nullable|exists:devices,id',
            'sync_from_date' => 'nullable|date',
            'sync_to_date'   => 'nullable|date|after_or_equal:sync_from_date',
        ]);

        $from = $request->sync_from_date ?? Carbon::today()->toDateString();
        $to   = $request->sync_to_date   ?? Carbon::today()->toDateString();

        $result = $this->activity->pullAttendanceFromAllDevices($from, $to, $request->device_id);

        if ($result['deviceCount'] === 0) {
            return back()->with(dangerMessage('danger', 'No active devices found.'));
        }

        $msg = implode("\n", $result['messages']);
        return back()->with(successMessage('success',
            "Attendance sync complete. {$result['total']} record(s) total.\n{$msg}"));
    }

    /**
     * PINs that punched attendance but couldn't be matched to a Student or
     * Teacher (unknown PIN, or the same PIN exists as both on a mixed
     * device). Lets an admin classify them instead of guessing wrong.
     */
    public function unmatched()
    {
        $rows = \App\Models\AttendanceLog::query()
            ->selectRaw("
                unmatched_pin,
                device_serial,
                MIN(punch_time) as first_seen,
                MAX(punch_time) as last_seen,
                COUNT(*) as punch_count
            ")
            ->whereNotNull('unmatched_pin')
            ->groupBy('unmatched_pin', 'device_serial')
            ->orderByDesc('last_seen')
            ->paginate(50);

        return view('attendance.unmatched', compact('rows'));
    }

    // ───────────────────────────── Attendance Logs ─────────────────────────────

    public function logs(Request $request)
    {
        $filters = $this->filters($request);
        [$from_date, $to_date] = Report::resolveRange($filters);

        if ($request->get('export') === 'csv') {
            return $this->exportLogs($filters, $from_date, $to_date);
        }

        $perPage = in_array((int) $request->per_page, [25, 50, 100, 200], true) ? (int) $request->per_page : 50;
        $logs    = Report::logs($filters, $perPage);

        return view('attendance.logs', array_merge($this->filterOptions(), compact('logs', 'from_date', 'to_date')));
    }

    protected function exportLogs(array $filters, string $from, string $to): StreamedResponse
    {
        $rows = Report::logsForExport($filters);

        return $this->csv("attendance_logs_{$from}_to_{$to}.csv",
            ['Date', 'User Type', 'ID', 'Name', 'Department', 'Shift In', 'Shift Out', 'In Time', 'Out Time', 'Late By', 'Early Out By', 'Working Hours', 'Punches'],
            $rows,
            fn ($r) => [
                $r->att_date,
                ucfirst((string) $r->user_type),
                $r->user_no,
                $r->name,
                $r->department ?? '-',
                $r->std_in ? Carbon::parse($r->std_in)->format('h:i A') : '-',
                $r->std_out ? Carbon::parse($r->std_out)->format('h:i A') : '-',
                Carbon::parse($r->in_time)->format('h:i A'),
                $r->out_time ? Carbon::parse($r->out_time)->format('h:i A') : '-',
                $r->is_late ? Report::minutesToHm((int) $r->late_minutes) : '-',
                $r->is_early_out ? Report::minutesToHm((int) $r->early_out_minutes) : '-',
                Report::minutesToHm((int) $r->work_minutes),
                $r->punches,
            ]
        );
    }

    // ───────────────────────────── Monthly Summary ─────────────────────────────

    public function monthlySummary(Request $request)
    {
        $filters = $this->filters($request);
        if (empty($filters['month']) && empty($filters['from_date'])) {
            $filters['month'] = Carbon::today()->format('Y-m');
        }
        [$from_date, $to_date] = Report::resolveRange($filters, 'month');
        $working_days = Report::workingDays($from_date, $to_date);

        if ($request->get('export') === 'csv') {
            return $this->exportSummary($filters, $from_date, $to_date, $working_days);
        }

        $perPage = in_array((int) $request->per_page, [25, 50, 100, 200], true) ? (int) $request->per_page : 50;
        $summary = Report::monthlySummary($filters, $perPage);
        $totals  = Report::userTypeTotals($filters);

        return view('attendance.monthly_summary', array_merge(
            $this->filterOptions(),
            compact('summary', 'totals', 'from_date', 'to_date', 'working_days')
        ));
    }

    protected function exportSummary(array $filters, string $from, string $to, int $workingDays): StreamedResponse
    {
        $rows = Report::monthlySummaryForExport($filters);

        return $this->csv("attendance_summary_{$from}_to_{$to}.csv",
            ['User Type', 'ID', 'Name', 'Department', 'Shift In', 'Shift Out', 'Working Days', 'Present Days', 'Absent Days',
             'Early In (days)', 'Late In (days)', 'Total Late', 'Early Out (days)', 'Total Early Out', 'No Out Punch (days)',
             'Earliest In', 'Average In', 'Total Working Hours'],
            $rows,
            fn ($r) => [
                ucfirst((string) $r->user_type),
                $r->user_no,
                $r->name,
                $r->department ?? '-',
                $r->std_in ? Carbon::parse($r->std_in)->format('h:i A') : '-',
                $r->std_out ? Carbon::parse($r->std_out)->format('h:i A') : '-',
                $workingDays,
                $r->present_days,
                max(0, $workingDays - (int) $r->present_days),
                (int) $r->early_in_days,
                (int) $r->late_days,
                Report::minutesToHm((int) $r->late_minutes),
                (int) $r->early_out_days,
                Report::minutesToHm((int) $r->early_out_minutes),
                (int) $r->single_punch_days,
                $r->earliest_in ? Carbon::parse($r->earliest_in)->format('h:i A') : '-',
                $r->avg_in ? Carbon::parse($r->avg_in)->format('h:i A') : '-',
                Report::minutesToHm((int) $r->work_minutes),
            ]
        );
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    protected function filters(Request $request): array
    {
        $userType = in_array($request->user_type, ['student', 'teacher'], true) ? $request->user_type : null;
        $status   = array_key_exists((string) $request->status, Report::STATUSES) ? $request->status : null;

        return array_filter([
            'date'          => $request->date,
            'month'         => $request->month,
            'from_date'     => $request->from_date,
            'to_date'       => $request->to_date,
            'user_type'     => $userType,
            'teacher_no'    => $request->teacher_no,
            'user_no'       => $request->user_no ? trim($request->user_no) : null,
            'department_id' => $request->department_id,
            'search'        => $request->search,
            'status'        => $status,
            'sort'          => $request->sort,
        ], fn ($v) => $v !== null && $v !== '');
    }

    protected function filterOptions(): array
    {
        return [
            'teachers'    => Teacher::orderBy('name')->get(['teacher_no', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'statuses'    => Report::STATUSES,
        ];
    }

    protected function csv(string $filename, array $header, iterable $rows, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $map($row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
