<?php

namespace App\Services;

use App\Models\Teacher;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side attendance reporting (logs, monthly summary, dashboard board).
 *
 * Standard timing chain:  teacher → department → shift (in_time / out_time).
 * Students (and teachers without a department/shift) fall back to the
 * site-setting in/out time, if configured.
 *
 * Performance notes:
 *  - punch_time is always filtered as a half-open range (>= from, < to+1)
 *    instead of DATE(punch_time) so MySQL can use the punch_time indexes.
 *  - Punches are collapsed to one row per person per day on attendance_logs
 *    alone FIRST, and only that small result is joined to
 *    teachers/departments/shifts.
 *  - Late / early-in / early-out / work-minutes are computed in SQL so
 *    filtering, sorting and pagination all happen in the database.
 */
class AttendanceReportService
{
    /**
     * A second punch closer than this to the first one is treated as an
     * accidental double scan, not an "out" punch.
     */
    public const MIN_OUT_GAP_MINUTES = 5;

    /**
     * Person key on attendance_logs. Some fetch paths store '' instead of NULL
     * in the unused no-column, so blanks are treated as NULL — otherwise
     * every teacher would collapse into one '' user.
     */
    public const USER_NO_SQL = "COALESCE(NULLIF(student_no, ''), NULLIF(teacher_no, ''))";

    /**
     * Student full name from the joined `students s` row. attendance_logs
     * has no name column — names always come from teachers / students.
     */
    public const STUDENT_NAME_SQL = "NULLIF(TRIM(CONCAT_WS(' ', NULLIF(s.firstname, ''), NULLIF(s.middlename, ''), NULLIF(s.lastname, ''))), '')";

    public const STATUSES = [
        'late'         => 'Late In',
        'on_time'      => 'On Time / Early In',
        'early_out'    => 'Early Out',
        'single_punch' => 'No Out Punch',
    ];

    // ───────────────────────────── Date range ─────────────────────────────

    /**
     * Resolve [from, to] (Y-m-d strings) from the request filters.
     * Supports: month=YYYY-MM, date=YYYY-MM-DD, from_date/to_date.
     */
    public static function resolveRange(array $f, string $default = 'today'): array
    {
        try {
            if (!empty($f['month'])) {
                $m = Carbon::createFromFormat('Y-m', $f['month'])->startOfMonth();
                return [$m->toDateString(), $m->copy()->endOfMonth()->toDateString()];
            }
            if (!empty($f['date'])) {
                $d = Carbon::parse($f['date'])->toDateString();
                return [$d, $d];
            }
            if (!empty($f['from_date'])) {
                $from = Carbon::parse($f['from_date']);
                $to   = !empty($f['to_date']) ? Carbon::parse($f['to_date']) : $from->copy();
                if ($to->lt($from)) {
                    [$from, $to] = [$to, $from];
                }
                return [$from->toDateString(), $to->toDateString()];
            }
        } catch (\Throwable) {
            // fall through to default
        }

        return $default === 'month'
            ? [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->toDateString()]
            : [Carbon::today()->toDateString(), Carbon::today()->toDateString()];
    }

    // ───────────────────────────── Core queries ─────────────────────────────

    /**
     * One row per person per day, straight from attendance_logs.
     */
    protected static function dailyPunches(string $from, string $to, array $f = []): Builder
    {
        $start = Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($to)->addDay()->startOfDay()->format('Y-m-d H:i:s');

        return DB::table('attendance_logs')
            ->selectRaw("
                user_type,
                " . self::USER_NO_SQL . " AS user_no,
                MAX(NULLIF(student_no, '')) AS student_no,
                MAX(NULLIF(teacher_no, '')) AS teacher_no,
                DATE(punch_time) AS att_date,
                MIN(punch_time) AS first_punch,
                MAX(punch_time) AS last_punch,
                COUNT(*)        AS punches
            ")
            ->where('punch_time', '>=', $start)
            ->where('punch_time', '<', $end)
            ->whereRaw(self::USER_NO_SQL . ' IS NOT NULL')
            ->when($f['user_type'] ?? null, fn ($q, $v) => $q->where('user_type', $v))
            ->when($f['teacher_no'] ?? null, fn ($q, $v) => $q->where('teacher_no', $v))
            ->when($f['user_no'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('teacher_no', $v)->orWhere('student_no', $v)
            ))
            // Grouped by the select aliases: MariaDB's ONLY_FULL_GROUP_BY rejects
            // the NULLIF() expression repeated here.
            ->groupBy('user_type', 'user_no', 'att_date');
    }

    /**
     * Per-day rows joined to teacher → department → shift, with all the
     * late / early / working-time flags computed.
     */
    public static function dailyQuery(string $from, string $to, array $f = []): Builder
    {
        $stdIn  = self::standardExpr('sh.in_time', siteSettings()->in_time ?? null);
        $stdOut = self::standardExpr('sh.out_time', siteSettings()->out_time ?? null);
        $gap    = (int) self::MIN_OUT_GAP_MINUTES;

        $hasOut  = "(a.punches > 1 AND TIMESTAMPDIFF(MINUTE, a.first_punch, a.last_punch) >= {$gap})";
        $isLate  = "({$stdIn} IS NOT NULL AND TIME(a.first_punch) > {$stdIn})";
        $isEarly = "({$hasOut} AND {$stdOut} IS NOT NULL AND TIME(a.last_punch) < {$stdOut})";

        $q = DB::query()
            ->fromSub(self::dailyPunches($from, $to, $f), 'a')
            ->leftJoin('teachers as t', 't.teacher_no', '=', 'a.teacher_no')
            // students.student_no is not unique, so join exactly ONE row per
            // student_no (a live student before a deleted one) — a plain join
            // would duplicate attendance rows.
            ->leftJoin('students as s', 's.id', '=', DB::raw(
                '(SELECT s2.id FROM students s2 WHERE s2.student_no = a.student_no
                  ORDER BY s2.deleted_at IS NULL DESC, s2.id DESC LIMIT 1)'
            ))
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->selectRaw("
                a.user_type,
                a.user_no,
                a.att_date,
                a.punches,
                COALESCE(NULLIF(t.name, ''), " . self::STUDENT_NAME_SQL . ", '(Unknown)') AS name,
                t.id          AS teacher_id,
                t.image       AS image,
                t.designation AS designation,
                d.id          AS department_id,
                d.name        AS department,
                sh.title      AS shift_title,
                {$stdIn}      AS std_in,
                {$stdOut}     AS std_out,
                a.first_punch AS in_time,
                CASE WHEN {$hasOut} THEN a.last_punch END AS out_time,
                CASE WHEN {$isLate} THEN 1 ELSE 0 END AS is_late,
                CASE WHEN {$isLate}
                     THEN CEIL(TIME_TO_SEC(TIMEDIFF(TIME(a.first_punch), {$stdIn})) / 60) ELSE 0 END AS late_minutes,
                CASE WHEN {$stdIn} IS NOT NULL AND TIME(a.first_punch) <= {$stdIn} THEN 1 ELSE 0 END AS is_early_in,
                CASE WHEN {$isEarly} THEN 1 ELSE 0 END AS is_early_out,
                CASE WHEN {$isEarly}
                     THEN CEIL(TIME_TO_SEC(TIMEDIFF({$stdOut}, TIME(a.last_punch))) / 60) ELSE 0 END AS early_out_minutes,
                CASE WHEN {$hasOut} THEN TIMESTAMPDIFF(MINUTE, a.first_punch, a.last_punch) ELSE 0 END AS work_minutes
            ");

        if (!empty($f['department_id'])) {
            $q->where('d.id', $f['department_id']);
        }
        if (!empty($f['search'])) {
            $s = '%' . trim($f['search']) . '%';
            $q->where(fn ($w) => $w->where('t.name', 'like', $s)
                ->orWhereRaw(self::STUDENT_NAME_SQL . ' LIKE ?', [$s])
                ->orWhere('a.user_no', 'like', $s));
        }

        switch ($f['status'] ?? null) {
            case 'late':
                $q->whereRaw($isLate);
                break;
            case 'on_time':
                $q->whereRaw("NOT {$isLate}")->whereRaw("{$stdIn} IS NOT NULL");
                break;
            case 'early_out':
                $q->whereRaw($isEarly);
                break;
            case 'single_punch':
                $q->whereRaw("NOT {$hasOut}");
                break;
        }

        return $q;
    }

    // ───────────────────────────── Attendance logs ─────────────────────────────

    public static function logs(array $f, int $perPage = 50)
    {
        [$from, $to] = self::resolveRange($f);

        $q = self::dailyQuery($from, $to, $f);
        self::applyLogSort($q, $f['sort'] ?? 'date_desc');

        return $q->paginate($perPage)->withQueryString();
    }

    public static function logsForExport(array $f): \Illuminate\Support\LazyCollection
    {
        [$from, $to] = self::resolveRange($f);

        $q = self::dailyQuery($from, $to, $f);
        self::applyLogSort($q, $f['sort'] ?? 'date_desc');

        return $q->cursor();
    }

    protected static function applyLogSort(Builder $q, string $sort): void
    {
        match ($sort) {
            'date_asc'   => $q->orderBy('a.att_date')->orderBy('a.first_punch'),
            'in_asc'     => $q->orderBy('a.att_date', 'desc')->orderByRaw('TIME(a.first_punch)'),
            'late_desc'  => $q->orderByDesc('late_minutes')->orderBy('a.att_date', 'desc'),
            'name'       => $q->orderBy('name')->orderBy('a.att_date', 'desc'),
            default      => $q->orderBy('a.att_date', 'desc')->orderBy('a.first_punch'),
        };
    }

    // ───────────────────────────── Monthly summary ─────────────────────────────

    protected static function summaryQuery(string $from, string $to, array $f): Builder
    {
        return DB::query()
            ->fromSub(self::dailyQuery($from, $to, $f), 'x')
            ->selectRaw("
                x.user_type,
                x.user_no,
                MAX(x.name)          AS name,
                MAX(x.teacher_id)    AS teacher_id,
                MAX(x.image)         AS image,
                MAX(x.designation)   AS designation,
                MAX(x.department)    AS department,
                MAX(x.shift_title)   AS shift_title,
                MAX(x.std_in)        AS std_in,
                MAX(x.std_out)       AS std_out,
                COUNT(*)             AS present_days,
                SUM(x.is_early_in)   AS early_in_days,
                SUM(x.is_late)       AS late_days,
                SUM(x.late_minutes)  AS late_minutes,
                SUM(x.is_early_out)  AS early_out_days,
                SUM(x.early_out_minutes) AS early_out_minutes,
                SUM(CASE WHEN x.out_time IS NULL THEN 1 ELSE 0 END) AS single_punch_days,
                SUM(x.work_minutes)  AS work_minutes,
                MIN(TIME(x.in_time)) AS earliest_in,
                MAX(TIME(x.in_time)) AS latest_in,
                SEC_TO_TIME(ROUND(AVG(TIME_TO_SEC(TIME(x.in_time))))) AS avg_in
            ")
            ->groupBy('x.user_type', 'x.user_no');
    }

    public static function monthlySummary(array $f, int $perPage = 50)
    {
        [$from, $to] = self::resolveRange($f, 'month');

        $q = self::summaryQuery($from, $to, $f);
        self::applySummarySort($q, $f['sort'] ?? 'name');

        return $q->paginate($perPage)->withQueryString();
    }

    public static function monthlySummaryForExport(array $f): \Illuminate\Support\LazyCollection
    {
        [$from, $to] = self::resolveRange($f, 'month');

        $q = self::summaryQuery($from, $to, $f);
        self::applySummarySort($q, $f['sort'] ?? 'name');

        return $q->cursor();
    }

    protected static function applySummarySort(Builder $q, string $sort): void
    {
        match ($sort) {
            'late_desc'    => $q->orderByDesc('late_days')->orderByDesc('late_minutes'),
            'hours_desc'   => $q->orderByDesc('work_minutes'),
            'present_desc' => $q->orderByDesc('present_days'),
            'user_no'      => $q->orderByRaw('CAST(x.user_no AS UNSIGNED)'),
            default        => $q->orderBy('x.user_type', 'desc')->orderBy('name'),
        };
    }

    /**
     * Totals per user_type (as stored on attendance_logs) for the summary header.
     */
    public static function userTypeTotals(array $f): Collection
    {
        [$from, $to] = self::resolveRange($f, 'month');

        return DB::query()
            ->fromSub(self::dailyQuery($from, $to, $f), 'x')
            ->selectRaw("
                COALESCE(x.user_type, 'unknown') AS user_type,
                COUNT(DISTINCT x.user_no) AS users,
                COUNT(*)                  AS present_days,
                SUM(x.is_late)            AS late_days,
                SUM(x.is_early_out)       AS early_out_days,
                SUM(x.work_minutes)       AS work_minutes
            ")
            ->groupBy(DB::raw("COALESCE(x.user_type, 'unknown')"))
            ->get()
            ->keyBy('user_type');
    }

    /**
     * Working days in range (excludes weekly + office holidays and future days).
     */
    public static function workingDays(string $from, string $to): int
    {
        $weekly = collect(weeklyHolidays())->map(fn ($d) => strtolower($d));
        $office = collect(officeHolidays());
        $end    = Carbon::parse($to)->min(Carbon::today());

        if ($end->lt(Carbon::parse($from))) {
            return 0;
        }

        $count = 0;
        foreach (CarbonPeriod::create($from, $end->toDateString()) as $day) {
            if ($weekly->contains(strtolower($day->format('l'))) || $office->contains($day->toDateString())) {
                continue;
            }
            $count++;
        }
        return $count;
    }

    // ───────────────────────────── Dashboard board ─────────────────────────────

    /**
     * Every teacher with today's (or $date's) status: late / on_time / absent,
     * plus the early_out flag. Sorted: arrivals by in-time first, then absentees.
     *
     * This is the ONE data source for every attendance board (dashboard
     * board and the standalone /attendance-board page) — change the
     * payload here and both pick it up.
     */
    public static function teacherBoard(string $date): array
    {
        $rows = self::dailyQuery($date, $date, ['user_type' => 'teacher'])
            ->get()
            ->keyBy('user_no');

        $teachers = Teacher::query()
            ->leftJoin('departments as d', 'd.id', '=', 'teachers.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->get([
                'teachers.id', 'teachers.name', 'teachers.teacher_no', 'teachers.image', 'teachers.designation',
                'd.name as department', 'sh.title as shift_title', 'sh.in_time as shift_in', 'sh.out_time as shift_out',
            ]);

        $fmt = fn ($time) => $time ? Carbon::parse($time)->format('h:i A') : null;

        $list = $teachers->map(function ($t) use ($rows, $fmt) {
            $r = $rows->get((string) $t->teacher_no);

            $status = !$r ? 'absent' : ($r->is_late ? 'late' : 'on_time');

            return [
                'id'           => $t->id,
                'name'         => $t->name,
                'teacher_no'   => $t->teacher_no,
                'designation'  => $t->designation,
                'department'   => $t->department,
                'image'        => ($t->image && file_exists($t->image)) ? asset($t->image) : null,
                'initial'      => strtoupper(mb_substr($t->name ?? 'T', 0, 1)),
                'shift_title'  => $t->shift_title,
                'shift_in'     => $fmt($t->shift_in),
                'shift_out'    => $fmt($t->shift_out),
                'status'       => $status,
                'in_time'      => $r ? $fmt($r->in_time) : null,
                'out_time'     => $r ? $fmt($r->out_time) : null,
                'late_by'      => $r && $r->is_late ? self::minutesToHm((int) $r->late_minutes) : null,
                'early_out'    => (bool) ($r->is_early_out ?? false),
                'early_out_by' => $r && $r->is_early_out ? self::minutesToHm((int) $r->early_out_minutes) : null,
                'work_hours'   => $r && $r->out_time ? self::minutesToHm((int) $r->work_minutes) : null,
                'sort_key'     => $r ? Carbon::parse($r->in_time)->format('His') : '999999',
            ];
        })->sortBy([['sort_key', 'asc'], ['name', 'asc']])->values();

        $summary = [
            'total'     => $list->count(),
            'present'   => $list->where('status', '!=', 'absent')->count(),
            'late'      => $list->where('status', 'late')->count(),
            'on_time'   => $list->where('status', 'on_time')->count(),
            'early_out' => $list->where('early_out', true)->count(),
            'absent'    => $list->where('status', 'absent')->count(),
        ];

        return [
            'date'     => Carbon::parse($date)->format('l, d M Y'),
            'summary'  => $summary,
            'teachers' => $list->map(fn ($t) => collect($t)->except('sort_key'))->all(),
        ];
    }

    /** Cards per slide the board can show. */
    public const BOARD_PAGE_SIZES = [12, 24, 48, 96, 200];
    public const BOARD_PAGE_SIZE_DEFAULT = 24;

    /**
     * The signed-in user's board page size, kept in the cache so the same
     * choice applies to the dashboard board and the full-page board, on
     * any browser. Pass $set to change it (invalid values are ignored).
     */
    public static function boardPerPage($set = null): int
    {
        $key = 'attendance_board.per_page.user.' . (auth()->id() ?? 'guest');

        if ($set !== null && in_array((int) $set, self::BOARD_PAGE_SIZES, true)) {
            cache()->forever($key, (int) $set);
            return (int) $set;
        }

        $value = (int) cache()->get($key, self::BOARD_PAGE_SIZE_DEFAULT);
        return in_array($value, self::BOARD_PAGE_SIZES, true) ? $value : self::BOARD_PAGE_SIZE_DEFAULT;
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    public static function minutesToHm(?int $minutes): string
    {
        $minutes = (int) $minutes;
        if ($minutes <= 0) {
            return '-';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm', $m);
    }

    /**
     * SQL for a standard time: the shift column, falling back to the site
     * setting when one is configured. The site value is normalised to
     * H:i:s by Carbon before being inlined, so it can only ever be a time.
     */
    protected static function standardExpr(string $column, $siteValue): string
    {
        if (empty($siteValue)) {
            return $column;
        }
        try {
            $time = Carbon::parse($siteValue)->format('H:i:s');
        } catch (\Throwable) {
            return $column;
        }
        return "COALESCE({$column}, CAST('{$time}' AS TIME))";
    }
}
