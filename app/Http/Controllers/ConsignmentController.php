<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use DB;

class ConsignmentController extends Controller
{
    public function viewCalendarMenu($branch){
        return view('consignment.calendar_menu', compact('branch'));
    }

    public function viewProductSoldForm($branch, $transaction_date) {
        $items = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->join('tabItem as i', 'i.name', 'cbi.item_code')
            ->where('cb.status', 'Approved')
            ->where('i.disabled', 0)->where('i.is_stock_item', 1)
            ->where('cb.branch_warehouse', $branch)->select('i.item_code', 'i.description')
            ->orderBy('i.description', 'asc')->get();

        $item_codes = collect($items)->pluck('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent')->toArray();

        $existing_record = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->where('transaction_date', $transaction_date)->pluck('qty', 'item_code')->toArray();

        return view('consignment.product_sold_form', compact('branch', 'transaction_date', 'items', 'item_images', 'existing_record'));
    }

    public function submitProductSoldForm(Request $request) {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $result = [];
            $no_of_items_updated = 0;
            foreach ($data['item'] as $item_code => $row) {
                $existing = DB::table('tabConsignment Product Sold')
                    ->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])
                    ->where('transaction_date', $data['transaction_date'])->first();
                if ($existing) {
                    // for update
                    $values = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'qty' => $row['qty'],
                    ];

                    $no_of_items_updated++;

                    DB::table('tabConsignment Product Sold')->where('name', $existing->name)->update($values);
                } else {
                    // for insert
                    $no_of_items_updated++;
                    $result[] = [
                        'name' => uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => null,
                        'parentfield' => null,
                        'parenttype' => null,
                        'idx' => 0,
                        'transaction_date' => $data['transaction_date'],
                        'branch_warehouse' => $data['branch_warehouse'],
                        'item_code' => $item_code,
                        'description' => $row['description'],
                        'qty' => $row['qty'],
                        'promodiser' => Auth::user()->full_name
                    ];
                }
            }

            if (count($result) > 0) {
                DB::table('tabConsignment Product Sold')->insert($result);
            }

            DB::commit();

            return redirect('/product_sold_success')->with([
                'success' => 'Record successfully updated',
                'no_of_items_updated' => $no_of_items_updated,
                'branch' => $data['branch_warehouse'],
                'transaction_date' => $data['transaction_date']
            ]);
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function productSoldSuccess() {
        return view('consignment.success_page');
    }

    public function calendarData($branch) {
        $query = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->select('transaction_date')->groupBy('transaction_date')->get();

        $data = [];
        foreach ($query as $row) {
            $data[] = [
                'title' => '',
                'start' => $row->transaction_date,
                'backgroundColor' => '#00a65a', //green
                'borderColor' => '#00a65a', //green
                'allDay' => true,
                'display' => 'background'
            ];
        }

        return $data;
    }

    public function beginningInventoryList(Request $request){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->whereIn('branch_warehouse', $assigned_consignment_store)->get();

        return view('consignment.beginning_inv_list', compact('beginning_inventory'));
    }

    public function beginningInvItemsList($id){
        $branch = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->pluck('branch_warehouse')->first();
        $inventory = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();

        return view('consignment.beginning_inv_items_list', compact('inventory', 'branch'));
    }

    public function beginningInventory(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $recorded_stores = DB::table('tabConsignment Beginning Inventory')->whereIn('branch_warehouse', $assigned_consignment_store)->select('name', 'branch_warehouse', 'transaction_date')->get();

        $recorded_stores = collect($recorded_stores)->groupBy('branch_warehouse');

        $null_store = isset($assigned_consignment_store[0]) ? $assigned_consignment_store[0] : null;
        if($recorded_stores){
            foreach($assigned_consignment_store as $store){ // Get the first store without beginning inventory record
                if(!isset($recorded_stores[$store])){
                    $null_store = $store;
                    break;
                }
            }
        }

        return view('consignment.beginning_inventory', compact('assigned_consignment_store', 'null_store'));
    }

    public function beginningInvItems($branch){
        $inv_record = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch)->first();

        $items = [];
        $inv_name = null;
        if($inv_record){
            $inv_name = $inv_record->name; 
            $inventory = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $inv_name)->select('item_code', 'item_description', 'stock_uom', 'opening_stock', 'stocks_displayed', 'price')->get();

            foreach($inventory as $inv){
                $items[] = [
                    'item_code' => $inv->item_code,
                    'item_description' => $inv->item_description,
                    'stock_uom' => $inv->stock_uom,
                    'opening_stock' => $inv->opening_stock * 1,
                    'stocks_displayed' => $inv->stocks_displayed * 1,
                    'price' => $inv->price * 1
                ];
            }
        }else{
            $bin_items = DB::table('tabBin as bin')->join('tabItem as item', 'bin.item_code', 'item.name')->where('bin.warehouse', $branch)->select('bin.warehouse', 'bin.item_code', 'bin.actual_qty', 'bin.stock_uom', 'item.description')->orderBy('bin.actual_qty', 'desc')->get();

            foreach($bin_items as $item){
                $items[] = [
                    'item_code' => $item->item_code,
                    'item_description' => $item->description,
                    'stock_uom' => $item->stock_uom,
                    'opening_stock' => 0,
                    'stocks_displayed' => 0,
                    'price' => 0
                ];
            }
        }

        $item_codes = collect($items)->map(function($q){
            return $q['item_code'];
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent');

        return view('consignment.beginning_inv_items', compact('items', 'branch', 'inv_name', 'item_images'));
    }

    public function saveBeginningInventory(Request $request){
        DB::beginTransaction();
        try {
            $opening_stock = $request->opening_stock;
            $price = $request->price;
            $item_codes = $request->item_code;
            $branch = $request->branch;

            if(max($opening_stock) <= 0 || max($price) <= 0){ // If all values of opening stocks or prices are 0
                return redirect()->back()->with('error', 'Please input values to '.(max($opening_stock) <= 0 ? 'Opening Stock' : 'Price'));
            }

            $now = Carbon::now()->toDateTimeString();
    
            $items = DB::table('tabItem')->whereIn('name', $item_codes)->select('name', 'item_name', 'stock_uom')->get();
            $item = collect($items)->groupBy('name');

            $item_count = 0;
            if(!$request->inv_name){ // If beginning inventory record does not exist
                $latest_inv = DB::table('tabConsignment Beginning Inventory')->where('name', 'like', '%inv%')->max('name');
                $latest_inv_exploded = explode("-", $latest_inv);
                $inv_id = (($latest_inv) ? $latest_inv_exploded[1] : 0) + 1;
                $inv_id = str_pad($inv_id, 6, '0', STR_PAD_LEFT);
                $inv_id = 'INV-'.$inv_id;
    
                $values = [
                    'docstatus' => 0,
                    'name' => $inv_id,
                    'idx' => 1,
                    'status' => 'For Approval',
                    'branch_warehouse' => $branch,
                    'creation' => $now,
                    'transaction_date' => $now,
                    'owner' => Auth::user()->wh_user,
                ];
                
                DB::table('tabConsignment Beginning Inventory')->insert($values);

                $row_values = [];
                foreach($item_codes as $item_code){
                    if(!$item_code || isset($opening_stock[$item_code]) && $opening_stock[$item_code] == 0){ // Prevents saving removed items and items with 0 opening stock
                        continue;
                    }

                    if(isset($opening_stock[$item_code]) && $opening_stock[$item_code] < 0 || isset($price[$item_code]) && $price[$item_code] < 0){
                        return redirect()->back()->with('error', 'Cannot enter value below 0');
                    }
    
                    $row_values = [
                        'name' => uniqid(),
                        'creation' => $now,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => $inv_id,
                        'idx' => 1,
                        'item_code' => $item_code,
                        'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->item_name : null,
                        'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                        'opening_stock' => isset($opening_stock[$item_code]) ? $opening_stock[$item_code] : 0,
                        'stocks_displayed' => 0,
                        'status' => 'For Approval',
                        'price' => isset($price[$item_code]) ? $price[$item_code] : 0
                    ];

                    $item_count = $i + 1;
                    DB::table('tabConsignment Beginning Inventory Item')->insert($row_values);
                }
            }else{
                DB::table('tabConsignment Beginning Inventory')->where('name', $request->inv_name)->update([
                    'modified' => $now,
                    'modified_by' => Auth::user()->wh_user
                ]);

                foreach($item_codes as $i => $item_code){
                    if(!$item_code || isset($opening_stock[$item_code]) && $opening_stock[$item_code] == 0){ // Prevents saving removed items and items with 0 opening stock
                        continue;
                    }

                    if(isset($opening_stock[$item_code]) && $opening_stock[$item_code] < 0 || isset($price[$item_code]) && $price[$item_code] < 0){
                        return redirect()->back()->with('error', 'Cannot enter value below 0');
                    }
                    
                    $values = [
                        'modified' => $now,
                        'modified_by' => Auth::user()->wh_user,
                        'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->item_name : null,
                        'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                        'opening_stock' => isset($opening_stock[$item_code]) ? $opening_stock[$item_code] : 0,
                        'price' => isset($price[$item_code]) ? $price[$item_code] : 0,
                    ];

                    $item_count = $i + 1;
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $request->inv_name)->where('item_code', $item_code)->update($values);
                }
            }

            session()->flash('success', 'Inventory Record Saved');
            DB::commit();
            return view('consignment.beginning_inv_success', compact('item_count', 'branch'));
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }
}