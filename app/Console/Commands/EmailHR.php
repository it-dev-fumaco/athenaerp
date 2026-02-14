<?php

namespace App\Console\Commands;

use App\Pipelines\EmailHRPipeline;
use Illuminate\Console\Command;

class EmailHR extends Command
{
    protected $signature = 'email:hr';

    protected $description = 'Email alert to hr';

    public function __construct(
        protected EmailHRPipeline $emailHRPipeline
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->emailHRPipeline->run((object) []);

        return self::SUCCESS;
    }
}
