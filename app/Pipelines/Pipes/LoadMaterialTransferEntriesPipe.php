<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class LoadMaterialTransferEntriesPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $passable->entries = ($passable->getMaterialTransferEntries)($passable->allowedWarehouses);

        return $next($passable);
    }
}
