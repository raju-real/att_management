<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holds the raw device PIN when a punch can't be matched to a student or
     * teacher (unknown PIN, or the same PIN exists on both — a genuine
     * collision on a mixed student+teacher device). Lets these show up in an
     * "Unmatched Attendance" review screen instead of being silently guessed.
     */
    public function up()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->string('unmatched_pin', 191)->nullable()->after('teacher_no');
            $table->index('unmatched_pin', 'idx_attendance_unmatched_pin');
        });
    }

    public function down()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_unmatched_pin');
            $table->dropColumn('unmatched_pin');
        });
    }
};
