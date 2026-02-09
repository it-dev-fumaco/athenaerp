<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:pullout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to update consigned qty from pull out request that was submitted in ERP';

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
        $submittedPullouts = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabStock Entry as ste', 'ste.name', 'cste.references')
            ->where('cste.status', 'Pending')->whereIn('cste.purpose', ['Pull Out', 'Store Transfer'])
            ->where('ste.docstatus', 1)->select('cste.name', 'cste.source_warehouse')->get();

        $csteNames = collect($submittedPullouts)->pluck('name');
        $csteItems = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabConsignment Stock Entry Detail as csted', 'cste.name', 'csted.parent')
            ->whereIn('csted.parent', $csteNames)->get();

        $sourceWarehouses = collect($submittedPullouts)->pluck('source_warehouse');
        $itemCodes = collect($csteItems)->pluck('item_code');

        $bin = DB::table('tabBin')->whereIn('warehouse', $sourceWarehouses)
            ->whereIn('item_code', $itemCodes)->select('name', 'warehouse', 'item_code', 'consigned_qty', 'stock_uom')->get();

        $binArray = [];
        foreach($bin as $b){
            $binArray[$b->warehouse][$b->item_code] = [
                'consigned_qty' => $b->consigned_qty,
                'name' => $b->name
            ];
        }

        foreach($csteItems as $item) {
            if ($item->purpose == 'Pull Out') {
                $binName = isset($binArray[$item->source_warehouse][$item->item_code]) ? $binArray[$item->source_warehouse][$item->item_code]['name'] : null;
                if ($binName) {
                    $currentConsignedQty = isset($binArray[$item->source_warehouse][$item->item_code]) ? $binArray[$item->source_warehouse][$item->item_code]['consigned_qty'] : 0;
                    $consignedQtyAfterTransaction = $currentConsignedQty -  $item->qty;
                    $consignedQtyAfterTransaction = $consignedQtyAfterTransaction < 0 ? 0 : $consignedQtyAfterTransaction;
    
                    DB::table('tabBin')->where('name', $binName)->update(['consigned_qty' => $consignedQtyAfterTransaction]);
                }
            }
        }

        if (count($csteNames) > 0) {
            DB::table('tabConsignment Stock Entry')->whereIn('name', $csteNames)->update(['status' => 'Completed']);
        }

        return self::SUCCESS;
    }
}
