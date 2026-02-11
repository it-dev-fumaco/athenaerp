<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class LoadBinsForPulloutPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if ($passable->csteItems->isEmpty()) {
            $passable->binArray = [];

            return $next($passable);
        }

        $sourceWarehouses = collect($passable->submittedPullouts)->pluck('source_warehouse')->unique()->values()->all();
        $itemCodes = $passable->csteItems->pluck('item_code')->unique()->values()->all();

        $bin = DB::table('tabBin')
            ->whereIn('warehouse', $sourceWarehouses)
            ->whereIn('item_code', $itemCodes)
            ->select('name', 'warehouse', 'item_code', 'consigned_qty', 'stock_uom')
            ->get();

        $binArray = [];
        foreach ($bin as $b) {
            $binArray[$b->warehouse][$b->item_code] = [
                'consigned_qty' => $b->consigned_qty,
                'name' => $b->name,
            ];
        }

        $passable->binArray = $binArray;

        return $next($passable);
    }
}
