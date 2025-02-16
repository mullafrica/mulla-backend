<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("mulla_business_bank_accounts_models", function (
            Blueprint $table
        ) {
            $table->id();
            $table->unsignedBigInteger("business_id");
            $table->string("account_name");
            $table->string("account_number");
            $table->string("bank");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("mulla_business_bank_accounts_models");
    }
};
