<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\Storage;

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

        // IMPORTANT: UpCloud disk is S3-compatible; do not use disk->path() + copy() (local FS).
        // Use Storage streaming upload instead.
        $tmpPath = $passable->file->getRealPath();
        $stream = @fopen($tmpPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Could not open uploaded file for streaming.');
        }

        $stored = Storage::disk('upcloud')->put($directory.'/'.$filename, $stream);
        @fclose($stream);

        if (! $stored) {
            throw new \RuntimeException('Could not save brochure file to '.$directory);
        }

        return $next($passable);
    }
}
