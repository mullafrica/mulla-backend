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
            $table->string('vtp_request_id')->nullable();
            $table->integer('vtp_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mulla_user_transactions', function (Blueprint $table) {
            $table->dropColumn('vtp_request_id');
            $table->dropColumn('vtp_status');
        });
    }
};
