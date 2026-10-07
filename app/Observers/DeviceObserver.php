<?php

namespace App\Observers;

use App\Models\Device;

/**
 * Keeps the "device_serial_number" cache = serial numbers of ACTIVE devices.
 * A device is added when it becomes active and removed when it becomes
 * inactive or is deleted. Read it with activeDeviceSerials().
 *
 * The cache is only rebuilt when it can change (create / delete / restore /
 * status or serial change) — not on the device's frequent last_seen_at
 * updates during check-ins.
 */
class DeviceObserver
{
    public const CACHE_KEY = 'device_serial_number';

    /**
     * Every device check-in runs AdmsService::registerDevice(), which calls
     * updateOrCreate(... 'name' => $serial ...) and would replace the name
     * you gave the device with its serial number. Keep the real name; only
     * a brand-new device (created by its first check-in) is named after its
     * serial. Renaming in the device form still works.
     */
    public function updating(Device $device): void
    {
        $original = (string) $device->getOriginal('name');

        if ($device->isDirty('name')
            && (string) $device->name === (string) $device->serial_no
            && $original !== ''
            && $original !== (string) $device->serial_no) {
            $device->name = $original;
        }
    }

    public function created(Device $device): void
    {
        self::refresh();
    }

    public function updated(Device $device): void
    {
        if ($device->wasChanged(['status', 'serial_no'])) {
            self::refresh();
        }
    }

    public function deleted(Device $device): void
    {
        self::refresh();
    }

    public function restored(Device $device): void
    {
        self::refresh();
    }

    public function forceDeleted(Device $device): void
    {
        self::refresh();
    }

    /** Rebuild the cached list from the database. */
    public static function refresh(): array
    {
        $serials = self::fromDatabase();
        cache()->forever(self::CACHE_KEY, $serials);
        return $serials;
    }

    /** @return string[] */
    public static function fromDatabase(): array
    {
        return Device::where('status', 'active')
            ->whereNotNull('serial_no')
            ->orderBy('serial_no')
            ->pluck('serial_no')
            ->map(fn ($s) => (string) $s)
            ->values()
            ->all();
    }
}
