<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function App\Http\Controllers\Admin\ucfirst;

class AttendanceController extends Controller
{
    public function attendanceLogs(Request $request)
    {
        // 🔹 Default: today
        $today = now()->toDateString();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            // Case 3: both dates present
            $startDate = $request->get('start_date');
            $endDate   = $request->get('end_date');
        } elseif ($request->filled('start_date')) {
            // Case 2: only start date
            $startDate = $request->get('start_date');
            $endDate   = $startDate;
        } else {
            // Case 1: no date → today only
            $startDate = $today;
            $endDate   = $today;
        }

        // 🔹 Get all active employees
        $employees = User::query()
            ->where('role', 'employee')
            ->where('status', 'active')
            ->select('id', 'employee_id', 'name')
            ->get();

        // 🔹 Attendance logs for the date range
        $attendanceLogs = AttendanceLog::query()
            ->select(
                'employee_id',
                DB::raw('DATE(punch_time) as attendance_date'),
                DB::raw('MIN(punch_time) as check_in'),
                DB::raw('CASE WHEN MIN(punch_time) = MAX(punch_time) THEN NULL ELSE MAX(punch_time) END as check_out'),
                'attendance_by'
            )
            ->whereBetween(DB::raw('DATE(punch_time)'), [$startDate, $endDate])
            ->groupBy('employee_id', DB::raw('DATE(punch_time)'), 'attendance_by')
            ->orderBy(DB::raw('DATE(punch_time)'), 'DESC')
            ->get();

        // 🔹 Map logs by employee + date
        $attendanceMap = [];
        foreach ($attendanceLogs as $log) {
            $attendanceMap[$log->employee_id][$log->attendance_date] = $log;
        }

        // 🔹 Build summary (Present/Absent)
        $summary = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($employees as $emp) {
            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                if (isset($attendanceMap[$emp->employee_id][$dateStr])) {
                    $log = $attendanceMap[$emp->employee_id][$dateStr];
                    $summary[] = [
                        'user_id'    => $emp->id,
                        'employee_name'  => $emp->name,
                        'employee_id'  => $emp->employee_id,
                        'attendance_date' => $dateStr,
                        'attendance_by'  => $log->attendance_by,
                        'check_in'       => $log->check_in,
                        'check_out'      => $log->check_out,
                        'status'         => 'Present',
                    ];
                } else {
                    $summary[] = [
                        'user_id'    => $emp->id,
                        'employee_name'  => $emp->name,
                        'employee_id'  => $emp->employee_id,
                        'attendance_date' => $dateStr,
                        'attendance_by'  => $emp->attendance_by,
                        'type'           => null,
                        'check_in'       => null,
                        'check_out'      => null,
                        'status'         => 'Absent',
                    ];
                }
            }
        }

        // 🔹 Apply filters (user, status)
        $collection = collect($summary);

        if ($request->filled('user')) {
            $collection = $collection->where('employee_id', $request->get('user'));
        }

        if ($request->filled('status')) {
            $collection = $collection->where('status', ucfirst($request->get('status'))); // "Present" or "Absent"
        }

        // 🔹 Manual pagination
        $perPage = 100;
        $page = $request->get('page', 1);
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('attendance.attendance_logs', [
            'attendance_summery' => $paginated,
            'employees' => $employees,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
    }

    public function trackLocation(Request $request)
    {
        // 🔹 Validate the incoming request to ensure we have what we need
        $request->validate([
            'employee_id' => 'required|exists:users,employee_id',
            'date' => 'sometimes|date_format:Y-m-d',
        ]);

        $employeeId = $request->input('employee_id');
        $attendanceDate = $request->input('date', now()->toDateString());

        // Find the associated employee for display purposes
        $employee = User::findOrFail($employeeId);

        // 🔹 Get the first punch of the day (Check-in)
        $checkIn = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('punch_time', $attendanceDate)
            ->orderBy('punch_time', 'asc')
            ->first();

        // 🔹 Get the last punch of the day (Check-out)
        $checkOut = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('punch_time', $attendanceDate)
            ->orderBy('punch_time', 'desc')
            ->first();

        // 🔹 If the first and last punch are the same, there is no distinct check-out.
        if ($checkIn && $checkOut && $checkIn->id === $checkOut->id) {
            $checkOut = null;
        }

        // 🔹 Pass both logs (or nulls) to the view
        return view('attendance.track_location', compact('checkIn', 'checkOut', 'employee', 'attendanceDate'));
    }
}
