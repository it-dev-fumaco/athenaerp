<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Illuminate\Support\Facades\Storage;
use Closure;

class StoreBrochureFilePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if ($passable instanceof \Illuminate\Http\JsonResponse) {
            return $passable;
        }

        $directory = 'brochures/'.strtoupper($passable->project);
        $filename = $passable->newFilename.'.'.$passable->fileExt;

        if (! Storage::disk('upcloud')->exists($directory)) {
            Storage::disk('upcloud')->makeDirectory($directory);
        }
        $destination = Storage::disk('upcloud')->path($directory.'/'.$filename);

        // Copy instead of move: the file may still be locked by PhpSpreadsheet after ReadBrochureSpreadsheetPipe
        if (! copy($passable->file->getRealPath(), $destination)) {
            throw new \RuntimeException('Could not save brochure file to '.$directory);
        }

        return $next($passable);
    }
}
