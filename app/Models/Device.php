<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory,ModelHelper,SoftDeletes;
    protected $table = "devices";

    protected $fillable = ['name', 'branch_id', 'serial_no', 'ip', 'enabled', 'last_seen_at'];
    protected $casts = ['enabled' => 'boolean', 'last_seen_at' => 'datetime'];
    protected $dates = ['last_synced_at'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function commands()
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'device_employee')->withPivot('status', 'synced_at');
    }
}
