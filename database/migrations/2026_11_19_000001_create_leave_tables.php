<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave management (per institute):
 *  - leave_types: each institute's own types (Casual, Medical, ...)
 *  - leaves:      a teacher/staff member's leave for a date range; `days` is the
 *                 number of working days in it (the institute's weekly and office
 *                 holidays are not counted).
 *
 * Reports read `leaves` to show L (Leave) instead of A (Absent).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->nullable()->index();
                $table->string('name', 100);
                $table->string('code', 10)->nullable();             // short label, e.g. CL, ML
                $table->unsignedSmallInteger('days_per_year')->nullable(); // allowance (optional, for information)
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('leaves')) {
            Schema::create('leaves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institute_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->index();
                $table->unsignedBigInteger('leave_type_id')->index();
                $table->date('from_date');
                $table->date('to_date');
                $table->unsignedSmallInteger('days')->default(0);   // working days in the range
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['institute_id', 'from_date', 'to_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('leave_types');
    }
};
