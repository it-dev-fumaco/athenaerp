<?php

namespace App\Pipelines\Pipes;

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
        $search = $passable->search ?? '';
        $perPage = 20;
        $page = $passable->page ?? 1;

        $warehouseIds = $allowedWarehouses instanceof \Illuminate\Support\Collection
            ? $allowedWarehouses->all()
            : (array) $allowedWarehouses;

        if (empty($warehouseIds)) {
            $passable->paginatedData = new LengthAwarePaginator([], 0, $perPage, $page);

            return $next($passable);
        }

        $pickingSlipQuery = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->whereRaw('dri.item_code = psi.item_code')
            ->where(['dr.docstatus' => 0, 'ps.docstatus' => 0])
            ->where(function ($query) use ($search) {
                $query->where('psi.item_code', 'like', "%{$search}%")
                    ->orWhere('psi.description', 'like', "%{$search}%")
                    ->orWhere('dr.customer', 'like', "%{$search}%")
                    ->orWhere('ps.sales_order', 'like', "%{$search}%")
                    ->orWhere('ps.name', 'like', "%{$search}%")
                    ->orWhere('dr.name', 'like', "%{$search}%")
                    ->orWhere('psi.name', 'like', "%{$search}%");
            })
            ->whereIn('dri.warehouse', $warehouseIds)
            ->select([
                'dr.delivery_date', 'ps.sales_order', DB::raw('NULL as sales_order_no'), 'psi.name AS id', 'psi.status',
                'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', DB::raw('SUM(dri.qty) as qty'), 'dri.uom',
                'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation',
                DB::raw('NULL as parent_item'), DB::raw('NULL as piName'), DB::raw('NULL as piQty'), DB::raw('NULL as piWarehouse'), DB::raw('NULL as piUom'),
                DB::raw('"picking_slip" as type'),
            ])
            ->groupBy(['dr.delivery_date', 'ps.sales_order', 'psi.name', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'dri.uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation'])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        $stockEntryQuery = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $warehouseIds)
            ->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->where(function ($query) use ($search) {
                $query->where('sted.item_code', 'like', "%{$search}%")
                    ->orWhere('sted.description', 'like', "%{$search}%")
                    ->orWhere('ste.customer_1', 'like', "%{$search}%")
                    ->orWhere('ste.sales_order_no', 'like', "%{$search}%")
                    ->orWhere('ste.name', 'like', "%{$search}%")
                    ->orWhere('ste.so_customer_name', 'like', "%{$search}%")
                    ->orWhere('sted.name', 'like', "%{$search}%");
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
            ->where(['dr.docstatus' => 0, 'ps.docstatus' => 0])
            ->whereIn('dri.warehouse', $warehouseIds)
            ->where(function ($query) use ($search) {
                $query->where('pi.item_code', 'like', "%{$search}%")
                    ->orWhere('pi.description', 'like', "%{$search}%")
                    ->orWhere('dr.customer', 'like', "%{$search}%")
                    ->orWhere('ps.sales_order', 'like', "%{$search}%")
                    ->orWhere('ps.name', 'like', "%{$search}%")
                    ->orWhere('dr.name', 'like', "%{$search}%")
                    ->orWhere('psi.name', 'like', "%{$search}%");
            })
            ->select([
                'dr.delivery_date', 'ps.sales_order', DB::raw('NULL as sales_order_no'), 'psi.name AS id', 'psi.status', 'ps.name', 'ps.delivery_note',
                'pi.item_code', 'pi.description', 'pi.qty as qty', 'pi.uom', 'pi.warehouse', 'psi.owner', 'dr.customer', 'ps.creation',
                'pi.parent_item', 'pi.name as piName', 'pi.qty as piQty', 'pi.warehouse as piWarehouse', 'pi.uom as piUom',
                DB::raw('"packed_item" as type'),
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        $unionQuery = $pickingSlipQuery->unionAll($stockEntryQuery)->unionAll($productBundleQuery);

        $passable->paginatedData = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->orderByRaw("FIELD(status, 'For Checking', 'Issued') ASC")
            ->mergeBindings($unionQuery->getQuery())
            ->paginate($perPage, ['*'], 'page', $page);

        return $next($passable);
    }
}
