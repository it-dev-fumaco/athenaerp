<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Auth;
use DB;

class ConsignmentController extends Controller
{
    public function viewCalendarMenu($branch){
        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        if ($sales_report_deadline) {
            $currentDate = Carbon::now();

            $cutoff_1 = $sales_report_deadline->{'1st_cutoff_date'};
            $cutoff_2 = $sales_report_deadline->{'2nd_cutoff_date'};

            $currentMonth = $currentDate->format('m');
            $currentYear = $currentDate->format('Y');

            $first_cutoff = Carbon::createFromFormat('m/d/Y', $currentMonth .'/'. $cutoff_1 .'/'. $currentYear)->format('Y-m-d');
            $second_cutoff = Carbon::createFromFormat('m/d/Y', $currentMonth .'/'. $cutoff_2 .'/'. $currentYear)->format('Y-m-d');

            $due_alert = 0;
            if ($first_cutoff > $currentDate->format('Y-m-d')) {
                $date_difference_in_days = Carbon::parse($first_cutoff)->diffInDays($currentDate->format('Y-m-d'));
                if ($date_difference_in_days <= 1) {
                    $due_alert = 1;
                }
            }

            if ($second_cutoff > $currentDate->format('Y-m-d')) {
                $date_difference_in_days = Carbon::parse($second_cutoff)->diffInDays($currentDate->format('Y-m-d'));
                if ($date_difference_in_days <= 1) {
                    $due_alert = 1;
                }
            }
        }

        return view('consignment.calendar_menu', compact('branch', 'due_alert'));
    }

    public function salesReportDeadline(Request $request) {        
        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        if ($sales_report_deadline) {
            $cutoff_1 = $sales_report_deadline->{'1st_cutoff_date'};
            $cutoff_2 = $sales_report_deadline->{'2nd_cutoff_date'};

            $calendarMonth = $request->month;
            $calendarYear = $request->year;

            $first_cutoff = Carbon::createFromFormat('m/d/Y', $calendarMonth .'/'. $cutoff_1 .'/'. $calendarYear)->format('F d, Y');
            $second_cutoff = Carbon::createFromFormat('m/d/Y', $calendarMonth .'/'. $cutoff_2 .'/'. $calendarYear)->format('F d, Y');

            return 'Deadline: ' . $first_cutoff . ' & ' . $second_cutoff;
        }
    }

    public function checkBeginningInventory(Request $request) {
        // count beginnning inventory based on selected date and branch warehouse
        $existing_inventory = DB::table('tabConsignment Beginning Inventory')
            ->where('branch_warehouse', $request->branch_warehouse)
            ->whereDate('transaction_date', '<=', Carbon::parse($request->date))
            ->where('status', 'Approved')->exists();

        if (!$existing_inventory) {
            return response()->json(['status' => 0, 'message' => 'No beginning inventory entry found.']);
        }

        return response()->json(['status' => 1, 'message' => 'Beginning inventory found.']);
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

            $transactionDate = Carbon::parse($data['transaction_date']);

            $start_date = Carbon::parse($data['transaction_date'])->subMonth();
            $end_date = Carbon::parse($data['transaction_date'])->addMonth();

            $period = CarbonPeriod::create($start_date, '1 month' , $end_date);

            $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

            $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;
            $cutoff_2 = $sales_report_deadline ? $sales_report_deadline->{'2nd_cutoff_date'} : 0;

            $transaction_date = $transactionDate->format('d-m-Y');
            
            $cutoff_period = [];
            foreach ($period as $date) {
                $date1 = $date->day($cutoff_1);
                if ($date1 >= $start_date && $date1 <= $end_date) {
                    $cutoff_period[] = $date->format('d-m-Y');
                }
                $date2 = $date->day($cutoff_2);
                if ($date2 >= $start_date && $date2 <= $end_date) {
                    $cutoff_period[] = $date->format('d-m-Y');
                }
            }

            $cutoff_period[] = $transaction_date;
            // sort array with given user-defined function
            usort($cutoff_period, function ($time1, $time2) {
                return strtotime($time1) - strtotime($time2);
            });

            $transaction_date_index = array_search($transaction_date, $cutoff_period);
            // set cutoff date
            $cutoff_date = Carbon::parse($cutoff_period[$transaction_date_index + 1])->format('Y-m-d');
            
            $currentDateTime = Carbon::now();
            $result = [];
            $no_of_items_updated = 0;

            $status = 'On Time';
            if ($currentDateTime->gt($cutoff_date)) {
                $status = 'Late';
            }

            $item_prices = DB::table('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->where('cb.status', 'Approved')
                ->whereIn('cbi.item_code', array_keys($data['item']))
                ->where('cb.branch_warehouse', $data['branch_warehouse'])
                ->select('cb.transaction_date', 'cbi.item_code', 'cbi.price')
                ->orderBy('cb.transaction_date', 'desc')->get();

            $item_prices = collect($item_prices)->groupBy('item_code')->toArray();
            foreach ($data['item'] as $item_code => $row) {
                $existing = DB::table('tabConsignment Product Sold')
                    ->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])
                    ->where('transaction_date', $data['transaction_date'])->first();
                if ($existing) {
                    // for update
                    $values = [
                        'modified' => $currentDateTime->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'qty' => $row['qty'],
                    ];

                    $no_of_items_updated++;

                    DB::table('tabConsignment Product Sold')->where('name', $existing->name)->update($values);
                } else {
                    // for insert
                    $price = array_key_exists($item_code, $item_prices) ? $item_prices[$item_code][0]->price : 0;
                    $no_of_items_updated++;
                    $result[] = [
                        'name' => uniqid(),
                        'creation' => $currentDateTime->toDateTimeString(),
                        'modified' => $currentDateTime->toDateTimeString(),
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
                        'promodiser' => Auth::user()->full_name,
                        'price' => (float)$price,
                        'status' => $status,
                        'amount' => ((float)$price * (float)$row['qty']),
                        'cutoff_date' => $cutoff_date
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

    public function calendarData($branch, Request $request) {
        $start = $request->start;
        $end = $request->end;
        $query = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->whereBetween('transaction_date', [$start, $end])
            ->select('transaction_date', DB::raw('GROUP_CONCAT(DISTINCT status) as status'))->groupBy('transaction_date')->get();

        $data = [];
        foreach ($query as $row) {
            $status = explode(',', strtolower($row->status));

            $color = '#28a745';
            if (in_array('late', $status)) {
                $color = '#dc3545';
            }
            // $color = $
            $data[] = [
                'title' => '',
                'start' => $row->transaction_date,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true,
                'display' => 'background'
            ];
        }

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        if ($sales_report_deadline) {
            $start_date = Carbon::parse($start);
            $end_date = Carbon::parse($end);
    
            $period = CarbonPeriod::create($start_date, '1 month' , $end_date);
           
            $cutoff_1 = $sales_report_deadline->{'1st_cutoff_date'};
            $cutoff_2 = $sales_report_deadline->{'2nd_cutoff_date'};
    
            $cutoff_period = [];
            foreach ($period as $date) {
                $date1 = $date->day($cutoff_1);
                if ($date1 >= $start_date && $date1 <= $end_date) {
                    $cutoff_period[] = $date->format('Y-m-d');
                }
                $date2 = $date->day($cutoff_2);
                if ($date2 >= $start_date && $date2 <= $end_date) {
                    $cutoff_period[] = $date->format('Y-m-d');
                }
            }
            // set duration from and duration to
            $duration_from = $cutoff_period[0];
            $duration_to = $cutoff_period[1];
    
            $data[] = [
                'title' => 'Cutoff',
                'start' => $duration_from,
                'backgroundColor' => '#2874a6',
                'borderColor' => '#2874a6',
                'allDay' => false,
            ];
    
            $data[] = [
                'title' => 'Cutoff',
                'start' => $duration_to,
                'backgroundColor' => '#2874a6',
                'borderColor' => '#2874a6',
                'allDay' => false,
            ];
        }
    
        return $data;
    }

    public function beginningInventoryApproval(Request $request){
        if(Auth::user()->user_group != 'Consignment Supervisor'){
            return redirect('/');
        }

        $from_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[0])->startOfDay() : null;
        $to_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[1])->endOfDay() : null;

        $status = $request->status ? $request->status : 'For Approval';
        
        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')
            ->when($request->search, function ($q) use ($request){
                return $q->where('name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('owner', 'LIKE', '%'.$request->search.'%');
            })
            ->when($request->date, function ($q) use ($from_date, $to_date){
                return $q->whereDate('transaction_date', '>=', $from_date)->whereDate('transaction_date', '<=', $to_date);
            })
            ->when($request->store, function ($q) use ($request){
                return $q->where('branch_warehouse', $request->store);
            })
            ->where('status', $status)
            ->orderBy('creation', 'desc')
            ->paginate(10);

        $ids = collect($beginning_inventory->items())->map(function($q){
            return $q->name;
        });

        $beginning_inv_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $ids)->get();
        $beginning_inventory_items = collect($beginning_inv_items)->groupBy('parent');

        $item_codes = collect($beginning_inv_items)->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $inv_arr = [];
        foreach($beginning_inventory as $inv){
            $items_arr = [];
            if(isset($beginning_inventory_items[$inv->name])){
                foreach($beginning_inventory_items[$inv->name] as $item){
                    $items_arr[] = [
                        'parent' => $item->parent,
                        'inv_name' => $inv->name,
                        'image' => isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : null,
                        'item_code' => $item->item_code,
                        'item_description' => $item->item_description,
                        'uom' => $item->stock_uom,
                        'opening_stock' => $item->opening_stock * 1,
                        'price' => $item->price * 1
                    ];
                }
            }

            $inv_arr[] = [
                'name' => $inv->name,
                'branch' => $inv->branch_warehouse,
                'owner' => $inv->owner,
                'creation' => Carbon::parse($inv->creation)->format('F d, Y'),
                'status' => $inv->status,
                'transaction_date' => Carbon::parse($inv->transaction_date)->format('F d, Y'),
                'items' => $items_arr
            ];
        }

        $consignment_stores = DB::table('tabAssigned Consignment Warehouse')->pluck('warehouse');
        $consignment_stores = collect($consignment_stores)->unique();

        return view('consignment.beginning_inventory_list', compact('consignment_stores', 'inv_arr', 'beginning_inventory'));
    }

    public function approveBeginningInventory(Request $request, $id){
        DB::beginTransaction();
        try {
            $branch = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->pluck('branch_warehouse')->first();

            if(!$branch){
                return redirect()->back()->with('error', 'Inventory record not found.');
            }

            $now = Carbon::now()->toDateTimeString();

            $update_values = [
                'status' => $request->status,
                'modified_by' => Auth::user()->wh_user,
                'modified' => $now
            ];

            if($request->status == 'Approved'){
                $items = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();

                foreach($items as $item){
                    if($item->status != 'For Approval'){ // Skip the approved/cancelled items
                        continue;
                    }
    
                    DB::table('tabBin')->where('item_code', $item->item_code)->where('warehouse', $branch)->update([
                        'consigned_qty' => $item->opening_stock,
                        'modified' => $now,
                        'modified_by' => Auth::user()->wh_user
                    ]);
    
                    // update each item, allows checking if item for this branch is approved/cancelled
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->where('item_code', $item->item_code)->update($update_values);
                }
            }else{
                // update item status' to cancelled
                DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->update($update_values);
            }
            
            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update($update_values);

            DB::commit();
            return redirect()->back()->with('success', 'Beginning Inventory for '.$branch.' was '.$request->status.'.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function promodiserDeliveryReport(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $delivery_report = DB::table('tabStock Entry')->where('transfer_as', 'Consignment')->where('purpose', 'Material Transfer')->whereIn('item_status', ['For Checking', 'Issued'])->whereIn('to_warehouse', $assigned_consignment_store)->orderBy('creation', 'desc')->paginate(10);
        $reference_ste = collect($delivery_report->items())->map(function ($q){
            return $q->name;
        });
        
        $ste_items = DB::table('tabStock Entry Detail')->whereIn('parent', $reference_ste)->get();

        $item_codes = collect($ste_items)->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $ste_items = collect($ste_items)->groupBy('parent');

        $ste_arr = [];
        foreach($delivery_report as $ste){
            if(!isset($ste_items[$ste->name])){ // remove ste's without ste detail
                continue;
            }

            $items_arr = [];
            foreach($ste_items[$ste->name] as $item){
                $items_arr[] = [
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'image' => isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : null,
                    'delivered_qty' => $item->transfer_qty,
                    'stock_uom' => $item->stock_uom,
                    'price' => $item->basic_rate,
                    'delivery_status' => $item->consignment_status
                ];
            }

            $status_check = collect($items_arr)->map(function($q){
                return $q['delivery_status'] ? 1 : 0; // return 1 if status is Received
            })->toArray();

            $ste_arr[] = [
                'name' => $ste->name,
                'from' => $ste->from_warehouse,
                'to_consignment' => $ste->to_warehouse,
                'status' => $ste->item_status,
                'items' => $items_arr,
                'creation' => $ste->creation,
                'delivery_date' => $ste->delivery_date,
                'delivery_status' => min($status_check) == 0 ? 0 : 1 // check if there are still items to receive
            ];
        }

        return view('consignment.promodiser_delivery_report', compact('delivery_report', 'ste_arr'));
    }

    public function promodiserReceiveDelivery($id){
        DB::beginTransaction();
        try {
            $branch = DB::table('tabStock Entry')->where('name', $id)->pluck('to_warehouse')->first();

            $ste_items = DB::table('tabStock Entry Detail')->where('parent', $id)->get();
            
            $item_codes = collect($ste_items)->map(function ($q){
                return $q->item_code;
            });

            $bin = DB::table('tabBin')->where('warehouse', $branch)->whereIn('item_code', $item_codes)->get();
            $bin_items = collect($bin)->groupBy('item_code');

            foreach($ste_items as $item){
                if($item->consignment_status == 'Received'){ // skip already received items
                    continue;
                }

                if(!isset($bin_items[$item->item_code])){ // skip if item does not exist in bin
                    continue;
                }

                $consigned_qty = $bin_items[$item->item_code][0]->consigned_qty;

                DB::table('tabBin')->where('warehouse', $branch)->where('item_code', $item->item_code)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $consigned_qty + $item->transfer_qty
                ]);

                DB::table('tabStock Entry Detail')->where('name', $item->name)->update([
                    'consignment_status' => 'Received',
                    'consignment_date_received' => Carbon::now()->toDateTimeString(),
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Items received');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'An error occured. Please try again later');
        }
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

    public function beginningInventory($inv = null){
        $inv_record = [];
        if($inv){
            $inv_record = DB::table('tabConsignment Beginning Inventory')->where('name', $inv)->where('status', 'For Approval')->first();

            if(!$inv_record){
                Abort(404);
            }
        }

        $branch = $inv_record ? $inv_record->branch_warehouse : null;
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.beginning_inventory', compact('assigned_consignment_store',  'inv', 'branch', 'inv_record'));
    }

    public function beginningInvItems(Request $request, $action, $branch){
        if($request->ajax()){
            $inv_record = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch)->where('status', 'For Approval')->first();

            $items = [];
            $inv_name = null;
            if($action == 'update'){ // If 'For Approval' beginning inventory record exists for this branch
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
            }else{ // Create new beginning inventory entry
                // get approved and for approval records
                $inv_records = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch)->whereIn('status', ['For Approval', 'Approved'])->pluck('name');
                $inv_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $inv_records)->pluck('item_code');

                // Get items from Bin
                $bin_items = DB::table('tabBin as bin')->join('tabItem as item', 'bin.item_code', 'item.name')
                    ->where('bin.warehouse', $branch)->where('actual_qty', '>', 0)->whereNotIn('bin.item_code', $inv_items) // do not include approved and for approval items
                    ->select('bin.warehouse', 'bin.item_code', 'bin.actual_qty', 'bin.stock_uom', 'item.description')->orderBy('bin.actual_qty', 'desc')
                    ->get();

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

            return view('consignment.beginning_inv_items', compact('items', 'branch', 'item_images', 'inv_name'));
        }
    }

    public function saveBeginningInventory(Request $request){
        DB::beginTransaction();
        try {
            $opening_stock = $request->opening_stock;
            $price = $request->price;
            $item_codes = $request->item_code;
            $item_codes = collect(array_filter($item_codes))->unique(); // remove null values
            $branch = $request->branch;

            if(max($opening_stock) <= 0 || max($price) <= 0){ // If all values of opening stocks or prices are 0
                return redirect()->back()->with('error', 'Please input values to '.(max($opening_stock) <= 0 ? 'Opening Stock' : 'Price'));
            }

            $now = Carbon::now()->toDateTimeString();
    
            $items = DB::table('tabItem')->whereIn('name', $item_codes)->select('name', 'description', 'stock_uom')->get();
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
                    'owner' => Auth::user()->full_name,
                ];
                
                DB::table('tabConsignment Beginning Inventory')->insert($values);

                $row_values = [];
                foreach($item_codes as $i => $item_code){
                    if(!$item_code || isset($opening_stock[$item_code]) && $opening_stock[$item_code] == 0){ // Prevents saving removed items and items with 0 opening stock
                        continue;
                    }

                    if(isset($opening_stock[$item_code]) && $opening_stock[$item_code] < 0 || isset($price[$item_code]) && $price[$item_code] < 0){
                        return redirect()->back()->with('error', 'Cannot enter value below 0');
                    }
    
                    $row_values = [
                        'name' => uniqid(),
                        'creation' => $now,
                        'owner' => Auth::user()->full_name,
                        'docstatus' => 0,
                        'parent' => $inv_id,
                        'idx' => 1,
                        'item_code' => $item_code,
                        'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->description : null,
                        'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                        'opening_stock' => isset($opening_stock[$item_code]) ? preg_replace("/[^0-9]/", "", $opening_stock[$item_code]) : 0,
                        'stocks_displayed' => 0,
                        'status' => 'For Approval',
                        'price' => isset($price[$item_code]) ? preg_replace("/[^0-9]/", "", $price[$item_code]) : 0
                    ];

                    $item_count = $item_count + 1;
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
                        'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->description : null,
                        'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                        'opening_stock' => isset($opening_stock[$item_code]) ? preg_replace("/[^0-9]/", "", $opening_stock[$item_code]) : 0,
                        'price' => isset($price[$item_code]) ? preg_replace("/[^0-9]/", "", $price[$item_code]) : 0,
                    ];

                    $item_count = $item_count + 1;
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