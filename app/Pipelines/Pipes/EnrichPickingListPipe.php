<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\ItemSupplier;
use App\Models\User;
use Closure;

class EnrichPickingListPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $paginatedData = $passable->paginatedData;
        $itemCodes = collect($paginatedData->items())->pluck('item_code')->unique();
        $owners = collect($paginatedData->items())->pluck('owner')->unique();

        $passable->supplierPartNos = ItemSupplier::query()
            ->whereIn('parent', $itemCodes)
            ->pluck('supplier_part_no', 'parent');

        $passable->ownerNames = User::query()
            ->whereIn('wh_user', $owners)
            ->pluck('full_name', 'wh_user');

        $warehouseNames = collect($paginatedData->items())
            ->map(fn ($d) => $d->warehouse ?? $d->s_warehouse ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $passable->parentWarehouses = isset($passable->getWarehouseParentsBulk) && is_callable($passable->getWarehouseParentsBulk)
            ? ($passable->getWarehouseParentsBulk)($warehouseNames)
            : [];

        return $next($passable);
    }
}
