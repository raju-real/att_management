<?php

namespace App\Services;

use App\Services\AttendanceReportService as Report;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Leave lookups for every report — the ONE place that decides which days a
 * teacher/staff member is on leave, so all reports agree.
 *
 * Status of a person on a day (same order everywhere):
 *   P  Present  — has attendance (a punch wins even inside a leave)
 *   H  Holiday  — weekly / office holiday of the institute
 *   L  Leave    — inside an approved leave (holidays in it are not leave days)
 *   A  Absent   — a working day with none of the above
 */
class LeaveService
{
    public const CODES = [
        'P' => ['Present', '#047857'],
        'A' => ['Absent',  '#dc2626'],
        'L' => ['Leave',   '#d97706'],
        'H' => ['Holiday', '#6b7280'],
    ];

    /** Days of [from, to] that are not holidays of the institute. */
    public static function countDays(?int $instituteId, string $from, string $to): int
    {
        $from = Carbon::parse($from)->startOfDay();
        $to   = Carbon::parse($to)->startOfDay();
        if ($to->lt($from)) {
            return 0;
        }
        $isHoliday = holidayChecker($instituteId);
        $n = 0;
        foreach (CarbonPeriod::create($from, $to) as $day) {
            if (! $isHoliday($day)) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Leave days in [from, to]:  [teacher_id => [Y-m-d => ['type' => 'Casual Leave', 'code' => 'CL']]]
     * Holidays of the teacher's institute inside a leave are left out.
     *
     * @param int|null   $institute  null = all institutes (super admin)
     * @param int[]|null $teacherIds limit to these teachers
     */
    public static function dayMap(string $from, string $to, ?int $institute = null, ?array $teacherIds = null): array
    {
        $from = Carbon::parse($from)->toDateString();
        $to   = Carbon::parse($to)->toDateString();
        if ($teacherIds !== null && ! $teacherIds) {
            return [];
        }

        $rows = DB::table('leaves as l')
            ->leftJoin('leave_types as lt', 'lt.id', '=', 'l.leave_type_id')
            ->whereNull('l.deleted_at')
            ->where('l.from_date', '<=', $to)
            ->where('l.to_date', '>=', $from)
            ->when($institute, fn ($q, $v) => $q->where('l.institute_id', $v))
            ->when($teacherIds !== null, fn ($q) => $q->whereIn('l.teacher_id', $teacherIds))
            ->orderBy('l.from_date')
            ->get(['l.teacher_id', 'l.institute_id', 'l.from_date', 'l.to_date', 'lt.name as type', 'lt.code']);

        $map = [];
        $holidays = [];
        foreach ($rows as $r) {
            $iid = $r->institute_id ? (int) $r->institute_id : null;
            $isHoliday = $holidays[$iid ?? 0] ??= holidayChecker($iid);
            $start = max($r->from_date, $from);
            $end   = min($r->to_date, $to);
            foreach (CarbonPeriod::create($start, $end) as $day) {
                if (! $isHoliday($day)) {
                    $map[(int) $r->teacher_id][$day->toDateString()] = ['type' => $r->type ?? 'Leave', 'code' => $r->code];
                }
            }
        }
        return $map;
    }

    /**
     * Per teacher for [from, to] (future days not counted), from the same
     * attendance rows as the reports:
     *   [teacher_id => ['working' => n, 'present' => n (on working days), 'leave' => n, 'absent' => n]]
     *
     * Leave = leave days without attendance; Absent = working days with neither.
     */
    public static function teacherCounts(string $from, string $to, ?int $institute = null): array
    {
        $end = Carbon::parse($to)->min(Carbon::today());
        if ($end->lt(Carbon::parse($from))) {
            return [];
        }
        $to = $end->toDateString();

        $teachers = DB::table('teachers')->whereNull('deleted_at')
            ->when($institute, fn ($q, $v) => $q->where('institute_id', $v))
            ->get(['id', 'institute_id', 'joining_date']);

        // days with attendance, per teacher
        $present = [];
        $daily = DB::query()->fromSub(Report::dailyQuery($from, $to, array_filter([
            'user_type' => 'teacher', 'institute_id' => $institute,
        ])), 'x')->whereNotNull('x.teacher_id')->get(['x.teacher_id', 'x.att_date']);
        foreach ($daily as $r) {
            $present[(int) $r->teacher_id][$r->att_date] = true;
        }

        $leaves = self::dayMap($from, $to, $institute);
        $days   = [];
        $out    = [];
        foreach ($teachers as $t) {
            $iid = $t->institute_id ? (int) $t->institute_id : 0;
            if (! isset($days[$iid])) {
                $isHoliday  = holidayChecker($iid ?: null);
                $days[$iid] = collect(CarbonPeriod::create($from, $to))
                    ->reject(fn ($d) => $isHoliday($d))
                    ->map(fn ($d) => $d->toDateString())->values()->all();
            }

            $c = ['working' => 0, 'present' => 0, 'leave' => 0, 'absent' => 0];
            foreach ($days[$iid] as $d) {
                if ($t->joining_date && $d < $t->joining_date) {
                    continue; // not yet joined
                }
                $c['working']++;
                if (isset($present[$t->id][$d])) {
                    $c['present']++;
                } elseif (isset($leaves[$t->id][$d])) {
                    $c['leave']++;
                } else {
                    $c['absent']++;
                }
            }
            $out[(int) $t->id] = $c;
        }
        return $out;
    }

    /** Another leave of this teacher overlapping [from, to]? */
    public static function overlapping(int $teacherId, string $from, string $to, ?int $ignoreId = null): ?object
    {
        return DB::table('leaves')->whereNull('deleted_at')
            ->where('teacher_id', $teacherId)
            ->where('from_date', '<=', $to)
            ->where('to_date', '>=', $from)
            ->when($ignoreId, fn ($q, $v) => $q->where('id', '!=', $v))
            ->first(['id', 'from_date', 'to_date']);
    }

    /**
     * Holidays of institutes for the leave form's live day count:
     * [institute_id => ['weekly' => ['friday', ...], 'office' => ['2026-12-16', ...]]]
     */
    public static function holidayData(iterable $instituteIds): array
    {
        $out = [];
        foreach ($instituteIds as $id) {
            $out[(int) $id] = [
                'weekly' => array_map('strtolower', weeklyHolidays((int) $id)),
                'office' => array_values(officeHolidays((int) $id)),
            ];
        }
        return $out;
    }
}
