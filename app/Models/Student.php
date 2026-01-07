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
       $last = self::withTrashed()->max('student_sl_no') ?? 10000; // start from 1000
        return $last + 1;
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
