<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "students";

    public static function getStudentNo(): int
    {
        // Get max student_sl_no and increment
        // CAST: student_no is a varchar, and a plain MAX() compares as text ('999' > '10001').
        $last = (int) (self::withTrashed()->selectRaw('MAX(CAST(student_no AS UNSIGNED)) AS m')->value('m') ?: 10000);
        $next = $last + 1;
        // Never hand out a device PIN that a teacher already uses.
        while (Teacher::withTrashed()->where('teacher_no', (string) $next)->exists()) {
            $next++;
        }
        return $next;
    }

    public static function getStdNo(): string
    {
        $lastUniqueStdNo = Student::latest('std_no')->first();
        // Start from 10001
        $newUniqueId = '10001';
        if ($lastUniqueStdNo) {
            $lastEmpId = $lastUniqueStdNo->std_no;

            if ($lastEmpId !== null && is_numeric($lastEmpId)) {
                $newSerialNumber = (int)$lastEmpId + 1;
                $newUniqueId = (string)$newSerialNumber;
            }
        }
        if (Student::where('std_no', $newUniqueId)->exists()) {
            return self::getStdNo(); // IMPORTANT: return it
        }
        return $newUniqueId;
    }
}
