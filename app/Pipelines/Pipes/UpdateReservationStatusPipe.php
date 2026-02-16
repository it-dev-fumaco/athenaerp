<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class UpdateReservationStatusPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (isset($passable->updateReservationStatus) && is_callable($passable->updateReservationStatus)) {
            ($passable->updateReservationStatus)();
        }

        return $next($passable);
    }
}
