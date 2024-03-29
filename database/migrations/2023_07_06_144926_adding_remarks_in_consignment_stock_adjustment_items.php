<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddingRemarksInConsignmentStockAdjustmentItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('tabConsignment Stock Adjustment Items', 'remarks')) return;
        Schema::table('tabConsignment Stock Adjustment Items', function (Blueprint $table) {
            $table->string('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tabConsignment Stock Adjustment Items', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
}
