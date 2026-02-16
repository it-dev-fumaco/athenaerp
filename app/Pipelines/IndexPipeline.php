<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\ResolveIndexResponsePipe;
use App\Pipelines\Pipes\UpdateReservationStatusPipe;
use Illuminate\Pipeline\Pipeline;

class IndexPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the main index (dashboard) pipeline.
     * Passable may have: updateReservationStatus (callable), getConsignmentDashboardView (callable).
     *
     * @return \Illuminate\Http\Response|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                UpdateReservationStatusPipe::class,
                ResolveIndexResponsePipe::class,
            ])
            ->then(fn ($p) => $p->response);
    }
}
