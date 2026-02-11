<?php

namespace App\Contracts\Pipeline;

use Closure;

interface Pipe
{
    /**
     * Process the passable and call the next pipe in the pipeline.
     */
    public function handle(mixed $passable, Closure $next): mixed;
}
