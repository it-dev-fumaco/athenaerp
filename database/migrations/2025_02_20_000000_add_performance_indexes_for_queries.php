<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes to speed up frequent WHERE, JOIN, GROUP BY and ORDER BY clauses.
     * Safe to run: skips adding if index already exists; down() drops only if present.
     *
     * @return void
     */
    public function up()
    {
        $this->addIndexIfMissing('tabBin', 'bin_item_warehouse_idx', ['item_code', 'warehouse']);
        $this->addIndexIfMissing('tabStock Reservation', 'stock_reservation_item_warehouse_status_idx', ['item_code', 'warehouse', 'status']);
        $this->addIndexIfMissing('tabStock Reservation', 'stock_reservation_warehouse_sales_status_idx', ['warehouse', 'sales_person', 'project', 'status']);
        $this->addIndexIfMissing('tabStock Entry Detail', 'stock_entry_detail_parent_item_idx', ['parent', 'item_code']);
        $this->addIndexIfMissing('tabStock Entry Detail', 'stock_entry_detail_item_warehouse_doc_idx', ['item_code', 's_warehouse', 'docstatus', 'status']);
        $this->addIndexIfMissing('tabWork Order Item', 'work_order_item_parent_item_idx', ['parent', 'item_code']);
        $this->addIndexIfMissing('tabWork Order Item', 'work_order_item_parent_alt_idx', ['parent', 'item_alternative_for']);
        $this->addIndexIfMissing('tabItem Supplier', 'item_supplier_parent_idx', ['parent']);
        $this->addIndexIfMissing('tabAthena Transactions', 'athena_ref_parent_type_idx', ['reference_parent', 'reference_type']);
        $this->addIndexIfMissing('tabAthena Transactions', 'athena_ref_name_parent_status_idx', ['reference_name', 'reference_parent', 'status']);
        $this->addIndexIfMissing('tabWarehouse', 'warehouse_disabled_name_idx', ['disabled', 'name']);
        $this->addIndexIfMissing('tabPacking Slip Item', 'packing_slip_item_parent_idx', ['parent']);
        $this->addIndexIfMissing('tabPacking Slip Item', 'packing_slip_item_item_code_idx', ['item_code']);
        $this->addIndexIfMissing('tabStock Entry', 'stock_entry_work_order_purpose_doc_idx', ['work_order', 'purpose', 'docstatus']);
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
        $results = DB::select('SHOW INDEX FROM `'.str_replace('`', '``', $table).'` WHERE Key_name = ?', [$indexName]);

        return count($results) > 0;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $drops = [
            ['tabBin', 'bin_item_warehouse_idx'],
            ['tabStock Reservation', 'stock_reservation_item_warehouse_status_idx'],
            ['tabStock Reservation', 'stock_reservation_warehouse_sales_status_idx'],
            ['tabStock Entry Detail', 'stock_entry_detail_parent_item_idx'],
            ['tabStock Entry Detail', 'stock_entry_detail_item_warehouse_doc_idx'],
            ['tabWork Order Item', 'work_order_item_parent_item_idx'],
            ['tabWork Order Item', 'work_order_item_parent_alt_idx'],
            ['tabItem Supplier', 'item_supplier_parent_idx'],
            ['tabAthena Transactions', 'athena_ref_parent_type_idx'],
            ['tabAthena Transactions', 'athena_ref_name_parent_status_idx'],
            ['tabWarehouse', 'warehouse_disabled_name_idx'],
            ['tabPacking Slip Item', 'packing_slip_item_parent_idx'],
            ['tabPacking Slip Item', 'packing_slip_item_item_code_idx'],
            ['tabStock Entry', 'stock_entry_work_order_purpose_doc_idx'],
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
