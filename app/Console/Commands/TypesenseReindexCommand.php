<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Services\Typesense\TypesenseCollectionManager;
use App\Services\Typesense\TypesenseItemIndexer;
use Illuminate\Console\Command;

class TypesenseReindexCommand extends Command
{
    protected $signature = 'typesense:reindex {--recreate : Drop and recreate the collection first}';

    protected $description = 'Full reindex of searchable items into Typesense';

    public function handle(TypesenseCollectionManager $manager, TypesenseItemIndexer $indexer): int
    {
        if ($this->option('recreate')) {
            $manager->deleteCollectionIfExists();
        }

        $manager->ensureCollection();

        $chunk = (int) config('typesense.reindex_chunk_size', 150);
        $total = Item::query()->where('disabled', 0)->where('has_variants', 0)->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Item::query()
            ->where('disabled', 0)
            ->where('has_variants', 0)
            ->orderBy('name')
            ->chunk($chunk, function ($items) use ($indexer, $bar) {
                $codes = $items->pluck('name')->all();
                $documents = $indexer->buildDocumentsForItemCodes($codes);
                $indexer->importDocuments($documents);
                $bar->advance($items->count());
            });

        $bar->finish();
        $this->newLine();
        $this->info('Typesense reindex completed.');

        return self::SUCCESS;
    }
}
