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

        $total_students = Student::count();
        $total_teachers = Teacher::count();
        $total_devices  = Device::active()->count();

        // Index-friendly range on punch_time (no DATE() wrapper).
        $today_present = DB::table('attendance_logs')
            ->where('punch_time', '>=', $today->format('Y-m-d 00:00:00'))
            ->where('punch_time', '<', $today->copy()->addDay()->format('Y-m-d 00:00:00'))
            ->whereRaw(Report::USER_NO_SQL . ' IS NOT NULL')
            ->distinct()
            ->count(DB::raw(Report::USER_NO_SQL));

        $today_logs = Report::logs(['date' => $today->toDateString(), 'sort' => 'in_asc'], 10);

        return view('dashboard', compact(
            'total_students',
            'total_teachers',
            'total_devices',
            'today_present',
            'today_logs'
        ));
    }
}
