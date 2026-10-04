<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends athwari/laravel-zkteco-adms's own protocol tables with the
 * business columns this app needs (device_for scoping, admin enable/disable,
 * Department/Shift-linked attendance resolution), so the package's tables
 * become the single source of truth for device activity instead of running
 * a parallel schema next to them.
 *
 * The old hand-rolled devices/attendance_logs/device_commands tables (from
 * the jmrashed/zkteco era) are renamed with a _legacy_ suffix rather than
 * dropped, preserving any historical rows.
 */
return new class extends Migration
{
    public function up()
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::table($prefix.'devices', function (Blueprint $table) use ($prefix) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('serial_no', 64)->nullable()->unique()->after('serial_number');
            $table->string('device_port')->nullable()->after('ip_address');
            $table->string('subnet_label', 100)->nullable()->after('device_port');
            $table->string('gateway_ip', 45)->nullable()->after('subnet_label');
            $table->string('location_note', 255)->nullable()->after('gateway_ip');
            $table->string('comm_key')->nullable()->after('location_note');
            $table->enum('device_for', ['student_teacher', 'student', 'teacher'])->default('student_teacher')->after('comm_key');
            $table->boolean('use_push_mode')->default(true)->after('device_for');
            $table->timestamp('last_seen_at')->nullable()->after('last_activity_at');
            $table->timestamp('last_synced_at')->nullable()->after('last_seen_at');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->index('device_for');
        });

        // 'status' already exists as a plain string column (default 'unknown')
        // from the package's own migration — repurposed here for admin
        // enable/disable ('active'/'inactive'); connectivity tracking moves
        // to last_activity_at/last_seen_at instead (see App\Models\Device).
        DB::statement("ALTER TABLE {$prefix}devices ALTER COLUMN status SET DEFAULT 'active'");
        DB::statement("UPDATE {$prefix}devices SET status = 'active' WHERE status = 'unknown'");

        Schema::table($prefix.'attendance_logs', function (Blueprint $table) {
            $table->enum('user_type', ['student', 'teacher'])->nullable()->after('pin');
            $table->string('student_no', 191)->nullable()->after('user_type');
            $table->string('teacher_no', 191)->nullable()->after('student_no');
            $table->string('unmatched_pin', 191)->nullable()->after('teacher_no');
            $table->string('name', 191)->nullable()->after('unmatched_pin');
            $table->string('device_serial', 255)->nullable()->after('device_id');
            $table->timestamp('punch_time')->nullable()->after('occurred_at');
            $table->enum('attendance_by', ['fingerprint', 'card', 'face', 'pin', 'manual'])->default('fingerprint');
            $table->string('punch_type')->nullable();
            $table->string('client_ip')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_text')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->softDeletes();

            $table->index('user_type', 'idx_zatt_user_type');
            $table->index('student_no', 'idx_zatt_student_no');
            $table->index('teacher_no', 'idx_zatt_teacher_no');
            $table->index('unmatched_pin', 'idx_zatt_unmatched_pin');
            $table->index('punch_time', 'idx_zatt_punch_time');
            $table->unique(['student_no', 'punch_time', 'device_serial'], 'uniq_zatt_student');
            $table->unique(['teacher_no', 'punch_time', 'device_serial'], 'uniq_zatt_teacher');
        });

        foreach (['devices', 'attendance_logs', 'device_commands'] as $legacy) {
            if (Schema::hasTable($legacy) && ! Schema::hasTable($legacy.'_legacy')) {
                Schema::rename($legacy, $legacy.'_legacy');
            }
        }
    }

    public function down()
    {
        foreach (['devices', 'attendance_logs', 'device_commands'] as $legacy) {
            if (Schema::hasTable($legacy.'_legacy') && ! Schema::hasTable($legacy)) {
                Schema::rename($legacy.'_legacy', $legacy);
            }
        }

        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::table($prefix.'attendance_logs', function (Blueprint $table) {
            $table->dropUnique('uniq_zatt_student');
            $table->dropUnique('uniq_zatt_teacher');
            $table->dropIndex('idx_zatt_user_type');
            $table->dropIndex('idx_zatt_student_no');
            $table->dropIndex('idx_zatt_teacher_no');
            $table->dropIndex('idx_zatt_unmatched_pin');
            $table->dropIndex('idx_zatt_punch_time');
            $table->dropColumn([
                'user_type', 'student_no', 'teacher_no', 'unmatched_pin', 'name',
                'device_serial', 'punch_time', 'attendance_by', 'punch_type',
                'client_ip', 'latitude', 'longitude', 'location_text',
                'created_by', 'updated_by', 'deleted_by', 'deleted_at',
            ]);
        });

        Schema::table($prefix.'devices', function (Blueprint $table) {
            $table->dropIndex('device_for');
            $table->dropColumn([
                'slug', 'serial_no', 'device_port', 'subnet_label', 'gateway_ip',
                'location_note', 'comm_key', 'device_for', 'use_push_mode',
                'last_seen_at', 'last_synced_at', 'created_by', 'updated_by', 'deleted_by',
            ]);
        });
    }
};
