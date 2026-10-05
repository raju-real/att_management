<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, ModelHelper, SoftDeletes;

    protected $table = 'devices';

    protected $fillable = [
        'name',
        'slug',
        'serial_no',
        'ip_address',
        'device_port',
        'subnet_label',
        'gateway_ip',
        'location_note',
        'comm_key',
        'device_for',
        'status',
        'use_push_mode',
        'last_synced_at',
        'last_seen_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'use_push_mode'  => 'boolean',
        'last_seen_at'   => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * True when device last called-in within the last 10 minutes.
     * (Device polls every 30 sec — 10 min gives plenty of buffer for slow networks.)
     */
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->use_push_mode) {
            return false; // online status is N/A for TCP devices
        }
        return $this->last_seen_at && $this->last_seen_at->diffInMinutes(now()) <= 10;
    }

    /**
     * Returns 'online', 'offline', or 'never' string for blade templates.
     */
    public function getOnlineStatusAttribute(): string
    {
        if (! $this->use_push_mode)       return 'tcp';
        if (! $this->last_seen_at)        return 'never';
        return $this->is_online ? 'online' : 'offline';
    }

    /**
     * Human-readable connection mode.
     */
    public function getConnectionModeAttribute(): string
    {
        return $this->use_push_mode ? 'Push (HTTP)' : 'TCP/UDP';
    }

    public function commands()
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'device_employee')
                    ->withPivot('status', 'synced_at');
    }
}
