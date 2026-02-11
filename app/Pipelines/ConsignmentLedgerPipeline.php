<?php

namespace App\Pipelines;

use App\Pipelines\Pipes\LoadBeginningInventoryLedgerPipe;
use App\Pipelines\Pipes\LoadReceivedStocksLedgerPipe;
use App\Pipelines\Pipes\LoadReturnsLedgerPipe;
use App\Pipelines\Pipes\LoadStoreTransfersLedgerPipe;
use Illuminate\Pipeline\Pipeline;
use Illuminate\View\View;

class ConsignmentLedgerPipeline
{
    public function __construct(
        protected Pipeline $pipeline
    ) {}

    /**
     * Run the consignment ledger pipeline. Passable must have: branchWarehouse, itemCode (optional).
     */
    public function run(object $passable): View
    {
        return $this->pipeline
            ->send($passable)
            ->through([
                LoadBeginningInventoryLedgerPipe::class,
                LoadReceivedStocksLedgerPipe::class,
                LoadStoreTransfersLedgerPipe::class,
                LoadReturnsLedgerPipe::class,
            ])
            ->then(fn ($p) => view('consignment.tbl_consignment_ledger', [
                'result' => $p->result ?? [],
                'branchWarehouse' => $p->branchWarehouse,
                'itemDescriptions' => $p->itemDescriptions ?? [],
            ]));
    }
}
