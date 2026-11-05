<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each institute's own settings (JSON on its users row):
 *   in_time, out_time, weekly_holidays, office_holidays, contact_email, contact_mobile
 *
 * A key that was never saved falls back to the global Site Settings
 * (see instituteSettings()), so nothing changes until the institute saves.
 * contact_email is separate from users.email, which is the login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'settings')) {
                $table->json('settings')->nullable()->after('permissions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
