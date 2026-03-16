<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for Sales Return (getMrSalesReturn), In Transit (feedbackedInTransit), Feedback.
     * Idempotent: skips if index already exists.
     */
    public function up(): void
    {
        $indexes = [
            // Sales Return: Delivery Note filter docstatus + is_return, Delivery Note Item filter by warehouse
            ['tabDelivery Note', 'delivery_note_docstatus_is_return_idx', ['docstatus', 'is_return']],
            ['tabDelivery Note Item', 'delivery_note_item_parent_warehouse_idx', ['parent', 'warehouse']],
            // Sales Return: Stock Entry MR (purpose Material Receipt, receive_as Sales Return), Consignment (transfer_as For Return)
            ['tabStock Entry', 'stock_entry_sales_return_mr_idx', ['docstatus', 'purpose', 'receive_as']],
            ['tabStock Entry', 'stock_entry_sales_return_consignment_idx', ['docstatus', 'purpose', 'transfer_as', 'naming_series']],
            ['tabStock Entry Detail', 'stock_entry_detail_t_warehouse_parent_idx', ['t_warehouse', 'parent']],
            // In Transit: Stock Entry filter from_warehouse, to_warehouse, purpose
            ['tabStock Entry', 'stock_entry_in_transit_idx', ['docstatus', 'company', 'from_warehouse', 'to_warehouse', 'purpose']],
            // Work Order / Sales Order / Material Request used in In Transit union
            ['tabWork Order', 'work_order_fg_warehouse_status_idx', ['fg_warehouse', 'status']],
            ['tabSales Order', 'sales_order_docstatus_status_idx', ['docstatus', 'status']],
            ['tabMaterial Request', 'material_request_docstatus_status_idx', ['docstatus', 'status']],
            // User lookups by wh_user (Sales Return, Material Issue, In Transit, Order replacement)
            ['tabWarehouse Users', 'warehouse_users_wh_user_idx', ['wh_user']],
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
            ['tabDelivery Note', 'delivery_note_docstatus_is_return_idx'],
            ['tabDelivery Note Item', 'delivery_note_item_parent_warehouse_idx'],
            ['tabStock Entry', 'stock_entry_sales_return_mr_idx'],
            ['tabStock Entry', 'stock_entry_sales_return_consignment_idx'],
            ['tabStock Entry Detail', 'stock_entry_detail_t_warehouse_parent_idx'],
            ['tabStock Entry', 'stock_entry_in_transit_idx'],
            ['tabWork Order', 'work_order_fg_warehouse_status_idx'],
            ['tabSales Order', 'sales_order_docstatus_status_idx'],
            ['tabMaterial Request', 'material_request_docstatus_status_idx'],
            ['tabWarehouse Users', 'warehouse_users_wh_user_idx'],
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
