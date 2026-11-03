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
        'permissions'       => 'array', // inst_user menu keys (App\Support\Permissions)
    ];

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    // ─── Roles: 'admin' = super admin, 'institute' = an institute account ────
    // An institute IS a user row: it logs in and holds its letterhead details
    // (name, address, eiin, website, logo). Institute-owned rows point at it
    // with institute_id (see App\Traits\BelongsToInstitute).

    public const ROLE_ADMIN     = 'admin';
    public const ROLE_INSTITUTE = 'institute';
    // A user created BY an institute: that institute's data, only the permitted menus.
    public const ROLE_INST_USER = 'inst_user';

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isInstitute(): bool
    {
        return $this->role === self::ROLE_INSTITUTE;
    }

    public function isInstUser(): bool
    {
        return $this->role === self::ROLE_INST_USER;
    }

    /** Admin and the main institute account have every menu; an inst_user only its permissions. */
    public function hasPermission(string $key): bool
    {
        return ! $this->isInstUser() || in_array($key, (array) $this->permissions, true);
    }

    /** The institute an inst_user belongs to. */
    public function parentInstitute()
    {
        return $this->belongsTo(self::class, 'institute_id')->where('role', self::ROLE_INSTITUTE);
    }

    /** Users an institute created. */
    public function instUsers()
    {
        return $this->hasMany(self::class, 'institute_id')->where('role', self::ROLE_INST_USER);
    }

    public function scopeInstituteUsers($query)
    {
        return $query->where('role', self::ROLE_INST_USER);
    }

    public function scopeInstitutes($query)
    {
        return $query->where('role', self::ROLE_INSTITUTE);
    }

    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class, 'institute_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'institute_id');
    }

    public function instituteDevices()
    {
        return $this->hasMany(Device::class, 'institute_id');
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
