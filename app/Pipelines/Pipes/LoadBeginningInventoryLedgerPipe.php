<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\BeginningInventory;
use Closure;

class LoadBeginningInventoryLedgerPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $branchWarehouse = $passable->branchWarehouse ?? null;
        $itemCode = $passable->itemCode ?? null;

        $passable->result = [];
        $passable->itemDescriptions = [];

        if (! $branchWarehouse) {
            return $next($passable);
        }

        $itemOpeningStock = BeginningInventory::query()
            ->from('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->where('cb.status', 'Approved')
            ->where('branch_warehouse', $branchWarehouse)
            ->when($itemCode, fn ($query) => $query->where('cbi.item_code', $itemCode))
            ->select('cbi.item_code', 'cbi.opening_stock', 'cb.transaction_date', 'cb.branch_warehouse', 'cb.name', 'cb.owner', 'cbi.item_description')
            ->orderBy('cb.transaction_date', 'asc')
            ->get();

        foreach ($itemOpeningStock as $r) {
            $passable->result[$r->item_code][$r->transaction_date][] = [
                'qty' => number_format($r->opening_stock),
                'type' => 'Beginning Inventory',
                'transaction_date' => $r->transaction_date,
                'reference' => $r->name,
                'owner' => $r->owner,
            ];
            $passable->itemDescriptions[$r->item_code] = $r->item_description;
        }

        $beginningInventoryStart = BeginningInventory::query()
            ->where('branch_warehouse', $branchWarehouse)
            ->orderBy('transaction_date', 'asc')
            ->pluck('transaction_date')
            ->first();

        $passable->beginningInventoryStartDate = $beginningInventoryStart
            ? \Carbon\Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d')
            : \Carbon\Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

        return $next($passable);
    }
}
