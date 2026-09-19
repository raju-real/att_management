<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $total_students = Student::count();
        $total_teachers = Teacher::count();
        $total_devices  = Device::active()->count();
        $today_present  = AttendanceLog::whereDate('punch_time', today())
            ->select(DB::raw("COALESCE(student_no, teacher_no) as user_no"))
            ->distinct()
            ->get()
            ->count();
        $today_logs = AttendanceService::getDailyAttendance([], 50);

        return view('dashboard', compact(
            'total_students',
            'total_teachers',
            'total_devices',
            'today_present',
            'today_logs'
        ));
    }

    /**
     * AJAX endpoint: returns absent & late teachers for a given date.
     * GET /dashboard/teacher-status?date=YYYY-MM-DD
     */
    public function teacherAttendanceStatus(Request $request)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        // Validate date format
        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            $date = Carbon::today()->toDateString();
        }

        // All active teachers
        $allTeachers = Teacher::all();

        // Teachers who punched in on that date
        $presentLogs = AttendanceLog::query()
            ->selectRaw("
                teacher_no,
                MIN(punch_time) as in_time
            ")
            ->whereDate('punch_time', $date)
            ->where('user_type', 'teacher')
            ->whereNotNull('teacher_no')
            ->groupBy('teacher_no')
            ->get()
            ->keyBy('teacher_no');

        $absentTeachers = [];
        $lateTeachers   = [];

        foreach ($allTeachers as $teacher) {
            $log = $presentLogs->get($teacher->teacher_no);

            if (!$log) {
                // Absent
                $absentTeachers[] = [
                    'id'    => $teacher->id,
                    'name'  => $teacher->name,
                    'image' => ($teacher->image && file_exists($teacher->image))
                                    ? asset($teacher->image)
                                    : null,
                    'initial' => strtoupper(substr($teacher->name ?? 'T', 0, 1)),
                ];
            } else {
                // Present — check if late
                if (isLateIn($log->in_time)) {
                    $lateTeachers[] = [
                        'id'      => $teacher->id,
                        'name'    => $teacher->name,
                        'image'   => ($teacher->image && file_exists($teacher->image))
                                        ? asset($teacher->image)
                                        : null,
                        'initial' => strtoupper(substr($teacher->name ?? 'T', 0, 1)),
                        'in_time' => \Carbon\Carbon::parse($log->in_time)->format('h:i A'),
                    ];
                }
            }
        }

        return response()->json([
            'date'            => Carbon::parse($date)->format('d M, Y'),
            'absent_teachers' => $absentTeachers,
            'late_teachers'   => $lateTeachers,
        ]);
    }
}
