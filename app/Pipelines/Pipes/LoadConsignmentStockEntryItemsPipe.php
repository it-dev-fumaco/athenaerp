<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class LoadConsignmentStockEntryItemsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (empty($passable->csteNames ?? [])) {
            $passable->csteItems = collect();

            return $next($passable);
        }

        $passable->csteItems = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabConsignment Stock Entry Detail as csted', 'cste.name', 'csted.parent')
            ->whereIn('csted.parent', $passable->csteNames)
            ->get();

        return $next($passable);
    }
}
