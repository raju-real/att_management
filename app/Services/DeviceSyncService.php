<?php

namespace App\Services;

/**
 * Keeps ONE student/teacher's PIN+name in sync with every device that
 * should have them, right when that record is created, edited, or deleted —
 * so an admin never has to remember to click "Push to Device" again after a
 * single edit. Bulk/selected push lives on the teacher & student lists.
 *
 * Both methods go through DevicePushService, so the same safe rules apply
 * everywhere: users already on a device are skipped, only a changed name is
 * updated, and password/card/fingerprints are never touched.
 */
class DeviceSyncService
{
    public function __construct(protected DevicePushService $push)
    {
    }

    /**
     * @param string $group 'student' or 'teacher'
     * @return string[] one status line per device touched
     */
    public function pushOne(string $group, string $pin, string $name): array
    {
        $pin = trim($pin);
        if ($pin === '') {
            return [];
        }

        return $this->push->push($group, [$pin => $name ?: ucfirst($group)])['lines'];
    }

    /**
     * @param string $group 'student' or 'teacher'
     * @return string[] one status line per device touched
     */
    public function deleteOne(string $group, string $pin): array
    {
        $pin = trim($pin);
        if ($pin === '') {
            return [];
        }

        return $this->push->remove($group, [$pin])['lines'];
    }
}
