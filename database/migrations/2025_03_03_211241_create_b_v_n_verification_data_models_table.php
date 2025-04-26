<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('b_v_n_verification_data_models', function (Blueprint $table) {
            $table->id();
            $table->string('bvn_id')->nullable();
            $table->string('bvn')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('otherName')->nullable();
            $table->string('dateOfBirth')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('enrollmentBank')->nullable();
            $table->string('enrollmentBranch')->nullable();
            $table->text('image', 'longtext')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_v_n_verification_data_models');
    }
};
