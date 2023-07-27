<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

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
        $submitted_pullouts = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabStock Entry as ste', 'ste.name', 'cste.references')
            ->where('cste.status', 'Pending')->where('cste.purpose', 'Pull Out')
            ->where('ste.docstatus', 1)->select('cste.name', 'cste.source_warehouse')->get();

        $cste_names = collect($submitted_pullouts)->pluck('name');
        $cste_items = DB::table('tabConsignment Stock Entry as cste')
            ->join('tabConsignment Stock Entry Detail as csted', 'cste.name', 'csted.parent')
            ->whereIn('csted.parent', $cste_names)->get();

        $source_warehouses = collect($submitted_pullouts)->pluck('source_warehouse');
        $item_codes = collect($cste_items)->pluck('item_code');

        $bin = DB::table('tabBin')->whereIn('warehouse', $source_warehouses)
            ->whereIn('item_code', $item_codes)->select('name', 'warehouse', 'item_code', 'consigned_qty', 'stock_uom')->get();

        $bin_array = [];
        foreach($bin as $b){
            $bin_array[$b->warehouse][$b->item_code] = [
                'consigned_qty' => $b->consigned_qty,
                'name' => $b->name
            ];
        }

        foreach($cste_items as $item) {
            $bin_name = isset($bin_array[$item->source_warehouse][$item->item_code]) ? $bin_array[$item->source_warehouse][$item->item_code]['name'] : null;
            if ($bin_name) {
                $current_consigned_qty = isset($bin_array[$item->source_warehouse][$item->item_code]) ? $bin_array[$item->source_warehouse][$item->item_code]['consigned_qty'] : 0;
                $consigned_qty_after_transaction = $current_consigned_qty -  $item->qty;
                $consigned_qty_after_transaction = $consigned_qty_after_transaction < 0 ? 0 : $consigned_qty_after_transaction;

                DB::table('tabBin')->where('name', $bin_name)->update(['consigned_qty' => $consigned_qty_after_transaction]);
            }
        }

        if (count($cste_names) > 0) {
            DB::table('tabConsignment Stock Entry')->whereIn('name', $cste_names)->update(['status' => 'Completed']);
        }
    }
}
