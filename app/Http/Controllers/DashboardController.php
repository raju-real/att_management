<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceReportService as Report;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        // institute users: own data only (InstituteScope); the admin may pick one institute
        $institute = selectedInstituteId();

        $total_students = Student::forInstitute($institute)->count();
        $total_teachers = Teacher::forInstitute($institute)->count();
        $total_devices  = Device::active()->forInstitute($institute)->count();

        // Index-friendly range on punch_time (no DATE() wrapper).
        $today_present = DB::table('attendance_logs')
            ->where('punch_time', '>=', $today->format('Y-m-d 00:00:00'))
            ->where('punch_time', '<', $today->copy()->addDay()->format('Y-m-d 00:00:00'))
            ->whereRaw(Report::USER_NO_SQL . ' IS NOT NULL')
            ->when($institute, fn ($q, $v) => $q->where('institute_id', $v))
            ->distinct()
            ->count(DB::raw('institute_id, ' . Report::USER_NO_SQL));

        $today_logs = Report::logs(array_filter(['date' => $today->toDateString(), 'sort' => 'in_asc', 'institute_id' => $institute]), 10);

        return view('dashboard', compact(
            'total_students',
            'total_teachers',
            'total_devices',
            'today_present',
            'today_logs'
        ));
    }
}
