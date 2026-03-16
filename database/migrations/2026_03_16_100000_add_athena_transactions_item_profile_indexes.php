<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for Athena Transactions 4-table join (Item Profile stock levels, Stock Reservations pending, getAthenaTransactions).
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        $indexes = [
            ['tabAthena Transactions', 'athena_ref_type_status_item_idx', ['reference_type', 'status', 'item_code']],
            ['tabAthena Transactions', 'athena_reference_parent_idx', ['reference_parent']],
            ['tabAthena Transactions', 'athena_item_code_source_wh_idx', ['item_code', 'source_warehouse']],
            ['tabAthena Transactions', 'athena_item_code_status_created_idx', ['item_code', 'status', 'creation']],
            ['tabPacking Slip Item', 'packing_slip_item_parent_item_status_idx', ['parent', 'item_code', 'status']],
        ];

        foreach ($indexes as [$table, $indexName, $columns]) {
            $this->addIndexIfMissing($table, $indexName, $columns);
        }
    }

    /**
     * @param  array<string>  $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName, $columns) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $escaped = str_replace('`', '``', $table);
        $results = DB::select('SHOW INDEX FROM `'.$escaped.'` WHERE Key_name = ?', [$indexName]);

        return count($results) > 0;
    }

    public function down(): void
    {
        $drops = [
            ['tabAthena Transactions', 'athena_ref_type_status_item_idx'],
            ['tabAthena Transactions', 'athena_reference_parent_idx'],
            ['tabAthena Transactions', 'athena_item_code_source_wh_idx'],
            ['tabAthena Transactions', 'athena_item_code_status_created_idx'],
            ['tabPacking Slip Item', 'packing_slip_item_parent_item_status_idx'],
        ];

        foreach ($drops as [$table, $indexName]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if ($this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                    $blueprint->dropIndex($indexName);
                });
            }
        }
    }
};
