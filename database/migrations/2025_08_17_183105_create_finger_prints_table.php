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
        Schema::create('finger_prints', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id');
            $table->unsignedTinyInteger('finger_index')->default(0);
            $table->longText('template'); // base64 encoded template
            $table->string('format')->default('ZK'); // optional
            $table->foreignId('source_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->integer('deleted_by')->nullable();
            $table->unique(['employee_id', 'finger_index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finger_prints');
    }
};
