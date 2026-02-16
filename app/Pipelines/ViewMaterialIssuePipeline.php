<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\EnrichMaterialIssuePipe;
use App\Pipelines\Pipes\FormatMaterialIssueResponsePipe;
use App\Pipelines\Pipes\LoadMaterialIssueEntriesPipe;
use Illuminate\Pipeline\Pipeline;

class ViewMaterialIssuePipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the view material issue pipeline.
     * Passable must have: allowedWarehouses, getActualQtyBulk, getAvailableQtyBulk, getWarehouseParentsBulk (callables).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadMaterialIssueEntriesPipe::class,
                EnrichMaterialIssuePipe::class,
                FormatMaterialIssueResponsePipe::class,
            ])
            ->then(fn ($p) => response()->json(['records' => $p->records]));
    }
}
