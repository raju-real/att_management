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

    /**
     * @return array{
     *   year:int, month:int, month_name:string, range_label:string, start:string, end:string,
     *   days: array<int, array{day:int, date:string, abbr:string, holiday:bool, future:bool}>,
     *   working_days:int, sections: array<int, array>
     * }
     */
    public static function build(int $year, int $month, array $filters = []): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->startOfDay();
        $today = Carbon::today();

        $weekly = collect(weeklyHolidays())->map(fn ($d) => strtolower($d));
        $office = collect(officeHolidays());

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[$d->day] = [
                'day'     => $d->day,
                'date'    => $d->toDateString(),
                'abbr'    => self::DAY_ABBR[$d->dayOfWeek],
                'holiday' => $weekly->contains(strtolower($d->format('l'))) || $office->contains($d->toDateString()),
                'future'  => $d->gt($today),
            ];
        }
        $workingDays = collect($days)->filter(fn ($d) => !$d['holiday'] && !$d['future'])->count();

        $deptId  = !empty($filters['department_id']) ? (int) $filters['department_id'] : null;
        $shiftId = !empty($filters['shift_id']) ? (int) $filters['shift_id'] : null;

        // Every teacher in scope (also those without any punch this month).
        $teachers = Teacher::query()
            ->leftJoin('departments as d', 'd.id', '=', 'teachers.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->when($deptId, fn ($q) => $q->where('teachers.department_id', $deptId))
            ->when($shiftId, fn ($q) => $q->where('d.shift_id', $shiftId))
            ->orderByRaw('sh.in_time IS NULL, sh.in_time')
            ->orderByRaw('CAST(teachers.teacher_no AS UNSIGNED), teachers.teacher_no')
            ->get([
                'teachers.teacher_no', 'teachers.name', 'teachers.designation', 'd.name as department',
                'sh.id as shift_id', 'sh.title as shift_title', 'sh.in_time as shift_in', 'sh.out_time as shift_out',
            ]);

        $rows = Report::dailyQuery($start->toDateString(), $end->toDateString(), array_filter([
            'user_type' => 'teacher', 'department_id' => $deptId, 'shift_id' => $shiftId,
        ]))->get()->keyBy(fn ($r) => $r->user_no . '|' . $r->att_date);

        $sections = [];
        foreach ($teachers->groupBy(fn ($t) => (string) ($t->shift_id ?? 0)) as $group) {
            $first = $group->first();
            $section = [
                'title'  => $first->shift_title ? $first->shift_title : 'No Shift Assigned',
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

        return [
            'year'         => $year,
            'month'        => $month,
            'month_name'   => $start->format('F'),
            'range_label'  => $start->format('F y') . ' (' . $start->format('d/m/y') . ' – ' . $end->format('d/m/y') . ')',
            'start'        => $start->toDateString(),
            'end'          => $end->toDateString(),
            'days'         => $days,
            'working_days' => $workingDays,
            'sections'     => $sections,
        ];
    }

    protected static function teacherLine(int $sl, $t, array $days, $rows, int $workingDays): array
    {
        $cells = [];
        $present = $presentOnWorkingDays = $absent = $late = $overtime = 0;

        foreach ($days as $d => $day) {
            $r = $rows->get($t->teacher_no . '|' . $day['date']);

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
