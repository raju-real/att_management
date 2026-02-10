<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'datetime',
        'refund_date' => 'datetime',
    ];

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }
}
