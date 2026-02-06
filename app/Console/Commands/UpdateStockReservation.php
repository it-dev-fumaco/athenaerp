<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateStockReservation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:stock_reservation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Stock Reservation Status every minute';

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
        try {
            DB::table('tabStock Reservation')->whereIn('status', ['Active', 'Partially Issued'])
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->where('valid_until', '<', Carbon::now())->update(['status' => 'Expired']);
            // update status partially issued
            DB::table('tabStock Reservation')
                ->whereNotIn('status', ['Cancelled', 'Issued', 'Expired'])
                ->where('consumed_qty', '>', 0)->whereRaw('consumed_qty < reserve_qty')
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->update(['status' => 'Partially Issued']);
            // update status issued
            DB::table('tabStock Reservation')->whereNotIn('status', ['Cancelled', 'Expired', 'Issued'])
                ->where('consumed_qty', '>', 0)->whereRaw('consumed_qty >= reserve_qty')
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->update(['status' => 'Issued']);
        } catch (\Throwable $th) {
            // throw $th;
            info("an error occured while updating stock reservation");
        }
        
        return self::SUCCESS;
    }
}
