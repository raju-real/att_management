<?php

namespace App\Services;

use App\Models\Teacher;
use App\Services\AttendanceReportService as Report;
use Illuminate\Support\Carbon;

/**
 * "Month & Day Wise Summary" — the monthly teacher attendance sheet:
 * one section per shift, two rows per teacher (In Time / Out Time), one
 * column per day, then Total Present / Absent / Late / Over Time / %.
 *
 * Built from AttendanceReportService::dailyQuery(), so work dates, night
 * shifts, late / early-out rules are exactly the same as every other
 * report. The same data feeds the web page, the PDF and the Excel file.
 */
class MonthlySheetService
{
    /** Weekday labels as on the printed sheet. */
    public const DAY_ABBR = ['Su', 'M', 'Tu', 'W', 'Th', 'Fr', 'Sa'];

    /** Longest date range one sheet may cover (every day is a column). */
    public const MAX_DAYS = 62;

    /** Whole calendar month (kept for callers that think in months). */
    public static function build(int $year, int $month, array $filters = []): array
    {
        $start = Carbon::create($year, $month, 1);
        return self::buildRange($start->toDateString(), $start->copy()->endOfMonth()->toDateString(), $filters);
    }

    /**
     * Sheet for any date range (max MAX_DAYS days).
     *
     * @return array{
     *   from:string, to:string, period_label:string, day_count:int, is_full_month:bool,
     *   year:int, month:int, month_name:string, range_label:string, start:string, end:string,
     *   days: array<string, array{day:int, date:string, abbr:string, month_mark:?string, holiday:bool, future:bool}>,
     *   working_days:int, filter_label:?string, sections: array<int, array>
     * }
     */
    public static function buildRange(string $from, string $to, array $filters = []): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($start->diffInDays($end) + 1 > self::MAX_DAYS) {
            $end = $start->copy()->addDays(self::MAX_DAYS - 1);
        }
        $today = Carbon::today();

        $weekly = collect(weeklyHolidays())->map(fn ($d) => strtolower($d));
        $office = collect(officeHolidays());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[$d->toDateString()] = [
                'day'        => $d->day,
                'date'       => $d->toDateString(),
                'abbr'       => self::DAY_ABBR[$d->dayOfWeek],
                // month name over the first column and every 1st, so a range
                // across months stays readable
                'month_mark' => ($d->equalTo($start) || $d->day === 1) ? $d->format('M') : null,
                'holiday'    => $weekly->contains(strtolower($d->format('l'))) || $office->contains($d->toDateString()),
                'future'     => $d->gt($today),
            ];
        }
        $workingDays = collect($days)->filter(fn ($d) => !$d['holiday'] && !$d['future'])->count();

        $deptId  = !empty($filters['department_id']) ? (int) $filters['department_id'] : null;
        $shiftId = !empty($filters['shift_id']) ? (int) $filters['shift_id'] : null;
        $institute = Report::instituteFilter($filters); // forced for institute users

        // Every teacher in scope (also those without any punch in the range).
        $teachers = Teacher::query()
            ->leftJoin('departments as d', 'd.id', '=', 'teachers.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->leftJoin('users as ins', 'ins.id', '=', 'teachers.institute_id')
            ->when($institute, fn ($q) => $q->where('teachers.institute_id', $institute))
            ->when($deptId, fn ($q) => $q->where('teachers.department_id', $deptId))
            ->when($shiftId, fn ($q) => $q->where('d.shift_id', $shiftId))
            ->orderBy('ins.name')
            ->orderByRaw('sh.in_time IS NULL, sh.in_time')
            ->orderByRaw('CAST(teachers.teacher_no AS UNSIGNED), teachers.teacher_no')
            ->get([
                'teachers.id', 'teachers.institute_id', 'ins.name as institute_name',
                'teachers.teacher_no', 'teachers.name', 'teachers.designation', 'd.name as department',
                'sh.id as shift_id', 'sh.title as shift_title', 'sh.in_time as shift_in', 'sh.out_time as shift_out',
            ]);

        $rows = Report::dailyQuery($start->toDateString(), $end->toDateString(), array_filter([
            'user_type' => 'teacher', 'department_id' => $deptId, 'shift_id' => $shiftId, 'institute_id' => $institute,
        ]))->get()->keyBy(fn ($r) => $r->teacher_id . '|' . $r->att_date); // device IDs repeat across institutes

        $sections = [];
        // one section per shift (per institute + shift when the admin views all institutes)
        foreach ($teachers->groupBy(fn ($t) => (int) $t->institute_id . '|' . (int) ($t->shift_id ?? 0)) as $group) {
            $first = $group->first();
            $title = $first->shift_title ? $first->shift_title : 'No Shift Assigned';
            $section = [
                'title'  => $institute ? $title : ($first->institute_name ?: 'No Institute') . ' - ' . $title,
                'timing' => $first->shift_in
                    ? Report::shiftClock($first->shift_in) . ' - ' . Report::shiftClock($first->shift_out, $first->shift_in)
                    : null,
                'teachers' => [],
            ];

            foreach ($group->values() as $i => $t) {
                $section['teachers'][] = self::teacherLine($i + 1, $t, $days, $rows, $workingDays);
            }
            $sections[] = $section;
        }

        $fullMonth = $start->day === 1 && $end->equalTo($start->copy()->endOfMonth()->startOfDay());
        $deptName  = $deptId ? \App\Models\Department::whereKey($deptId)->value('name') : null;

        return [
            'from'          => $start->toDateString(),
            'to'            => $end->toDateString(),
            'period_label'  => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
            'day_count'     => count($days),
            'is_full_month' => $fullMonth,
            'year'          => $start->year,
            'month'         => $start->month,
            'month_name'    => $start->format('F'),
            'range_label'   => $fullMonth
                ? $start->format('F y') . ' (' . $start->format('d/m/y') . ' – ' . $end->format('d/m/y') . ')'
                : $start->format('d/m/y') . ' – ' . $end->format('d/m/y'),
            'start'         => $start->toDateString(),
            'end'           => $end->toDateString(),
            'days'          => $days,
            'working_days'  => $workingDays,
            'filter_label'  => $deptName ? 'Department: ' . $deptName : null,
            'institute_id'  => $institute,
            'sections'      => $sections,
        ];
    }

    protected static function teacherLine(int $sl, $t, array $days, $rows, int $workingDays): array
    {
        $cells = [];
        $present = $presentOnWorkingDays = $absent = $late = $overtime = 0;

        foreach ($days as $d => $day) {
            $r = $rows->get($t->id . '|' . $day['date']);

            if ($r) {
                $present++;
                if (!$day['holiday'] && !$day['future']) {
                    $presentOnWorkingDays++;
                }
                if ($r->is_late) {
                    $late++;
                }
                // Over time = worked past the shift's out time.
                if ($r->out_time && $r->sched_out && $r->out_time > $r->sched_out) {
                    $overtime += (int) floor((strtotime($r->out_time) - strtotime($r->sched_out)) / 60);
                }
                $cells[$d] = [
                    'type'      => 'present',
                    'in'        => self::time($r->in_time, $day['date']),
                    'out'       => self::time($r->out_time, $day['date']),
                    'late'      => (bool) $r->is_late,
                    'early_out' => (bool) $r->is_early_out,
                ];
            } elseif ($day['future']) {
                $cells[$d] = ['type' => 'future'];
            } elseif ($day['holiday']) {
                $cells[$d] = ['type' => 'holiday'];
            } else {
                $absent++;
                $cells[$d] = ['type' => 'absent'];
            }
        }

        return [
            'sl'          => $sl,
            'no'          => $t->teacher_no,
            'name'        => $t->name,
            'designation' => $t->designation,
            'department'  => $t->department,
            'cells'       => $cells,
            'present'     => $present,
            'absent'      => $absent,
            'late'        => $late,
            'overtime'    => $overtime > 0 ? sprintf('%d:%02d', intdiv($overtime, 60), $overtime % 60) : '-',
            'percent'     => $workingDays > 0 ? round(min(100, $presentOnWorkingDays / $workingDays * 100), 1) : null,
        ];
    }

    /** "07:55", or "06:05+" for a punch on the next day; "-" if missing. */
    protected static function time($datetime, string $workDate): string
    {
        if (empty($datetime)) {
            return '-';
        }
        $dt = Carbon::parse($datetime);
        return $dt->format('H:i') . ($dt->toDateString() > $workDate ? '+' : '');
    }
}
