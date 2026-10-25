<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, ModelHelper, SoftDeletes, \App\Traits\BelongsToInstitute;

    protected $table = 'departments';

    protected $fillable = ['institute_id', 'name', 'shift_id', 'status'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
