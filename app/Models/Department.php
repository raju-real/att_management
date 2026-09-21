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

    protected $fillable = ['name', 'status'];

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
