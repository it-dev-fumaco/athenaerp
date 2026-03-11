<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for Internal Transfer, Production Withdrawal, Material Issue, Order replacement.
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        // s_warehouse is on tabStock Entry Detail; tabStock Entry has no s_warehouse column
        $indexes = [
            ['tabStock Entry', 'stock_entry_doc_purpose_idx', ['docstatus', 'purpose']],
            // t_warehouse is on tabStock Entry Detail
            ['tabStock Entry', 'stock_entry_doc_purpose_transfer_idx', ['docstatus', 'purpose', 'transfer_as']],
            ['tabStock Entry', 'stock_entry_material_issue_idx', ['docstatus', 'purpose', 'issue_as']],
            ['tabStock Entry', 'stock_entry_replacement_idx', ['docstatus', 'purpose', 'issue_as']],
            ['tabStock Entry Detail', 'stock_entry_detail_s_warehouse_parent_idx', ['s_warehouse', 'parent']],
        ];

        foreach ($indexes as [$table, $indexName, $columns]) {
            $this->addIndexIfMissing($table, $indexName, $columns);
        }
    }

    /** @param array<string> $columns */
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
            ['tabStock Entry', 'stock_entry_doc_purpose_idx'],
            ['tabStock Entry', 'stock_entry_doc_purpose_transfer_idx'],
            ['tabStock Entry', 'stock_entry_material_issue_idx'],
            ['tabStock Entry', 'stock_entry_replacement_idx'],
            ['tabStock Entry Detail', 'stock_entry_detail_s_warehouse_parent_idx'],
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
