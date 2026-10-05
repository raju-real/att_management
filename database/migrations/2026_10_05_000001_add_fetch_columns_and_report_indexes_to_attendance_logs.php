<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Columns the device fetch paths (TCP pull via DeviceActivityService,
 *    iClock push, UserResolver, healUnmatchedAttendance) still write/read.
 *    Without them those inserts fail with "Unknown column". All nullable,
 *    and only added when missing, so this is safe on any existing DB.
 *
 * 2. Composite indexes for the report queries, which always filter on a
 *    punch_time range together with user_type / teacher_no / student_no.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_logs', 'unmatched_pin')) {
                $table->string('unmatched_pin', 191)->nullable()->after('teacher_no');
            }
            if (! Schema::hasColumn('attendance_logs', 'punch_type')) {
                $table->string('punch_type', 20)->nullable()->after('attendance_by');
            }
            if (! Schema::hasColumn('attendance_logs', 'verify_mode')) {
                $table->string('verify_mode', 50)->nullable()->after('punch_type');
            }
            if (! Schema::hasColumn('attendance_logs', 'raw_payload')) {
                $table->text('raw_payload')->nullable()->after('verify_mode');
            }
        });

        $existing = collect(DB::select('SHOW INDEX FROM attendance_logs'))->pluck('Key_name')->unique();

        Schema::table('attendance_logs', function (Blueprint $table) use ($existing) {
            if (! $existing->contains('idx_al_punch_type_user')) {
                $table->index(['punch_time', 'user_type'], 'idx_al_punch_type_user');
            }
            if (! $existing->contains('idx_al_teacher_punch')) {
                $table->index(['teacher_no', 'punch_time'], 'idx_al_teacher_punch');
            }
            if (! $existing->contains('idx_al_student_punch')) {
                $table->index(['student_no', 'punch_time'], 'idx_al_student_punch');
            }
            if (! $existing->contains('idx_al_unmatched_pin')) {
                $table->index('unmatched_pin', 'idx_al_unmatched_pin');
            }
        });

        // Departments/teachers are joined on these on every report row.
        $deptIdx = collect(DB::select('SHOW INDEX FROM departments'))->pluck('Key_name');
        if (! $deptIdx->contains('idx_departments_shift_id')) {
            Schema::table('departments', fn (Blueprint $t) => $t->index('shift_id', 'idx_departments_shift_id'));
        }
        $teacherIdx = collect(DB::select('SHOW INDEX FROM teachers'))->pluck('Key_name');
        if (! $teacherIdx->contains('idx_teachers_department_id')) {
            Schema::table('teachers', fn (Blueprint $t) => $t->index('department_id', 'idx_teachers_department_id'));
        }
    }

    public function down()
    {
        Schema::table('teachers', fn (Blueprint $t) => $t->dropIndex('idx_teachers_department_id'));
        Schema::table('departments', fn (Blueprint $t) => $t->dropIndex('idx_departments_shift_id'));

        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_al_punch_type_user');
            $table->dropIndex('idx_al_teacher_punch');
            $table->dropIndex('idx_al_student_punch');
            $table->dropIndex('idx_al_unmatched_pin');
            $table->dropColumn(['unmatched_pin', 'punch_type', 'verify_mode', 'raw_payload']);
        });
    }
};
