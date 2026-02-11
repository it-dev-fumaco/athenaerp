<?php

namespace App\Console\Commands;

use App\Pipelines\UpdateStocksPipeline;
use Illuminate\Console\Command;

class UpdateStocks extends Command
{
    protected $signature = 'update:pullout';

    protected $description = 'Command to update consigned qty from pull out request that was submitted in ERP';

    public function __construct(
        protected UpdateStocksPipeline $updateStocksPipeline
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->updateStocksPipeline->run((object) []);

        return self::SUCCESS;
    }
}
