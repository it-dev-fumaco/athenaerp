<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\BuildMaterialTransferForManufactureLookupPipe;
use App\Pipelines\Pipes\FormatMaterialTransferForManufactureRecordsPipe;
use App\Pipelines\Pipes\LoadMaterialTransferForManufactureEntriesPipe;
use Illuminate\Pipeline\Pipeline;

class ViewMaterialTransferForManufacturePipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the view material transfer for manufacture pipeline.
     * Passable must have: allowedWarehouses, getMaterialTransferForManufactureEntries,
     * buildMaterialTransferLookupData, buildMaterialTransferRecordsList (callables).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadMaterialTransferForManufactureEntriesPipe::class,
                BuildMaterialTransferForManufactureLookupPipe::class,
                FormatMaterialTransferForManufactureRecordsPipe::class,
            ])
            ->then(fn ($p) => response()->json(['records' => $p->records]));
    }
}
