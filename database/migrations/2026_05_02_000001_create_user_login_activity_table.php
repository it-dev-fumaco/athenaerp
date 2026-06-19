<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabUser Activity Login', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 255)->nullable()->index();
            $table->string('username', 512);
            $table->timestamp('login_at')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status', 16);

            $table->index(['status', 'login_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabUser Activity Login');
        Schema::dropIfExists('user_login_activity');
    }
};
