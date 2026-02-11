<?php

namespace App\Console\Commands;

use App\Pipelines\UpdateStockReservationPipeline;
use Illuminate\Console\Command;

class UpdateStockReservation extends Command
{
    protected $signature = 'update:stock_reservation';

    protected $description = 'Update Stock Reservation Status every minute';

    public function __construct(
        protected UpdateStockReservationPipeline $updateStockReservationPipeline
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->updateStockReservationPipeline->run((object) []);
        } catch (\Throwable $th) {
            info('an error occured while updating stock reservation');
        }

        return self::SUCCESS;
    }
}
