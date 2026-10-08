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

        // Push-mode devices are asked to re-upload the range; TCP devices are read live.
        $result = app(\App\Services\AdmsCommandService::class)
            ->pullAttendance($from, $to, $request->device_id ? (int) $request->device_id : null);

        if ($result['deviceCount'] === 0) {
            return back()->with(dangerMessage('danger', 'No active devices found.'));
        }

        $head = $result['pulled'] > 0
            ? "Pulled {$result['pulled']} record(s) directly from TCP device(s)."
            : 'Pull request processed.';
        if ($result['requested'] > 0) {
            $head .= " {$result['requested']} push-mode device(s) asked to re-send {$from} → {$to}; refresh the logs in about a minute.";
        }

        return back()->with(successMessage('success', $head . "\n" . implode("\n", $result['messages'])));
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

        // Same rows and rules as the Attendance Logs page. Date = work date
        // (the day the shift started); "(+1)" = punch on the next day.
        return $this->csv("attendance_logs_{$from}_to_{$to}.csv",
            ['Work Date', 'Day', 'User Type', 'ID', 'Name', 'Department', 'Shift', 'Shift In', 'Shift Out',
             'Late After', 'Early Out Before', 'In Time', 'Out Time', 'Status', 'Late By', 'Early Out By',
             'Working Hours', 'Punches', 'Outside Shift Punches'],
            $rows,
            fn ($r) => [
                $r->att_date,
                Carbon::parse($r->att_date)->format('l'),
                ucfirst((string) $r->user_type),
                $r->user_no,
                $r->name,
                $r->department ?? '-',
                $r->shift_title ?? ($r->std_in ? 'Default' : '-'),
                Report::shiftClock($r->std_in),
                Report::shiftClock($r->std_out, $r->std_in),
                Report::shiftClock($r->late_count_time),
                Report::shiftClock($r->early_out_count_time),
                Report::clock($r->in_time, $r->att_date),
                Report::clock($r->out_time, $r->att_date),
                self::statusText($r),
                $r->is_late ? Report::minutesToHm((int) $r->late_minutes) : '-',
                $r->is_early_out ? Report::minutesToHm((int) $r->early_out_minutes) : '-',
                $r->in_time && $r->out_time ? Report::minutesToHm((int) $r->work_minutes) : '-',
                $r->punches,
                (int) $r->outside_punches,
            ]
        );
    }

    /** Human status of one day row, shared by the CSV export. */
    public static function statusText(object $r): string
    {
        $parts = [];
        if ($r->missing_in)   $parts[] = 'Missing In';
        if ($r->missing_out)  $parts[] = 'Missing Out';
        if ($r->is_late)      $parts[] = 'Late In';
        if ($r->is_early_out) $parts[] = 'Early Out';
        if (!$parts && $r->in_time) $parts[] = $r->late_count_time ? 'On Time' : 'Present';
        if ((int) $r->outside_punches > 0) $parts[] = 'Outside Shift Punch';
        return implode(', ', $parts);
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
            ['User Type', 'ID', 'Name', 'Department', 'Shift', 'Shift In', 'Shift Out', 'Working Days', 'Present Days', 'Absent Days',
             'On Time (days)', 'Late In (days)', 'Total Late', 'Early Out (days)', 'Total Early Out', 'Missing In/Out (days)',
             'Outside Shift Punches', 'Earliest In', 'Average In', 'Total Working Hours'],
            $rows,
            fn ($r) => [
                ucfirst((string) $r->user_type),
                $r->user_no,
                $r->name,
                $r->department ?? '-',
                $r->shift_title ?? ($r->std_in ? 'Default' : '-'),
                Report::shiftClock($r->std_in),
                Report::shiftClock($r->std_out, $r->std_in),
                $workingDays,
                $r->present_days,
                max(0, $workingDays - (int) $r->present_days),
                (int) $r->early_in_days,
                (int) $r->late_days,
                Report::minutesToHm((int) $r->late_minutes),
                (int) $r->early_out_days,
                Report::minutesToHm((int) $r->early_out_minutes),
                (int) $r->single_punch_days,
                (int) $r->outside_punches,
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
            'shift_id'      => $request->shift_id,
            'search'        => $request->search,
            'status'        => $status,
            'sort'          => $request->sort,
        ], fn ($v) => $v !== null && $v !== '');
    }

    protected function filterOptions(): array
    {
        return [
            'teachers'    => Teacher::orderBy('name')->get(['teacher_no', 'name']),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'shift_id']),
            'shifts'      => \App\Models\Shift::orderBy('in_time')->get(['id', 'title', 'in_time', 'out_time']),
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
