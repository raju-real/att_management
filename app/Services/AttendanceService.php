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
            ->when($filters['student_id'] ?? null, fn($q, $teacherNo) => $q->where('student_id', $teacherNo))
            ->when($filters['teacher_no'] ?? null, fn($q, $teacherNo) => $q->where('teacher_no', $teacherNo))
            ->groupBy(
                'user_type',
                DB::raw('attendance_date'),
                'name',
                DB::raw('user_no')
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
            ->keyBy(fn($row) => "{$row->user_type}-" .
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
                    $report = $report->filter(fn($row) => $row['status'] === 'Present' && !empty($row['in_time']) && isLateIn($row['in_time']));
                } elseif ($status === 'early-out' && !empty(siteSettings()->out_time)) {
                    $standardOut = Carbon::createFromFormat('H:i:s', siteSettings()->out_time);
                    $report = $report->filter(fn($row) => $row['status'] === 'Present'
                        && !empty($row['out_time'])
                        && Carbon::parse($row['out_time'])->format('H:i:s') < $standardOut->format('H:i:s')
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
        $from = $filters['from_date'] ?? Carbon::today()->toDateString();
        $to = $filters['to_date'] ?? $from;

        $dataType = $filters['data_type'] ?? 'all_days'; // default

        /**
         * ----------------------------
         * 2️⃣ Fetch PRESENT attendance
         * ----------------------------
         */
        $rows = AttendanceLog::query()
            ->selectRaw("
            DATE_FORMAT(punch_time, '%Y-%m-%d') as attendance_date,
            user_type,
            name,
            COALESCE(student_no, teacher_no) as user_no,
            MIN(punch_time) as in_time,
            MAX(punch_time) as out_time,
            COUNT(*) as punch_count
        ")
            ->whereDate('punch_time', '>=', $from)
            ->whereDate('punch_time', '<=', $to)
            ->when($filters['user_type'] ?? null,
                fn($q, $v) => $q->where('user_type', $v)
            )
            ->when($filters['student_id'] ?? null,
                fn($q, $v) => $q->where('student_id', $v)
                    ->orWhere('student_no', $v)
            )
            ->when($filters['teacher_no'] ?? null,
                fn($q, $v) => $q->where('teacher_no', $v)
            )
            ->groupBy(
                DB::raw("DATE_FORMAT(punch_time, '%Y-%m-%d')"),
                'user_type',
                'name',
                DB::raw('COALESCE(student_no, teacher_no)')
            )
            ->orderBy('attendance_date')
            ->get()
            ->groupBy('attendance_date');

        /**
         * ----------------------------
         * 3️⃣ Build report by date type
         * ----------------------------
         */
        $report = collect();

        foreach (CarbonPeriod::create($from, $to) as $date) {

            $day = $date->format('Y-m-d');
            $dayRows = $rows->get($day, collect());

            /**
             * 🔹 working_days → skip empty dates
             */
            if ($dataType === 'working_days' && $dayRows->isEmpty()) {
                continue;
            }

            if ($dataType === 'of_days' && $dayRows->isNotEmpty()) {
                continue;
            }

            /**
             * 🔹 all_days → include empty dates
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

    public static function monthWiseUserSummery(array $filters = [])
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


    protected static function calculateHours($in, $out)
    {
        if (!$in || !$out) return null;

        $minutes = Carbon::parse($in)->diffInMinutes(Carbon::parse($out));

        return number_format($minutes / 60, 2); // 7.50 hours
    }

}
