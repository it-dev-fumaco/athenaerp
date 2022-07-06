<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Auth;
use DB;
use Storage;

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
            return response()->json(['status' => 0, 'message' => 'No beginning inventory entry found on <br>'. Carbon::parse($request->date)->format('F d, Y')]);
        }

        return response()->json(['status' => 1, 'message' => 'Beginning inventory found.']);
    }

    public function viewInventoryAuditForm($branch, $transaction_date) {
        // get last inventory audit date
        $last_inventory_date = DB::table('tabConsignment Inventory Audit')
            ->where('status', 'Approved')->where('branch_warehouse', $branch)->max('transaction_date');

        if (!$last_inventory_date) {
            // get beginning inventory date if last inventory date is not null
            $last_inventory_date = DB::table('tabConsignment Beginning Inventory')
                ->where('status', 'Approved')->where('branch_warehouse', $branch)->max('transaction_date');
        }

        $inventory_audit_from = $last_inventory_date;
        $inventory_audit_to = $transaction_date;

        $duration = Carbon::parse($inventory_audit_from)->addDay()->format('F d, Y') . ' - ' . Carbon::parse($inventory_audit_to)->format('F d, Y');

        $start = Carbon::parse($inventory_audit_from)->format('Y-m-d');
        $end = Carbon::parse($inventory_audit_to)->format('Y-m-d');

        $items = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->join('tabItem as i', 'i.name', 'cbi.item_code')
            ->where('cb.status', 'Approved')
            ->where('i.disabled', 0)->where('i.is_stock_item', 1)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($transaction_date))
            ->where('cb.branch_warehouse', $branch)->select('i.item_code', 'i.description')
            ->orderBy('i.description', 'asc')->get();
            
        $item_codes = collect($items)->pluck('item_code');
        
        $consigned_stocks = DB::table('tabBin')->whereIn('item_code', $item_codes)->where('warehouse', $branch)->pluck('consigned_qty', 'item_code')->toArray();

        $item_total_sold = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->where('status', '!=', 'Cancelled')
            ->whereBetween('transaction_date', [$start, $end])->selectRaw('SUM(qty) as sold_qty, item_code')
            ->groupBy('item_code')->pluck('sold_qty', 'item_code')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent')->toArray();

        return view('consignment.inventory_audit_form', compact('branch', 'transaction_date', 'items', 'item_images', 'item_total_sold', 'duration', 'inventory_audit_from', 'inventory_audit_to', 'consigned_stocks'));
    }

    public function consignmentStores(Request $request) {
        if ($request->ajax()) {
            if($request->has('assigned_to_me') && $request->assigned_to_me == 1){ // only get warehouses assigned to the promodiser
                return DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->where('warehouse', 'LIKE', '%'.$request->q.'%')->select('warehouse as id', 'warehouse as text')->orderBy('warehouse', 'asc')->get();
            }else{ // get all warehouses
                return DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
                    ->where('is_group', 0)->where('disabled', 0)->where('name','LIKE', '%'.$request->q.'%')
                    ->select('name as id', 'warehouse_name as text')->orderBy('warehouse_name', 'asc')->get();
            }
        }
    }

    public function submitInventoryAuditForm(Request $request) {
        $data = $request->all();
        DB::beginTransaction();
        try {
            $cutoff_date = $this->getCutoffDate($data['transaction_date']);
            $period_from = $cutoff_date[0];
            $period_to = $cutoff_date[1];
            
            $currentDateTime = Carbon::now();
            $result = $result_2 = [];
            $no_of_items_updated = 0;

            $status = 'On Time';
            if ($currentDateTime->gt($period_to)) {
                $status = 'Late';
            }

            $period_from = Carbon::parse($cutoff_date[0])->format('Y-m-d');
            $period_to = Carbon::parse($cutoff_date[1])->format('Y-m-d');

            $consigned_stocks = DB::table('tabBin')->whereIn('item_code', array_keys($data['item']))
                ->where('warehouse', $data['branch_warehouse'])->pluck('consigned_qty', 'item_code')->toArray();

            $item_prices = DB::table('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->where('cb.status', 'Approved')
                ->whereIn('cbi.item_code', array_keys($data['item']))
                ->where('cb.branch_warehouse', $data['branch_warehouse'])
                ->select('cb.transaction_date', 'cbi.item_code', 'cbi.price')
                ->orderBy('cb.transaction_date', 'desc')->get();

            $item_prices = collect($item_prices)->groupBy('item_code')->toArray();

            foreach ($data['item'] as $item_code => $row) {
                $consigned_qty = array_key_exists($item_code, $consigned_stocks) ? $consigned_stocks[$item_code] : 0;
                $existing = DB::table('tabConsignment Inventory Audit')
                    ->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])
                    ->where('transaction_date', $data['transaction_date'])->first();

                if ($existing) {
                    $no_of_items_updated++;
                    // $consigned_qty = $consigned_qty;// + $existing->qty;
                    $sold_qty = $consigned_qty - (float)$row['qty'];

                    if ($consigned_qty < (float)$row['qty']) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Audit qty is greater than actual qty for <b>' . $item_code . '</b>.<br>Please request for stock adjustment.');
                    }

                    DB::table('tabBin')->where('item_code', $item_code)->where('warehouse', $data['branch_warehouse'])
                        ->update(['consigned_qty' => (float)$row['qty']]);

                    $checker = DB::table('tabConsignment Product Sold')->where('status', '!=', 'Cancelled')->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])->where('transaction_date', $data['transaction_date'])->first();

                    // for update
                    if($checker){
                        $values = [
                            'modified' => $currentDateTime->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'qty' => $sold_qty + $checker->qty,
                        ];
    
                        DB::table('tabConsignment Product Sold')->where('name', $checker->name)->update($values);
                    }else{
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
                            'qty' => $sold_qty,
                            'promodiser' => Auth::user()->full_name,
                            'price' => (float)$price,
                            'status' => $status,
                            'amount' => ((float)$price * (float)$sold_qty),
                            'cutoff_period_from' => $period_from,
                            'cutoff_period_to' => $period_to,
                            'available_stock_on_transaction' => $consigned_qty
                        ];
                    }
                    
                    // for update
                    $values = [
                        'modified' => $currentDateTime->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'qty' => (float)$row['qty'],
                    ];

                    DB::table('tabConsignment Inventory Audit')->where('name', $existing->name)->update($values);
                } else {
                    // for insert
                    $price = array_key_exists($item_code, $item_prices) ? $item_prices[$item_code][0]->price : 0;
                    $sold_qty = $consigned_qty - (float)$row['qty'];

                    if ($consigned_qty < (float)$row['qty']) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Audit qty is greater than actual qty for <b>' . $item_code . '</b>.<br>Please request for stock adjustment.');
                    }

                    DB::table('tabBin')->where('item_code', $item_code)->where('warehouse', $data['branch_warehouse'])
                        ->update(['consigned_qty' => (float)$row['qty']]);

                    $no_of_items_updated++;
       
                    $result_2[] = [
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
                        'qty' => (float)$row['qty'],
                        'promodiser' => Auth::user()->full_name,
                        'price' => (float)$price,
                        'status' => $status,
                        'amount' => ((float)$price * (float)$row['qty']),
                        'cutoff_period_from' => $period_from,
                        'cutoff_period_to' => $period_to,
                        'available_stock_on_transaction' => $consigned_qty,
                        'audit_date_from' => $data['audit_date_from'],
                        'audit_date_to' => $data['audit_date_to'],
                    ];
                    
                    $checker = DB::table('tabConsignment Product Sold')->where('status', '!=', 'Cancelled')->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])->where('transaction_date', $data['transaction_date'])->first();

                    if($checker){
                        $values = [
                            'modified' => $currentDateTime->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'qty' => $sold_qty + $checker->qty,
                        ];
    
                        DB::table('tabConsignment Product Sold')->where('name', $checker->name)->update($values);
                    }else{
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
                            'qty' => $sold_qty,
                            'promodiser' => Auth::user()->full_name,
                            'price' => (float)$price,
                            'status' => $status,
                            'amount' => ((float)$price * (float)$sold_qty),
                            'cutoff_period_from' => $period_from,
                            'cutoff_period_to' => $period_to,
                            'available_stock_on_transaction' => $consigned_qty
                        ];
                    }
                }
            }

            if (count($result) > 0) {
                DB::table('tabConsignment Product Sold')->insert($result);
            }

            // return $result;
            if (count($result_2) > 0) {
                DB::table('tabConsignment Inventory Audit')->insert($result_2);
            }

            DB::commit();

            return redirect()->back()->with([
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

    public function viewProductSoldForm($branch, $transaction_date) {
        $items = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->join('tabItem as i', 'i.name', 'cbi.item_code')
            ->where('cb.status', 'Approved')
            ->where('i.disabled', 0)->where('i.is_stock_item', 1)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($transaction_date))
            ->where('cb.branch_warehouse', $branch)
            ->select('i.item_code', 'i.description')
            ->orderBy('i.description', 'asc')->get();

        $item_codes = collect($items)->pluck('item_code');

        $consigned_stocks = DB::table('tabBin')->whereIn('item_code', $item_codes)->where('warehouse', $branch)->pluck('consigned_qty', 'item_code')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent')->toArray();

        $existing_record = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->where('status', '!=', 'Cancelled')
            ->where('transaction_date', $transaction_date)->pluck('qty', 'item_code')->toArray();

        return view('consignment.product_sold_form', compact('branch', 'transaction_date', 'items', 'item_images', 'existing_record', 'consigned_stocks'));
    }

    public function getCutoffDate($transaction_date) {
        $transactionDate = Carbon::parse($transaction_date);

        $start_date = Carbon::parse($transaction_date)->subMonth();
        $end_date = Carbon::parse($transaction_date)->addMonth();

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
        $period_from = Carbon::parse($cutoff_period[$transaction_date_index - 1])->startOfDay();
        $period_to = Carbon::parse($cutoff_period[$transaction_date_index + 1])->endOfDay();
        
        return [$period_from, $period_to];
    }

    public function submitProductSoldForm(Request $request) {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $cutoff_date = $this->getCutoffDate($data['transaction_date']);
            $period_from = $cutoff_date[0];
            $period_to = $cutoff_date[1];
            
            $currentDateTime = Carbon::now();
            $result = [];
            $no_of_items_updated = 0;

            $status = 'On Time';
            if ($currentDateTime->gt($period_to)) {
                $status = 'Late';
            }

            $period_from = Carbon::parse($cutoff_date[0])->format('Y-m-d');
            $period_to = Carbon::parse($cutoff_date[1])->format('Y-m-d');

            $item_prices = DB::table('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->where('cb.status', 'Approved')
                ->whereIn('cbi.item_code', array_keys($data['item']))
                ->where('cb.branch_warehouse', $data['branch_warehouse'])
                ->select('cb.transaction_date', 'cbi.item_code', 'cbi.price')
                ->orderBy('cb.transaction_date', 'desc')->get();

            $item_prices = collect($item_prices)->groupBy('item_code')->toArray();

            $consigned_stocks = DB::table('tabBin')->whereIn('item_code', array_keys($data['item']))
                ->where('warehouse', $data['branch_warehouse'])->pluck('consigned_qty', 'item_code')->toArray();

            foreach ($data['item'] as $item_code => $row) {
                $consigned_qty = array_key_exists($item_code, $consigned_stocks) ? $consigned_stocks[$item_code] : 0;
                $existing = DB::table('tabConsignment Product Sold')
                ->where('status', '!=', 'Cancelled')
                    ->where('item_code', $item_code)->where('branch_warehouse', $data['branch_warehouse'])
                    ->where('transaction_date', $data['transaction_date'])->first();
                if ($existing) {
                    $consigned_qty = $consigned_qty + $existing->qty;
                    $amount = $existing->price * $row['qty'];

                    if ($consigned_qty < (float)$row['qty']) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Insufficient stock for <b>' . $item_code . '</b>.<br>Available quantity is <b>' . number_format($consigned_qty) . '</b>.');
                    }

                    if ((float)$row['qty'] < 0) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Qty for <b>' . $item_code . '</b> cannot be less than 0.');
                    }

                    DB::table('tabBin')->where('item_code', $item_code)->where('warehouse', $data['branch_warehouse'])
                        ->update(['consigned_qty' => (float)$consigned_qty - (float)$row['qty']]);

                    // for update
                    $values = [
                        'modified' => $currentDateTime->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'qty' => $row['qty'],
                        'amount' => $amount
                    ];

                    $no_of_items_updated++;

                    DB::table('tabConsignment Product Sold')->where('name', $existing->name)->update($values);
                } else {
                    // for insert
                    $price = array_key_exists($item_code, $item_prices) ? $item_prices[$item_code][0]->price : 0;

                    if ((float)$row['qty'] < 0) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Qty for <b>' . $item_code . '</b> cannot be less than 0.');
                    }

                    if ($consigned_qty < (float)$row['qty']) {
                        return redirect()->back()
                            ->with(['old_data' => $data])
                            ->with('error', 'Insufficient stock for <b>' . $item_code . '</b>.<br>Available quantity is <b>' . number_format($consigned_qty) . '</b>.');
                    }

                    DB::table('tabBin')->where('item_code', $item_code)->where('warehouse', $data['branch_warehouse'])
                        ->update(['consigned_qty' => (float)$consigned_qty - (float)$row['qty']]);
                    
                    if ($row['qty'] > 0) {
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
                            'cutoff_period_from' => $period_from,
                            'cutoff_period_to' => $period_to,
                            'available_stock_on_transaction' => $consigned_qty
                        ];
                    }
                }
            }

            if (count($result) > 0) {
                DB::table('tabConsignment Product Sold')->insert($result);
            }

            DB::commit();

            return redirect()->back()->with([
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

    public function calendarData($branch, Request $request) {
        $start = $request->start;
        $end = $request->end;
        $query = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $branch)
            ->whereBetween('transaction_date', [$start, $end])->where('status', '!=', 'Cancelled')
            ->select('transaction_date', DB::raw('GROUP_CONCAT(DISTINCT status) as status'), DB::raw('SUM(amount) as grand_total'))
            ->groupBy('transaction_date')->get();

        $beginning_inventories = DB::table('tabConsignment Beginning Inventory')
            ->where('branch_warehouse', $branch)->where('status', 'Approved')
            ->distinct()->pluck('transaction_date');

        $data = [];
        foreach ($query as $row) {
            $status = explode(',', strtolower($row->status));

            $color = '#28a745';
            if (in_array('late', $status)) {
                $color = '#dc3545';
            }
            
            $data[] = [
                'title' => '',
                'start' => $row->transaction_date,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true,
                'display' => 'background'
            ];

            $data[] = [
                'title' => '₱ ' . number_format($row->grand_total, 2),
                'start' => $row->transaction_date,
                'backgroundColor' => '#808B96',
                'borderColor' => '#808B96',
                'allDay' => false,
            ];
        }

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        if ($sales_report_deadline) {
            $start_date = Carbon::parse($start);
            $end_date = Carbon::parse($end);

            $period = CarbonPeriod::create($start_date, '28 days' , $end_date);
           
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
                'backgroundColor' => '#a93226',
                'borderColor' => '#a93226',
                'allDay' => false,
            ];
    
            $data[] = [
                'title' => 'Cutoff',
                'start' => $duration_to,
                'backgroundColor' => '#a93226',
                'borderColor' => '#a93226',
                'allDay' => false,
            ];

            $data[] = [
                'title' => 'Inventory Audit',
                'start' => $duration_from,
                'backgroundColor' => '#34495e',
                'borderColor' => '#34495e',
                'allDay' => false,
            ];

            $data[] = [
                'title' => 'Inventory Audit',
                'start' => $duration_to,
                'backgroundColor' => '#34495e',
                'borderColor' => '#34495e',
                'allDay' => false,
            ];
        }

        foreach($beginning_inventories as $transaction_date) {
            $data[] = [
                'title' => 'Beginning Inventory',
                'start' => $transaction_date,
                'backgroundColor' => '#2874a6',
                'borderColor' => '#2874a6',
                'allDay' => false,
            ];
        }
    
        return $data;
    }

    public function salesReport(){
        $sales_report = DB::table('tabConsignment Product Sold')->where('status', '!=', 'Cancelled')->get();
    
        $warehouses_with_approved_inventory = DB::table('tabConsignment Beginning Inventory')->where('status', 'Approved')->pluck('branch_warehouse')->unique();
    
        $product_sold_arr = DB::table('tabConsignment Product Sold')->whereYear('transaction_date', Carbon::now()->format('Y'))
            ->where('status', '!=', 'Cancelled')->whereIn('branch_warehouse', $warehouses_with_approved_inventory)
            ->select('cutoff_period_from', 'branch_warehouse', 'promodiser', DB::raw('sum(qty) as qty'), DB::raw('sum(amount) as amount'))
            ->groupBy('cutoff_period_from', 'branch_warehouse', 'promodiser')
            ->get();
    
        $cutoff_periods = collect($product_sold_arr)->map(function ($q){
            return $q->cutoff_period_from;
        })->unique();
    
        $warehouses = collect($product_sold_arr)->map(function ($q){
            return $q->branch_warehouse;
        })->unique();
    
        $product_sold = [];
        $product_sold_total_per_cutoff = [];
        foreach($product_sold_arr as $i => $sold){
            $product_sold[$sold->promodiser][$sold->branch_warehouse][$sold->cutoff_period_from] = [
                'qty' => $sold->qty,
                'amount' => $sold->amount
            ];
    
            $product_sold_total_per_cutoff[$sold->cutoff_period_from][$i] = [
                'qty' => $sold->qty,
                'amount' => $sold->amount
            ];
        }
    
        $included_promodisers = DB::table('tabAssigned Consignment Warehouse')->pluck('parent');
        $included_promodisers = collect($included_promodisers)->unique();

        $promodisers = DB::table('tabWarehouse Users')->where('user_group', 'Promodiser')
            ->whereIn('frappe_userid', $included_promodisers)->get();
    
        $included_promodisers_full_name = collect($promodisers)->map(function($q){
            return $q->full_name;
        });
        
        $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')->get();
        $assigned_consignment_stores = collect($assigned_consignment_stores)->groupBy('parent');
    
       $opening_stocks = DB::table('tabConsignment Beginning Inventory as cbi')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'cbi.name')
            ->where('cbi.status', 'Approved')
            ->whereIn('cbi.owner', $included_promodisers_full_name)
            ->select('cbi.owner', 'cbi.branch_warehouse', DB::raw('sum(item.opening_stock) as qty'))
            ->groupBy('cbi.owner', 'cbi.branch_warehouse')
            ->get();

        $total_amount = DB::table('tabConsignment Beginning Inventory as cbi')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'cbi.name')
            ->where('cbi.status', 'Approved')->whereIn('cbi.owner', $included_promodisers_full_name)
            ->select('cbi.owner', 'cbi.branch_warehouse', 'item.item_code', DB::raw('sum(item.opening_stock) as qty'), DB::raw('sum(item.price) as price'))
            ->groupBy('cbi.owner', 'cbi.branch_warehouse', 'item.item_code')
            ->get();

        $total_amount_arr = [];
        foreach($total_amount as $value){
            $total_amount_arr[$value->owner][$value->branch_warehouse][$value->item_code] = [
                'qty' => $value->qty,
                'price' => $value->price,
                'amount' => $value->qty * $value->price
            ];
        }

        $opening_stocks_arr = [];
        foreach($opening_stocks as $stock){
            $opening_stocks_arr[$stock->owner][$stock->branch_warehouse] = [
                'qty' => $stock->qty
            ];
        }

        $report_arr = [];
        foreach($promodisers as $user){
            $report_arr[] = [
                'user' => $user->full_name,
                'assigned_warehouses' => isset($assigned_consignment_stores[$user->frappe_userid]) ? $assigned_consignment_stores[$user->frappe_userid] : []
            ];
        }
    
        return view('consignment.supervisor.tbl_sales_report', compact('report_arr', 'product_sold', 'cutoff_periods', 'opening_stocks_arr', 'product_sold_total_per_cutoff', 'total_amount_arr'));
    }

    public function beginningInventoryApproval(Request $request){
        $from_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[0])->startOfDay() : null;
        $to_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[1])->endOfDay() : null;

        $consignment_stores = [];
        $status = $request->status ? $request->status : 'All';
        if(Auth::user()->user_group == 'Consignment Supervisor'){
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
                ->when($status != 'All', function ($q) use ($status){
                    return $q->where('status', $status);
                })
                ->orderBy('creation', 'desc')
                ->paginate(10);
        } else {
            $consignment_stores = DB::table('tabAssigned Consignment Warehouse')
                ->when(Auth::user()->frappe_userid, function ($q){
                    return $q->where('parent', Auth::user()->frappe_userid);
                })
                ->pluck('warehouse');
            $consignment_stores = collect($consignment_stores)->unique();
            
            $beginning_inventory = DB::table('tabConsignment Beginning Inventory')
                ->when($request->search, function ($q) use ($request){
                    return $q->where('name', 'LIKE', '%'.$request->search.'%')
                        ->orWhere('owner', 'LIKE', '%'.$request->search.'%');
                })
                ->when($request->date, function ($q) use ($from_date, $to_date){
                    return $q->whereDate('transaction_date', '>=', $from_date)->whereDate('transaction_date', '<=', $to_date);
                })
                ->when(Auth::user()->user_group == 'Promodiser', function ($q) use ($consignment_stores){
                    return $q->whereIn('branch_warehouse', $consignment_stores);
                })
                ->when($request->store, function ($q) use ($request){
                    return $q->where('branch_warehouse', $request->store);
                })
                ->where('status', '!=', 'For Approval')
                ->orderBy('creation', 'desc')
                ->paginate(10);
        }

        $ids = collect($beginning_inventory->items())->map(function($q){
            return $q->name;
        });

        $warehouses = collect($beginning_inventory->items())->map(function($q){
            return $q->branch_warehouse;
        });

        $beginning_inv_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $ids)->get();
        $beginning_inventory_items = collect($beginning_inv_items)->groupBy('parent');

        $product_sold_arr = DB::table('tabConsignment Product Sold')->where('qty', '>', 0)->whereIn('branch_warehouse', $warehouses)->where('status', '!=', 'Cancelled')
            ->select('transaction_date', 'branch_warehouse', 'item_code', 'description', 'price', DB::raw('sum(qty) as qty'), DB::raw('sum(amount) as amount'))
            ->groupBy('transaction_date', 'branch_warehouse', 'item_code', 'description', 'price')
            ->get();
        $product_sold = collect($product_sold_arr)->groupBy('branch_warehouse');
        
        $sold_item_codes = collect($product_sold_arr)->map(function ($q){
            return $q->item_code;
        });

        $item_codes = collect($beginning_inv_items)->map(function ($q){
            return $q->item_code;
        })->merge($sold_item_codes)->unique();

        $warehouses = collect($beginning_inventory->items())->map(function ($q){
            return $q->branch_warehouse;
        })->unique();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $uoms = DB::table('tabItem')->whereIn('item_code', $item_codes)->select('item_code', 'stock_uom')->get();
        $uom = collect($uoms)->groupBy('item_code');

        $inv_arr = [];
        foreach($beginning_inventory as $inv){
            $items_arr = [];
            $included_items = [];
            if(isset($beginning_inventory_items[$inv->name])){
                foreach($beginning_inventory_items[$inv->name] as $item){
                    $items_arr[] = [
                        'parent' => $item->parent,
                        'inv_name' => $inv->name,
                        'image' => isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : null,
                        'img_count' => array_key_exists($item->item_code, $item_image) ? count($item_image[$item->item_code]) : 0,
                        'item_code' => $item->item_code,
                        'item_description' => $item->item_description,
                        'uom' => $item->stock_uom,
                        'opening_stock' => ($item->opening_stock * 1),
                        'price' => ($item->price * 1),
                        'amount' => ($item->price * 1) * ($item->opening_stock * 1),
                    ];
                }

                $included_items = collect($items_arr)->map(function ($q){
                    return $q['item_code'];
                })->toArray();
            }

            $sold_arr = [];
            if(isset($product_sold[$inv->branch_warehouse])){
                foreach($product_sold[$inv->branch_warehouse] as $sold){
                    if(!$items_arr || !in_array($sold->item_code, $included_items)){
                        continue;
                    }

                    $orig_exists = 0;
                    $webp_exists = 0;

                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';

                    if(isset($item_image[$sold->item_code])){
                        $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$sold->item_code][0]->image_path) ? 1 : 0;
                        $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$sold->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                        $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$sold->item_code][0]->image_path)[0].'.webp' : null;
                        $img = $orig_exists == 1 ? '/img/'.$item_image[$sold->item_code][0]->image_path : null;

                        if($orig_exists == 0 && $webp_exists == 0){
                            $img = '/icon/no_img.png';
                            $webp = '/icon/no_img.webp';
                        }
                    }

                    $sold_arr[] = [
                        'date' => $sold->transaction_date,
                        'item_code' => $sold->item_code,
                        'description' => $sold->description,
                        'image' => $img,
                        'webp' => $webp,
                        'uom' => isset($uom[$sold->item_code]) ? $uom[$sold->item_code][0]->stock_uom : null,
                        'qty' => $sold->qty,
                        'price' => $sold->price,
                        'amount' => $sold->amount
                    ];
                }
            }

            $inv_arr[] = [
                'name' => $inv->name,
                'branch' => $inv->branch_warehouse,
                'owner' => $inv->owner,
                'creation' => Carbon::parse($inv->creation)->format('M d, Y - h:i a'),
                'status' => $inv->status,
                'transaction_date' => Carbon::parse($inv->transaction_date)->format('M d, Y - h:i a'),
                'items' => $items_arr,
                'qty' => collect($items_arr)->sum('opening_stock'),
                'amount' => collect($items_arr)->sum('amount'),
                'sold' => $sold_arr
            ];
        }

        if(in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])){
            return view('consignment.supervisor.view_stock_adjustments', compact('consignment_stores', 'inv_arr', 'beginning_inventory'));
        }

        return view('consignment.beginning_inventory_list', compact('consignment_stores', 'inv_arr', 'beginning_inventory'));
    }

    public function approveBeginningInventory(Request $request, $id){
        DB::beginTransaction();
        try {
            $branch = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->pluck('branch_warehouse')->first();
            $prices = $request->price;

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

                $item_codes = collect($items)->map(function ($q){
                    return $q->item_code;
                });

                $bin = DB::table('tabBin')->where('warehouse', $branch)->whereIn('item_code', $item_codes)->get();
                $bin_items = collect($bin)->groupBy('item_code');

                foreach($items as $item){
                    if($item->status != 'For Approval'){ // Skip the approved/cancelled items
                        continue;
                    }
    
                    if(isset($bin_items[$item->item_code])){
                        DB::table('tabBin')->where('item_code', $item->item_code)->where('warehouse', $branch)->update([
                            'consigned_qty' => $item->opening_stock,
                            'modified' => $now,
                            'modified_by' => Auth::user()->wh_user
                        ]);
                    }else{
                        $latest_bin = DB::table('tabBin')->where('name', 'like', '%bin/%')->max('name');
                        $latest_bin_exploded = explode("/", $latest_bin);
                        $bin_id = (($latest_bin) ? $latest_bin_exploded[1] : 0) + 1;
                        $bin_id = str_pad($bin_id, 7, '0', STR_PAD_LEFT);
                        $bin_id = 'BIN/'.$bin_id;

                        DB::table('tabBin')->insert([
                            'name' => $bin_id,
                            'creation' => $now,
                            'modified' => $now,
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'idx' => 0, 
                            'warehouse' => $branch,
                            'item_code' => $item->item_code,
                            'stock_uom' => $item->stock_uom,
                            'valuation_rate' => isset($prices[$item->item_code]) ? preg_replace("/[^0-9 .]/", "", $prices[$item->item_code][0]) * 1 : 0,
                            'consigned_qty' => $item->opening_stock
                        ]);
                    }
                    

                    if(isset($prices[$item->item_code])){ // in case there is an update in price
                        $update_values['price'] = preg_replace("/[^0-9 .]/", "", $prices[$item->item_code][0]) * 1;
                    }
    
                    // update each item, allows checking if item for this branch is approved/cancelled
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->where('item_code', $item->item_code)->update($update_values);
                }
            }else{
                // update item status' to cancelled
                DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->update($update_values);
            }

            if(isset($update_values['price'])){ // remove price in updates array, parent table of beginning inventory does not have price
                unset($update_values['price']);
            }

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update($update_values);

            DB::commit();
            if ($request->ajax()) {
                return response()->json(['status' => 1, 'message' => 'Beginning Inventory for '.$branch.' was '.$request->status.'.']);
            }

            return redirect()->back()->with('success', 'Beginning Inventory for '.$branch.' was '.$request->status.'.');
        } catch (Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again later.']);
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function cancelApprovedBeginningInventory($id){
        DB::beginTransaction();
        try {
            $inventory = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->first();

            if(!$inventory){
                return redirect()->back()->with('error', 'Beginning inventory record does not exist.');
            }

            if($inventory->status == 'Cancelled'){
                return redirect()->back()->with('error', 'Beginning inventory record is already cancelled.');
            }

            $items = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();

            // Update each item in Bin and Product Sold
            foreach($items as $item){
                DB::table('tabBin')->where('warehouse', $inventory->branch_warehouse)->where('item_code', $item->item_code)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => 0
                ]);

                DB::table('tabConsignment Product Sold')->where('branch_warehouse', $inventory->branch_warehouse)->where('item_code', $item->item_code)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'status' => 'Cancelled'
                ]);
            }

            $update_values = [
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ];

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update($update_values);
            DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->update($update_values);

            DB::commit();
            return redirect()->back()->with('success', 'Beginning Inventory for '.$inventory->branch_warehouse.' was cancelled.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function promodiserDeliveryReport($type){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();

        $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

        $delivery_report = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ // do not include ste's of received items
                return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
            })
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', '<', 2)
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->whereIn('sted.t_warehouse', $assigned_consignment_store)
            ->select('ste.name', 'ste.delivery_date', 'ste.item_status', 'ste.from_warehouse', 'sted.t_warehouse', 'sted.s_warehouse', 'ste.creation', 'ste.posting_time', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.consignment_status', 'ste.transfer_as', 'ste.docstatus', 'sted.consignment_date_received')
            ->orderBy('ste.creation', 'desc')->paginate(10);

        $delivery_report_q = collect($delivery_report->items())->groupBy('name');

        $item_codes = collect($delivery_report->items())->map(function ($q){
            return $q->item_code;
        });

        $source_warehouses = collect($delivery_report->items())->map(function ($q){
            return $q->s_warehouse;
        });

        $target_warehouses = collect($delivery_report->items())->map(function ($q){
            return $q->t_warehouse;
        });

        $warehouses = collect($source_warehouses)->merge($target_warehouses)->unique();

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->whereIn('cbi.item_code', $item_codes)->whereIn('cb.branch_warehouse', $warehouses)->select('cbi.item_code', 'cbi.price', 'cb.branch_warehouse')->get();
        
        $prices_arr = [];
        foreach($beginning_inventory as $inv){
            $prices_arr[$inv->branch_warehouse][$inv->item_code] = [
                'price' => $inv->price
            ];
        }

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $now = Carbon::now();

        $ste_arr = [];
        foreach($delivery_report_q as $ste => $row){
            $items_arr = [];
            foreach($row as $item){
                $ref_warehouse = $row[0]->transfer_as == 'Consignment' ? $row[0]->t_warehouse : $row[0]->s_warehouse;
                $items_arr[] = [
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'image' => isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : null,
                    'img_count' => isset($item_image[$item->item_code]) ? count($item_image[$item->item_code]) : 0,
                    'delivered_qty' => $item->transfer_qty,
                    'stock_uom' => $item->stock_uom,
                    'price' => isset($prices_arr[$ref_warehouse][$item->item_code]) ? number_format($prices_arr[$ref_warehouse][$item->item_code]['price'], 2) : 0,
                    'delivery_status' => $item->consignment_status,
                    'date_received' => $item->consignment_date_received
                ];
            }

            $status_check = collect($items_arr)->map(function($q){
                return $q['delivery_status'] ? 1 : 0; // return 1 if status is Received
            })->toArray();

            $delivery_date = Carbon::parse($row[0]->delivery_date);
          
            if($row[0]->item_status == 'Issued' && $now > $delivery_date){
                $status = 'Delivered';
            }else{
                $status = 'Pending';
            }

            $ste_arr[] = [
                'name' => $row[0]->name,
                'from' => $row[0]->from_warehouse,
                'to_consignment' => $row[0]->t_warehouse,
                'status' => $status,
                'items' => $items_arr,
                'creation' => $row[0]->creation,
                'delivery_date' => $row[0]->delivery_date,
                'delivery_status' => min($status_check) == 0 ? 0 : 1, // check if there are still items to receive
                'posting_time' => $row[0]->posting_time,
                'date_received' => min($status_check) == 1 ? collect($items_arr)->min('date_received') : null
            ];
        }

        return view('consignment.promodiser_delivery_report', compact('delivery_report', 'ste_arr', 'type'));
    }

    public function promodiserReceiveDelivery(Request $request, $id){
        DB::beginTransaction();
        try {
            $wh = DB::table('tabStock Entry')->where('name', $id)->first();
            if(!$wh){
                return redirect()->back()->with('error', $id.' not found.');
            }

            $ste_items = DB::table('tabStock Entry Detail')->where('parent', $id)->get();

            $source_warehouses = collect($ste_items)->map(function($q){
                return $q->s_warehouse;
            })->unique();

            $target_warehouses = collect($ste_items)->map(function($q){
                return $q->t_warehouse;
            })->unique();

            $wh_warehouses = [$wh->from_warehouse, $wh->to_warehouse];
            $reference_warehouses = collect($source_warehouses)->merge($target_warehouses);
            $reference_warehouses = collect($reference_warehouses)->merge($wh_warehouses)->unique()->toArray();
            
            $item_codes = collect($ste_items)->map(function ($q){
                return $q->item_code;
            });

            $bin = DB::table('tabBin')->whereIn('warehouse', array_filter($reference_warehouses))->whereIn('item_code', $item_codes)->get();
            $bin_items = [];
            foreach($bin as $b){
                $bin_items[$b->warehouse][$b->item_code] = [
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->whereIn('cb.branch_warehouse', array_filter([$target_warehouses, $wh->to_warehouse]))
                ->select('cb.branch_warehouse', 'cbi.item_code', 'cb.name', 'cb.status', 'cbi.opening_stock')->get();

            $item_codes_with_beginning_inventory = collect($beginning_inventory)->map(function ($q){
                return $q->item_code;
            })->toArray();

            $item_codes_without_beginning_inventory = array_diff($item_codes->toArray(), $item_codes_with_beginning_inventory);

            $beginning_inventory_arr = [];
            foreach($beginning_inventory as $inv){
                $beginning_inventory_arr[$inv->branch_warehouse][$inv->item_code] = [
                    'name' => $inv->name,
                    'status' => $inv->status,
                    'consigned_qty' => $inv->opening_stock
                ];
            }

            $inv_id = null;
            if($item_codes_without_beginning_inventory){
                $latest_inv = DB::table('tabConsignment Beginning Inventory')->where('name', 'like', '%inv%')->max('name');
                $latest_inv_exploded = explode("-", $latest_inv);
                $inv_id = (($latest_inv) ? $latest_inv_exploded[1] : 0) + 1;
                $inv_id = str_pad($inv_id, 6, '0', STR_PAD_LEFT);
                $inv_id = 'INV-'.$inv_id;
    
                $values = [
                    'docstatus' => 0,
                    'name' => $inv_id,
                    'idx' => 0,
                    'status' => 'For Approval',
                    'branch_warehouse' => $target_warehouses ? $target_warehouses->first() : $wh->to_warehouse,
                    'creation' => Carbon::now()->toDateTimeString(),
                    'transaction_date' => Carbon::now()->toDateTimeString(),
                    'owner' => Auth::user()->full_name,
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->full_name
                ];
                
                DB::table('tabConsignment Beginning Inventory')->insert($values);
            }

            $now = Carbon::now();
            $prices = $request->price ? $request->price : [];

            $i = 0;
            foreach($ste_items as $item){
                $basic_rate = $item->basic_rate;
                $branch =  $wh->to_warehouse ? $wh->to_warehouse : $item->t_warehouse;
                $src_branch = $wh->from_warehouse ? $wh->from_warehouse : $item->s_warehouse;

                if(!isset($prices[$item->item_code])){
                    return redirect()->back()->with('error', 'Please enter price for all items.');
                }
                
                $basic_rate = preg_replace("/[^0-9 .]/", "", $prices[$item->item_code]);

                // Beginning Inventory
                if(in_array($item->item_code, $item_codes_with_beginning_inventory) && isset($beginning_inventory_arr[$branch][$item->item_code])){
                    $inv_arr = [
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->full_name,
                        'price' => $basic_rate,
                        'amount' => $item->transfer_qty * $basic_rate
                    ];

                    if($beginning_inventory_arr[$branch][$item->item_code]['status'] == 'For Approval'){
                        unset($inv_arr['amount']);

                        $opening_stock = $beginning_inventory_arr[$branch][$item->item_code]['consigned_qty'] + $item->transfer_qty;
                        $inv_arr['opening_stock'] = $opening_stock;
                        $inv_arr['amount'] = $opening_stock * $basic_rate;
                    }

                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $beginning_inventory_arr[$branch][$item->item_code]['name'])->where('item_code', $item->item_code)->update($inv_arr);
                }else{
                    DB::table('tabConsignment Beginning Inventory Item')->insert([
                        'name' => uniqid(),
                        'creation' => Carbon::now()->toDateTimeString(),
                        'owner' => Auth::user()->full_name,
                        'docstatus' => 0,
                        'parent' => $inv_id,
                        'idx' => $i++,
                        'item_code' => $item->item_code,
                        'item_description' => $item->description,
                        'stock_uom' => $item->stock_uom,
                        'opening_stock' => $item->transfer_qty,
                        'stocks_displayed' => 0,
                        'status' => 'For Approval',
                        'price' => $basic_rate,
                        'amount' => $basic_rate * $item->transfer_qty,
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->full_name,
                        'parentfield' => 'items',
                        'parenttype' => 'Consignment Beginning Inventory' 
                    ]);
                }

                // Source Warehouse
                if($wh->transfer_as == 'Store Transfer' && $wh->purpose != 'Material Receipt'){
                    $src_consigned = isset($bin_items[$src_branch][$item->item_code]) ? $bin_items[$src_branch][$item->item_code]['consigned_qty'] : 0;
                    if($src_consigned < $item->transfer_qty){
                        return redirect()->back()->with('error', 'Not enough qty for '.$item->item_code.'. Qty needed is '.number_format($item->transfer_qty).', available qty is '.number_format($src_consigned).'.');
                    }

                    DB::table('tabBin')->where('warehouse', $src_branch)->where('item_code', $item->item_code)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => $src_consigned - $item->transfer_qty
                    ]);
                }

                // Target Warehouse
                if(isset($bin_items[$branch][$item->item_code])){
                    if($item->consignment_status == 'Received'){
                        continue;
                    }

                    $consigned_qty = $bin_items[$branch][$item->item_code]['consigned_qty'];

                    DB::table('tabBin')->where('warehouse', $branch)->where('item_code', $item->item_code)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => $consigned_qty + $item->transfer_qty
                    ]);
                }else{
                    $latest_bin = DB::table('tabBin')->where('name', 'like', '%bin/%')->max('name');
                    $latest_bin_exploded = explode("/", $latest_bin);
                    $bin_id = (($latest_bin) ? $latest_bin_exploded[1] : 0) + 1;
                    $bin_id = str_pad($bin_id, 7, '0', STR_PAD_LEFT);
                    $bin_id = 'BIN/'.$bin_id;

                    DB::table('tabBin')->insert([
                        'name' => $bin_id,
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->full_name,
                        'owner' => Auth::user()->full_name,
                        'docstatus' => 0,
                        'idx' => 0,
                        'warehouse' => $branch,
                        'item_code' => $item->item_code,
                        'stock_uom' => $item->stock_uom,
                        'valuation_rate' => $basic_rate,
                        'consigned_qty' => $item->transfer_qty
                    ]);
                }

                // Stock Entry Detail
                $ste_details_update = [
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'basic_rate' => $basic_rate,
                    'custom_basic_rate' => $basic_rate,
                    'basic_amount' => $basic_rate * $item->transfer_qty,
                    'custom_basic_amount' => $basic_rate * $item->transfer_qty
                ];

                if($item->consignment_status != 'Received'){
                    $ste_details_update['consignment_status'] = 'Received';
                    $ste_details_update['consignment_date_received'] = Carbon::now()->toDateTimeString();
                }

                DB::table('tabStock Entry Detail')->where('name', $item->name)->update($ste_details_update);
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
        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->whereIn('branch_warehouse', $assigned_consignment_store)->orderBy('creation', 'desc')->paginate(10);

        return view('consignment.beginning_inv_list', compact('beginning_inventory'));
    }

    public function beginningInvItemsList($id){
        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->first();

        if(!$beginning_inventory){
            return redirect()->back()->with('error', 'Inventory Record Not Found.');
        }
        
        $inventory = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();

        $item_codes = collect($inventory)->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->get();
        $item_image = collect($item_images)->groupBy('parent');

        return view('consignment.beginning_inv_items_list', compact('inventory', 'item_image', 'beginning_inventory'));
    }

    public function beginningInventory($inv = null){
        $inv_record = [];
        if($inv){
            $inv_record = DB::table('tabConsignment Beginning Inventory')->where('name', $inv)->where('status', 'For Approval')->first();

            if(!$inv_record){
                return redirect()->back()->with('error', 'Inventory Record Not Found.');
            }
        }

        $branch = $inv_record ? $inv_record->branch_warehouse : null;
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.beginning_inventory', compact('assigned_consignment_store',  'inv', 'branch', 'inv_record'));
    }

    public function getItems(Request $request, $branch){
        $items_already_added_in_table = $request->excluded_items ? $request->excluded_items : [];
        $bin_items = DB::table('tabBin')->where('warehouse', $branch)->pluck('item_code');

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch)->whereIn('status', ['Approved', 'For Approval'])->pluck('name');
        $inventory_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $beginning_inventory)->whereIn('status', ['Approved', 'For Approval'])->pluck('item_code');

        $excluded_items = collect($bin_items)->merge($inventory_items)->unique(); // exclude items already in bin and approved and for approval items
        $excluded_items = collect($excluded_items)->merge($items_already_added_in_table)->unique(); // exclude items already added in items table

        $search_str = explode(' ', $request->q);

        $items = DB::table('tabItem')->whereNotIn('item_code', $excluded_items)
            ->where('disabled', 0)->where('has_variants', 0)->where('is_stock_item', 1)
            ->when($request->q, function ($query) use ($search_str, $request) {
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('description', 'LIKE', "%".$str."%");
                    }

                    $q->orWhere('name', 'LIKE', "%".$request->q."%");
                });
            })
            ->limit(4)->get();

        $item_codes = collect($items)->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $items_arr = [];
        foreach($items as $item){
            $image = '/icon/no_img.png';
            if(isset($item_image[$item->item_code]) || $item->item_image_path){
                $image = isset($item_image[$item->item_code]) ? '/img/'.$item_image[$item->item_code][0]->image_path : '/img/'.$item->item_image_path;
            }

            $image_webp = '/icon/no_img.webp';
            if(isset($item_image[$item->item_code]) || $item->item_image_path){
                $image_webp = isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : $item->item_image_path;
                $image_webp = '/img/'.(explode('.', $image_webp)[0]).'.webp';
            }

            $items_arr[] = [
                'id' => $item->item_code,
                'text' => $item->item_code.' - '.strip_tags($item->description),
                'description' => strip_tags($item->description),
                'classification' => $item->item_classification,
                'image' => asset('storage'.$image),
                'image_webp' => asset('storage'.$image_webp),
                'alt' => str_slug(explode('.', $image)[0], '-')
            ];
        }

        return response()->json([
            'items' => $items_arr
        ]);
    }

    public function beginningInvItems(Request $request, $action, $branch, $id = null){
        if($request->ajax()){
            $items = [];
            $inv_name = null;
            if($action == 'update'){ // If 'For Approval' beginning inventory record exists for this branch
                $inv_name = $id;
                $inventory = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->select('item_code', 'item_description', 'stock_uom', 'opening_stock', 'stocks_displayed', 'price')->get();

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
            $item_images = collect($item_images)->groupBy('parent')->toArray();

            return view('consignment.beginning_inv_items', compact('items', 'branch', 'item_images', 'inv_name'));
        }
    }

    public function saveBeginningInventory(Request $request){
        DB::beginTransaction();
        try {
            $opening_stock = $request->opening_stock;
            $opening_stock = preg_replace("/[^0-9 .]/", "", $opening_stock);

            $price = $request->price;
            $price = preg_replace("/[^0-9 .]/", "", $price);

            $item_codes = $request->item_code;
            $item_codes = collect(array_filter($item_codes))->unique(); // remove null values
            $branch = $request->branch;

            if(!$item_codes){
                return redirect()->back()->with('error', 'Please select an item to save');
            }

            if(max($opening_stock) <= 0 || max($price) <= 0 || !array_filter($opening_stock) || !array_filter($price)) { // If all values of opening stocks or prices are 0 or if opening stocks or prices are null
                $null_value = null;
                if(max($opening_stock) <= 0 || !array_filter($opening_stock)){
                    $null_value = 'Opening Stock';
                }else{
                    $null_value = 'Price';
                }

                return redirect()->back()->with('error', 'Please input values to '.$null_value);
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
                    'idx' => 0,
                    'status' => 'For Approval',
                    'branch_warehouse' => $branch,
                    'creation' => $now,
                    'transaction_date' => $now,
                    'owner' => Auth::user()->full_name,
                    'modified' => $now,
                    'modified_by' => Auth::user()->full_name
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
                        'idx' => $i + 1,
                        'item_code' => $item_code,
                        'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->description : null,
                        'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                        'opening_stock' => isset($opening_stock[$item_code]) ? preg_replace("/[^0-9 .]/", "", $opening_stock[$item_code]) : 0,
                        'stocks_displayed' => 0,
                        'status' => 'For Approval',
                        'price' => isset($price[$item_code]) ? preg_replace("/[^0-9 .]/", "", $price[$item_code]) : 0,
                        'modified' => $now,
                        'modified_by' => Auth::user()->full_name,
                        'parentfield' => 'items',
                        'parenttype' => 'Consignment Beginning Inventory' 
                    ];

                    $item_count = $item_count + 1;
                    DB::table('tabConsignment Beginning Inventory Item')->insert($row_values);
                }

                session()->flash('success', 'Beginning Inventory is For Approval');
            }else if(isset($request->cancel)){ // delete cancelled beginning inventory record
                DB::table('tabConsignment Beginning Inventory')->where('name', $request->inv_name)->delete();
                DB::table('tabConsignment Beginning Inventory Item')->where('parent', $request->inv_name)->delete();

                session()->flash('success', 'Beginning Inventory is Cancelled');
                session()->flash('cancelled', 'Cancelled');
            }else{
                DB::table('tabConsignment Beginning Inventory')->where('name', $request->inv_name)->update([
                    'modified' => $now,
                    'modified_by' => Auth::user()->wh_user
                ]);
                
                $inventory_items = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $request->inv_name)->pluck('item_code')->toArray();
                $removed_items = array_diff($inventory_items, $item_codes->toArray());

                foreach($removed_items as $remove){ // delete removed items
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $request->inv_name)->where('item_code', $remove)->delete();
                }

                foreach($item_codes as $i => $item_code){
                    if(!$item_code || isset($opening_stock[$item_code]) && $opening_stock[$item_code] == 0){ // Prevents saving removed items and items with 0 opening stock
                        continue;
                    }

                    if(isset($opening_stock[$item_code]) && $opening_stock[$item_code] < 0 || isset($price[$item_code]) && $price[$item_code] < 0){
                        return redirect()->back()->with('error', 'Cannot enter value below 0');
                    }

                    if(in_array($item_code, $inventory_items)){
                        $values = [
                            'modified' => $now,
                            'modified_by' => Auth::user()->wh_user,
                            'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->description : null,
                            'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                            'opening_stock' => isset($opening_stock[$item_code]) ? preg_replace("/[^0-9 .]/", "", $opening_stock[$item_code]) : 0,
                            'price' => isset($price[$item_code]) ? preg_replace("/[^0-9 .]/", "", $price[$item_code]) : 0,
                        ];

                        DB::table('tabConsignment Beginning Inventory Item')->where('parent', $request->inv_name)->where('item_code', $item_code)->update($values);
                    }else{
                        $idx = count($inventory_items) + ($i + 1);
                        $row_values = [
                            'name' => uniqid(),
                            'creation' => $now,
                            'owner' => Auth::user()->full_name,
                            'docstatus' => 0,
                            'parent' => $request->inv_name,
                            'idx' => $idx,
                            'item_code' => $item_code,
                            'item_description' => isset($item[$item_code]) ? $item[$item_code][0]->description : null,
                            'stock_uom' => isset($item[$item_code]) ? $item[$item_code][0]->stock_uom : null,
                            'opening_stock' => isset($opening_stock[$item_code]) ? preg_replace("/[^0-9 .]/", "", $opening_stock[$item_code]) : 0,
                            'stocks_displayed' => 0,
                            'status' => 'For Approval',
                            'price' => isset($price[$item_code]) ? preg_replace("/[^0-9 .]/", "", $price[$item_code]) : 0,
                            'modified' => $now,
                            'modified_by' => Auth::user()->full_name,
                            'parentfield' => 'items',
                            'parenttype' => 'Consignment Beginning Inventory' 
                        ];
    
                        DB::table('tabConsignment Beginning Inventory Item')->insert($row_values);
                    }
                    $item_count = $item_count + 1; 
                }
                session()->flash('success', 'Beginning Inventory is Updated');
            }

            DB::commit();
            return view('consignment.beginning_inv_success', compact('item_count', 'branch'));
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function stockTransferReport(Request $request){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')
            ->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $damaged_items = DB::table('tabConsignment Damaged Item')
            ->when($request->search, function ($q) use ($request){
                $q->where('item_code', 'like', '%' . $request->search .'%')
                    ->orWhere('description', 'like', '%' . $request->search .'%');
            })
            ->when($request->store, function ($q) use ($request){
                $q->where('branch_warehouse', $request->store);
            })
            ->when(Auth::user()->user_group == 'Promodiser', function ($q) use ($assigned_consignment_store){
                $q->whereIn('branch_warehouse', $assigned_consignment_store);
            })->orderBy('creation', 'desc')->paginate(20, ['*'], 'damaged_items');
        
        $item_codes = collect($damaged_items->items())->map(function ($q){
            return $q->item_code;
        });

        $ste_item_codes = [];
        if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) { // for supervisor stock transfers list
            $stock_entry = DB::table('tabStock Entry')
                ->when($request->tab1_q, function ($q) use ($request){
                    return $q->where('name', 'like', '%'.$request->tab1_q.'%');
                })
                ->when($request->source_warehouse, function ($q) use ($request){
                    return $q->where('from_warehouse', $request->source_warehouse);
                })
                ->when($request->target_warehouse, function ($q) use ($request){
                    return $q->where('to_warehouse', $request->target_warehouse);
                })
                ->when($request->tab1_status && $request->tab1_status != 'All', function ($q) use ($request){
                    return $q->where('docstatus', $request->tab1_status);
                })
                ->when($request->tab1_purpose, function ($q) use ($request){
                    return $q->where('transfer_as', $request->tab1_purpose);
                })
                ->where('naming_series', 'STEC-')
                ->orderBy('docstatus', 'asc')
                ->orderBy('creation', 'desc')
                ->paginate(20, ['*'], 'stock_transfers');

            $reference = collect($stock_entry->items())->map(function ($q){
                return $q->name;
            });

            $stock_entry_detail = DB::table('tabStock Entry Detail')->whereIn('parent', $reference)->get();
            $ste_items = collect($stock_entry_detail)->groupBy('parent');

            $ste_item_codes = collect($stock_entry_detail)->map(function ($q){
                return $q->item_code;
            });

            $item_codes = collect($item_codes)->merge($ste_item_codes);
        }

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $items_arr = [];
        foreach($damaged_items as $item){
            $orig_exists = 0;
            $webp_exists = 0;

            $img = '/icon/no_img.png';
            $webp = '/icon/no_img.webp';

            if(isset($item_image[$item->item_code])){
                $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$item->item_code][0]->image_path) ? 1 : 0;
                $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : null;
                $img = $orig_exists == 1 ? '/img/'.$item_image[$item->item_code][0]->image_path : null;

                if($orig_exists == 0 && $webp_exists == 0){
                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';
                }
            }
            
            $items_arr[] = [
                'item_code' => $item->item_code,
                'description' => $item->description,
                'damaged_qty' => ($item->qty * 1),
                'uom' => $item->stock_uom,
                'store' => $item->branch_warehouse,
                'damage_description' => $item->damage_description,
                'promodiser' => $item->promodiser,
                'image' => $img,
                'webp' => $webp,
                'creation' => Carbon::parse($item->creation)->format('M d, Y - h:i a'),
                'test' => $orig_exists,
                'test2' => $webp_exists
            ];
        }

        if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) {
            $source_warehouses = collect($stock_entry->items())->map(function ($q){
                return $q->from_warehouse;
            })->unique();

            $bin = DB::table('tabBin')->whereIn('warehouse', $source_warehouses)->whereIn('item_code', $ste_item_codes)->get();
            $bin_arr = [];
            foreach($bin as $b){
                $bin_arr[$b->warehouse][$b->item_code] = [
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            $ste_arr = [];
            foreach($stock_entry as $ste){
                $items = [];
                if(isset($ste_items[$ste->name])){
                    foreach($ste_items[$ste->name] as $item){
                        $orig_exists = 0;
                        $webp_exists = 0;

                        $img = '/icon/no_img.png';
                        $webp = '/icon/no_img.webp';

                        if(isset($item_image[$item->item_code])){
                            $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$item->item_code][0]->image_path) ? 1 : 0;
                            $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                            $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : null;
                            $img = $orig_exists == 1 ? '/img/'.$item_image[$item->item_code][0]->image_path : null;

                            if($orig_exists == 0 && $webp_exists == 0){
                                $img = '/icon/no_img.png';
                                $webp = '/icon/no_img.webp';
                            }
                        }

                        $items[] = [
                            'item_code' => $item->item_code,
                            'description' => $item->description,
                            'transfer_qty' => $item->transfer_qty,
                            'uom' => $item->stock_uom,
                            'consigned_qty' => isset($bin_arr[$ste->from_warehouse][$item->item_code]) ? $bin_arr[$ste->from_warehouse][$item->item_code]['consigned_qty'] : 0,
                            'image' => $img,
                            'webp' => $webp
                        ];
                    }
                }

                $ste_arr[] = [
                    'name' => $ste->name,
                    'creation' => Carbon::parse($ste->creation)->format('M d, Y - h:i a'),
                    'source_warehouse' => $ste->from_warehouse,
                    'target_warehouse' => $ste->to_warehouse,
                    'status' => $ste->docstatus == 1 ? 'Approved' : 'For Approval',
                    'transfer_as' => $ste->transfer_as,
                    'submitted_by' => $ste->owner,
                    'items' => $items
                ];
            }

            return view('consignment.view_damaged_items_list', compact('items_arr', 'damaged_items', 'ste_arr', 'stock_entry'));
        }

        return view('consignment.damaged_items_list', compact('items_arr'));
    }

    public function promodiserDamageForm(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cbi')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'cbi.name')
            ->whereIn('cbi.branch_warehouse', $assigned_consignment_store)->where('cbi.status', 'Approved')
            ->select('cbi.branch_warehouse', 'cbi.name', 'cbi.transaction_date')->get();
        $beginning_inventory = collect($beginning_inventory)->groupBy('branch_warehouse');

        return view('consignment.promodiser_damage_report_form', compact('assigned_consignment_store', 'beginning_inventory'));
    }

    public function submitDamagedItem(Request $request){
        DB::beginTransaction();
        try {
            $item_codes = $request->item_code;
            $damaged_qty = preg_replace("/[^0-9]/", "", $request->damaged_qty);
            $reason = $request->reason;

            if(collect($damaged_qty)->min() <= 0){
                return redirect()->back()->with('error', 'Damaged items qty cannot be less than or equal to zero.');
            }

            $items = DB::table('tabBin as bin')
                ->join('tabItem as item', 'item.item_code', 'bin.item_code')
                ->whereIn('bin.item_code', $item_codes)->where('bin.warehouse', $request->branch)
                ->select('bin.item_code', 'item.description', 'bin.consigned_qty', 'bin.stock_uom')->get();
            $items = collect($items)->groupBy('item_code');

            $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cbi')
                ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'cbi.name')
                ->where('cbi.branch_warehouse', $request->branch)->whereIn('item.item_code', $item_codes)->where('cbi.status', 'Approved')
                ->select('item.item_code', 'cbi.name', 'cbi.transaction_date')->get();
            $beginning_inventory = collect($beginning_inventory)->groupBy('item_code');

            foreach($item_codes as $item_code){
                if(!isset($items[$item_code]) || !isset($beginning_inventory[$item_code])){
                    return redirect()->back()->with('error', $item_code.' has not been delivered to '.$request->branch.' yet or beginning inventory has not been approved yet.');
                }else{
                    if($items[$item_code][0]->consigned_qty < $damaged_qty[$item_code]){
                        return redirect()->back()->with('error', 'Damaged qty for '.$item_code.' is more than the delivered qty.');
                    }
                }

                $insert_values = [
                    'name' => uniqid(),
                    'creation' => Carbon::now()->toDateTimeString(),
                    'owner' => Auth::user()->full_name,
                    'docstatus' => 1,
                    'transaction_date' => $beginning_inventory[$item_code][0]->transaction_date,
                    'branch_warehouse' => $request->branch,
                    'item_code' => $item_code,
                    'description' => isset($items[$item_code]) ? $items[$item_code][0]->description : null,
                    'qty' => isset($damaged_qty[$item_code]) ? $damaged_qty[$item_code] : 0,
                    'stock_uom' => isset($items[$item_code]) ? $items[$item_code][0]->stock_uom : null,
                    'damage_description' => isset($reason[$item_code]) ? $reason[$item_code] : 0,
                    'promodiser' => Auth::user()->full_name,
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->full_name
                ];

                DB::table('tabConsignment Damaged Item')->insert($insert_values);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Damage report submitted.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function damagedItems(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $damaged_items = DB::table('tabConsignment Damaged Item')->whereIn('branch_warehouse', $assigned_consignment_store)->paginate(10);

        $item_codes = collect($damaged_items->items())->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->get();
        $item_image = collect($item_images)->groupBy('parent');

        $damaged_arr = [];
        foreach($damaged_items as $item){
            $orig_exists = 0;
            $webp_exists = 0;

            $img = '/icon/no_img.png';
            $webp = '/icon/no_img.webp';

            if(isset($item_image[$item->item_code])){
                $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$item->item_code][0]->image_path) ? 1 : 0;
                $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : null;
                $img = $orig_exists == 1 ? '/img/'.$item_image[$item->item_code][0]->image_path : null;

                if($orig_exists == 0 && $webp_exists == 0){
                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';
                }
            }

            $damaged_arr[] = [
                'name' => $item->name,
                'item_code' => $item->item_code,
                'item_description' => $item->description,
                'damaged_qty' => $item->qty,
                'uom' => $item->stock_uom,
                'damage_description' => $item->damage_description,
                'promodiser' => $item->promodiser,
                'creation' => $item->creation,
                'store' => $item->branch_warehouse,
                'image' => $img,
                'webp' => $webp,
                'status' => $item->status
            ];
        }

        return view('consignment.promodiser_damaged_list', compact('damaged_arr', 'damaged_items'));
    }

    public function returnDamagedItem($id){
        DB::beginTransaction();
        try {
            $damaged_item = DB::table('tabConsignment Damaged Item')->where('name', $id)->first();

            if(!$damaged_item){
                return redirect()->back()->with('error', 'Item not found.');
            }

            if($damaged_item->status == 'Returned'){
                return redirect()->back()->with('error', 'Item is already returned.');
            }

            $price = DB::table('tabConsignment Beginning Inventory as cbi')
                ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'cbi.name')
                ->where('cbi.branch_warehouse', $damaged_item->branch_warehouse)->where('item.item_code', $damaged_item->item_code)->where('cbi.status', 'Approved')
                ->pluck('price')->first();

            if(!$price){
                return redirect()->back()->with('error', 'No Beginning Inventory Record found for this item.');
            }

            $consigned_qty = DB::table('tabBin')->where('warehouse', 'Quarantine Warehouse - FI')->where('item_code', $damaged_item->item_code)->pluck('consigned_qty')->first();
            if($consigned_qty){
                DB::table('tabBin')->where('warehouse', 'Quarantine Warehouse - FI')->where('item_code', $damaged_item->item_code)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->full_name,
                    'consigned_qty' => $consigned_qty + $damaged_item->qty
                ]);
            }else{
                $latest_bin = DB::table('tabBin')->where('name', 'like', '%bin/%')->max('name');
                $latest_bin_exploded = explode("/", $latest_bin);
                $bin_id = (($latest_bin) ? $latest_bin_exploded[1] : 0) + 1;
                $bin_id = str_pad($bin_id, 7, '0', STR_PAD_LEFT);
                $bin_id = 'BIN/'.$bin_id;

                DB::table('tabBin')->insert([
                    'name' => $bin_id,
                    'creation' => Carbon::now()->toDateTimeString(),
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->full_name,
                    'owner' => Auth::user()->full_name,
                    'docstatus' => 0,
                    'idx' => 0,
                    'warehouse' => 'Quarantine Warehouse - FI',
                    'item_code' => $damaged_item->item_code,
                    'stock_uom' => $damaged_item->stock_uom,
                    'valuation_rate' => $price,
                    'consigned_qty' => $damaged_item->qty
                ]);
            }

            DB::table('tabConsignment Damaged Item')->where('name', $id)->update([
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->full_name,
                'status' => 'Returned'
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Item Returned.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function getReceivedItems(Request $request, $branch){
        $search_str = explode(' ', $request->q);
        $excluded_item_codes = $request->excluded_items ? $request->excluded_items : []; // exclude items already added in the items table

        $items = DB::table('tabBin as bin')
            ->join('tabItem as item', 'item.item_code', 'bin.item_code')
            ->when($request->q, function ($query) use ($request, $search_str){
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('item.description', 'LIKE', "%".$str."%");
                    }

                    $q->orWhere('item.item_code', 'LIKE', "%".$request->q."%");
                });
            })
            ->where('bin.warehouse', $branch)->where('bin.consigned_qty', '>', 0)->get();
        
        $item_codes = collect($items)->map(function ($q) {
            return $q->item_code;
        });

        $items = collect($items)->groupBy('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $default_images = DB::table('tabItem')->whereIn('item_code', $item_codes)->whereNotNull('item_image_path')->select('item_code', 'item_image_path as image_path')->get(); // in case there are no saved images in Item Images
        $default_image = collect($default_images)->groupBy('item_code');

        $inventory_arr = DB::table('tabConsignment Beginning Inventory as inv')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'inv.name')
            ->where('inv.branch_warehouse', $branch)->where('inv.status', 'Approved')->where('item.status', 'Approved')->whereIn('item.item_code', $item_codes)
            ->when($excluded_item_codes, function ($q) use ($excluded_item_codes){
                return $q->whereNotIn('item.item_code', $excluded_item_codes);
            })
            ->select('item.item_code', 'item.price', 'inv.transaction_date')->get();

        $inventory = collect($inventory_arr)->groupBy('item_code');

        $items_arr = [];
        foreach($inventory_arr as $item){
            $orig_exists = 0;
            $webp_exists = 0;

            $img = '/icon/no_img.png';
            $webp = '/icon/no_img.webp';

            $img_path = null;
            $webp_path = null;

            if(isset($item_image[$item->item_code]) || isset($default_image[$item->item_code])){
                $img_path = isset($item_image[$item->item_code]) ? $item_image[$item->item_code][0]->image_path : $default_image[$item->item_code][0]->image_path;
                $webp_path = isset($item_image[$item->item_code]) ? explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : explode('.', $default_image[$item->item_code][0]->image_path)[0].'.webp';

                $orig_exists = Storage::disk('public')->exists('/img/'.$img_path) ? 1 : 0;
                $webp_exists = Storage::disk('public')->exists('/img/'.$webp_path) ? 1 : 0;

                $img = $orig_exists == 1 ? '/img/'.$img_path : null;
                $webp = $webp_exists == 1 ? '/img/'.$webp_path : null;

                if($orig_exists == 0 && $webp_exists == 0){
                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';
                }
            }

            $items_arr[] = [
                'id' => $item->item_code,
                'text' => $item->item_code.' - '.(isset($items[$item->item_code]) ? strip_tags($items[$item->item_code][0]->description) : null),
                'description' =>  isset($items[$item->item_code]) ? strip_tags($items[$item->item_code][0]->description) : null,
                'max' => isset($items[$item->item_code]) ? $items[$item->item_code][0]->consigned_qty * 1 : null,
                'uom' => isset($items[$item->item_code]) ? $items[$item->item_code][0]->stock_uom : null,
                'price' => isset($inventory[$item->item_code]) ? '₱ '.number_format($inventory[$item->item_code][0]->price, 2) : '₱ 0.00',
                'transaction_date' => isset($inventory[$item->item_code]) ? $inventory[$item->item_code][0]->transaction_date : null,
                'img' => asset('storage'.$img),
                'webp' => asset('storage'.$webp),
                'alt' => str_slug(explode('.', $img)[0], '-')
            ];
        }

        return response()->json($items_arr);
    }

    public function stockTransferSubmit(Request $request){
        DB::beginTransaction();
        try {
            $now = Carbon::now();

            $item_codes = array_filter(collect($request->item_code)->unique()->toArray());
            $transfer_qty = $request->item;

            $source_warehouse = $request->transfer_as == 'Sales Return' ? null : $request->source_warehouse;
            $target_warehouse = $request->transfer_as == 'For Return' ? 'Quarantine Warehouse - FI' : $request->target_warehouse;

            $reference_warehouse = $request->transfer_as == 'Sales Return' ? $request->target_warehouse : $request->source_warehouse; // used to get data from bin
            if(!$item_codes || !$transfer_qty){
                return redirect()->back()->with('error', 'Please select an item to return');
            }

            $min = collect($transfer_qty)->min();
            if($min['transfer_qty'] <= 0){ // if there are 0 return qty
                return redirect()->back()->with('error', 'Return Qty cannot be less than or equal to 0');
            }

            $bin = DB::table('tabBin as bin')->join('tabItem as item', 'item.item_code', 'bin.item_code')
                ->whereIn('bin.warehouse', array_filter([$source_warehouse, $target_warehouse]))->whereIn('bin.item_code', $item_codes)
                ->select('item.item_code', 'item.description', 'item.item_name', 'bin.warehouse', 'item.stock_uom', 'bin.actual_qty', 'bin.consigned_qty')->get();
            
            $items = [];
            foreach($bin as $b){
                $items[$b->warehouse][$b->item_code] = [
                    'description' => $b->description,
                    'item_name' => $b->item_name,
                    'uom' => $b->stock_uom,
                    'actual_qty' => $b->actual_qty,
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->where('status', 'Approved')->where('branch_warehouse', $reference_warehouse)->pluck('name');
            $inventory_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $beginning_inventory)->whereIn('item_code', $item_codes)->where('status', 'Approved')->select('item_code', 'price')->get();

            $inventory_prices = [];
            foreach($inventory_items as $item){
                $inventory_prices[$item->item_code] = [
                    'price' => $item->price,
                    'amount' => isset($transfer_qty[$item->item_code]) ? preg_replace("/[^0-9 .]/", "", $transfer_qty[$item->item_code]['transfer_qty']) * $item->price : $item->price
                ];
            }

            $latest_ste = DB::table('tabStock Entry')->where('naming_series', 'STEC-')->max('name');
            $latest_ste_exploded = explode("-", $latest_ste);
            $new_id = (($latest_ste) ? $latest_ste_exploded[1] : 0) + 1;
            $new_id = str_pad($new_id, 6, '0', STR_PAD_LEFT);
            $new_id = 'STEC-'.$new_id;

            $stock_entry_data = [
                'name' => $new_id,
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->full_name,
                'owner' => Auth::user()->full_name,
                'docstatus' => 0,
                'idx' => 0,
                'use_multi_level_bom' => 0,
                'naming_series' => 'STEC-',
                'posting_time' => $now->format('H:i:s'),
                'to_warehouse' => $target_warehouse,
                'title' => $request->transfer_as == 'Sales Return' ? 'Material Receipt' : 'Material Transfer',
                'from_warehouse' => $source_warehouse,
                'set_posting_time' => 0,
                'from_bom' => 0,
                'value_difference' => 0,
                'company' => 'FUMACO Inc.',
                'total_outgoing_value' => collect($inventory_prices)->sum('amount'),
                'total_additional_costs' => 0,
                'total_amount' => collect($inventory_prices)->sum('amount'),
                'total_incoming_value' => collect($inventory_prices)->sum('amount'),
                'posting_date' => $now->format('Y-m-d'),
                'purpose' => $request->transfer_as == 'Sales Return' ? 'Material Receipt' : 'Material Transfer',
                'stock_entry_type' => $request->transfer_as == 'Sales Return' ? 'Material Receipt' : 'Material Transfer',
                'item_status' => 'Issued',
                'transfer_as' => $request->transfer_as == 'Sales Return' ? null : $request->transfer_as,
                'receive_as' => $request->transfer_as == 'Sales Return' ? $request->transfer_as : null,
                'qty_repack' => 0,
                // 'customer_1' => $target_warehouse,
                'delivery_date' => $now->format('Y-m-d'),
                'remarks' => 'Generated in AthenaERP'
            ];

            DB::table('tabStock Entry')->insert($stock_entry_data);
      
            $logs = [
                'name' => uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => $request->transfer_as . ' request from ' . $request->source_warehouse . ' to ' . $target_warehouse. ' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Entry',
                'reference_name' => $new_id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            DB::table('tabActivity Log')->insert($logs);

            foreach($item_codes as $i => $item_code){
                if(!isset($transfer_qty[$item_code])){
                    return redirect()->back()->with('error', 'Please enter transfer qty for '. $item_code);
                }

                if(isset($items[$reference_warehouse][$item_code])){
                    if($transfer_qty[$item_code]['transfer_qty'] > $items[$reference_warehouse][$item_code]['consigned_qty']){
                        return redirect()->back()->with('error', 'Transfer qty cannot be more than the stock qty.');
                    }
                }

                $stock_entry_detail = [
                    'name' =>  uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->full_name,
                    'owner' => Auth::user()->full_name,
                    'docstatus' => 0,
                    'parent' => $new_id,
                    'parentfield' => 'items',
                    'parenttype' => 'Stock Entry',
                    'idx' => $i + 1,
                    't_warehouse' => $target_warehouse,
                    'transfer_qty' => $transfer_qty[$item_code]['transfer_qty'],
                    'expense_account' => 'Cost of Goods Sold - FI',
                    'cost_center' => 'Main - FI',
                    'actual_qty' => isset($items[$reference_warehouse][$item_code]) ? $items[$reference_warehouse][$item_code]['actual_qty'] : 0,
                    's_warehouse' => $source_warehouse,
                    'item_name' => isset($items[$reference_warehouse][$item_code]) ? $items[$reference_warehouse][$item_code]['item_name'] : null,
                    'additional_cost' => 0,
                    'stock_uom' => isset($items[$reference_warehouse][$item_code]) ? $items[$reference_warehouse][$item_code]['uom'] : null,
                    'basic_amount' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['amount'] : 0,
                    'custom_basic_amount' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['amount'] : 0,
                    'sample_quantity' => 0,
                    'uom' => isset($items[$reference_warehouse][$item_code]) ? $items[$reference_warehouse][$item_code]['uom'] : null,
                    'basic_rate' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['price'] : 0,
                    'custom_basic_rate' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['price'] : 0,
                    'description' => isset($items[$reference_warehouse][$item_code]) ? $items[$reference_warehouse][$item_code]['description'] : null,
                    'conversion_factor' => 1,
                    'item_code' => $item_code,
                    'validate_item_code' => $item_code,
                    'retain_sample' => 0,
                    'qty' => $transfer_qty[$item_code]['transfer_qty'],
                    'allow_zero_valuation_rate' => 0,
                    'amount' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['amount'] : 0,
                    'valuation_rate' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['price'] : 0,
                    'target_warehouse_location' => $target_warehouse,
                    'source_warehouse_location' => $source_warehouse,
                    'status' => 'Issued',
                    'return_reference' => $new_id,
                    'session_user' => Auth::user()->full_name,
                    'issued_qty' => $transfer_qty[$item_code]['transfer_qty'],
                    'date_modified' => $now->toDateTimeString(),
                    'remarks' => 'Generated in AthenaERP'
                ];

                DB::table('tabStock Entry Detail')->insert($stock_entry_detail);

                // source warehouse
                if($request->transfer_as == 'For Return' && isset($items[$reference_warehouse][$item_code])){
                    DB::table('tabBin')->where('warehouse', $reference_warehouse)->where('item_code', $item_code)->update([
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->full_name,
                        'consigned_qty' => $items[$reference_warehouse][$item_code]['consigned_qty'] - $transfer_qty[$item_code]['transfer_qty']
                    ]);
                }

                // target warehouse
                if($request->transfer_as != 'Store Transfer'){
                    if(isset($items[$target_warehouse][$item_code])){
                        DB::table('tabBin')->where('warehouse', $target_warehouse)->where('item_code', $item_code)->update([
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->full_name,
                            'consigned_qty' => $items[$target_warehouse][$item_code]['consigned_qty'] + $transfer_qty[$item_code]['transfer_qty']
                        ]);
                    }else{
                        $latest_bin = DB::table('tabBin')->where('name', 'like', '%bin/%')->max('name');
                        $latest_bin_exploded = explode("/", $latest_bin);
                        $bin_id = (($latest_bin) ? $latest_bin_exploded[1] : 0) + 1;
                        $bin_id = str_pad($bin_id, 7, '0', STR_PAD_LEFT);
                        $bin_id = 'BIN/'.$bin_id;
    
                        DB::table('tabBin')->insert([
                            'name' => $bin_id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->full_name,
                            'owner' => Auth::user()->full_name,
                            'docstatus' => 0,
                            'idx' => 0,
                            'warehouse' => $target_warehouse,
                            'item_code' => $item_code,
                            'stock_uom' => isset($items[$target_warehouse][$item_code]) ? $items[$target_warehouse][$item_code]['uom'] : null,
                            'valuation_rate' => isset($inventory_prices[$item_code]) ? $inventory_prices[$item_code]['price'] : 0,
                            'consigned_qty' => $transfer_qty[$item_code]['transfer_qty']
                        ]);
                    }
                }
            }

            $purpose = $request->transfer_as == 'Sales Return' ? 'Material Receipt' : 'Material Transfer';

            DB::commit();
            return redirect()->route('stock_transfers', ['purpose' => $purpose])->with('success', 'Stock transfer request has been submitted.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function stockTransferForm(){
        $all_consignment_stores = DB::table('tabAssigned Consignment Warehouse')->select('parent', 'warehouse')->get();
        
        $consignment_stores = collect($all_consignment_stores)->map(function($q){
            return $q->warehouse;
        });

        $assigned_consignment_stores = collect($all_consignment_stores)->map(function($q){
            if($q->parent == Auth::user()->frappe_userid){
                return $q->warehouse;
            }
        })->filter();

        return view('consignment.stock_transfer_form', compact('assigned_consignment_stores', 'consignment_stores'));
    }

    public function stockTransferCancel($id){
        DB::beginTransaction();
        try {
            $stock_entry = DB::table('tabStock Entry')->where('name', $id)->first();

            if(!$stock_entry){
                return redirect()->back()->with('error', 'Stock Entry does not exist or Stock Entry is already deleted.');
            }

            $source_warehouse = $stock_entry->from_warehouse;
            $target_warehouse = $stock_entry->to_warehouse;

            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry->name)->get();
            
            $item_codes = collect($stock_entry_detail)->map(function ($q){
                return $q->item_code;
            });

            $now = Carbon::now();
            
            $bin = DB::table('tabBin')->whereIn('warehouse', array_filter([$source_warehouse, $target_warehouse]))->whereIn('item_code', $item_codes)->get();

            $bin_arr = [];
            foreach($bin as $b){
                $bin_arr[$b->warehouse][$b->item_code] = [
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            foreach($stock_entry_detail as $items){
                if($stock_entry->purpose == 'Material Transfer'){ // Stock Transfers and Returns
                    if(!isset($bin_arr[$items->s_warehouse][$items->item_code]) || !isset($bin_arr[$items->t_warehouse][$items->item_code])){
                        return redirect()->back()->with('error', 'Items not found.');
                    }
                }else{ // Sales Returns
                    if(!isset($bin_arr[$items->t_warehouse][$items->item_code])){
                        return redirect()->back()->with('error', 'Items not found.');
                    }
                }

                if($stock_entry->transfer_as != 'Store Transfer'){
                    // target warehouse
                    $target_warehouse_qty = $bin_arr[$items->t_warehouse][$items->item_code]['consigned_qty'] - $items->transfer_qty;
                    $target_warehouse_qty = $target_warehouse_qty > 0 ? $target_warehouse_qty : 0;

                    DB::table('tabBin')->where('warehouse', $items->t_warehouse)->where('item_code', $items->item_code)->update([
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->full_name,
                        'consigned_qty' => $target_warehouse_qty
                    ]);

                    // source warehouse
                    if($stock_entry->purpose == 'Material Transfer'){ // Returns
                        DB::table('tabBin')->where('warehouse', $items->s_warehouse)->where('item_code', $items->item_code)->update([
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->full_name,
                            'consigned_qty' => $bin_arr[$items->s_warehouse][$items->item_code]['consigned_qty'] + $items->transfer_qty
                        ]);
                    }
                }
            }

            DB::table('tabStock Entry')->where('name', $id)->delete();
            DB::table('tabStock Entry Detail')->where('parent', $id)->delete();

            $transaction = null;
            if($stock_entry->purpose == 'Material Transfer'){
                if($stock_entry->transfer_as == 'Consignment'){
                    $transaction = 'Store Transfer';
                }else{
                    $transaction = 'Return to Plant';
                }
            }else{
                $transaction = 'Sales Return';
            }

            DB::commit();
            return redirect()->route('stock_transfers', ['purpose' => $stock_entry->purpose])->with('success', $transaction.' has been cancelled.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function stockTransferList($purpose){
        $consignment_stores = [];
        if(Auth::user()->user_group == 'Promodiser'){
            $consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        }

        $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();

        $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

        $stock_transfers = DB::table('tabStock Entry')
            ->when(Auth::user()->user_group == 'Promodiser', function ($q) use ($consignment_stores, $purpose){
                return $q->whereIn(($purpose == 'Material Transfer' ? 'from_warehouse' : 'to_warehouse'), $consignment_stores);
            })
            ->where('purpose', $purpose)
            ->when($purpose == 'Material Transfer', function ($q){
                return $q->whereIn('transfer_as', ['For Return', 'Sales Return', 'Store Transfer']);
            })
            ->when($purpose == 'Material Receipt', function ($q){
                return $q->where('receive_as', 'Sales Return');
            })
            ->whereDate('delivery_date', '>=', $beginning_inventory_start_date)
            ->where('docstatus', '<', 2)
            ->orderBy('creation', 'desc')->paginate(10);

        $src_warehouses = collect($stock_transfers->items())->map(function ($q){
            return $q->from_warehouse;
        });

        $reference_ste = collect($stock_transfers->items())->map(function ($q){
            return $q->name;
        });

        $stock_transfer_items = DB::table('tabStock Entry Detail')->whereIn('parent', $reference_ste)->get();
        $stock_transfer_item = collect($stock_transfer_items)->groupBy('parent');
        
        $item_codes = collect($stock_transfer_items)->map(function ($q){
            return $q->item_code;
        });

        $bin = DB::table('tabBin')->whereIn('warehouse', $src_warehouses)->whereIn('item_code', $item_codes)->get();
        $bin_arr = [];
        foreach($bin as $b){
            $bin_arr[$b->warehouse][$b->item_code] = [
                'consigned_qty' => $b->consigned_qty
            ];
        }

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->get();
        $item_image = collect($item_images)->groupBy('parent');

        $ste_arr = [];
        foreach($stock_transfers as $ste){
            $items_arr = [];
            if(isset($stock_transfer_item[$ste->name])){
                foreach($stock_transfer_item[$ste->name] as $item){
                    $orig_exists = 0;
                    $webp_exists = 0;

                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';

                    if(isset($item_image[$item->item_code])){
                        $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$item->item_code][0]->image_path) ? 1 : 0;
                        $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                        $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : null;
                        $img = $orig_exists == 1 ? '/img/'.$item_image[$item->item_code][0]->image_path : null;

                        if($orig_exists == 0 && $webp_exists == 0){
                            $img = '/icon/no_img.png';
                            $webp = '/icon/no_img.webp';
                        }
                    }

                    $items_arr[] = [
                        'item_code' => $item->item_code,
                        'description' => $item->description,
                        'consigned_qty' => isset($bin_arr[$ste->from_warehouse][$item->item_code]) ? $bin_arr[$ste->from_warehouse][$item->item_code]['consigned_qty'] : 0,
                        'transfer_qty' => $item->transfer_qty,
                        'uom' => $item->stock_uom,
                        'image' => $img,
                        'webp' => $webp,
                        'img_count' => array_key_exists($item->item_code, $item_image) ? count($item_image[$item->item_code]) : 0
                    ];
                }
            }

            $ste_arr[] = [
                'name' => $ste->name,
                'from_warehouse' => $ste->from_warehouse,
                'to_warehouse' => $ste->to_warehouse,
                'status' => $ste->item_status,
                'items' => $items_arr,
                'owner' => $ste->owner,
                'docstatus' => $ste->docstatus,
                'transfer_type' => $ste->transfer_as,
                'date' => $ste->creation
            ];
        }

        return view('consignment.stock_transfers_list', compact('stock_transfers', 'ste_arr', 'purpose'));
    }

    public function viewInventoryAuditList(Request $request) {
        $select_year = [];
        for ($i = 2022; $i <= date('Y') ; $i++) { 
            $select_year[] = $i;
        }
        
        $assigned_consignment_stores = [];
        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? true : false;
        if ($is_promodiser) {
            $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')
                ->where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')
                ->distinct()->pluck('warehouse');

            $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory as w')
                ->where('status', 'Approved')->whereIn('branch_warehouse', $assigned_consignment_stores)
                ->orderBy('branch_warehouse', 'asc')
                ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
                ->toArray();
    
            $inventory_audit_per_warehouse = DB::table('tabConsignment Inventory Audit')
                ->whereIn('branch_warehouse', array_keys($stores_with_beginning_inventory))
                ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
                ->toArray();
    
            $end = Carbon::now()->endOfDay();
    
            $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        
            $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;
            $cutoff_2 = $sales_report_deadline ? $sales_report_deadline->{'2nd_cutoff_date'} : 0;

            $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();
            $second_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_2 .'/'. $end->format('Y'))->endOfDay();
    
            if ($first_cutoff->gt($end)) {
                $end = $first_cutoff;
            }
    
            if ($second_cutoff->gt($end)) {
                $end = $second_cutoff;
            }
    
            $cutoff_date = $this->getCutoffDate($end->endOfDay());
            $period_from = $cutoff_date[0];
            $period_to = $cutoff_date[1];    
    
            $pending_arr = [];
            foreach ($assigned_consignment_stores as $store) {
                $beginning_inventory_transaction_date = array_key_exists($store, $stores_with_beginning_inventory) ? $stores_with_beginning_inventory[$store] : null;
                $last_inventory_audit_date = array_key_exists($store, $inventory_audit_per_warehouse) ? $inventory_audit_per_warehouse[$store] : null;
    
                $duration = $start = null;
                if ($beginning_inventory_transaction_date) {
                    $start = Carbon::parse($beginning_inventory_transaction_date);
                }
    
                if ($last_inventory_audit_date) {
                    $start = Carbon::parse($last_inventory_audit_date);
                }

                if ($start) {
                    $last_audit_date = $start;
    
                    $start = $start->startOfDay();
        
                    $is_late = 0;
                    $period = CarbonPeriod::create($start, '28 days' , $end);
                    foreach ($period as $date) {
                        $date1 = $date->day($cutoff_1);
                        if ($date1 >= $start && $date1 <= $end) {
                            $is_late++;
                        }
                        $date2 = $date->day($cutoff_2);
                        if ($date2 >= $start && $date2 <= $end) {
                            $is_late++;
                        }
                    }
        
                    $check = Carbon::parse($start)->between($period_from, $period_to);
    
                    $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . Carbon::now()->format('F d, Y');
                    if ($last_audit_date->endOfDay()->lt($end) && $beginning_inventory_transaction_date) {
                        if (!$check) {
                            $pending_arr[] = [
                                'store' => $store,
                                'beginning_inventory_date' => $beginning_inventory_transaction_date,
                                'last_inventory_audit_date' => $last_inventory_audit_date,
                                'duration' => $duration,
                                'is_late' => $is_late,
                                'today' => Carbon::now()->format('Y-m-d'),
                            ];
                        }
                    }
                }

                if(!$beginning_inventory_transaction_date) {
                    $pending_arr[] = [
                        'store' => $store,
                        'beginning_inventory_date' => $beginning_inventory_transaction_date,
                        'last_inventory_audit_date' => null,
                        'duration' => $duration,
                        'is_late' => 0,
                        'today' => Carbon::now()->format('Y-m-d'),
                    ];
                }  
            }
    
            $pending = collect($pending_arr)->groupBy('store');

            return view('consignment.promodiser_inventory_audit_list', compact('pending', 'assigned_consignment_stores', 'select_year'));
        }

        return view('consignment.supervisor.view_inventory_audit', compact('assigned_consignment_stores', 'select_year'));
    }

    public function getSubmittedInvAudit(Request $request) {
        $store = $request->store;
        $year = $request->year;

        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? true : false;
        if ($is_promodiser) {
            $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')
                ->where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')
                ->distinct()->pluck('warehouse');

            $query = DB::table('tabConsignment Inventory Audit')
                ->when($store, function ($q) use ($store){
                    return $q->where('branch_warehouse', $store);
                })
                ->when($year, function ($q) use ($year){
                    return $q->whereYear('audit_date_from', $year);
                })
                ->whereIn('branch_warehouse', $assigned_consignment_stores)
                ->select('audit_date_from', 'audit_date_to', 'branch_warehouse', 'status')
                ->groupBy('branch_warehouse', 'audit_date_to', 'audit_date_from', 'status')
                ->orderBy('audit_date_from', 'desc')
                ->paginate(10);

            $result = [];
            foreach ($query as $row) {
                $total_sales = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $row->branch_warehouse)
                ->where('status', '!=', 'Cancelled')
                    ->whereBetween('transaction_date', [$row->audit_date_from, $row->audit_date_to])->sum('amount');

                $result[$row->branch_warehouse][] = [
                    'audit_date_from' => $row->audit_date_from,
                    'audit_date_to' => $row->audit_date_to,
                    'status' => $row->status,
                    'total_sales' => $total_sales,
                ];
            }

            return view('consignment.tbl_submitted_inventory_audit', compact('result', 'query'));
        }

        $list = DB::table('tabConsignment Inventory Audit')
            ->when($store, function ($q) use ($store){
                return $q->where('branch_warehouse', $store);
            })
            ->when($year, function ($q) use ($year){
                return $q->whereYear('audit_date_from', $year);
            })
            ->selectRaw('audit_date_from, audit_date_to, branch_warehouse, transaction_date, GROUP_CONCAT(DISTINCT promodiser ORDER BY promodiser ASC SEPARATOR ",") as promodiser')
            ->groupBy('branch_warehouse', 'audit_date_to', 'audit_date_from', 'transaction_date')->paginate(10);

        $result = [];
        foreach ($list as $row) {
            $total_sales = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $row->branch_warehouse)
                ->where('status', '!=', 'Cancelled')->whereBetween('transaction_date', [$row->audit_date_from, $row->audit_date_to])->sum('amount');

            $total_qty_sold = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $row->branch_warehouse)
                ->where('status', '!=', 'Cancelled')->whereBetween('transaction_date', [$row->audit_date_from, $row->audit_date_to])->sum('qty');

            $result[] = [
                'transaction_date' => $row->transaction_date,
                'audit_date_from' => $row->audit_date_from,
                'audit_date_to' => $row->audit_date_to,
                'branch_warehouse' => $row->branch_warehouse,
                'total_sales' => $total_sales,
                'total_qty_sold' => $total_qty_sold,
                'promodiser' => $row->promodiser
            ];
        }

        return view('consignment.supervisor.tbl_inventory_audit_history', compact('list', 'result'));
    }

    public function viewInventoryAuditItems($store, $from, $to) {
        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? true : false;

        $list = DB::table('tabConsignment Inventory Audit')
            ->where('branch_warehouse', $store)->where('audit_date_from', $from)
            ->where('audit_date_to', $to)->get();

        $product_sold_query = DB::table('tabConsignment Product Sold')->where('branch_warehouse', $store)
            ->where('status', '!=', 'Cancelled')->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('SUM(qty) as sold_qty, SUM(amount) as total_value, item_code')
            ->groupBy('item_code')->get();

        $total_sales = collect($product_sold_query)->sum('total_value');

        $product_sold = collect($product_sold_query)->groupBy('item_code')->toArray();
        
        $duration = Carbon::parse($from)->format('F d, Y') . ' - ' . Carbon::parse($to)->format('F d, Y');

        $item_codes = collect($list)->pluck('item_code');

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->where('cb.status', 'Approved')->whereIn('cbi.item_code', $item_codes)->where('cb.branch_warehouse', $store)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($to))
            ->select('cbi.item_code', 'cb.transaction_date', 'opening_stock')
            ->orderBy('cb.transaction_date', 'desc')->get();

        $beginning_inventory = collect($beginning_inventory)->groupBy('item_code')->toArray();

        $inv_audit = DB::table('tabConsignment Inventory Audit')
            ->where('branch_warehouse', $store)->where('transaction_date', '<', $from)
            ->select('item_code', 'qty', 'transaction_date')
            ->orderBy('transaction_date', 'asc')->get();

        $inv_audit = collect($inv_audit)->groupBy('item_code')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent')->toArray();

        $result = [];
        foreach ($list as $row) {
            $orig_exists = 0;
            $webp_exists = 0;

            $img = '/icon/no_img.png';
            $webp = '/icon/no_img.webp';

            if(isset($item_image[$row->item_code])){
                $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$row->item_code][0]->image_path) ? 1 : 0;
                $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$row->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$row->item_code][0]->image_path)[0].'.webp' : null;
                $img = $orig_exists == 1 ? '/img/'.$item_image[$row->item_code][0]->image_path : null;

                if($orig_exists == 0 && $webp_exists == 0){
                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';
                }
            }

            $id = $row->item_code;
            $img_count = array_key_exists($id, $item_image) ? count($item_image[$id]) : 0;
            $total_sold = array_key_exists($id, $product_sold) ? $product_sold[$id][0]->sold_qty : 0;
            $total_value = array_key_exists($id, $product_sold) ? $product_sold[$id][0]->total_value : 0;
            $opening_qty = array_key_exists($id, $inv_audit) ? $inv_audit[$id][0]->qty : 0;

            if (array_key_exists($id, $inv_audit)) {
                $opening_qty = $inv_audit[$id][0]->qty;
            } else {
                $opening_qty = array_key_exists($id, $beginning_inventory) ? $beginning_inventory[$id][0]->opening_stock : 0;
            }
            
            $result[] = [
                'item_code' => $id,
                'description' => $row->description,
                'img' => $img,
                'img_webp' => $webp,
                'img_count' => $img_count,
                'total_value' => $total_value,
                'opening_qty' => number_format($opening_qty),
                'sold_qty' => $total_sold,
                'audit_qty' => number_format($row->qty)
            ];
        }

        if($is_promodiser) {
            return view('consignment.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'total_sales'));
        }

        $promodisers = DB::table('tabConsignment Inventory Audit')
            ->where('branch_warehouse', $store)->where('audit_date_from', $from)
            ->where('audit_date_to', $to)->distinct()->pluck('promodiser')->toArray();
            
        $promodisers = implode(', ', $promodisers);

        return view('consignment.supervisor.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'promodisers'));
    }

    public function submitStockAdjustment(Request $request, $id){
        DB::beginTransaction();
        try {
            $item_codes = array_keys($request->item);
            $stocks = $request->item;

            $now = Carbon::now();

            $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->first();
            if(!$beginning_inventory){
                return redirect()->back()->with('error', 'Record not found or has been deleted.');
            }

            $bin = DB::table('tabBin')->where('warehouse', $beginning_inventory->branch_warehouse)->whereIn('item_code', $item_codes)->select('name', 'item_code', 'consigned_qty')->get();
            $bin = collect($bin)->groupBy('item_code');

            foreach($item_codes as $item_code){
                if(isset($stocks[$item_code])){
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->where('item_code', $item_code)->update([
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->user_group == 'Consignment Supervisor' ? Auth::user()->wh_user : Auth::user()->full_name,
                        'opening_stock' => preg_replace("/[^0-9]/", "", $stocks[$item_code]['qty'])
                    ]);

                    DB::table('tabBin')->where('warehouse', $beginning_inventory->branch_warehouse)->where('item_code', $item_code)->update([
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->user_group == 'Consignment Supervisor' ? Auth::user()->wh_user : Auth::user()->full_name,
                        'consigned_qty' => preg_replace("/[^0-9]/", "", $stocks[$item_code]['qty'])
                    ]);

                    $previous_stock = isset($bin[$item_code]) ? $bin[$item_code][0]->consigned_qty : 0;
                    $previous_stock = number_format($previous_stock);

                    $logs = [
                        'name' => uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'idx' => 0,
                        'subject' => 'Stock Adjustment for '.$beginning_inventory->branch_warehouse.', set '.$item_code.' consigned qty from '.$previous_stock.' to '.preg_replace("/[^0-9]/", "", $stocks[$item_code]['qty']).' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                        'content' => 'Consignment Activity Log',
                        'communication_date' => $now->toDateTimeString(),
                        'reference_doctype' => 'Stock Adjustment',
                        'reference_name' => isset($bin[$item_code]) ? $bin[$item_code][0]->name : null,
                        'reference_owner' => Auth::user()->wh_user,
                        'user' => Auth::user()->wh_user,
                        'full_name' => Auth::user()->full_name,
                    ];
        
                    DB::table('tabActivity Log')->insert($logs);
                }
            }

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update([
                'modified' => $now,
                'modified_by' => Auth::user()->user_group == 'Consignment Supervisor' ? Auth::user()->wh_user : Auth::user()->full_name
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse Stocks Adjusted.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function getPendingSubmissionInventoryAudit(Request $request) {
        $store = $request->store;

        $promodisers_query = DB::table('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->where('user_group', 'Promodiser')->selectRaw('GROUP_CONCAT(DISTINCT wu.full_name ORDER BY wu.full_name ASC SEPARATOR ",") as full_name, acw.warehouse')
            ->groupBy('acw.warehouse')->pluck('full_name', 'warehouse')->toArray();

        $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory as w')
            ->where('status', 'Approved')->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->when($store, function ($q) use ($store){
                return $q->where('branch_warehouse', $store);
            })
            ->orderBy('branch_warehouse', 'asc')->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')->toArray();

        $inventory_audit_per_warehouse = DB::table('tabConsignment Inventory Audit')
            ->whereIn('branch_warehouse', array_keys($stores_with_beginning_inventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = Carbon::now()->endOfDay();

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
    
        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;
        $cutoff_2 = $sales_report_deadline ? $sales_report_deadline->{'2nd_cutoff_date'} : 0;

        $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();
        $second_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_2 .'/'. $end->format('Y'))->endOfDay();

        if ($first_cutoff->gt($end)) {
            $end = $first_cutoff;
        }

        if ($second_cutoff->gt($end)) {
            $end = $second_cutoff;
        }

        $cutoff_date = $this->getCutoffDate($end->endOfDay());
        $period_from = $cutoff_date[0];
        $period_to = $cutoff_date[1];

        $pending = [];
        foreach (array_keys($stores_with_beginning_inventory) as $store) {
            $beginning_inventory_transaction_date = array_key_exists($store, $stores_with_beginning_inventory) ? $stores_with_beginning_inventory[$store] : null;
            $last_inventory_audit_date = array_key_exists($store, $inventory_audit_per_warehouse) ? $inventory_audit_per_warehouse[$store] : null;

            $promodisers = array_key_exists($store, $promodisers_query) ? $promodisers_query[$store] : null;

            $duration = null;
            if ($beginning_inventory_transaction_date) {
                $start = Carbon::parse($beginning_inventory_transaction_date);
            }

            if ($last_inventory_audit_date) {
                $start = Carbon::parse($last_inventory_audit_date);
            }

            $last_audit_date = $start;

            $start = $start->startOfDay();

            $is_late = 0;
            $period = CarbonPeriod::create($start, '28 days' , $end);
            foreach ($period as $date) {
                $date1 = $date->day($cutoff_1);
                if ($date1 >= $start && $date1 <= $end) {
                    $is_late++;
                }
                $date2 = $date->day($cutoff_2);
                if ($date2 >= $start && $date2 <= $end) {
                    $is_late++;
                }
            }
   
            $check = Carbon::parse($start)->between($period_from, $period_to);
    
            $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . Carbon::now()->format('F d, Y');
            if ($last_audit_date->endOfDay()->lt($end) && $beginning_inventory_transaction_date) {
                if (!$check) {
                    $pending[] = [
                        'store' => $store,
                        'beginning_inventory_date' => $beginning_inventory_transaction_date,
                        'last_inventory_audit_date' => $last_inventory_audit_date,
                        'duration' => $duration,
                        'is_late' => $is_late,
                        'promodisers' => $promodisers
                    ];
                }
             }

             if(!$beginning_inventory_transaction_date) {
                 if (!$check) {
                    $pending[] = [
                        'store' => $store,
                        'beginning_inventory_date' => $beginning_inventory_transaction_date,
                        'last_inventory_audit_date' => $last_inventory_audit_date,
                        'duration' => $duration,
                        'is_late' => $is_late,
                        'promodisers' => $promodisers
                    ];
                }
            }
        }

        return view('consignment.supervisor.tbl_pending_submission_inventory_audit', compact('pending'));
    }

    public function viewSalesReport() {
        $select_year = [];
        for ($i = 2022; $i <= date('Y') ; $i++) { 
            $select_year[] = $i;
        }

        return view('consignment.supervisor.view_product_sold_list', compact('select_year'));
    }

    public function productSoldList(Request $request) {
        $store = $request->store;
        $year = $request->year;

        $list = DB::table('tabConsignment Product Sold')
            ->when($store, function ($q) use ($store){
                return $q->where('branch_warehouse', $store);
            })
            ->when($year, function ($q) use ($year){
                return $q->whereYear('cutoff_period_from', $year);
            })
            ->where('status', '!=', 'Cancelled')
            ->selectRaw('branch_warehouse, cutoff_period_from, cutoff_period_to, SUM(qty) as total_sold, SUM(amount) as total_amount, COUNT(DISTINCT item_code) as total_item, GROUP_CONCAT(DISTINCT promodiser ORDER BY promodiser ASC SEPARATOR ",") as promodisers')
            ->orderBy('transaction_date', 'desc')->groupBy('branch_warehouse', 'cutoff_period_from', 'cutoff_period_to')
            ->paginate(20);

        return view('consignment.supervisor.tbl_product_sold_history', compact('list'));
    }

    public function viewProductSoldItems($store, $from, $to) {
        $list = DB::table('tabConsignment Product Sold')
            ->where('status', '!=', 'Cancelled')
            ->where('branch_warehouse', $store)
            ->where('cutoff_period_from', $from)
            ->where('cutoff_period_to', $to)
            ->selectRaw('item_code, description, SUM(qty) as qty, SUM(amount) as amount')
            ->orderBy('description', 'asc')->groupBy('item_code', 'description')->get();

        $promodisers = DB::table('tabConsignment Product Sold')
            ->where('status', '!=', 'Cancelled')
            ->where('branch_warehouse', $store)->where('cutoff_period_from', $from)
            ->where('cutoff_period_to', $to)->distinct()->pluck('promodiser')->toArray();
            
        $promodisers = implode(', ', $promodisers);
           
        $duration = Carbon::parse($from)->format('F d, Y') . ' - ' . Carbon::parse($to)->format('F d, Y');

        $item_codes = collect($list)->pluck('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_image = collect($item_images)->groupBy('parent')->toArray();

        $result = [];
        foreach ($list as $row) {
            $id = $row->item_code;

            $orig_exists = 0;
            $webp_exists = 0;

            $img = '/icon/no_img.png';
            $webp = '/icon/no_img.webp';

            if(isset($item_image[$id])){
                $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$id][0]->image_path) ? 1 : 0;
                $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$id][0]->image_path)[0].'.webp') ? 1 : 0;

                $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$id][0]->image_path)[0].'.webp' : null;
                $img = $orig_exists == 1 ? '/img/'.$item_image[$id][0]->image_path : null;

                if($orig_exists == 0 && $webp_exists == 0){
                    $img = '/icon/no_img.png';
                    $webp = '/icon/no_img.webp';
                }
            }

            $img_count = array_key_exists($id, $item_image) ? count($item_image[$id]) : 0;
            
            $result[] = [
                'item_code' => $id,
                'description' => $row->description,
                'img' => $img,
                'img_webp' => $webp,
                'img_count' => $img_count,
                'qty' => $row->qty,
                'amount' => $row->amount
            ];
        }

        return view('consignment.supervisor.view_product_sold_items', compact('result', 'store', 'duration', 'list', 'promodisers'));
    }

    public function activityLogs(Request $request) {
        $logs = DB::table('tabActivity Log')->where('content', 'Consignment Activity Log')
            ->select('creation', 'subject', 'reference_name', 'full_name')
            ->orderBy('creation', 'desc')->paginate(20);

        return view('consignment.supervisor.tbl_activity_logs', compact('logs'));
    }

    public function viewPromodisersList() {
        if (Auth::user()->user_group != 'Consignment Supervisor') {
            return redirect('/');
        }

        $query = DB::table('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->where('wu.user_group', 'Promodiser')
            ->select('wu.wh_user', 'wu.last_login', 'wu.full_name', 'acw.warehouse')
            ->orderBy('wu.wh_user', 'asc')->get();

        $list = collect($query)->groupBy('wh_user')->toArray();

        $total_promodisers = count(array_keys($list));

        $result = [];
        foreach($list as $prmodiser => $row) {
            $result[] = [
                'promodiser_name' => $row[0]->full_name,
                'stores' => array_column($row, 'warehouse'),
                'last_login' => $row[0]->last_login,
            ];
        }

        $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory')
            ->where('status', 'Approved')->select('branch_warehouse', DB::raw('MIN(transaction_date) as transaction_date'))->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')->toArray();

        return view('consignment.supervisor.view_promodisers_list', compact('result', 'total_promodisers', 'stores_with_beginning_inventory'));
    }

    public function getAuditDeliveries(Request $request) {
        $store = $request->store;
        $cutoff = $request->cutoff;
        $cutoff_start = $cutoff_end = null;
        if ($cutoff) {
            $cutoff = explode('/', $request->cutoff);
            $cutoff_start = $cutoff[0];
            $cutoff_end = $cutoff[1];
        }

        $list = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)
            ->whereBetween('ste.delivery_date', [$cutoff_start, $cutoff_end])
            ->where('sted.t_warehouse', $store)
            ->select('ste.name', 'ste.delivery_date', 'sted.s_warehouse', 'sted.t_warehouse', 'ste.creation', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.basic_amount', 'ste.owner')->orderBy('ste.creation', 'desc')->get();

        return view('consignment.supervisor.tbl_audit_deliveries', compact('list'));
    }

    public function getAuditReturns(Request $request) {
        $store = $request->store;
        $cutoff = $request->cutoff;
        $cutoff_start = $cutoff_end = null;
        if ($cutoff) {
            $cutoff = explode('/', $request->cutoff);
            $cutoff_start = $cutoff[0];
            $cutoff_end = $cutoff[1];
        }

        $list = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('ste.delivery_date', [$cutoff_start, $cutoff_end])
            ->where('sted.t_warehouse', $store)
            ->where(function($q) {
                $q->whereIn('ste.transfer_as', ['For Return', 'Store Transfer'])
                    ->orWhereIn('ste.receive_as', ['Sales Return']);
            })
            ->whereIn('ste.purpose', ['Material Transfer', 'Material Receipt'])
            ->where('ste.docstatus', 1)
            ->select('ste.name', 'ste.delivery_date', 'sted.s_warehouse', 'sted.t_warehouse', 'ste.creation', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.basic_amount', 'ste.owner')
            ->orderBy('ste.creation', 'desc')->get();

        return view('consignment.supervisor.tbl_audit_returns', compact('list'));
    }

    public function getAuditSales(Request $request) {
        $store = $request->store;
        $cutoff = $request->cutoff;
        $cutoff_start = $cutoff_end = null;
        if ($cutoff) {
            $cutoff = explode('/', $request->cutoff);
            $cutoff_start = $cutoff[0];
            $cutoff_end = $cutoff[1];
        }

        $query = DB::table('tabConsignment Product Sold')
            ->where('branch_warehouse', $store)
            ->whereBetween('transaction_date', [$cutoff_start, $cutoff_end])
            ->where('status', '!=', 'Cancelled')
            ->select('item_code', 'description', 'qty', 'price', 'amount', 'transaction_date', 'promodiser', 'available_stock_on_transaction')
            ->orderBy('description', 'asc')->orderBy('transaction_date', 'desc')->get();

        $ending_inventory = collect($query)->groupBy('item_code')->toArray();

        $promodisers = collect($query)->pluck('promodiser')->unique()->toArray();

        $item_codes = collect($query)->pluck('item_code');

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->where('cb.status', 'Approved')->whereIn('cbi.item_code', $item_codes)
            ->where('cb.branch_warehouse', $store)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($cutoff_end))
            ->select('cbi.item_code', 'cb.transaction_date', 'opening_stock')
            ->orderBy('cb.transaction_date', 'desc')->get();

        $beginning_inventory = collect($beginning_inventory)->groupBy('item_code')->toArray();

        $inv_audit = DB::table('tabConsignment Inventory Audit')
            ->where('branch_warehouse', $store)->where('transaction_date', '<', $cutoff_start)
            ->select('item_code', 'qty', 'transaction_date')
            ->orderBy('transaction_date', 'asc')->get();

        $inv_audit = collect($inv_audit)->groupBy('item_code')->toArray();

        $transaction_dates = collect($query)->pluck('transaction_date')->unique()->toArray();
        // sort array with given user-defined function
        usort($transaction_dates, function ($time1, $time2) {
            return strtotime($time1) - strtotime($time2);
        });

        $sales_query = $query->groupBy('item_code');

        $sales = [];
        $total_sales = $total_items = $total_qty_sold = 0;
        foreach ($sales_query as $item_code => $row) {
            $per_day = [];
            $amount = 0;
            foreach ($row as $r) {
                $per_day[$r->transaction_date] = $r->qty;
                $total_qty_sold += $r->qty;
                $amount += $r->amount;
            }

            $total_sales += $amount;
            $total_items++;

            if (array_key_exists($item_code, $inv_audit)) {
                $opening_qty = $inv_audit[$item_code][0]->qty;
            } else {
                $opening_qty = array_key_exists($item_code, $beginning_inventory) ? $beginning_inventory[$item_code][0]->opening_stock : 0;
            }

            $ending_qty = array_key_exists($item_code, $ending_inventory) ? $ending_inventory[$item_code][0]->available_stock_on_transaction - $ending_inventory[$item_code][0]->qty : 0;
           
            $sales[$item_code] = [
                'description' => $row[0]->description,
                'price' => $row[0]->price,
                'per_day' => $per_day,
                'amount' => $amount,
                'opening_qty' => $opening_qty,
                'ending_qty' => $ending_qty
            ];
        }

        $summary = [
            'total_items' => number_format($total_items),
            'total_qty_sold' => number_format($total_qty_sold),
            'total_sales' => '₱ ' . number_format($total_sales, 2),
            'promodisers' => implode(', ', $promodisers)
        ];

        return view('consignment.supervisor.tbl_audit_sales', compact('sales', 'transaction_dates', 'summary'));
    }
}