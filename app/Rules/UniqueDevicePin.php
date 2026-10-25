<?php

namespace App\Rules;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\Rule;

/**
 * Device PINs are shared by students (student_no) and teachers (teacher_no)
 * on a mixed device. A PIN used by both makes every punch ambiguous, so a
 * teacher_no must not exist as a student_no and vice versa.
 *
 *   new UniqueDevicePin('teacher')  → value must not be an existing student_no
 *   new UniqueDevicePin('student')  → value must not be an existing teacher_no
 *
 * Checked inside ONE institute only (each institute has its own devices, so
 * two schools may use the same numbers). $instituteId defaults to the
 * logged-in institute.
 */
class UniqueDevicePin implements Rule
{
    protected ?string $owner = null;

    public function __construct(protected string $for, protected ?int $instituteId = null)
    {
        $this->instituteId ??= authInstituteId();
    }

    public function passes($attribute, $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        if ($this->for === 'teacher') {
            $student = Student::withoutGlobalScope(\App\Models\Scopes\InstituteScope::class)
                ->where('institute_id', $this->instituteId)->where('student_no', $value)->first(['firstname', 'lastname']);
            $this->owner = $student ? 'student ' . trim($student->firstname . ' ' . $student->lastname) : null;
        } else {
            $teacher = Teacher::withoutGlobalScope(\App\Models\Scopes\InstituteScope::class)->withTrashed()
                ->where('institute_id', $this->instituteId)->where('teacher_no', $value)->first(['name']);
            $this->owner = $teacher ? 'teacher/staff ' . $teacher->name : null;
        }

        return $this->owner === null;
    }

    public function message(): string
    {
        return "This device ID is already used by {$this->owner}. Students and teachers/staff must have different device IDs.";
    }
}
