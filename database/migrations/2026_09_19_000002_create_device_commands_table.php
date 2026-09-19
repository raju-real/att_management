<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the device_commands table.
 *
 * Used by iClock push mode: the admin queues a command here and the ZkTeco
 * device picks it up the next time it polls /iclock/getrequest.
 *
 * Supported command_text examples:
 *   DATA UPDATE USERINFO PIN=1001 Name=John Card= Privilege=0
 *   DATA DELETE USERINFO PIN=1001
 *   CLEAR ATTLOG
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->index();
            $table->text('command_text');
            $table->enum('status', ['pending', 'sent', 'done', 'failed'])->default('pending')->index();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
