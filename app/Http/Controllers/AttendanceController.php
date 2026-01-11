<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function App\Http\Controllers\Admin\ucfirst;

class AttendanceController extends Controller
{
    public function attendanceLogs(Request $request)
    {
        $filter = [];
        $filter['user_type'] = request()->get('user_type') ?? '';
        $filter['user_no'] = request()->get('user_no') ?? '';
        $filter['student_no'] = request()->get('student_no') ?? '';
        $filter['teacher_no'] = request()->get('teacher_no') ?? '';
        $filter['from_date'] = request()->get('from_date') ?? Carbon::today()->toDateString();
        $filter['to_date'] = request()->get('to_date');
        //$report = $this->attendanceReport($filter);
        $attendance_logs = AttendanceService::getDailyAttendance($filter);
        $from_date = $filter['from_date'];
        $to_date = $filter['to_date'];
        return view('attendance.attendance_logs', compact('attendance_logs','from_date','to_date'));
    }

}
