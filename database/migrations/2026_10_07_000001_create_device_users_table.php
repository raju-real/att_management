<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users enrolled on each fingerprint device (PIN + name), as last reported
 * by the device. Filled by "Fetch Users": push devices upload them after a
 * DATA QUERY USERINFO command; TCP devices are read live.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('device_users')) {
            return;
        }

        Schema::create('device_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->index();
            $table->string('pin', 50);
            $table->string('name', 191)->nullable();
            $table->unsignedTinyInteger('privilege')->default(0)->comment('0 = user, 14 = admin');
            $table->string('card', 50)->nullable();
            $table->timestamp('received_at')->nullable()->comment('last time the device reported this user');
            $table->timestamps();

            $table->unique(['device_id', 'pin']);
            $table->index('pin');
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_users');
    }
};
