<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\StockEntry;
use Carbon\Carbon;
use Closure;

class LoadReturnsLedgerPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->branchWarehouse ?? null)) {
            return $next($passable);
        }

        $itemReturned = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->when($passable->beginningInventoryStartDate ?? null, fn ($query) => $query->whereDate('ste.delivery_date', '>=', $passable->beginningInventoryStartDate))
            ->when($passable->itemCode ?? null, fn ($query) => $query->where('sted.item_code', $passable->itemCode))
            ->whereIn('ste.transfer_as', ['For Return'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $passable->branchWarehouse)
            ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
            ->orderBy('sted.creation', 'desc')
            ->get();

        foreach ($itemReturned as $a) {
            $dateReturned = Carbon::parse($a->creation)->format('Y-m-d');
            $passable->result[$a->item_code][$dateReturned][] = [
                'qty' => number_format($a->transfer_qty),
                'type' => 'Stocks Returned',
                'transaction_date' => $dateReturned,
                'reference' => $a->name,
                'owner' => $a->owner,
            ];
            $passable->itemDescriptions[$a->item_code] = $a->description;
        }

        return $next($passable);
    }
}
