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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_no')->nullable();
            $table->string('student_id',191)->nullable();
            $table->string('firstname',191)->nullable();
            $table->string('middlename',191)->nullable();
            $table->string('lastname',191)->nullable();
            $table->string('nickname',191)->nullable();
            $table->string('photo',255)->nullable();
            $table->string('nationality',191)->nullable();
            $table->string('religion',191)->nullable();
            $table->string('gender',191)->nullable();
            $table->string('class',191)->nullable();
            $table->string('roll',191)->nullable();
            $table->string('section',191)->nullable();
            $table->string('shift',191)->nullable();
            $table->string('medium',191)->nullable();
            $table->string('group',191)->nullable();
            $table->string('fname',191)->nullable();
            $table->string('fmobile',191)->nullable();
            $table->string('mname',191)->nullable();
            $table->string('mmobile',191)->nullable();
            $table->string('bloodgroup',191)->nullable();
            $table->string('mobile',191)->nullable();
            $table->string('session',191)->nullable();
            $table->integer('school_id')->nullable();
            $table->integer('votter_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('g_date',191)->nullable();
            $table->string('status',191)->nullable();
            $table->string('sms_status',191)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
};
