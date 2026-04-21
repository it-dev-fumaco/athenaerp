<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\Typesense\TypesenseCollectionManager;
use App\Services\Typesense\TypesenseItemIndexer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TypesenseSyncDeltaCommand extends Command
{
    protected $signature = 'typesense:sync-delta';

    protected $description = 'Upsert items modified since the last delta cursor (catches ERP sync outside Laravel)';

    public function handle(TypesenseCollectionManager $manager, TypesenseItemIndexer $indexer): int
    {
        if (! config('typesense.enabled')) {
            $this->warn('TYPESENSE_ENABLED is false; skipping delta sync.');

            return self::SUCCESS;
        }

        $manager->ensureCollection();

        $cursor = Cache::get('typesense.delta_cursor');
        $chunk = max(50, (int) config('typesense.reindex_chunk_size', 150));

        $processed = 0;

        Item::query()
            ->when($cursor, fn ($q) => $q->where('modified', '>', $cursor))
            ->orderBy('modified')
            ->chunk($chunk, function ($items) use ($indexer, &$processed) {
                $maxModified = null;
                foreach ($items as $item) {
                    $indexer->upsertItem($item->name);
                    $processed++;
                    $maxModified = $item->modified;
                }
                if ($maxModified !== null) {
                    Cache::put('typesense.delta_cursor', $maxModified, now()->addDays(60));
                }
            });

        $this->info("Delta sync processed {$processed} item(s).");

        return self::SUCCESS;
    }
}
