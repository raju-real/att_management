<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A user (PIN + name) enrolled on a fingerprint device. */
class DeviceUser extends Model
{
    protected $table = 'device_users';

    protected $fillable = ['device_id', 'pin', 'name', 'privilege', 'card', 'received_at'];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
