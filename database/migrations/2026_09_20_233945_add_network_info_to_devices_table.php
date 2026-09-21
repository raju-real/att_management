<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purely informational fields so a multi-building / multi-subnet
     * deployment can be documented per device inside the app itself
     * (shown in the device list and the setup guide) instead of only
     * in an external spreadsheet.
     */
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('subnet_label', 100)->nullable()->after('device_port');
            $table->string('gateway_ip', 45)->nullable()->after('subnet_label');
            $table->string('location_note', 255)->nullable()->after('gateway_ip');
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['subnet_label', 'gateway_ip', 'location_note']);
        });
    }
};
