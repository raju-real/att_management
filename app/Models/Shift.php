<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, ModelHelper, SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = ['department_id', 'title', 'in_time', 'out_time', 'status'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
