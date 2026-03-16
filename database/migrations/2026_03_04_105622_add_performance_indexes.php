<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for columns used in where(), orderBy(), and join() across
     * Eloquent models and controllers. Idempotent: skips if index exists.
     */
    public function up(): void
    {
        $indexes = [
            // tabWarehouse Access: User::warehouseAccess(), ItemSearchService joins on wa.parent
            ['tabWarehouse Access', 'warehouse_access_parent_idx', ['parent']],
            // tabAssigned Consignment Warehouse: AssignedWarehouses::where('parent', $user)
            ['tabAssigned Consignment Warehouse', 'assigned_wh_parent_idx', ['parent']],
            // tabItem Images: WHERE parent IN (...), ORDER BY idx
            ['tabItem Images', 'item_images_parent_idx_idx', ['parent', 'idx']],
            // tabItem Variant Attribute: WHERE parent, attribute, attribute_value; JOINs in BrochureController
            ['tabItem Variant Attribute', 'item_variant_attr_parent_idx', ['parent']],
            ['tabItem Variant Attribute', 'item_variant_attr_attr_val_idx', ['attribute', 'attribute_value']],
            // tabItem: scopes (disabled, variant_of, item_group), search (item_classification), orderBy modified
            ['tabItem', 'item_disabled_idx', ['disabled']],
            ['tabItem', 'item_variant_of_idx', ['variant_of']],
            ['tabItem', 'item_item_group_idx', ['item_group']],
            ['tabItem', 'item_classification_idx', ['item_classification']],
            ['tabItem', 'item_modified_idx', ['modified']],
            // tabConsignment Stock Adjustment Items: JOIN csa.name = csai.parent
            ['tabConsignment Stock Adjustment Items', 'csa_items_parent_idx', ['parent']],
            // tabConsignment Beginning Inventory Item: WHERE parent, item_code
            ['tabConsignment Beginning Inventory Item', 'cbi_item_parent_idx', ['parent']],
            ['tabConsignment Beginning Inventory Item', 'cbi_item_parent_item_idx', ['parent', 'item_code']],
            // tabItem Group: WHERE parent_item_group
            ['tabItem Group', 'item_group_parent_idx', ['parent_item_group']],
            // tabProduct Brochure Log: scopes transaction_type, project
            ['tabProduct Brochure Log', 'brochure_log_type_idx', ['transaction_type']],
            ['tabProduct Brochure Log', 'brochure_log_project_idx', ['project']],
            // tabItem Default: Item::itemDefault relationship
            ['tabItem Default', 'item_default_parent_idx', ['parent']],
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
            ['tabWarehouse Access', 'warehouse_access_parent_idx'],
            ['tabAssigned Consignment Warehouse', 'assigned_wh_parent_idx'],
            ['tabItem Images', 'item_images_parent_idx_idx'],
            ['tabItem Variant Attribute', 'item_variant_attr_parent_idx'],
            ['tabItem Variant Attribute', 'item_variant_attr_attr_val_idx'],
            ['tabItem', 'item_disabled_idx'],
            ['tabItem', 'item_variant_of_idx'],
            ['tabItem', 'item_item_group_idx'],
            ['tabItem', 'item_classification_idx'],
            ['tabItem', 'item_modified_idx'],
            ['tabConsignment Stock Adjustment Items', 'csa_items_parent_idx'],
            ['tabConsignment Beginning Inventory Item', 'cbi_item_parent_idx'],
            ['tabConsignment Beginning Inventory Item', 'cbi_item_parent_item_idx'],
            ['tabItem Group', 'item_group_parent_idx'],
            ['tabProduct Brochure Log', 'brochure_log_type_idx'],
            ['tabProduct Brochure Log', 'brochure_log_project_idx'],
            ['tabItem Default', 'item_default_parent_idx'],
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
