<?php

namespace App\Console\Commands;

use App\Pipelines\EmailStockReservationExpiryPipeline;
use Illuminate\Console\Command;

class EmailStockReservationExpiry extends Command
{
    protected $signature = 'email:stock_reservation_expiry';

    protected $description = 'Email alert for stock reservations near expiry (1 and 2 days prior)';

    public function __construct(
        protected EmailStockReservationExpiryPipeline $emailStockReservationExpiryPipeline
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->emailStockReservationExpiryPipeline->run((object) []);

        return self::SUCCESS;
    }
}
