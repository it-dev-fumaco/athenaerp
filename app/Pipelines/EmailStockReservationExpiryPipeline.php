<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\LoadExpiringReservationsPipe;
use App\Pipelines\Pipes\ResolveRecipientEmailsPipe;
use App\Pipelines\Pipes\SendStockReservationExpiryEmailsPipe;
use Illuminate\Pipeline\Pipeline;

class EmailStockReservationExpiryPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the stock reservation expiry email pipeline.
     *
     * @param  object  $passable  Fresh object (e.g. (object) [])
     */
    public function run(object $passable)
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadExpiringReservationsPipe::class,
                ResolveRecipientEmailsPipe::class,
                SendStockReservationExpiryEmailsPipe::class,
            ])
            ->then(fn ($p) => $p);
    }
}
