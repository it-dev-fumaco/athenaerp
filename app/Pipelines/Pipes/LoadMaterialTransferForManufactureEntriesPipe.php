<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class LoadMaterialTransferForManufactureEntriesPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $passable->entries = ($passable->getMaterialTransferForManufactureEntries)($passable->allowedWarehouses);

        return $next($passable);
    }
}
