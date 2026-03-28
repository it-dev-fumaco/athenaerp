<?php

namespace App\Pipelines\Pipes;

use App\Constants\StockEntryConstants;
use App\Contracts\Pipeline\Pipe;
use App\Models\PackingSlip;
use App\Models\StockEntry;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BuildPickingListPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $allowedWarehouses = $passable->allowedWarehouses;
        $search = trim((string) ($passable->search ?? ''));
        $perPage = (int) ($passable->perPage ?? config('delivery.per_page', 20));
        $perPage = max(1, min($perPage, 500));
        $page = $passable->page ?? 1;

        $warehouseIds = $allowedWarehouses instanceof \Illuminate\Support\Collection
            ? $allowedWarehouses->all()
            : (array) $allowedWarehouses;

        if (empty($warehouseIds)) {
            $passable->paginatedData = new LengthAwarePaginator([], 0, $perPage, $page);

            return $next($passable);
        }

        $creationFrom = now()->subMonths(config('delivery.creation_months', 12))->startOfDay();

        $pickingSlipQuery = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->leftJoin('tabDelivery Note Item as dri', function ($join) {
                $join->on('dri.parent', '=', 'ps.delivery_note')
                    ->on('dri.item_code', '=', 'psi.item_code');
            })
            ->leftJoin('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->whereIn('ps.docstatus', [0, 1])
            ->where('ps.creation', '>=', $creationFrom)
            ->where(function ($query) {
                $query->where( 'psi.status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->orWhere(function ($issuedQuery) {
                        $issuedQuery->where('psi.status', StockEntryConstants::STATUS_ISSUED)
                            ->whereExists(function ($siblingQuery) {
                                $siblingQuery->select(DB::raw(1))
                                    ->from('tabPacking Slip Item as psi_sibling')
                                    ->whereColumn('psi_sibling.parent', 'psi.parent')
                                    ->where('psi_sibling.status', StockEntryConstants::STATUS_FOR_CHECKING);
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNull('dr.name')
                    ->orWhereIn('dr.docstatus', [0, 1]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('psi.item_code', 'like', "%{$search}%")
                        ->orWhere('psi.description', 'like', "%{$search}%")
                        ->orWhere('dr.customer', 'like', "%{$search}%")
                        ->orWhere('ps.sales_order', 'like', "%{$search}%")
                        ->orWhere('ps.name', 'like', "%{$search}%")
                        ->orWhere('dr.name', 'like', "%{$search}%")
                        ->orWhere('psi.name', 'like', "%{$search}%");
                });
            })
            ->select([
                DB::raw('COALESCE(MAX(dr.delivery_date), NULL) as delivery_date'),
                'ps.sales_order',
                DB::raw('NULL as sales_order_no'),
                'psi.name AS id',
                'psi.status',
                'ps.name',
                'ps.delivery_note',
                'psi.item_code',
                'psi.description',
                DB::raw('COALESCE(SUM(dri.qty), psi.qty) as qty'),
                DB::raw('COALESCE(MAX(dri.uom), psi.stock_uom) as uom'),
                DB::raw('MAX(dri.warehouse) as warehouse'),
                'psi.owner',
                DB::raw('COALESCE(MAX(dr.customer), NULL) as customer'),
                'ps.creation',
                DB::raw('NULL as parent_item'),
                DB::raw('NULL as piName'),
                DB::raw('NULL as piQty'),
                DB::raw('NULL as piWarehouse'),
                DB::raw('NULL as piUom'),
                DB::raw('"picking_slip" as type'),
            ])
            ->groupBy(['ps.sales_order', 'psi.name', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.qty', 'psi.stock_uom', 'psi.owner', 'ps.creation'])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        $stockEntryQuery = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $warehouseIds)
            ->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->where(function ($query) {
                $query->where('sted.status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->orWhere(function ($issuedQuery) {
                        $issuedQuery->where('sted.status', StockEntryConstants::STATUS_ISSUED)
                            ->whereExists(function ($siblingQuery) {
                                $siblingQuery->select(DB::raw(1))
                                    ->from('tabStock Entry Detail as sted_sibling')
                                    ->whereColumn('sted_sibling.parent', 'sted.parent')
                                    ->where('sted_sibling.status', StockEntryConstants::STATUS_FOR_CHECKING);
                            });
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sted.item_code', 'like', "%{$search}%")
                        ->orWhere('sted.description', 'like', "%{$search}%")
                        ->orWhere('ste.customer_1', 'like', "%{$search}%")
                        ->orWhere('ste.sales_order_no', 'like', "%{$search}%")
                        ->orWhere('ste.name', 'like', "%{$search}%")
                        ->orWhere('ste.so_customer_name', 'like', "%{$search}%")
                        ->orWhere('sted.name', 'like', "%{$search}%");
                });
            })
            ->select([
                'ste.delivery_date', DB::raw('NULL as sales_order'), 'ste.sales_order_no', 'sted.name AS id', 'sted.status', 'ste.name',
                DB::raw('NULL as delivery_note'), 'sted.item_code', 'sted.description', 'sted.qty', 'sted.uom', 'sted.s_warehouse as warehouse',
                'sted.owner', 'ste.customer_1 as customer', 'ste.creation',
                DB::raw('NULL as parent_item'), DB::raw('NULL as piName'), DB::raw('NULL as piQty'), DB::raw('NULL as piWarehouse'), DB::raw('NULL as piUom'),
                DB::raw('"stock_entry" as type'),
            ])
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC");

        $productBundleQuery = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->join('tabPacked Item as pi', 'pi.name', '=', 'psi.pi_detail')
            ->whereIn('dr.docstatus', [0, 1])
            ->whereIn('ps.docstatus', [0, 1])
            ->where('ps.creation', '>=', $creationFrom)
            ->whereIn('dri.warehouse', $warehouseIds)
            ->where(function ($query) {
                $query->where('psi.status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->orWhere(function ($issuedQuery) {
                        $issuedQuery->where('psi.status', StockEntryConstants::STATUS_ISSUED)
                            ->whereExists(function ($siblingQuery) {
                                $siblingQuery->select(DB::raw(1))
                                    ->from('tabPacking Slip Item as psi_sibling')
                                    ->whereColumn('psi_sibling.parent', 'psi.parent')
                                    ->where('psi_sibling.status', StockEntryConstants::STATUS_FOR_CHECKING);
                            });
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pi.item_code', 'like', "%{$search}%")
                        ->orWhere('pi.description', 'like', "%{$search}%")
                        ->orWhere('dr.customer', 'like', "%{$search}%")
                        ->orWhere('ps.sales_order', 'like', "%{$search}%")
                        ->orWhere('ps.name', 'like', "%{$search}%")
                        ->orWhere('dr.name', 'like', "%{$search}%")
                        ->orWhere('psi.name', 'like', "%{$search}%");
                });
            })
            ->select([
                'dr.delivery_date', 'ps.sales_order', DB::raw('NULL as sales_order_no'), 'psi.name AS id', 'psi.status', 'ps.name', 'ps.delivery_note',
                'pi.item_code', 'pi.description', 'pi.qty as qty', 'pi.uom', 'pi.warehouse', 'psi.owner', 'dr.customer', 'ps.creation',
                'pi.parent_item', 'pi.name as piName', 'pi.qty as piQty', 'pi.warehouse as piWarehouse', 'pi.uom as piUom',
                DB::raw('"packed_item" as type'),
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        $unionQuery = $pickingSlipQuery->unionAll($stockEntryQuery)->unionAll($productBundleQuery);

        // Use paginate so the UI can display correct totals/last_page.
        $passable->paginatedData = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->orderBy('creation', 'desc')
            ->orderByRaw("FIELD(status, 'For Checking', 'Issued') ASC")
            ->mergeBindings($unionQuery->getQuery())
            ->paginate($perPage, ['*'], 'page', $page);

        return $next($passable);
    }
}
