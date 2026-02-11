<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class BuildMaterialTransferViewLookupPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $passable->lookupData = ($passable->buildMaterialTransferViewLookupData)($passable->entries);

        return $next($passable);
    }
}
