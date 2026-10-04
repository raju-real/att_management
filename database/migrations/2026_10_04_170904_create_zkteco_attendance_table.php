<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_attendance', function (Blueprint $table) {

            $table->id();

            $table->string('serial_number');

            $table->string('pin');

            $table->dateTime('attendance_time');

            $table->unsignedTinyInteger('status')->default(0);

            $table->unsignedTinyInteger('verify_type')->nullable();

            $table->string('work_code')->nullable();

            $table->string('raw_data')->nullable();

            $table->timestamps();

            $table->unique([
                'device_id',
                'pin',
                'attendance_time',
                'status',
                'verify_type'
            ], 'zk_attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_attendance');
    }
};
