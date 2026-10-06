<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_fee_id');
            $table->string('transaction_id')->unique();
            $table->string('gateway')->nullable(); // ssl_commerz, bkash, rocket, nagad
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED', 'REFUNDED'])->default('PENDING');
            $table->decimal('transaction_amount', 10, 2);
            $table->string('currency')->default('BDT');
            
            // SSL Commerz specific fields
            $table->string('val_id')->nullable();
            $table->string('bank_tran_id')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_no')->nullable();
            $table->string('card_issuer')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_sub_brand')->nullable();
            $table->string('card_issuer_country')->nullable();
            $table->string('card_issuer_country_code')->nullable();
            $table->decimal('store_amount', 10, 2)->nullable();
            $table->text('verify_sign')->nullable();
            $table->text('verify_key')->nullable();
            $table->text('verify_sign_sha2')->nullable();
            $table->decimal('currency_amount', 10, 2)->nullable();
            $table->decimal('currency_rate', 10, 4)->nullable();
            $table->string('risk_level')->nullable();
            $table->string('risk_title')->nullable();
            
            // Refund fields
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_ref_id')->nullable();
            $table->timestamp('refund_date')->nullable();
            $table->string('refund_status')->nullable();
            $table->text('refund_remarks')->nullable();
            
            $table->string('transaction_ip')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
};
