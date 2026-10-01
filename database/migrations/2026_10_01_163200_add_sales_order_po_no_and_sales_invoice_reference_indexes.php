<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stop full scans on the two hot lookups:
     * tabSales Order:    WHERE po_no = ? AND docstatus IN (0, 1) ORDER BY name ASC
     * tabSales Invoice:  WHERE reference = ? AND docstatus IN (0, 1) ORDER BY name DESC LIMIT 1
     *
     * Equality column first, then docstatus, then name so the ORDER BY is covered.
     * Idempotent: skips if the index already exists.
     */
    public function up(): void
    {
        $indexes = [
            ['tabSales Order', 'sales_order_po_no_docstatus_name_idx', ['po_no', 'docstatus', 'name']],
            ['tabSales Invoice', 'sales_invoice_reference_docstatus_name_idx', ['reference', 'docstatus', 'name']],
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

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
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
            ['tabSales Order', 'sales_order_po_no_docstatus_name_idx'],
            ['tabSales Invoice', 'sales_invoice_reference_docstatus_name_idx'],
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
