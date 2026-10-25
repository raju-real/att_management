<?php

namespace App\Services;

use App\Models\Teacher;
use App\Services\AttendanceReportService as Report;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Data for the Teacher/Staff Profile (page + PDF): one row per month of the
 * chosen year, built from the same per-day attendance rows as every other
 * report (AttendanceReportService::dailyQuery — shift rules, night shifts,
 * late / early out), so the numbers always match the other reports.
 *
 * Per month:
 *  - Total Present = days with attendance
 *  - Total Absent  = working days (no weekly / office holiday, not in the
 *                    future, not before the joining date) without attendance
 *  - Total Late    = sum of late time (h:mm)
 *  - Late Present  = days present but late
 *  - Over Time     = time worked after the shift out time (h:mm)
 *  - Leave         = no leave module yet → "-"
 *  - Percentage    = present on working days / working days
 */
class TeacherProfileService
{
    public const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
        'August', 'September', 'October', 'November', 'December'];

    /**
     * @return array{year:int, months: array<int, array>, total: array}
     */
    public static function yearly(Teacher $teacher, int $year): array
    {
        $start = Carbon::create($year, 1, 1);
        $end   = Carbon::create($year, 12, 31);
        $today = Carbon::today();

        // Every punch day of this teacher in the year (only this institute's logs).
        $rows = Report::dailyQuery($start->toDateString(), $end->toDateString(), [
            'user_type'    => 'teacher',
            'teacher_no'   => (string) $teacher->teacher_no,
            'institute_id' => $teacher->institute_id,
        ])->get()
            ->where('teacher_id', $teacher->id)
            ->keyBy('att_date');

        $weekly  = collect(weeklyHolidays())->map(fn ($d) => strtolower($d));
        $office  = collect(officeHolidays());
        $joining = $teacher->joining_date ? Carbon::parse($teacher->joining_date)->startOfDay() : null;

        $months = [];
        $total  = ['present' => 0, 'absent' => 0, 'late_min' => 0, 'late_days' => 0, 'ot_min' => 0, 'working' => 0, 'present_working' => 0];

        foreach (range(1, 12) as $m) {
            $mStart = Carbon::create($year, $m, 1);
            $mEnd   = $mStart->copy()->endOfMonth();

            $present = $absent = $lateMin = $lateDays = $otMin = $working = $presentWorking = 0;
            $started = $mStart->lte($today);

            if ($started) {
                foreach (CarbonPeriod::create($mStart, $mEnd->min($today)) as $day) {
                    $date      = $day->toDateString();
                    $r         = $rows->get($date);
                    $isHoliday = $weekly->contains(strtolower($day->format('l'))) || $office->contains($date);
                    $counts    = ! $isHoliday && (! $joining || $day->gte($joining));

                    if ($counts) {
                        $working++;
                    }
                    if ($r) {
                        $present++;
                        if ($counts) {
                            $presentWorking++;
                        }
                        if ($r->is_late) {
                            $lateDays++;
                            $lateMin += (int) $r->late_minutes;
                        }
                        if ($r->out_time && $r->sched_out && $r->out_time > $r->sched_out) {
                            $otMin += (int) floor((strtotime($r->out_time) - strtotime($r->sched_out)) / 60);
                        }
                    } elseif ($counts) {
                        $absent++;
                    }
                }
            }

            $months[$m] = [
                'name'       => self::MONTHS[$m - 1],
                'started'    => $started,
                'present'    => $started ? $present : null,
                'absent'     => $started ? $absent : null,
                'late'       => $started ? self::hm($lateMin) : null,
                'late_days'  => $started ? $lateDays : null,
                'overtime'   => $started ? self::hm($otMin) : null,
                'leave'      => null,
                'percent'    => $started && $working > 0 ? round(min(100, $presentWorking / $working * 100), 1) : null,
                'working'    => $working,
            ];

            foreach (['present' => $present, 'absent' => $absent, 'late_min' => $lateMin, 'late_days' => $lateDays,
                      'ot_min' => $otMin, 'working' => $working, 'present_working' => $presentWorking] as $k => $v) {
                $total[$k] += $v;
            }
        }

        return [
            'year'   => $year,
            'months' => $months,
            'total'  => [
                'present'   => $total['present'],
                'absent'    => $total['absent'],
                'late'      => self::hm($total['late_min']),
                'late_days' => $total['late_days'],
                'overtime'  => self::hm($total['ot_min']),
                'working'   => $total['working'],
                'percent'   => $total['working'] > 0 ? round(min(100, $total['present_working'] / $total['working'] * 100), 1) : null,
            ],
        ];
    }

    /** 125 → "2:05", 0 → "-". */
    protected static function hm(int $minutes): string
    {
        return $minutes > 0 ? sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60) : '-';
    }
}
