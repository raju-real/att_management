<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory, \App\Traits\BelongsToInstitute;
    protected $table = 'attendance_logs';
    protected $guarded = [];

    protected $casts = [
        'punch_time' => 'datetime', // or 'date'
    ];

    /**
     * Every punch belongs to the institute of the device it came from.
     * Set here (not in the device/fetch services) so every save path —
     * ADMS push, TCP pull, sync command — gets it without changing them.
     */
    protected static function booted(): void
    {
        static::creating(function (AttendanceLog $log) {
            if (!empty($log->institute_id)) {
                return;
            }
            $device = null;
            if (!empty($log->device_id)) {
                $device = Device::withoutGlobalScopes()->withTrashed()->find($log->device_id);
            }
            if (!$device && !empty($log->device_serial)) {
                $device = Device::withoutGlobalScopes()->withTrashed()->where('serial_no', $log->device_serial)->first();
            }
            if ($device && $device->institute_id) {
                $log->institute_id = $device->institute_id;
            }
        });
    }

    public function employee() {
        return $this->belongsTo(User::class, 'employee_id','employee_id');
    }

    public function device() {
        return $this->belongsTo(Device::class, 'device_id','serial_no');
    }
}
