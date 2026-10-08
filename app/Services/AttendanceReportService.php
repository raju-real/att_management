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
        'single_punch' => 'Missing In / Out Punch',
        'outside'      => 'Punch Outside Shift Window',
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
    //
    // Punches are grouped by WORK DATE (the day the shift started), not by
    // calendar date, so a night shift 22:00 → 06:00 is one row:
    //
    //   day boundary = shift in_time − punch_before_minutes
    //   work date    = DATE(punch_time − day boundary)
    //
    //   Night 22:00–06:00, 180 min before → boundary 19:00
    //     8 Oct 21:55 → 8 Oct,   9 Oct 06:05 → 8 Oct (same shift)
    //   Day   08:00–14:00, 180 min before → boundary 05:00
    //     8 Oct 07:50 → 8 Oct
    //
    // Punches outside [in − before, out + after] are "outside shift" and are
    // not used as in/out (unless the day has nothing else).
    // People without a shift (students, teachers without department/shift)
    // use the site default in/out time; with no default, calendar dates.

    /** Default punch window when a shift (or the site default) has none. */
    public const DEFAULT_BEFORE_MINUTES = 180;
    public const DEFAULT_AFTER_MINUTES  = 360;

    /**
     * Stage 1 — one row per punch with the person's shift rules and the
     * punch's work date.
     */
    protected static function punchQuery(string $from, string $to, array $f = []): Builder
    {
        $stdIn   = self::standardExpr('sh.in_time', siteSettings()->in_time ?? null);
        $stdOut  = self::standardExpr('sh.out_time', siteSettings()->out_time ?? null);
        $lateT   = "COALESCE(sh.late_count_time, {$stdIn})";
        $earlyT  = "COALESCE(sh.early_out_count_time, {$stdOut})";
        $beforeS = '(COALESCE(sh.punch_before_minutes, ' . self::DEFAULT_BEFORE_MINUTES . ') * 60)';
        $afterS  = '(COALESCE(sh.punch_after_minutes, ' . self::DEFAULT_AFTER_MINUTES . ') * 60)';
        $durS    = "(CASE WHEN {$stdIn} IS NULL OR {$stdOut} IS NULL THEN NULL
                     ELSE COALESCE(NULLIF(MOD(TIME_TO_SEC({$stdOut}) - TIME_TO_SEC({$stdIn}) + 86400, 86400), 0), 86400) END)";
        $boundS  = "(CASE WHEN {$stdIn} IS NULL THEN 0 ELSE TIME_TO_SEC({$stdIn}) - {$beforeS} END)";
        // Seconds since this punch's shift-day boundary (0 … 86399).
        $offsetS = "MOD(TIME_TO_SEC(TIME(al.punch_time)) - {$boundS} + 172800, 86400)";

        // Raw range wide enough for any boundary (−12 h … +24 h); the exact
        // work-date range is applied in stage 2.
        $start = Carbon::parse($from)->subDay()->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($to)->addDays(2)->startOfDay()->format('Y-m-d H:i:s');

        return DB::table('attendance_logs as al')
            ->leftJoin('teachers as t', 't.teacher_no', '=', DB::raw("NULLIF(al.teacher_no, '')"))
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->selectRaw("
                al.user_type,
                COALESCE(NULLIF(al.student_no, ''), NULLIF(al.teacher_no, '')) AS user_no,
                NULLIF(al.student_no, '') AS student_no,
                NULLIF(al.teacher_no, '') AS teacher_no,
                al.punch_time,
                {$stdIn}  AS std_in,
                {$stdOut} AS std_out,
                {$lateT}  AS late_t,
                {$earlyT} AS early_t,
                {$durS}   AS dur_s,
                DATE(DATE_SUB(al.punch_time, INTERVAL {$boundS} SECOND)) AS work_date,
                CASE WHEN {$durS} IS NULL THEN 1
                     WHEN {$offsetS} <= {$beforeS} + {$durS} + {$afterS} THEN 1 ELSE 0 END AS in_win
            ")
            ->where('al.punch_time', '>=', $start)
            ->where('al.punch_time', '<', $end)
            ->whereRaw("COALESCE(NULLIF(al.student_no, ''), NULLIF(al.teacher_no, '')) IS NOT NULL")
            ->when($f['user_type'] ?? null, fn ($q, $v) => $q->where('al.user_type', $v))
            ->when($f['teacher_no'] ?? null, fn ($q, $v) => $q->where('al.teacher_no', $v))
            ->when($f['user_no'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('al.teacher_no', $v)->orWhere('al.student_no', $v)
            ));
    }

    /**
     * Stage 2 — one row per person per WORK DATE: first/last punch inside
     * the shift window (falls back to any punch if none was inside).
     */
    protected static function dailyPunches(string $from, string $to, array $f = []): Builder
    {
        return DB::query()
            ->fromSub(self::punchQuery($from, $to, $f), 'p')
            ->selectRaw("
                p.user_type,
                p.user_no,
                MAX(p.student_no) AS student_no,
                MAX(p.teacher_no) AS teacher_no,
                p.work_date       AS att_date,
                MAX(p.std_in)     AS std_in,
                MAX(p.std_out)    AS std_out,
                MAX(p.late_t)     AS late_t,
                MAX(p.early_t)    AS early_t,
                MAX(p.dur_s)      AS dur_s,
                COALESCE(MIN(CASE WHEN p.in_win = 1 THEN p.punch_time END), MIN(p.punch_time)) AS first_p,
                COALESCE(MAX(CASE WHEN p.in_win = 1 THEN p.punch_time END), MAX(p.punch_time)) AS last_p,
                COUNT(*)      AS punches,
                SUM(p.in_win) AS win_punches
            ")
            ->whereBetween('p.work_date', [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()])
            ->groupBy('p.user_type', 'p.user_no', 'p.work_date');
    }

    /**
     * Stage 3 — names, department, shift and the scheduled times of that
     * shift day as real datetimes (so they cross midnight correctly).
     */
    protected static function scheduledQuery(string $from, string $to, array $f = []): Builder
    {
        $gap = (int) self::MIN_OUT_GAP_MINUTES;
        $sIn = 'CASE WHEN a.std_in IS NULL THEN NULL ELSE TIMESTAMP(a.att_date, a.std_in) END';
        $fwd = fn (string $t) => "MOD(TIME_TO_SEC({$t}) - TIME_TO_SEC(a.std_in) + 86400, 86400)";

        return DB::query()
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
                a.*,
                COALESCE(NULLIF(t.name, ''), " . self::STUDENT_NAME_SQL . ", '(Unknown)') AS name,
                t.id          AS teacher_id,
                t.image       AS image,
                t.designation AS designation,
                d.id          AS department_id,
                d.name        AS department,
                d.shift_id    AS shift_id,
                sh.title      AS shift_title,
                {$sIn}                                                     AS sched_in,
                ({$sIn}) + INTERVAL a.dur_s SECOND                         AS sched_out,
                ({$sIn}) + INTERVAL {$fwd('a.late_t')} SECOND               AS late_at,
                ({$sIn}) + INTERVAL {$fwd('a.early_t')} SECOND              AS early_at,
                (TIMESTAMPDIFF(MINUTE, a.first_p, a.last_p) >= {$gap})       AS has_two
            ");
    }

    /**
     * Stage 4 — the per-day attendance row every report uses (logs, monthly
     * summary, dashboard, boards, CSV): in/out, late, early out, hours.
     *
     * Rules within one shift day:
     *  - In  = first punch; Out = last punch if ≥ MIN_OUT_GAP_MINUTES later
     *  - Only one punch: it is the OUT punch if nearer the scheduled out
     *    time (in shows "-"), otherwise the IN punch (out shows "-")
     *  - Late      = in  > late count time; minutes counted from the in time
     *  - Early out = out < early-out count time; minutes counted to the out time
     *  - Work time = out − in
     */
    public static function dailyQuery(string $from, string $to, array $f = []): Builder
    {
        $singleOut = "(NOT r.has_two AND r.sched_in IS NOT NULL
                       AND ABS(TIMESTAMPDIFF(SECOND, r.first_p, r.sched_out)) < ABS(TIMESTAMPDIFF(SECOND, r.first_p, r.sched_in)))";
        $in      = "(CASE WHEN {$singleOut} THEN NULL ELSE r.first_p END)";
        $out     = "(CASE WHEN r.has_two THEN r.last_p WHEN {$singleOut} THEN r.first_p END)";
        $isLate  = "({$in} IS NOT NULL AND r.late_at IS NOT NULL AND {$in} > r.late_at)";
        $isEarly = "({$out} IS NOT NULL AND r.early_at IS NOT NULL AND {$out} < r.early_at)";
        $onTime  = "({$in} IS NOT NULL AND r.late_at IS NOT NULL AND {$in} <= r.late_at)";

        $q = DB::query()
            ->fromSub(self::scheduledQuery($from, $to, $f), 'r')
            ->selectRaw("
                r.user_type,
                r.user_no,
                r.att_date,
                r.punches,
                r.punches - r.win_punches AS outside_punches,
                (r.win_punches = 0)       AS outside_only,
                r.name,
                r.teacher_id,
                r.image,
                r.designation,
                r.department_id,
                r.department,
                r.shift_id,
                r.shift_title,
                r.std_in,
                r.std_out,
                r.late_t   AS late_count_time,
                r.early_t  AS early_out_count_time,
                (r.std_in IS NOT NULL AND r.std_out <= r.std_in) AS is_overnight,
                r.sched_in,
                r.sched_out,
                {$in}  AS in_time,
                {$out} AS out_time,
                ({$in}  IS NOT NULL AND DATE({$in})  > r.att_date) AS in_next_day,
                ({$out} IS NOT NULL AND DATE({$out}) > r.att_date) AS out_next_day,
                ({$in} IS NULL)                         AS missing_in,
                ({$in} IS NOT NULL AND {$out} IS NULL)  AS missing_out,
                CASE WHEN {$isLate} THEN 1 ELSE 0 END AS is_late,
                CASE WHEN {$isLate} THEN CEIL(TIMESTAMPDIFF(SECOND, r.sched_in, {$in}) / 60) ELSE 0 END AS late_minutes,
                CASE WHEN {$onTime} THEN 1 ELSE 0 END AS is_early_in,
                CASE WHEN {$isEarly} THEN 1 ELSE 0 END AS is_early_out,
                CASE WHEN {$isEarly} THEN GREATEST(CEIL(TIMESTAMPDIFF(SECOND, {$out}, r.sched_out) / 60), 0) ELSE 0 END AS early_out_minutes,
                CASE WHEN {$in} IS NOT NULL AND {$out} IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, {$in}, {$out}) ELSE 0 END AS work_minutes,
                CASE WHEN {$in} IS NOT NULL AND r.sched_in IS NOT NULL THEN TIMESTAMPDIFF(SECOND, r.sched_in, {$in}) END AS arrival_offset
            ");

        if (!empty($f['department_id'])) {
            $q->where('r.department_id', $f['department_id']);
        }
        if (!empty($f['shift_id'])) {
            $q->where('r.shift_id', $f['shift_id']);
        }
        if (!empty($f['search'])) {
            $s = '%' . trim($f['search']) . '%';
            $q->where(fn ($w) => $w->where('r.name', 'like', $s)->orWhere('r.user_no', 'like', $s));
        }

        switch ($f['status'] ?? null) {
            case 'late':
                $q->whereRaw($isLate);
                break;
            case 'on_time':
                $q->whereRaw($onTime);
                break;
            case 'early_out':
                $q->whereRaw($isEarly);
                break;
            case 'single_punch':
                $q->whereRaw("({$in} IS NULL OR {$out} IS NULL)");
                break;
            case 'outside':
                $q->whereRaw('r.punches > r.win_punches');
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
            'date_asc'   => $q->orderBy('r.att_date')->orderBy('r.first_p'),
            // earliest arrival relative to each person's own shift start
            'in_asc'     => $q->orderBy('r.att_date', 'desc')
                ->orderByRaw('COALESCE(TIMESTAMPDIFF(SECOND, r.sched_in, r.first_p), TIME_TO_SEC(TIME(r.first_p)))'),
            'late_desc'  => $q->orderByDesc('late_minutes')->orderBy('r.att_date', 'desc'),
            'name'       => $q->orderBy('r.name')->orderBy('r.att_date', 'desc'),
            default      => $q->orderBy('r.att_date', 'desc')->orderBy('r.first_p'),
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
                SUM(CASE WHEN x.in_time IS NULL OR x.out_time IS NULL THEN 1 ELSE 0 END) AS single_punch_days,
                SUM(x.outside_punches) AS outside_punches,
                SUM(x.work_minutes)  AS work_minutes,
                " . self::arrivalClock('MIN(x.arrival_offset)', 'MIN(TIME(x.in_time))') . " AS earliest_in,
                " . self::arrivalClock('MAX(x.arrival_offset)', 'MAX(TIME(x.in_time))') . " AS latest_in,
                " . self::arrivalClock('ROUND(AVG(x.arrival_offset))', 'SEC_TO_TIME(ROUND(AVG(TIME_TO_SEC(TIME(x.in_time)))))') . " AS avg_in
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
    public static function teacherBoard(string $date, array $filters = []): array
    {
        $deptId  = !empty($filters['department_id']) ? (int) $filters['department_id'] : null;
        $shiftId = !empty($filters['shift_id']) ? (int) $filters['shift_id'] : null;
        $isToday = Carbon::parse($date)->isToday();
        $now     = now();

        $teachers = Teacher::query()
            ->leftJoin('departments as d', 'd.id', '=', 'teachers.department_id')
            ->leftJoin('shifts as sh', 'sh.id', '=', 'd.shift_id')
            ->when($deptId, fn ($q) => $q->where('teachers.department_id', $deptId))
            ->when($shiftId, fn ($q) => $q->where('d.shift_id', $shiftId))
            ->get([
                'teachers.id', 'teachers.name', 'teachers.teacher_no', 'teachers.image', 'teachers.designation',
                'd.name as department', 'sh.title as shift_title', 'sh.in_time as shift_in', 'sh.out_time as shift_out',
                'sh.punch_before_minutes as shift_before', 'sh.punch_after_minutes as shift_after',
            ]);

        // Rows for the date and the day before: on "today", a night-shift
        // teacher's current shift day may still be yesterday (e.g. at 02:00).
        $from = $isToday ? Carbon::parse($date)->subDay()->toDateString() : $date;
        $rows = self::dailyQuery($from, $date, array_filter([
            'user_type' => 'teacher', 'department_id' => $deptId, 'shift_id' => $shiftId,
        ]))->get()->keyBy(fn ($r) => $r->user_no . '|' . $r->att_date);

        $siteIn  = siteSettings()->in_time ?? null;
        $siteOut = siteSettings()->out_time ?? null;
        $fmt     = fn ($time) => $time ? Carbon::parse($time)->format('h:i A') : null;

        $list = $teachers->map(function ($t) use ($rows, $fmt, $date, $isToday, $now, $siteIn, $siteOut) {
            $stdIn  = $t->shift_in ?: ($siteIn ? Carbon::parse($siteIn)->format('H:i:s') : null);
            $stdOut = $t->shift_out ?: ($siteOut ? Carbon::parse($siteOut)->format('H:i:s') : null);
            $before = (int) ($t->shift_before ?? self::DEFAULT_BEFORE_MINUTES);

            // The shift day this teacher is in right now (or the chosen date):
            // before today's shift window opens, yesterday's shift is still
            // "current" while its own window (out + after) has not closed —
            // e.g. a night shift at 02:00. After that, today's shift is shown
            // (as upcoming until it starts).
            $workDate = $date;
            if ($isToday && $stdIn) {
                $todayOpens = Carbon::parse($date . ' ' . $stdIn)->subMinutes($before);
                if ($now->lt($todayOpens)) {
                    $yesterday   = Carbon::parse($date)->subDay()->toDateString();
                    $lengthMin   = $stdOut ? \App\Models\Shift::durationMinutes($stdIn, $stdOut) : 0;
                    $after       = (int) ($t->shift_after ?? self::DEFAULT_AFTER_MINUTES);
                    $yesterdayEnds = Carbon::parse($yesterday . ' ' . $stdIn)->addMinutes($lengthMin + $after);
                    if ($now->lte($yesterdayEnds)) {
                        $workDate = $yesterday;
                    }
                }
            }
            $r = $rows->get($t->teacher_no . '|' . $workDate);

            if ($r) {
                $status = $r->is_late ? 'late' : 'on_time';
            } else {
                $schedIn = $stdIn ? Carbon::parse($workDate . ' ' . $stdIn) : null;
                $status  = ($isToday && $schedIn && $now->lt($schedIn)) ? 'upcoming' : 'absent';
            }

            $plus = fn ($dt) => $dt && Carbon::parse($dt)->toDateString() > $workDate ? ' +1' : '';

            return [
                'id'           => $t->id,
                'name'         => $t->name,
                'teacher_no'   => $t->teacher_no,
                'designation'  => $t->designation,
                'department'   => $t->department,
                'image'        => ($t->image && file_exists($t->image)) ? asset($t->image) : null,
                'initial'      => strtoupper(mb_substr($t->name ?? 'T', 0, 1)),
                'shift_title'  => $t->shift_title,
                'shift_in'     => $fmt($stdIn),
                'shift_out'    => $stdOut ? $fmt($stdOut) . ($stdIn && $stdOut <= $stdIn ? ' +1' : '') : null,
                'work_date'    => $workDate,
                'status'       => $status,
                'in_time'      => $r && $r->in_time ? $fmt($r->in_time) . $plus($r->in_time) : null,
                'out_time'     => $r && $r->out_time ? $fmt($r->out_time) . $plus($r->out_time) : null,
                'missing_in'   => (bool) ($r->missing_in ?? false),
                'late_by'      => $r && $r->is_late ? self::minutesToHm((int) $r->late_minutes) : null,
                'early_out'    => (bool) ($r->is_early_out ?? false),
                'early_out_by' => $r && $r->is_early_out ? self::minutesToHm((int) $r->early_out_minutes) : null,
                'work_hours'   => $r && $r->in_time && $r->out_time ? self::minutesToHm((int) $r->work_minutes) : null,
                // arrivals first in order of arrival, then upcoming, then absent
                'sort_key'     => $r ? '0' . str_pad((string) ((int) ($r->arrival_offset ?? 0) + 86400), 6, '0', STR_PAD_LEFT)
                                     : ($status === 'upcoming' ? '1' : '2'),
            ];
        })->sortBy([['sort_key', 'asc'], ['name', 'asc']])->values();

        $summary = [
            'total'     => $list->count(),
            'present'   => $list->whereIn('status', ['late', 'on_time'])->count(),
            'late'      => $list->where('status', 'late')->count(),
            'on_time'   => $list->where('status', 'on_time')->count(),
            'early_out' => $list->where('early_out', true)->count(),
            'absent'    => $list->where('status', 'absent')->count(),
            'upcoming'  => $list->where('status', 'upcoming')->count(),
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

    /**
     * Clock time from an arrival offset (seconds after the shift in time),
     * so min/max/average work across midnight for night shifts. Falls back
     * to plain clock times for people without a standard in time.
     */
    protected static function arrivalClock(string $offsetAgg, string $fallback): string
    {
        return "CASE WHEN MAX(x.std_in) IS NOT NULL AND {$offsetAgg} IS NOT NULL
                     THEN SEC_TO_TIME(MOD(TIME_TO_SEC(MAX(x.std_in)) + {$offsetAgg} + 864000, 86400))
                     ELSE {$fallback} END";
    }

    /**
     * "06:05 AM (+1)" — a punch time as shown in every report (views, CSV).
     * "(+1)" marks a time on the day after the shift's work date (night
     * shifts). Empty → "-".
     */
    public static function clock($datetime, ?string $workDate = null, string $format = 'h:i A'): string
    {
        if (empty($datetime)) {
            return '-';
        }
        $dt = Carbon::parse($datetime);
        return $dt->format($format) . ($workDate && $dt->toDateString() > $workDate ? ' (+1)' : '');
    }

    /** Shift / standard time with "(+1)" when it ends the next day. */
    public static function shiftClock($time, $inTime = null): string
    {
        if (empty($time)) {
            return '-';
        }
        $label = Carbon::parse($time)->format('h:i A');
        return $inTime && $time <= $inTime ? $label . ' (+1)' : $label;
    }

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
