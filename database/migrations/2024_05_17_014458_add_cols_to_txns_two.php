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
        Schema::table('mulla_user_transactions', function (Blueprint $table) {
            $table->string('voucher_code')->nullable();
            $table->string('voucher_serial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mulla_user_transactions', function (Blueprint $table) {
            //
        });
    }
};
