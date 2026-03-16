<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for Item Profile (getItemDetails, getItemStockLevels, ItemProfileService).
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        $indexes = [
            // Last purchase order by item (ItemProfileService, variants)
            ['tabPurchase Order', 'purchase_order_docstatus_creation_idx', ['docstatus', 'creation']],
            ['tabPurchase Order Item', 'purchase_order_item_item_code_idx', ['item_code']],
            // Landed cost (ItemProfileService)
            ['tabLanded Cost Voucher', 'landed_cost_voucher_docstatus_idx', ['docstatus']],
            ['tabLanded Cost Item', 'landed_cost_item_item_code_idx', ['item_code']],
            // Website price (ItemProfileService, ItemProfileController variants)
            ['tabItem Price', 'item_price_list_item_modified_idx', ['price_list', 'selling', 'item_code', 'modified']],
            // Warehouse join for getItemStockLevels (Promodiser) and WarehouseAccess
            ['tabWarehouse', 'warehouse_parent_group_stock_idx', ['parent_warehouse', 'is_group', 'stock_warehouse']],
            // Price Settings (ItemProfileService Singles)
            ['tabSingles', 'singles_doctype_field_idx', ['doctype', 'field']],
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
            ['tabPurchase Order', 'purchase_order_docstatus_creation_idx'],
            ['tabPurchase Order Item', 'purchase_order_item_item_code_idx'],
            ['tabLanded Cost Voucher', 'landed_cost_voucher_docstatus_idx'],
            ['tabLanded Cost Item', 'landed_cost_item_item_code_idx'],
            ['tabItem Price', 'item_price_list_item_modified_idx'],
            ['tabWarehouse', 'warehouse_parent_group_stock_idx'],
            ['tabSingles', 'singles_doctype_field_idx'],
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
