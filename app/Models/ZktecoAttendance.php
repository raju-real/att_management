<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZktecoAttendance extends Model
{
    protected $table = 'zkteco_attendance';

    protected $fillable = [
        'device_id',
        'serial_number',
        'pin',
        'attendance_time',
        'status',
        'verify_type',
        'work_code',
        'raw_data',
    ];

    protected $casts = [
        'attendance_time' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(
            Device::class,
            'device_id'
        );
    }
}
