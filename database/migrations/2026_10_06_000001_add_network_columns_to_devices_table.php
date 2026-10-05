<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DeviceController::fillDevice() and the device views use these columns,
 * but the devices create-migration never had them, so adding/editing a
 * device failed with "Unknown column". Each one is only added if missing.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'subnet_label')) {
                $table->string('subnet_label', 100)->nullable()->after('device_port');
            }
            if (! Schema::hasColumn('devices', 'gateway_ip')) {
                $table->string('gateway_ip', 45)->nullable()->after('subnet_label');
            }
            if (! Schema::hasColumn('devices', 'location_note')) {
                $table->string('location_note', 255)->nullable()->after('gateway_ip');
            }
            if (! Schema::hasColumn('devices', 'use_push_mode')) {
                $table->boolean('use_push_mode')->default(true)->after('status')
                    ->comment('true = iClock/ADMS HTTP push mode; false = TCP/UDP direct connect');
            }
        });
    }

    public function down()
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['subnet_label', 'gateway_ip', 'location_note', 'use_push_mode']);
        });
    }
};
