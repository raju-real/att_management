<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $total_students = Student::count();
        $total_teachers = Teacher::count();
        $total_devices = Device::active()->count();
        $today_present = AttendanceLog::whereDate('punch_time', today())
            ->select(DB::raw("COALESCE(student_no, teacher_no) as user_no"))
            ->distinct()
            ->get()
            ->count();
        $today_logs = AttendanceService::getDailyAttendance([]);
        return view('dashboard', compact('total_students','total_teachers', 'total_devices', 'today_present', 'today_logs'));
    }
}
