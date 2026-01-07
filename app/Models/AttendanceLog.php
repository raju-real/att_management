<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'attendance_logs';
    protected $guarded = [];

    protected $casts = [
        'punch_time' => 'datetime', // or 'date'
    ];

    public function employee() {
        return $this->belongsTo(User::class, 'employee_id','employee_id');
    }

    public function device() {
        return $this->belongsTo(Device::class, 'device_id','serial_no');
    }

   
}
