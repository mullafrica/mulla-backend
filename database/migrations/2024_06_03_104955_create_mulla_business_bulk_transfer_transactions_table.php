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
        Schema::create('mulla_business_bulk_transfer_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('bulk_transfer_id');
            $table->string('reference');
            $table->string('pt_recipient_id');
            $table->string('currency');
            $table->bigInteger('amount');
            $table->string('recipient_account_no');
            $table->string('recipient_account_name');
            $table->string('recipient_bank');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulla_business_bulk_transfer_transactions');
    }
};
