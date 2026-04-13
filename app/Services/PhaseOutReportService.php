<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PhaseOutReportService
{
    /**
     * Dashboard summary: counts, stock totals, and per-brand breakdown for phase-out items.
     *
     * @return array{tagged_count: int, total_units: float, total_stock_value: float, by_brand: array<int, array{brand: string, item_count: int, stock_value: float}>, distinct_warehouse_count: int, warehouses: array<int, string>}
     */
    public function getPhaseOutSummary(): array
    {
        try {
            return $this->buildPhaseOutSummary();
        } catch (\Throwable $e) {
            Log::warning('phase_out.summary_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'tagged_count' => 0,
                'total_units' => 0.0,
                'total_stock_value' => 0.0,
                'by_brand' => [],
                'distinct_warehouse_count' => 0,
                'warehouses' => [],
            ];
        }
    }

    /**
     * @return array{tagged_count: int, total_units: float, total_stock_value: float, by_brand: array<int, array{brand: string, item_count: int, stock_value: float}>, distinct_warehouse_count: int, warehouses: array<int, string>}
     */
    private function buildPhaseOutSummary(): array
    {
        $col = Item::lifecycleStatusColumn();
        $status = Item::LIFECYCLE_STATUS_PHASE_OUT;

        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return [
                'tagged_count' => 0,
                'total_units' => 0.0,
                'total_stock_value' => 0.0,
                'by_brand' => [],
                'distinct_warehouse_count' => 0,
                'warehouses' => [],
            ];
        }

        $sumQtyExpr = Schema::hasColumn('tabBin', 'actual_qty')
            ? 'COALESCE(SUM(b.actual_qty), 0)'
            : '0';
        $sumValExpr = Schema::hasColumn('tabBin', 'stock_value')
            ? 'COALESCE(SUM(b.stock_value), 0)'
            : '0';

        $totals = DB::table('tabItem as i')
            ->leftJoin('tabBin as b', 'b.item_code', '=', 'i.name')
            ->where('i.'.$col, $status)
            ->selectRaw('COUNT(DISTINCT i.name) as tagged_count')
            ->selectRaw($sumQtyExpr.' as total_units')
            ->selectRaw($sumValExpr.' as total_stock_value')
            ->first();

        $taggedCount = (int) ($totals->tagged_count ?? 0);
        $totalUnits = (float) ($totals->total_units ?? 0);
        $totalStockValue = (float) ($totals->total_stock_value ?? 0);

        $byBrand = [];
        if (Schema::hasColumn('tabItem', 'brand')) {
            $brandExpr = "COALESCE(NULLIF(TRIM(i.brand), ''), 'Unbranded')";
            $sumBrandVal = Schema::hasColumn('tabBin', 'stock_value')
                ? 'COALESCE(SUM(b.stock_value), 0)'
                : '0';

            $byBrand = DB::table('tabItem as i')
                ->leftJoin('tabBin as b', 'b.item_code', '=', 'i.name')
                ->where('i.'.$col, $status)
                ->groupBy(DB::raw($brandExpr))
                ->orderBy(DB::raw($brandExpr))
                ->selectRaw($brandExpr.' as brand')
                ->selectRaw('COUNT(DISTINCT i.name) as item_count')
                ->selectRaw($sumBrandVal.' as stock_value')
                ->get()
                ->map(fn ($row) => [
                    'brand' => (string) $row->brand,
                    'item_count' => (int) $row->item_count,
                    'stock_value' => (float) $row->stock_value,
                ])->values()->all();
        } else {
            $byBrand = [
                [
                    'brand' => 'All',
                    'item_count' => $taggedCount,
                    'stock_value' => $totalStockValue,
                ],
            ];
        }

        $warehouses = $this->phaseOutDistinctWarehouses($col);
        $distinctWarehouseCount = count($warehouses);

        return [
            'tagged_count' => $taggedCount,
            'total_units' => $totalUnits,
            'total_stock_value' => $totalStockValue,
            'by_brand' => $byBrand,
            'distinct_warehouse_count' => $distinctWarehouseCount,
            'warehouses' => $warehouses,
        ];
    }

    /**
     * Distinct warehouse codes that appear on tabBin for items tagged For Phase Out (for filter dropdown + stat card).
     *
     * @return array<int, string>
     */
    private function phaseOutDistinctWarehouses(string $lifecycleCol): array
    {
        if (! Schema::hasTable('tabBin') || ! Schema::hasColumn('tabBin', 'warehouse')) {
            return [];
        }

        $status = Item::LIFECYCLE_STATUS_PHASE_OUT;

        return DB::table('tabBin as b')
            ->join('tabItem as i', 'i.name', '=', 'b.item_code')
            ->where('i.'.$lifecycleCol, $status)
            ->whereNotNull('b.warehouse')
            ->where('b.warehouse', '!=', '')
            ->distinct()
            ->orderBy('b.warehouse')
            ->pluck('b.warehouse')
            ->map(fn ($w) => (string) $w)
            ->values()
            ->all();
    }

    /**
     * Base query for tagged enriched list (filters applied, no order/pagination).
     *
     * @param  array{search?: string, warehouse?: string, brand?: string}  $filters
     */
    private function buildTaggedEnrichedBaseQuery(array $filters): ?Builder
    {
        $col = Item::lifecycleStatusColumn();
        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return null;
        }

        $hasBrand = Schema::hasColumn('tabItem', 'brand');

        $binQtySum = DB::table('tabBin')
            ->select('item_code', DB::raw('COALESCE(SUM(actual_qty), 0) as total_actual_qty'))
            ->groupBy('item_code');

        $ledgerCancelled = Schema::hasColumn('tabStock Ledger Entry', 'is_cancelled')
            ? ' AND sle.is_cancelled = 0'
            : '';

        $select = [
            'tabItem.name',
            'tabItem.item_name',
            'tabItem.creation',
            'tabItem.stock_uom',
        ];
        if ($hasBrand) {
            $select[] = 'tabItem.brand';
        }

        return Item::query()
            ->where('tabItem.'.$col, Item::LIFECYCLE_STATUS_PHASE_OUT)
            ->leftJoinSub($binQtySum, 'bq', 'bq.item_code', '=', 'tabItem.name')
            ->select($select)
            ->addSelect(DB::raw('COALESCE(bq.total_actual_qty, 0) as total_actual_qty'))
            ->addSelect(DB::raw(
                '(SELECT MAX(sle.posting_date) FROM `tabStock Ledger Entry` sle WHERE sle.item_code = tabItem.name'.$ledgerCancelled.') as last_movement_date'
            ))
            ->addSelect(DB::raw('(SELECT b2.warehouse FROM tabBin b2 WHERE b2.item_code = tabItem.name ORDER BY b2.actual_qty DESC LIMIT 1) as primary_warehouse'))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $raw = (string) $filters['search'];
                $like = '%'.addcslashes($raw, '%_\\').'%';
                $query->where(function ($q2) use ($like) {
                    $q2->where('tabItem.name', 'like', $like)
                        ->orWhere('tabItem.item_name', 'like', $like);
                });
            })
            ->when(! empty($filters['warehouse']), function ($query) use ($filters) {
                // Matches the "primary warehouse" column (highest actual_qty bin).
                $query->whereRaw(
                    '(SELECT b2.warehouse FROM tabBin b2 WHERE b2.item_code = tabItem.name ORDER BY b2.actual_qty DESC LIMIT 1) = ?',
                    [$filters['warehouse']]
                );
            })
            ->when($hasBrand && ! empty($filters['brand']), function ($query) use ($filters) {
                $b = (string) $filters['brand'];
                if ($b === 'Unbranded') {
                    $query->where(function ($q2) {
                        $q2->whereNull('tabItem.brand')
                            ->orWhereRaw("NULLIF(TRIM(tabItem.brand), '') IS NULL");
                    });
                } else {
                    $query->where('tabItem.brand', $b);
                }
            });
    }

    /**
     * Aggregates for the same filter set as the tagged-items table (all pages).
     *
     * @param  array{search?: string, warehouse?: string, brand?: string}  $filters
     * @return array{total_stock_sum: float, unique_warehouse_count: int, unique_brand_count: int}
     */
    public function aggregateTaggedEnriched(array $filters): array
    {
        $base = $this->buildTaggedEnrichedBaseQuery($filters);
        if ($base === null) {
            return [
                'total_stock_sum' => 0.0,
                'unique_warehouse_count' => 0,
                'unique_brand_count' => 0,
            ];
        }

        $hasBrand = Schema::hasColumn('tabItem', 'brand');

        $sumQuery = clone $base;
        $sumQuery->getQuery()->orders = null;
        $totalStockSum = (float) $sumQuery->sum(DB::raw('COALESCE(bq.total_actual_qty, 0)'));

        $warehouseExpr = '(SELECT b2.warehouse FROM tabBin b2 WHERE b2.item_code = tabItem.name ORDER BY b2.actual_qty DESC LIMIT 1)';
        $whSub = $base->clone()->select('tabItem.name')->addSelect(DB::raw($warehouseExpr.' as warehouse_key'));
        $uniqueWarehouseCount = (int) (DB::query()
            ->fromSub($whSub->toBase(), 'w')
            ->whereNotNull('warehouse_key')
            ->where('warehouse_key', '!=', '')
            ->selectRaw('COUNT(DISTINCT warehouse_key) as c')
            ->value('c') ?? 0);

        if ($hasBrand) {
            $brandSub = $base->clone()
                ->select('tabItem.brand')
                ->whereNotNull('tabItem.brand')
                ->whereRaw("NULLIF(TRIM(tabItem.brand), '') IS NOT NULL");
            $uniqueBrandCount = (int) (DB::query()
                ->fromSub($brandSub->toBase(), 'b')
                ->selectRaw('COUNT(DISTINCT b.brand) as c')
                ->value('c') ?? 0);
        } else {
            $uniqueBrandCount = 0;
        }

        return [
            'total_stock_sum' => $totalStockSum,
            'unique_warehouse_count' => $uniqueWarehouseCount,
            'unique_brand_count' => $uniqueBrandCount,
        ];
    }

    /**
     * @param  array{search?: string, warehouse?: string, brand?: string}  $filters
     */
    public function paginateTaggedEnriched(int $perPage, int $page, array $filters = []): LengthAwarePaginator
    {
        $base = $this->buildTaggedEnrichedBaseQuery($filters);
        if ($base === null) {
            return Item::query()->whereRaw('0 = 1')->paginate($perPage, ['*'], 'page', $page);
        }

        return $base->orderByDesc('tabItem.creation')->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginateTagged(int $perPage, int $page): LengthAwarePaginator
    {
        $col = Item::lifecycleStatusColumn();
        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return Item::query()->whereRaw('0 = 1')->paginate($perPage, ['*'], 'tagged_page', $page);
        }

        return Item::query()
            ->where('tabItem.'.$col, Item::LIFECYCLE_STATUS_PHASE_OUT)
            ->orderByDesc('creation')
            ->paginate($perPage, ['*'], 'tagged_page', $page);
    }

    /**
     * @param  array{brand?: string, created_before?: string, no_movement_days?: int, excess_stock_only?: bool}  $filters
     */
    public function paginateCandidates(int $perPage, int $page, int $months, array $filters = []): LengthAwarePaginator
    {
        $col = Item::lifecycleStatusColumn();
        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return Item::query()->whereRaw('0 = 1')->paginate($perPage, ['*'], 'candidates_page', $page);
        }

        if (! empty($filters['no_movement_days'])) {
            $cutoff = now()->subDays((int) $filters['no_movement_days'])->toDateString();
        } else {
            $cutoff = now()->subMonths($months)->toDateString();
        }

        $ledgerSub = DB::table('tabStock Ledger Entry')
            ->select('item_code', DB::raw('MAX(posting_date) as last_posting'));

        if (Schema::hasColumn('tabStock Ledger Entry', 'is_cancelled')) {
            $ledgerSub->where('is_cancelled', 0);
        }

        $ledgerSub->groupBy('item_code');

        $query = Item::query()
            ->joinSub($ledgerSub, 'sle', 'sle.item_code', '=', 'tabItem.name')
            ->enabled()
            ->stockItem()
            ->leafVariants()
            ->where(function ($q) use ($col) {
                $q->whereNull('tabItem.'.$col)
                    ->orWhere('tabItem.'.$col, '')
                    ->orWhere('tabItem.'.$col, Item::LIFECYCLE_STATUS_ACTIVE);
            })
            ->where('sle.last_posting', '<', $cutoff)
            ->when(! empty($filters['brand']) && Schema::hasColumn('tabItem', 'brand'), function ($q) use ($filters) {
                $q->where('tabItem.brand', $filters['brand']);
            })
            ->when(! empty($filters['created_before']), function ($q) use ($filters) {
                $q->whereDate('tabItem.creation', '<', $filters['created_before']);
            })
            ->when(! empty($filters['excess_stock_only']), function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tabBin as b')
                        ->join(DB::raw('`tabItem Reorder` as ir'), function ($join) {
                            $join->whereRaw('ir.parent = tabItem.name')
                                ->whereColumn('ir.warehouse', 'b.warehouse');
                        })
                        ->whereColumn('b.item_code', 'tabItem.name')
                        ->whereRaw('b.actual_qty > (2 * IFNULL(ir.warehouse_reorder_level, 0))');
                });
            })
            ->orderBy('sle.last_posting')
            ->select('tabItem.*')
            ->addSelect(DB::raw('sle.last_posting as last_stock_ledger_posting'));

        if (Schema::hasTable('tabBin') && Schema::hasColumn('tabBin', 'actual_qty')) {
            $query->addSelect(DB::raw(
                '(SELECT COALESCE(SUM(b2.actual_qty), 0) FROM `tabBin` b2 WHERE b2.item_code = `tabItem`.name) as total_actual_qty'
            ));
        } else {
            $query->addSelect(DB::raw('0 as total_actual_qty'));
        }

        return $query->paginate($perPage, ['*'], 'candidates_page', $page);
    }

    /**
     * Paginate items eligible for mass lifecycle update (Active lifecycle, with stock ledger activity).
     *
     * @param  array{brand?: string, item_classification?: string, last_movement_days?: int, entry_year?: int}  $filters
     */
    public function paginateMassUpdateItems(int $perPage, int $page, array $filters = []): LengthAwarePaginator
    {
        $col = Item::lifecycleStatusColumn();
        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return Item::query()->whereRaw('0 = 1')->paginate($perPage, ['*'], 'page', $page);
        }

        $ledgerSub = DB::table('tabStock Ledger Entry')
            ->select('item_code', DB::raw('MAX(posting_date) as last_posting'));

        if (Schema::hasColumn('tabStock Ledger Entry', 'is_cancelled')) {
            $ledgerSub->where('is_cancelled', 0);
        }

        $ledgerSub->groupBy('item_code');

        $query = Item::query()
            ->joinSub($ledgerSub, 'sle', 'sle.item_code', '=', 'tabItem.name')
            ->enabled()
            ->stockItem()
            ->leafVariants()
            ->where(function ($q) use ($col) {
                $q->whereNull('tabItem.'.$col)
                    ->orWhere('tabItem.'.$col, '')
                    ->orWhere('tabItem.'.$col, Item::LIFECYCLE_STATUS_ACTIVE);
            });

        $this->applyMassUpdateDefaultExclusions($query);

        $query
            ->when(! empty($filters['brand']) && Schema::hasColumn('tabItem', 'brand'), function ($q) use ($filters) {
                $q->where('tabItem.brand', $filters['brand']);
            })
            ->when(! empty($filters['item_classification']) && Schema::hasColumn('tabItem', 'item_classification'), function ($q) use ($filters) {
                $q->where('tabItem.item_classification', $filters['item_classification']);
            })
            ->when(isset($filters['last_movement_days']), function ($q) use ($filters) {
                $q->whereRaw('DATEDIFF(CURDATE(), sle.last_posting) < ?', [(int) $filters['last_movement_days']]);
            })
            ->when(isset($filters['entry_year']) && Schema::hasColumn('tabItem', 'creation'), function ($q) use ($filters) {
                $q->whereYear('tabItem.creation', (int) $filters['entry_year']);
            })
            ->orderByDesc('sle.last_posting')
            ->select('tabItem.name', 'tabItem.item_name')
            ->addSelect(DB::raw('sle.last_posting as last_stock_ledger_posting'))
            ->addSelect(DB::raw('DATEDIFF(CURDATE(), sle.last_posting) as days_since_last_movement'));

        if (Schema::hasColumn('tabItem', 'item_classification')) {
            $query->addSelect('tabItem.item_classification');
        }
        if (Schema::hasColumn('tabItem', 'brand')) {
            $query->addSelect('tabItem.brand');
        }
        if (Schema::hasColumn('tabItem', 'creation')) {
            $query->addSelect(DB::raw('tabItem.creation as entry_date'));
        } else {
            $query->addSelect(DB::raw('NULL as entry_date'));
        }

        if (Schema::hasTable('tabBin') && Schema::hasColumn('tabBin', 'actual_qty')) {
            $query->addSelect(DB::raw(
                '(SELECT COALESCE(SUM(b2.actual_qty), 0) FROM `tabBin` b2 WHERE b2.item_code = `tabItem`.name) as total_actual_qty'
            ));
        } else {
            $query->addSelect(DB::raw('0 as total_actual_qty'));
        }

        if (
            Schema::hasTable('tabPurchase Receipt Item')
            && Schema::hasTable('tabPurchase Receipt')
            && Schema::hasColumn('tabPurchase Receipt Item', 'item_code')
            && Schema::hasColumn('tabPurchase Receipt Item', 'parent')
            && Schema::hasColumn('tabPurchase Receipt', 'posting_date')
        ) {
            $priDocstatus = Schema::hasColumn('tabPurchase Receipt Item', 'docstatus')
                ? ' AND pri.docstatus = 1'
                : '';
            $prReturn = Schema::hasColumn('tabPurchase Receipt', 'is_return')
                ? ' AND (pr.is_return = 0 OR pr.is_return IS NULL)'
                : '';

            $query->addSelect(DB::raw(
                '(SELECT MAX(pr.posting_date) FROM `tabPurchase Receipt Item` pri '
                .'INNER JOIN `tabPurchase Receipt` pr ON pr.name = pri.parent '
                .'WHERE pri.item_code = `tabItem`.name AND pr.docstatus = 1'
                .$priDocstatus
                .$prReturn
                .') as last_purchase_receipt_date'
            ));
        } else {
            $query->addSelect(DB::raw('NULL as last_purchase_receipt_date'));
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Mandatory exclusions for Mass Update Lifecycle Status list (not shown in UI).
     * Non-disabled items: already enforced via {@see Item::scopeEnabled()} (disabled = 0).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Item>  $query
     */
    private function applyMassUpdateDefaultExclusions($query): void
    {
        $excludedItemGroups = [
            'Sub Assemblies',
            'All Factory Supplies',
            'Base',
            'Housing',
            'Consumable',
            'Expense Item',
        ];

        $excludedItemClassifications = [
            'FY - Factory Supplies',
            'MS - Maintenance Supplies',
            'OS - Office Supplies',
            'SC - Service Charge',
            'MP - Ms Plate',
            'PA - Paints',
            'CH - Chemicals',
            'MD - Medicines',
            'FR - Factory Repair',
            'MA - Maintenance',
        ];

        if (Schema::hasColumn('tabItem', 'item_group')) {
            $query->where(function ($q) use ($excludedItemGroups) {
                $q->whereNull('tabItem.item_group')
                    ->orWhereNotIn('tabItem.item_group', $excludedItemGroups);
            });
            foreach (['item_group_level_1', 'item_group_level_2', 'item_group_level_3', 'item_group_level_4', 'item_group_level_5'] as $col) {
                if (Schema::hasColumn('tabItem', $col)) {
                    $query->where(function ($q) use ($excludedItemGroups, $col) {
                        $q->whereNull('tabItem.'.$col)
                            ->orWhereNotIn('tabItem.'.$col, $excludedItemGroups);
                    });
                }
            }
        }

        if (Schema::hasColumn('tabItem', 'item_classification')) {
            $query->where(function ($q) use ($excludedItemClassifications) {
                $q->whereNull('tabItem.item_classification')
                    ->orWhereNotIn('tabItem.item_classification', $excludedItemClassifications);
            });
        }
    }
}
