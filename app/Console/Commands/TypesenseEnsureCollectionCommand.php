<?php

namespace App\Console\Commands;

use App\Services\Typesense\TypesenseCollectionManager;
use Illuminate\Console\Command;

class TypesenseEnsureCollectionCommand extends Command
{
    protected $signature = 'typesense:ensure-collection';

    protected $description = 'Create the Typesense items collection if it does not exist';

    public function handle(TypesenseCollectionManager $manager): int
    {
        $manager->ensureCollection();
        $this->info('Typesense collection ['.$manager->collectionName().'] is ready.');

        return self::SUCCESS;
    }
}
