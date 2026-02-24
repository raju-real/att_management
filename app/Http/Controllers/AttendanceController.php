<?php

namespace App\Http\Controllers;

use App\Exports\DateWisePresentExport;
use App\Exports\MonthWisePresentExport;
use App\Exports\UserWiseSummaryExport;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use niklasravnsborg\LaravelPdf\Facades\Pdf;


class AttendanceController extends Controller
{
    public function syncBackground(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'sync_from_date' => 'nullable|date',
            'sync_to_date'   => 'nullable|date|after_or_equal:sync_from_date',
        ]);

        $from = $request->sync_from_date ?? Carbon::today()->toDateString();
        $to   = $request->sync_to_date   ?? Carbon::today()->toDateString();

        \App\Jobs\SyncAttendanceJob::dispatch($from, $to);

        return back()->with(successMessage('success', "Attendance sync from {$from} to {$to} queued and running in background."));
    }


    public function presentLogs()
    {
        $filter = [];
        $filter['user_type'] = request()->get('user_type') ?? '';
        $filter['user_no'] = request()->get('user_no') ?? '';
        $filter['student_no'] = request()->get('student_no') ?? '';
        $filter['student_id'] = request()->get('student_id') ?? '';
        $filter['teacher_no'] = request()->get('teacher_no') ?? '';
        $filter['from_date'] = request()->get('from_date') ?? Carbon::today()->toDateString();
        $filter['to_date'] = request()->get('to_date');
        //$report = $this->attendanceReport($filter);
        $attendance_logs = AttendanceService::getDailyAttendance($filter);
        $from_date = $filter['from_date'];
        $to_date = $filter['to_date'];
        return view('attendance.present_logs', compact('attendance_logs', 'from_date', 'to_date'));
    }

    public function attendanceSummery()
    {
        $filter = [];
        $filter['user_type'] = request()->get('user_type') ?? '';
        $filter['user_no'] = request()->get('user_no') ?? '';
        $filter['student_no'] = request()->get('student_no') ?? '';
        $filter['student_id'] = request()->get('student_id') ?? '';
        $filter['teacher_no'] = request()->get('teacher_no') ?? '';
        $filter['from_date'] = request()->get('from_date') ?? Carbon::today()->toDateString();
        $filter['to_date'] = request()->get('to_date');
        $filter['status'] = request()->get('status');
        $attendance_logs = AttendanceService::attendanceSummery($filter);
        $from_date = $filter['from_date'];
        $to_date = $filter['to_date'];
        return view('attendance.present_absent_logs', compact('attendance_logs', 'from_date', 'to_date'));
    }

    public function monthWisePresentReport()
    {
        $filter = [];
        $filter['user_type'] = request()->get('user_type') ?? '';
        $filter['student_id'] = request()->get('student_id') ?? '';
        $filter['teacher_no'] = request()->get('teacher_no') ?? '';
        $filter['from_date'] = request()->get('from_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $filter['to_date'] = request()->get('to_date') ?? Carbon::today()->toDateString();
        $filter['data_type'] = request()->get('data_type') ?? 'all_days';

        $from_date = $filter['from_date'];
        $to_date = $filter['to_date'];

        $display_type = request()->get('display_type') ?? 'show_data';

        if ($display_type === 'show_data') {
            $attendance_reports = AttendanceService::monthWisePresentReport($filter);
            return view('reports.month_wise_present', compact('attendance_reports', 'from_date', 'to_date'));
        } elseif ($display_type === 'download_as_xl') {
            $bas_file_name = dateFormat($from_date, 'd_m_y') . '_to_' . dateFormat($to_date, 'd_m_y');
            return Excel::download(
                new MonthWisePresentExport($filter),
                $bas_file_name . '_' . now()->format('Ymd_His') . '_attendance_report.xlsx'
            );
        } elseif ($display_type === 'download_as_pdf') {
            $attendance_reports = AttendanceService::monthWisePresentReport($filter);
            $bas_file_name = dateFormat($from_date, 'd_m_y') . '_to_' . dateFormat($to_date, 'd_m_y');
            $report = PDF::loadView('pdf.month_wise_present_report', compact('attendance_reports', 'from_date', 'to_date'));
            return $report->download($bas_file_name . '_' . now()->format('Ymd_His') . '_attendance report' . '.pdf');
        }
    }

    public function monthWiseUserSummery()
    {
        $filter = [];
        $filter['user_type'] = request()->get('user_type') ?? '';
        $filter['student_no'] = request()->get('student_no') ?? '';
        $filter['teacher_no'] = request()->get('teacher_no') ?? '';
        $filter['from_date'] = request()->get('from_date') ?? Carbon::now()->startOfMonth()->toDateString();
        $filter['to_date'] = request()->get('to_date') ??  Carbon::today()->toDateString();
        $display_type = request()->get('display_type') ?? 'show_data';

        $from_date = $filter['from_date'];
        $to_date = $filter['to_date'];
        $in_time = siteSettings()->in_time;
        $out_time = siteSettings()->out_time;

        $attendance_reports = AttendanceService::monthWiseUserSummery($filter);
        if ($display_type === 'show_data') {
            return view('reports.month_wise_user_summery', compact('attendance_reports', 'from_date', 'to_date'));
        } elseif ($display_type === 'download_as_xl') {
            $bas_file_name = dateFormat($from_date, 'd_m_y') . '_to_' . dateFormat($to_date, 'd_m_y');
            return Excel::download(
                new UserWiseSummaryExport($attendance_reports, $from_date, $to_date, $in_time, $out_time),
                $bas_file_name . 'month_user_wise_summary_' . now()->format('Ymd_His') . '.xlsx'
            );
        } elseif ($display_type === 'download_as_pdf') {
            $attendance_reports = AttendanceService::monthWiseUserSummery($filter);
            $bas_file_name = dateFormat($from_date, 'd_m_y') . '_to_' . dateFormat($to_date, 'd_m_y');
            $report = PDF::loadView('pdf.month_wise_user_summery', compact('attendance_reports', 'from_date', 'to_date'));
            return $report->download($bas_file_name . '_' . now()->format('Ymd_His') . '_attendance report' . '.pdf');
        }
    }
}
