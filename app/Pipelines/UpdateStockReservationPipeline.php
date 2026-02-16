<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\ExpireOldReservationsPipe;
use App\Pipelines\Pipes\MarkIssuedReservationsPipe;
use App\Pipelines\Pipes\MarkPartiallyIssuedReservationsPipe;
use Illuminate\Pipeline\Pipeline;

class UpdateStockReservationPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the stock reservation update pipeline. Passable can be empty.
     *
     * @param  object  $passable  Optional; can be (object) []
     * @return mixed
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                ExpireOldReservationsPipe::class,
                MarkPartiallyIssuedReservationsPipe::class,
                MarkIssuedReservationsPipe::class,
            ])
            ->then(fn ($p) => $p);
    }
}
