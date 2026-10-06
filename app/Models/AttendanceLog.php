<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;
    protected $table = 'attendance_logs';
    protected $guarded = [];

    protected $casts = [
        'punch_time' => 'datetime', // or 'date'
    ];

    public function device() {
        return $this->belongsTo(Device::class, 'device_serial','serial_no');
    }


}
