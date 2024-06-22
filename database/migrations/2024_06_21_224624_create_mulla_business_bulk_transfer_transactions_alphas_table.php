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
        Schema::create('mulla_business_bulk_transfer_transactions_alphas', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_id');
            $table->string('transfer_code')->nullable();
            $table->string('reference');
            $table->string('recipient_code');
            $table->string('currency')->nullable();
            $table->bigInteger('amount');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulla_business_bulk_transfer_transactions_alphas');
    }
};
