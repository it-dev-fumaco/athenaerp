<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class BuildMaterialTransferForManufactureLookupPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $passable->lookupData = ($passable->buildMaterialTransferLookupData)($passable->entries);

        return $next($passable);
    }
}
