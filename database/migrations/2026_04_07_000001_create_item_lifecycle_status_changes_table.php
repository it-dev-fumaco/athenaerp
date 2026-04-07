<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_lifecycle_status_changes', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->index();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('reason');
            $table->string('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_lifecycle_status_changes');
    }
};

