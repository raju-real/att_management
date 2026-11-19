<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A teacher/staff member's leave for a date range (both days included).
 * `days` = working days in the range (holidays of the institute not counted).
 */
class Leave extends Model
{
    use SoftDeletes, \App\Traits\BelongsToInstitute;

    protected $fillable = ['institute_id', 'teacher_id', 'leave_type_id', 'from_date', 'to_date', 'days', 'reason', 'created_by'];

    protected $casts = ['from_date' => 'date', 'to_date' => 'date', 'days' => 'integer'];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    public function type()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id')->withTrashed();
    }
}
