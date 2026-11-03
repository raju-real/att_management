<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Institute users ("inst_user"): staff accounts created by an institute.
 * They see that institute's data only, and only the menus in `permissions`.
 *
 *   users.role         admin | institute | inst_user
 *   users.institute_id the institute (users.id) an inst_user belongs to
 *   users.permissions  JSON list of menu keys (App\Support\Permissions)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','institute','inst_user') NOT NULL DEFAULT 'institute'");

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'institute_id')) {
                $table->unsignedBigInteger('institute_id')->nullable()->after('role')->index();
            }
            if (! Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('institute_id');
            }
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'inst_user')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['institute_id']);
            $table->dropColumn(['institute_id', 'permissions']);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','institute') NOT NULL DEFAULT 'institute'");
    }
};
