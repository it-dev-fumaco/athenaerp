<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PhaseOutReportService
{
    /**
     * Dashboard summary: counts, stock totals, and per-brand breakdown for phase-out items.
     *
     * @return array{tagged_count: int, total_units: float, total_stock_value: float, by_brand: array<int, array{brand: string, item_count: int, stock_value: float}>}
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
            ];
        }
    }

    /**
     * @return array{tagged_count: int, total_units: float, total_stock_value: float, by_brand: array<int, array{brand: string, item_count: int, stock_value: float}>}
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

        return [
            'tagged_count' => $taggedCount,
            'total_units' => $totalUnits,
            'total_stock_value' => $totalStockValue,
            'by_brand' => $byBrand,
        ];
    }

    public function paginateTaggedEnriched(int $perPage, int $page): LengthAwarePaginator
    {
        $col = Item::lifecycleStatusColumn();
        if (! Schema::hasTable('tabItem') || ! Schema::hasColumn('tabItem', $col)) {
            return Item::query()->whereRaw('0 = 1')->paginate($perPage, ['*'], 'page', $page);
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

        $q = Item::query()
            ->where('tabItem.'.$col, Item::LIFECYCLE_STATUS_PHASE_OUT)
            ->leftJoinSub($binQtySum, 'bq', 'bq.item_code', '=', 'tabItem.name')
            ->select($select)
            ->addSelect(DB::raw('COALESCE(bq.total_actual_qty, 0) as total_actual_qty'))
            ->addSelect(DB::raw(
                '(SELECT MAX(sle.posting_date) FROM `tabStock Ledger Entry` sle WHERE sle.item_code = tabItem.name'.$ledgerCancelled.') as last_movement_date'
            ))
            ->addSelect(DB::raw('(SELECT b2.warehouse FROM tabBin b2 WHERE b2.item_code = tabItem.name ORDER BY b2.actual_qty DESC LIMIT 1) as primary_warehouse'))
            ->orderByDesc('tabItem.creation');

        return $q->paginate($perPage, ['*'], 'page', $page);
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
}
