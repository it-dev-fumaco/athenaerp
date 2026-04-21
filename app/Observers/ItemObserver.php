<?php

namespace App\Observers;

use App\Jobs\SyncItemToTypesense;
use App\Models\Item;

class ItemObserver
{
    public function saved(Item $item): void
    {
        if (! config('typesense.enabled')) {
            return;
        }

        SyncItemToTypesense::dispatch($item->name);
    }

    public function deleted(Item $item): void
    {
        if (! config('typesense.enabled')) {
            return;
        }

        SyncItemToTypesense::dispatch($item->name, delete: true);
    }
}
