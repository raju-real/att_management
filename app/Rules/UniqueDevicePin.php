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
 */
class UniqueDevicePin implements Rule
{
    protected ?string $owner = null;

    public function __construct(protected string $for)
    {
    }

    public function passes($attribute, $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        if ($this->for === 'teacher') {
            $student = Student::where('student_no', $value)->first(['firstname', 'lastname']);
            $this->owner = $student ? 'student ' . trim($student->firstname . ' ' . $student->lastname) : null;
        } else {
            $teacher = Teacher::withTrashed()->where('teacher_no', $value)->first(['name']);
            $this->owner = $teacher ? 'teacher ' . $teacher->name : null;
        }

        return $this->owner === null;
    }

    public function message(): string
    {
        return "This device ID is already used by {$this->owner}. Students and teachers must have different device IDs.";
    }
}
