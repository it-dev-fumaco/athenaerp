<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnsureBrochureDirectoryAndFilenamePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if ($passable instanceof \Illuminate\Http\JsonResponse) {
            return $passable;
        }

        $project = $passable->project;
        $projectPath = 'brochures/'.strtoupper($project);

        if (! Storage::disk('upcloud')->exists($projectPath)) {
            Storage::disk('upcloud')->makeDirectory($projectPath);
        }

        $storageFiles = Storage::disk('upcloud')->files($projectPath);
        $series = $storageFiles ? (count($storageFiles) > 1 ? count($storageFiles) : 1) : null;
        $seriesSuffix = $series ? '-'.(string) $series : '';

        $passable->newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d').$seriesSuffix;
        $passable->transactionDate = now()->toDateTimeString();

        return $next($passable);
    }
}
