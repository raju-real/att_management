<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentFee extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function feeLot()
    {
        return $this->belongsTo(FeeLot::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public static function generateUniqueId(): string
    {
        do {
            $uniqueId = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (StudentFee::where('unique_id', $uniqueId)->exists());

        return $uniqueId;
    }
}
