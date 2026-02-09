<?php

namespace App\Models;

use App\Traits\HasUniqueId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentFee extends Model
{
    use HasFactory, HasUniqueId;

    protected $guarded = ['id'];

    public function feeLot()
    {
        return $this->belongsTo(FeeLot::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
