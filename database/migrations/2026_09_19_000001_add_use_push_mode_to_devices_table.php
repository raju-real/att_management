<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds use_push_mode flag to devices table.
 *
 * When use_push_mode = true the device uses the iClock / ADMS HTTP push
 * protocol (device → server) instead of the TCP/UDP socket pull
 * (server → device). Push mode works on shared hosting and across subnets.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('use_push_mode')->default(false)
                  ->after('status')
                  ->comment('true = iClock HTTP push mode; false = TCP/UDP direct connect');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('use_push_mode');
        });
    }
};
