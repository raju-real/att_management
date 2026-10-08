<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A shift is the standard in/out timing. Departments point at a shift
 * (departments.shift_id) and teachers inherit it through their department.
 *
 * Night shifts: out_time <= in_time means the shift ends on the NEXT day
 * (e.g. 22:00 → 06:00). All punches between (in_time − punch_before_minutes)
 * and (out_time + punch_after_minutes) belong to the shift day it started on.
 */
class Shift extends Model
{
    use HasFactory, ModelHelper, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'title', 'in_time', 'out_time', 'status',
        'late_count_time', 'early_out_count_time',
        'punch_before_minutes', 'punch_after_minutes', 'is_overnight',
    ];

    protected $casts = [
        'is_overnight'         => 'boolean',
        'punch_before_minutes' => 'integer',
        'punch_after_minutes'  => 'integer',
    ];

    public const DEFAULT_PUNCH_BEFORE_MINUTES = 180;
    public const DEFAULT_PUNCH_AFTER_MINUTES  = 360;

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function teachers()
    {
        return $this->hasManyThrough(Teacher::class, Department::class);
    }

    public function getTimingAttribute(): string
    {
        return timeFormat($this->in_time, 'h:i A') . ' - ' . timeFormat($this->out_time, 'h:i A')
            . ($this->is_overnight ? ' (+1)' : '');
    }

    /** Seconds from in_time to a clock time, going forward around midnight. */
    public static function secondsAfter(string $from, string $to): int
    {
        $a = strtotime('1970-01-01 ' . $from . ' UTC');
        $b = strtotime('1970-01-01 ' . $to . ' UTC');
        return (($b - $a) % 86400 + 86400) % 86400;
    }

    /** Shift length in minutes (night shifts cross midnight). */
    public static function durationMinutes(string $in, string $out): int
    {
        $s = self::secondsAfter($in, $out);
        return intdiv($s === 0 ? 86400 : $s, 60);
    }
}
