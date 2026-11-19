<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** An institute's own leave type: Casual, Medical, ... */
class LeaveType extends Model
{
    use SoftDeletes, \App\Traits\BelongsToInstitute;

    protected $fillable = ['institute_id', 'name', 'code', 'days_per_year', 'status'];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    /** "Casual Leave (CL)" */
    public function getLabelAttribute(): string
    {
        return $this->name . ($this->code ? ' (' . $this->code . ')' : '');
    }
}
