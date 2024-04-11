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
        Schema::create('mulla_user_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('payment_reference')->nullable();
            $table->string('bill_reference')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('cashback', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->string('type')->nullable();
            $table->string('bill_token')->nullable();
            $table->string('bill_units')->nullable();
            $table->string('bill_device_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulla_user_transactions');
    }
};
