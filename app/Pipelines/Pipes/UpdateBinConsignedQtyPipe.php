<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class UpdateBinConsignedQtyPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $binArray = $passable->binArray ?? [];

        foreach ($passable->csteItems as $item) {
            if (($item->purpose ?? null) !== 'Pull Out') {
                continue;
            }

            $binName = $binArray[$item->source_warehouse][$item->item_code]['name'] ?? null;
            if (! $binName) {
                continue;
            }

            $currentConsignedQty = $binArray[$item->source_warehouse][$item->item_code]['consigned_qty'] ?? 0;
            $consignedQtyAfterTransaction = $currentConsignedQty - ($item->qty ?? 0);
            $consignedQtyAfterTransaction = max(0, $consignedQtyAfterTransaction);

            DB::table('tabBin')->where('name', $binName)->update(['consigned_qty' => $consignedQtyAfterTransaction]);

            // Keep in-memory array in sync for multiple items in same bin
            $binArray[$item->source_warehouse][$item->item_code]['consigned_qty'] = $consignedQtyAfterTransaction;
        }

        $passable->binArray = $binArray;

        return $next($passable);
    }
}
