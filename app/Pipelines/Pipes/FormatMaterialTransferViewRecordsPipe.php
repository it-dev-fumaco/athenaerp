<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class FormatMaterialTransferViewRecordsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $passable->records = ($passable->buildMaterialTransferViewRecordsList)($passable->entries, $passable->lookupData);

        return $next($passable);
    }
}
