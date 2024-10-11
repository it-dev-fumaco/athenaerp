<?php

namespace App\Http\Controllers;

use App\Models\AssignedWarehouses;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Auth;
use DB;
use Storage;
use Cache;
use Mail;
use Illuminate\Support\Str;
use App\Mail\StockTransfersNotification;
use Exception;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;

use App\Models\ActivityLog;
use App\Models\BeginningInventory;
use App\Models\Bin;
use App\Models\ConsignmentDamagedItems;
use App\Models\ConsignmentStockAdjustment;
use App\Models\ConsignmentStockEntry;
use App\Models\ERPUser;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\StockEntry;
use App\Models\Warehouse;

use App\Traits\GeneralTrait;
use App\Traits\ERPTrait;
class ConsignmentController extends Controller
{
    use GeneralTrait, ERPTrait;
    public function viewSalesReportList($branch, Request $request) {
        $months = [];
        for ($m=1; $m<=12; $m++) {
            $months[] = date('F', mktime(0,0,0,$m, 1, date('Y')));
        }
        $currentYear = Carbon::now()->format('Y');
        $currentMonth = Carbon::now()->format('m');
       
        $years = [];
        for ($i = 2021; $i <= $currentYear; $i++) { 
            array_push($years, $i);
        }

        if ($request->ajax()) {
            $request_year = $request->year ? $request->year : $currentYear;
            $sales_per_month = DB::table('tabConsignment Monthly Sales Report')->where('fiscal_year', $request_year)->where('warehouse', $branch)->get()->groupBy('month');

            return view('consignment.tbl_sales_report', compact('months', 'sales_per_month', 'currentMonth', 'currentYear', 'request_year', 'branch'));
        }

        return view('consignment.view_sales_report_list', compact('years', 'currentYear', 'branch'));
    }

    public function salesReportDeadline(Request $request) {        
        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        if ($sales_report_deadline) {
            $cutoff_1 = $sales_report_deadline->{'1st_cutoff_date'};

            $calendarMonth = $request->month;
            $calendarYear = $request->year;

            $first_cutoff = Carbon::createFromFormat('m/d/Y', $calendarMonth .'/'. $cutoff_1 .'/'. $calendarYear)->format('F d, Y');

            return 'Deadline: ' . $first_cutoff;
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

    // /view_inventory_audit_form/{branch}/{transaction_date}
    public function viewInventoryAuditForm($branch, $transaction_date) {
        // get last inventory audit date

        $last_inventory_date = DB::table('tabConsignment Inventory Audit Report')
            ->where('branch_warehouse', $branch)->max('audit_date_to');

        if (!$last_inventory_date) {
            // get beginning inventory date if last inventory date is not null
            $last_inventory_date = DB::table('tabConsignment Beginning Inventory')
                ->where('status', 'Approved')->where('branch_warehouse', $branch)->max('transaction_date');
        }

        $inventory_audit_from = $last_inventory_date ? $last_inventory_date : Carbon::now()->format('Y-m-d'); 
        $inventory_audit_to = $transaction_date;

        $date_from = Carbon::parse($inventory_audit_from);

        if($date_from->startOfDay() < Carbon::now()->startOfDay()){
            $date_from = $date_from->addDay();
        }

        $duration = $date_from->format('F d, Y') . ' - ' . Carbon::parse($inventory_audit_to)->format('F d, Y');

        $start = $date_from->format('Y-m-d');
        $end = Carbon::parse($inventory_audit_to)->format('Y-m-d');

        $items = DB::table('tabBin as b')
            ->join('tabItem as i', 'i.name', 'b.item_code')
            ->where('b.warehouse', $branch)->where('b.consigned_qty', '>', 0)
            ->select('b.item_code', 'i.description', 'b.consignment_price as price', 'i.item_classification')//->union($sold_out_items)
            ->orderBy('i.description', 'asc')->get();

        $items = collect($items)->unique('item_code');
        $items = $items->sortBy('description');
        $item_count = count($items);
            
        $item_codes = collect($items)->pluck('item_code');
        
        $consigned_stocks = DB::table('tabBin')->whereIn('item_code', $item_codes)->where('warehouse', $branch)->pluck('consigned_qty', 'item_code')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $item_images = collect($item_images)->groupBy('parent')->toArray();

        $item_classification = collect($items)->groupBy('item_classification');

        return view('consignment.inventory_audit_form', compact('branch', 'transaction_date', 'items', 'item_images', 'duration', 'inventory_audit_from', 'inventory_audit_to', 'consigned_stocks', 'item_classification', 'item_count'));
    }

    // /consignment_stores
    public function consignmentStores(Request $request) {
        if ($request->ajax()) {
            if($request->has('assigned_to_me') && $request->assigned_to_me == 1){ // only get warehouses assigned to the promodiser
                return DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->where('warehouse', 'LIKE', '%'.$request->q.'%')->select('warehouse as id', 'warehouse as text')->limit(20)->orderBy('warehouse', 'asc')->get();
            }else{ // get all warehouses
                return DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
                    ->where('is_group', 0)->where('disabled', 0)->where('name','LIKE', '%'.$request->q.'%')
                    ->select('name as id', 'warehouse_name as text')->limit(20)->orderBy('warehouse_name', 'asc')->get();
            }
        }
    }

    // /submit_inventory_audit_form
    public function submitInventoryAuditForm(Request $request) {
        $data = $request->all();
        $state_before_update = $items_with_insufficient_stocks = [];

        try {
            $cutoff_date = $this->getCutoffDate($data['transaction_date']);
            $period_from = $cutoff_date[0];
            $period_to = $cutoff_date[1];

            // If user submits without qty input
            $null_qty_items = collect($data['item'])->where('qty', null);
            if(count($null_qty_items) > 0){
                return redirect()->back()->withInput($request->all())->with('error', 'Please enter the qty of all items.');
            }

            if($request->price && collect($request->price)->min() <= 0){
                return redirect()->back()->withInput($request->all())->with('error', 'Price cannot be less than or equal to 0');
            }

            $currentDateTime = Carbon::now();
            $no_of_items_updated = 0;

            $status = 'On Time';
            if ($currentDateTime->gt($period_to)) {
                $status = 'Late';
            }

            $period_from = Carbon::parse($cutoff_date[0])->format('Y-m-d');
            $period_to = Carbon::parse($cutoff_date[1])->format('Y-m-d');

            $iar_existing_record = DB::table('tabConsignment Inventory Audit Report')
                ->where('transaction_date', $data['transaction_date'])->where('branch_warehouse', $data['branch_warehouse'])
                ->pluck('name')
                ->first();

            $method = $iar_existing_record ? 'put' : 'post';

            $item_details = $data['item'];

            $bin_items = DB::table('tabItem as p')
                ->join('tabBin as c', 'c.item_code', 'p.name')->whereIn('p.item_code', array_keys($item_details))
                ->where('warehouse', $data['branch_warehouse'])->select('c.consigned_qty', 'p.item_code', 'p.description', 'c.consignment_price as price', 'c.name as bin_id', 'c.modified', 'c.modified_by')->get();

            $items = $sold_arr = [];
            foreach ($bin_items as $row) {
                $item_code = $row->item_code;

                if(!isset($item_details[$item_code])){
                    throw new Exception("Item $item_code not found.");
                }

                $item_detail = $item_details[$item_code];

                $qty = 0;
                if (isset($item_detail['qty'])) {
                    $qty = preg_replace("/[^0-9 .]/", "", $item_detail['qty']);
                }

                $item_description = $item_detail['description'];

                $consigned_qty = $row->consigned_qty;
                $price = $row->price;

                $sold_qty = ($consigned_qty - (float)$qty);

                if($sold_qty){
                    $sold_arr[] = [
                        'item' => $item_code,
                        'sold_qty' => $sold_qty,
                        'amount' => ((float)$price * (float)$sold_qty)
                    ];
                }

                $activity_log_data[$item_code] = [
                    'consigned_qty_before_transaction' => (float)$consigned_qty,
                    'sold_qty' => $sold_qty,
                    'expected_qty_after_transaction' => (float)$qty
                ];

                if ($consigned_qty < (float)$qty) {
                    $items_with_insufficient_stocks[] = $item_code;
                }

                $bin_update = [];

                if($qty != $row->consigned_qty){
                    $bin_update['consigned_qty'] = (float) $qty;
                }

                if($price <= 0 && isset($request->price[$item_code])){
                    $price = preg_replace("/[^0-9 .]/", "", $request->price[$item_code]);
                    $bin_update['consignment_price'] = $price;
                }

                if($bin_update){
                    $state_before_update['Bin'][$row->bin_id] = [
                        'consigned_qty' => $row->consigned_qty,
                        'consignment_price' => $row->price,
                        'modified' => $row->modified,
                        'modified_by' => $row->modified_by
                    ];

                    $bin_response = $this->erpOperation('put', 'Bin', $row->bin_id, $bin_update);

                    if(!isset($bin_response['data'])){
                        throw new Exception($bin_response['exception']);
                    }
                }
                
                $iar_amount = (float)$price * (float)$qty;

                $items[] = [
                    'item_code' => $item_code,
                    'description' => $item_description,
                    'qty' => (float)$qty,
                    'price' => (float)$price,
                    'amount' => $iar_amount,
                    'available_stock_on_transaction' => $consigned_qty
                ];
            }

            if(count($items_with_insufficient_stocks) > 0){
                throw new Exception('There are items with insufficient stocks.');
            }

            $data = [
                'transaction_date' => $data['transaction_date'],
                'branch_warehouse' => $data['branch_warehouse'],
                'grand_total' => collect($items)->sum('amount'),
                'promodiser' => Auth::user()->full_name,
                'status' => $status,
                'cutoff_period_from' => $period_from,
                'cutoff_period_to' => $period_to,
                'audit_date_from' => $data['audit_date_from'],
                'audit_date_to' => $data['audit_date_to'],
                'items' => $items
            ];

            $iar_response = $this->erpOperation($method, 'Consignment Inventory Audit Report', $iar_existing_record, $data);

            if(!isset($iar_response['data'])){
                throw new Exception($iar_response['exception']);
            }

            // get actual qty
            $updated_bin = DB::table('tabBin')->whereIn('item_code', array_keys($data['items']))->where('warehouse', $data['branch_warehouse'])->pluck('consigned_qty', 'item_code');
            foreach ($updated_bin as $item_code => $actual_qty) {
                $activity_log_data[$item_code]['actual_qty_after_transaction'] = (float)$actual_qty;
            }

            $logs = [
                'name' => uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Inventory Audit Report of '.$data['branch_warehouse'].' for cutoff periods '.$period_from.' - '.$period_to.'  has been created by '.Auth::user()->full_name.' at '.Carbon::now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => Carbon::now()->toDateTimeString(),
                'reference_doctype' => 'Inventory Audit Report',
                'reference_name' => $iar_existing_record,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activity_log_data, true)
            ];

            DB::table('tabActivity Log')->insert($logs);

            try {
                $email_data = collect($activity_log_data['details'])->merge(['reference' => $iar_existing_record])->toArray();

                Mail::send('mail_template.consignment_inventory_audit', $email_data, function($message){
                    $message->to(str_replace('.local', '.com', Auth::user()->wh_user));
                    $message->subject('AthenaERP - Inventory Audit Report');
                });
            } catch (\Throwable $th) {}

            return redirect()->back()->with([
                'success' => 'Record successfully updated',
                'total_qty_sold' => $sold_arr ? collect($sold_arr)->sum('sold_qty') : 0,
                'grand_total' => $sold_arr ? collect($sold_arr)->sum('amount') : 0,
                'branch' => $data['branch_warehouse'],
                'old_data' => $data,
                'transaction_date' => $data['transaction_date']
            ]);
        } catch (\Throwable $th) {
            // revert the changes
            $this->revertChanges($state_before_update);

            return redirect()->back()
                ->withInput($request->input())
                ->with(['old_data' => $data, 'item_codes' => $items_with_insufficient_stocks])
                ->with('error', 'An error occured. Please contact your system administrator.');
        }
    }
    
    // /view_monthly_sales_form/{branch}/{date}
    public function viewMonthlySalesForm($branch, $date){
        $days = Carbon::parse($date)->daysInMonth;
        $exploded = explode('-', $date);
        $month = $exploded[0];
        $year = $exploded[1];

        $report = DB::table('tabConsignment Monthly Sales Report')
            ->where('fiscal_year', $year)->where('month', $month)
            ->where('warehouse', $branch)->first();
            
        $sales_per_day = $report ? collect(json_decode($report->sales_per_day)) : [];

        return view('consignment.tbl_sales_report_form', compact('branch', 'sales_per_day', 'month', 'year', 'report', 'days'));
    }

    public function submitMonthlySaleForm(Request $request){
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $sales_per_day = [];
            foreach($request->day as $day => $detail){
                $amount = preg_replace("/[^0-9 .]/", "", $detail['amount']);
                if(!is_numeric($amount)){
                    return redirect()->back()->with('error', 'Amount should be a number.');
                }
                $sales_per_day[$day] = (float) $amount;
            }

            $transaction_month = new Carbon('last day of '. $request->month .' ' . $request->year);
            $cutoff_date = $this->getCutoffDate($transaction_month)[1];

            $status = isset($request->draft) && $request->draft ? 'Draft' : 'Submitted';
            
            $submission_status = $date_submitted = $submitted_by = null;
            if ($now->gt($cutoff_date) && $status == 'Submitted') {
                $submission_status = 'Late';
            }

            if ($status == 'Submitted') {
                $submitted_by = Auth::user()->wh_user;
                $email_data = [
                    'warehouse' => $request->branch,
                    'month' => $request->month,
                    'total_amount' => collect($sales_per_day)->sum(),
                    'remarks' => $request->remarks,
                    'year' => $request->year,
                    'status' => $status,
                    'submission_status' => $submission_status,
                    'date_submitted' => $date_submitted
                ];

                $recipient = str_replace('.local', '.com', Auth::user()->wh_user);
                $this->sendMail('mail_template.consignment_sales_report', $email_data, $recipient, 'AthenaERP - Sales Report');
            }

            $sales_report = DB::table('tabConsignment Monthly Sales Report')->where('fiscal_year', $request->year)->where('month', $request->month)->where('warehouse', $request->branch)->first();

            $reference = null;
            $method = 'post';
            if($sales_report){
                $reference = $sales_report->name;
                $method = 'put';
            }

            $data = [
                'warehouse' => $request->branch,
                'month' => $request->month,
                'sales_per_day' => json_encode($sales_per_day, true),
                'total_amount' => collect($sales_per_day)->sum(),
                'remarks' => $request->remarks,
                'fiscal_year' => $request->year,
                'status' => $status,
                'submission_status' => $submission_status,
                'date_submitted' => $date_submitted,
                'submitted_by' => $submitted_by
            ];

            $response = $this->erpOperation($method, 'Consignment Monthly Sales Report', $reference, $data);

            if(!isset($response['data'])){
                throw new Exception($response['exc_type']);
            }

            return redirect()->back()->with('success', 'Sales Report for the month of <b>'.$request->month.'</b> has been '.($sales_report ? 'updated!' : 'added!'));
        } catch (Exception $th) {
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    public function getCutoffDate($transaction_date) {
        $transactionDate = Carbon::parse($transaction_date);

        $start_date = Carbon::parse($transaction_date)->subMonth();
        $end_date = Carbon::parse($transaction_date)->addMonths(2);

        $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

        $transaction_date = $transactionDate->format('Y-m-d');
        
        $cutoff_period = [];
        foreach ($period as $i => $date) {
            $date1 = $date->day($cutoff_1);
            if ($date1 >= $start_date && $date1 <= $end_date) {
                $cutoff_period[] = $date->format('Y-m-d');
            }

            if($i == 0){
                $feb_cutoff = $cutoff_1 <= 28 ? $cutoff_1 : 28;
                $cutoff_period[] = $feb_cutoff.'-02-'.Carbon::now()->format('Y');
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

    public function salesReport(Request $request){
        $exploded_date = explode(' to ', $request->daterange);
        $request_start_date = isset($exploded_date[0]) ? $exploded_date[0] : Carbon::now()->startOfMonth();
        $request_end_date = isset($exploded_date[1]) ? $exploded_date[1] : Carbon::now();

        $request_start_date = Carbon::parse($request_start_date);
        $request_end_date = Carbon::parse($request_end_date);

        $months_array = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $period = CarbonPeriod::create($request_start_date, $request_end_date);

        $included_dates = $included_months = [];
        foreach ($period as $date) {
            $included_months[] = $months_array[(int) Carbon::parse($date)->format('m')];
            $included_dates[] = Carbon::parse($date)->format('Y-m-d');
        }

        $sales_report = DB::table('tabConsignment Monthly Sales Report')
            ->whereIn('fiscal_year', [$request_start_date->format('Y'), $request_end_date->format('Y')])
            ->whereIn('month', $included_months)
            ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') ASC")
            ->get();

        $report = [];
        foreach($sales_report as $details){
            $month_index = array_search($details->month, $months_array);
            $sales_per_day = collect(json_decode($details->sales_per_day));
            foreach($sales_per_day as $day => $amount){
                $sale_date = Carbon::parse($details->fiscal_year . '-' . $month_index . '-' . $day)->format('Y-m-d');
                if (in_array($sale_date, $included_dates)) {
                    $report[$details->warehouse][$sale_date] = $amount;
                }
            }
        }

        $warehouses_with_data = collect($sales_report)->pluck('warehouse');

        return view('consignment.supervisor.tbl_sales_report', compact('report', 'included_dates', 'warehouses_with_data'));
    }

    // /inventory_items/{branch}
    public function inventoryItems($branch){
        $assigned_consignment_stores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')->pluck('warehouse');

        $inv_summary = Item::whereHas('bin', function ($bin) use ($branch){
                $bin->where('warehouse', $branch)->where('consigned_qty', '>', 0);
            })->with('defaultImage')->with('bin', function ($bin) use ($branch){
                $bin->where('warehouse', $branch)->where('consigned_qty', '>', 0)->select('name', 'warehouse', 'item_code', 'consigned_qty', 'consignment_price');
            })->where('disabled', 0)->where('is_stock_item', 1)
            ->select('name', 'item_code', 'description', 'stock_uom')
            ->orderBy('item_code')->get();

        return view('consignment.promodiser_warehouse_items', compact('inv_summary', 'branch', 'assigned_consignment_stores'));
    }

    // /beginning_inv_list
    public function beginningInventoryApproval(Request $request){
        $from_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[0])->startOfDay() : null;
        $to_date = $request->date ? Carbon::parse(explode(' to ', $request->date)[1])->endOfDay() : null;

        $consignment_stores = [];
        $status = $request->status ? $request->status : 'All';
        if(in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])){
            $status = $request->status ? $request->status : 'For Approval';

            $beginning_inventory = BeginningInventory::with('items')
                ->when($request->search, function ($q) use ($request){
                    return $q->where('name', 'LIKE', "%$request->search%")
                        ->orWhere('owner', 'LIKE', "%$request->search%");
                })
                ->when($request->date, function ($q) use ($from_date, $to_date){
                    return $q->whereBetween('transaction_date', [$from_date, $to_date]);
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
                ->orderBy('creation', 'desc')
                ->paginate(10);
        }

        $item_codes = collect($beginning_inventory->items())->flatMap(function($stock_transfer) {
            return $stock_transfer->items->pluck('item_code');
        })->unique()->values();

        $warehouses = collect($beginning_inventory->items())->pluck('branch_warehouse');

        $flatten_item_codes = $item_codes->implode("','");

        $bin_details = Bin::with('defaultImage')->whereRaw("item_code in ('$flatten_item_codes')")->whereIn('warehouse', $warehouses)
            ->select('item_code', 'warehouse', 'consignment_price')->get()->groupBy(['warehouse', 'item_code']);

        $inv_arr = collect($beginning_inventory->items())->map(function ($inventory) use ($bin_details){
            $bin = $bin_details[$inventory->branch_warehouse];

            $inventory->owner = ucwords(str_replace('.', ' ', explode('@', $inventory->owner)[0]));
            $inventory->transaction_date = Carbon::parse($inventory->transaction_date)->format('M. d, Y');

            $inventory->qty = collect($inventory->items)->sum('opening_stock');
            $inventory->amount = collect($inventory->items)->sum('amount');
            $inventory->items = collect($inventory->items)->map(function ($item) use ($bin) {
                $item_code = $item->item_code;

                $item->image = '/icon/no_img.png';
                $price = 0;

                $item->opening_stock = (int) $item->opening_stock;
                $item->amount = (float) $item->amount;

                if(isset($bin[$item_code][0])){
                    $consignment_details = $bin[$item_code][0];
                    $price = $item->status == 'For Approval' ? $item->price : $consignment_details->consignment_price;

                    $item->image = isset($consignment_details->defaultImage->image_path) ? '/img/'.$consignment_details->defaultImage->image_path : '/icon/no_img.png';
                    if(Storage::disk('public')->exists(explode('.', $item->image)[0].'.webp')){
                        $item->image = explode('.', $item->image)[0].'.webp';
                    }
                }

                return $item;
            });

            return $inventory;
        });

        $last_record = collect($beginning_inventory->items()) ? collect($beginning_inventory->items())->sortByDesc('creation')->last() : [];
        $earliest_date = $last_record ? Carbon::parse($last_record->creation)->format("Y-M-d") : Carbon::now()->format("Y-M-d");

        $activity_logs_users = ActivityLog::where('content', 'Consignment Activity Log')->distinct()->pluck('full_name');

        if(in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])){
            return view('consignment.supervisor.view_stock_adjustments', compact('consignment_stores', 'inv_arr', 'beginning_inventory', 'activity_logs_users'));
        }

        return view('consignment.beginning_inventory_list', compact('consignment_stores', 'inv_arr', 'beginning_inventory', 'earliest_date'));
    }

    // /approve_beginning_inv/{id}
    public function approveBeginningInventory(Request $request, $id){
        DB::beginTransaction();
        try {
            $branch = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->pluck('branch_warehouse')->first();
            $prices = $request->price;
            $qty = $request->qty;

            $item_codes = array_keys($prices);

            if(count($item_codes) <= 0){
                return redirect()->back()->with('error', 'Please Enter an Item');
            }

            if(!$branch){
                return redirect()->back()->with('error', 'Inventory record not found.');
            }

            $now = Carbon::now()->toDateTimeString();

            $update_values = [
                'modified_by' => Auth::user()->wh_user,
                'modified' => $now
            ];

            if($request->has('status') && in_array($request->status, ['Approved', 'Cancelled'])){
                $update_values['status'] = $request->status;
            }

            if($request->status == 'Approved' || !$request->has('status')){
                DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->whereNotIn('item_code', $item_codes)->delete();

                $items = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();
                $items = collect($items)->groupBy('item_code');

                $item_details = DB::table('tabItem')->whereIn('name', $item_codes)->select('name', 'description', 'stock_uom')->get();
                $item_details = collect($item_details)->groupBy('name');

                $bin = DB::table('tabBin')->where('warehouse', $branch)->whereIn('item_code', $item_codes)->get();
                $bin_items = collect($bin)->groupBy('item_code');

                $skipped_items = [];
                foreach($item_codes as $i => $item_code){
                    if(isset($items[$item_code]) && $items[$item_code][0]->status != 'For Approval'){ // Skip the approved/cancelled items
                        $skipped_items = collect($skipped_items)->merge($item_code)->toArray();
                        continue;
                    }
                    
                    $price = isset($prices[$item_code]) ? preg_replace("/[^0-9 .]/", "", $prices[$item_code][0]) * 1 : 0;
                    if(!$price){
                        return redirect()->back()->with('error', 'Item price cannot be empty');
                    }

                    // Update Bin if approved
                    if($request->has('status') && $request->status == 'Approved'){
                        if(isset($bin_items[$item_code])){
                            DB::table('tabBin')->where('item_code', $item_code)->where('warehouse', $branch)->update([
                                'consigned_qty' => isset($qty[$item_code]) ? $qty[$item_code][0] : 0,
                                'consignment_price' => $price,
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
                                'item_code' => $item_code,
                                'stock_uom' => isset($item_details[$item_code]) ? $item_details[$item_code][0]->stock_uom : null,
                                'valuation_rate' => $price,
                                'consigned_qty' => isset($qty[$item_code]) ? $qty[$item_code][0] : 0,
                                'consignment_price' => $price
                            ]);
                        }
                    }

                    // Beginning Inventory
                    if(isset($items[$item_code]) || in_array($item_code, $skipped_items)){
                        if(isset($prices[$item_code])){
                            $update_values['price'] = $price;
                            $update_values['idx'] = $i + 1;
                        }

                        if(in_array($item_code, $skipped_items) && $request->has('status')){
                            $update_values['status'] = $request->status;
                        }
        
                        DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->where('item_code', $item_code)->update($update_values);
                    }else{
                        $item_qty = isset($qty[$item_code]) ? preg_replace("/[^0-9 .]/", "", $qty[$item_code][0]) : 0;

                        if(!$item_qty){
                            return redirect()->back()->with('error', 'Opening qty cannot be empty');
                        }

                        $insert = [
                            'name' => uniqid(),
                            'creation' => $now,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'parent' => $id,
                            'idx' => $i + 1,
                            'item_code' => $item_code,
                            'item_description' => isset($item_details[$item_code]) ? $item_details[$item_code][0]->description : null,
                            'stock_uom' => isset($item_details[$item_code]) ? $item_details[$item_code][0]->stock_uom : null,
                            'opening_stock' => $item_qty,
                            'stocks_displayed' => 0,
                            'price' => $price,
                            'amount' => $price * $item_qty,
                            'modified' => $now,
                            'modified_by' => Auth::user()->wh_user,
                            'parentfield' => 'items',
                            'parenttype' => 'Consignment Beginning Inventory' 
                        ];

                        if($request->has('status') && $request->status == 'Approved'){
                            $insert['status'] = $request->status;
                        }

                        DB::table('tabConsignment Beginning Inventory Item')->insert($insert);
                    }
                }
            }else{
                // update item status' to cancelled
                DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->update($update_values);
            }

            if(isset($update_values['price'])){ // remove price/idx in updates array, parent table of beginning inventory does not have price/idx
                unset($update_values['price']);
            }

            if(isset($update_values['idx'])){
                unset($update_values['idx']);
            }

            if($request->status == 'Approved'){
                $update_values['approved_by'] = Auth::user()->full_name;
                $update_values['date_approved'] = $now;
            }

            if($request->has('remarks')){
                $update_values['remarks'] = $request->remarks;
            }

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update($update_values);

            DB::commit();
            if ($request->ajax()) {
                return response()->json(['status' => 1, 'message' => 'Beginning Inventory for '.$branch.' was '.($request->has('status') ? $request->status : 'Updated').'.']);
            }

            return redirect()->back()->with('success', 'Beginning Inventory for '.$branch.' was '.($request->has('status') ? $request->status : 'Updated').'.');
        } catch (Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again later.']);
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /cancel/approved_beginning_inv/{id}
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

            if(count($items) > 0) {
                // Update each item in Bin and Product Sold
                $activity_logs_data = [];
                foreach($items as $item){
                    DB::table('tabBin')->where('warehouse', $inventory->branch_warehouse)->where('item_code', $item->item_code)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => 0
                    ]);

                    $activity_logs_data[$item->item_code]['opening_stock'] = (float)$item->opening_stock;
                }         
            }

            $update_values = [
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ];

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update($update_values);
            DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->update($update_values);

            DB::table('tabActivity Log')->insert([
                'name' => uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Approved Beginning Inventory Record for '.$inventory->branch_warehouse.' has been cancelled by '.$inventory->owner.' at '.Carbon::now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => Carbon::now()->toDateTimeString(),
                'reference_doctype' => 'Beginning Inventory',
                'reference_name' => $inventory->name,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activity_logs_data, true)
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Beginning Inventory for '.$inventory->branch_warehouse.' was cancelled.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /promodiser/delivery_report/{type}
    public function promodiserDeliveryReport($type, Request $request){
        $assigned_consignment_store = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $beginning_inventory_start = BeginningInventory::orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();

        $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

        $delivery_report = StockEntry::whereHas('items', function ($items) use ($assigned_consignment_store){
                $items->whereIn('t_warehouse', $assigned_consignment_store);
            })->with('items', function ($items) use ($assigned_consignment_store){
                $items->with('defaultImage')->whereIn('t_warehouse', $assigned_consignment_store)->select('name', 'parent', 't_warehouse', 's_warehouse', 'item_code', 'description', 'transfer_qty', 'stock_uom', 'basic_rate', 'consignment_status', 'consignment_date_received', 'consignment_received_by');
            })->whereDate('delivery_date', '>=', $beginning_inventory_start_date)
            ->whereIn('transfer_as', ['Consignment', 'Store Transfer'])->where('purpose', 'Material Transfer')->where('docstatus', 1)->whereIn('item_status', ['For Checking', 'Issued'])
            ->when($type == 'pending_to_receive', function ($query){
                return $query->where(function($q){
                    return $q->whereNull('consignment_status')->orWhere('consignment_status', 'To Receive');
                });
            })
            ->select('name','delivery_date', 'item_status', 'from_warehouse', 'to_warehouse', 'creation', 'posting_time', 'consignment_status', 'transfer_as', 'docstatus', 'consignment_date_received', 'consignment_received_by')
            ->orderBy('creation', 'desc')->orderByRaw("FIELD(consignment_status, '', 'Received') ASC")->paginate(10);

        $item_codes = collect($delivery_report->items())->flatMap(function($stock_entry) {
            return $stock_entry->items->pluck('item_code');
        })->unique()->values();

        $target_warehouses = collect($delivery_report->items())->flatMap(function($stock_entry) {
            return $stock_entry->items->pluck('target_warehouses');
        })->unique()->values();

        $item_prices = Bin::whereIn('warehouse', $target_warehouses)->whereIn('item_code', $item_codes)->select('warehouse', 'consignment_price', 'item_code')->get()->groupBy(['item_code', 'warehouse']);

        $ste_arr = collect($delivery_report->items())->map(function ($stock_entry) use ($item_prices){
            $stock_entry->items = collect($stock_entry->items)->map(function ($item) use ($item_prices){
                $item_code = $item->item_code;
                $warehouse = $item->t_warehouse;
                $price = isset($item_prices[$item_code][$warehouse]) ? $item_prices[$item_code][$warehouse][0]->consignment_price : $item->basic_rate;
                $price = (float) $price;

                $item->transfer_qty = (int) $item->transfer_qty;

                $item->image = isset($item->defaultImage->image_path) ? '/img/'.$item->defaultImage->image_path : '/icon/no_img.png';
                if(Storage::disk('public')->exists(explode('.', $item->image)[0].'.webp')){
                    $item->image = explode('.', $item->image)[0].'.webp';
                }

                $item->price = $price;
                return $item;
            });

            if($stock_entry->item_status == 'Issued' && Carbon::parse($stock_entry->delivery_date)->lt(Carbon::now())){
                $status = 'Delivered';
            }

            $stock_entry->status = $status;

            return $stock_entry;
        });

        $blade = $request->ajax() ? 'delivery_report_tbl' : 'promodiser_delivery_report';

        return view('consignment.'.$blade, compact('delivery_report', 'ste_arr', 'type'));
    }

    public function promodiserInquireDelivery(Request $request){
        $delivery_report = [];
        $item_image = [];
        if($request->ajax()){
            $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

            $delivery_report = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)->whereIn('ste.item_status', ['For Checking', 'Issued'])->where('ste.name', $request->ste)->where(function ($q) use ($assigned_consignment_stores){
                    return $q->whereIn('ste.to_warehouse', $assigned_consignment_stores)->orWhereIn('sted.t_warehouse', $assigned_consignment_stores);
                })
                ->select('ste.name', 'ste.delivery_date', 'ste.item_status', 'ste.from_warehouse', 'sted.t_warehouse', 'sted.s_warehouse', 'ste.creation', 'ste.posting_time', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.consignment_status', 'ste.transfer_as', 'ste.docstatus', 'sted.consignment_date_received', 'sted.consignment_received_by')
                ->orderBy('ste.creation', 'desc')->get();

            $item_images = DB::table('tabItem Images')->whereIn('parent', collect($delivery_report)->pluck('item_code'))->pluck('image_path', 'parent');
            $item_images = collect($item_images)->map(function ($image){
                return $this->base64_image("img/$image");
            });
    
            $no_img = $this->base64_image('icon/no_img.png');
            $item_images['no_img'] = $no_img;

            return view('consignment.promodiser_delivery_inquire_tbl', compact('delivery_report', 'item_images'));
        }

        return view('consignment.promodiser_delivery_inquire', compact('delivery_report'));
    }

    // /promodiser/receive/{id}
    public function promodiserReceiveDelivery(Request $request, $id){
        $state_before_update = [];
        try {
            $stock_entry = $this->erpOperation('get', 'Stock Entry', $id);

            if(!isset($stock_entry['data'])){
                throw new Exception('Stock Entry not found.');
            }

            $stock_entry = $stock_entry['data'];

            if(isset($stock_entry['consignment_status']) && $stock_entry['consignment_status'] == 'Received'){
                throw new Exception("$id already received.");
            }

            // check the prices
            $item_prices = [];
            foreach($request->price as $item_code => $p){
                $price = preg_replace("/[^0-9 .]/", "", $p);
                $item_prices[$item_code] = $price;
                if($stock_entry['transfer_as'] != 'For Return'){
                    if(!is_numeric($price) || $price <= 0){
                        throw new Exception('Item prices cannot be less than or equal to 0.');
                    }
                }
            }

            $default_source_warehouse = isset($stock_entry['from_warehouse']) ? $stock_entry['from_warehouse'] : null;
            $default_target_warehouse = isset($stock_entry['to_warehouse']) ? $stock_entry['to_warehouse'] : null;

            $ste_items = $stock_entry['items'];
            // Get item codes, source warehouse/s, and target warehouse/s
            $item_codes = collect($ste_items)->pluck('item_code');
            $source_warehouses = collect($ste_items)->pluck('s_warehouse')->push($default_source_warehouse)->filter()->unique();
            $target_warehouses = collect($ste_items)->pluck('t_warehouse')->push($request->target_warehouse)->push($default_target_warehouse)->filter()->unique();

            // get all items for each source and target warehouses
            $target_warehouse_details = DB::table('tabBin')->whereIn('warehouse', $target_warehouses)
                ->whereIn('item_code', $item_codes)->select('name', 'warehouse', 'item_code', 'actual_qty', 'consigned_qty', 'consignment_price', 'modified', 'modified_by')
                ->get()->groupBy(['warehouse', 'item_code']);

            $source_warehouse_details = DB::table('tabBin')->whereIn('warehouse', $source_warehouses)
                ->whereIn('item_code', $item_codes)->select('name', 'warehouse', 'item_code', 'actual_qty', 'consigned_qty', 'consignment_price', 'modified', 'modified_by')
                ->get()->groupBy(['warehouse', 'item_code']);
                
            $validate_stocks = collect($ste_items)->map(function ($item) use ($source_warehouse_details, $target_warehouse_details, $request, $default_source_warehouse, $default_target_warehouse){
                $source_warehouse = $item['s_warehouse'] ?? $default_source_warehouse;
                $target_warehouse = $item['t_warehouse'] ?? ($request->target_warehouse ?? $default_target_warehouse);
                $item_code = $item['item_code'];
                if(!isset($source_warehouse_details[$source_warehouse][$item_code])){
                    return "Item $item_code does not exist in $source_warehouse";
                }

                if(!isset($target_warehouse_details[$target_warehouse][$item_code])){
                    return "Item $item_code does not exist in $target_warehouse";
                }

                $source_warehouse_consigned_qty = $source_warehouse_details[$source_warehouse][$item_code][0]->consigned_qty;

                if($item['transfer_qty'] > $source_warehouse_consigned_qty){
                    return "Insufficient stocks for item $item_code from $source_warehouse";
                }
                
                return null;
            })->filter();

            if((count($validate_stocks) > 0)){
                throw new Exception(collect($validate_stocks)->first());
            }

            $now = Carbon::now();

            // set the details of the json data for activity logs
            $data['details'] = [
                'reference' => $id,
                'transaction_date' => $now->toDateTimeString()
            ];
            
            $received_items = $expected_qty_after_transaction = $actual_qty_after_transaction = [];

            foreach($ste_items as $item){
                $item_code = $item['item_code'];
                $src_branch = $item['s_warehouse'] ?? $default_source_warehouse;
                $target_branch = $request->target_warehouse ?? ($item['t_warehouse'] ?? $default_target_warehouse);

                // Source Warehouse
                $source_bin_details = $source_warehouse_details[$src_branch][$item_code][0];
                $source_consigned_qty = $source_bin_details->consigned_qty;
                $source_bin_id = $source_bin_details->name;

                $source_updated_consigned_qty = $source_consigned_qty > $item['transfer_qty'] ? $source_consigned_qty - $item['transfer_qty'] : 0;
                    
                $update_bin = ['consigned_qty' => $source_updated_consigned_qty];

                // revert changes if there are errors
                $state_before_update['Bin'][$source_bin_id] = $source_bin_details;

                // activity logs
                $data[$src_branch][$item_code]['quantity'] = [
                    'previous' => $source_consigned_qty,
                    'transferred_qty' => $item['transfer_qty'],
                    'new' => $source_updated_consigned_qty
                ];

                $bin_response = $this->erpOperation('put', 'Bin', $source_bin_id, $update_bin);

                if(!isset($bin_response['data'])){
                    throw new Exception('An error occured while updating Bin.');
                }

                $expected_qty_after_transaction['source'][$src_branch][$item_code] = $source_updated_consigned_qty;

                $target_bin_details = $target_warehouse_details[$target_branch][$item_code][0];
                $target_consigned_qty = $target_bin_details->consigned_qty;
                $target_consignment_price = $target_bin_details->consignment_price;
                $target_bin_id = $target_bin_details->name;

                $basic_rate = $item['basic_rate'];
                if($stock_entry['transfer_as'] != 'For Return'){
                    $basic_rate = isset($item_prices[$item_code]) ? $item_prices[$item_code] : $basic_rate;
                }

                $target_updated_consigned_qty = $target_consigned_qty + $item['transfer_qty'];
                    
                $update_bin = [
                    'consigned_qty' => $target_updated_consigned_qty,
                    'consignment_price' => $target_consignment_price
                ];

                // activity logs
                $data[$target_branch][$item_code]['quantity'] = [
                    'previous' => $source_consigned_qty,
                    'transferred_qty' => $item['transfer_qty'],
                    'new' => $source_updated_consigned_qty
                ];

                if(isset($item_prices[$item_code])){
                    $update_bin['consignment_price'] = $basic_rate;

                    $data[$target_branch][$item_code]['price'] = [
                        'previous' => $target_consignment_price,
                        'new' => $basic_rate
                    ];
                }

                // revert changes if there are errors
                $state_before_update['Bin'][$target_bin_id] = $target_bin_details;

                $bin_response = $this->erpOperation('put', 'Bin', $target_bin_id, $update_bin);

                if(!isset($bin_response['data'])){
                    throw new Exception('Bin: '.$bin_response['exception']);
                }

                $expected_qty_after_transaction['target'][$target_branch][$item_code] = $target_updated_consigned_qty;
            
                // Stock Entry Detail
                $ste_details_update = ['status' => 'Issued'];

                if(!isset($item['consignment_status']) || $item['consignment_status'] != 'Received'){
                    $ste_details_update['consignment_status'] = 'Received';
                    $ste_details_update['consignment_date_received'] = $now->toDateTimeString();
                    $ste_details_update['consignment_received_by'] = Auth::user()->wh_user;
                }

                // Update the target warehouse, if needed. This is only available for the consignment supervisor
                if($request->target_warehouse){
                    $ste_details_update['t_warehouse'] = $request->target_warehouse;
                    $ste_details_update['target_warehouse_location'] = $request->target_warehouse;
                }

                $state_before_update['Stock Entry Detail'][$item['name']] = $item;
                
                $stock_entry_detail_response = $this->erpOperation('put', 'Stock Entry Detail', $item['name'], $ste_details_update);

                if(!isset($stock_entry_detail_response['data'])){
                    throw new Exception('Stock Entry Detail: '.$stock_entry_detail_response['exception']);
                }

                $received_items[] = [
                    'item_code' => $item_code,
                    'qty' => $item['transfer_qty'],
                    'price' => $basic_rate,
                    'amount' => $basic_rate * $item['transfer_qty']
                ];
            }

            $warehouses_arr = collect($source_warehouses)->merge($target_warehouses);

            // get the actual qty after update for the source and target warehouse
            $actual_qty_after_transaction = DB::table('tabBin')->whereIn('warehouse', $warehouses_arr)->whereIn('item_code', $item_codes)->get(['item_code', 'consigned_qty', 'warehouse']);
            $actual_qty_after_transaction = $actual_qty_after_transaction->groupBy(['warehouse', 'item_code']);

            // Compare the expected qty vs actual qty after transaction for both source warehouse and target warehouse
            foreach ($ste_items as $item) {
                // source warehouse
                $item_code = $item['item_code'];
                $src = $item['s_warehouse'] ? $item['s_warehouse'] : $stock_entry['from_warehouse'];
                $is_consigned = false;
                if($src != 'Consignment Warehouse - FI'){
                    $is_consigned = DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')->where('is_group', 0)->where('disabled', 0)->where('name', $src)->exists();
                }
                
                if($is_consigned){
                    $expected_qty_in_source = isset($expected_qty_after_transaction['source'][$src][$item_code]) ? $expected_qty_after_transaction['source'][$src][$item_code] : 0;
                    $actual_consigned_qty_in_source = isset($actual_qty_after_transaction[$src][$item_code]) ? $actual_qty_after_transaction[$src][$item_code][0]->consigned_qty : 0;

                    if($expected_qty_in_source != $actual_consigned_qty_in_source){
                        throw new Exception("Error: Expected qty of item $item_code did not match the actual qty in source warehouse");
                    }
                }

                // target warehouse
                $trg = $item['t_warehouse'] ? $item['t_warehouse'] : $stock_entry['to_warehouse'];
                if(isset($request->receive_delivery)){
                    $expected_qty_in_target = isset($expected_qty_after_transaction['target'][$trg][$item_code]) ? $expected_qty_after_transaction['target'][$trg][$item_code] : 0;
                    $actual_consigned_qty_in_target = isset($actual_qty_after_transaction[$trg][$item_code]) ? $actual_qty_after_transaction[$trg][$item_code][0]->consigned_qty : 0;

                    if($expected_qty_in_target != $actual_consigned_qty_in_target){
                        throw new Exception("Error: Expected qty of $item_code did not match the actual qty in target warehouse");
                    }
                }
            }

            // Set source and target warehouse for logs
            $source_warehouse = isset($stock_entry['from_warehouse']) ? $stock_entry['from_warehouse'] : null;
            if(!$source_warehouse){
                $source_warehouse = isset($source_warehouses[0]) ? $source_warehouses[0] : null;
            }

            if($request->target_warehouse){
                $target_warehouse = $request->target_warehouse;
            }else{
                $target_warehouse = $stock_entry['to_warehouse'] ? $stock_entry['to_warehouse'] : null;
                if(!$target_warehouse){
                    $target_warehouse = isset($target_warehouses[0]) ? $target_warehouses[0] : null;
                }
            }

            $logs = [
                'subject' => 'Stock Transfer from '.$source_warehouse.' to '.$target_warehouse.' has been received by '.Auth::user()->full_name. ' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($data, true)
            ];

            $log = $this->erpOperation('post', 'Activity Log', null, $logs);

            if(!isset($log['data'])){
                session()->flash('warning', 'Activity Log not posted');
            }

            $stock_entry_response['data'] = $this->erpOperation('put', 'Stock Entry', $id, [
                'consignment_status' => 'Received',
                'consignment_date_received' => $now->toDateTimeString(),
                'consignment_received_by' => Auth::user()->wh_user,
            ]);

            if(!isset($stock_entry_response['data'])){
                throw new Exception('Stock Entry: '.$stock_entry_response['exception']);
            }

            $message = null;

            if(isset($request->receive_delivery)){
                $t = $stock_entry['transfer_as'] != 'For Return' ? 'your store inventory!' : (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) ? $target_warehouse : 'Quarantine Warehouse!');
                $message = collect($received_items)->sum('qty').' Item(s) is/are successfully received and added to '.$t;
            }

            $received_items['message'] = $message;
            $received_items['branch'] = $target_warehouse;
            $received_items['action'] = 'received';

            return response()->json(['success' => 1, 'message' => $message]);
        } catch (\Throwable $e) {
            $this->revertChanges($state_before_update);
            return response()->json(['success' => 0, 'message' => $e->getMessage()]);
        }
    }

    // /promodiser/cancel/received/{id}
    public function promodiserCancelReceivedDelivery($id){
        DB::beginTransaction();
        try {
            $stock_entry = DB::table('tabStock Entry')->where('name', $id)->first();
            $received_items = DB::table('tabStock Entry Detail')->where('parent', $id)->get();

            $item_codes = collect($received_items)->map(function ($q){
                return $q->item_code;
            });

            $branches = [];

            $target_warehouses = collect($received_items)->map(function ($q){
                return $q->t_warehouse;
            })->unique()->toArray();

            $source_warehouses = collect($received_items)->map(function ($q){
                return $q->s_warehouse;
            })->unique()->toArray();

            $st_warehouses = [$stock_entry->from_warehouse, $stock_entry->to_warehouse];

            $branches = array_merge($target_warehouses, $source_warehouses, $st_warehouses);

            $bin_consigned_qty = DB::table('tabBin')->whereIn('item_code', $item_codes)->whereIn('warehouse', $branches)->select('warehouse', 'item_code', 'consigned_qty')->get();

            $consigned_qty = [];
            foreach($bin_consigned_qty as $bin){
                $consigned_qty[$bin->warehouse][$bin->item_code] = [
                    'consigned_qty' => $bin->consigned_qty
                ];
            }

            foreach($received_items as $item){
                $branch = $stock_entry->to_warehouse ? $stock_entry->to_warehouse : $item->t_warehouse;
                if($item->consignment_status != 'Received'){
                    return redirect()->back()->with('error', $id.' is not yet received.');
                }

                if(!isset($consigned_qty[$branch][$item->item_code])){
                    return redirect()->back()->with('error', 'Item not found.');
                }

                if($consigned_qty[$branch][$item->item_code]['consigned_qty'] < $item->transfer_qty ){
                    return redirect()->back()->with('error', 'Cannot cancel received items.<br/> Available qty is '.number_format($consigned_qty[$branch][$item->item_code]['consigned_qty']).', received qty is '.number_format($item->transfer_qty));
                }

                if($stock_entry->transfer_as == 'Store Transfer'){ // return stocks to source warehouse
                    $src_branch = $stock_entry->from_warehouse ? $stock_entry->from_warehouse : $item->s_warehouse;
                    DB::table('tabBin')->where('item_code', $item->item_code)->where('warehouse', $src_branch)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => $consigned_qty[$src_branch][$item->item_code]['consigned_qty'] + $item->transfer_qty
                    ]);
                }

                DB::table('tabBin')->where('item_code', $item->item_code)->where('warehouse', $branch)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $consigned_qty[$branch][$item->item_code]['consigned_qty'] - $item->transfer_qty
                ]);
                
                DB::table('tabStock Entry Detail')->where('parent', $id)->where('item_code', $item->item_code)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consignment_status' => null,
                    'consignment_date_received' => null
                ]);

                $cancelled_arr[] = [
                    'item_code' => $item->item_code,
                    'qty' => $item->transfer_qty,
                    'price' => $item->basic_rate,
                    'amount' => $item->basic_rate * $item->transfer_qty
                ];
            }

            $source_warehouse = $stock_entry->from_warehouse ? $stock_entry->from_warehouse : null;
            if(!$source_warehouse){
                $source_warehouse = isset($received_items[0]) ? $received_items[0]->s_warehouse : null;
            }

            $target_warehouse = $stock_entry->to_warehouse ? $stock_entry->to_warehouse : null;
            if(!$target_warehouse){
                $target_warehouse = isset($received_items[0]) ? $received_items[0]->t_warehouse : null;
            }

            $logs = [
                'name' => uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Transfer from '.$source_warehouse.' to '.$target_warehouse.' has been cancelled by '.Auth::user()->full_name. ' at '.Carbon::now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => Carbon::now()->toDateTimeString(),
                'reference_doctype' => 'Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            DB::table('tabActivity Log')->insert($logs);

            $cancelled_arr['message'] = 'Stock transfer cancelled.';
            $cancelled_arr['branch'] = $target_warehouse;
            $cancelled_arr['action'] = 'canceled';

            DB::commit();
            return redirect()->back()->with('success', $cancelled_arr);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'An error occured. Please try again later');
        }
    }

    // /beginning_inventory_list
    public function beginningInventoryList(Request $request){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $beginning_inventory = DB::table('tabConsignment Beginning Inventory')->whereIn('branch_warehouse', $assigned_consignment_store)->orderBy('creation', 'desc')->paginate(10);

        return view('consignment.beginning_inv_list', compact('beginning_inventory'));
    }

    // /beginning_inventory
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

    // /get_items/{branch}
    public function getItems(Request $request, $branch){
        $search_str = explode(' ', $request->q);

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
            ->select('item.item_code', 'item.description', 'item.item_image_path', 'item.item_classification', 'item.stock_uom')
            ->groupBy('item.item_code', 'item.description', 'item.item_image_path', 'item.item_classification', 'item.stock_uom')
            ->limit(8)->get();

        $item_codes = collect($items)->pluck('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("img/$image");
        });

        $no_img = $this->base64_image('icon/no_img.png');

        $items_arr = [];
        foreach($items as $item){
            $image = isset($item_images[$item->item_code]) ? $item_images[$item->item_code] : $no_img;

            $items_arr[] = [
                'id' => $item->item_code,
                'text' => $item->item_code.' - '.strip_tags($item->description),
                'description' => strip_tags($item->description),
                'classification' => $item->item_classification,
                'image' => $image,
                'alt' => Str::slug(strip_tags($item->description), '-'),
                'uom' => $item->stock_uom
            ];
        }

        return response()->json([
            'items' => $items_arr
        ]);
    }

    // /beginning_inv_items/{action}/{branch}/{id?}
    public function beginningInvItems(Request $request, $action, $branch, $id = null){
        if($request->ajax()){
            $items = [];
            $inv_name = null;
            $remarks = null;
            // get approved, for approval records and items with consigned qty
            $items_with_consigned_qty = DB::table('tabBin')->where('warehouse', $branch)->where('consigned_qty', '>', 0)->pluck('item_code');

            $inv_records = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch)->whereIn('status', ['For Approval', 'Approved'])->pluck('name');
            $inv_items = DB::table('tabConsignment Beginning Inventory Item')->whereIn('parent', $inv_records)->pluck('item_code');

            $inv_items = collect($inv_items)->merge($items_with_consigned_qty);

            if($action == 'update'){ // If 'For Approval' beginning inventory record exists for this branch
                $inv_name = $id;
                $cbi = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->first();
                $remarks = $cbi ? $cbi->remarks : null;

                $inventory = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)
                    ->select('item_code', 'item_description', 'stock_uom', 'opening_stock', 'stocks_displayed', 'price')
                    ->orderBy('item_description', 'asc')->get();

                foreach($inventory as $inv){
                    $items[] = [
                        'item_code' => $inv->item_code,
                        'item_description' => trim(strip_tags($inv->item_description)),
                        'stock_uom' => $inv->stock_uom,
                        'opening_stock' => $inv->opening_stock * 1,
                        'stocks_displayed' => $inv->stocks_displayed * 1,
                        'price' => $inv->price * 1
                    ];
                }
            }else{ // Create new beginning inventory entry
                $bin_items = DB::table('tabBin as bin')->join('tabItem as item', 'bin.item_code', 'item.name')
                    ->where('bin.warehouse', $branch)->where('bin.actual_qty', '>', 0)->where('bin.consigned_qty', 0)->whereNotIn('bin.item_code', $inv_items) // do not include approved and for approval items
                    ->select('bin.warehouse', 'bin.item_code', 'bin.actual_qty', 'bin.stock_uom', 'item.description')->orderBy('bin.actual_qty', 'desc')
                    ->get();

                foreach($bin_items as $item){
                    $items[] = [
                        'item_code' => $item->item_code,
                        'item_description' => trim(strip_tags($item->description)),
                        'stock_uom' => $item->stock_uom,
                        'opening_stock' => 0,
                        'stocks_displayed' => 0,
                        'price' => 0
                    ];
                }
            }

            $items = collect($items)->sortBy('item_description');

            $item_codes = collect($items)->pluck('item_code');

            $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
            $item_images = collect($item_images)->map(function ($image){
                return $this->base64_image("img/$image");
            });

            $no_img = $this->base64_image('icon/no_img.png');
            $item_images['no_img'] = $no_img;

            $detail = [];
            if ($id) {
                $detail = DB::table('tabConsignment Beginning Inventory')->where('name', $id)->first();
            }

            return view('consignment.beginning_inv_items', compact('items', 'branch', 'item_images', 'inv_name', 'inv_items', 'remarks', 'detail'));
        }
    }

    // /save_beginning_inventory
    public function saveBeginningInventory(Request $request){
        try {
            if(!$request->branch){
                return redirect()->back()->with('error', 'Please select a store');
            }

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

            $max_opening_stock = max($opening_stock);
            $max_price = max($price);
            $has_opening_stock = array_filter($opening_stock);
            $has_price = array_filter($price);

            if ($max_opening_stock <= 0 || $max_price <= 0 || !$has_opening_stock || !$has_price) {
                $null_value = ($max_opening_stock <= 0 || !$has_opening_stock) ? 'Opening Stock' : 'Price';
                return redirect()->back()->with('error', 'Please input values to '.$null_value);
            }

            $now = Carbon::now();
    
            $items = DB::table('tabItem')->whereIn('name', $item_codes)->select('name', 'item_code', 'description', 'stock_uom')->get();
            $items = collect($items)->map(function ($item) use ($opening_stock, $price){
                unset($item->name);
                $qty = isset($opening_stock[$item->item_code]) ? $opening_stock[$item->item_code] : 1;
                $qty = (float) $qty;
                $value = isset($opening_stock[$item->item_code]) ? $price[$item->item_code] : 1;
                $value = (float) $value;
                
                $item->item_description = strip_tags($item->description);
                $item->opening_stock = $qty;
                $item->status = 'For Approval';
                $item->price = $value;
                $item->amount = $qty * $value;

                return $item;
            });

            $body = [
                'docstatus' => 0,
                'status' => 'For Approval',
                'branch_warehouse' => $branch,
                'transaction_date' => $now->toDateTimeString(),
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpOperation('post', 'Consignment Beginning Inventory', null, $body);

            if(!isset($response['data'])){
                throw new Exception('An error occured. Please try again.');
            }

            $subject = 'For Approval Beginning Inventory Entry for ' .$branch. ' has been created by '.Auth::user()->full_name.' at '.$now;
            $logs = [
                'docstatus' => 0,
                'subject' => $subject,
                'content' => 'Consignment Activity Log',
                'communication_date' => $now,
                'reference_doctype' => 'Beginning Inventory',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $athena_logs = $this->erpOperation('post', 'Activity Log', null, $logs);
            
            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Saved! Please wait for approval');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function cancelDraftBeginningInventory($beginning_inventory_id){
        try {
            $response = $this->erpOperation('delete', 'Consignment Beginning Inventory', $beginning_inventory_id);

            if(!isset($response['data'])){
                throw new Exception('An error occured.');
            }

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Canceled.');
        } catch (\Throwable $th) {
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function updateDraftBeginningInventory($id, Request $request){
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

            $max_opening_stock = max($opening_stock);
            $max_price = max($price);
            $has_opening_stock = array_filter($opening_stock);
            $has_price = array_filter($price);

            if ($max_opening_stock <= 0 || $max_price <= 0 || !$has_opening_stock || !$has_price) {
                $null_value = ($max_opening_stock <= 0 || !$has_opening_stock) ? 'Opening Stock' : 'Price';
                return redirect()->back()->with('error', 'Please input values to '.$null_value);
            }

            $items = DB::table('tabItem')->whereIn('name', $item_codes)->select('name', 'item_code', 'description', 'stock_uom')->get();
            $items = collect($items)->map(function ($item) use ($opening_stock, $price){
                unset($item->name);
                $qty = isset($opening_stock[$item->item_code]) ? $opening_stock[$item->item_code] : 1;
                $qty = (float) $qty;
                $value = isset($opening_stock[$item->item_code]) ? $price[$item->item_code] : 1;
                $value = (float) $value;
                
                $item->item_description = strip_tags($item->description);
                $item->opening_stock = $qty;
                $item->status = 'For Approval';
                $item->price = $value;
                $item->amount = $qty * $value;

                return $item;
            });

            $body = [
                'branch_warehouse' => $branch,
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpOperation('put', 'Consignment Beginning Inventory', $id, $body);

            if(!isset($response['data'])){
                throw new Exception('An error occured. Please try again.');
            }

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory entry updated!');
        } catch (\Throwable $th) {
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }
    public function viewDamagedItemsList(Request $request) {
        $list = DB::table('tabConsignment Damaged Item')
            ->when($request->search, function ($q) use ($request){
                $q->where('item_code', 'like', '%' . $request->search .'%')
                    ->orWhere('description', 'like', '%' . $request->search .'%');
            })
            ->when($request->store, function ($q) use ($request){
                $q->where('branch_warehouse', $request->store);
            })
            ->orderBy('creation', 'desc')->paginate(20);
        
        $item_codes = collect($list->items())->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("img/$image");
        });

        $no_img = $this->base64_image('icon/no_img.png');

        $result = [];
        foreach($list as $item){
            $orig_exists = $webp_exists = 0;

            $img = isset($item_images[$item->item_code]) ? $item_images[$item->item_code] : $no_img;
            
            $result[] = [
                'item_code' => $item->item_code,
                'description' => $item->description,
                'damaged_qty' => ($item->qty * 1),
                'uom' => $item->stock_uom,
                'store' => $item->branch_warehouse,
                'damage_description' => $item->damage_description,
                'promodiser' => $item->promodiser,
                'image' => $img,
                'image_slug' => Str::slug(explode('.', $item->description)[0], '-'),
                'item_status' => $item->status,
                'creation' => Carbon::parse($item->creation)->format('M d, Y - h:i A'),
            ];
        }

        return view('consignment.supervisor.tbl_damaged_items', compact('result', 'list'));
    }

    public function countStockTransfer($purpose) {
        return DB::table('tabConsignment Stock Entry')->where('purpose', $purpose)->where('status', 'Pending')->count();
    }

    public function generateStockTransferEntry(Request $request) {
        try {
            $id = $request->cste;
            $table = 'Consignment Stock Entry';
            $details = $this->erpOperation('get', $table, $id);
            if (!isset($details['data'])) {
                throw new Exception('Record not found.');
            }

            $details = $details['data'];

            if (in_array($details['status'], ['Cancelled', 'Completed'])) {
                throw new Exception('Stock Transfer is ' . $details['status']);
            }

            $source_warehouse = $details['source_warehouse'];
            $target_warehouse = $details['target_warehouse'];
            $items = $details['items'];

            $item_codes = collect($items)->pluck('item_code');

            $item_details = Item::whereIn('item_code', $item_codes)
                ->with('bin', function ($bin) use ($source_warehouse, $target_warehouse){
                    $bin->whereIn('warehouse', [$source_warehouse, $target_warehouse]);
                })
                ->get()->groupBy('item_code');

            $validate_items = collect($items)->map(function ($item) use ($item_details, $source_warehouse){
                $item_code = $item['item_code'];

                if(!isset($item_details[$item_code])){
                    return "Item $item_code does not exist in $source_warehouse";
                }

                $bin_details = $item_details[$item_code][0]->bin;
                $bin_details = collect($bin_details)->groupBy('warehouse');

                if(!isset($bin_details[$source_warehouse])){
                    return "Item $item_code does not exist in $source_warehouse";
                }

                return null;
            })->unique()->first();

            if($validate_items){
                throw new Exception($validate_items);
            }

            $inventory_amount = collect($items)->sum('amount');

            $now = Carbon::now();
            $stock_entry_detail = [];
            foreach ($items as $item) {
                $item_code = $item['item_code'];
                $item_detail = isset($item_details[$item_code]) ? $item_details[$item_code] : [];
                $stock_entry_detail[] = [
                    't_warehouse' => $target_warehouse,
                    'transfer_qty' => $item['qty'],
                    'expense_account' => 'Cost of Goods Sold - FI',
                    'cost_center' => 'Main - FI',
                    's_warehouse' => $source_warehouse,
                    'custom_basic_amount' => $item['amount'],
                    'custom_basic_rate' => $item['price'],
                    'item_code' => $item_code,
                    'validate_item_code' => $item_code,
                    'qty' => $item['qty'],
                    'status' => 'Issued',
                    'session_user' => Auth::user()->full_name,
                    'issued_qty' => $item['qty'],
                    'date_modified' => $now->toDateTimeString(),
                    'return_reason' => isset($item['reason']) ? $item['reason'] : null,
                    'remarks' => 'Generated in AthenaERP'
                ];
            }

            $stock_entry_data = [
                'docstatus' => 0,
                'naming_series' => 'STEC-',
                'posting_time' => $now->format('H:i:s'),
                'to_warehouse' => $target_warehouse,
                'from_warehouse' => $source_warehouse,
                'company' => 'FUMACO Inc.',
                'total_outgoing_value' => $inventory_amount,
                'total_amount' => $inventory_amount,
                'total_incoming_value' => $inventory_amount,
                'posting_date' => $now->format('Y-m-d'),
                'purpose' => 'Material Transfer',
                'stock_entry_type' => 'Material Transfer',
                'item_status' => 'Issued',
                'transfer_as' => $details['purpose'] == 'Pull Out' ? 'Pull Out Item' : 'Store Transfer',
                'delivery_date' => $now->format('Y-m-d'),
                'remarks' => 'Generated in AthenaERP. '. $details['remarks'],
                'order_from' => 'Other Reference',
                'reference_no' => '-',
                'items' => $stock_entry_detail
            ];

            $response = $this->erpOperation('post', 'Stock Entry', null, $stock_entry_data);

            if(!isset($response['data'])){
                throw new Exception($response['exception']);
            }

            $response = $response['data'];

            $consignment_response = $this->erpOperation('put', $table, $details['name'], ['references' => $response['name']]);

            if(!isset($consignment_response['data'])){
                session()->flash('error', $consignment_response['exception']);
            }

            $data = [
                'stock_entry_name' => $response['name'],
                'link' => 'http://10.0.0.83/app/stock-entry/' . $response['name']
            ];

            return response()->json(['status' => 1, 'message' => 'Stock Entry has been created.', 'data' => $data]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 0, 'message' => 'An error occured. Please contact your system administrator.'], 400);
        }
    }

    // /stocks_report/list
    public function stockTransferReport(Request $request){
        if (!in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) { // for supervisor stock transfers list
            return redirect('/')->with('error', 'Unauthorized');
        }

        if($request->ajax()) {
            $purpose = $request->purpose;

            $list = ConsignmentStockEntry::with('items')
                ->with('stock_entry', function ($stock_entry){
                    $stock_entry->select('docstatus', 'name', 'consignment_status', 'consignment_received_by', 'consignment_date_received');
                })
                ->where('purpose', $purpose)
                ->when($request->q, function ($q) use ($request){
                    return $q->where('name', 'like', "%$request->q%");
                })
                ->when($request->source_warehouse, function ($q) use ($request){
                    return $q->where('source_warehouse', $request->source_warehouse);
                })
                ->when($request->target_warehouse, function ($q) use ($request){
                    return $q->where('target_warehouse', $request->target_warehouse);
                })
                ->when($request->status, function ($q) use ($request){
                    return $q->where('status', $request->status);
                })->orderBy('creation', 'desc')->paginate(20);

            $item_codes = collect($list->items())->flatMap(function($stock_transfer) {
                return $stock_transfer->items->pluck('item_code');
            })->unique()->values();

            $warehouses = collect($list->items())->pluck($purpose == 'Item Return' ? 'target_warehouse' : 'source_warehouse');

            $flatten_item_codes = $item_codes->implode("','");

            $bin_details = Bin::with('defaultImage')->whereRaw("item_code in ('$flatten_item_codes')")->whereIn('warehouse', $warehouses)
                ->select('item_code', 'warehouse', 'consigned_qty')->get()->groupBy(['warehouse', 'item_code']);

            $result = collect($list->items())->map(function ($stock_transfer) use ($bin_details, $purpose){
                $warehouse = $purpose == 'Item Return' ? $stock_transfer->target_warehouse : $stock_transfer->source_warehouse;
                $bin = $bin_details[$warehouse];

                $stock_transfer->submitted_by = ucwords(str_replace('.', ' ', explode('@', $stock_transfer->owner)[0]));

                $stock_transfer->items = collect($stock_transfer->items)->map(function ($item) use ($bin) {
                    $item_code = $item->item_code;
                    $consignment_details = $bin[$item_code][0];

                    $item->consigned_qty = (int) $consignment_details->consigned_qty;
                    $item->qty = (int) $item->qty;
                    $item->price = (float) $item->price;
                    $item->amount = (float) $item->amount;

                    $item->image = isset($consignment_details->defaultImage->image_path) ? '/img/'.$consignment_details->defaultImage->image_path : '/icon/no_img.png';
                    if(Storage::disk('public')->exists(explode('.', $item->image)[0].'.webp')){
                        $item->image = explode('.', $item->image)[0].'.webp';
                    }

                    return $item;
                });

                return $stock_transfer;
            });

            return view('consignment.supervisor.tbl_stock_transfer', compact('result', 'list', 'purpose'));
        }

        return view('consignment.supervisor.view_stock_transfers');
    }

    public function replenish_index(Request $request){
        $assigned_consignment_stores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        if($request->ajax()){
            $target_warehouses = $request->branch ? [$request->branch] : $assigned_consignment_stores;
            $list = ConsignmentStockEntry::whereIn('target_warehouse', $target_warehouses)->where('purpose', 'Stock Replenishment')
                ->when($request->status, function ($query) use ($request){
                    return $query->where('status', $request->status);
                })
                ->when($request->search, function ($query) use ($request){
                    return $query->where('name', 'like', "%$request->search%");
                })->orderByDesc('modified')->paginate(10);
    
            return view('consignment.replenish_tbl', compact('list'));
        }

        return view('consignment.replenish_index', compact('assigned_consignment_stores'));
    }

    public function replenish_update_form($id){
        $assigned_consignment_stores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $stock_entry = ConsignmentStockEntry::with('items')->find($id);

        $item_images = ItemImages::whereIn('parent', collect($stock_entry->items)->pluck('item_code'))->pluck('image_path', 'parent');

        return view('consignment.replenish_form', compact('stock_entry', 'item_images', 'assigned_consignment_stores'));
    }

    public function replenish_update(Request $request, $id){
        $state_before_update = [];
        try {
            $branch = $request->branch;
            $items = $request->items;
            $now = Carbon::now();

            $items_data = [];
            foreach ($items as $item_code => $item) {
                $name = isset($item['name']) ? $item['name'] : null;
                $price = (float) $item['price'];
                $qty = (int) $item['qty'];
                $remarks = $item['reason'];
                $status = $request->status == 'Draft' ? 'Pending' : $request->status;
                $amount = $price * $qty;
                $items_data[] = compact('name', 'item_code', 'price', 'qty', 'remarks', 'status', 'amount');
            }

            $data = [
                'target_warehouse' => $branch,
                'status' => $request->status,
                'transaction_date' => $now->toDateTimeString(),
                'items' => $items_data
            ];

            $response = $this->erpOperation('put', 'Consignment Stock Entry', $id, $data);
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating stock entry';
                throw new Exception($err);
            }

            return redirect()->back()->with('success', "$id successfully updated!");
        } catch (Exception $th) {
            throw $th;
            return redirect()->back()->with('error', $th->getMessage());
        }
        
    }

    public function replenish_form(Request $request){
        $assigned_consignment_stores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        return view('consignment.replenish_form', compact('assigned_consignment_stores'));
    }

    public function replenish_delete($id){
        try {
            $response = $this->erpOperation('delete', 'Consignment Stock Entry', $id);

            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while deleting the document';
                throw new Exception($err);
            }

            return redirect('consignment/replenish')->with('success', "$id Deleted.");
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function replenish_submit(Request $request){
        try {
            $branch = $request->branch;
            $items = $request->items;
            $now = Carbon::now();

            $items_data = [];
            foreach ($items as $item_code => $item) {
                $price = (float) $item['price'];
                $qty = (int) $item['qty'];
                $remarks = $item['reason'];
                $status = $request->status == 'Draft' ? 'Pending' : $request->status;
                $amount = $price * $qty;
                $items_data[] = compact('item_code', 'price', 'qty', 'remarks', 'status', 'amount');
            }

            $data = [
                'target_warehouse' => $branch,
                'purpose' => 'Stock Replenishment',
                'status' => $request->status,
                'items' => $items_data,
                'transaction_date' => $now->toDateTimeString()
            ];

            $response = $this->erpOperation('post', 'Consignment Stock Entry', null, $data);

            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while submitting Stock Entry';
                throw new Exception($err);
            }

            // return $response;

            return redirect('consignment/replenish');
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function promodiserDamageForm(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
                 
        return view('consignment.promodiser_damage_report_form', compact('assigned_consignment_store'));
    }

    // /promodiser/damage_report/submit
    public function submitDamagedItem(Request $request){
        $state_before_update = [];
        try {
            $item_codes = $request->item_code;
            $damaged_qty = preg_replace("/[^0-9 .]/", "", $request->damaged_qty);
            $reason = $request->reason;
            $branch = $request->branch;

            $now = Carbon::now();
            
            if(collect($damaged_qty)->min() <= 0){
                throw new Exception('Items cannot be less than or equal to zero.');
            }

            $items = Item::whereIn('item_code', $item_codes)
                ->with('bin', function ($query) use ($branch) {
                    $query->where('warehouse', $branch) 
                        ->select('item_code', 'consigned_qty', 'stock_uom', 'warehouse');
                })
                ->select('item_code', 'description', 'stock_uom')
                ->get();

            $validate_items = collect($items)->map(function ($item) use ($item_codes, $damaged_qty, $branch){
                $bin_details = collect($item->bin)->first();

                $item_code = $item->item_code;
                $consigned_qty = $bin_details->consigned_qty;

                if(!in_array($item_code, $item_codes)){
                    return "Item $item_code not found on $branch";
                }

                if(isset($damaged_qty[$item_code]) && $damaged_qty[$item_code] > $consigned_qty){
                    return "Damaged qty of Item $item_code is more than its available qty on $branch";
                }

                return null;
            })->filter()->first();

            if($validate_items){
                throw new Exception($validate_items);
            }
            
            $items = collect($items)->groupBy('item_code');
            $user = Auth::user()->full_name;

            foreach($item_codes as $item_code){
                $item_details = $items[$item_code][0];

                $qty = isset($damaged_qty[$item_code]) ? $damaged_qty[$item_code] : 0;
                $uom = $item_details->stock_uom;

                $data = [
                    'transaction_date' => $now->toDateTimeString(),
                    'branch_warehouse' => $branch,
                    'item_code' => $item_code,
                    'description' => $item_details->description,
                    'qty' => $qty,
                    'stock_uom' => $uom,
                    'damage_description' => isset($reason[$item_code]) ? $reason[$item_code] : 0,
                    'promodiser' => Auth::user()->name
                ];

                $response = $this->erpOperation('post', 'Consignment Damaged Item', null, $data);

                if(!isset($response['data'])){
                    throw new Exception($response['exception']);
                }

                $activity_log_data[] = $data;

                $response = $response['data'];
                $state_before_update['Consignment Damaged Item'][$response['name']] = 'delete';
            }

            $logs = [
                'subject' => "Damaged Item Report from $branch has been created by $user at $now",
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Damaged Item',
                'reference_name' => 'Consignment Damaged Item',
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => $user,
                'data' => json_encode($activity_log_data, true)
            ];

            $log = $this->erpOperation('post', 'Activity Log', null, $logs);

            if(isset($log['data'])){
                session()->flash('error', 'Activity Log not posted');
            }

            return redirect()->back()->with('success', 'Damage report submitted.');
        } catch (Exception $e) {
            $this->revertChanges($state_before_update);

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /damage_report/list
    public function damagedItems(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $damaged_items = DB::table('tabConsignment Damaged Item')->whereIn('branch_warehouse', $assigned_consignment_store)->orderBy('creation', 'desc')->paginate(10);

        $item_codes = collect($damaged_items->items())->map(function ($q){
            return $q->item_code;
        });

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("img/$image");
        });

        $no_img = $this->base64_image('/icon/no_img.png');

        $damaged_arr = [];
        foreach($damaged_items as $item){
            $img = isset($item_images[$item->item_code]) ? $item_images[$item->item_code] : $no_img;

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
                'status' => $item->status
            ];
        }

        return view('consignment.promodiser_damaged_list', compact('damaged_arr', 'damaged_items'));
    }

    // /damaged/return/{id}
    public function returnDamagedItem($id){
        DB::beginTransaction();
        try {
            $damaged_item = DB::table('tabConsignment Damaged Item')->where('name', $id)->first();
            $existing_source =  DB::table('tabBin')->where('warehouse', $damaged_item->branch_warehouse)->where('item_code', $damaged_item->item_code)->first();

            if(!$damaged_item || !$existing_source){
                return redirect()->back()->with('error', 'Item not found.');
            }

            if($damaged_item->status == 'Returned'){
                return redirect()->back()->with('error', 'Item is already returned.');
            }

            $existing_target =  DB::table('tabBin')->where('warehouse', 'Quarantine Warehouse - FI')->where('item_code', $damaged_item->item_code)->first();
            if ($existing_target) {
                // add qty to target quarantine wareghouse
                DB::table('tabBin')->where('name', $existing_target->name)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $existing_target->consigned_qty + $damaged_item->qty
                ]);
            } else {
                $latest_bin = DB::table('tabBin')->where('name', 'like', '%bin/%')->max('name');
                $latest_bin_exploded = explode("/", $latest_bin);
                $bin_id = (($latest_bin) ? $latest_bin_exploded[1] : 0) + 1;
                $bin_id = str_pad($bin_id, 7, '0', STR_PAD_LEFT);
                $bin_id = 'BIN/'.$bin_id;

                DB::table('tabBin')->insert([
                    'name' => $bin_id,
                    'creation' => Carbon::now()->toDateTimeString(),
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 0,
                    'idx' => 0,
                    'warehouse' => 'Quarantine Warehouse - FI',
                    'item_code' => $damaged_item->item_code,
                    'stock_uom' => $damaged_item->stock_uom,
                    'valuation_rate' => $existing_source->consignment_price,
                    'consigned_qty' => $damaged_item->qty,
                    'consignment_price' => $existing_source->consignment_price
                ]);
            }

            // deduct qty to source warehouse
            DB::table('tabBin')->where('name', $existing_source->name)->update([
               'modified' => Carbon::now()->toDateTimeString(),
               'modified_by' => Auth::user()->wh_user,
               'consigned_qty' => $existing_source->consigned_qty - $damaged_item->qty
            ]);

            DB::table('tabConsignment Damaged Item')->where('name', $id)->update([
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Returned'
            ]);

            $logs = [
                'name' => uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Damaged Item Report for '.number_format($damaged_item->qty).' '.$damaged_item->stock_uom.' of '.$damaged_item->item_code.' from '.$damaged_item->branch_warehouse.' has been returned to Quarantine Warehouse - FI by '.Auth::user()->full_name.' at '.Carbon::now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => Carbon::now()->toDateTimeString(),
                'reference_doctype' => 'Damaged Items',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            DB::table('tabActivity Log')->insert($logs);

            DB::commit();
            return redirect()->back()->with('success', 'Item Returned.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function getConsignmentWarehouses(Request $request){
        $search_str = $request->q ? explode(' ', $request->q) : [];

        $warehouses = DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')->where('docstatus', '<', 2)
            ->when($request->q, function ($query) use ($request, $search_str){
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('name', 'LIKE', "%".$str."%");
                    }

                    $q->orWhere('name', 'LIKE', "%".$request->q."%");
                });
            })
            ->select('name as id', 'name as text')->get();
        
        return response()->json($warehouses);
    }

    // /beginning_inv/get_received_items/{branch}
    public function getReceivedItems(Request $request, $branch){
        $search_str = explode(' ', $request->q);

        $sold_item_codes = [];
        $sold_qty = [];

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
            ->where('bin.warehouse', $branch)->select('bin.*', 'item.*')->get();

        $item_codes = collect($items)->pluck('item_code');

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("img/$image");
        });

        $no_img = $this->base64_image('/icon/no_img.png');

        $default_images = DB::table('tabItem')->whereIn('item_code', $item_codes)->whereNotNull('item_image_path')->select('item_code', 'item_image_path as image_path')->get(); // in case there are no saved images in Item Images
        $default_image = collect($default_images)->groupBy('item_code');

        $inventory_arr = DB::table('tabConsignment Beginning Inventory as inv')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'inv.name')
            ->where('inv.branch_warehouse', $branch)->where('inv.status', 'Approved')->where('item.status', 'Approved')->whereIn('item.item_code', $item_codes)
            ->select('item.item_code', 'item.price', 'inv.transaction_date')->get();

        $inventory = collect($inventory_arr)->groupBy('item_code');

        $items_arr = [];
        foreach($items as $item){
            $img = isset($item_images[$item->item_code]) ? $item_images[$item->item_code] : $no_img;

            $max = $item->consigned_qty * 1;

            $items_arr[] = [
                'id' => $item->item_code,
                'text' => $item->item_code.' - '.strip_tags($item->description),
                'description' => strip_tags($item->description),
                'max' => $max,
                'uom' => $item->stock_uom,
                'price' => '₱ '.number_format($item->consignment_price, 2),
                'transaction_date' => isset($inventory[$item->item_code]) ? $inventory[$item->item_code][0]->transaction_date : null,
                'img' => $img,
                'alt' => Str::slug(explode('.', $img)[0], '-')
            ];
        }

        $items_arr = collect($items_arr)->sortByDesc('max')->values()->all();

        return response()->json($items_arr);
    }

    // /stock_transfer/submit
    public function stockTransferSubmit(Request $request){
        try {
            $now = Carbon::now();

            $item_codes = array_filter(collect($request->item_code)->unique()->toArray());
            $transfer_qty = collect($request->item)->map(function ($q){
                return preg_replace("/[^0-9 .]/", "", $q);
            });
            $purpose = $request->transfer_as == 'Pull Out' ? 'Pull Out' : 'Store-to-Store Transfer';

            $item_transfer_details = $request->item;

            $source_warehouse = $request->source_warehouse;
            $target_warehouse = $request->transfer_as == 'Pull Out' ? 'Quarantine Warehouse - FI' : $request->target_warehouse;
          
            if(!$item_codes || !$transfer_qty){
                return redirect()->back()->with('error', 'Please select an item to return');
            }

            $min = collect($transfer_qty)->min();
            if($min['transfer_qty'] <= 0){ // if there are 0 return qty
                return redirect()->back()->with('error', 'Return Qty cannot be less than or equal to 0');
            }

            $bin = DB::table('tabBin as bin')->join('tabItem as item', 'item.item_code', 'bin.item_code')
                ->where('bin.warehouse', $source_warehouse)->whereIn('bin.item_code', $item_codes)
                ->select('item.item_code', 'item.description as item_description', 'item.stock_uom as uom', 'bin.consignment_price as price')
                ->get();

            $items = collect($bin)->map(function ($item) use ($transfer_qty, $item_transfer_details){
                $item_code = $item->item_code;
                $transfer_qty = isset($transfer_qty[$item_code]['transfer_qty']) ? (float) $transfer_qty[$item_code]['transfer_qty'] : 0;
                $item->qty = $transfer_qty;
                $item->amount = $transfer_qty * $item->price;
                $item->cost_center = 'Main - FI';
                $item->item_description = strip_tags($item->item_description);
                $item->remarks = 'Generated in AthenaERP';
                $item->reason = isset($item_transfer_details[$item_code]['reason']) ? $item_transfer_details[$item_code]['reason'] : null;

                return $item;
            });

            $data = [
                'source_warehouse' => $source_warehouse,
                'target_warehouse' => $target_warehouse,
                'purpose' => $request->transfer_as,
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpOperation('post', 'Consignment Stock Entry', null, $data);

            if(!isset($response['data'])){
                throw new Exception($response['exc_type']);
            }

            $user = Auth::user()->full_name;
            $logs = [
                'subject' => "$purpose request from $source_warehouse to $target_warehouse has been created by $user at $now",
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $log = $this->erpOperation('post', 'Activity Log', null, $logs);

            if(!isset($log['data'])){
                session()->flash('warning', 'Activity Log not posted.');
            }

            return redirect()->route('stock_transfers', ['purpose' => $purpose])->with('success', 'Stock transfer request has been submitted.');
        } catch (\Throwable $e) {
            // throw $e;
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /stock_transfer/form
    public function stockTransferForm(Request $request){
        $action = $request->action;
        $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.stock_transfer_form', compact('assigned_consignment_stores', 'action'));
    }

    // /item_return/form
    public function itemReturnForm(){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        return view('consignment.item_returns_form', compact('assigned_consignment_store'));
    }

    // /item_return/submit
    public function itemReturnSubmit(Request $request){
        try{
            $items = $request->item;
            $now = Carbon::now();

            $item_details = DB::table('tabItem as p')
                ->join('tabBin as c', 'p.name', 'c.item_code')
                ->where('c.warehouse', $request->target_warehouse)->whereIn('p.name', array_keys($items))
                ->get(['p.name', 'p.description', 'p.stock_uom', 'c.consignment_price', 'c.consigned_qty', 'c.name as bin_id']);

            $ste_details = [];
            foreach($item_details as $item){
                if(!isset($items[$item->name])){
                    continue;
                }
                
                $transfer_detail = $items[$item->name];
                $item_code = $item->name;

                $bin = $this->erpOperation('put', 'Bin', $item->bin_id, ['consigned_qty' => (float)$item->consigned_qty + (float)$transfer_detail['qty']]);

                $ste_details[] = [
                    'item_code' => $item_code,
                    'item_description' => isset($item_details[$item_code]) ? $item_details[$item_code][0]->description : null,
                    'uom' => isset($item_details[$item_code]) ? $item_details[$item_code][0]->stock_uom : null,
                    'qty' => (float)$transfer_detail['qty'],
                    'price' => (float)$item->consignment_price,
                    'amount' => $item->consignment_price * $transfer_detail['qty'],
                    'reason' => $transfer_detail['reason']
                ];

                $activity_logs_details[$item_code]['quantity'] = [
                    'previous' => $item->consigned_qty,
                    'new' => $item->consigned_qty + (float)$transfer_detail['qty'],
                    'returned' => (float)$transfer_detail['qty']
                ];
            }

            $data = [
                'target_warehouse' => $request->target_warehouse,
                'purpose' => 'Item Return',
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'remarks' => $request->remarks,
                'items' => $ste_details
            ];

            $response = $this->erpOperation('post', 'Consignment Stock Entry', null, $data);

            if(!isset($response['data'])){
                throw new Exception($response['exc_type']);
            }

            $log_data = [
                'subject' => 'Item Return  to '.$request->target_warehouse.' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activity_logs_details, true)
            ];

            $log = $this->erpOperation('post', 'Activity Log', null, $log_data);

            if(!isset($log['data'])){
                session()->flash('warning', 'Activity Log not posted');
            }

            return redirect()->back()->with('success', 'Transaction Recorded.');
        } catch (\Throwable $th) {
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    // /stock_transfer/cancel/{id}
    public function stockTransferCancel($id){
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $stock_entry = DB::table('tabConsignment Stock Entry')->where('name', $id)->first();
            if(!$stock_entry){
                return redirect()->back()->with('error', 'Record not found.');
            }

            if ($stock_entry->status == 'Completed') {
                return redirect()->back()->with('error', 'Unable to cancel. Request is already COMPLETED.');
            }

            $response = $this->erpOperation('put', 'Consignment Stock Entry', $stock_entry->name, ['status' => 'Cancelled']);

            if(!isset($response['data'])){
                throw new Exception($response['exc_type']);
            }

            if($stock_entry->purpose == 'Item Return'){
                $stock_entry_items = DB::table('tabConsignment Stock Entry Detail')->where('parent', $id)->get();

                $items = DB::table('tabBin')->where('warehouse', $stock_entry->target_warehouse)->whereIn('item_code', collect($stock_entry_items)->pluck('item_code'))->get()->groupBy('item_code');

                foreach ($stock_entry_items as $item) {
                    if(isset($items[$item->item_code]))

                    $item_details = $items[$item->item_code][0];
                    $bin = $this->erpOperation('put', 'Bin', $item_details->name, ['consigned_qty' => $item_details->consigned_qty > $item->qty ? $item_details->consigned_qty - $item->qty : 0]);
                }
            }

            $source_warehouse = $stock_entry->source_warehouse;
            $target_warehouse = $stock_entry->target_warehouse;
            $transaction = $stock_entry->purpose;

            $logs = [
                'subject' => $transaction.' request from '.$source_warehouse.' to '.$target_warehouse.' has been cancelled by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $log = $this->erpOperation('post', 'Activity Log', null, $logs);

            if(!isset($log['data'])){
                session()->flash('warning', 'Activity Log not posted');
            }

            return redirect()->route('stock_transfers', ['purpose' => $stock_entry->purpose])->with('success', $transaction.' has been cancelled.');
        } catch (\Throwable $e) {
            // throw $e;
            return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
        }
    }

    // stock_transfer/list
    public function stockTransferList(Request $request){
        $purpose = $request->purpose;

        $consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        if($request->ajax()){
            $ref_warehouse = $purpose == 'Item Return' ? 'target_warehouse' : 'source_warehouse';
            $stock_transfers = DB::table('tabConsignment Stock Entry')
                ->whereIn($ref_warehouse, $consignment_stores)
                ->where('purpose', $purpose)
                ->orderBy('creation', 'desc')->paginate(10);

            $warehouses = collect($stock_transfers->items())->map(function ($q) use ($ref_warehouse){
                return $q->$ref_warehouse;
            });

            $reference_ste = collect($stock_transfers->items())->map(function ($q){
                return $q->name;
            });

            $stock_transfer_items = DB::table('tabConsignment Stock Entry Detail')->whereIn('parent', $reference_ste)->get();
            $stock_transfer_item = collect($stock_transfer_items)->groupBy('parent');
            
            $item_codes = collect($stock_transfer_items)->map(function ($q){
                return $q->item_code;
            });

            $bin = DB::table('tabBin')->whereIn('warehouse', $warehouses)->whereIn('item_code', $item_codes)->get();
            $bin_arr = [];
            foreach($bin as $b){
                $bin_arr[$b->warehouse][$b->item_code] = [
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->pluck('image_path', 'parent');
            $item_images = collect($item_images)->map(function ($image){
                return $this->base64_image("img/$image");
            });

            $no_img = $this->base64_image('/icon/no_img.png');

            $ste_arr = [];
            foreach($stock_transfers as $ste){
                $items_arr = [];
                if(isset($stock_transfer_item[$ste->name])){
                    foreach($stock_transfer_item[$ste->name] as $item){
                        $img = isset($item_images[$item->item_code]) ? $item_images[$item->item_code] : $no_img;

                        $items_arr[] = [
                            'item_code' => $item->item_code,
                            'description' => $item->item_description,
                            'consigned_qty' => isset($bin_arr[$ste->$ref_warehouse][$item->item_code]) ? $bin_arr[$ste->$ref_warehouse][$item->item_code]['consigned_qty'] : 0,
                            'transfer_qty' => $item->qty,
                            'uom' => $item->uom,
                            'image' => $img,
                            'return_reason' => $item->reason
                        ];
                    }
                }

                $ste_arr[] = [
                    'name' => $ste->name,
                    'title' => $ste->title,
                    'from_warehouse' => $ste->source_warehouse,
                    'to_warehouse' => $ste->target_warehouse,
                    'status' => $ste->status,
                    'items' => $items_arr,
                    'owner' => ucwords(str_replace('.', ' ', explode('@', $ste->owner)[0])),
                    'docstatus' => $ste->docstatus,
                    'transfer_type' => $ste->purpose,
                    'date' => $ste->creation,
                    'remarks' => $ste->remarks,
                ];
            }

            return view('consignment.stock_transfers_table', compact('stock_transfers', 'ste_arr', 'purpose'));
        }

        return view('consignment.stock_transfers_list', compact('purpose'));
    }

    // /inventory_audit
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
    
            $inventory_audit_per_warehouse = DB::table('tabConsignment Inventory Audit Report')
                ->whereIn('branch_warehouse', array_keys($stores_with_beginning_inventory))
                ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
                ->toArray();
    
            $end = Carbon::now()->endOfDay();
    
            $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
        
            $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

            $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();
    
            if ($first_cutoff->gt($end)) {
                $end = $first_cutoff;
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
                    }
    
                    $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . Carbon::now()->format('F d, Y');
                    if (Carbon::parse($start)->addDay()->startOfDay()->lte(Carbon::now()->startOfDay())) {
                        if ($last_audit_date->endOfDay()->lt($end) && $beginning_inventory_transaction_date) {
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

                if(!$beginning_inventory_transaction_date || !$last_inventory_audit_date) {
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

        // get previous cutoff
        $current_cutoff = $this->getCutoffDate(Carbon::now()->endOfDay());
        $previous_cutoff = $this->getCutoffDate($current_cutoff[0]);

        $previous_cutoff_start = $previous_cutoff[0];
        $previous_cutoff_end = $previous_cutoff[1];

        $previous_cutoff_display = Carbon::parse($previous_cutoff_start)->format('M. d, Y') . ' - ' . Carbon::parse($previous_cutoff_end)->format('M. d, Y');
        $previous_cutoff_sales = $this->getSalesAmount(Carbon::parse($previous_cutoff_start)->format('Y-m-d'), Carbon::parse($previous_cutoff_end)->format('Y-m-d'), null);

        $consignment_branches = DB::table('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->join('tabWarehouse as w', 'w.name', 'acw.warehouse')
            ->where('wu.user_group', 'Promodiser')->where('w.is_group', 0)
            ->where('w.disabled', 0)->distinct()->pluck('w.name')->count();

        $stores_with_submitted_report = DB::table('tabConsignment Inventory Audit Report')
            ->where('cutoff_period_from', Carbon::parse($previous_cutoff_start)->format('Y-m-d'))
            ->where('cutoff_period_to', Carbon::parse($previous_cutoff_end)->format('Y-m-d'))
            ->distinct()->pluck('branch_warehouse')->count();

        $displayed_data = [
            'recent_period' => $previous_cutoff_display,
            'stores_submitted' => $stores_with_submitted_report,
            'stores_pending' => $consignment_branches - $stores_with_submitted_report,
            'total_sales' => '₱ ' . number_format($previous_cutoff_sales, 2) 
        ];

        $promodisers = DB::table('tabWarehouse Users')->where('enabled', 1)->where('user_group', 'Promodiser')->pluck('full_name');

        return view('consignment.supervisor.view_inventory_audit', compact('assigned_consignment_stores', 'select_year', 'displayed_data', 'promodisers'));
    }

    // /submitted_inventory_audit
    public function getSubmittedInvAudit(Request $request) {
        $store = $request->store;
        $year = $request->year;

        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? true : false;
        if ($is_promodiser) {
            $assigned_consignment_stores = DB::table('tabAssigned Consignment Warehouse')
                ->where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')
                ->distinct()->pluck('warehouse')->toArray();

            $query = DB::table('tabConsignment Inventory Audit Report')
                ->when($store, function ($q) use ($store){
                    return $q->where('branch_warehouse', $store);
                })
                ->when($year, function ($q) use ($year){
                    return $q->whereYear('audit_date_to', $year);
                })
                ->whereIn('branch_warehouse', $assigned_consignment_stores)
                ->select('audit_date_from', 'audit_date_to', 'branch_warehouse', 'status', 'promodiser', 'transaction_date', 'cutoff_period_from', 'cutoff_period_to')
                ->groupBy('branch_warehouse', 'audit_date_to', 'audit_date_from', 'status', 'promodiser', 'transaction_date', 'cutoff_period_from', 'cutoff_period_to')
                ->orderBy('audit_date_from', 'desc')
                ->paginate(10);

            $result = [];
            foreach ($query as $row) {
                $result[$row->branch_warehouse][] = [
                    'audit_date_from' => $row->audit_date_from,
                    'audit_date_to' => $row->audit_date_to,
                    'status' => $row->status,
                    'promodiser' => $row->promodiser,
                    'date_submitted' => $row->transaction_date
                ];
            }

            return view('consignment.tbl_submitted_inventory_audit', compact('result', 'query'));
        }

        $list = DB::table('tabConsignment Inventory Audit Report')
            ->when($store, function ($q) use ($store){
                return $q->where('branch_warehouse', $store);
            })
            ->when($year, function ($q) use ($year){
                return $q->whereYear('audit_date_from', $year)->orWhereYear('audit_date_to', $year);
            })
            ->when($request->promodiser, function ($q) use ($request){
                return $q->where('promodiser', $request->promodiser);
            })
            ->selectRaw('name, audit_date_from, audit_date_to, branch_warehouse, transaction_date, promodiser')
            ->orderBy('audit_date_to', 'desc')->paginate(25);

        $audit_items = DB::table('tabConsignment Inventory Audit Report Item')
            ->whereIn('parent', collect($list->items())->pluck('name'))
            ->selectRaw('SUM(qty) as total_item_qty, COUNT(item_code) as total_items, parent')
            ->groupBy('parent')->get()->groupBy('parent')->toArray();

        $result = [];
        foreach ($list as $row) {
            $total_items = $total_item_qty = 0;
            if (isset($audit_items[$row->name])) {
                $total_items = $audit_items[$row->name][0]->total_items;
                $total_item_qty = $audit_items[$row->name][0]->total_item_qty;
            }

            $result[] = [
                'transaction_date' => $row->transaction_date,
                'audit_date_from' => $row->audit_date_from,
                'audit_date_to' => $row->audit_date_to,
                'branch_warehouse' => $row->branch_warehouse,
                'total_items' => $total_items,
                'total_item_qty' => $total_item_qty,
                'promodiser' => $row->promodiser
            ];
        }

        return view('consignment.supervisor.tbl_inventory_audit_history', compact('list', 'result'));
    }

    public function viewInventoryAuditItems($store, $from, $to, Request $request) {
        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? true : false;

        $list = DB::table('tabConsignment Inventory Audit Report as cia')
            ->join('tabConsignment Inventory Audit Report Item as ciar', 'cia.name', 'ciar.parent')
            ->join('tabItem as i', 'i.name', 'ciar.item_code')
            ->where('branch_warehouse', $store)->where('audit_date_from', $from)->where('audit_date_to', $to)
            ->select('cia.name as inventory_audit_id', 'cia.*', 'i.*', 'ciar.*')
            ->get();

        $activity_logs = DB::table('tabActivity Log')->whereIn('reference_name', collect($list)->pluck('inventory_audit_id'))->select('reference_name', 'data')->orderBy('creation', 'desc')->first();

        $activity_logs_data = $activity_logs ? collect(json_decode($activity_logs->data)) : [];

        if (count($list) <= 0) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        $first_record = collect($list)->first();

        $previous_inventory_audit = DB::table('tabConsignment Inventory Audit Report')
            ->where('branch_warehouse', $store)->whereDate('transaction_date', '<', $first_record->transaction_date)
            ->orderBy('transaction_date', 'desc')->first();

        $start = $from;
        if ($previous_inventory_audit) {
           $start = Carbon::parse($previous_inventory_audit->transaction_date)->addDays(1)->format('Y-m-d');
        }

        $total_sales = $this->getSalesAmount(Carbon::parse($start)->startOfDay(), Carbon::parse($to)->endOfDay(), $store);
       
        $duration = Carbon::parse($from)->format('F d, Y') . ' - ' . Carbon::parse($to)->format('F d, Y');

        $item_codes = collect($list)->pluck('item_code');

        $beginning_inventory = DB::table('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->where('cb.status', 'Approved')->whereIn('cbi.item_code', $item_codes)->where('cb.branch_warehouse', $store)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($to)->endOfDay())
            ->select('cbi.item_code', 'cb.transaction_date', 'opening_stock')
            ->orderBy('cb.transaction_date', 'desc')->get();

        $beginning_inventory = collect($beginning_inventory)->groupBy('item_code')->toArray();

        $inv_audit = DB::table('tabConsignment Inventory Audit Report as cia')->join('tabConsignment Inventory Audit Report Item as ciar', 'cia.name', 'ciar.parent')
            ->where('branch_warehouse', $store)->where('transaction_date', '<', $from)
            ->select('item_code', 'qty', 'transaction_date')
            ->orderBy('transaction_date', 'asc')->get();

        $inv_audit = collect($inv_audit)->groupBy('item_code')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("img/$image");
        });

        $no_img = $this->base64_image('icon/no_img.png');

        $result = [];
        foreach ($list as $row) {
            $id = $row->item_code;

            $img = isset($item_images[$id]) ? $item_images[$id] : $no_img;
            $opening_qty = array_key_exists($id, $inv_audit) ? $inv_audit[$id][0]->qty : 0;

            if (array_key_exists($id, $inv_audit)) {
                $opening_qty = $inv_audit[$id][0]->qty;
            } else {
                $opening_qty = array_key_exists($id, $beginning_inventory) ? $beginning_inventory[$id][0]->opening_stock : 0;
            }

            if(!$is_promodiser) {
                $description = explode(',', strip_tags($row->description));

                $description_part1 = array_key_exists(0, $description) ? trim($description[0]) : null;
                $description_part2 = array_key_exists(1, $description) ? trim($description[1]) : null;
                $description_part3 = array_key_exists(2, $description) ? trim($description[2]) : null;
                $description_part4 = array_key_exists(3, $description) ? trim($description[3]) : null;
    
                $displayed_description = $description_part1 . ', ' . $description_part2 . ', ' . $description_part3 . ', ' . $description_part4;
            } else {
                $displayed_description = $row->description;
            }
            
            $result[] = [
                'item_code' => $id,
                'description' => $displayed_description,
                'item_classification' => $row->item_classification,
                'price' => $row->price,
                'amount' => $row->amount,
                'img' => $img,
                // 'img_webp' => $webp,
                // 'img_count' => $img_count,
                'opening_qty' => number_format($opening_qty),
                'previous_qty' => number_format($row->available_stock_on_transaction),
                'audit_qty' => number_format($row->qty),
                'sold_qty' => isset($activity_logs_data[$row->item_code]) ? collect($activity_logs_data[$row->item_code])['sold_qty'] : 0
            ];
        }

        if($is_promodiser) {
            $item_classification = collect($result)->groupBy('item_classification');

            return view('consignment.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'item_classification'));
        }

        $next_record = DB::table('tabConsignment Inventory Audit Report')
            ->where('branch_warehouse', $store)->where('transaction_date', '>', $list[0]->transaction_date)
            ->where('name', '!=', $list[0]->name)->orderBy('transaction_date', 'asc')->first();

        $previous_record = DB::table('tabConsignment Inventory Audit Report')
            ->where('branch_warehouse', $store)->where('transaction_date', '<', $list[0]->transaction_date)
            ->where('name', '!=', $list[0]->name)->orderBy('transaction_date', 'desc')->first();
        
        $next_record_link = $previous_record_link = null;
        $sales_increase = true;
        $previous_sales_record = 0;
        if ($next_record) {
            $next_record_link = "/view_inventory_audit_items/". $store ."/".$next_record->audit_date_from."/".$next_record->audit_date_to;
        }

        if ($previous_record) {
            $previous_record_link = "/view_inventory_audit_items/". $store ."/".$previous_record->audit_date_from."/".$previous_record->audit_date_to;

            $previous_sales_record = $this->getSalesAmount(Carbon::parse($previous_record->audit_date_from)->startOfDay(), Carbon::parse($previous_record->audit_date_to)->endOfDay(), $store);

            $sales_increase = $total_sales > $previous_sales_record ? true : false;
        }

        $ste_received_items = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)
            ->where('sted.t_warehouse', $store)->where('sted.consignment_status', 'Received')
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, sted.consignment_date_received, sted.consignment_received_by, ste.delivery_date')
            ->orderBy('sted.consignment_date_received', 'desc')->get();

        $ste_returned_items = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['For Return'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $store)
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, ste.creation, sted.t_warehouse')
            ->orderBy('sted.creation', 'desc')->get();

        $ste_transferred_items = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['Store Transfer'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $store)
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, sted.t_warehouse, ste.creation, sted.consignment_date_received, sted.consignment_received_by')
            ->orderBy('sted.creation', 'desc')->get();

        $damaged_items = DB::table('tabConsignment Damaged Item')
            ->where('branch_warehouse', $store)
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date', 'desc')->get();

        $received_items = [];
        foreach ($ste_received_items as $row) {
            $received_items[$row->item_code][] = [
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                'delivery_date' => Carbon::parse($row->delivery_date)->format('M. d, Y'),
                'date_received' => Carbon::parse($row->consignment_date_received)->format('M. d, Y h:i A'),
                'received_by' => $row->consignment_received_by
            ];
        }

        $returned_items = [];
        foreach ($ste_returned_items as $row) {
            $returned_items[$row->item_code][] = [
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                't_warehouse' => $row->t_warehouse
            ];
        }

        $transferred_items = [];
        foreach ($ste_transferred_items as $row) {
            $transferred_items[$row->item_code][] = [
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                't_warehouse' => $row->t_warehouse,
                'date_received' => Carbon::parse($row->consignment_date_received)->format('M. d, Y h:i A'),
                'received_by' => $row->consignment_received_by
            ];
        }

        $damaged_item_list = [];
        foreach ($damaged_items as $row) {
            $damaged_item_list[$row->item_code][] = [
                'qty' => $row->qty * 1,
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'damage_description' => $row->damage_description,
                'stock_uom' => $row->stock_uom
            ];
        }

        $promodisers = DB::table('tabConsignment Inventory Audit Report')
            ->where('branch_warehouse', $store)->where('audit_date_from', $from)
            ->where('audit_date_to', $to)->distinct()->pluck('promodiser')->toArray();
            
        $promodisers = implode(', ', $promodisers);

        return view('consignment.supervisor.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'promodisers', 'received_items', 'previous_record_link', 'next_record_link', 'sales_increase', 'transferred_items', 'returned_items', 'damaged_item_list', 'total_sales'));
    }

    private function check_item_transactions($item_code, $branch, $date, $csa_id = null){
        $transaction_date = Carbon::parse($date);
        $now = Carbon::now();

        $has_stock_entry = StockEntry::whereHas('items', function ($item) use ($now, $transaction_date, $item_code, $branch){
                $item->where('item_code', $item_code)->whereBetween('consignment_date_received', [$transaction_date, $now])->where('s_warehouse', $branch);
            })
            ->whereIn('transfer_as', ['Consignment', 'For Return', 'Store Transfer'])->where('item_status', ['For Checking', 'Issued'])->where('purpose', 'Material Transfer')->where('docstatus', 1)
            ->exists();

        $has_damaged_items = ConsignmentDamagedItems::where('branch_warehouse', $branch)->where('item_code', $item_code)->whereBetween('transaction_date', [$transaction_date, $now])->exists();

        $has_stock_adjustments = ConsignmentStockAdjustment::whereHas('items', function ($item) use ($item_code){
                $item->where('item_code', $item_code);
            })
            ->whereBetween('creation', [$transaction_date, $now])
            ->where('warehouse', $branch)->where('status', '!=', 'Cancelled')
            ->when($csa_id != null, function ($q) use ($csa_id){
                return $q->where('name', '!=', $csa_id);
            })->exists();

        return [
            'ste_transactions' => $has_stock_entry,
            'damaged_transactions' => $has_damaged_items,
            'stock_adjustment_transactions' => $has_stock_adjustments
        ];
    }

    public function cancelStockAdjustment($id){
        DB::beginTransaction();
        try{
            $adjustment_details = DB::table('tabConsignment Stock Adjustment')->where('name', $id)->first();

            if(!$adjustment_details){
                return redirect()->back()->with('error', 'Stock adjustment record not found.');
            }

            if($adjustment_details->status == 'Cancelled'){
                return redirect()->back()->with('error', 'Stock adjustment is already cancelled');
            }

            $adjusted_items = DB::table('tabConsignment Stock Adjustment Items')->where('parent', $adjustment_details->name)->get();

            if(!$adjusted_items){
                return redirect()->back()->with('error', 'Items not found.');
            }

            foreach($adjusted_items as $item){
                $has_transactions = $this->check_item_transactions($item->item_code, $adjustment_details->warehouse, $adjustment_details->creation, $id);

                if(collect($has_transactions)->max() > 0){
                    return redirect()->back()->with('error', 'Cannot cancel stock adjustment record. Item '.$item->item_code.' has existing transaction(s).');
                }
                
                DB::table('tabBin')->where('item_code', $item->item_code)->where('warehouse', $adjustment_details->warehouse)->update([
                    'modified' => Carbon::now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $item->previous_qty,
                    'consignment_price' => $item->previous_price
                ]);
            }

            DB::table('tabConsignment Stock Adjustment')->where('name', $id)->update([
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ]);

            $logs = [
                'name' => uniqid(),
                'creation' => Carbon::now()->toDateTimeString(),
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Adjustment '.$adjustment_details->name.' has been cancelled by '.Auth::user()->full_name.' at '.Carbon::now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => Carbon::now()->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Adjustment',
                'reference_name' => $adjustment_details->name,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            DB::table('tabActivity Log')->insert($logs);

            DB::commit();
            return redirect()->back()->with('success', 'Stock Adjustment Cancelled.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function viewStockAdjustmentHistory(Request $request){
        $stock_adjustments = ConsignmentStockAdjustment::with('items')->when($request->branch_warehouse, function ($q) use ($request){
                return $q->where('warehouse', $request->branch_warehouse);
            })
            ->orderBy('creation', 'desc')->paginate(10);

        $item_codes = collect($stock_adjustments->items())->flatMap(function($stock_adjustment) {
            return $stock_adjustment->items->pluck('item_code');
        })->unique()->values();

        $flatten_item_codes = $item_codes->implode("','");

        $item_images = ItemImages::whereRaw("parent IN ('$flatten_item_codes')")->pluck('image_path', 'parent');

        $stock_adjustments_array = collect($stock_adjustments->items())->map(function ($stock_adjustment) use ($item_images){
            $warehouse = $stock_adjustment->warehouse;
            $creation = $stock_adjustment->creation;
            $stock_adjustment->items = collect($stock_adjustment->items)->map(function ($item) use ($item_images, $warehouse, $creation){
                $item_code = $item->item_code;
                $transactions = $this->check_item_transactions($item_code, $warehouse, $creation, $item->parent);

                $item->transactions = $transactions;
                $item->has_transactions = in_array(true, $transactions);

                $item->previous_qty = (int) $item->previous_qty;
                $item->previous_price = (float) $item->previous_price;

                $item->new_qty = (int) $item->new_qty;
                $item->new_price = (float) $item->new_price;

                $item->item_description = strip_tags($item->item_description);

                $item->reason = $item->remarks;

                $item->image = isset($item_images[$item_code]) ? '/img/'.$item_images[$item_code] : '/icon/no_img.png';
                if(Storage::disk('public')->exists(explode('.', $item->image)[0].'.webp')){
                    $item->image = explode('.', $item->image)[0].'.webp';
                }

                return $item;
            });

            $stock_adjustment->transaction_date = Carbon::parse("$stock_adjustment->transaction_date $stock_adjustment->transaction_time")->format('M. d, Y h:i A');
            $stock_adjustment->has_transactions = in_array(true, collect($stock_adjustment->items)->pluck('has_transactions')->toArray());

            return $stock_adjustment;
        });

        return view('consignment.supervisor.view_stock_adjustment_history', compact('stock_adjustments', 'stock_adjustments_array'));
    }

    public function viewStockAdjustmentForm(){
        $item = DB::table('tabBin')->join('tabItem', 'tabItem.name', 'tabBin.item_code')->select('tabItem.*')->orderByDesc('tabBin.creation')->first();
        return view('consignment.supervisor.adjust_stocks', compact('item'));
    }

    public function adjustStocks(Request $request){
        $state_before_update = [];
        try {
            if(!$request->warehouse){
                throw new Exception('Please select a warehouse');
            }

            $now = Carbon::now();
            $branch = $request->warehouse;
            $item_codes = $request->item_codes;
            $input = $request->item;

            if(!$item_codes || !$input){
                throw new Exception('Please select an Item.');
            }
            
            $item_details = Item::whereIn('name', $item_codes)
                ->with('bin', function ($bin) use ($branch){
                    $bin->where('warehouse', $branch)
                        ->select('name', 'item_code', 'consigned_qty', 'consignment_price', 'modified', 'modified_by');
                })
                ->select('item_code', 'description', 'stock_uom')
                ->get();
            
            if(!$item_details){
                throw new Exception('No items found.');
            }

            $consignment_items = $activity_logs = [];
            foreach($item_details as $item){
                $item_code = $item->item_code;
                $bin = collect($item->bin)->first();

                $bin_id = $bin->name;
                unset($bin->name, $bin->item_code);

                $state_before_update['Bin'][$bin_id] = $bin;

                $new_stock = preg_replace("/[^0-9]/", null, $input[$item_code]['qty']);
                $new_stock = $new_stock ? $new_stock * 1 : 0;

                $new_price = preg_replace("/[^0-9 .]/", null, $input[$item_code]['price']);
                $new_price = $new_price ? $new_price * 1 : 0;

                $update = [];

                if($bin->consigned_qty != $new_stock){
                    $update['consigned_qty'] = $new_stock;
                    $activity_logs[$branch][$item_code]['quantity'] = [
                        'previous' => $bin->consigned_qty,
                        'new' => $new_stock
                    ];
                }

                if($bin->consignment_price != $new_price){
                    $update['consignment_price'] = $new_price;
                    $activity_logs[$branch][$item_code]['price'] = [
                        'previous' => $bin->consignment_price,
                        'new' => $new_price
                    ];
                }

                $item_remarks = isset($item_details[$item_code]['remarks']) ? $item_details[$item_code]['remarks'] : null;

                if(!$update){
                    continue;
                }

                $bin_response = $this->erpOperation('put', 'Bin', $bin_id, $update);

                if(!isset($bin_response['data'])){
                    throw new Exception($bin_response['exception']);
                }

                $consignment_items[] = [
                    'item_code' => $item_code,
                    'item_description' => $item->description,
                    'uom' => $item->stock_uom,
                    'previous_qty' => $bin->consigned_qty,
                    'new_qty' => $new_stock,
                    'previous_price' => $bin->consignment_price,
                    'new_price' => $new_price,
                    'remarks' => $item_remarks
                ];
            }

            $consignment_data = [
                'warehouse' => $request->warehouse,
                'created_by' => Auth::user()->wh_user,
                'transaction_date' => $now->toDateString(),
                'transaction_time' => $now->toTimeString(),
                'remarks' => $request->notes,
                'items' => $consignment_items
            ];

            $consignment_response = $this->erpOperation('post', 'Consignment Stock Adjustment', null, $consignment_data);

            if(!isset($consignment_response['data'])){
                throw new Exception($consignment_response['exception']);
            }

            $consignment_id = $consignment_response['data']['name'];

            DB::table('tabActivity Log')->insert([
                'name' => uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Adjustment for '. $request->warehouse.' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Adjustment',
                'reference_name' => $consignment_id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activity_logs, true)
            ]);

            // Send Email Notification to assigned Promodisers
            $images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->get()->groupBy('parent');

            $promodisers = DB::table('tabAssigned Consignment Warehouse as acw')
                ->join('tabWarehouse Users as wu', 'wu.frappe_userid', 'acw.parent')
                ->where('acw.warehouse', $branch)->pluck('wu.wh_user');

            $promodisers = collect($promodisers)->map(function ($q){
                return str_replace('.local', '.com', $q);
            });

            $mail_data = [
                'warehouse' => $branch,
                'images' => $images,
                'reference_no' => $consignment_id,
                'created_by' => Auth::user()->wh_user,
                'created_at' => Carbon::now()->format('M d, Y h:i A'),
                'logs' => $activity_logs,
                'notes' => $request->notes
            ];

            if($promodisers){
                foreach ($promodisers as $promodiser) {
                    try {
                        Mail::send('mail_template.stock_adjustments', $mail_data, function($message) use ($promodiser){
                            $message->to($promodiser);
                            $message->subject('AthenaERP - Stock Adjustment');
                        });
                    } catch (\Throwable $e) {
                        session()->flash('error', 'An error occured while sending notification email');
                    }
                }
            }

            session()->flash('success', 'Warehouse Stocks Adjusted.');
            return redirect('/beginning_inv_list');
        } catch (\Throwable $e) {
            $this->revertChanges($state_before_update);
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /stock_adjust/submit/{id}
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

            $bin = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();
            $bin = collect($bin)->groupBy('item_code');

            $cbi_items = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->get();
            $cbi_items = collect($cbi_items)->groupBy('item_code');

            $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();
            $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

            $total_received_qty = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->whereDate('sted.consignment_date_received', '>=', $beginning_inventory_start_date)->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])->whereIn('ste.item_status', ['For Checking', 'Issued'])->where('ste.purpose', 'Material Transfer')->where('ste.docstatus', 1)->whereIn('sted.item_code', $item_codes)->where('sted.t_warehouse', $beginning_inventory->branch_warehouse)->where('sted.consignment_status', 'Received')
                ->selectRaw('sted.item_code, SUM(sted.transfer_qty) as qty')
                ->groupBy('sted.item_code')->get();
            $total_received_qty = collect($total_received_qty)->groupBy('item_code');

            $activity_logs_data = [];
            foreach($item_codes as $item_code){
                if(isset($stocks[$item_code]) && isset($cbi_items[$item_code])){
                    $previous_stock = isset($bin[$item_code]) ? (float)$bin[$item_code][0]->opening_stock : 0;
                    $previous_price = (float)$cbi_items[$item_code][0]->price;

                    $opening_qty = (float)preg_replace("/[^0-9]/", "", $stocks[$item_code]['qty']);
                    $price = (float)preg_replace("/[^0-9 .]/", "", $stocks[$item_code]['price']);

                    if($previous_stock == $opening_qty && $previous_price == $price){
                        continue;
                    }

                    $cbi_array = $cbi_stock_array = $cbi_price_array = [];
                    $bin_array = $bin_stock_array = $bin_price_array = [];
                    $update_array = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user
                    ];

                    if($previous_stock != $opening_qty){
                        $total_received = isset($total_received_qty[$item_code]) ? $total_received_qty[$item_code][0]->qty : 0;

                        $updated_stocks = $opening_qty + $total_received;
                        $updated_stocks = $updated_stocks > 0 ? $updated_stocks : 0;

                        $bin_stock_array = ['consigned_qty' => $updated_stocks];
                        $cbi_stock_array = ['opening_stock' => $opening_qty];

                        $activity_logs_data[$item_code]['previous_qty'] = $previous_stock;
                        $activity_logs_data[$item_code]['new_qty'] = $opening_qty;
                    }

                    if($previous_price != $price){
                        $bin_stock_array = ['consignment_price' => $price];
                        $cbi_price_array = [
                            'price' => $price,
                            'amount' => $price * $opening_qty
                        ];

                        $activity_logs_data[$item_code]['previous_price'] = $previous_price;
                        $activity_logs_data[$item_code]['new_price'] = $price;
                    }

                    $cbi_array = array_merge($update_array, $cbi_stock_array, $cbi_price_array);
                    $bin_array = array_merge($update_array, $bin_stock_array, $bin_price_array);
                    
                    DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->where('item_code', $item_code)->update($cbi_array);
                    DB::table('tabBin')->where('warehouse', $beginning_inventory->branch_warehouse)->where('item_code', $item_code)->update($bin_array);
                }
            }

            DB::table('tabActivity Log')->insert([
                'name' => uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'content' => 'Consignment Activity Log',
                'subject' => 'Stock Adjustment for '.$beginning_inventory->branch_warehouse.' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Adjustment',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activity_logs_data, true)
            ]);

            $grand_total = DB::table('tabConsignment Beginning Inventory Item')->where('parent', $id)->sum('amount');

            DB::table('tabConsignment Beginning Inventory')->where('name', $id)->update([
                'modified' => $now,
                'modified_by' => Auth::user()->wh_user,
                'grand_total' => $grand_total,
                'remarks' => $request->remarks
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse Stocks Adjusted.');
        } catch (\Throwable $e) {
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

        $inventory_audit_per_warehouse = DB::table('tabConsignment Inventory Audit Report as cia')->join('tabConsignment Inventory Audit Report Item as ciar', 'cia.name', 'ciar.parent')
            ->whereIn('branch_warehouse', array_keys($stores_with_beginning_inventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = Carbon::now()->endOfDay();

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();
    
        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

        $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();

        if ($first_cutoff->gt($end)) {
            $end = $first_cutoff;
        }

        $cutoff_date = $this->getCutoffDate(Carbon::now()->endOfDay());
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
            }
   
            $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . Carbon::now()->format('F d, Y');
            $check = Carbon::parse($start)->between($period_from, $period_to);
            if (Carbon::parse($start)->addDay()->startOfDay()->lt(Carbon::now()->startOfDay())) {
                if ($last_audit_date->endOfDay()->lt($end) && $beginning_inventory_transaction_date) {
                    if (!$check) {
                        $pending[] = [
                            'store' => $store,
                            'beginning_inventory_date' => $beginning_inventory_transaction_date,
                            'last_inventory_audit_date' => $last_inventory_audit_date,
                            'duration' => $duration,
                            'is_late' => $is_late,
                            'promodisers' => $promodisers,
                        ];
                    }
                }
            }

             if(!$beginning_inventory_transaction_date) {
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

        return view('consignment.supervisor.tbl_pending_submission_inventory_audit', compact('pending'));
    }

    public function viewSalesReport() {
        $select_year = [];
        for ($i = 2022; $i <= date('Y') ; $i++) { 
            $select_year[] = $i;
        }

        return view('consignment.supervisor.view_product_sold_list', compact('select_year'));
    }

    // /get_activity_logs
    public function activityLogs(Request $request) {
        $dates = $request->date ? explode(' to ', $request->date) : [];
        
        $logs = ActivityLog::where('content', 'Consignment Activity Log')
            ->when($request->warehouse, function($q) use ($request){
                return $q->where('subject', 'like', "%$request->warehouse%");
            })
            ->when($dates, function ($q) use ($dates){
                return $q->whereBetween('creation', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            })
            ->when($request->user, function ($q) use ($request){
                return $q->where('full_name', $request->user);
            })
            ->select('creation', 'subject', 'reference_name', 'full_name')
            ->orderBy('creation', 'desc')->paginate(20);

        return view('consignment.supervisor.tbl_activity_logs', compact('logs'));
    }

    // /view_promodisers
    public function viewPromodisersList() {
        if (!in_array(Auth::user()->user_group, ['Director', 'Consignment Supervisor'])) {
            return redirect('/');
        }

        $user_details = ERPUser::where('enabled', 1)
            ->whereHas('wh_user', function ($user){
                $user->where('user_group', 'Promodiser');
            })
            ->with('social', function ($user){
                $user->select('parent', 'userid');
            })
            ->with('wh_user', function ($user){
                $user->select('wh_user', 'name', 'frappe_userid', 'full_name', 'frappe_userid', 'enabled')
                    ->with('assigned_warehouses', function ($warehouse){
                        $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                    });
            })
            ->select('name', 'full_name')->get();

        $total_promodisers = count($user_details);

        $result = collect($user_details)->map(function ($user){
            if (Cache::has('user-is-online-' . $user->name)) {
                $login_status = '<span class="text-success font-weight-bold">ONLINE NOW</span>';
            } else {
                $login_status = Carbon::parse($user->last_login)->format('F d, Y h:i A');
            }

            return [
                'id' => $user->name,
                'promodiser_name' => $user->full_name,
                'stores' => collect($user->wh_user->assigned_warehouses)->pluck('warehouse'),
                'login_status' => $user->last_login ? $login_status : null,
                'enabled' => $user->wh_user->enabled
            ];
        });

        $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory')
            ->where('status', 'Approved')->select('branch_warehouse', DB::raw('MIN(transaction_date) as transaction_date'))->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')->toArray();

        return view('consignment.supervisor.view_promodisers_list', compact('result', 'total_promodisers', 'stores_with_beginning_inventory'));
    }

    public function addPromodiserForm(){
        $consignment_stores = DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->where('is_group', 0)->where('disabled', 0)->orderBy('warehouse_name', 'asc')->pluck('name');

        $not_included = DB::table('tabWarehouse Users')->whereIn('user_group', ['Promodiser', 'Consignment Supervisor', 'Director'])->pluck('wh_user');
        $not_included = collect($not_included)
            ->push('Administrator')
            ->push('Guest')
            ->all();

        $users = DB::table('tabUser as u')
            ->join('tabUser Social Login as s', 'u.name', 's.parent')
            ->whereNotIn('u.name', $not_included)->where('enabled', 1)
            ->select('u.name', 'u.full_name')
            ->get();

        return view('consignment.supervisor.add_promodiser', compact('consignment_stores', 'users'));
    }

    public function addPromodiser(Request $request){
        try {
            $user = $request->user;
            $warehouses = $request->warehouses;

            $user_details = ERPUser::where('name', $user)->where('enabled', 1)
                ->with('social', function ($user){
                    $user->select('parent', 'userid');
                })
                ->with('wh_user', function ($user){
                    $user->select('wh_user', 'name', 'frappe_userid', 'user_group', 'modified', 'modified_by', 'price_list')
                        ->with('assigned_warehouses', function ($warehouse){
                            $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                        });
                })
                ->select('name', 'full_name')->first();

            if(!$user_details){
                return redirect()->back()->with('error', 'User not found.');
            }

            $frappe_userid = $user_details->social->userid;
            $wh_user = $user_details->wh_user;

            $data = [
                'user_group' => 'Promodiser',
                'price_list' => 'Consignment Price',
                'wh_user' => $user_details->name,
                'full_name' => $user_details->full_name,
                'frappe_userid' => $frappe_userid
            ];

            $method = 'post';
            $reference = null;
            if($wh_user){
                $method = 'put';
                $reference = $wh_user->name;
                $frappe_userid = $wh_user->name;

                unset($data['frappe_userid']);
                DB::table('tabAssigned Consignment Warehouse')->where('parent', $frappe_userid)->delete();
            }

            $warehouse_details = Warehouse::whereIn('name', $warehouses)->select('name as warehouse', 'warehouse_name')->get();

            $data['consignment_store'] = collect($warehouse_details)->toArray();
            $data['warehouse'] = collect($warehouse_details)->toArray();

            $response = $this->erpOperation($method, 'Warehouse Users', $reference, $data);

            if(!isset($response['data'])){
                throw new Exception($response['exception']);
            }
            $response = $this->erpOperation('put', 'Warehouse Users', $response['data']['name'], ['frappe_userid' => $response['data']['name']]);

            return redirect('/view_promodisers')->with('success', 'Promodiser Added.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function editPromodiserForm($id){
        $user_details = ERPUser::where('name', $id)->where('enabled', 1)
            ->with('wh_user', function ($user){
                $user->select('wh_user', 'name', 'frappe_userid', 'enabled')
                    ->with('assigned_warehouses', function ($warehouse){
                        $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                    });
            })
            ->select('name', 'full_name')->first();

        if(!$user_details){
            return redirect()->back()->with('error', 'User not found');
        }

        $assigned_warehouses = collect($user_details->wh_user->assigned_warehouses)->pluck('warehouse');

        $consignment_stores = DB::table('tabWarehouse')->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->where('is_group', 0)->where('disabled', 0)->orderBy('warehouse_name', 'asc')->pluck('name');

        return view('consignment.supervisor.edit_promodiser', compact('assigned_warehouses', 'user_details', 'consignment_stores', 'id'));
    }

    public function editPromodiser($id, Request $request){
        try {
            $user_details = ERPUser::where('name', $id)->where('enabled', 1)
                ->with('wh_user', function ($user){
                    $user->select('wh_user', 'name', 'frappe_userid', 'user_group', 'modified', 'modified_by', 'price_list')
                        ->with('assigned_warehouses', function ($warehouse){
                            $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                        });
                })
                ->select('name', 'full_name')->first();
                
            if(!$user_details){
                throw new Exception('User not found');
            }

            $frappe_userid = $user_details->wh_user->name;
            $assigned_warehouses = $user_details->wh_user->assigned_warehouses;

            $warehouses_entry = $request->warehouses;

            $a = array_diff($assigned_warehouses->toArray(), $warehouses_entry);
            $b = array_diff($warehouses_entry, $assigned_warehouses->toArray());
            $warehouses = [];
            if(count($a) > 0 || count($b) > 0){ // if changes are made to the assigned warehouses
                DB::table('tabAssigned Consignment Warehouse')->where('parent', $frappe_userid)->delete();

                $warehouses = Warehouse::whereIn('name', $warehouses_entry)->select('name as warehouse', 'warehouse_name')->get();
            }

            $data = ['enabled' => isset($request->enabled) ? 1 : 0];

            if($warehouses){
                $data['consignment_store'] = $warehouses;
            }

            $response = $this->erpOperation('put', 'Warehouse Users', $frappe_userid, $data);

            if(!isset($response['data'])){
                throw new Exception(isset($response['exception']) ? $response['exception'] : 'An error occured while updating user.');
            }

            if($request->ajax()){
                return ['success' => 1, 'message' => 'Promodiser details updated.'];
            }
            return redirect('/view_promodisers')->with('success', 'Promodiser details updated.');
        } catch (\Throwable $e) {
            if($request->ajax()){
                return ['success' => 0, 'message' => 'An error occured. Please contact your system administrator.', 500];
            }
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
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


    // /view_consignment_deliveries
    public function viewDeliveries(Request $request) {
        if($request->ajax()){
            $status = $request->status;

            $list = StockEntry::with('mreq')
                ->with('items', function($items) {
                    $items->with('defaultImage')->select('name', 'parent', 't_warehouse', 'item_code', 'description', 'qty', 'transfer_qty', 'basic_rate', 'basic_amount');
                })
                ->whereDate('delivery_date', '>=', '2022-06-25')->whereIn('transfer_as', ['Consignment', 'Store Transfer'])->where('purpose', 'Material Transfer')->where('docstatus', 1)
                ->when($status == 'Received', function ($q){
                    return $q->where('consignment_status', 'Received');
                })
                ->when($status == 'To Receive', function ($q){
                    return $q->where(function ($query){
                        $query->whereNull('consignment_status')->orWhere('consignment_status', 'To Receive');
                    });
                })
                ->when($request->store, function ($q) use ($request){
                    return $q->where('to_warehouse', $request->store);
                })
                ->orderByRaw("FIELD(consignment_status, '', 'To Receive', 'Received') ASC")
                ->orderByDesc('creation')->paginate(20);

            $item_codes = collect($list->items())->flatMap(function($items) {
                return $items->items->pluck('item_code');
            })->unique()->values();

            $target_warehouses = collect($list->items())->flatMap(function($items) {
                return $items->items->pluck('t_warehouse');
            })->unique()->values();

            $item_details = Bin::with('item')
                ->whereIn('item_code', $item_codes)->whereIn('warehouse', $target_warehouses)
                ->select('name', 'item_code', 'warehouse', 'consignment_price')
                ->get()->groupBy(['warehouse', 'item_code']);

            $result = collect($list->items())->map(function ($stock_entry) use ($item_details){
                $stock_entry->items = collect($stock_entry->items)->map(function ($item) use ($item_details){
                    $item->image = $item->defaultImage ? '/img/'.$item->defaultImage->image_path : 'icon/no_img.png';
                    if(Storage::disk('public')->exists('/img/'.explode('.', $item->image)[0].'.webp')){
                        $item->image = explode('.', $item->image)[0].'.webp';
                    }
                    
                    $item->price = isset($item_details[$item->t_warehouse][$item->item_code]) ? (float) $item_details[$item->t_warehouse][$item->item_code][0]->consignment_price : 0;

                    $item->amount = $item->price * $item->qty;

                    return $item;
                });

                $stock_entry->created_by = isset($stock_entry->mreq->owner) ? ucwords(str_replace('.', ' ', explode('@', $stock_entry->mreq->owner)[0])) : null;

                return $stock_entry;
            });

            return view('consignment.supervisor.view_pending_to_receive', compact('list', 'result'));
        }

        return view('consignment.supervisor.view_deliveries');
    }

    public function getErpItems(Request $request) {
        $search_str = explode(' ', $request->q);

        return DB::table('tabItem')
            ->where('disabled', 0)->where('has_variants', 0)->where('is_stock_item', 1)
            ->when($request->q, function ($query) use ($request, $search_str){
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('description', 'LIKE', "%".$str."%");
                    }

                    $q->orWhere('item_code', 'LIKE', "%".$request->q."%");
                });
            })
            ->select('item_code as id', DB::raw('CONCAT(item_code, "-", description) as text '))->orderBy('item_code', 'asc')
            ->limit(8)->get();
    }

    public function consignmentLedger(Request $request) {
        if ($request->ajax()) {
            $branch_warehouse = $request->branch_warehouse;
            $item_code = $request->item_code;

            $result = $item_descriptions = [];
            if ($branch_warehouse) {
                $item_opening_stock = DB::table('tabConsignment Beginning Inventory as cb')
                    ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                    ->where('cb.status', 'Approved')->where('branch_warehouse', $branch_warehouse)
                    ->when($item_code, function ($q) use ($item_code){ 
                        return $q->where('cbi.item_code', $item_code);
                    })
                    ->select('cbi.item_code', 'cbi.opening_stock', 'cb.transaction_date', 'cb.branch_warehouse', 'cb.name', 'cb.owner', 'cbi.item_description')
                    ->orderBy('cb.transaction_date', 'asc')->get();
            
                foreach ($item_opening_stock as $r) {
                    $result[$r->item_code][$r->transaction_date][] = [
                        'qty' => number_format($r->opening_stock),
                        'type' => 'Beginning Inventory',
                        'transaction_date' => $r->transaction_date,
                        'reference' => $r->name,
                        'owner' => $r->owner
                    ];

                    $item_descriptions[$r->item_code] = $r->item_description;
                }
        
                $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')->where('branch_warehouse', $branch_warehouse)
                    ->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();
            
                $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');
        
                $item_receive = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                        return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                    })
                    ->when($item_code, function ($q) use ($item_code){ 
                        return $q->where('sted.item_code', $item_code);
                    })
                    ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.consignment_status', 'Received')
                    ->where('sted.t_warehouse', $branch_warehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.consignment_date_received', 'sted.item_code', 'sted.transfer_qty', 'sted.consignment_received_by', 'sted.description')
                    ->orderBy('sted.consignment_date_received', 'desc')->get();
                
                foreach ($item_receive as $a) {
                    $date_received = Carbon::parse($a->consignment_date_received)->format('Y-m-d');
                    $result[$a->item_code][$date_received][] = [
                        'qty' =>  number_format($a->transfer_qty),
                        'type' => 'Stocks Received',
                        'transaction_date' => $date_received,
                        'reference' => $a->name,
                        'owner' => $a->consignment_received_by
                    ];

                    $item_descriptions[$a->item_code] = $a->description;
                }
        
                $item_transferred = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                        return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                    })
                    ->when($item_code, function ($q) use ($item_code){ 
                        return $q->where('sted.item_code', $item_code);
                    })
                    ->whereIn('ste.transfer_as', ['Store Transfer'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.s_warehouse', $branch_warehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
                    ->orderBy('sted.creation', 'desc')->get();
        
                foreach ($item_transferred as $v) {
                    $date_transferred = Carbon::parse($v->creation)->format('Y-m-d');
                    $result[$v->item_code][$date_transferred][] = [
                        'qty' =>  number_format($v->transfer_qty),
                        'type' => 'Store Transfer',
                        'transaction_date' => $date_transferred,
                        'reference' => $v->name,
                        'owner' => $v->owner
                    ];

                    $item_descriptions[$v->item_code] = $v->description;
                }
        
                $item_returned = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                        return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                    })
                    ->when($item_code, function ($q) use ($item_code){ 
                        return $q->where('sted.item_code', $item_code);
                    })
                    ->whereIn('ste.transfer_as', ['For Return'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.s_warehouse', $branch_warehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
                    ->orderBy('sted.creation', 'desc')->get();
        
                foreach ($item_returned as $a) {
                    $date_returned = Carbon::parse($a->creation)->format('Y-m-d');
                    $result[$a->item_code][$date_returned][] = [
                        'qty' =>  number_format($a->transfer_qty),
                        'type' => 'Stocks Returned',
                        'transaction_date' => $date_returned,
                        'reference' => $a->name,
                        'owner' => $a->owner
                    ];

                    $item_descriptions[$a->item_code] = $a->description;
                }
            }
    
            return view('consignment.tbl_consignment_ledger', compact('result', 'branch_warehouse', 'item_descriptions'));
        }

        return view('consignment.consignment_ledger');
    }

    public function consignmentStockMovement($item_code, Request $request) {
        $branch_warehouse = $request->branch_warehouse;

        $dates = $request->date_range ? explode(' to ', $request->date_range) : [];
        $user = $request->user != 'Select All' ? $request->user : null;

        $result = [];
        if ($item_code) {
            $item_opening_stock = DB::table('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->where('cb.status', 'Approved')->where('cbi.item_code', $item_code)
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){ 
                    return $q->where('branch_warehouse', $branch_warehouse);
                })
                ->when($request->date_range, function ($q) use ($dates){
                    return $q->whereDate('cb.transaction_date', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('cb.transaction_date', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($q) use ($user){
                    return $q->where('cb.owner', $user);
                })
                ->select('cbi.item_code', 'cbi.opening_stock', 'cb.transaction_date', 'cb.branch_warehouse', 'cb.name', 'cb.owner', 'cb.creation')
                ->orderBy('cb.transaction_date', 'asc')->get();
        
            foreach ($item_opening_stock as $r) {
                $result[] = [
                    'qty' => number_format($r->opening_stock),
                    'type' => 'Beginning Inventory',
                    'transaction_date' => $r->transaction_date,
                    'branch_warehouse' => $r->branch_warehouse,
                    'reference' => $r->name,
                    'owner' => $r->owner,
                    'creation' => $r->creation
                ];
            }
    
            $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){ 
                    return $q->where('branch_warehouse', $branch_warehouse);
                })
                ->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();
        
            $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');
    
            $item_receive = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                    return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                })
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){ 
                    return $q->where('sted.t_warehouse', $branch_warehouse);
                })
                ->when($request->date_range, function ($q) use ($dates){
                    return $q->whereDate('sted.consignment_date_received', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.consignment_date_received', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($q) use ($user){
                    return $q->where(function ($query) use ($user){
                        return $query->where('sted.consignment_received_by', $user)->orWhere('ste.consignment_received_by', $user)->orWhere('sted.modified_by', $user);
                    });
                })
                ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.consignment_status', 'Received')
                ->where('sted.item_code', $item_code)
                ->select('ste.name', 'sted.t_warehouse', 'sted.consignment_date_received', 'sted.item_code', 'sted.transfer_qty', 'ste.consignment_received_by as parent_received_by', 'sted.consignment_received_by as child_received_by', 'sted.modified_by', 'ste.creation')
                ->orderBy('sted.consignment_date_received', 'desc')->get();
            
            foreach ($item_receive as $a) {
                $date_received = Carbon::parse($a->consignment_date_received)->format('Y-m-d');

                $owner = $a->child_received_by;
                if(!$owner){
                    $owner = $a->parent_received_by ? $a->parent_received_by : $a->modified_by;
                }

                $result[] = [
                    'qty' =>  number_format($a->transfer_qty),
                    'type' => 'Stocks Received',
                    'transaction_date' => $date_received,
                    'branch_warehouse' => $a->t_warehouse,
                    'reference' => $a->name,
                    'owner' => $owner,
                    'creation' => $a->creation
                ];
            }
    
            $item_transferred = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                    return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                })
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){ 
                    return $q->where('sted.s_warehouse', $branch_warehouse);
                })
                ->when($request->date_range, function ($q) use ($dates){
                    return $q->whereDate('sted.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($q) use ($user){
                    return $q->where('ste.owner', $user);
                })
                ->whereIn('ste.transfer_as', ['Store Transfer'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.item_code', $item_code)
                ->select('ste.name', 'sted.s_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner')
                ->orderBy('sted.creation', 'desc')->get();
    
            foreach ($item_transferred as $v) {
                $date_transferred = Carbon::parse($v->creation)->format('Y-m-d');
                $result[] = [
                    'qty' =>  '-'.number_format($v->transfer_qty),
                    'type' => 'Store Transfer',
                    'transaction_date' => $date_transferred,
                    'branch_warehouse' => $v->s_warehouse,
                    'reference' => $v->name,
                    'owner' => $v->owner,
                    'creation' => $v->creation
                ];
            }
    
            $item_returned = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginning_inventory_start_date, function ($q) use ($beginning_inventory_start_date){ 
                    return $q->whereDate('ste.delivery_date', '>=', $beginning_inventory_start_date);
                })
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){ 
                    return $q->where('sted.s_warehouse', $branch_warehouse);
                })
                ->when($request->date_range, function ($q) use ($dates){
                    return $q->whereDate('sted.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($q) use ($user){
                    return $q->where('ste.owner', $user);
                })
                ->whereIn('ste.transfer_as', ['For Return'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.item_code', $item_code)
                ->select('ste.name', 'sted.s_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner')
                ->orderBy('sted.creation', 'desc')->get();

            foreach ($item_returned as $a) {
                $date_returned = Carbon::parse($a->creation)->format('Y-m-d');
                $result[] = [
                    'qty' =>  '-'.number_format($a->transfer_qty),
                    'type' => 'Stocks Returned',
                    'transaction_date' => $date_returned,
                    'branch_warehouse' => $a->s_warehouse,
                    'reference' => $a->name,
                    'owner' => $a->owner,
                    'creation' => $a->creation
                ];
            }

            $stock_adjustments = DB::table('tabConsignment Stock Adjustment as csa')
                ->join('tabConsignment Stock Adjustment Items as csai', 'csa.name', 'csai.parent')
                ->where('csai.item_code', $item_code)
                ->whereRaw('csai.previous_qty != csai.new_qty')
                ->when($branch_warehouse, function ($q) use ($branch_warehouse){
                    return $q->where('csa.warehouse', $branch_warehouse);
                })
                ->when($request->date_range, function ($q) use ($dates){
                    return $q->whereDate('csa.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('csa.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($q) use ($user){
                    return $q->where('csa.owner', $user);
                })
                ->select('csa.name', 'csai.new_qty', 'csa.transaction_date', 'csa.warehouse', 'csa.owner', 'csa.creation')
                ->orderBy('csa.creation', 'desc')->get();

            foreach ($stock_adjustments as $sa) {
                $result[] = [
                    'qty' => number_format($sa->new_qty),
                    'type' => 'Stock Adjustment',
                    'transaction_date' => Carbon::parse($sa->transaction_date)->format('Y-m-d'),
                    'branch_warehouse' => $sa->warehouse,
                    'reference' => $sa->name,
                    'owner' => $sa->owner,
                    'creation' => $sa->creation
                ];
            }
        }

        if($request->get_users == 1){
            $all[] = [
                'id' => 'Select All',
                'text' => 'Select All'
            ];

            $users = collect($result)->map(function ($q){
                if($q['owner']){
                    return [
                        'id' => $q['owner'],
                        'text' => $q['owner']
                    ];
                }
            })->filter()->unique();

            $users = collect($all)->merge($users);

            return response()->json($users);
        }

        $result = collect($result)->sortBy('creation')->reverse()->toArray();

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($result);
        // Define how many items we want to be visible in each page
        $perPage = 20;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $result = $paginatedItems;

        return view('tbl_consignment_stock_movement', compact('result'));
    }

    private function generateGlEntries($stock_entry){
        try {
            $now = Carbon::now();
            $stock_entry_qry = DB::table('tabStock Entry')->where('name', $stock_entry)->first();
            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry)
                ->select('s_warehouse', 't_warehouse', DB::raw('SUM((basic_rate * qty)) as basic_amount'), 'parent', 'cost_center', 'expense_account')
                ->groupBy('s_warehouse', 't_warehouse', 'parent', 'cost_center', 'expense_account')->get();
    
            $basic_amount = 0;
            foreach ($stock_entry_detail as $row) {
                $basic_amount += ($row->t_warehouse) ? $row->basic_amount : 0;
            }
    
            $gl_entry = [];
            foreach ($stock_entry_detail as $row) {    
                if($row->s_warehouse){
                    $credit = $basic_amount;
                    $debit = 0;
                    $account = $row->expense_account;
                    $expense_account = $row->s_warehouse;
                }else{
                    $credit = 0;
                    $debit = $basic_amount;
                    $account = $row->t_warehouse;
                    $expense_account = $row->expense_account;
                }
    
                $gl_entry[] = [
                    'name' => 'ath' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'idx' => 0,
                    'fiscal_year' => $now->format('Y'),
                    'voucher_no' => $row->parent,
                    'cost_center' => $row->cost_center,
                    'credit' => $credit,
                    'party_type' => null,
                    'transaction_date' => null,
                    'debit' => $debit,
                    'party' => null,
                    '_liked_by' => null,
                    'company' => 'FUMACO Inc.',
                    '_assign' => null,
                    'voucher_type' => 'Stock Entry',
                    '_comments' => null,
                    'is_advance' => 'No',
                    'remarks' => 'Accounting Entry for Stock',
                    'account_currency' => 'PHP',
                    'debit_in_account_currency' => $debit,
                    '_user_tags' => null,
                    'account' => $account,
                    'against_voucher_type' => null,
                    'against' => $expense_account,
                    'project' => $stock_entry_qry->project,
                    'against_voucher' => null,
                    'is_opening' => 'No',
                    'posting_date' => $stock_entry_qry->posting_date,
                    'credit_in_account_currency' => $credit,
                    'total_allocated_amount' => 0,
                    'reference_no' => null,
                    'mode_of_payment' => null,
                    'order_type' => null,
                    'po_no' => null,
                    'reference_date' => null,
                    'cr_ref_no' => null,
                    'or_ref_no' => null,
                    'dr_ref_no' => null,
                    'pr_ref_no' => null,
                ];
            }
            
            DB::table('tabGL Entry')->insert($gl_entry);

            return ['success' => true, 'message' => 'GL Entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
	}

    private function generateLedgerEntries($stock_entry) {
        try {
            $now = Carbon::now();
            $stock_entry_qry = DB::table('tabStock Entry')->where('name', $stock_entry)->first();

            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry)->get();

            if (in_array($stock_entry_qry->purpose, ['Material Transfer'])) {                
                $s_data = $t_data = [];
                foreach ($stock_entry_detail as $row) {
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->s_warehouse)
                        ->where('item_code', $row->item_code)->first();

                    $actual_qty = $valuation_rate = 0;
                    if ($bin_qry) {
                        $actual_qty = $bin_qry->actual_qty;
                        $valuation_rate = $bin_qry->valuation_rate;
                    }
                        
                    $s_data[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty * -1,
                        'stock_value' => $actual_qty * $valuation_rate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => $row->name,
                        'incoming_rate' => 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->s_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuation_rate,
                        'project' => $stock_entry_qry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actual_qty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => ($row->qty * $row->valuation_rate) * -1,
                        'posting_date' => $now->format('Y-m-d'),
                    ];
                    
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();
                    
                    $actual_qty = $valuation_rate = 0;
                    if ($bin_qry) {
                        $actual_qty = $bin_qry->actual_qty;
                        $valuation_rate = $bin_qry->valuation_rate;
                    }

                    $t_data[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty,
                        'stock_value' => $actual_qty * $valuation_rate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => null,
                        'incoming_rate' => $row->basic_rate,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->t_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuation_rate,
                        'project' => $stock_entry_qry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actual_qty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => $row->qty * $row->valuation_rate,
                        'posting_date' => $now->format('Y-m-d'),
					    'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $stock_ledger_entry = array_merge($s_data, $t_data);

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stock_ledger_entry);
                }
            } else {
                $t_data = [];
                foreach ($stock_entry_detail as $row) {
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();

                    $actual_qty = $valuation_rate = 0;
                    if ($bin_qry) {
                        $actual_qty = $bin_qry->actual_qty;
                        $valuation_rate = $bin_qry->valuation_rate;
                    }

                    $t_data[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty,
                        'stock_value' => $actual_qty * $valuation_rate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => null,
                        'incoming_rate' => $row->basic_rate,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->t_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuation_rate,
                        'project' => $stock_entry_qry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actual_qty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => $row->qty * $row->valuation_rate,
                        'posting_date' => $now->format('Y-m-d'),
					    'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($t_data);
                }
            }

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateCancelledLedgerEntries($stock_entry) {
        try {
            $now = Carbon::now();
            $sle = DB::table('tabStock Ledger Entry')->where('voucher_no', $stock_entry)->get();

            DB::table('tabStock Ledger Entry')->where('voucher_no', $stock_entry)->update(['is_cancelled' => 1]);

            $data = [];
            foreach ($sle as $r) {
                $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $r->warehouse)
                    ->where('item_code', $r->item_code)->first();

                $actual_qty = $valuation_rate = 0;
                if ($bin_qry) {
                    $actual_qty = $bin_qry->actual_qty;
                    $valuation_rate = $bin_qry->valuation_rate;
                }

                $data[] = [
                    'name' => 'cn' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => $r->docstatus,
                    'parent' => $r->parent,
                    'parentfield' => $r->parentfield,
                    'parenttype' => $r->parenttype,
                    'idx' => $r->idx,
                    'serial_no' => $r->serial_no,
                    'fiscal_year' => $r->fiscal_year,
                    'voucher_type' => $r->voucher_type,
                    'posting_time' => $r->posting_time,
                    'actual_qty' => $r->actual_qty * -1,
                    'stock_value' => $actual_qty * $valuation_rate,
                    '_comments' => null,
                    'dependant_sle_voucher_detail_no' => $r->dependant_sle_voucher_detail_no,
                    'incoming_rate' => $r->incoming_rate,
                    'voucher_detail_no' => $r->voucher_detail_no,
                    'stock_uom' => $r->stock_uom,
                    'warehouse' => $r->warehouse,
                    '_liked_by' => null,
                    'company' => $r->company,
                    '_assign' => null,
                    'item_code' => $r->item_code,
                    'valuation_rate' => $valuation_rate,
                    'project' => $r->project,
                    'voucher_no' => $r->voucher_no,
                    'outgoing_rate' => $r->outgoing_rate,
                    'is_cancelled' => 1,
                    'qty_after_transaction' => $actual_qty,
                    '_user_tags' => null,
                    'batch_no' => $r->batch_no,
                    'stock_value_difference' => ($r->actual_qty * $r->valuation_rate) * -1,
                    'posting_date' => $r->posting_date,
					'posting_datetime' => $now->format('Y-m-d H:i:s')
                ];
            }

            DB::connection('mysql')->table('tabStock Ledger Entry')->insert($data);

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateCancelledGlEntries($stock_entry){
        try {
            $now = Carbon::now();
            $sle = DB::table('tabGL Entry')->where('voucher_no', $stock_entry)->get();

            DB::table('tabGL Entry')->where('voucher_no', $stock_entry)->update(['is_cancelled' => 1]);

            $data = [];
            foreach ($sle as $r) {
                $data[] = [
                    'name' => 'ge' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => $r->docstatus,
                    'parent' => $r->parent,
                    'parentfield' => $r->parentfield,
                    'parenttype' => $r->parenttype,
                    'idx' => $r->idx,
                    'fiscal_year' => $r->fiscal_year,
                    'voucher_no' => $r->voucher_no,
                    'cost_center' => $r->cost_center,
                    'credit' => $r->debit,
                    'party_type' => $r->party_type,
                    'transaction_date' => $r->transaction_date,
                    'debit' => $r->credit,
                    'party' => $r->party,
                    '_liked_by' => null,
                    'company' => $r->company,
                    '_assign' => null,
                    'voucher_type' => $r->voucher_type,
                    '_comments' => null,
                    'is_advance' => $r->is_advance,
                    'remarks' => 'On cancellation of ' . $r->voucher_no,
                    'account_currency' => $r->account_currency,
                    'debit_in_account_currency' => $r->credit_in_account_currency,
                    '_user_tags' => null,
                    'account' => $r->account,
                    'against_voucher_type' => $r->against_voucher_type,
                    'against' => $r->against,
                    'project' => $r->project,
                    'against_voucher' => $r->against_voucher,
                    'is_opening' => $r->is_opening,
                    'posting_date' => $r->posting_date,
                    'credit_in_account_currency' => $r->debit_in_account_currency,
                    'total_allocated_amount' => $r->total_allocated_amount,
                    'is_cancelled' => 1,
                    'reference_no' => null,
                    'mode_of_payment' => null,
                    'order_type' => null,
                    'po_no' => null,
                    'reference_date' => null,
                    'cr_ref_no' => null,
                    'or_ref_no' => null,
                    'dr_ref_no' => null,
                    'pr_ref_no' => null,
                ];
            }

            DB::table('tabGL Entry')->insert($data);

            return ['success' => true, 'message' => 'GL Entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
	}

    public function import_tool(){
        return view('consignment.supervisor.Import_tool.index');
    }

    public function select_values(Request $request){
        $customer = DB::table('tabCustomer')
            ->when($request->q, function ($q) use ($request){
                return $q->where('name', 'like', '%'.$request->q.'%');
            })->select('name as id', 'name as text')->limit(15)->orderBy('name')->get();
        $project = DB::table('tabProject')
            ->when($request->q, function ($q) use ($request){
                return $q->where('name', 'like', '%'.$request->q.'%');
            })->select('name as id', 'name as text')->limit(15)->orderBy('name')->get();

        return response()->json([
            'customer' => $customer,
            'project' => $project
        ]);
    }

    public function readFile(Request $request){
        try {
            $customer = $request->customer;
            $project = $request->project;
            $branch = $request->branch;
            $customer_purchase_order = $request->cpo;
            $path = request()->file('selected_file')->storeAs('tmp', request()->file('selected_file')->getClientOriginalName().uniqid(), 'local');

            $path = storage_path(). '/app/'.$path;
            $reader = new ReaderXlsx();
            $spreadsheet = $reader->load($path);
            
    
            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column numbers referenced in the worksheet
            $highestRow = $sheet->getHighestRow(); // e.g. 10
            $highestColumn = 'D'; // e.g 'F'

            $sheet_arr = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $sheet_arr['barcode'][] = trim($sheet->getCell('A' . $row)->getValue());
                $sheet_arr['description'][] = trim($sheet->getCell('B' . $row)->getValue());
                $sheet_arr['sold'][] = (float)$sheet->getCell('C' . $row)->getValue();
                $sheet_arr['amount'][] = (float)$sheet->getCell('D' . $row)->getValue();
            }

            $item_details = DB::table('tabItem as i')
                ->join('tabConsignment Item Barcode as b', 'b.parent', 'i.name')
                ->where('b.customer', $customer)
                ->select('b.barcode', 'b.customer', 'i.name', 'i.item_name', 'i.description', 'i.stock_uom')
                ->get();
                
            $item_details = collect($item_details)->groupBy('barcode');

            $items = [];
            foreach($sheet_arr['barcode'] as $i => $barcode){
                if(!$i){
                    continue;
                }

                $active = 0;
                $item_code = $erp_description = $uom = null;
                $default_description = $barcode;
                $explode_barcode_column = explode(" ", $barcode);
                foreach ($explode_barcode_column as $code) {
                    if(isset($item_details[$code])){
                        $barcode = trim($code);
                        $item_code = $item_details[$barcode][0]->name;
                        $erp_description = $item_details[$barcode][0]->description;
                        $uom = $item_details[$barcode][0]->stock_uom;
                        $active = 1;
                        break;
                    }
                }

                $description = isset($sheet_arr['description'][$i]) && $sheet_arr['description'][$i] != '' ? $sheet_arr['description'][$i] : ($active ? $default_description : null);

                if(!$description){
                    continue;
                }

                $sold = isset($sheet_arr['sold'][$i]) ? $sheet_arr['sold'][$i] : 0;
                $amount = isset($sheet_arr['amount'][$i]) ? $sheet_arr['amount'][$i] : 0;
                $items[$barcode] = [
                    'barcode' => $barcode,
                    'active' => $active,
                    'item_code' => $item_code,
                    'erp_description' => $erp_description,
                    'description' => $description,
                    'sold' => isset($items[$barcode]['sold']) ? $items[$barcode]['sold'] += $sold : $sold,
                    'amount' => isset($items[$barcode]['amount']) ? $items[$barcode]['amount'] += $amount : $amount,
                    'uom' => $uom,
                ];
            }

            return view('consignment.supervisor.Import_tool.tbl', compact('items', 'customer', 'project', 'branch', 'customer_purchase_order'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createSalesOrder(Request $request) {
        try {
            $sales_order_item_data = [];

            $request_items = $request->items;
            $request_customer = $request->customer;
            $request_branch_warehouse = $request->branch_warehouse;

            $current_timestamp = Carbon::now();
            $current_timestamp_string = $current_timestamp->toDateTimeString();
            $current_user = Auth::user()->full_name;

            // get addresses name in dynamic link based on customer
            $addresses_name = DB::connection('mysql')->table('tabDynamic Link as dl')->join('tabAddress as a', 'dl.parent', 'a.name')->where('dl.link_doctype', 'Customer')
                ->where('dl.link_name', $request_customer)->where('a.address_type', 'Shipping')->where('a.disabled', 0)->orderBy('dl.parent', 'asc')->pluck('a.name');

            $shipping_address_name = null;
            $current_intersect_count = 0;
            $request_branch_warehouse_arr = explode(" ", $request_branch_warehouse);
            foreach ($addresses_name as $address) {
                $address_arr = array_map('trim', explode('-', str_replace(' ', '-', $address)));
                $intersect_count = count(array_intersect($address_arr, $request_branch_warehouse_arr));
                if ($intersect_count > $current_intersect_count) {
                    $current_intersect_count = $intersect_count;
                    $shipping_address_name = $address;
                }
            }

            $items_classification = DB::connection('mysql')->table('tabItem')
                ->whereIn('name', array_filter(array_column($request_items, 'item_code')))
                ->pluck('item_classification', 'name')->toArray();
            
            foreach ($request_items as $i => $item) {
                $row = $i + 1;
                $item_code = $item['item_code'];
                if (!$item_code) {
                    return response()->json(['status' => 0, 'message' => 'Unable to find item code for Row #' . $row]);
                }

                $item_classification = array_key_exists($item_code, $items_classification) ? $items_classification[$item_code] : null;

                $sales_order_item_data[] = [
                    "item_code" => $item_code,
                    "delivery_date" => $current_timestamp_string,
                    "qty" => $item['qty'],
                    "rate" => $item['rate'],
                    "warehouse" => $request->branch_warehouse,
                    "item_classification" => $item_classification,
                ];
            }

            $sales_taxes[] = [
                'charge_type' => 'On Net Total',
                'account_head' => 'Output tax - FI',
                'description' => 'Output tax',
                'rate' => 12
            ];

            $sales_order_data = [
                "customer" => $request->customer,
                "order_type" => "Sales",
                "company" => "FUMACO Inc.",
                "delivery_date" => $current_timestamp_string,
                "po_no" => $request->po_no,
                "shipping_address_name" => $shipping_address_name,
                "disable_rounded_total" => 1,
                "order_type_1" => "Vatable",
                "sales_type" => "Sales on Consignment",
                "sales_person" => "Plant 2",
                "custom_remarks" => "Generated from AthenaERP Consignment Sales Report Import Tool. Created by: " . $current_user,
                "branch_warehouse" => $request->branch_warehouse,
                "project" => $request->project,
                "items" => $sales_order_item_data,
                "taxes" => $sales_taxes
            ];

            $erp_api_key = env('ERP_API_KEY');
            $erp_api_secret_key = env('ERP_API_SECRET_KEY');
            $erp_api_base_url = env('ERP_API_BASE_URL');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'token ' . $erp_api_key . ':' . $erp_api_secret_key,
                'Accept-Language' => 'en'
            ])->post($erp_api_base_url . '/api/resource/Sales Order', $sales_order_data);

            
            if ($response->successful()) {
                $sales_order = $response['data']['name'];
                return response()->json(['status' => 1, 'message' => 'Sales Order <a href="' . $erp_api_base_url . '/app/sales-order/' . $sales_order . '" target="_blank">' . $sales_order . '</a> has been created.']);
            }

            return response()->json(['status' => 0, 'message' => 'Something went wrong. Please contact your system administrator.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 0, 'message' => 'Something went wrong. Please contact your system administrator.']);
        }
    }

    public function assign_barcodes(Request $request){
        DB::beginTransaction();
        try {
            $assigned_barcodes = DB::table('tabItem as i')
                ->join('tabConsignment Item Barcode as b', 'b.parent', 'i.name')
                ->whereIn('b.barcode', $request->barcode)->where('b.customer', $request->customer)
                ->pluck('i.name', 'b.barcode');
            
            $barcodes = $request->barcode;
            $item_codes = $request->item_code;
            foreach($barcodes as $b => $barcode){
                if (!$item_codes[$b]) {
                    return response()->json(['status' => 0, 'message' => 'Please select item code for <b>' . $barcode . '</b>.']);
                }

                if(isset($assigned_barcodes[$barcode])){
                    return response()->json(['status' => 0, 'message' => 'Barcode <b>'.$barcode.'</b> is already assigned to item <b>'.$assigned_barcodes[$barcode].'</b>']);
                }
                
                $insert_arr[] = [
                    'name' => uniqid(),
                    'creation' => Carbon::now()->toDateTimeString(),
                    'modified' => Carbon::now()->toDateTimeString(),
                    'owner' => Auth::user()->wh_user,
                    'modified_by' => Auth::user()->wh_user,
                    'docstatus' => 0,
                    'idx' => 1,
                    'parent' => $item_codes[$b],
                    'parentfield' => 'barcodes',
                    'parenttype' => 'Item',
                    'customer' => $request->customer,
                    'barcode' => $barcode
                ];
            }

            DB::table('tabConsignment Item Barcode')->insert($insert_arr);

            DB::commit();
            return response()->json(['status' => 1, 'message' => 'Success!']);
        } catch (\Throwable $th) {
            DB::rollback();
            
            return response()->json(['status' => 0, 'message' => 'An error occured while updating item barcodes. Please contact your system administrator.']);
        }
    }

    public function consignment_branches(Request $request){
        if($request->ajax()){
            $search_string = $request->search;
            $branches = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')->where('name', '!=', 'Consignment Warehouse - FI')
                ->with('bin', function ($bin){
                    $bin->select('name', 'warehouse', 'item_code', 'stock_uom', 'consigned_qty', 'consignment_price', 'actual_qty', DB::raw('consigned_qty * consignment_price as amount'));
                })
                ->when($request->search, function ($query) use ($search_string){
                    return $query->where(function($q) use ($search_string) {
                        $search_str = explode(' ', $search_string);
                        foreach ($search_str as $str) {
                            $q->where('name', 'LIKE', "%$str%");
                        }

                        $q->orWhere('name', 'LIKE', "%$search_string%");
                    });
                })->paginate(20);

            $warehouses = collect($branches->items())->pluck('name');

            $item_codes = $branches->flatMap(function($branch) {
                return $branch->bin->pluck('item_code');
            })->unique()->values();

            $flatten_item_codes = $item_codes->implode("','");

            $item_details = Item::whereRaw("name IN ('$flatten_item_codes')")->where('disabled', 0)
                ->with('defaultImage', function ($image){
                    $image->select('parent', 'image_path');
                })
                ->select('name', 'item_code', 'item_classification', 'description', 'item_name')
                ->get()->groupBy('item_code');

            $promodisers = DB::table('tabWarehouse Users as wu')
                ->join('tabAssigned Consignment Warehouse as acw', 'acw.parent', 'wu.frappe_userid')
                ->whereIn('acw.warehouse', $warehouses)->select('wu.*', 'acw.warehouse')->get()->groupBy('warehouse');

            return view('consignment.supervisor.tbl_branches', compact('branches', 'item_details', 'promodisers'));
        }

        return view('consignment.supervisor.branches');
    }

    public function export_to_excel($branch){
        $items = DB::table('tabItem as i')
            ->join('tabBin as b', 'i.name', 'b.item_code')
            ->where('b.warehouse', $branch)->where('i.disabled', 0)
            ->where(function($q) {
                $q->where('b.actual_qty', '>', 0)->orWhere('b.consigned_qty', '>', 0);
            })
            ->select('i.item_code', 'i.description', 'i.item_classification', 'b.consigned_qty', 'b.warehouse', 'b.actual_qty', 'b.stock_uom', 'b.consignment_price', DB::raw('b.consigned_qty * b.consignment_price as amount'))
            ->orderBy('b.warehouse', 'asc')->orderBy('b.actual_qty', 'desc')->get();

        return view('consignment.supervisor.export.warehouse_items', compact('branch', 'items'));
    }

    private function getSalesAmount($start, $end, $warehouse) {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $months_array = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $period = CarbonPeriod::create($start, $end);

        $included_dates = $included_months = [];
        foreach ($period as $date) {
            $included_months[] = $months_array[(int) Carbon::parse($date)->format('m')];
            $included_dates[] = Carbon::parse($date)->format('Y-m-d');
        }

        $sales_report = DB::table('tabConsignment Monthly Sales Report')
            ->whereIn('fiscal_year', [$start->format('Y'), $end->format('Y')])
            ->whereIn('month', $included_months)
            ->when($warehouse, function($q) use ($warehouse) {
                return $q->where('warehouse', $warehouse);
            })
            ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') ASC")
            ->get();

        $sales_amount = 0;
        foreach($sales_report as $details){
            $month_index = array_search($details->month, $months_array);
            $sales_per_day = collect(json_decode($details->sales_per_day));
            foreach($sales_per_day as $day => $amount){
                $sale_date = Carbon::parse($details->fiscal_year . '-' . $month_index . '-' . $day)->format('Y-m-d');
                if (in_array($sale_date, $included_dates)) {
                    $sales_amount += $amount;
                }
            }
        }

        return $sales_amount;
    }

    public function generateConsignmentID($table, $series, $count){
        $latest_record = DB::table($table)->orderBy('creation', 'desc')->first();

        $latest_id = 0;
        if($latest_record){
            if(!$latest_record->title){
                $last_serial_name = DB::table($table)->where('name', 'like', '%'.strtolower($series).'-000%')->orderBy('creation', 'desc')->pluck('name')->first();
    
                $latest_id = $last_serial_name ? explode('-', $last_serial_name)[1] : 0;
            }else{
                $latest_id = $latest_record->title ? explode('-', $latest_record->title)[1] : 0;
            }
        }

        $new_id = $latest_id + 1;
        $new_id = str_pad($new_id, $count, 0, STR_PAD_LEFT);
        $new_id = strtoupper($series).'-'.$new_id;

        return $new_id;
    }
}