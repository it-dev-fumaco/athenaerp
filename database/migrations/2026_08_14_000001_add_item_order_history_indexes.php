<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for Item Profile Order History (Sales Order customer aggregates).
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        $indexes = [
            ['tabSales Order Item', 'sales_order_item_item_code_idx', ['item_code']],
            ['tabSales Order', 'sales_order_docstatus_transaction_date_idx', ['docstatus', 'transaction_date']],
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
            ['tabSales Order Item', 'sales_order_item_item_code_idx'],
            ['tabSales Order', 'sales_order_docstatus_transaction_date_idx'],
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
