<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Columns: teacher_no, name, email, mobile, designation [, department]
 *
 * Upsert by teacher_no:
 *  - existing teacher (even soft-deleted, since teacher_no is unique in the
 *    DB) → updated (and restored if it was deleted)
 *  - otherwise → created
 *
 * Blank cells never wipe existing data on update. The optional `department`
 * column (department name) overrides the default department chosen on the
 * upload form; new teachers fall back to that default.
 */
class TeachersImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $updated = 0;
    public array $skipped = [];

    protected Collection $departments;

    public function __construct(protected ?int $defaultDepartmentId = null)
    {
        $this->departments = Department::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id]);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // + heading row, 1-based

            $teacherNo = $this->cell($row, 'teacher_no');
            $name      = $this->cell($row, 'name');

            if ($teacherNo === null && $name === null) {
                continue; // blank line
            }
            if ($teacherNo === null) {
                $this->skipped[] = "Row {$line}: teacher_no is empty.";
                continue;
            }
            if (mb_strlen($teacherNo) > 50) {
                $this->skipped[] = "Row {$line}: teacher_no is too long.";
                continue;
            }

            $email = $this->cell($row, 'email');
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->skipped[] = "Row {$line} ({$teacherNo}): invalid email \"{$email}\".";
                continue;
            }

            $departmentId = null;
            if (($deptName = $this->cell($row, 'department')) !== null) {
                $departmentId = $this->departments->get(mb_strtolower($deptName));
                if (!$departmentId) {
                    $this->skipped[] = "Row {$line} ({$teacherNo}): department \"{$deptName}\" not found.";
                    continue;
                }
            }

            $teacher = Teacher::withTrashed()->where('teacher_no', $teacherNo)->first();
            $isNew   = !$teacher;

            if ($isNew) {
                if ($name === null) {
                    $this->skipped[] = "Row {$line} ({$teacherNo}): name is required for a new teacher.";
                    continue;
                }
                $departmentId ??= $this->defaultDepartmentId;
                if (!$departmentId) {
                    $this->skipped[] = "Row {$line} ({$teacherNo}): no department (add a department column or choose a default).";
                    continue;
                }
                $teacher = new Teacher();
                $teacher->teacher_no = $teacherNo;
            }

            // Only overwrite with values actually present in the file.
            foreach (['name', 'email', 'mobile', 'designation'] as $field) {
                $value = $this->cell($row, $field);
                if ($value !== null) {
                    $teacher->{$field} = mb_substr($value, 0, 255);
                }
            }
            if ($departmentId) {
                $teacher->department_id = $departmentId;
            }

            try {
                if ($teacher->trashed()) {
                    $teacher->deleted_at = null;
                }
                $teacher->save();
                $isNew ? $this->created++ : $this->updated++;
            } catch (\Throwable $e) {
                $this->skipped[] = "Row {$line} ({$teacherNo}): could not save — " . $e->getMessage();
            }
        }
    }

    /**
     * Trimmed string value or null. Excel hands numeric cells back as
     * int/float (e.g. 101.0), so whole numbers are normalised to "101".
     */
    protected function cell($row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (is_float($value) && floor($value) == $value) {
            $value = (string) (int) $value;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
