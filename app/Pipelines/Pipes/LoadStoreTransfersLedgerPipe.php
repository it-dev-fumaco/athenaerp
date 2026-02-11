<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\StockEntry;
use Carbon\Carbon;
use Closure;

class LoadStoreTransfersLedgerPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->branchWarehouse ?? null)) {
            return $next($passable);
        }

        $itemTransferred = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->when($passable->beginningInventoryStartDate ?? null, fn ($query) => $query->whereDate('ste.delivery_date', '>=', $passable->beginningInventoryStartDate))
            ->when($passable->itemCode ?? null, fn ($query) => $query->where('sted.item_code', $passable->itemCode))
            ->whereIn('ste.transfer_as', ['Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $passable->branchWarehouse)
            ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
            ->orderBy('sted.creation', 'desc')
            ->get();

        foreach ($itemTransferred as $v) {
            $dateTransferred = Carbon::parse($v->creation)->format('Y-m-d');
            $passable->result[$v->item_code][$dateTransferred][] = [
                'qty' => number_format($v->transfer_qty),
                'type' => 'Store Transfer',
                'transaction_date' => $dateTransferred,
                'reference' => $v->name,
                'owner' => $v->owner,
            ];
            $passable->itemDescriptions[$v->item_code] = $v->description;
        }

        return $next($passable);
    }
}
