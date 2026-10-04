<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original attendance_logs table had a `student_id` column (the
 * student's own roll/ID number, separate from `student_no` which is the
 * device PIN) that AttendanceService's reports still select/group by.
 * Missed when extending zkteco_attendance_logs — added back here.
 */
return new class extends Migration
{
    public function up()
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::table($prefix.'attendance_logs', function (Blueprint $table) {
            $table->string('student_id', 191)->nullable()->after('student_no');
            $table->index('student_id', 'idx_zatt_student_id');
        });
    }

    public function down()
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::table($prefix.'attendance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_zatt_student_id');
            $table->dropColumn('student_id');
        });
    }
};
