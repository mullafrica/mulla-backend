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
            $table->string('provider')->default('vtpass')->after('vtp_status');
            $table->text('notes')->nullable()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mulla_user_transactions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'notes']);
        });
    }
};
