<?php

namespace App\Console\Commands;

use App\Traits\GeneralTrait;
use Illuminate\Console\Command;

class UpdateSteStatus extends Command
{
    use GeneralTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ste_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $result = $this->updateSteStatus();
        return $result ? self::SUCCESS : self::FAILURE;
    }
}
