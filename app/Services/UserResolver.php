<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;

/**
 * Resolves a raw device PIN (the "user ID" a fingerprint device sends on a
 * punch) to a Student or Teacher record.
 *
 * A PIN must only ever be matched against `students.student_no` or
 * `teachers.teacher_no` — the fields we actually push to devices — never
 * against a model's internal auto-increment `id`. Teacher numbers can start
 * as low as 1, which happens to collide with row IDs in any table, so
 * matching on `id` silently attributes punches to the wrong person.
 *
 * `device_for` narrows the search: a device dedicated to one group is never
 * even queried against the other table, which also protects against a
 * coincidental PIN collision across groups on a shared/mixed device.
 */
class UserResolver
{
    public const UNKNOWN   = 'unknown';   // PIN matches neither table
    public const AMBIGUOUS = 'ambiguous'; // PIN matches BOTH tables (data problem)

    /**
     * @return array{
     *     user_type: ?string,
     *     student_no: ?string,
     *     teacher_no: ?string,
     *     name: ?string,
     *     status: ?string,
     * }
     */
    public static function resolve(string $pin, ?Device $device = null): array
    {
        $pin   = trim($pin);
        $scope = $device->device_for ?? 'student_teacher';

        $student = in_array($scope, ['student', 'student_teacher'], true) && $pin !== ''
            ? Student::where('student_no', $pin)->first()
            : null;

        $teacher = in_array($scope, ['teacher', 'student_teacher'], true) && $pin !== ''
            ? Teacher::where('teacher_no', $pin)->first()
            : null;

        // Same PIN registered under both a student and a teacher — a data
        // problem on a mixed device. Don't silently guess; flag it.
        if ($student && $teacher) {
            return [
                'user_type' => null, 'student_no' => null, 'teacher_no' => null,
                'name' => null, 'status' => self::AMBIGUOUS,
            ];
        }

        if ($student) {
            return [
                'user_type'  => 'student',
                'student_no' => (string) $student->student_no,
                'teacher_no' => null,
                'name'       => showStudentFullName($student->firstname, $student->middlename, $student->lastname),
                'status'     => null,
            ];
        }

        if ($teacher) {
            return [
                'user_type'  => 'teacher',
                'student_no' => null,
                'teacher_no' => (string) $teacher->teacher_no,
                'name'       => $teacher->name,
                'status'     => null,
            ];
        }

        return [
            'user_type' => null, 'student_no' => null, 'teacher_no' => null,
            'name' => null, 'status' => $pin === '' ? null : self::UNKNOWN,
        ];
    }

    /**
     * Build the firstOrCreate() criteria + attributes for an AttendanceLog
     * row from a resolved PIN, keeping unmatched punches distinctly keyed
     * (via `unmatched_pin`) instead of being merged into an unrelated row.
     */
    public static function attendanceLogFields(string $pin, array $resolved, string $deviceSerial, \Illuminate\Support\Carbon $punchTime): array
    {
        $criteria = [
            'device_serial' => $deviceSerial,
            'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
        ];

        if ($resolved['student_no']) {
            $criteria['student_no'] = $resolved['student_no'];
        } elseif ($resolved['teacher_no']) {
            $criteria['teacher_no'] = $resolved['teacher_no'];
        } else {
            $criteria['unmatched_pin'] = $pin;
        }

        return $criteria;
    }
}
