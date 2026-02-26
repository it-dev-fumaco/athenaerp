<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes on tabStock Ledger Entry for stock integrity and item profile ledger queries.
     * Supports: WHERE item_code, is_cancelled; ORDER BY posting_date, posting_time, name.
     *
     * @return void
     */
    public function up(): void
    {
        $this->addIndexIfMissing('tabStock Ledger Entry', 'stock_ledger_item_cancelled_idx', ['item_code', 'is_cancelled']);
        $this->addIndexIfMissing('tabStock Ledger Entry', 'stock_ledger_posting_date_idx', ['posting_date']);
        $this->addIndexIfMissing('tabStock Ledger Entry', 'stock_ledger_voucher_no_idx', ['voucher_no']);
    }

    /** @param  array<string>  $columns */
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
        $results = DB::select('SHOW INDEX FROM `'.str_replace('`', '``', $table).'` WHERE Key_name = ?', [$indexName]);

        return count($results) > 0;
    }

    public function down(): void
    {
        $drops = [
            ['tabStock Ledger Entry', 'stock_ledger_item_cancelled_idx'],
            ['tabStock Ledger Entry', 'stock_ledger_posting_date_idx'],
            ['tabStock Ledger Entry', 'stock_ledger_voucher_no_idx'],
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
