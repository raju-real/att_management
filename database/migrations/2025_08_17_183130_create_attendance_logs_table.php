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
            $table->string('std_no', 191)->nullable();
            $table->string('student_id', 191)->nullable();
            $table->string('teacher_no', 191)->nullable();
            $table->integer('device_id')->nullable();
            $table->string('device_serial', 255)->nullable();
            $table->timestamp('punch_time')->nullable();
            $table->enum('attendance_by', ['fingerprint', 'card', 'face', 'pin', 'manual'])->default('fingerprint');
            $table->string('client_ip')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_text')->nullable(); // optional reverse geocoded address
            $table->json('raw_payload')->nullable();
            $table->string('verify_mode')->nullable(); // fingerprint, card, face, pin, password, etc.
            $table->string('work_code')->nullable(); // work code from device, if any
            $table->string('punch_type')->nullable(); // work code from device, if any
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            // ✅ STUDENT attendance uniqueness
            $table->unique(['std_no', 'punch_time', 'device_serial'], 'uniq_student_attendance');
            // ✅ TEACHER attendance uniqueness
            $table->unique(['teacher_no', 'punch_time', 'device_serial'], 'uniq_teacher_attendance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};
