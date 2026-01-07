<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, ModelHelper;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeEmployee($query)
    {
        return $query->where('role', 'employee');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function fingerprints()
    {
        return $this->hasMany(Fingerprint::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_employee')->withPivot('status', 'synced_at');
    }

    public function attendance()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public static function getEmployeeId(): string
    {
        $lastUserId = User::latest('id')->first();
        // Start from 1001
        $newEmployeeId = '1001';
        if ($lastUserId) {
            $lastEmpId = $lastUserId->employee_id;

            if ($lastEmpId !== null && is_numeric($lastEmpId)) {
                $newSerialNumber = (int)$lastEmpId + 1;
                $newEmployeeId = (string)$newSerialNumber;
            }
        }
        if (User::where('employee_id', $newEmployeeId)->exists()) {
            return self::getEmployeeId(); // IMPORTANT: return it
        }
        return $newEmployeeId;
    }

}
