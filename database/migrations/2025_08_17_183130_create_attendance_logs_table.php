<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['student', 'teacher'])->nullable();
            $table->string('student_no', 191)->nullable();
            $table->string('student_id', 191)->nullable();
            $table->string('teacher_no', 191)->nullable();
            $table->string('name', 191)->nullable();
            $table->integer('device_id')->nullable();
            $table->string('device_serial', 255)->nullable();
            $table->timestamp('punch_time')->nullable();
            $table->string('pin')->nullable();
            $table->dateTime('attendance_time')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('verify_type')->nullable();
            $table->string('work_code')->nullable();
            $table->string('raw_data')->nullable();
            $table->enum('attendance_by', ['fingerprint', 'card', 'face', 'pin', 'manual'])->default('fingerprint');
            $table->timestamps();
            // ✅ STUDENT attendance uniqueness
            // $table->unique(['student_no', 'punch_time', 'device_serial'], 'uniq_student_attendance');
            // // ✅ TEACHER attendance uniqueness
            // $table->unique(['teacher_no', 'punch_time', 'device_serial'], 'uniq_teacher_attendance');
            /*
            |--------------------------------------------------------------------------
            | INDEXES (PERFORMANCE)
            |--------------------------------------------------------------------------
            */

            $table->index('user_type', 'idx_attendance_user_type');
            $table->index('student_no', 'idx_attendance_student_no');
            $table->index('teacher_no', 'idx_attendance_teacher_no');
            $table->index('punch_time', 'idx_attendance_punch_time');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            // DROP UNIQUE
            $table->dropUnique('uniq_student_attendance');
            $table->dropUnique('uniq_teacher_attendance');
            // DROP INDEXES
            $table->dropIndex('idx_attendance_user_type');
            $table->dropIndex('idx_attendance_student_no');
            $table->dropIndex('idx_attendance_teacher_no');
            $table->dropIndex('idx_attendance_punch_time');
        });
    }
};
