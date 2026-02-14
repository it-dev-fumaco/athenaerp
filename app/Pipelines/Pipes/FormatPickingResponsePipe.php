<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;

class FormatPickingResponsePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $paginatedData = $passable->paginatedData;
        $supplierPartNos = $passable->supplierPartNos ?? collect();
        $ownerNames = $passable->ownerNames ?? collect();
        $parentWarehouses = $passable->parentWarehouses ?? [];

        $list = [];
        foreach ($paginatedData->items() as $d) {
            $partNos = $supplierPartNos->get($d->item_code, '');
            $owner = $ownerNames->get($d->owner, null);
            $warehouseKey = $d->warehouse ?? $d->s_warehouse ?? null;
            $parentWarehouse = $warehouseKey ? Arr::get($parentWarehouses, $warehouseKey, null) : null;

            $list[] = [
                'owner' => $owner,
                'warehouse' => $d->warehouse ?? $d->s_warehouse ?? null,
                'customer' => $d->customer ?? $d->customer_1 ?? null,
                'sales_order' => $d->sales_order ?? $d->sales_order_no ?? null,
                'id' => $d->id,
                'part_nos' => $partNos,
                'status' => $d->status,
                'name' => $d->name,
                'delivery_note' => $d->delivery_note ?? null,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'qty' => $d->qty,
                'stock_uom' => $d->uom ?? $d->stock_uom ?? null,
                'parent_warehouse' => $parentWarehouse,
                'creation' => $d->creation ? Carbon::parse($d->creation)->format('M-d-Y h:i:A') : null,
                'type' => $d->type,
                'classification' => $d->transfer_as ?? 'Customer Order',
                'delivery_date' => $d->delivery_date ? Carbon::parse($d->delivery_date)->format('M-d-Y') : null,
                'delivery_status' => $d->delivery_date && Carbon::parse($d->delivery_date) < now() ? 'late' : null,
            ];
        }

        $passable->pickingList = $list;
        $passable->pagination = [
            'total' => $paginatedData->total(),
            'per_page' => $paginatedData->perPage(),
            'current_page' => $paginatedData->currentPage(),
            'last_page' => $paginatedData->lastPage(),
        ];

        return $next($passable);
    }
}
