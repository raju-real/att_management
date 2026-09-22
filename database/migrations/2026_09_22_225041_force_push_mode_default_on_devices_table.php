<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Push Mode (ADMS) is now the only supported connection mode — one
     * standard way to connect a device, in development and production
     * alike. Backfill any existing device still marked TCP-only and make
     * the column default to push mode for anything created going forward.
     *
     * Raw SQL (not ->change()) — this project doesn't have doctrine/dbal,
     * which Laravel's column-modification helper requires.
     */
    public function up()
    {
        DB::table('devices')->update(['use_push_mode' => true]);
        DB::statement('ALTER TABLE devices ALTER COLUMN use_push_mode SET DEFAULT 1');
    }

    public function down()
    {
        DB::statement('ALTER TABLE devices ALTER COLUMN use_push_mode SET DEFAULT 0');
    }
};
