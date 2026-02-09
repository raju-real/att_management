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
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->uuid('unique_id')->unique();
            $table->foreignId('fee_lot_id')->constrained('fee_lots')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'partial'])->default('pending');
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('transaction_id')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_fees');
    }
};
