<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class AttendanceService.
 */
class AttendanceService
{
    public static function getDailyAttendance(array $filters = [])
    {
        $from = $filters['from_date'] ?? Carbon::today()->toDateString();
        $to = $filters['to_date'] ?? $filters['from_date'] ?? Carbon::today()->toDateString();

        return AttendanceLog::query()
            ->select([
                'user_type',
                DB::raw("CASE WHEN user_type = 'student' THEN student_no ELSE teacher_no END AS user_no"),
                'name',
                DB::raw('DATE(punch_time) as attendance_date'),
                DB::raw('MIN(punch_time) as in_time'),
                DB::raw('MAX(punch_time) as out_time'),
                DB::raw('COUNT(*) as total_punches'),
            ])
            ->whereBetween(DB::raw('DATE(punch_time)'), [$from, $to])
            ->when($filters['user_type'] ?? null, fn($q, $type) => $q->where('user_type', $type))
            ->when($filters['user_no'] ?? null, fn($q, $userNo) => $q->where('student_no', $userNo)->orWhere('teacher_no', $userNo))
            ->when($filters['student_no'] ?? null, fn($q, $teacherNo) => $q->where('student_no', $teacherNo))
            ->when($filters['teacher_no'] ?? null, fn($q, $teacherNo) => $q->where('teacher_no', $teacherNo))
            ->groupBy(
                'user_type',
                DB::raw('attendance_date'),
                'name',
                DB::raw('user_no')
            )
            ->orderBy('attendance_date', 'desc')
            ->paginate(100);
    }

    public static function attendanceReport(array $filters = [])
    {
        /**
         * ----------------------------
         * 1️⃣ Resolve date range
         * ----------------------------
         */
        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        if (!$from && !$to) {
            $from = $to = now()->toDateString();
        }

        if ($from && !$to) {
            $to = $from;
        }

        /**
         * ----------------------------
         * 2️⃣ Attendance aggregation
         * ----------------------------
         */
        $attendance = AttendanceLog::query()
            ->selectRaw("
            user_type,
            student_no,
            student_id,
            teacher_no,
            DATE(punch_time) as attendance_date,
            MIN(punch_time) as in_time,
            MAX(punch_time) as out_time
        ")
            ->whereBetween(
                DB::raw('DATE(punch_time)'),
                [$from, $to]
            )
            ->when($filters['user_type'] ?? null, fn($q, $v) => $q->where('user_type', $v)
            )
            ->when($filters['student_no'] ?? null, fn($q, $v) => $q->where('student_no', $v)
            )
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('student_id', $v)
            )
            ->when($filters['teacher_no'] ?? null, fn($q, $v) => $q->where('teacher_no', $v)
            )
            ->groupBy(
                'user_type',
                'student_no',
                'student_id',
                'teacher_no',
                DB::raw('DATE(punch_time)')
            )
            ->get()
            ->keyBy(fn($row) => "{$row->user_type}-" .
                ($row->student_no ?? $row->teacher_no) .
                "-{$row->attendance_date}"
            );

        /**
         * ----------------------------
         * 3️⃣ Expected users
         * ----------------------------
         */
        $students = Student::query()
            ->when($filters['student_no'] ?? null, fn($q, $v) => $q->where('student_no', $v)
            )
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('student_id', $v)
            )
            ->get();

        $teachers = Teacher::query()
            ->when($filters['teacher_no'] ?? null, fn($q, $v) => $q->where('teacher_no', $v)
            )
            ->get();

        /**
         * ----------------------------
         * 4️⃣ Final report (PRESENT + ABSENT)
         * ----------------------------
         */
        $report = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {

            $day = $date->toDateString();

            // Students
            foreach ($students as $student) {
                $key = "student-{$student->student_no}-{$day}";
                $log = $attendance->get($key);

                $report[] = [
                    'date' => $day,
                    'user_type' => 'student',
                    'student_no' => $student->student_no,
                    'student_id' => $student->student_id,
                    'teacher_no' => null,
                    'status' => $log ? 'PRESENT' : 'ABSENT',
                    'in_time' => $log?->in_time,
                    'out_time' => $log?->out_time,
                ];
            }

            // Teachers
            foreach ($teachers as $teacher) {
                $key = "teacher-{$teacher->teacher_no}-{$day}";
                $log = $attendance->get($key);

                $report[] = [
                    'date' => $day,
                    'user_type' => 'teacher',
                    'student_no' => null,
                    'student_id' => null,
                    'teacher_no' => $teacher->teacher_no,
                    'status' => $log ? 'PRESENT' : 'ABSENT',
                    'in_time' => $log?->in_time,
                    'out_time' => $log?->out_time,
                ];
            }
        }

        return collect($report);
    }
}
