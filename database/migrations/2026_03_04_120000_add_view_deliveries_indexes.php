<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for view_deliveries / picking list union (BuildPickingListPipe).
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        $indexes = [
            ['tabPacking Slip', 'packing_slip_docstatus_creation_idx', ['docstatus', 'creation']],
            ['tabDelivery Note', 'delivery_note_docstatus_idx', ['docstatus']],
            // s_warehouse is on tabStock Entry Detail, not tabStock Entry
            ['tabStock Entry', 'stock_entry_deliveries_idx', ['docstatus', 'purpose', 'transfer_as']],
            ['tabStock Entry', 'stock_entry_creation_idx', ['creation']],
            ['tabDelivery Note Item', 'delivery_note_item_parent_item_idx', ['parent', 'item_code']],
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
            ['tabPacking Slip', 'packing_slip_docstatus_creation_idx'],
            ['tabDelivery Note', 'delivery_note_docstatus_idx'],
            ['tabStock Entry', 'stock_entry_deliveries_idx'],
            ['tabStock Entry', 'stock_entry_creation_idx'],
            ['tabDelivery Note Item', 'delivery_note_item_parent_item_idx'],
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
