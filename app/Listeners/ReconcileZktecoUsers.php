<?php

namespace App\Listeners;

use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use Athwari\LaravelZktecoAdms\Events\UserQueryReceived;
use Illuminate\Support\Facades\Cache;

/**
 * Handles the device's response to a "Pull Users" request (queued via
 * DeviceActivityService::pullUsers() as a DATA QUERY USERINFO command).
 * The response arrives asynchronously — on whatever /iclock/cdata POST the
 * device happens to make after running the query, not on the request that
 * queued it — so results are cached here for the UI to pick up afterward.
 *
 * Mirrors the reconciliation rules DeviceSyncService/UserResolver use
 * elsewhere: a device dedicated to one group (device_for = student|teacher)
 * is unambiguous; a mixed device only auto-touches PINs that already exist
 * in exactly one of Student/Teacher, and reports anything else for manual
 * review instead of guessing.
 */
class ReconcileZktecoUsers
{
    public function handle(UserQueryReceived $event): void
    {
        $device = Device::where('serial_number', $event->serialNumber)->first();
        if (! $device) {
            return;
        }

        $createdStudents = 0;
        $createdTeachers = 0;
        $updatedTeachers = 0;
        $skipped         = 0;
        $unclassified    = [];

        foreach ($event->users as $u) {
            $pin  = trim($u->pin);
            $name = trim($u->name);
            if ($pin === '') {
                continue;
            }

            $scope = $device->device_for;

            if ($scope === 'student') {
                if (! Student::where('student_no', $pin)->exists()) {
                    $student = new Student();
                    $student->student_no = $pin;
                    $student->firstname  = $name ?: "Student {$pin}";
                    $student->save();
                    $createdStudents++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if ($scope === 'teacher') {
                $teacher = Teacher::where('teacher_no', $pin)->first();
                if (! $teacher) {
                    $teacher = new Teacher();
                    $teacher->teacher_no = $pin;
                    $teacher->name       = $name ?: "Teacher {$pin}";
                    $teacher->save();
                    $createdTeachers++;
                } elseif ($name && $teacher->name !== $name) {
                    $teacher->name = $name;
                    $teacher->save();
                    $updatedTeachers++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $existingStudent = Student::where('student_no', $pin)->first();
            $existingTeacher = Teacher::where('teacher_no', $pin)->first();

            if ($existingStudent && $existingTeacher) {
                $unclassified[] = ['pin' => $pin, 'name' => $name, 'reason' => 'PIN exists as BOTH a student and a teacher — needs manual review'];
                continue;
            }
            if ($existingStudent) {
                $skipped++;
                continue;
            }
            if ($existingTeacher) {
                if ($name && $existingTeacher->name !== $name) {
                    $existingTeacher->name = $name;
                    $existingTeacher->save();
                    $updatedTeachers++;
                } else {
                    $skipped++;
                }
                continue;
            }

            $unclassified[] = ['pin' => $pin, 'name' => $name, 'reason' => 'New PIN not yet in your Student or Teacher list'];
        }

        $result = [
            'success'         => true,
            'createdStudents' => $createdStudents,
            'createdTeachers' => $createdTeachers,
            'updatedTeachers' => $updatedTeachers,
            'skipped'         => $skipped,
            'unclassified'    => $unclassified,
            'totalOnDevice'   => count($event->users),
            'receivedAt'      => now()->toIso8601String(),
        ];

        $service = app(\App\Services\DeviceActivityService::class);
        Cache::put($service->pullUsersCacheKey($device), $result, now()->addMinutes(30));
    }
}
