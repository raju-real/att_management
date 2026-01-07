<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "teachers";

    public static function getTeacherSlNo(): string
    {
        $last = self::withTrashed()
            ->orderBy('teacher_sl_no', 'desc')
            ->value('teacher_sl_no');

        $next = $last ? intval($last) + 1 : 1;

        return str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public static function getTeacherNo(): int
    {
        // Get max teacher_sl_no and increment
       $last = self::withTrashed()->max('teacher_sl_no') ?? 10001; // start from 1000
        return $last + 1;
    }

    public static function getTeacherNoOld(): string
    {
        $lastUniqueTeacherNo = Teacher::latest('teacher_sl_no')->first();
        // Start from 10001
        $newUniqueId = '10001';
        if ($lastUniqueTeacherNo) {
            $lastEmpId = $lastUniqueTeacherNo->teacher_sl_no;

            if ($lastEmpId !== null && is_numeric($lastEmpId)) {
                $newSerialNumber = (int)$lastEmpId + 1;
                $newUniqueId = (string)$newSerialNumber;
            }
        }
        if (Teacher::where('teacher_sl_no', $newUniqueId)->exists()) {
            return self::getTeacherNo(); // IMPORTANT: return it
        }
        return $newUniqueId;
    }
}
