<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('device_id')->nullable();
            // $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['add_pending', 'delete_pending', 'synced', 'failed'])->default('add_pending');
            $table->timestamp('synced_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->unique(['employee_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_employees');
    }
};
