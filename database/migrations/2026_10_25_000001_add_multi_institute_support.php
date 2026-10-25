<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-institute support.
 *
 *  - users.role: 'admin' (super admin, sees everything) | 'institute'
 *    An institute IS a user row (it logs in), with its letterhead details.
 *  - institute_id (= users.id of the institute) on every institute-owned table.
 *  - teacher_no is unique per institute (two schools may both have #101).
 *
 * Existing rows keep institute_id NULL ("unassigned"); assign them with
 *   php artisan institute:assign {institute_id}
 */
return new class extends Migration
{
    protected array $tables = ['teachers', 'students', 'departments', 'shifts', 'devices', 'attendance_logs', 'device_users'];

    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'institute'])->default('institute')->after('id')->index();
            }
            foreach (['address' => 255, 'eiin' => 30, 'website' => 100, 'logo' => 255] as $col => $len) {
                if (! Schema::hasColumn('users', $col)) {
                    $table->string($col, $len)->nullable();
                }
            }
        });
        // institute logins need longer e-mails than 30 chars
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(100) NOT NULL');
        // the existing account(s) are the super admin
        DB::table('users')->update(['role' => 'admin']);

        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && ! Schema::hasColumn($t, 'institute_id')) {
                Schema::table($t, fn (Blueprint $table) => $table->unsignedBigInteger('institute_id')->nullable()->after('id')->index());
            }
        }

        // fast institute + date range reports
        Schema::table('attendance_logs', fn (Blueprint $t) => $t->index(['institute_id', 'punch_time'], 'idx_al_institute_punch'));

        // teacher_no: unique per institute instead of globally
        $idx = collect(DB::select('SHOW INDEX FROM teachers'))->pluck('Key_name');
        if ($idx->contains('teachers_teacher_no_unique')) {
            Schema::table('teachers', fn (Blueprint $t) => $t->dropUnique('teachers_teacher_no_unique'));
        }
        Schema::table('teachers', fn (Blueprint $t) => $t->unique(['institute_id', 'teacher_no'], 'uniq_teachers_institute_no'));

        // Teacher/Staff profile fields (Teacher's Profile report)
        Schema::table('teachers', function (Blueprint $table) {
            $cols = [
                'date_of_birth'     => fn () => $table->date('date_of_birth')->nullable(),
                'gender'            => fn () => $table->string('gender', 20)->nullable(),
                'blood_group'       => fn () => $table->string('blood_group', 5)->nullable(),
                'religion'          => fn () => $table->string('religion', 30)->nullable(),
                'subject'           => fn () => $table->string('subject', 100)->nullable(),
                'present_address'   => fn () => $table->text('present_address')->nullable(),
                'permanent_address' => fn () => $table->text('permanent_address')->nullable(),
                'education'         => fn () => $table->json('education')->nullable(),
                'newspaper_ad'      => fn () => $table->string('newspaper_ad', 191)->nullable(),
                'joining_date'      => fn () => $table->date('joining_date')->nullable(),
                'joining_post'      => fn () => $table->string('joining_post', 100)->nullable(),
                'time_scale'        => fn () => $table->string('time_scale', 100)->nullable(),
                'mpo_index'         => fn () => $table->string('mpo_index', 50)->nullable(),
                'class_teacher'     => fn () => $table->json('class_teacher')->nullable(),
                'subject_teacher'   => fn () => $table->json('subject_teacher')->nullable(),
            ];
            foreach ($cols as $name => $add) {
                if (! Schema::hasColumn('teachers', $name)) {
                    $add();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('uniq_teachers_institute_no');
            $table->unique('teacher_no');
            $table->dropColumn(['date_of_birth', 'gender', 'blood_group', 'religion', 'subject', 'present_address', 'permanent_address',
                'education', 'newspaper_ad', 'joining_date', 'joining_post', 'time_scale', 'mpo_index', 'class_teacher', 'subject_teacher']);
        });
        Schema::table('attendance_logs', fn (Blueprint $t) => $t->dropIndex('idx_al_institute_punch'));
        foreach ($this->tables as $t) {
            if (Schema::hasColumn($t, 'institute_id')) {
                Schema::table($t, fn (Blueprint $table) => $table->dropColumn('institute_id'));
            }
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'address', 'eiin', 'website', 'logo']));
    }
};
