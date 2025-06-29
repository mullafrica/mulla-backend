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
        Schema::table('mulla_user_meter_numbers', function (Blueprint $table) {
            $table->string('validation_provider')->default('vtpass')->after('disco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mulla_user_meter_numbers', function (Blueprint $table) {
            $table->dropColumn('validation_provider');
        });
    }
};
