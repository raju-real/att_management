<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    use HasFactory;

    protected $table = 'device_commands';

    protected $fillable = [
        'device_id',
        'command_text',
        'status',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Queue a command for a device to pick up on its next iClock poll.
     */
    public static function queue(int $deviceId, string $commandText): self
    {
        return self::create([
            'device_id'    => $deviceId,
            'command_text' => $commandText,
            'status'       => 'pending',
        ]);
    }

    /**
     * Build a SET USER command string for the iClock protocol.
     */
    public static function setUserCommand(string $pin, string $name, string $card = '', int $privilege = 0): string
    {
        $name = str_replace([',', '\\'], [' ', ''], $name); // sanitize
        return "DATA UPDATE USERINFO PIN={$pin}\tName={$name}\tCard={$card}\tPrivilege={$privilege}\tPassword=\tGroup=1\tTimeZone=0000000000000000000100000000\t";
    }

    /**
     * Build a DELETE USER command string for the iClock protocol.
     */
    public static function deleteUserCommand(string $pin): string
    {
        return "DATA DELETE USERINFO PIN={$pin}";
    }
}
