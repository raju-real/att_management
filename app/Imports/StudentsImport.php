<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    /** Every imported student belongs to this institute (defaults to the logged-in one). */
    public function __construct(protected ?int $instituteId = null)
    {
        $this->instituteId ??= authInstituteId();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // student_id and firstname are required
            if (empty($row['student_id']) || empty($row['firstname'])) {
                continue;
            }

            // Check if student already exists by student_id to prevent duplicates
            $existing = Student::withoutGlobalScope(\App\Models\Scopes\InstituteScope::class)
                ->where('institute_id', $this->instituteId)->where('student_id', $row['student_id'])->first();
            if ($existing) {
                continue;
            }

            $student = new Student();
            $student->institute_id = $this->instituteId;
            $student->student_no = Student::getStudentNo(); // Auto Generate
            $student->student_id = $row['student_id'];
            $student->firstname = $row['firstname'];
            $student->middlename = $row['middlename'] ?? null;
            $student->lastname = $row['lastname'] ?? null;
            $student->nickname = $row['nickname'] ?? null;
            $student->class = $row['class'] ?? null;
            $student->section = $row['section'] ?? null;
            $student->roll = $row['roll'] ?? null;
            $student->shift = $row['shift'] ?? null;
            $student->medium = $row['medium'] ?? null;
            $student->group = $row['group'] ?? null;
            $student->save();
        }
    }
}
