<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A shift is the standard in/out timing. Departments point at a shift
 * (departments.shift_id) and teachers inherit it through their department.
 */
class Shift extends Model
{
    use HasFactory, ModelHelper, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = ['title', 'in_time', 'out_time', 'status'];

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
        return timeFormat($this->in_time, 'h:i A') . ' - ' . timeFormat($this->out_time, 'h:i A');
    }
}
