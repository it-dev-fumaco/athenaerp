<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\BuildMaterialTransferViewLookupPipe;
use App\Pipelines\Pipes\FormatMaterialTransferViewRecordsPipe;
use App\Pipelines\Pipes\LoadMaterialTransferEntriesPipe;
use Illuminate\Pipeline\Pipeline;

class ViewMaterialTransferPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the view material transfer pipeline.
     * Passable must have: allowedWarehouses, getMaterialTransferEntries,
     * buildMaterialTransferViewLookupData, buildMaterialTransferViewRecordsList (callables).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadMaterialTransferEntriesPipe::class,
                BuildMaterialTransferViewLookupPipe::class,
                FormatMaterialTransferViewRecordsPipe::class,
            ])
            ->then(fn ($p) => response()->json(['records' => $p->records]));
    }
}
