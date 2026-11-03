<?php

namespace App\Http\Controllers;

/**
 * Dashboard = live attendance display of ONE institute (photo spotlight,
 * absent list scrolling up, present list scrolling left, today's totals).
 *
 * An institute user always sees its own institute. The super admin sees an
 * empty page until an institute is chosen in the dropdown.
 *
 * The data comes from the same JSON as the attendance boards
 * (AttendanceBoardController::data → AttendanceReportService::teacherBoard),
 * so the numbers always match.
 */
class DashboardController extends Controller
{
    public function dashboard()
    {
        $institute = selectedInstituteId(); // forced for institute users
        $school    = $institute ? schoolInfo($institute) : null;

        // department / shift filters of this institute
        $departments = $institute ? \App\Models\Department::forInstitute($institute)->orderBy('name')->get(['id', 'name', 'shift_id']) : collect();
        $shifts      = $institute ? \App\Models\Shift::forInstitute($institute)->orderBy('in_time')->get(['id', 'title', 'in_time', 'out_time']) : collect();

        // teachers/staff of this institute for the select2 "show in the centre" dropdown
        $teachers = $institute
            ? \App\Models\Teacher::forInstitute($institute)->orderBy('name')->get(['id', 'name', 'teacher_no'])
            : collect();

        return view('dashboard', compact('institute', 'school', 'departments', 'shifts', 'teachers'));
    }
}
