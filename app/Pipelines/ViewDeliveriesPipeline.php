<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\BuildPickingListPipe;
use App\Pipelines\Pipes\EnrichPickingListPipe;
use App\Pipelines\Pipes\FormatPickingResponsePipe;
use Illuminate\Pipeline\Pipeline;

class ViewDeliveriesPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the view deliveries (picking list) pipeline.
     * Passable must have: allowedWarehouses, search, getWarehouseParentsBulk (callable).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                BuildPickingListPipe::class,
                EnrichPickingListPipe::class,
                FormatPickingResponsePipe::class,
            ])
            ->then(fn ($p) => response()->json([
                'picking' => $p->pickingList,
                'pagination' => $p->pagination,
            ]));
    }

    /**
     * Return total count of delivery/picking list rows (same dataset as run()).
     * Passable must have: allowedWarehouses, search (optional), perPage, page.
     *
     * @return int
     */
    public function getTotalCount(object $passable): int
    {
        $this->pipeline
            ->send($passable)
            ->through([BuildPickingListPipe::class])
            ->then(fn ($p) => $p);

        return $passable->paginatedData->total();
    }
}
