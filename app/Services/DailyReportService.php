<?php

namespace App\Services;

use App\Models\Department;
use App\Services\AttendanceReportService as Report;
use Illuminate\Support\Carbon;

/**
 * Daily Report: one institute, one date, one row per department.
 *
 * Built from the same per-teacher status as the dashboard and the boards
 * (AttendanceReportService::teacherBoard): shift rules, night shifts, the
 * institute's own holidays and default times all apply, so the numbers match.
 *
 *  - Total Employee    = teachers/staff of the department
 *  - Total Present     = punched in (on time or late)
 *  - Total Absent      = no punch on a working day (shift already started)
 *  - Total Late Present= present but late
 *  - Total Late        = sum of late time (h:mm)
 *  - Total Leave       = no leave module yet → "-"
 *
 * Not counted as present or absent: "shift not started yet" (today only)
 * and "holiday" — shown as separate columns so every row adds up.
 */
class DailyReportService
{
    /**
     * @return array{date:string, date_label:string, is_today:bool, is_holiday:bool,
     *               rows: array<int, array>, total: array}
     */
    public static function build(int $instituteId, string $date): array
    {
        $date  = Carbon::parse($date)->toDateString();
        $board = Report::teacherBoard($date, ['institute_id' => $instituteId]);
        $people = collect($board['teachers'])->groupBy(fn ($t) => (int) ($t['department_id'] ?? 0));

        // every department of the institute, in the order they were created
        $departments = Department::forInstitute($instituteId)->orderBy('id')->get(['id', 'name']);
        if ($people->has(0)) {
            $departments->push((object) ['id' => 0, 'name' => 'No Department']);
        }

        $rows = [];
        foreach ($departments as $i => $d) {
            $rows[] = ['sl' => $i + 1, 'department' => $d->name] + self::count($people->get($d->id, collect()));
        }

        $total = self::count(collect($board['teachers']));

        return [
            'date'       => $date,
            'date_label' => Carbon::parse($date)->format('d M Y, l'),
            'is_today'   => Carbon::parse($date)->isToday(),
            'is_holiday' => holidayChecker($instituteId)($date),
            'rows'       => $rows,
            'total'      => $total,
        ];
    }

    protected static function count($list): array
    {
        $present = $list->whereIn('status', ['on_time', 'late']);
        $late    = $list->where('status', 'late');
        $minutes = (int) $late->sum('late_minutes');

        return [
            'employees'    => $list->count(),
            'present'      => $present->count(),
            'absent'       => $list->where('status', 'absent')->count(),
            'late_present' => $late->count(),
            'late_time'    => $minutes > 0 ? sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60) : '-',
            'upcoming'     => $list->where('status', 'upcoming')->count(),
            'holiday'      => $list->where('status', 'holiday')->count(),
            'percent'      => self::percent($present->count(), $list->count() - $list->whereIn('status', ['upcoming', 'holiday'])->count()),
        ];
    }

    protected static function percent(int $present, int $counted): ?float
    {
        return $counted > 0 ? round($present / $counted * 100, 1) : null;
    }
}
