<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class AttendanceService.
 */
class AttendanceService
{
    // ======================= Log View Part ====================
    public static function getDailyAttendance(array $filters = [], $paginate = 50)
    {
        $from = $filters['from_date'] ?? Carbon::today()->toDateString();
        $to   = $filters['to_date'] ?? $filters['from_date'] ?? Carbon::today()->toDateString();

        return AttendanceLog::query()
            ->selectRaw("
                al.user_type,
                COALESCE(al.student_no, al.teacher_no) AS user_no,
                COALESCE(
                    NULLIF(TRIM(CONCAT(COALESCE(MIN(s.firstname),''),' ',COALESCE(MIN(s.middlename),''),' ',COALESCE(MIN(s.lastname),''))), ''),
                    MIN(t.name),
                    MIN(al.name),
                    '(Unknown)'
                ) AS name,
                DATE(al.punch_time) as attendance_date,
                MIN(al.punch_time) as in_time,
                MAX(al.punch_time) as out_time,
                COUNT(*) as total_punches,
                MIN(sh.in_time) as shift_in_time,
                MIN(sh.out_time) as shift_out_time
            ")
            ->from('attendance_logs as al')
            ->leftJoin('students as s', 's.student_no', '=', 'al.student_no')
            ->leftJoin('teachers as t', 't.teacher_no', '=', 'al.teacher_no')
            ->leftJoin('shifts as sh', 'sh.id', '=', 't.shift_id')
            ->whereNull('al.deleted_at')
            ->whereBetween(DB::raw('DATE(al.punch_time)'), [$from, $to])
            ->when($filters['user_type'] ?? null, fn($q, $v) => $q->where('al.user_type', $v))
            ->when($filters['user_no']    ?? null, fn($q, $v) => $q->where('al.student_no', $v)->orWhere('al.teacher_no', $v))
            ->when($filters['student_no'] ?? null, fn($q, $v) => $q->where('al.student_no', $v))
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('s.student_id', $v))
            ->when($filters['teacher_no'] ?? null, fn($q, $v) => $q->where('al.teacher_no', $v))
            ->groupBy(
                'al.user_type',
                DB::raw('COALESCE(al.student_no, al.teacher_no)'),
                DB::raw('DATE(al.punch_time)')
            )
            ->orderBy('attendance_date', 'desc')
            ->paginate($paginate);
    }


    public static function attendanceSummery(array $filters = [])
    {
        /**
         * ----------------------------
         * 1️⃣ Resolve date range
         * ----------------------------
         */
        $from = $filters['from_date'] ?? Carbon::today()->toDateString();
        $to = $filters['to_date'] ?? $from;

        /**
         * ----------------------------
         * 2️⃣ Attendance aggregation (PRESENT ONLY)
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
            ->whereBetween(DB::raw('DATE(punch_time)'), [$from, $to])
            ->groupBy(
                'user_type',
                'student_no',
                'student_id',
                'teacher_no',
                DB::raw('DATE(punch_time)')
            )
            ->get()
            ->keyBy(
                fn($row) => "{$row->user_type}-" .
                    ($row->student_no ?? $row->teacher_no) .
                    "-{$row->attendance_date}"
            );

        /**
         * ----------------------------
         * 3️⃣ Expected users (RESPECT user_type)
         * ----------------------------
         */
        $students = collect();
        $teachers = collect();

        if (empty($filters['user_type']) || $filters['user_type'] === 'student') {
            $students = Student::query()
                ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('student_no', $v)->orWhere('student_id', $v))
                ->get();
        }

        if (empty($filters['user_type']) || $filters['user_type'] === 'teacher') {
            $teachers = Teacher::query()
                ->with('shift')
                ->when($filters['teacher_no'] ?? null, fn($q, $v) => $q->where('teacher_no', $v))
                ->get();
        }

        /**
         * ----------------------------
         * 4️⃣ Build report (PRESENT + ABSENT)
         * ----------------------------
         */
        $report = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $day = $date->toDateString();

            foreach ($students as $student) {
                $key = "student-{$student->student_no}-{$day}";
                $log = $attendance->get($key);

                $report->push([
                    'attendance_date' => $day,
                    'user_type' => 'student',
                    'student_no' => $student->student_no,
                    'student_id' => $student->student_id,
                    'teacher_no' => null,
                    'name' => showStudentFullName(
                        $student->firstname,
                        $student->middlename,
                        $student->lastname
                    ),
                    'status' => $log ? 'Present' : 'Absent',
                    'in_time' => $log?->in_time,
                    'out_time' => $log?->out_time,
                    'standard_in' => null,
                    'standard_out' => null,
                ]);
            }

            foreach ($teachers as $teacher) {
                $key = "teacher-{$teacher->teacher_no}-{$day}";
                $log = $attendance->get($key);

                $report->push([
                    'attendance_date' => $day,
                    'user_type' => 'teacher',
                    'student_no' => null,
                    'student_id' => null,
                    'teacher_no' => $teacher->teacher_no,
                    'name' => $teacher->name,
                    'status' => $log ? 'Present' : 'Absent',
                    'in_time' => $log?->in_time,
                    'out_time' => $log?->out_time,
                    'standard_in' => $teacher->shift->in_time ?? null,
                    'standard_out' => $teacher->shift->out_time ?? null,
                ]);
            }
        }

        /**
         * ----------------------------
         * 5️⃣ Filter by status
         * ----------------------------
         */
        if (!empty($filters['status'])) {
            $status = strtolower($filters['status']);
            if (in_array($status, ['present', 'absent'])) {
                $report = $report->where('status', ucfirst($status));
            }
        }

        /**
         * ----------------------------
         * 5️⃣ Filter by attendance type
         * ----------------------------
         */
        if (!empty($filters['attendance_type'])) {
            $status = strtolower($filters['attendance_type']);
            if (in_array($status, ['late-in', 'early-out'])) {
                if ($status === 'late-in') {
                    $report = $report->filter(fn($row) => $row['status'] === 'Present' && !empty($row['in_time']) && isLateIn($row['in_time'], $row['standard_in'] ?? null));
                } elseif ($status === 'early-out') {
                    $report = $report->filter(
                        fn($row) => $row['status'] === 'Present'
                            && !empty($row['out_time'])
                            && isEarlyOut($row['out_time'], $row['standard_out'] ?? null)
                    );
                }
            }
        }

        /**
         * ----------------------------
         * 6️⃣ Pagination
         * ----------------------------
         */
        $perPage = $filters['per_page'] ?? 50;
        $page = request()->get('page', 1);

        return new LengthAwarePaginator(
            $report->forPage($page, $perPage)->values(),
            $report->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }



    // ======================= Report Part =======================

    /**
     * Date wise report
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public static function monthWisePresentReport(array $filters = [])
    {
        /**
         * ----------------------------
         * 1️⃣ Resolve date range
         * ----------------------------
         */
        $from = !empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'])->toDateString()
            : Carbon::now()->startOfMonth()->toDateString();

        $to = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : Carbon::now()->toDateString();

        $dataType = $filters['data_type'] ?? 'all_days'; // all_days | working_days | off_days

        /**
         * ----------------------------
         * 2️⃣ Fetch PRESENT attendance
         * ----------------------------
         */
        $rows = AttendanceLog::query()
            ->selectRaw("
                DATE(al.punch_time) as attendance_date,
                al.user_type,
                MIN(al.name) as name,
                COALESCE(al.student_no, al.teacher_no) as user_no,
                MIN(al.punch_time) as in_time,
                MAX(al.punch_time) as out_time,
                COUNT(*) as punch_count,
                MIN(sh.in_time) as shift_in_time,
                MIN(sh.out_time) as shift_out_time
            ")
            ->from('attendance_logs as al')
            ->leftJoin('teachers as t', 't.teacher_no', '=', 'al.teacher_no')
            ->leftJoin('shifts as sh', 'sh.id', '=', 't.shift_id')
            ->whereBetween(DB::raw('DATE(al.punch_time)'), [$from, $to])
            ->when($filters['user_type'] ?? null, fn($q, $v) => $q->where('al.user_type', $v))
            ->when($filters['student_id'] ?? null, fn($q, $v) => $q->where('al.student_id', $v)->orWhere('al.student_no', $v))
            ->when($filters['teacher_no'] ?? null, fn($q, $v) => $q->where('al.teacher_no', $v))
            ->groupBy(
                DB::raw('DATE(al.punch_time)'),
                'al.user_type',
                DB::raw('COALESCE(al.student_no, al.teacher_no)')
            )
            ->get()
            ->groupBy('attendance_date');

        /**
         * ----------------------------
         * 3️⃣ Holiday configuration
         * ----------------------------
         */
        $officeHolidays = collect(officeHolidays()); // dates
        $weeklyHolidays = collect(weeklyHolidays());

        /**
         * ----------------------------
         * 4️⃣ Build final report
         * ----------------------------
         */
        $report = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {

            $day = $date->toDateString();
            $dayName = dayNameFromDate($date);

            $isOfficeHoliday = $officeHolidays->contains($day);
            $isWeeklyHoliday = $weeklyHolidays->contains($dayName);

            $dayRows = $rows->get($day, collect());

            /**
             * 🔹 WORKING DAYS
             * Skip office holidays + weekly holidays + empty days
             * if ($dataType === 'working_days' && $dayRows->isEmpty()) {
             * continue;
             * }
             *
             * if ($dataType === 'of_days' && $dayRows->isNotEmpty()) {
             * continue;
             * }
             */
            // For 'all_days' — include every day (skip only days that are truly empty AND not a holiday)
            // For 'working_days' — skip holidays
            // For 'off_days' — skip working days
            if ($dataType === 'working_days' && ($isOfficeHoliday || $isWeeklyHoliday)) {
                continue;
            }

            if ($dataType === 'off_days' && !$isOfficeHoliday && !$isWeeklyHoliday) {
                continue;
            }

            // For all data types: skip completely empty non-holiday days
            if ($dayRows->isEmpty() && !$isOfficeHoliday && !$isWeeklyHoliday) {
                continue;
            }

            /**
             * 🔹 ALL DAYS → include everything
             */
            $report->put(
                $day,
                $dayRows->map(fn($row) => [
                    'user_type' => $row->user_type,
                    'user_no' => $row->user_no,
                    'name' => $row->name,
                    'in_time' => $row->in_time,
                    'out_time' => $row->out_time,
                    'punch_count' => $row->punch_count,
                    'working_hours' => self::calculateHours($row->in_time, $row->out_time),
                    'standard_in' => $row->shift_in_time,
                    'standard_out' => $row->shift_out_time,
                ])->values()
            );
        }

        return $report;
    }


    /**
     * Month wise user summery
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */

    public static function monthWiseUserSummeryOld(array $filters = [])
    {
        $from = $filters['from_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to = $filters['to_date'] ?? Carbon::now()->toDateString();

        $site_in_time = siteSettings()->in_time ?? '00:00:00';
        $site_out_time = siteSettings()->out_time ?? '00:00:00';

        $query = AttendanceLog::query()
            ->selectRaw("
                user_type,
                COALESCE(student_no, teacher_no) as user_no,
                MIN(punch_time) as first_in,
                MAX(punch_time) as last_out,
                COUNT(*) as total_punch
            ")
            ->whereBetween(DB::raw('DATE(punch_time)'), [$from, $to])
            ->groupBy('user_type', DB::raw('COALESCE(student_no, teacher_no)'))
            ->orderBy('user_type');

        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (!empty($filters['student_no'])) {
            $query->where('student_no', $filters['student_no']);
        }

        if (!empty($filters['teacher_no'])) {
            $query->where('teacher_no', $filters['teacher_no']);
        }

        $attendances = $query->get();

        // Prepare summary
        $summary = $attendances->map(function ($row) use ($site_in_time, $site_out_time) {

            $first_in = Carbon::parse($row->first_in);
            $last_out = Carbon::parse($row->last_out);
            $standard_in = Carbon::parse($site_in_time);
            $standard_out = Carbon::parse($site_out_time);

            return [
                'user_type' => $row->user_type,
                'user_no' => $row->user_no,
                'first_in' => $first_in->format('H:i:s'),
                'last_out' => $last_out->format('H:i:s'),
                'early_in' => $first_in->lt($standard_in) ? $first_in->format('H:i:s') : '-',
                'late_in' => $first_in->gt($standard_in) ? $first_in->format('H:i:s') : '-',
                'early_out' => $last_out->lt($standard_out) ? $last_out->format('H:i:s') : '-',
                'late_out' => $last_out->gt($standard_out) ? $last_out->format('H:i:s') : '-',
                'total_hours' => round($last_out->diffInMinutes($first_in) / 60, 2),
                'total_punch' => $row->total_punch,
            ];
        });

        return $summary;
    }

    public static function monthWiseUserSummery(array $filters = [])
    {
        $from = $filters['from_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $to   = $filters['to_date']   ?? Carbon::now()->toDateString();

        $officeHolidays = officeHolidays();
        $weeklyHolidays = weeklyHolidays();

        // 1️⃣ Fetch attendance — join students/teachers for reliable name
        $query = AttendanceLog::query()
            ->selectRaw("
                al.user_type,
                COALESCE(al.student_no, al.teacher_no) as user_no,
                MIN(al.name) as name,
                DATE(al.punch_time) as punch_date,
                MIN(al.punch_time) as first_in,
                MAX(al.punch_time) as last_out,
                MIN(sh.in_time) as shift_in_time,
                MIN(sh.out_time) as shift_out_time
            ")
            ->from('attendance_logs as al')
            ->leftJoin('teachers as t', 't.teacher_no', '=', 'al.teacher_no')
            ->leftJoin('shifts as sh', 'sh.id', '=', 't.shift_id')
            ->whereBetween(DB::raw('DATE(al.punch_time)'), [$from, $to])
            ->groupBy(
                'al.user_type',
                DB::raw('COALESCE(al.student_no, al.teacher_no)'),
                DB::raw('DATE(al.punch_time)')
            )
            ->orderBy('al.user_type');

        if (!empty($filters['user_type'])) {
            $query->where('al.user_type', $filters['user_type']);
        }
        if (!empty($filters['student_no'])) {
            $query->where('al.student_no', $filters['student_no']);
        }
        if (!empty($filters['teacher_no'])) {
            $query->where('al.teacher_no', $filters['teacher_no']);
        }

        $attendanceRows = $query->get();

        // 2️⃣ Group by user
        $attendanceByUser = $attendanceRows->groupBy('user_no');

        $summary = collect();

        foreach ($attendanceByUser as $user_no => $records) {
            $user_type = $records->first()->user_type;
            // Use first non-null name found
            $name = $records->first(fn($r) => !empty($r->name))?->name ?? 'Unknown';

            $first_in  = $records->min(fn($r) => $r->first_in);
            $late_in   = $records->max(fn($r) => $r->first_in);
            $early_out = $records->min(fn($r) => $r->last_out);
            $late_out  = $records->max(fn($r) => $r->last_out);

            $presentDays  = $records->count();
            $totalMinutes = 0;

            foreach ($records as $r) {
                if ($r->first_in && $r->last_out) {
                    $totalMinutes += Carbon::parse($r->last_out)->diffInMinutes(Carbon::parse($r->first_in));
                }
            }

            $totalHours = round($totalMinutes / 60, 2);

            $allDates = collect(CarbonPeriod::create($from, $to))->map(fn($d) => $d->toDateString());

            $absentDays = $allDates->filter(function ($day) use ($records, $officeHolidays, $weeklyHolidays) {
                $isHoliday    = in_array($day, $officeHolidays)
                    || in_array(Carbon::parse($day)->format('l'), $weeklyHolidays);
                $hasAttendance = $records->contains(fn($r) => $r->punch_date == $day);
                return !$isHoliday && !$hasAttendance;
            })->count();

            $summary->push([
                'user_type'          => $user_type,
                'user_no'            => $user_no,
                'name'               => $name,
                'earliest_in'        => Carbon::parse($first_in)->format('H:i:s'),
                'latest_late_in'     => Carbon::parse($late_in)->format('H:i:s'),
                'earliest_out'       => Carbon::parse($early_out)->format('H:i:s'),
                'latest_late_out'    => Carbon::parse($late_out)->format('H:i:s'),
                'total_present_days' => $presentDays,
                'total_absent_days'  => $absentDays,
                'total_working_hours' => $totalHours,
                'standard_in'        => $records->first()->shift_in_time,
                'standard_out'       => $records->first()->shift_out_time,
            ]);
        }

        return $summary;
    }


    protected static function calculateHours($in, $out)
    {
        if (!$in || !$out) return null;

        $minutes = Carbon::parse($in)->diffInMinutes(Carbon::parse($out));

        return number_format($minutes / 60, 2); // 7.50 hours
    }
}
