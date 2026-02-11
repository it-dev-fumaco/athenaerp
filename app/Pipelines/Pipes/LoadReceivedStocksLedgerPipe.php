<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\StockEntry;
use Carbon\Carbon;
use Closure;

class LoadReceivedStocksLedgerPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->branchWarehouse ?? null)) {
            return $next($passable);
        }

        $itemReceive = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->when($passable->beginningInventoryStartDate ?? null, fn ($query) => $query->whereDate('ste.delivery_date', '>=', $passable->beginningInventoryStartDate))
            ->when($passable->itemCode ?? null, fn ($query) => $query->where('sted.item_code', $passable->itemCode))
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.consignment_status', 'Received')
            ->where('sted.t_warehouse', $passable->branchWarehouse)
            ->select('ste.name', 'sted.t_warehouse', 'sted.consignment_date_received', 'sted.item_code', 'sted.transfer_qty', 'sted.consignment_received_by', 'sted.description')
            ->orderBy('sted.consignment_date_received', 'desc')
            ->get();

        foreach ($itemReceive as $a) {
            $dateReceived = Carbon::parse($a->consignment_date_received)->format('Y-m-d');
            $passable->result[$a->item_code][$dateReceived][] = [
                'qty' => number_format($a->transfer_qty),
                'type' => 'Stocks Received',
                'transaction_date' => $dateReceived,
                'reference' => $a->name,
                'owner' => $a->consignment_received_by,
            ];
            $passable->itemDescriptions[$a->item_code] = $a->description;
        }

        return $next($passable);
    }
}
