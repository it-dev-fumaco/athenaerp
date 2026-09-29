<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\ItemImages;
use App\Services\ItemImageErpSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillErpItemImagesCommand extends Command
{
    protected $signature = 'items:backfill-erp-images
                            {--dry-run : Print planned updates without writing or changing object ACL}
                            {--item=* : Item code; repeatable}
                            {--limit= : Max items to process}
                            {--force : Overwrite tabItem.image even if it already looks like an Upcloud https URL}
                            {--user=Administrator : modified_by value written to ERPNext}';

    protected $description = 'Publish default Athena item images and write permanent Upcloud URLs to tabItem.image';

    public function handle(ItemImageErpSyncService $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $modifiedBy = (string) $this->option('user');
        $limitOption = $this->option('limit');
        $limit = ($limitOption === null || $limitOption === '') ? null : max(0, (int) $limitOption);
        $itemFilter = array_values(array_filter(
            array_map('strval', (array) $this->option('item')),
            fn (string $value) => trim($value) !== ''
        ));
        $itemFilter = array_map('trim', $itemFilter);

        $updated = 0;
        $skipped = 0;
        $missing = 0;
        $errors = 0;
        $processed = 0;

        $this->info($dryRun ? 'Dry run: no database or ACL changes.' : 'Backfilling ERPNext item images...');

        $bar = null;
        if (! $dryRun) {
            $total = $this->estimateCount($itemFilter, $limit);
            $bar = $this->output->createProgressBar($total);
            $bar->start();
        }

        foreach ($this->eachItemCode($itemFilter, $limit) as $itemCode) {
            $processed++;

            try {
                $default = $sync->defaultImage($itemCode);
                $imagePath = $default?->image_path ? trim((string) $default->image_path) : '';

                if ($imagePath === '') {
                    $skipped++;
                    if ($dryRun) {
                        $this->line("{$itemCode}: skipped (no image_path)");
                    }
                    $bar?->advance();

                    continue;
                }

                $currentImage = Item::query()->where('name', $itemCode)->value('image');
                if (! $force && $sync->isAlreadyBackfilled(is_string($currentImage) ? $currentImage : null)) {
                    $skipped++;
                    if ($dryRun) {
                        $this->line("{$itemCode}: skipped (already backfilled)");
                    }
                    $bar?->advance();

                    continue;
                }

                if (! $sync->objectExists($imagePath)) {
                    $missing++;
                    $this->newLine();
                    $this->warn("{$itemCode}: missing object for {$imagePath}");
                    $bar?->advance();

                    continue;
                }

                $key = $sync->resolveStorageKey($imagePath);
                $url = $sync->permanentUrl($imagePath);

                if ($dryRun) {
                    $updated++;
                    $this->line("{$itemCode}: {$key} -> {$url}");

                    continue;
                }

                $sync->syncDefaultImage($itemCode, $modifiedBy);
                $updated++;
                $bar?->advance();
            } catch (\Throwable $e) {
                $errors++;
                Log::error('items:backfill-erp-images failed', [
                    'item_code' => $itemCode,
                    'error' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("{$itemCode}: ".$e->getMessage());
                $bar?->advance();
            }
        }

        $bar?->finish();
        $this->newLine(2);
        $this->info("Processed {$processed}. Updated {$updated}, skipped {$skipped}, missing-object {$missing}, errors {$errors}.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $itemFilter
     * @return \Generator<int, string>
     */
    private function eachItemCode(array $itemFilter, ?int $limit): \Generator
    {
        if ($itemFilter !== []) {
            if ($limit !== null) {
                $itemFilter = array_slice($itemFilter, 0, $limit);
            }

            foreach ($itemFilter as $code) {
                yield $code;
            }

            return;
        }

        $last = '';
        $yielded = 0;
        $chunkSize = 100;

        while (true) {
            $parents = ItemImages::query()
                ->select('parent')
                ->when($last !== '', fn ($query) => $query->where('parent', '>', $last))
                ->groupBy('parent')
                ->orderBy('parent')
                ->limit($chunkSize)
                ->pluck('parent');

            if ($parents->isEmpty()) {
                break;
            }

            foreach ($parents as $parent) {
                yield $parent;
                $yielded++;
                $last = $parent;

                if ($limit !== null && $yielded >= $limit) {
                    return;
                }
            }

            if ($parents->count() < $chunkSize) {
                break;
            }
        }
    }

    /**
     * @param  array<int, string>  $itemFilter
     */
    private function estimateCount(array $itemFilter, ?int $limit): int
    {
        if ($itemFilter !== []) {
            $count = count($itemFilter);

            return $limit !== null ? min($count, $limit) : $count;
        }

        $count = (int) ItemImages::query()->selectRaw('count(distinct parent) as c')->value('c');

        return $limit !== null ? min($count, $limit) : $count;
    }
}
