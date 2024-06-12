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
        Schema::create('mulla_business_bulk_transfer_list_items_models', function (Blueprint $table) {
            $table->id();
            $table->string('list_id');
            $table->string('email');
            $table->bigInteger('amount');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('recipient_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulla_business_bulk_transfer_list_items_models');
    }
};