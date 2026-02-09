<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeLot extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }
}
