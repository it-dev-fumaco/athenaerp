<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\ItemSupplier;
use App\Models\SalesOrder;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class EnrichMaterialIssuePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $entries = $passable->entries;

        $salesOrderNos = $entries->pluck('sales_order_no')->unique()->filter()->values()->toArray();
        $itemCodes = $entries->pluck('item_code')->unique()->values()->toArray();
        $ownerNames = $entries->pluck('owner')->unique()->filter()->values()->toArray();
        $warehouses = $entries->pluck('s_warehouse')->unique()->filter()->values()->toArray();

        $passable->soCustomers = SalesOrder::query()
            ->whereIn('name', $salesOrderNos)
            ->pluck('customer', 'name')
            ->toArray();

        $passable->partNosQuery = ItemSupplier::query()
            ->whereIn('parent', $itemCodes)
            ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))
            ->groupBy('parent')
            ->pluck('supplier_part_nos', 'parent')
            ->toArray();

        $passable->ownerFullNames = User::query()
            ->whereIn('name', $ownerNames)
            ->pluck('full_name', 'name')
            ->toArray();

        $itemWarehousePairs = $entries->map(fn ($d) => [$d->item_code, $d->s_warehouse])->unique()->values()->toArray();
        $passable->actualQtyMap = ($passable->getActualQtyBulk)($itemWarehousePairs);
        $passable->availableQtyMap = ($passable->getAvailableQtyBulk)($itemWarehousePairs);
        $passable->parentWarehouses = ($passable->getWarehouseParentsBulk)($warehouses);

        return $next($passable);
    }
}
