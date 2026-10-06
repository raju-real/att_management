<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * attendance_logs no longer stores a name; reports look the student up by
 * student_no for every row, so student_no needs an index.
 */
return new class extends Migration
{
    public function up()
    {
        $exists = collect(DB::select('SHOW INDEX FROM students'))->pluck('Key_name')->contains('idx_students_student_no');
        if (! $exists) {
            Schema::table('students', fn (Blueprint $t) => $t->index('student_no', 'idx_students_student_no'));
        }
    }

    public function down()
    {
        Schema::table('students', fn (Blueprint $t) => $t->dropIndex('idx_students_student_no'));
    }
};
