<?php

namespace App\Jobs;

use App\Services\Typesense\TypesenseItemIndexer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncItemToTypesense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $itemCode,
        public bool $delete = false
    ) {}

    public function handle(TypesenseItemIndexer $indexer): void
    {
        if (! config('typesense.enabled')) {
            return;
        }

        try {
            if ($this->delete) {
                $indexer->deleteItem($this->itemCode);

                return;
            }

            $indexer->upsertItem($this->itemCode);
        } catch (\Throwable $e) {
            Log::warning('Typesense sync job failed.', [
                'item_code' => $this->itemCode,
                'delete' => $this->delete,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
