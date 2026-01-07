<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('serial_no')->unique();
            $table->string('ip_address')->nullable();
            $table->string('device_port')->nullable();
            $table->string('comm_key')->nullable()->comment("Communication Key| Menu → Comm / Network → Comm Key/ Menu ->Pc Connection(Comm Key) | 0 (most devices) | Sometimes 12345");
            $table->enum('device_for', ['student', 'teacher']);
            $table->enum('status', ['active', 'inactive'])->default("active");
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devices');
    }
};
