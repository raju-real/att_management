<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, ModelHelper, SoftDeletes;

    protected $table = 'departments';

    protected $fillable = ['name', 'shift_id', 'status'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
