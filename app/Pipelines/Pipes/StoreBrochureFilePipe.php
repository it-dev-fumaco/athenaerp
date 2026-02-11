<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;

class StoreBrochureFilePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $destinationPath = public_path('storage/brochures/'.strtoupper($passable->project));
        $filename = $passable->newFilename.'.'.$passable->fileExt;

        $passable->file->move($destinationPath, $filename);

        return $next($passable);
    }
}
