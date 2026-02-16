<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class MarkConsignmentStockEntriesCompletedPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! empty($passable->csteNames ?? [])) {
            DB::table('tabConsignment Stock Entry')
                ->whereIn('name', $passable->csteNames)
                ->update(['status' => 'Completed']);
        }

        return $next($passable);
    }
}
