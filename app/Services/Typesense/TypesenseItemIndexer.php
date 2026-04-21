<?php

namespace App\Services\Typesense;

use App\Models\Item;
use App\Models\ItemSupplier;
use Illuminate\Support\Facades\DB;
use Typesense\Client;

class TypesenseItemIndexer
{
    public function __construct(
        private Client $client,
        private TypesenseCollectionManager $collections
    ) {}

    public function collectionName(): string
    {
        return $this->collections->collectionName();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildDocumentForItemCode(string $itemCode): ?array
    {
        $docs = $this->buildDocumentsForItemCodes([$itemCode]);

        return $docs[0] ?? null;
    }

    /**
     * @param  array<int, string>  $itemCodes
     * @return array<int, array<string, mixed>>
     */
    public function buildDocumentsForItemCodes(array $itemCodes): array
    {
        if ($itemCodes === []) {
            return [];
        }

        $lifecycleCol = Item::lifecycleStatusColumn();

        $items = Item::query()
            ->whereIn('name', $itemCodes)
            ->get()
            ->keyBy('name');

        $supplierParts = ItemSupplier::query()
            ->whereIn('parent', $itemCodes)
            ->selectRaw('parent, GROUP_CONCAT(DISTINCT supplier_part_no SEPARATOR "; ") as nos')
            ->groupBy('parent')
            ->pluck('nos', 'parent');

        $binRows = DB::table('tabBin')
            ->whereIn('item_code', $itemCodes)
            ->select('item_code', 'warehouse')
            ->get()
            ->groupBy('item_code');

        $stockSums = DB::table('tabBin')
            ->join('tabWarehouse', 'tabBin.warehouse', '=', 'tabWarehouse.name')
            ->whereIn('tabBin.item_code', $itemCodes)
            ->where('tabWarehouse.stock_warehouse', 1)
            ->groupBy('tabBin.item_code')
            ->selectRaw('tabBin.item_code, SUM(tabBin.actual_qty) as total_qty')
            ->pluck('total_qty', 'item_code');

        $documents = [];

        foreach ($itemCodes as $code) {
            $item = $items->get($code);
            if (! $item) {
                continue;
            }

            if ((int) $item->disabled !== 0 || (int) $item->has_variants !== 0) {
                continue;
            }

            $warehouses = isset($binRows[$code])
                ? $binRows[$code]->pluck('warehouse')->unique()->values()->all()
                : [];

            $hasStock = ((float) ($stockSums[$code] ?? 0)) > 0.0;

            $modifiedRaw = $item->modified;
            $modifiedTs = $modifiedRaw ? strtotime((string) $modifiedRaw) : 0;

            $lifecycle = '';
            if (isset($item->getAttributes()[$lifecycleCol])) {
                $lifecycle = (string) $item->getAttributes()[$lifecycleCol];
            }

            $documents[] = [
                'id' => $item->name,
                'name' => $item->name,
                'description' => (string) $item->description,
                'item_name' => (string) ($item->item_name ?? ''),
                'item_group' => (string) $item->item_group,
                'lvl1' => (string) ($item->item_group_level_1 ?? ''),
                'lvl2' => (string) ($item->item_group_level_2 ?? ''),
                'lvl3' => (string) ($item->item_group_level_3 ?? ''),
                'lvl4' => (string) ($item->item_group_level_4 ?? ''),
                'lvl5' => (string) ($item->item_group_level_5 ?? ''),
                'item_classification' => (string) ($item->item_classification ?? ''),
                'brand' => (string) ($item->brand ?? ''),
                'stock_uom' => (string) ($item->stock_uom ?? ''),
                'supplier_part_nos' => (string) ($supplierParts[$item->name] ?? ''),
                'warehouse_codes' => $warehouses,
                'has_stock_warehouse' => $hasStock,
                'modified' => (int) $modifiedTs,
                'lifecycle_status' => $lifecycle,
            ];
        }

        return $documents;
    }

    public function upsertItem(string $itemCode): void
    {
        $doc = $this->buildDocumentForItemCode($itemCode);
        $name = $this->collectionName();
        if ($doc === null) {
            $this->deleteItem($itemCode);

            return;
        }

        $this->client->collections[$name]->documents->upsert($doc);
    }

    public function deleteItem(string $itemCode): void
    {
        $name = $this->collectionName();
        try {
            $this->client->collections[$name]->documents[$itemCode]->delete();
        } catch (\Typesense\Exceptions\ObjectNotFound) {
            // already gone
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    public function importDocuments(array $documents, string $action = 'upsert'): void
    {
        if ($documents === []) {
            return;
        }

        $name = $this->collectionName();
        $this->client->collections[$name]->documents->import($documents, ['action' => $action]);
    }
}
