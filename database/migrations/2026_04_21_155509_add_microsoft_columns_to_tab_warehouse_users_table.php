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
        Schema::table('tabWarehouse Users', function (Blueprint $table) {
            $table->string('microsoft_id', 191)->nullable()->index();
            $table->string('microsoft_email', 191)->nullable();
            $table->string('microsoft_name', 191)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabWarehouse Users', function (Blueprint $table) {
            $table->dropIndex(['microsoft_id']);
            $table->dropColumn(['microsoft_id', 'microsoft_email', 'microsoft_name']);
        });
    }
};
