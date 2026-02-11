<?php

namespace App\Pipelines;

use App\Http\Helpers\ApiResponse;
use App\Pipelines\Pipes\EnsureBrochureDirectoryAndFilenamePipe;
use App\Pipelines\Pipes\PersistBrochureLogPipe;
use App\Pipelines\Pipes\ReadBrochureSpreadsheetPipe;
use App\Pipelines\Pipes\StoreBrochureFilePipe;
use App\Pipelines\Pipes\ValidateBrochureFilePipe;
use Illuminate\Pipeline\Pipeline;

class BrochureUploadPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the brochure upload pipeline. Expects passable with: request, file, readFileCallable.
     *
     * @param  object  $passable  Must have: request, file, readFileCallable (callable that accepts file and returns fileContents array)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                ValidateBrochureFilePipe::class,
                ReadBrochureSpreadsheetPipe::class,
                EnsureBrochureDirectoryAndFilenamePipe::class,
                PersistBrochureLogPipe::class,
                StoreBrochureFilePipe::class,
            ])
            ->then(fn ($p) => ApiResponse::success(
                '/preview/'.strtoupper($p->project).'/'.$p->newFilename.'.'.$p->fileExt
            ));
    }
}
