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
        Schema::create('mulla_business_bulk_transfer_list_models', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('business_id');
            $table->string('title');
            $table->string('type')->default('bulk_transfer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mulla_business_bulk_transfer_list_models');
    }
};