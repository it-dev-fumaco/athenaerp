<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\LoadBinsForPulloutPipe;
use App\Pipelines\Pipes\LoadConsignmentStockEntryItemsPipe;
use App\Pipelines\Pipes\LoadSubmittedPulloutsPipe;
use App\Pipelines\Pipes\MarkConsignmentStockEntriesCompletedPipe;
use App\Pipelines\Pipes\UpdateBinConsignedQtyPipe;
use Illuminate\Pipeline\Pipeline;

class UpdateStocksPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the update stocks (pullout) pipeline. Passable can be empty.
     *
     * @param  object  $passable  Optional; e.g. (object) []
     * @return mixed
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadSubmittedPulloutsPipe::class,
                LoadConsignmentStockEntryItemsPipe::class,
                LoadBinsForPulloutPipe::class,
                UpdateBinConsignedQtyPipe::class,
                MarkConsignmentStockEntriesCompletedPipe::class,
            ])
            ->then(fn ($p) => $p);
    }
}
