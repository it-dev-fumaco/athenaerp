<?php

namespace App\Http\Controllers;

use App\Models\ConsignmentStockEntry;
use DB;
use Exception;
use Mail;
use Auth;
use Webp;
use ZipArchive;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

use App\Models\Bin;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\DeliveryNote;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\PackingSlip;
use App\Models\Project;
use App\Models\SalesOrder;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\StockReservation;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;

use App\Models\MESFeedbackedLogs;
use App\Models\MESJobTicket;
use App\Models\MESOperation;
use App\Models\MESProductionOrder;

use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;

class MainController extends Controller
{
    use ERPTrait, GeneralTrait;
    public function get_api_headers(){
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'token '.env('ERP_API_KEY').':'.env('ERP_API_SECRET_KEY'),
            'Accept-Language' => 'en'
        ];
    }

    public function allowed_parent_warehouses(){
        $user = Auth::user()->frappe_userid;
        return DB::table('tabWarehouse Access')
            ->where('parent', $user)->pluck('warehouse');
    }

    public function getCutoffDate($transaction_date) {
        $transactionDate = Carbon::parse($transaction_date);

        $start_date = Carbon::parse($transaction_date)->subMonth();
        $end_date = Carbon::parse($transaction_date)->addMonths(2);

        $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

        $transaction_date = $transactionDate->format('d-m-Y');
        
        $cutoff_period = [];
        foreach ($period as $i => $date) {
            $date1 = $date->day($cutoff_1);
            if ($date1 >= $start_date && $date1 <= $end_date) {
                $cutoff_period[] = $date->format('d-m-Y');
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

    public function index(Request $request){
        $this->update_reservation_status();
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);
        if(Auth::user()->user_group == 'User'){
            return redirect('/search_results');
        }

        if(Auth::user()->user_group == 'Promodiser'){
            $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', $user)->orderBy('warehouse', 'asc')->pluck('warehouse');
            
            if (count($assigned_consignment_store) > 0) {
                $currentDateTime = Carbon::now();

                $start_date = Carbon::now()->subMonth();
                $end_date = Carbon::now()->addMonths(2);

                $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

                $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

                $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

                $date_now = $currentDateTime->format('d-m-Y');
                
                $cutoff_period = [];
                foreach ($period as $i => $date) {
                    $date1 = $date->day($cutoff_1);
                    if ($date1 >= $start_date && $date1 <= $end_date) {
                        $cutoff_period[] = $date->format('d-m-Y');
                    }

                    if($i == 0){
                        $feb_cutoff = $cutoff_1 <= 28 ? $cutoff_1 : 28;
                        $cutoff_period[] = $feb_cutoff.'-02-'.Carbon::now()->format('Y');
                    }
                }

                $cutoff_period[] = $date_now;
                // sort array with given user-defined function
                usort($cutoff_period, function ($time1, $time2) {
                    return strtotime($time1) - strtotime($time2);
                });

                $date_now_index = array_search($date_now, $cutoff_period);
                // set duration from and duration to
                $duration_to = $cutoff_period[$date_now_index + 1];

                $duration_from = Carbon::parse($duration_to)->subMonths(1)->addDays(1)->format('d-m-Y');

                $due = 'Due: '. Carbon::parse($duration_to)->format('M d, Y');

                $inv_summary = DB::table('tabBin as b')
                    ->join('tabItem as i', 'i.name', 'b.item_code')
                    ->where('i.disabled', 0)->where('i.is_stock_item', 1)
                    ->whereIn('b.warehouse', $assigned_consignment_store)
                    ->where('consigned_qty', '>', 0)
                    ->select('b.warehouse', 'b.consigned_qty')
                    ->get()->toArray();

                $inv_summary = collect($inv_summary)->groupBy('warehouse');

                $inventory_summary = [];
                foreach ($inv_summary as $warehouse => $row) {
                    $inventory_summary[$warehouse] = [
                        'items_on_hand' => collect($row)->count(),
                        'total_qty' => collect($row)->sum('consigned_qty'),
                    ];
                }

                // get total pending inventory audit
                $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory as w')
                    ->where('status', 'Approved')
                    ->whereIn('branch_warehouse', $assigned_consignment_store)
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

                $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();
    
                if ($first_cutoff->gt($end)) {
                    $end = $first_cutoff;
                }
    
                $cutoff_date = $this->getCutoffDate($end->endOfDay());
                $period_from = $cutoff_date[0]->addDay();
                $period_to = $cutoff_date[1];

                // get total stock transfer
                $total_stock_transfer = DB::table('tabConsignment Stock Entry')
                    ->whereIn('source_warehouse', $assigned_consignment_store)->where('status', 'Pending')
                    ->count();

                // get total consignment orders
                $total_consignment_orders = MaterialRequest::where('custom_purpose', 'Consignment Order')->where('transfer_as', 'Consignment')->whereIn('branch_warehouse', $assigned_consignment_store)->where('consignment_status', 'For Approval')->count();

                // get incoming / to receive items
                $beginning_inventory_start = DB::table('tabConsignment Beginning Inventory')->orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();
                $beginning_inventory_start_date = $beginning_inventory_start ? Carbon::parse($beginning_inventory_start)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

                $now = Carbon::now();

                $branches_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory')
                    ->whereIn('branch_warehouse', $assigned_consignment_store)->where('status', '!=', 'Cancelled')
                    ->distinct()->pluck('branch_warehouse')->toArray();

                $branches_with_pending_beginning_inventory = [];
                foreach ($assigned_consignment_store as $store) {
                    if (!in_array($store, $branches_with_beginning_inventory)) {
                        $branches_with_pending_beginning_inventory[] = $store;
                    }
                }

                return view('consignment.index_promodiser', compact('assigned_consignment_store', 'inventory_summary', 'total_stock_transfer', 'total_consignment_orders', 'branches_with_pending_beginning_inventory', 'due'));
            }

            return redirect('/search_results');
        }

        if(Auth::user()->user_group == 'Consignment Supervisor'){
            return $this->viewConsignmentDashboard();
        }

        return view('index');
    }

    public function viewConsignmentDashboard() {
        $currentDateTime = Carbon::now();

        $start_date = Carbon::now()->subMonth();
        $end_date = Carbon::now()->addMonths(2);

        $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;

        $date_now = $currentDateTime->format('d-m-Y');
        
        $cutoff_period = [];
        foreach ($period as $i => $date) {
            $date1 = $date->day($cutoff_1);
            if ($date1 >= $start_date && $date1 <= $end_date) {
                $cutoff_period[] = $date->format('d-m-Y');
            }

            if($i == 0){
                $feb_cutoff = $cutoff_1 <= 28 ? $cutoff_1 : 28;
                $cutoff_period[] = $feb_cutoff.'-02-'.Carbon::now()->format('Y');
            }
        }

        $cutoff_period[] = $date_now;
        // sort array with given user-defined function
        usort($cutoff_period, function ($time1, $time2) {
            return strtotime($time1) - strtotime($time2);
        });

        $date_now_index = array_search($date_now, $cutoff_period);
        // set duration from and duration to
        $duration_to = $cutoff_period[$date_now_index + 1];

        $duration_from = Carbon::parse($duration_to)->subMonths(1)->addDays(1)->format('d-m-Y');

        $duration = Carbon::parse($duration_from)->format('M d, Y') . ' - ' . Carbon::parse($duration_to)->format('M d, Y');

        $consignment_branches = DB::table('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->join('tabWarehouse as w', 'w.name', 'acw.warehouse')
            ->where('wu.user_group', 'Promodiser')->where('w.disabled', 0)
            ->where('is_group', 0)
            ->select('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->groupBy('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->orderBy('w.warehouse_name', 'asc')->get()->toArray();

        $active_consignment_branches = collect($consignment_branches)->where('is_group', 0)->where('disabled', 0);

        $promodisers = DB::table('tabWarehouse Users')->where('user_group', 'Promodiser')->where('enabled', 1)->count();

        $consignment_branches_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory')
            ->where('status', 'Approved')->whereIn('branch_warehouse', array_column($consignment_branches, 'name'))
            ->distinct()->pluck('branch_warehouse')->count();

        if (count($consignment_branches) > 0) {
            $beginning_inv_percentage = number_format(($consignment_branches_with_beginning_inventory / count($consignment_branches)) * 100, 2);
        } else {
            $beginning_inv_percentage = 0;
        }

        // get total stock transfer
        $total_stock_transfers = DB::table('tabConsignment Stock Entry')->where('purpose', '!=', 'Item Return')->where('status', 'Pending')->count();

        $pending_to_receive = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereDate('ste.delivery_date', '>=', '2022-06-25')
            ->whereIn('ste.transfer_as', ['Consignment', 'For Return', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where(function($q) {
                $q->whereNull('sted.consignment_status')
                ->orwhere('sted.consignment_status', '!=', 'Received');
            })
            ->count();

        // get total consignment orders
        $total_consignment_orders = MaterialRequest::where('custom_purpose', 'Consignment Order')->where('transfer_as', 'Consignment')->where('consignment_status', 'For Approval')->count();

        $total_pending_inventory_audit = 0;
        // get total pending inventory audit
        $stores_with_beginning_inventory = DB::table('tabConsignment Beginning Inventory as w')
            ->where('status', 'Approved')->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->orderBy('branch_warehouse', 'asc')->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')->toArray();

        $inventory_audit_per_warehouse = DB::table('tabConsignment Inventory Audit Report')
            ->whereIn('branch_warehouse', array_keys($stores_with_beginning_inventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = Carbon::now()->endOfDay();

        $first_cutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') .'/'. $cutoff_1 .'/'. $end->format('Y'))->endOfDay();

        if ($first_cutoff->gt($end)) {
            $end = $first_cutoff;
        }

        $cutoff_date = $this->getCutoffDate(Carbon::now()->endOfDay());
        $period_from = $cutoff_date[0]->addDay();
        $period_to = $cutoff_date[1];

        $pending = [];
        foreach (array_keys($stores_with_beginning_inventory) as $store) {
            $beginning_inventory_transaction_date = array_key_exists($store, $stores_with_beginning_inventory) ? $stores_with_beginning_inventory[$store] : null;
            $last_inventory_audit_date = array_key_exists($store, $inventory_audit_per_warehouse) ? $inventory_audit_per_warehouse[$store] : null;

            if ($beginning_inventory_transaction_date) {
                $start = Carbon::parse($beginning_inventory_transaction_date);
            }

            if ($last_inventory_audit_date) {
                $start = Carbon::parse($last_inventory_audit_date);
            }

            $last_audit_date = $start;

            $start = $start->startOfDay();

            $check = Carbon::parse($start)->between($period_from, $period_to);
            if (Carbon::parse($start)->addDay()->startOfDay()->lt(Carbon::parse($period_to)->startOfDay())) {
                if ($last_audit_date->endOfDay()->lt($end) && $beginning_inventory_transaction_date) {
                        if(!$check) {
                        $total_pending_inventory_audit++;
                    }
                }
            }
        }

        $start_date = Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');
        $end_date = Carbon::now();

        $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff_filters = [];
        if ($sales_report_deadline) {
            $cutoff_1 = $sales_report_deadline->{'1st_cutoff_date'};
            
            $cutoff_period = [];
            foreach ($period as $i => $date) {
                $from = $to = null;
                $date1 = $date->day($cutoff_1);
                if ($date1 >= $start_date && $date1 <= $end_date) {
                    $cutoff_period[] = $date->format('d-m-Y');
                }

                if($i == 0){
                    $feb_cutoff = $cutoff_1 <= 28 ? $cutoff_1 : 28;
                    $cutoff_period[] = $feb_cutoff.'-02-'.Carbon::now()->format('Y');
                }
            }
    
            $cutoff_period[] = $end_date->format('d-m-Y');
            // sort array with given user-defined function
            usort($cutoff_period, function ($time1, $time2) {
                return strtotime($time1) - strtotime($time2);
            });
    
            foreach ($cutoff_period as $n => $cutoff_date) {
                if (array_key_exists($n + 1, $cutoff_period)) {
                    $cutoff_filters[] = [
                        'id' => $cutoff_period[$n] .'/'. $cutoff_period[$n + 1],
                        'cutoff_start' => Carbon::parse($cutoff_period[$n])->format('M. d, Y'),
                        'cutoff_end' => Carbon::parse($cutoff_period[$n + 1])->format('M. d, Y'),
                    ];
                }
            }
        }

        $sales_report_included_years = [];
        for ($i = 2022; $i <= date('Y') ; $i++) { 
            $sales_report_included_years[] = $i;
        }

        return view('consignment.index_consignment_supervisor', compact('duration', 'pending_to_receive', 'beginning_inv_percentage', 'promodisers', 'active_consignment_branches', 'consignment_branches', 'consignment_branches_with_beginning_inventory', 'total_stock_transfers', 'total_pending_inventory_audit', 'total_consignment_orders', 'cutoff_filters', 'sales_report_included_years'));
    }

    public function search_results(Request $request){
        $search_str = explode(' ', $request->searchString);

        $user = null;
        $assigned_consignment_store = [];
        if($request->assigned_to_me){
            $user = Auth::user()->frappe_userid;
            $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', $user)->pluck('warehouse');
        }

        $select_columns = [
            'tabItem.name as item_code',
            'tabItem.description',
            'tabItem.item_group',
            'tabItem.stock_uom',
            'tabItem.custom_item_cost',
            'tabItem.item_classification',
            'tabItem.item_group_level_1 as lvl1',
            'tabItem.item_group_level_2 as lvl2',
            'tabItem.item_group_level_3 as lvl3',
            'tabItem.item_group_level_4 as lvl4',
            'tabItem.item_group_level_5 as lvl5',
            'tabItem.weight_per_unit',
            'tabItem.package_length',
            'tabItem.package_width',
            'tabItem.package_height',
            'tabItem.weight_uom',
            'tabItem.package_dimension_uom',
            $request->wh ? 'd.warehouse' : null
        ];

        $select_columns = array_filter($select_columns);

        $check_qty = 1;
        if($request->has('check_qty')){
            $check_qty = $request->check_qty == 'on' ? 1 : 0;
        }

        $allow_warehouse = $consignment_stores = [];
        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
        if ($is_promodiser) {
            $allowed_parent_warehouse_for_promodiser = DB::table('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.parent_warehouse')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $allowed_warehouse_for_promodiser = DB::table('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.name')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse')->toArray();

            $allow_warehouse = array_merge($allowed_parent_warehouse_for_promodiser, $allowed_warehouse_for_promodiser);
            $allow_warehouse = array_merge($allow_warehouse, $consignment_stores);
        }

        $item_codes_based_on_warehouse_assigned = [];
        if(isset($request->assigned_to_me)){
            $item_codes_based_on_warehouse_assigned = DB::table('tabBin')->whereIn('warehouse', $assigned_consignment_store)->select('item_code', 'warehouse')->get();
            $item_codes_based_on_warehouse_assigned = array_keys(collect($item_codes_based_on_warehouse_assigned)->groupBy('item_code')->toArray());
        }

        $items = DB::table('tabItem')->where('tabItem.disabled', 0)
            ->where('tabItem.has_variants', 0)//->where('tabItem.is_stock_item', 1)
            ->when($request->assigned_items, function ($q) use ($consignment_stores){
                return $q->join('tabBin as bin', 'bin.item_code', 'tabItem.item_code')
                    ->whereIn('bin.warehouse', $consignment_stores);
            })
            ->when($request->searchString, function ($query) use ($search_str, $request) {
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('tabItem.description', 'LIKE', "%$str%");
                    }

                    $q->orWhere('tabItem.name', 'LIKE', "%$request->searchString%")
                        ->orWhere('tabItem.item_group', 'LIKE', "%$request->searchString%")
                        ->orWhere('tabItem.item_classification', 'LIKE', "%$request->searchString%")
                        ->orWhere('tabItem.stock_uom', 'LIKE', "%$request->searchString%")
                        ->orWhere(DB::raw('(SELECT GROUP_CONCAT(DISTINCT supplier_part_no SEPARATOR "; ") FROM `tabItem Supplier` WHERE parent = `tabItem`.name)'), 'LIKE', "%$request->searchString%");
                });
            })
            ->when($request->classification, function($q) use ($request){
                return $q->where('tabItem.item_classification', $request->classification);
            })
            ->when($request->brand, function($q) use ($request){
                return $q->where('tabItem.brand', $request->brand);
            })
            ->when($check_qty && !$is_promodiser, function($q) {
                return $q->where(DB::raw('(SELECT SUM(`tabBin`.actual_qty) FROM `tabBin` JOIN tabWarehouse ON tabWarehouse.name = `tabBin`.warehouse WHERE `tabBin`.item_code = `tabItem`.name and `tabWarehouse`.stock_warehouse = 1)'), '>', 0);
            })
            ->when($request->assigned_to_me, function($q) use ($item_codes_based_on_warehouse_assigned){
                return $q->whereIn('tabItem.name', $item_codes_based_on_warehouse_assigned);
            })
            ->when($request->wh, function($q) use ($request){
                return $q->join('tabBin as d', 'd.item_code', 'tabItem.name')
                    ->where('d.warehouse', $request->wh);
            })
            ->when($request->group, function ($q) use ($request){
                return $q->where(function ($query) use ($request){
                    return $query->where('tabItem.item_group', $request->group)
                        ->orWhere('tabItem.item_group_level_1', $request->group)
                        ->orWhere('tabItem.item_group_level_2', $request->group)
                        ->orWhere('tabItem.item_group_level_3', $request->group)
                        ->orWhere('tabItem.item_group_level_4', $request->group)
                        ->orWhere('tabItem.item_group_level_5', $request->group);
                });
            })->select($select_columns)->orderBy('tabItem.modified', 'desc')->paginate(20);

        $itemGroups = collect($items->items())->map(function ($q){
            return [
                'item_group' => $q->item_group,
                'item_group_level_1' => $q->lvl1,
                'item_group_level_2' => $q->lvl2,
                'item_group_level_3' => $q->lvl3,
                'item_group_level_4' => $q->lvl4,
                'item_group_level_5' => $q->lvl5,
            ];
        })->unique()->values()->all();

        $a = array_column($itemGroups, 'item_group');
        $a1 = array_column($itemGroups, 'item_group_level_1');
        $a2 = array_column($itemGroups, 'item_group_level_2');
        $a3 = array_column($itemGroups, 'item_group_level_3');
        $a4 = array_column($itemGroups, 'item_group_level_4');
        $a5 = array_column($itemGroups, 'item_group_level_5');

        $igs = array_unique(array_merge($a, $a1, $a2, $a3, $a4, $a5));

        $breadcrumbs = [];
        if($request->group){
            $selected_group = DB::table('tabItem Group')->where('item_group_name', $request->group)->first();
            if($selected_group){
                session()->forget('breadcrumbs');
                if(!session()->has('breadcrumbs')){
                    session()->put('breadcrumbs', []);
                }

                session()->push('breadcrumbs', $request->group);
                $this->breadcrumbs($selected_group->parent_item_group);

                $breadcrumbs = array_reverse(session()->get('breadcrumbs'));
            }
        }

        // $total_items = count($items);
        $total_items = $items->total();

        // $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // // Create a new Laravel collection from the array data3
        // $itemCollection = collect($items);
        // // Define how many items we want to be visible in each page
        // $perPage = 20;
        // // Slice the collection to get the items to display in current page
        // $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // // Create our paginator and pass it to the view
        // $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
        // // set url path for generted links
        // $paginatedItems->setPath($request->url());
        // $items = $paginatedItems;

        // $url = $request->fullUrl();
        // $items->withPath($url);

        if($request->searchString){
            DB::table('tabAthena Inventory Search History')->insert([
                'name' => uniqid(),
                'creation' => Carbon::now(),
                'modified' => Carbon::now(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'search_string' => $request->searchString,
                'total_result' => $total_items
            ]);
        }
        
        if($request->get_total){
            return number_format($total_items);
        }

        $item_codes = array_column($items->items(), 'item_code');

        $bundled_items = DB::table('tabProduct Bundle')->whereIn('name', $item_codes)->pluck('name')->toArray();

        $item_inventory = DB::table('tabBin')->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')->whereIn('item_code', $item_codes)
            ->when($is_promodiser, function($q) use ($allow_warehouse) {
                return $q->whereIn('warehouse', $allow_warehouse);
            })
            ->where('stock_warehouse', 1)->where('tabWarehouse.disabled', 0)
            ->select('item_code', 'warehouse', 'location', 'actual_qty', 'tabBin.consigned_qty', 'stock_uom', 'parent_warehouse')
            ->get();

        $item_warehouses = array_column($item_inventory->toArray(), 'warehouse');

        $item_inventory = collect($item_inventory)->groupBy('item_code')->toArray();

        $stock_reservation = StockReservation::whereIn('item_code', $item_codes)
            ->whereIn('warehouse', $item_warehouses)->whereIn('status', ['Active', 'Partially Issued'])
            ->selectRaw('SUM(reserve_qty) as total_reserved_qty, SUM(consumed_qty) as total_consumed_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')->get();
        $stock_reservation = collect($stock_reservation)->groupBy('item')->toArray();

        $ste_total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
            ->whereIn('item_code', $item_codes)->whereIn('s_warehouse', $item_warehouses)
            ->selectRaw('SUM(qty) as total_issued, CONCAT(item_code, "-", s_warehouse) as item')
            ->groupBy('item_code', 's_warehouse')->get();
        $ste_total_issued = collect($ste_total_issued)->groupBy('item')->toArray();

        $at_total_issued = DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')->whereIn('at.item_code', $item_codes)
            ->whereIn('psi.item_code', $item_codes)->whereIn('at.source_warehouse', $item_warehouses)
            ->selectRaw('SUM(at.issued_qty) as total_issued, CONCAT(at.item_code, "-", at.source_warehouse) as item')
            ->groupBy('at.item_code', 'at.source_warehouse')
            ->get();

        $at_total_issued = collect($at_total_issued)->groupBy('item')->toArray();

        $lowLevelStock = DB::table('tabItem Reorder')
            ->whereIn('parent', $item_codes)->whereIn('warehouse', $item_warehouses)
            ->selectRaw('SUM(warehouse_reorder_level) as total_warehouse_reorder_level, CONCAT(parent, "-", warehouse) as item')
            ->groupBy('parent', 'warehouse')->get();
        $lowLevelStock = collect($lowLevelStock)->groupBy('item')->toArray();

        $item_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $item_images = collect($item_images)->map(function ($image){
            return $this->base64_image("/img/$image");
        });
        $no_img_placeholder = $this->base64_image('/icon/no_img.png');

        $part_nos_query = DB::table('tabItem Supplier')->whereIn('parent', $item_codes)
            ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))->groupBy('parent')->pluck('supplier_part_nos', 'parent');

        $user_department = Auth::user()->department;
        // get departments NOT allowed to view prices
        $allowed_department = DB::table('tabDeparment with Price Access')->pluck('department')->toArray();

        $last_purchase_order = [];
        $last_landed_cost_voucher = [];
        $price_settings = [];
        $website_prices = [];
        if (in_array($user_department, $allowed_department) || in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            $last_purchase_order = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
                ->where('po.docstatus', 1)->whereIn('poi.item_code', $item_codes)->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')->orderBy('po.creation', 'desc')->get();

            $last_landed_cost_voucher = DB::table('tabLanded Cost Voucher as a')->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                ->where('a.docstatus', 1)->whereIn('b.item_code', $item_codes)->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')->orderBy('transaction_date', 'desc')->get();
            
            $last_purchase_order_rates = collect($last_purchase_order)->groupBy('item_code')->toArray();
            $last_landed_cost_voucher_rates = collect($last_landed_cost_voucher)->groupBy('item_code')->toArray();

            $website_prices = DB::table('tabItem Price')->where('price_list', 'Website Price List')->where('selling', 1)
                ->whereIn('item_code', $item_codes)->orderBy('modified', 'desc')->pluck('price_list_rate', 'item_code')->toArray();

            $price_settings = DB::table('tabSingles')->where('doctype', 'Price Settings')
                ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])->pluck('value', 'field')->toArray();
        }

        $minimum_price_computation = array_key_exists('minimum_price_computation', $price_settings) ? $price_settings['minimum_price_computation'] : 0;
        $standard_price_computation = array_key_exists('standard_price_computation', $price_settings) ? $price_settings['standard_price_computation'] : 0;
        $is_tax_included_in_rate = array_key_exists('is_tax_included_in_rate', $price_settings) ? $price_settings['is_tax_included_in_rate'] : 0;

        $item_list = [];
        foreach ($items as $row) {
            $image = isset($item_images[$row->item_code]) ? $item_images[$row->item_code] : $no_img_placeholder;

            $part_nos = Arr::exists($part_nos_query, $row->item_code) ? $part_nos_query[$row->item_code] : null;

            $site_warehouses = [];
            $consignment_warehouses = [];
            $item_inventory_arr = [];
            if (array_key_exists($row->item_code, $item_inventory)) {
                $item_inventory_arr = $item_inventory[$row->item_code];
            }
            foreach ($item_inventory_arr as $value) {
                $reserved_qty = 0;
                if (array_key_exists($value->item_code . '-' . $value->warehouse, $stock_reservation)) {
                    $reserved_qty = $stock_reservation[$value->item_code . '-' . $value->warehouse][0]['total_reserved_qty'];
                }

                $consumed_qty = 0;
                if (array_key_exists($value->item_code . '-' . $value->warehouse, $stock_reservation)) {
                    $consumed_qty = $stock_reservation[$value->item_code . '-' . $value->warehouse][0]['total_consumed_qty'];
                }

                $issued_qty = 0;
                if (array_key_exists($value->item_code . '-' . $value->warehouse, $ste_total_issued)) {
                    $issued_qty = $ste_total_issued[$value->item_code . '-' . $value->warehouse][0]->total_issued;
                }

                if (array_key_exists($value->item_code . '-' . $value->warehouse, $at_total_issued)) {
                    $issued_qty += $at_total_issued[$value->item_code . '-' . $value->warehouse][0]->total_issued;
                }

                $reserved_qty = $reserved_qty - $consumed_qty;
                $reserved_qty = $reserved_qty > 0 ? $reserved_qty : 0;

                $actual_qty = $value->actual_qty;

                $warehouse_reorder_level = 0;
                if (array_key_exists($value->item_code . '-' . $value->warehouse, $lowLevelStock)) {
                    $warehouse_reorder_level = $lowLevelStock[$value->item_code . '-' . $value->warehouse][0]->total_warehouse_reorder_level;
                }

                $issued_reserved_qty = ($reserved_qty + $issued_qty) - $consumed_qty;

                $available_qty = ($actual_qty > $issued_reserved_qty) ? $actual_qty - $issued_reserved_qty : 0;
                if($value->parent_warehouse == "P2 Consignment Warehouse - FI" && !$is_promodiser) {
                    $consignment_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'location' => $value->location,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $value->actual_qty,
                        'available_qty' => $available_qty,
                        'consigned_qty' => $value->consigned_qty > 0 ? $value->consigned_qty : 0,
                        'stock_uom' => $value->stock_uom ? $value->stock_uom : $row->stock_uom,
                        'warehouse_reorder_level' => $warehouse_reorder_level,
                        'parent_warehouse' => $value->parent_warehouse
                    ];
                }else{
                    if(Auth::user()->user_group == 'Promodiser' && $value->parent_warehouse == "P2 Consignment Warehouse - FI"){
                        $available_qty = $value->consigned_qty > 0 ? $value->consigned_qty : 0;
                    }

                    $site_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'location' => $value->location,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $value->actual_qty,
                        'available_qty' => $available_qty,
                        'consigned_qty' => $value->consigned_qty > 0 ? $value->consigned_qty : 0,
                        'stock_uom' => $value->stock_uom ? $value->stock_uom : $row->stock_uom,
                        'warehouse_reorder_level' => $warehouse_reorder_level,
                        'parent_warehouse' => $value->parent_warehouse
                    ];
                }
            }

            $last_purchase_order_rates = collect($last_purchase_order)->groupBy('item_code')->toArray();
            $last_landed_cost_voucher_rates = collect($last_landed_cost_voucher)->groupBy('item_code')->toArray();

            $item_rate = 0;
            $last_purchase_order_arr = array_key_exists($row->item_code, $last_purchase_order_rates) ? $last_purchase_order_rates[$row->item_code][0] : [];
            if ($last_purchase_order_arr) {
                if ($last_purchase_order_arr->supplier_group == 'Imported') {
                    $last_landed_cost_voucher = array_key_exists($row->item_code, $last_landed_cost_voucher_rates) ? $last_landed_cost_voucher_rates[$row->item_code][0] : [];
                    
                    if ($last_landed_cost_voucher) {
                        $item_rate = $last_landed_cost_voucher->valuation_rate;
                    }
                } else {
                    $item_rate = $last_purchase_order_arr->base_rate;
                }
            }

            if ($item_rate <= 0) {
                $item_rate = $row->custom_item_cost;
            }

            $minimum_selling_price = $item_rate * $minimum_price_computation;
            $default_price = $item_rate * $standard_price_computation;
            if ($is_tax_included_in_rate) {
                $default_price = ($item_rate * $standard_price_computation) * 1.12;
            }

            $website_price = array_key_exists($row->item_code, $website_prices) ? $website_prices[$row->item_code] : 0;

            $default_price = ($website_price > 0) ? $website_price : $default_price;

            $package_dimension = null;
            if(
                $row->weight_per_unit > 0 ||
                $row->package_length > 0 ||
                $row->package_width > 0 ||
                $row->package_height > 0
            ){
                $package_dimension = '<span class="text-muted">Net Weight:</span> <b>'.($row->weight_per_unit > 0 ? (float)$row->weight_per_unit.' '.$row->weight_uom : '-' ).'</b>, ';
                $package_dimension .= '<span class="text-muted">Length:</span> <b>'.($row->package_length > 0 ? (float)$row->package_length.' '.$row->package_dimension_uom : '-' ).'</b>, ';
                $package_dimension .= '<span class="text-muted">Width:</span>  <b>'.($row->package_width > 0 ? (float)$row->package_width.' '.$row->package_dimension_uom : '-' ).'</b>, ';
                $package_dimension .= '<span class="text-muted">Height:</span> <b>'.($row->package_height > 0 ? (float)$row->package_height.' '.$row->package_dimension_uom : '-').'</b>';
            }

            $item_list[] = [
                'name' => $row->item_code,
                'description' => $row->description,
                // 'item_image_paths' => $item_images,
                'image' => $image,
                'part_nos' => $part_nos,
                'item_group' => $row->item_group,
                'stock_uom' => $row->stock_uom,
                'item_classification' => $row->item_classification,
                'item_inventory' => $site_warehouses,
                'consignment_warehouses' => $consignment_warehouses,
                'default_price' => $default_price,
                'package_dimension' => $package_dimension
            ];
        }

        $root = DB::table('tabItem Group')->where('parent_item_group', '')->pluck('name')->first();

        $item_group = DB::table('tabItem Group')
            ->when(array_filter($request->all()), function ($q) use ($igs, $request){
                $q->whereIn('item_group_name', $igs)
                    ->orWhere('item_group_name', 'LIKE', '%'.$request->searchString.'%');
            })
            ->select('name','item_group_name','parent_item_group','is_group','old_parent', 'order_no')
            ->orderByRaw('LENGTH(order_no) ASC')
            ->orderBy('order_no', 'ASC')
            ->get();

        $all = collect($item_group)->groupBy('parent_item_group');

        $item_groups = collect($item_group)->where('parent_item_group', $root)->where('is_group', 1)->groupBy('name')->toArray();
        $sub_items = array_filter($request->all()) ? collect($item_group)->where('parent_item_group', '!=', $root) : [];

        $arr = [];
        if($sub_items){
            $item_group_arr = [];
            $igs_collection = collect($item_group)->groupBy('item_group_name');
            session()->forget('igs_array');
            if(!session()->has('igs_array')){
                session()->put('igs_array', []);
            }

            foreach($sub_items as $a){
                if(!in_array($a->item_group_name, session()->get('igs_array'))){
                    session()->push('igs_array', $a->item_group_name);
                }

                $this->check_item_group_tree($a->parent_item_group, $igs_collection);
            }

            $igs_array = session()->get('igs_array');

            $arr = array_filter($igs_array);
        }

        $item_group_array = $this->item_group_tree(1, $item_groups, $all, $arr);

        return view('search_results', compact('item_list', 'items', 'all', 'item_groups', 'item_group_array', 'breadcrumbs', 'total_items', 'root', 'allowed_department', 'user_department', 'bundled_items', 'no_img_placeholder'));
    }

    private function breadcrumbs($parent){
        session()->push('breadcrumbs', $parent);

        $root_parent = DB::table('tabItem Group')->where('item_group_name', $parent)->pluck('parent_item_group')->first();
        if($root_parent){
            $this->breadcrumbs($root_parent);
        }
        return 1;
    }

    private function check_item_group_tree($parent, $igs_collection){
        $item_group = isset($igs_collection[$parent]) ? $igs_collection[$parent][0] : [];
        $item_groups = session()->get('igs_array');
        if($item_group){
            if(!in_array($item_group->item_group_name, $item_groups)){
                session()->push('igs_array', $item_group->item_group_name);
            }

            $this->check_item_group_tree($item_group->parent_item_group, $igs_collection);
            return 1;
        }
    }

    private function item_group_tree($current_lvl, $group, $all, $igs_array = []){
        $current_lvl = $current_lvl + 1;

        $lvl_arr = [];
        if($igs_array){
            foreach($group as $lvl){
                $next_level = isset($all[$lvl[0]->name]) ? collect($all[$lvl[0]->name])->groupBy('name') : [];
                if(in_array($lvl[0]->name, $igs_array)){
                    if($next_level){
                        $nxt = $this->item_group_tree($current_lvl, $next_level, $all, $igs_array);
                        $lvl_arr[$lvl[0]->name] = [
                            'lvl'.$current_lvl => $nxt,
                            'is_group' => $lvl[0]->is_group,
                        ];
                    }else{
                        $lvl_arr[$lvl[0]->name] = [
                            'lvl'.$current_lvl => $next_level,
                            'is_group' => $lvl[0]->is_group,
                        ];
                    }
                }
            }
        }else{
            foreach($group as $lvl){
                $next_level = isset($all[$lvl[0]->name]) ? collect($all[$lvl[0]->name])->groupBy('name') : [];
                if($next_level){
                    $nxt = $this->item_group_tree($current_lvl, $next_level, $all);
                    $lvl_arr[$lvl[0]->name] = [
                        'lvl'.$current_lvl => $nxt,
                        'is_group' => $lvl[0]->is_group,
                    ];
                }else{
                    $lvl_arr[$lvl[0]->name] = [
                        'lvl'.$current_lvl => $next_level,
                        'is_group' => $lvl[0]->is_group,
                    ];
                }
            }
        }

        return $lvl_arr;
    }

    public function search_results_images(Request $request){
        if($request->ajax()){
            $item_images = DB::table('tabItem Images')->where('parent', $request->item_code)->orderBy('idx', 'asc')->get();

            $dir = $request->dir == 'next' ? 0 : count($item_images) - 1;
    
            $img = isset($item_images[$request->img_key]) ? $item_images[$request->img_key]->image_path : $item_images[$dir]->image_path;
            $current_key = isset($item_images[$request->img_key]) ? $request->img_key : $dir;
            
            $img_arr = [
                'item_code' => $request->item_code,
                'alt' => Str::slug(explode('.', $item_images[$current_key]->image_path)[0]),
                'orig_image_path' => asset('storage/').'/img/'.$img,
                'orig_path' => Storage::disk('public')->exists('/img/'.$img) ? 1 : 0,
                'webp_image_path' => asset('storage/').'/img/'.explode('.', $img)[0].'.webp',
                'webp_path' => Storage::disk('public')->exists('/img/'.explode('.',$img)[0]) ? 1 : 0,
                'current_img_key' => $current_key
            ];
                    
            return $img_arr;
        }
    }

    public function recently_received_items(Request $request){
        $allowed_warehouses = $this->user_allowed_warehouse(Auth::user()->frappe_userid);

        $list = DB::table('tabPurchase Receipt Item as item')
            ->join('tabPurchase Receipt as parent', 'parent.name', 'item.parent')
            ->whereIn('item.warehouse', $allowed_warehouses)->where('item.docstatus', 1)->whereDate('parent.posting_date', '>', Carbon::now()->subDays(7)->startOfDay())
            ->select('parent.name', 'item.item_code', 'item.description', 'item.image', 'item.warehouse', 'item.qty', 'item.uom', 'item.creation', 'parent.posting_date', 'parent.posting_time', 'parent.owner')
            ->orderBy('parent.posting_date', 'desc')->paginate(10);

        $item_images = DB::table('tabItem Images')->whereIn('parent', collect($list->items())->pluck('item_code'))->get();
        $item_image = collect($item_images)->groupBy('parent');

        return view('tbl_recently_received_items', compact('item_image', 'list'));
    }

    public function reserved_qty(Request $request){
        $reservedQty = DB::table('tabStock Reservation')->select('item_code', 'warehouse', 'reserve_qty')->get();

        return view('index', compact('reservedQty'));
    }

    public function count_ste_for_issue($purpose){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $count = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', $purpose)
            ->whereNotIn('sted.status', ['Issued', 'Returned'])
            ->when($purpose == 'Material Issue', function($q) use ($allowed_warehouses){
				return $q->whereNotIn('ste.issue_as', ['Customer Replacement', 'Sample'])
                    ->whereIn('sted.s_warehouse', $allowed_warehouses);
            })
            ->when($purpose == 'Material Transfer', function($q) use ($allowed_warehouses){
				return $q->whereNotin('ste.transfer_as', ['Consignment', 'Sample Item', 'For Return'])
                    ->whereIn('sted.s_warehouse', $allowed_warehouses);
            })
            ->when($purpose == 'Material Transfer for Manufacture', function($q) use ($allowed_warehouses){
				return $q->whereIn('sted.s_warehouse', $allowed_warehouses);
            })
            ->when($purpose == 'Material Receipt', function($q) use ($allowed_warehouses){
				return $q->where('ste.receive_as', 'Sales Return')
                    ->whereIn('sted.t_warehouse', $allowed_warehouses);
            })->count();

        if($purpose == 'Material Receipt') {
            $count += DB::table('tabDelivery Note as dn')->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
                ->where('dn.is_return', 1)->where('dn.docstatus', 0)->whereIn('dni.warehouse', $allowed_warehouses)->count();

            $count += DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', 0)->where('ste.purpose', 'Material Transfer')->where('ste.transfer_as', 'For Return')->whereIn('sted.t_warehouse', $allowed_warehouses)->where('ste.naming_series', 'STEC-')->count();
        }

        if($purpose == 'Material Transfer') {
            $count += DB::table('tabStock Entry as ste')->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')->whereNotIn('sted.status', ['Issued', 'Returned'])
                ->whereIn('t_warehouse', $allowed_warehouses)->whereIn('transfer_as', ['For Return', 'Internal Transfer'])
                ->count();
        }

        return $count;
    }

    public function count_ps_for_issue(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q_1 = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->where('psi.status', 'For Checking')
                ->whereRaw(('dri.item_code = psi.item_code'))->where('ps.docstatus', 0)
                ->where('dri.docstatus', 0)->whereIn('dri.warehouse', $allowed_warehouses)
                ->count();

        $q_2 = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')
            ->where('sted.status', 'For Checking')
            ->whereIn('s_warehouse', $allowed_warehouses)->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'ste.customer_1', 'sted.parent', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'sted.name as id', 'sted.stock_uom')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->count();

        return ($q_1 + $q_2);
    }

    public function user_allowed_warehouse($user){
        $allowed_parent_warehouses = DB::table('tabWarehouse Access')
            ->where('parent', $user)->pluck('warehouse');

        return DB::table('tabWarehouse')
            ->where('disabled', 0)->whereIn('parent_warehouse', $allowed_parent_warehouses)->pluck('name');
    }

    public function load_suggestion_box(Request $request){
        $search_str = explode(' ', $request->search_string);
        $q = DB::table('tabItem')
            ->where('disabled', 0)
            ->where('has_variants', 0)//->where('is_stock_item', 1)
            ->where(function($q) use ($search_str, $request) {
                foreach ($search_str as $str) {
                    $q->where('tabItem.description', 'LIKE', "%".$str."%");
                }
                $q->orWhere('tabItem.name', 'like', '%'.$request->search_string.'%')
                ->orWhere('item_classification', 'like', '%'.$request->search_string.'%')
                ->orWhere('item_group', 'like', '%'.$request->search_string.'%')
                ->orWhere('stock_uom', 'like', '%'.$request->search_string.'%')
                ->orWhere(DB::raw('(SELECT GROUP_CONCAT(DISTINCT supplier_part_no SEPARATOR "; ") FROM `tabItem Supplier` WHERE parent = `tabItem`.name)'), 'LIKE', "%".$request->search_string."%");
            })
            ->select('tabItem.name', 'description', 'item_image_path')
            ->orderBy('tabItem.modified', 'desc')->paginate(8);

        $item_codes = collect($q->items())->pluck('name');

        $bundled_items = DB::table('tabProduct Bundle')->whereIn('name', $item_codes)->pluck('name')->toArray();

        $image_collection = DB::table('tabItem Images')->whereIn('parent', $item_codes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $image_collection = collect($image_collection)->map(function ($image){
            return $this->base64_image("/img/$image");
        });

        $no_img = $this->base64_image('/icon/no_img.png');

        return view('suggestion_box', compact('q', 'image_collection', 'bundled_items', 'no_img'));
    }

    public function get_select_filters(Request $request){
        $warehouses = DB::table('tabWarehouse')->where('is_group', 0)->where('disabled', 0)
            ->whereIn('category', ['Physical', 'Consigned'])
            ->when($request->q, function ($q) use ($request){
                $q->where('name', 'LIKE', '%'.$request->q.'%')->orWhere('warehouse_name', 'LIKE', '%'.$request->q.'%');
            })->where('disabled', 0)
            ->selectRaw('name as id, name as text')->orderBy('name', 'asc')->get();

        $item_groups = DB::table('tabItem Group')->where('is_group', 0)->where('name','LIKE', '%'.$request->q.'%')
            ->where('show_in_erpinventory', 1)->selectRaw('name as id, name as text')->orderBy('name', 'asc')->get();//pluck('name');

        $item_classification = DB::table('tabItem Classification')
            ->orderBy('name', 'asc')->pluck('name');

        $item_class_filter = DB::table('tabItem Classification')->where('name', 'LIKE', '%'.$request->q.'%')->selectRaw('name as id, name as text')->orderBy('name', 'asc')->get();

        $brand_filter = DB::table('tabBrand')->where('name', 'LIKE', '%'.$request->q.'%')->selectRaw('name as id, name as text')->orderBy('name', 'asc')->get();

        // $WHusers = DB::table('tabAthena Transactions')->groupBy('warehouse_user')->pluck('warehouse_user');
        $Athena_wh_users = DB::table('tabAthena Transactions')->groupBy('warehouse_user')->where('warehouse_user','LIKE', '%'.$request->q.'%')->where('status', 'Issued')
            ->selectRaw('warehouse_user as id, warehouse_user as text')->get();

        $Athena_src_wh = DB::table('tabAthena Transactions')->groupBy('source_warehouse')->where('source_warehouse','LIKE', '%'.$request->q.'%')->where('status', 'Issued')
            ->where('source_warehouse', '!=', '')->selectRaw('source_warehouse as id, source_warehouse as text')->get();

        $Athena_to_wh = DB::table('tabAthena Transactions')->groupBy('target_warehouse')->where('target_warehouse','LIKE', '%'.$request->q.'%')->where('status', 'Issued')
            ->where('target_warehouse', '!=', '')->selectRaw('target_warehouse as id, target_warehouse as text')->get();

        $ERP_wh_users = DB::table('tabStock Entry Detail')->groupBy('session_user')->where('session_user','LIKE', '%'.$request->q.'%')
            ->selectRaw('session_user as id, session_user as text')->get();

        $ERP_wh = DB::table('tabStock Ledger Entry')->groupBy('warehouse')->where('warehouse','LIKE', '%'.$request->q.'%')
            ->selectRaw('warehouse as id, warehouse as text')->get();

        return response()->json([
            // 'parent_warehouses' => $allowed_warehouses,
            'warehouses' => $warehouses,
            'warehouse_users' => $Athena_wh_users,
            'source_warehouse' => $Athena_src_wh,
            'target_warehouse' => $Athena_to_wh,
            'warehouse' => $ERP_wh,
            'session_user' => $ERP_wh_users,
            'item_groups' => $item_groups,
            'item_class_filter' => $item_class_filter,
            'item_classification' => $item_classification,
            'brand' => $brand_filter
        ]);
    }

    public function get_actual_qty($item_code, $warehouse){
        return DB::table('tabBin')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->sum('actual_qty');
    }

    public function get_issued_qty($item_code, $warehouse){
        $total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
            ->where('item_code', $item_code)->where('s_warehouse', $warehouse)->sum('qty');

        $total_issued += DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')->where('at.item_code', $item_code)
            ->where('psi.item_code', $item_code)->where('at.source_warehouse', $warehouse)
            ->sum('at.issued_qty');

        return $total_issued;
    }

    public function get_parent_warehouses(){
        $user = Auth::user()->frappe_userid;
        $q = DB::table('tabWarehouse Access as wa')
            ->join('tabWarehouse Users as wu', 'wa.parent', 'wu.name')
            ->where('wu.frappe_userid', $user)->get();

        $list = [];
        foreach($q as $w){
            $list[] = [
                'name' => $w->warehouse_name,
                'user' => $w->wh_user,
                'frappe_userid' => $w->frappe_userid
            ];
        }

        return response()->json(['wh' => $list]);
    }

    public function get_warehouse_parent($child_warehouse){
        $q = DB::table('tabWarehouse')->where('disabled', 0)->where('name', $child_warehouse)->first();
        if($q){
            return $q->parent_warehouse;
        }

        return null;
    }

    // /material_issue
    public function view_material_issue(Request $request){
        if(!$request->arr){
            return view('material_issue');
        }
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Issue')
            ->whereIn('s_warehouse', $allowed_warehouses)
            ->whereNotIn('ste.issue_as', ['Customer Replacement', 'Sample'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.creation', 'ste.issue_as')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

        $list = [];
        foreach ($q as $d) {
            $actual_qty = $this->get_actual_qty($d->item_code, $d->s_warehouse);

            $total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
                ->where('item_code', $d->item_code)->where('s_warehouse', $d->s_warehouse)->sum('qty');
            
            $balance = $actual_qty - $total_issued;

            $customer = DB::table('tabSales Order')->where('name', $d->sales_order_no)->first();
            $customer = ($customer) ? $customer->customer : null;

            $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');

            $part_nos = implode(', ', $part_nos->toArray());

            $owner = DB::table('tabUser')->where('name', $d->owner)->first()->full_name;

            $parent_warehouse = $this->get_warehouse_parent($d->s_warehouse);

            $list[] = [
                'customer' => $customer,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'actual_qty' => $actual_qty,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'balance' => $this->get_available_qty($d->item_code, $d->s_warehouse),//$balance,
                'sales_order_no' => $d->sales_order_no,
                'issue_as' => $d->issue_as,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
            ];
        }

        return response()->json(['records' => $list]);
    }

    // /material_transfer_for_manufacture
    public function view_material_transfer_for_manufacture(Request $request){
        if(!$request->arr){
            return view('material_transfer_for_manufacture');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer for Manufacture')
            ->where('ste.transfer_as', '!=', 'For Return')
            ->whereIn('s_warehouse', $allowed_warehouses)
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'ste.owner', 'ste.material_request', 'ste.work_order', 'ste.creation', 'ste.so_customer_name')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

        $item_codes = array_unique(array_column($q->toArray(), 'item_code'));
        $item_codes_arr = [];
        foreach ($item_codes as $item_code) {
            array_push($item_codes_arr, $item_code);
        }
        $s_warehouses = array_unique(array_column($q->toArray(), 's_warehouse'));
        $s_warehouses_arr = [];
        foreach ($s_warehouses as $s_warehouse) {
            array_push($s_warehouses_arr, $s_warehouse);
        }

        $work_orders = array_unique(array_column($q->toArray(), 'work_order'));
        $work_orders_arr = [];
        foreach ($work_orders as $work_order) {
            array_push($work_orders_arr, $work_order);
        }

        $work_order_delivery_date = DB::table('tabWork Order')->whereIn('name', $work_orders_arr)->pluck('delivery_date', 'name');

        $item_actual_qty = DB::table('tabBin')->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')
            ->whereIn('tabBin.item_code', $item_codes_arr)->whereIn('tabBin.warehouse', $s_warehouses_arr)
            ->where('tabWarehouse.disabled', 0)
            ->selectRaw('SUM(actual_qty) as actual_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')->get();

        $item_actual_qty = collect($item_actual_qty)->groupBy('item')->toArray();

        $stock_reservation = StockReservation::whereIn('item_code', $item_codes_arr)->whereIn('warehouse', $s_warehouses_arr)
            ->whereIn('status', ['Active', 'Partially Issued'])->selectRaw('SUM(reserve_qty) as total_reserved_qty, SUM(consumed_qty) as total_consumed_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')->get();
        $stock_reservation = collect($stock_reservation)->groupBy('item')->toArray();

        $ste_total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
            ->whereIn('item_code', $item_codes_arr)->whereIn('s_warehouse', $s_warehouses_arr)
            ->selectRaw('SUM(qty) as total_issued, CONCAT(item_code, "-", s_warehouse) as item')
            ->groupBy('item_code', 's_warehouse')->get();
        $ste_total_issued = collect($ste_total_issued)->groupBy('item')->toArray();

        $at_total_issued = DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')->whereIn('at.item_code', $item_codes_arr)
            ->whereIn('psi.item_code', $item_codes_arr)->whereIn('at.source_warehouse', $s_warehouses_arr)
            ->selectRaw('SUM(at.issued_qty) as total_issued, CONCAT(at.item_code, "-", at.source_warehouse) as item')
            ->groupBy('at.item_code', 'at.source_warehouse')
            ->get();

        $at_total_issued = collect($at_total_issued)->groupBy('item')->toArray();

        $part_nos_query = DB::table('tabItem Supplier')->whereIn('parent', $item_codes_arr)
            ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))->groupBy('parent')->pluck('supplier_part_nos', 'parent');

        $parent_warehouses = DB::table('tabWarehouse')->where('disabled', 0)->whereIn('name', $s_warehouses_arr)->pluck('parent_warehouse', 'name');

        $list = [];
        foreach ($q as $d) {
            $reserved_qty = 0;
            if (array_key_exists($d->item_code . '-' . $d->s_warehouse, $stock_reservation)) {
                $reserved_qty = $stock_reservation[$d->item_code . '-' . $d->s_warehouse][0]['total_reserved_qty'];
            }

            $consumed_qty = 0;
            if (array_key_exists($d->item_code . '-' . $d->s_warehouse, $stock_reservation)) {
                $consumed_qty = $stock_reservation[$d->item_code . '-' . $d->s_warehouse][0]['total_consumed_qty'];
            }

            $reserved_qty = $reserved_qty - $consumed_qty;

            $issued_qty = 0;
            if (array_key_exists($d->item_code . '-' . $d->s_warehouse, $ste_total_issued)) {
                $issued_qty = $ste_total_issued[$d->item_code . '-' . $d->s_warehouse][0]->total_issued;
            }

            if (array_key_exists($d->item_code . '-' . $d->s_warehouse, $at_total_issued)) {
                $issued_qty += $at_total_issued[$d->item_code . '-' . $d->s_warehouse][0]->total_issued;
            }

            $actual_qty = 0;
            if (array_key_exists($d->item_code . '-' . $d->s_warehouse, $item_actual_qty)) {
                $actual_qty = $item_actual_qty[$d->item_code . '-' . $d->s_warehouse][0]->actual_qty;
            }

            // $actual_qty = $actual_qty - ($issued_qty + $reserved_qty);
            // $actual_qty = $actual_qty > 0 ? $actual_qty : 0;

            $ref_no = ($d->material_request) ? $d->material_request : $d->sales_order_no;

            $part_nos = Arr::exists($part_nos_query, $d->item_code) ? $part_nos_query[$d->item_code] : 0;

            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $parent_warehouse = (Arr::exists($parent_warehouses, $d->s_warehouse)) ? $parent_warehouses[$d->s_warehouse] : null;

            $delivery_date = (Arr::exists($work_order_delivery_date, $d->work_order)) ? $work_order_delivery_date[$d->work_order] : null;
            $order_status = null;

            $list[] = [
                'customer' => $d->so_customer_name,
                'order_status' => $order_status,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'balance' => $actual_qty,
                'ref_no' => $ref_no,
                'parent_warehouse' => $parent_warehouse,
                'production_order' => $d->work_order,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'delivery_date' => ($delivery_date) ? Carbon::parse($delivery_date)->format('M-d-Y') : null,
                'delivery_status' => ($delivery_date) ? ((Carbon::parse($delivery_date) < Carbon::now()) ? 'late' : null) : null
            ];
        }
        
        return response()->json(['records' => $list]);
    }

    // /material_transfer
    public function view_material_transfer(Request $request){
        if(!$request->arr){
            return view('material_transfer');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q1 = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowed_warehouses)->whereNotin('transfer_as', ['Consignment', 'Sample Item', 'For Return'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'ste.work_order')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC");

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')
            ->whereIn('t_warehouse', $allowed_warehouses)->whereIn('transfer_as', ['For Return', 'Internal Transfer', 'Pull Out Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'ste.work_order')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")->union($q1)->get();

        $item_codes = array_values(array_unique(array_column($q->toArray(), 'item_code')));
        $s_warehouses = array_values(array_unique(array_column($q->toArray(), 's_warehouse')));

        if (count($item_codes) > 0) {
            // get reserved qty per item
            $stock_reservation_qty = DB::table('tabStock Reservation')->whereIn('item_code', $item_codes)->whereIn('warehouse', $s_warehouses)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'reserve_qty')->pluck('reserve_qty', 'id');

            $consumed_qty = DB::table('tabStock Reservation')->whereIn('item_code', $item_codes)->whereIn('warehouse', $s_warehouses)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'consumed_qty')->pluck('consumed_qty', 'id');

            // get actual qty per item
            $item_actual_qty = DB::table('tabBin')->whereIn('item_code', $item_codes)->whereIn('warehouse', $s_warehouses)
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'actual_qty')->pluck('actual_qty', 'id');

            $total_issued_ste = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
                ->whereIn('item_code', $item_codes)->whereIn('s_warehouse', $s_warehouses)
                ->select(DB::raw('CONCAT(item_code, REPLACE(s_warehouse, " ", "")) as id'), DB::raw('sum(qty) as qty'))
                ->groupBy('item_code', 's_warehouse')->pluck('qty', 'id');

            $total_issued_at = DB::table('tabAthena Transactions as at')
                ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
                ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
                ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
                ->where('psi.status', 'Issued')->whereIn('at.item_code', $item_codes)
                ->whereIn('psi.item_code', $item_codes)->whereIn('at.source_warehouse', $s_warehouses)
                ->select(DB::raw('CONCAT(at.item_code, REPLACE(at.source_warehouse, " ", "")) as id'), DB::raw('sum(at.issued_qty) as issued_qty'))
                ->groupBy('at.item_code', 'at.source_warehouse')->pluck('issued_qty', 'id');

            $material_requests = array_unique(array_column($q->toArray(), 'material_request'));
            $sales_orders = array_unique(array_column($q->toArray(), 'sales_order_no'));

            $references = DB::table('tabMaterial Request')->whereIn('name', $material_requests)->pluck('customer', 'name');

            $part_nos_query = DB::table('tabItem Supplier')->whereIn('parent', $item_codes)
                ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))->groupBy('parent')->pluck('supplier_part_nos', 'parent');

            $parent_warehouses = DB::table('tabWarehouse')->where('disabled', 0)->whereIn('name', $s_warehouses)->pluck('parent_warehouse', 'name');
        }

        $list = [];
        foreach ($q as $d) {
            $arr_key = $d->item_code . str_replace(' ', '', $d->s_warehouse);

            $reserved_qty = Arr::exists($stock_reservation_qty, $arr_key) ? $stock_reservation_qty[$arr_key] : 0;
            $reserved_qty += Arr::exists($consumed_qty, $arr_key) ? $consumed_qty[$arr_key] : 0;
            $issued_qty = Arr::exists($total_issued_ste, $arr_key) ? $total_issued_ste[$arr_key] : 0;
            $issued_qty += Arr::exists($total_issued_at, $arr_key) ? $total_issued_at[$arr_key] : 0;
            $actual_qty = Arr::exists($item_actual_qty, $arr_key) ? $item_actual_qty[$arr_key] : 0;

            $available_qty = ($actual_qty - $issued_qty);
            $available_qty = ($available_qty - $reserved_qty);
            $available_qty = ($available_qty < 0) ? 0 : $available_qty;

            if($d->material_request){
                $customer = Arr::exists($references, $d->material_request) ? $references[$d->material_request] : null;
            }else{
                $customer = Arr::exists($references, $d->sales_order_no) ? $references[$d->sales_order_no] : null;
            }

            $ref_no = ($d->material_request) ? $d->material_request : $d->sales_order_no;

            $part_nos = Arr::exists($part_nos_query, $d->item_code) ? $part_nos_query[$d->item_code] : 0;

            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            if (in_array($d->transfer_as, ['For Return', 'Pull Out Item'])) {
                $parent_warehouse = (Arr::exists($parent_warehouses, $d->t_warehouse)) ? $parent_warehouses[$d->t_warehouse] : null; 
            } else {
                $parent_warehouse = (Arr::exists($parent_warehouses, $d->s_warehouse)) ? $parent_warehouses[$d->s_warehouse] : null; 
            }

            $list[] = [
                'customer' => $customer,
                'work_order' => $d->work_order,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'transfer_as' => $d->transfer_as,
                'available_qty' => $available_qty,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'ref_no' => $ref_no,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'transaction_date' => Carbon::parse($d->creation),
            ];
        }

        return response()->json(['records' => $list]);
    }

    public function get_mr_sales_return(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        // $dr_sales_return = DeliveryNote::with('items')->whereHas('items', function ($item) use ($allowed_warehouses){
        //         $item->whereIn('warehouse', $allowed_warehouses)->select('parent', 'name', 'item_code', 'description', 'qty', 'item_status');
        //     })->where('docstatus', 0)->where('is_return', 1)
        //     ->select('name', 'reference', 'customer', 'owner', 'creation')
        //     ->orderByRaw("FIELD(dni.item_status, 'For Checking', 'For Return', 'Returned') ASC")->get();

        // Remove
        
        $dr_sales_return = DB::table('tabDelivery Note as dn')->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
            ->where('dn.docstatus', 0)->where('is_return', 1)->whereIn('dni.warehouse', $allowed_warehouses)
            ->select('dni.name as c_name', 'dn.name', 'dni.warehouse', 'dni.item_code', 'dni.description', 'dni.qty', 'dn.reference', 'dni.item_status', 'dn.customer', 'dn.owner', 'dn.creation')
            ->orderByRaw("FIELD(dni.item_status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $mr_sales_return = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('ste.purpose', 'Material Receipt')
            ->where('ste.receive_as', 'Sales Return')->whereIn('sted.t_warehouse', $allowed_warehouses)
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner', 'ste.creation')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $consignment_return = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('ste.purpose', 'Material Transfer')
            ->where('ste.transfer_as', 'For Return')->whereIn('sted.t_warehouse', $allowed_warehouses)->where('ste.naming_series', 'STEC-')
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner', 'ste.creation', 'sted.consignment_received_by', 'sted.consignment_date_received')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $list = [];
        foreach ($mr_sales_return as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->t_warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format($d->transfer_qty),
                'sales_order_no' => $d->sales_order_no,
                'status' => $d->status,
                'so_customer_name' => $d->so_customer_name,
                'parent_warehouse' => $this->get_warehouse_parent($d->t_warehouse),
                'reference_doc' => 'stock_entry',
                'transaction_date' => $d->creation
            ];
        }

        foreach ($dr_sales_return as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->c_name,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format(abs($d->qty)),
                'sales_order_no' => $d->reference,
                'status' => $d->item_status,
                'so_customer_name' => $d->customer,
                'parent_warehouse' => $this->get_warehouse_parent($d->warehouse),
                'reference_doc' => 'delivery_note',
                'transaction_date' => $d->creation
            ];
        }

        foreach ($consignment_return as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->t_warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format($d->transfer_qty),
                'sales_order_no' => $d->sales_order_no,
                'status' => $d->status,
                'so_customer_name' => $d->s_warehouse,
                'parent_warehouse' => $this->get_warehouse_parent($d->t_warehouse),
                'reference_doc' => 'stock_entry',
                'transaction_date' => $d->creation
            ];
        }

        return response()->json(['mr_return' => $list]);
    }
    
    public function feedback_details($id){  
        // $user = Auth::user()->frappe_userid;
        // $allowed_warehouses = $this->user_allowed_warehouse($user);
        $try = DB::connection('mysql_mes')->table('production_order AS po')
            ->where('po.production_order', $id)->get();

        if(count($try) == 1){
            $data = DB::connection('mysql_mes')->table('production_order AS po')
                ->where('po.production_order', $id)
                ->select('po.*')->first();

            $img = DB::table('tabItem Images')->where('parent', $data->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
            $img = $img ? "/img/$img" : '/icon/no_img.png';
            $img = $this->base64_image($img);

            $q = [
                'production_order' => $data->production_order,
                'fg_warehouse' => $data->fg_warehouse,
                'src_warehouse' => $data->wip_warehouse,
                'sales_order' => $data->sales_order,
                'status' => $data->status,
                'material_request' => $data->material_request,
                'img' => $img,
                'customer' => $data->customer,
                'item_code' => $data->item_code,
                'description' => $data->description,
                'qty_to_receive' => $data->produced_qty - $data->feedback_qty,
                'feedback_qty' => $data->feedback_qty,
                'stock_uom' => $data->stock_uom,
            ];
        }else{
            $se = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->where('se.work_order', $id)->first();

            $q[] = [
                'production_order' => $se->work_order,
                'fg_warehouse' => $se->to_warehouse,
                'sales_order_no' => $se->sales_order_no,
                'status' => $se->item_status,
                'material_request' => $se->material_request,
                'customer' => $se->customer,
                'item_code' => $se->item_code,
                'description' => $se->description,
                'qty_to_receive' => $se->actual_qty,
                'feedback_qty' => $se->transfer_qty,
                'stock_uom' => $se->stock_uom,
            ];
        }

        // return $q;
        return view('feedback_details_modal', compact('q'));
    }

    public function feedback_submit(Request $request){
        DB::beginTransaction();
        try{
            $now = Carbon::now();

            $erp_update = [];
            
            $erp_update = [
                'produced_qty' => $request->r_qty + $request->ofeedback_qty,
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => $request->r_qty == $request->f_qty ? "Completed" : "In Process"
            ];


            $erp_prod = DB::table('tabWork Order')->where('name', $request->prod_order)->where('docstatus', 1)->update($erp_update);

            $mes_update = [];
            
            $mes_update = [
                'feedback_qty' => $request->r_qty + $request->ofeedback_qty,
                'last_modified_by' => Auth::user()->wh_user,
                'last_modified_at' => $now->toDateTimeString()
            ];

            // return $mes_update;
            $mes_prod = DB::connection('mysql_mes')->table('production_order as po')->where('po.production_order', $request->prod_order)->update($mes_update);

            $feedback_log = [];

            $feedback_log = [
                'production_order' => $request->prod_order,
                'ste_no' => "",
                'item_code' => $request->itemCode,
                'item_name' => $request->itemDesc,
                'feedbacked_qty' => $request->r_qty,
                'from_warehouse' => $request->src_wh,
                'to_warehouse' => $request->to_wh,
                'transaction_date' => $now->format('Y-m-d'),
                'transaction_time' => $now->format('H:i:s'),
                'status' => "",
                'created_at' => $now->toDateTimeString(),
                'created_by' => Auth::user()->wh_user
            ];

            // return $feedback_log;

            $mes_log = DB::connection('mysql_mes')->table('feedbacked_logs')->insert($feedback_log);

            DB::commit();
            return redirect()->back();
        }catch(\Exception $e){
            DB::rollback();
        }
    }

    // /get_ste_details/{id}
    public function get_ste_details($id, Request $request){
        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('sted.name', $id)
            ->select('ste.work_order', 'ste.transfer_as', 'ste.purpose', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.actual_qty', 'sted.validate_item_code', 'sted.owner', 'sted.status', 'sted.remarks', 'sted.stock_uom', 'ste.sales_order_no', 'ste.material_request', 'ste.issue_as', 'ste.docstatus')
            ->first();

        if(!$q){
            throw new \ErrorException('Stock Entry not found.');
        }

        $ref_no = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;

        $owner = ucwords(str_replace('.', ' ', explode('@', $q->owner)[0]));

        $img = DB::table('tabItem Images')->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64_image($img);

        $s_warehouse = $q->purpose == 'Manufacture' ? 'Goods In Transit - FI' : $q->s_warehouse;

        $available_qty = $this->get_available_qty($q->item_code, $s_warehouse);
    
        $stock_reservation_details = [];
        $so_details = DB::table('tabSales Order')->where('name', $ref_no)->first();

        $mr_details = DB::table('tabMaterial Request')->where('name', $ref_no)->first();

        $sales_person = ($so_details) ? $so_details->sales_person : null;
        $sales_person = ($mr_details) ? $mr_details->sales_person : $sales_person;
        $project = ($so_details) ? $so_details->project : null;
        $project = ($mr_details) ? $mr_details->project : $project;
        $consignment_warehouse = null;
        if($q->transfer_as == 'Consignment') {
            $sales_person = null;
            $project = null;
            $consignment_warehouse = $q->t_warehouse;
        }

        $stock_reservation_details = $this->get_stock_reservation($q->item_code, $q->s_warehouse, $sales_person, $project, $consignment_warehouse);

        $data = [
            'name' => $q->name,
            'purpose' => $q->purpose,
            's_warehouse' => $q->s_warehouse,
            't_warehouse' => $q->t_warehouse,
            'available_qty' => $available_qty,
            'validate_item_code' => $q->validate_item_code,
            'img' => $img,
            'item_code' => $q->item_code,
            'description' => $q->description,
            'ref_no' => ($ref_no) ? $ref_no : '-',
            'stock_uom' => $q->stock_uom,
            'qty' => ($q->qty * 1),
            'transfer_as' => $q->transfer_as,
            'owner' => $owner,
            'status' => $q->status,
            'stock_reservation' => $stock_reservation_details,
            'docstatus' => $q->docstatus,
            'parent' => $q->parent,
            'reference' => 'Stock Entry'
        ];

        if($q->purpose == 'Manufacture'){
            return view('goods_in_transit_modal_content', compact('data'));
        }

        if($q->purpose == 'Material Transfer for Manufacture') {
            return view('production_withdrawals_modal_content', compact('data'));
        }

        if($q->purpose == 'Material Issue') {
            if($q->issue_as == 'Customer Replacement') {
                return view('order_replacement_modal_content', compact('data'));    
            } else {
                return view('material_issue_modal_content', compact('data'));
            }
        }

        if(in_array($q->transfer_as, ['Consignment', 'Sample Item'])) {
            $is_stock_entry = true;
            return view('deliveries_modal_content', compact('data', 'is_stock_entry'));
        }

        if($q->purpose == 'Material Receipt'){
            $is_stock_entry = true;
            return view('return_modal_content', compact('data', 'is_stock_entry'));
        }

        if($q->purpose == 'Material Transfer'){
            return view('internal_transfer_modal_content', compact('data'));
        }

        return response()->json($data);
    }
    public function get_stock_reservation($item_code, $warehouse, $sales_person, $project, $consignment_warehouse, $order_type = null, $po_no = null){
        $query = [];
        if($sales_person) {
            $query = DB::table('tabStock Reservation')
                ->where('warehouse', $warehouse)->where('item_code', $item_code)
                ->where('sales_person', trim($sales_person))
                ->where('project', $project)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }

        if($consignment_warehouse) {
             $query = DB::table('tabStock Reservation')
                ->where('warehouse', $warehouse)->where('item_code', $item_code)
                ->where('consignment_warehouse', $consignment_warehouse)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }

        if ($order_type == 'Shopping Cart') {
            $query = DB::table('tabStock Reservation')
                ->where('warehouse', $warehouse)->where('item_code', $item_code)
                ->where('reference_no', $po_no)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }
       
        return ($query) ? $query : [];
    }

    public function get_ps_details(Request $request, $id){
        if($request->type == 'packed_item'){
            $q = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabPacked Item as pi', 'pi.name', 'psi.pi_detail')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = pi.parent_item'))
                ->where('ps.docstatus', '<', 2)
                ->where('ps.item_status', 'For Checking')
                ->where('psi.name', $id)
                ->where('dri.docstatus', 0)
                ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'dri.item_code', 'psi.description', 'psi.qty', 'psi.name as id', 'dri.warehouse', 'psi.status', 'dri.stock_uom', 'psi.qty', 'dri.name as dri_name', 'dr.reference as sales_order', 'dri.uom', 'ps.docstatus')
                ->first();
        }else{
            $q = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))
                ->where('ps.docstatus', '<', 2)
                ->where('ps.item_status', 'For Checking')
                ->where('psi.name', $id)
                ->where('dri.docstatus', 0)
                ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.qty', 'psi.name as id', 'dri.warehouse', 'psi.status', 'dri.stock_uom', 'psi.qty', 'dri.name as dri_name', 'dr.reference as sales_order', 'dri.uom', 'ps.docstatus')
                ->first();
        }
        
        if(!$q){
            return response()->json([
                'error' => 1,
                'modal_title' => 'Not Found', 
                'modal_message' => 'Item not found. Please reload the page.'
            ]);
        }

        $item_details = DB::table('tabItem')->where('name', $q->item_code)->first();
        
        $img = DB::table('tabItem Images')->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64_image($img);
        
        $is_bundle = false;
        if(!$item_details->is_stock_item){
            $is_bundle = DB::table('tabProduct Bundle')->where('name', $q->item_code)->exists();
        }
        $stock_reservation_details = [];
        $product_bundle_items = [];
        if($is_bundle){
            $query = DB::table('tabPacked Item')->where('parent_detail_docname', $q->dri_name)->get();
            foreach ($query as $row) {
                $available_qty_row = $this->get_available_qty($row->item_code, $row->warehouse);

                $product_bundle_items[] = [
                    'item_code' => $row->item_code,
                    'description' => $row->description,
                    'uom' => $row->uom,
                    'qty' => ($row->qty * 1),
                    'available_qty' => $available_qty_row,
                    'warehouse' => $row->warehouse
                ];

                $stock_reservation_details = [];
                $so_details = DB::table('tabSales Order')->where('name', $q->sales_order)->first();
                if($so_details) {
                    $stock_reservation_details = $this->get_stock_reservation($row->item_code, $q->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
                }
            }
        }

        if(!$stock_reservation_details) {
            $stock_reservation_details = [];
            $so_details = DB::table('tabSales Order')->where('name', $q->sales_order)->first();
            if($so_details) {
                $stock_reservation_details = $this->get_stock_reservation($q->item_code, $q->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
            }
        }

        $available_qty = $this->get_available_qty($q->item_code, $q->warehouse);

        $uom_conversion = [];
        if ($q->uom != $q->stock_uom) {
            $uom_conversion = DB::table('tabUOM Conversion Detail')->where('parent', $q->item_code)
                ->whereIn('uom', [$q->uom, $q->stock_uom])->orderBy('idx', 'asc')->get();
        }

        $data = [
            'id' => $q->id,
            'type' => $request->type,
	        'barcode' => $q->barcode,
            'item_image' => $img,//$item_details->item_image_path,
            'delivery_note' => $q->delivery_note,
            'description' => $q->description,
            'item_code' => $q->item_code,
            'name' => $q->name,
            'sales_order' => $q->sales_order,
            'status' => $q->status,
            'stock_uom' => $q->stock_uom,
            'uom' => $q->uom,
            'qty' => ($q->qty * 1),
            'warehouse' => $q->warehouse,
            'available_qty' => $available_qty,
            'is_bundle' => $is_bundle,
            'product_bundle_items' => $product_bundle_items,
            'dri_name' => $q->dri_name,
            'stock_reservation' => $stock_reservation_details,
            'uom_conversion' => $uom_conversion,
            'reference' => 'Picking Slip',
            'docstatus' => $q->docstatus
        ];

        $is_stock_entry = false;
        return view('deliveries_modal_content', compact('data', 'is_stock_entry'));
    }

    public function submit_sales_return(Request $request){
        try{
            $child_id = $request->child_tbl_id;
            $stock_entry = StockEntry::with('items')->whereHas('items', function ($item) use ($child_id){
                return $item->where('name', $child_id);
            })->first();

            $now = Carbon::now();

            if(!$stock_entry){
                throw new Exception('Record not found');
            }

            $stock_entry->qty = (float) $stock_entry->qty;

            $stock_entry_item = collect($stock_entry->items)->where('name', $child_id)->first();
            $item_code = $stock_entry_item->item_code;
            $itemDetails = Item::find($item_code);

            if(in_array($stock_entry->item_status, ['Issued', 'Returned'])){
                throw new Exception("Item already $stock_entry->item_status");
            }

            if($stock_entry->docstatus == 1){
                throw new Exception('Item already issued.');
            }

            if(!$itemDetails){
                throw new Exception("Item <b>$item_code</b> not found.");
            }

            if($itemDetails->is_stock_item == 0){
                throw new Exception("Item <b>$item_code</b> is not a stock item.");
            }

            if($request->barcode != $item_code){
                throw new Exception("Invalid barcode for <b>$item_code</b>.");
            }

            if($request->qty <= 0){
                throw new Exception("Qty cannot be less than or equal to 0.");
            }

            foreach ($stock_entry->items as $item) { // Update only the specific child
                if ($item->name === $child_id) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Returned';
                    $item->transfer_qty = $request->qty;
                    $item->qty = $request->qty;
                    $item->issued_qty = $request->qty;
                    $item->validate_item_code = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    break;
                }
            }

            $pending_items = collect($stock_entry->items)->where('name', $child_id)->where('status', 'For Checking')->count();
            if($pending_items <= 0){
                $stock_entry->item_status = 'Returned';
            }

            $response = $this->erpOperation('put', 'Stock Entry', $stock_entry->name, collect($stock_entry)->toArray());
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating Stock Entry';
                throw new Exception($err);
            }

            return response()->json(['status' => 1, 'message' => "Item <b>$item_code</b> has been returned"]);
        }catch(Exception $e){
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function submit_internal_transfer(Request $request){
        try {
            $child_id = $request->child_tbl_id;
            $stock_entry = StockEntry::with('items')->whereHas('items', function ($item) use ($child_id){
                return $item->where('name', $child_id);
            })->first();

            $now = Carbon::now();

            if(!$stock_entry){
                throw new Exception('Record not found');
            }

            $stock_entry->qty = (float) $stock_entry->qty;

            $stock_entry_item = collect($stock_entry->items)->where('name', $child_id)->first();
            $item_code = $stock_entry_item->item_code;
            $itemDetails = Item::find($item_code);

            if(in_array($stock_entry->item_status, ['Issued', 'Returned'])){
                throw new Exception("Item already $stock_entry->item_status");
            }

            if($stock_entry->docstatus == 1){
                throw new Exception('Item already issued.');
            }

            if(!$itemDetails){
                throw new Exception("Item <b>$item_code</b> not found.");
            }

            if($itemDetails->is_stock_item == 0){
                throw new Exception("Item <b>$item_code</b> is not a stock item.");
            }

            if($request->barcode != $item_code){
                throw new Exception("Invalid barcode for <b>$itemDetails->item_code</b>.");
            }

            if($request->qty <= 0){
                throw new Exception("Qty cannot be less than or equal to 0.");
            }

            if($stock_entry->material_request){
                $mreq_issued_qty = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                    ->where('sted.s_warehouse', $stock_entry_item->s_warehouse)->where('sted.t_warehouse', $stock_entry_item->t_warehouse)
                    ->where('ste.material_request', $stock_entry->material_request)->where('ste.docstatus', 1)
                    ->where('purpose', 'Material Transfer')->where('sted.item_code', $item_code)
                    ->where('sted.status', 'Issued')->where('ste.docstatus', '<', 2)->sum('issued_qty');

                $mreq_qry = MaterialRequest::with(['items' => function ($item) use ($item_code){
                        $item->where('item_code', $item_code);
                    }])->whereHas('items', function ($item) use ($item_code){
                        $item->where('item_code', $item_code);
                    })->find($stock_entry->material_request);

                if(!$mreq_qry){
                    throw new Exception("Item $item_code not found in $stock_entry->material_request<br/>Please contact MREQ owner: $stock_entry->requested_by");
                }

                $mreq_item = collect($mreq_qry->items)->first();
                $mreq_requested_qty = $mreq_item->qty;
                if($mreq_issued_qty >= $mreq_requested_qty){
                    $mreq_issued_qty = number_format($mreq_issued_qty);
                    $mreq_requested_qty = number_format($mreq_requested_qty);
                    throw new Exception("Issued qty cannot be greater than requested qty<br/>Total Issued Qty: $mreq_issued_qty<br/>Requested Qty: $mreq_requested_qty<br/>Please contact MREQ owner: '.$stock_entry->requested_by");
                }

                if($request->qty > ($mreq_requested_qty - $mreq_issued_qty)){
                    $diff = $mreq_requested_qty - $mreq_issued_qty;
                    throw new Exception("Qty cannot be greater than $diff.");
                }
            }

            $unissued_qty = $stock_entry_item->qty - $request->qty;

            if($unissued_qty > 0){
                $unissued_stock_entry = [
                    'title' => 'Material Transfer',
                    'naming_series' => 'STE-',
                    'company' => 'FUMACO Inc.',
                    'project' => $stock_entry->project,
                    'work_order' => $stock_entry->work_order,
                    'purpose' => 'Material Transfer',
                    'stock_entry_type' => 'Material Transfer',
                    'material_request' => $stock_entry->material_request,
                    'item_status' => 'For Checking',
                    'sales_order_no' => $stock_entry->sales_order_no,
                    'transfer_as' => 'For Return',
                    'item_classification' => $stock_entry->item_classification,
                    'so_customer_name' => $stock_entry->so_customer_name,
                    'order_type' => $stock_entry->order_type,
                    'items' => [[
                        't_warehouse' => $stock_entry_item->t_warehouse,
                        'transfer_qty' => $unissued_qty,
                        'expense_account' => 'Cost of Goods Sold - FI',
                        'cost_center' => 'Main - FI',
                        's_warehouse' => $stock_entry_item->s_warehouse,
                        'item_code' => $item_code,
                        'qty' => $unissued_qty
                    ]]
                ];

                $unissued_response = $this->erpOperation('post', 'Stock Entry', null, $unissued_stock_entry);
                if(!isset($unissued_response['data'])){
                    $err = isset($unissued_response['exception']) ? $unissued_response['exception'] : 'An error occured while submitting Stock entry for unissued items';
                    throw new Exception($err);
                }
            }

            foreach ($stock_entry->items as $item) { // Update only the specific child
                if ($item->name === $child_id) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Issued';
                    $item->transfer_qty = $request->qty;
                    $item->qty = $request->qty;
                    $item->issued_qty = $request->qty;
                    $item->validate_item_code = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    break;
                }
            }

            $checker = collect($stock_entry->items)->where('name', '!=', $child_id)->where('status', 'For Checking')->count();
            if(!$checker){
                $stock_entry->item_status = 'Issued';
                $stock_entry->docstatus = 1;
            }

            if($stock_entry->naming_series == 'STEC'){ // for consignment
                foreach ($stock_entry->items as $item) { // Update only the specific child
                    if ($item->name === $child_id) {
                        $item->consignment_status = 'Received';
                        $item->consignment_date_received = Carbon::now()->toDateTimeString();
                        $item->consignment_received_by = Auth::user()->wh_user;
                        break;
                    }
                }
                
                $consignment_checker = collect($stock_entry->items)->where('name', '!=', $child_id)->where('consignment_status', 'To Receive')->count();
                if(!$consignment_checker){
                    $stock_entry->consignment_status = 'Received';
                    $stock_entry->consignment_date_received = Carbon::now()->toDateTimeString();
                    $stock_entry->consignment_received_by = Auth::user()->wh_user;
                }
            }

            $response = $this->erpOperation('put', 'Stock Entry', $stock_entry->name, collect($stock_entry)->toArray());
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating Stock Entry';
                throw new Exception($err);
            }

            return response()->json(['status' => 1, 'message' => "Item <b>$item_code</b> has been checked out."]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
        }
    }

    // /submit_transaction
    public function submit_transaction(Request $request){
        DB::beginTransaction();
        try {
            $steDetails = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')->where('sed.name', $request->child_tbl_id)
                ->select('se.name as parent_se', 'se.*', 'se.owner as requested_by' , 'sed.*', 'sed.status as per_item_status', 'se.docstatus as se_status', 'se.material_request as mreq')->first();

            $now = Carbon::now();

            if(!$steDetails){
                return response()->json(['status' => 0, 'message' => 'Record not found.'], 500);
            }

            if(in_array($steDetails->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $steDetails->per_item_status . '.'], 500);
            }

            if($steDetails->se_status == 1){
                return response()->json(['status' => 0, 'message' => 'Item already issued.'], 500);
            }

            $itemDetails = DB::table('tabItem')->where('name', $steDetails->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $steDetails->item_code . '</b> not found.'], 500);
            }

            if($itemDetails->is_stock_item == 0){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $steDetails->item_code . '</b> is not a stock item.'], 500);
            }

            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => 'Invalid barcode for <b>' . $itemDetails->item_code . '</b>.'], 500);
            }

            if($request->qty <= 0){
                return response()->json(['status' => 0, 'message' => 'Qty cannot be less than or equal to 0.'], 500);
            }

            if($steDetails->purpose != 'Material Transfer for Manufacture' && $request->qty > $steDetails->qty){
                return response()->json(['status' => 0, 'message' => 'Qty cannot be greater than ' . ($steDetails->qty * 1) .'.'], 500);
            }

            $available_qty = $this->get_available_qty($steDetails->item_code, $steDetails->s_warehouse);
            if($steDetails->purpose != 'Material Receipt' && $request->deduct_reserve == 0){
                if($request->qty > $available_qty){
                    return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><
                    br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . $request->qty . '</b>.'], 500);
                }
            }

            $sales_person = DB::table('tabSales Order')->where('name', $steDetails->sales_order_no)->pluck('sales_person')->first();

            $reserved_qty = DB::table('tabStock Reservation')->where('item_code', $steDetails->item_code)->where('warehouse', $steDetails->s_warehouse)->where('sales_person', $sales_person)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

            $consumed_qty = DB::table('tabStock Reservation')->where('item_code', $steDetails->item_code)->where('warehouse', $steDetails->s_warehouse)->where('sales_person', $sales_person)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');
            
            $remaining_reserved = $reserved_qty - $consumed_qty;
            $remaining_reserved = $remaining_reserved > 0 ? $remaining_reserved : 0;

            if($request->qty > $remaining_reserved && $request->deduct_reserve == 1){ // For deduct from reserved, if requested qty is more than the reserved qty
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><br><br>Reserved qty is <b>' . $remaining_reserved . '</b>, you need <b>' . $request->qty . '</b>.'], 500);
            }

            if ($steDetails->purpose == 'Material Transfer' && $steDetails->material_request){
                $mreq_issued_qty = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                    ->where('sted.s_warehouse', $steDetails->s_warehouse)->where('sted.t_warehouse', $steDetails->t_warehouse)
                    ->where('ste.material_request', $steDetails->mreq)->where('ste.docstatus', 1)
                    ->where('purpose', 'Material Transfer')->where('sted.item_code', $steDetails->item_code)
                    ->where('sted.status', 'Issued')->where('ste.docstatus', '<', 2)->sum('issued_qty');

                $mreq_qry = DB::table('tabMaterial Request as mr')
                    ->join('tabMaterial Request Item as mri', 'mr.name', 'mri.parent')
                    ->where('mr.name', $steDetails->mreq)->where('mri.item_code', $steDetails->item_code)
                    ->select('mri.item_code', 'mri.qty')->first();

                if(!$mreq_qry){
                    return response()->json(['status' => 0, 'message' => 'Item '.$steDetails->item_code.' not found in '.$steDetails->material_request.'<br/>Please contact MREQ owner: '.$steDetails->requested_by], 500);
                }

                $mreq_requested_qty = $mreq_qry->qty;

                if($mreq_issued_qty >= $mreq_requested_qty){
                    return response()->json(['status' => 0, 'message' => 'Issued qty cannot be greater than requested qty<br/>Total Issued Qty: '.number_format($mreq_issued_qty).'<br/>Requested Qty: '.number_format($mreq_requested_qty).'<br/>Please contact MREQ owner: '.$steDetails->requested_by], 500);
                }

                if($request->qty > ($mreq_requested_qty - $mreq_issued_qty)){
                    return response()->json(['status' => 0, 'message' => 'Qty cannot be greater than '.($mreq_requested_qty - $mreq_issued_qty).'.'], 500);
                }
            }

            $status = $steDetails->status;
            if($steDetails->purpose == 'Material Receipt' && $steDetails->receive_as == 'Sales Return') {
                $status = 'Returned';
            }else {
                $status = 'Issued';
            }

            $values = [
                'session_user' => Auth::user()->wh_user,
                'status' => $status, 
                'transfer_qty' => $request->qty, 
                'qty' => $request->qty, 
                'issued_qty' => $request->qty, 
                'validate_item_code' => $request->barcode, 
                'date_modified' => Carbon::now()->toDateTimeString()
            ];

            DB::table('tabStock Entry Detail')->where('name', $request->child_tbl_id)->update($values);
            
            $this->insert_transaction_log('Stock Entry', $request->child_tbl_id);

            $status_result = $this->update_pending_ste_item_status();

            // get expected qty BEFORE submission of stock entry (for double checking of stocks after transaction)
            $expected_qty_in_source = null;
            $expected_qty_in_target = null;
            if($steDetails->s_warehouse != $steDetails->t_warehouse){
                if($steDetails->s_warehouse){
                    $current_qty_in_source = $this->get_actual_qty($steDetails->item_code, $steDetails->s_warehouse);
                    $expected_qty_in_source = $current_qty_in_source - $request->qty;
                }

                $current_qty_in_target = $this->get_actual_qty($steDetails->item_code, $steDetails->t_warehouse);
                $expected_qty_in_target = $current_qty_in_target + $request->qty;
            }

            if ($steDetails->purpose == 'Material Transfer for Manufacture') {
                $cancelled_production_order = DB::table('tabWork Order')
                    ->where('name', $steDetails->work_order)->where('docstatus', 2)->first();

                if($cancelled_production_order){
                    return response()->json(['status' => 0, 'message' => 'Production Order ' . $cancelled_production_order->name . ' was cancelled. Please reload the page.'], 500);
                }

                $this->submit_stock_entry($steDetails->parent_se);
                $this->generate_stock_entry($steDetails->work_order);
            }

            if ($steDetails->purpose == 'Material Transfer') {
                if($steDetails->transfer_as == 'For Return' && $status_result == 'Returned'){
                    $this->submit_stock_entry($steDetails->parent_se);

                    if ($steDetails->work_order) {
                        $prodDetails = DB::table('tabWork Order Item')->where('parent', $steDetails->work_order)->where('item_code', $steDetails->item_code)->first();
                        if ($prodDetails) {
                            // check item alternative 
                            if ($prodDetails->item_alternative_for) {
                                // get original item code
                                $origProdReqItem = DB::connection('mysql')->table('tabWork Order Item')
                                    ->where('parent', $steDetails->work_order)->where('item_code', $prodDetails->item_alternative_for)->first();
                                
                                if ($origProdReqItem) {
                                    // update original item code required qty
                                    DB::connection('mysql')->table('tabWork Order Item')->where('name', $origProdReqItem->name)
                                        ->update(['required_qty' => $origProdReqItem->required_qty + $steDetails->qty]);
                                    
                                    $remaining_required_alternative = ($prodDetails->required_qty - $steDetails->qty);
                                    if ($remaining_required_alternative <= 0) {
                                        // delete item alternative from production order required items
                                        DB::connection('mysql')->table('tabWork Order Item')->where('name', $prodDetails->name)->delete();
                                    } else {
                                        // update required qty of alternative item
                                        DB::connection('mysql')->table('tabWork Order Item')->where('name', $prodDetails->name)
                                            ->update(['required_qty' => $remaining_required_alternative]);
                                    }
                                }
                            }
                        }
                    }
                }

                $unissued_qty = $steDetails->qty - $request->qty;

                if($unissued_qty > 0){ // For partial returns create new STE for the remaining qty
                    $actual_qty = DB::table('tabBin')->where('item_code', $steDetails->item_code)->where('warehouse', $steDetails->s_warehouse)->pluck('actual_qty')->first();
                    $latest_ste = DB::table('tabStock Entry')->where('name', 'like', '%step%')->max('name');
                    $latest_ste_exploded = explode("-", $latest_ste);
                    $new_id = (($latest_ste) ? $latest_ste_exploded[1] : 0) + 1;
                    $new_id = str_pad($new_id, 6, '0', STR_PAD_LEFT);
                    $new_id = 'STEP-'.$new_id;

                    $stock_entry_detail = [
                        'name' =>  uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'parent' => $new_id,
                        'parentfield' => 'items',
                        'parenttype' => 'Stock Entry',
                        'idx' => 1,
                        't_warehouse' => $steDetails->t_warehouse,
                        'transfer_qty' => $unissued_qty,
                        'serial_no' => null,
                        'expense_account' => 'Cost of Goods Sold - FI',
                        'cost_center' => 'Main - FI',
                        'actual_qty' => $actual_qty,
                        's_warehouse' => $steDetails->s_warehouse,
                        'item_name' => $steDetails->item_name,
                        'image' => null,
                        'additional_cost' => 0,
                        'stock_uom' => $steDetails->stock_uom,
                        'basic_amount' => $steDetails->basic_rate * $unissued_qty,
                        'sample_quantity' => 0,
                        'uom' => $steDetails->uom,
                        'basic_rate' => $steDetails->basic_rate,
                        'description' => $steDetails->description,
                        'barcode' => null,
                        'conversion_factor' => $steDetails->conversion_factor,
                        'item_code' => $steDetails->item_code,
                        'retain_sample' => 0,
                        'qty' => $unissued_qty,
                        'bom_no' => null,
                        'allow_zero_valuation_rate' => 0,
                        'material_request_item' => null,
                        'amount' => $steDetails->basic_rate * $unissued_qty,
                        'batch_no' => null,
                        'valuation_rate' => $steDetails->valuation_rate,
                        'material_request' => null,
                        't_warehouse_personnel' => null,
                        's_warehouse_personnel' => null,
                        'target_warehouse_location' => null,
                        'source_warehouse_location' => null,
                        'status' => 'For Checking',
                        'date_modified' => null,
                        'session_user' => null,
                        'remarks' => null,
                        'return_reference' => $new_id
                    ];

                    $stock_entry_data = [
                        'name' => $new_id,
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 0,
                        'idx' => 0,
                        'use_multi_level_bom' => 0,
                        'delivery_note_no' => null,
                        'naming_series' => 'STE-',
                        'fg_completed_qty' => 0,
                        'letter_head' => null,
                        '_liked_by' => null,
                        'purchase_receipt_no' => null,
                        'posting_time' => $now->format('H:i:s'),
                        'to_warehouse' => null,
                        'title' => 'Material Transfer',
                        '_comments' => null,
                        'from_warehouse' => null,
                        'set_posting_time' => 0,
                        'purchase_order' => null,
                        'from_bom' => 0,
                        'supplier_address' => null,
                        'supplier' => null,
                        'source_address_display' => null,
                        'address_display' => null,
                        'source_warehouse_address' => null,
                        'value_difference' => 0,
                        'credit_note' => null,
                        'sales_invoice_no' => null,
                        'company' => 'FUMACO Inc.',
                        'target_warehouse_address' => null,
                        'total_outgoing_value' => collect($stock_entry_detail)->sum('basic_amount'),
                        'supplier_name' => null,
                        'remarks' => null,
                        '_user_tags' => null,
                        'total_additional_costs' => 0,
                        'bom_no' => null,
                        'amended_from' => null,
                        'total_amount' => collect($stock_entry_detail)->sum('basic_amount'),
                        'total_incoming_value' => collect($stock_entry_detail)->sum('basic_amount'),
                        'project' => $steDetails->project,
                        '_assign' => null,
                        'select_print_heading' => null,
                        'posting_date' => $now->format('Y-m-d'),
                        'target_address_display' => null,
                        'work_order' => $steDetails->work_order,
                        'purpose' => 'Material Transfer',
                        'stock_entry_type' => 'Material Transfer',
                        'shipping_address_contact_person' => null,
                        'customer_1' => null,
                        'material_request' => $steDetails->material_request,
                        'reference_no' => null,
                        'delivery_date' => null,
                        'delivery_address' => null,
                        'city' => null,
                        'address_line_2' => null,
                        'address_line_1' => null,
                        'item_status' => 'For Checking',
                        'sales_order_no' => $steDetails->sales_order_no,
                        'transfer_as' => 'For Return',
                        'workflow_state' => null,
                        'item_classification' => $steDetails->item_classification,
                        'bom_repack' => null,
                        'qty_repack' => 0,
                        'issue_as' => null,
                        'receive_as' => null,
                        'so_customer_name' => $steDetails->so_customer_name,
                        'order_type' => $steDetails->order_type,
                    ];

                    DB::table('tabStock Entry Detail')->insert($stock_entry_detail);
                    DB::table('tabStock Entry')->insert($stock_entry_data);
                }

                // Returns from Consignment Function
                if($steDetails->transfer_as == 'For Return' && $steDetails->naming_series == 'STEC-'){
                    $get_qty = DB::table('tabBin')->whereIn('warehouse', [$steDetails->s_warehouse, $steDetails->t_warehouse])->where('item_code', $steDetails->item_code)->select('warehouse', 'item_code', 'actual_qty', 'consigned_qty')->get();
                    $wh_qty = collect($get_qty)->groupBy('warehouse');

                    // source warehouse
                    $source_actual_qty = $this->get_actual_qty($steDetails->item_code, $steDetails->s_warehouse);
                    $source_consigned = isset($wh_qty[$steDetails->s_warehouse]) ? $wh_qty[$steDetails->s_warehouse][0]->consigned_qty : 0;

                    $source_new_qty = $source_actual_qty - $steDetails->transfer_qty;
                    $source_new_consigned = $source_consigned - $steDetails->transfer_qty;

                    DB::table('tabBin')->where('warehouse', $steDetails->s_warehouse)->where('item_code', $steDetails->item_code)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'actual_qty' => $source_new_qty,
                        'consigned_qty' => $source_new_consigned
                    ]);

                    // target warehouse
                    $target_actual_qty = $this->get_actual_qty($steDetails->item_code, $steDetails->t_warehouse);
                    $target_consigned = isset($wh_qty[$steDetails->t_warehouse]) ? $wh_qty[$steDetails->t_warehouse][0]->consigned_qty : 0;

                    $target_new_qty = $target_actual_qty + $steDetails->transfer_qty;
                    $target_new_consigned = $target_consigned + $steDetails->transfer_qty;

                    DB::table('tabBin')->where('warehouse', $steDetails->t_warehouse)->where('item_code', $steDetails->item_code)->update([
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'actual_qty' => $target_new_qty,
                        'consigned_qty' => $target_new_consigned
                    ]);

                    $consignment_status_update = [
                        'modified' => Carbon::now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'consignment_status' => 'Received',
                        'consignment_date_received' => Carbon::now()->toDateTimeString(),
                        'consignment_received_by' => Auth::user()->wh_user
                    ];

                    DB::table('tabStock Entry Detail')->where('name', $steDetails->name)->update($consignment_status_update);

                    $checker = DB::table('tabStock Entry Detail')->where('parent', $steDetails->parent)->where('consignment_status', 'To Receive')->exists();
                    if(!$checker){
                        DB::table('tabStock Entry')->where('name', $steDetails->parent)->update($consignment_status_update);
                    }
                }
            }            

            $stock_reservation_details = [];
            if($request->has_reservation && $request->has_reservation == 1) {
                $ref_no = ($steDetails->sales_order_no) ? $steDetails->sales_order_no : $steDetails->material_request;
                
                $so_details = DB::table('tabSales Order')->where('name', $ref_no)->first();

                $sales_person = ($so_details) ? $so_details->sales_person : null;
                $project = ($so_details) ? $so_details->project : null;
                $consignment_warehouse = null;
                if($steDetails->transfer_as == 'Consignment') {
                    $sales_person = null;
                    $project = null;
                    $consignment_warehouse = $steDetails->t_warehouse;
                }
                
                $stock_reservation_details = $this->get_stock_reservation($steDetails->item_code, $steDetails->s_warehouse, $sales_person, $project, $consignment_warehouse);

                if($stock_reservation_details && $request->deduct_reserve == 1){
                    $consumed_qty = $stock_reservation_details->consumed_qty + $request->qty;
                    $consumed_qty = ($consumed_qty > $stock_reservation_details->reserve_qty) ? $stock_reservation_details->reserve_qty : $consumed_qty;

                    $data = [
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => Carbon::now()->toDateTimeString(),
                        'consumed_qty' => $consumed_qty
                    ];

                    DB::table('tabStock Reservation')->where('name', $stock_reservation_details->name)->update($data);
                }

                $this->update_reservation_status();
            }

            // get actual qty AFTER submission of stock entry (for double checking of stocks after transaction)
            $ste_docstatus = DB::table('tabStock Entry')->where('name', $steDetails->parent_se)->pluck('docstatus')->first();

            if($ste_docstatus && $ste_docstatus == 1 && $steDetails->s_warehouse != $steDetails->t_warehouse){
                if($steDetails->s_warehouse){
                    $actual_qty_in_source = $this->get_actual_qty($steDetails->item_code, $steDetails->s_warehouse);
                    if(number_format($expected_qty_in_source, 4, '.', '') != number_format($actual_qty_in_source, 4, '.', '')){
                        return response()->json(['success' => 0, 'message' => 'There was a problem submitting transaction. Please reload the page and try again.'], 500);
                    }
                }
                
                $actual_qty_in_target = $this->get_actual_qty($steDetails->item_code, $steDetails->t_warehouse);
                if(number_format($expected_qty_in_target, 4, '.', '') != number_format($actual_qty_in_target, 4, '.', '')){
                    return response()->json(['success' => 0, 'message' => 'There was a problem submitting transaction. Please reload the page and try again.'], 500);
                }
            }
        
            DB::commit();

            if($request->deduct_reserve == 1) {
                return response()->json(['status' => 1, 'message' => 'Item ' . $steDetails->item_code . ' has been deducted from reservation.']);
            }

            if (($steDetails->transfer_as == 'For Return') || $steDetails->purpose == 'Material Receipt') {
                return response()->json(['status' => 1, 'message' => 'Item <b>' . $steDetails->item_code . '</b> has been returned.']);
            }else{
                return response()->json(['status' => 1, 'message' => 'Item <b>' . $steDetails->item_code . '</b> has been checked out.']);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.'], 500);
        }
    }

    public function insert_transaction_log($transaction_type, $id){
        if($transaction_type == 'Picking Slip'){
            $q = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))->where('ps.item_status', 'For Checking')->where('dri.docstatus', 0)->where('psi.name', $id)
                ->select('psi.name', 'psi.parent', 'psi.item_code', 'psi.description', 'ps.delivery_note', 'dri.warehouse', 'psi.qty', 'psi.barcode', 'psi.session_user', 'psi.stock_uom')
                ->first();

            if(!$q){
                $q = DB::table('tabPacking Slip as ps')
                    ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                    ->join('tabPacked Item as pi', 'pi.name', 'psi.pi_detail')
                    ->where('ps.item_status', 'For Checking')
                    ->where('psi.name', $id)
                    ->select('psi.name', 'psi.parent', 'psi.item_code', 'psi.description', 'ps.delivery_note', 'pi.warehouse', 'psi.qty', 'psi.barcode', 'psi.session_user', 'psi.stock_uom')
                    ->first();
            }

            $type = 'Check Out - Delivered';
            $purpose = 'Picking Slip';
            $barcode = $q->barcode;
            $remarks = null;
            $s_warehouse = $q->warehouse;
            $t_warehouse = null;
            $reference_no = $q->delivery_note;
        } else if($transaction_type == 'Delivery Note') {
            $q = DB::table('tabDelivery Note as dn')
                ->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
                ->where('dni.name', $id)->select('dni.name', 'dni.parent', 'dni.item_code', 'dni.description', 'dn.name as delivery_note', 'dni.warehouse', 'dni.qty', 'dni.barcode', 'dni.session_user', 'dni.stock_uom')
                ->first();

            $type = 'Check In - Received';
            $purpose = 'Sales Return';
            $barcode = $q->barcode;
            $remarks = null;
            $s_warehouse = null;
            $t_warehouse = $q->warehouse;
            $reference_no = $q->delivery_note;
        } else {
            $q = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')->where('sted.name', $id)
                ->select('sted.*', 'ste.sales_order_no', 'ste.material_request', 'ste.purpose', 'ste.transfer_as', 'ste.issue_as', 'ste.receive_as')
                ->first();

            $type = null;
            if($q->purpose == 'Manufacture') {
                $type = 'Check In - Received';
            }

            if($q->purpose == 'Material Transfer for Manufacture') {
                $type = 'Check Out - Issued';
            }

            if($q->purpose == 'Material Transfer' && $q->transfer_as == 'Internal Transfer') {
                $type = 'Check Out - Transferred';
            }

            if($q->purpose == 'Material Transfer' && in_array($q->transfer_as, ['Consignment', 'Sample Item'])) {
                $type = 'Check Out - Delivered';
            }

            if($q->purpose == 'Material Transfer' && $q->transfer_as == 'For Return') {
                $type = 'Check In - Returned';
            }

            if($q->purpose == 'Material Issue' && $q->issue_as == 'Customer Replacement') {
                $type = 'Check Out - Replaced';
            }

            if($q->purpose == 'Material Issue' && $q->issue_as != 'Customer Replacement') {
                $type = 'Check Out - Issued';
            }

            if($q->purpose == 'Material Receipt' && $q->receive_as == 'Sales Return') {
                $type = 'Check In - Received';
            }

            $purpose = $q->purpose;
            $barcode = $q->validate_item_code;
            $remarks = $q->remarks;
            $s_warehouse = $q->s_warehouse;
            $t_warehouse = $q->t_warehouse;
            $reference_no = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;
        }
       
        $now = Carbon::now();
        
        $values = [
            'name' => uniqid(date('mdY')),
            'reference_type' => $transaction_type,
            'reference_name' => $q->name,
            'reference_parent' => $q->parent,
            'item_code' => $q->item_code,
            'qty' => $q->qty,
            'barcode' => $barcode,
            'transaction_date' => $now->toDateTimeString(),
            'warehouse_user' => $q->session_user,
            'issued_qty' => $q->qty,
            'remarks' => $remarks,
            'source_warehouse' => $s_warehouse,
            'target_warehouse' => $t_warehouse,
            'description' => $q->description,
            'reference_no' => $reference_no,
            'creation' => $now->toDateTimeString(),
            'modified' => $now->toDateTimeString(),
            'modified_by' => Auth::user()->wh_user,
            'owner' => Auth::user()->wh_user,
            'uom' => $q->stock_uom,
            'purpose' => $purpose,
            'transaction_type' => $type
        ];

        $existing_log = DB::table('tabAthena Transactions')
            ->where('reference_name', $q->name)->where('reference_parent', $q->parent)->where('status', 'Issued')
            ->exists();

        if(!$existing_log){
            DB::table('tabAthena Transactions')->insert($values);
        }
    }

    public function update_pending_ste_item_status(){
        DB::beginTransaction();
        try {
            $for_checking_ste = DB::table('tabStock Entry')
                ->where('item_status', 'For Checking')->where('docstatus', 0)
                ->select('name', 'transfer_as', 'receive_as')->get();

            $item_status = null;
            foreach($for_checking_ste as $ste){
                $items_for_checking = DB::table('tabStock Entry Detail')
                    ->where('parent', $ste->name)->where('status', 'For Checking')->exists();

                if(!$items_for_checking){
                    if($ste->receive_as == 'Sales Return'){
                        DB::table('tabStock Entry')->where('name', $ste->name)->where('docstatus', 0)->update(['item_status' => 'Returned']);
                    }else{
                        $item_status = ($ste->transfer_as == 'For Return') ? 'Returned' : 'Issued';
                        DB::table('tabStock Entry')->where('name', $ste->name)->where('docstatus', 0)->update(['item_status' => $item_status]);
                    }
                }
            }

            DB::commit();

            return $item_status;
        } catch (\Exception $e) {
            DB::rollback();
        }
    }

    public function checkout_picking_slip_bundled($packing_slip, $child_id, $request){
        DB::connection('mysql')->beginTransaction();
        try {
            $now = Carbon::now();
            $packed_items = collect($packing_slip->items)->filter(function ($item) use ($request) {
                return $item->packed->parent_item == $request->barcode;
            });

            if(!$packed_items){
                throw new Exception("Item(s) not found");
            }

            if($request->has_reservation && $request->deduct_reserve) {
                $so_details = SalesOrder::find($request->sales_order);
                foreach($packed_items as $packed){
                    if($so_details) {
                        $stock_reservation_details = $this->get_stock_reservation($packed->item_code, $request->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
                    }
    
                    if($stock_reservation_details){
                        $consumed_qty = $stock_reservation_details->consumed_qty + $request->qty;
                        $consumed_qty = ($consumed_qty > $stock_reservation_details->reserve_qty) ? $stock_reservation_details->reserve_qty : $consumed_qty;
    
                        $data = [
                            'modified_by' => Auth::user()->wh_user,
                            'modified' => Carbon::now()->toDateTimeString(),
                            'consumed_qty' => $consumed_qty
                        ];

                        DB::connection('mysql')->table('tabStock Reservation')->where('name', $stock_reservation_details->name)->update($data);
                    }
                }
            }

            $items_to_issue = [];
            foreach ($packing_slip->items as $item) {
                $item->qty = (float) $item->qty;
                if ($item->packed->parent_item === $request->barcode) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Issued';
                    $item->barcode = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    $item->qty = (float) $request->qty;

                    $items_to_issue[] = $item->name;
                }

                unset($item->packed);
            }

            $items_for_checking = collect($packing_slip->items)->whereNotIn('name', $items_to_issue)->where('status', 'For Checking')->count();

            if($items_for_checking <= 0){
                $packing_slip->docstatus = 1;
                $packing_slip->item_status = 'Issued';
            }

            $response = $this->erpOperation('put', 'Packing Slip', $packing_slip->name, collect($packing_slip)->toArray(), true);
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating Packing Slip';
                throw new Exception($err);
            }

            DB::connection('mysql')->commit();
            $message = $request->deduct_reserve ? "Item <b>$request->barcode</b> has been deducted from reservation" : "Item <b>$request->barcode</b> has been checked out";
            return response()->json(['status' => 1, 'message' => $message]);
        } catch (\Throwable $th) {
            DB::connection('mysql')->rollback();
            return response()->json([
                'status' => 0, 
                'modal_title' => 'Error', 
                'modal_message' => $th->getMessage()
            ]);
        }
    }

    public function checkout_picking_slip(Request $request){
        try {
            $child_id = $request->child_tbl_id;
            $now = Carbon::now();
            $packing_slip = PackingSlip::with(['items' => function ($item) {
                $item->with('packed');
            }])->whereHas('items', function ($item) use ($child_id){
                $item->where('name', $child_id);
            })->first();

            if(!$packing_slip){
                throw new Exception("Record not found");
            }

            if($request->qty <= 0){
                throw new Exception("Qty cannot be less than or equal to 0.");
            }

            if(in_array($packing_slip->item_status, ['Issued', 'Returned'])){
                throw new Exception("Item already $packing_slip->item_status.");
            }

            if($packing_slip->docstatus == 1){
                throw new Exception("Item already submitted.");
            }

            // Checkout for bundled items
            if($request->type == 'packed_item'){
                return $this->checkout_picking_slip_bundled($packing_slip, $child_id, $request);
            }

            $packing_slip_item = collect($packing_slip->items)->where('name', $child_id)->first();

            $item_code = $packing_slip_item->item_code;

            $itemDetails = Item::find($item_code);
            if(!$itemDetails){
                throw new Exception("Item <b>$item_code</b> not found.");
            }

            if($request->is_bundle == 0 && $itemDetails->is_stock_item == 0) {
                throw new Exception("Item <b>$item_code</b> is not a stock item.");
            }
            
            if($request->barcode != $itemDetails->item_code){
                throw new Exception("Invalid barcode for <b>$itemDetails->item_code</b>.");
            }
            
            $qty_check = $this->get_available_qty($item_code, $request->warehouse);
            $qty_display = 'qty';
            if($request->deduct_reserve){
                $qty_check = $this->get_reserved_qty($item_code, $request->warehouse);
                $qty_display = 'reserved qty';
            }

            if($request->qty > $qty_check && $request->is_bundle == false){
                throw new Exception("Qty not available for <b>$item_code</b> in <b>$request->warehouse</b><
                br><br>Available $qty_display is <b>$qty_check</b>, you need <b>$request->qty</b>.");
            }

            $stock_reservation_details = [];
            if($request->has_reservation && $request->deduct_reserve) {
                $so_details = SalesOrder::find($request->sales_order);
                if($so_details) {
                    $stock_reservation_details = $this->get_stock_reservation($item_code, $request->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
                }

                if($stock_reservation_details){
                    $consumed_qty = $stock_reservation_details->consumed_qty + $request->qty;
                    $consumed_qty = ($consumed_qty > $stock_reservation_details->reserve_qty) ? $stock_reservation_details->reserve_qty : $consumed_qty;

                    $data = [
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => Carbon::now()->toDateTimeString(),
                        'consumed_qty' => $consumed_qty
                    ];

                    $reservation_response = $this->erpOperation('put', 'Stock Reservation', $stock_reservation_details->name, $data);
                    if(!isset($reservation_response['data'])){
                        $err = isset($reservation_response['exception']) ? $reservation_response['exception'] : 'An error occured while updating Packing Slip';
                        throw new Exception($err);
                    }
                }
            }

            foreach ($packing_slip->items as $item) {
                $item->qty = (float) $item->qty;
                if(!$item->dn_detail){ // Delivery Note Item reference is required
                    $delivery_note_item = DB::table('tabDelivery Note as dn')
                        ->join('tabDelivery Note Item as dni', 'dni.parent', 'dn.name')
                        ->where('dn.name', $packing_slip->delivery_note)->where('dni.item_code', $item->item_code)
                        ->pluck('dni.name')->first();

                    $item->dn_detail = $delivery_note_item;
                }

                if ($item->name === $child_id) { // Issue only the specific child
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Issued';
                    $item->barcode = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    $item->qty = (float) $request->qty;
                }
            }

            $items_for_checking = collect($packing_slip->items)->where('name', '!=', $child_id)->where('status', 'For Checking')->count();

            if($items_for_checking <= 0){
                $packing_slip->docstatus = 1;
                $packing_slip->item_status = 'Issued';
            }

            $response = $this->erpOperation('put', 'Packing Slip', $packing_slip->name, collect($packing_slip)->toArray(), true);
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating Packing Slip';
                throw new Exception($err);
            }

            $message = $request->deduct_reserve ? "Item <b>$item_code</b> has been deducted from reservation" : "Item <b>$item_code</b> has been checked out";
            return response()->json(['status' => 1, 'message' => $message]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 0, 
                'modal_title' => 'Error', 
                'message' => $th->getMessage()
            ]);
        }
    }

    // /checkout_picking_slip_item
    public function checkout_picking_slip_item(Request $request){
        DB::connection('mysql')->beginTransaction();
        try {
            if($request->type == 'packed_item'){
                $ps_details = DB::table('tabPacking Slip as ps')
                    ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                    ->join('tabPacked Item as pi', 'pi.name', 'psi.pi_detail')
                    ->where('psi.name', $request->child_tbl_id)
                    ->select('ps.name as parent_ps', 'ps.*', 'psi.*', 'psi.status as per_item_status', 'ps.docstatus as ps_status', 'pi.parent_item as item_code')->first();
            }else{
                $ps_details = DB::table('tabPacking Slip as ps')
                    ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                    ->where('psi.name', $request->child_tbl_id)
                    ->select('ps.name as parent_ps', 'ps.*', 'psi.*', 'psi.status as per_item_status', 'ps.docstatus as ps_status')->first();
            }

            if(!$ps_details){
                return response()->json(['status' => 0, 'message' => 'Record not found.']);
            }

            if(in_array($ps_details->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $ps_details->per_item_status . '.']);
            }

            if($ps_details->ps_status == 1){
                return response()->json(['status' => 0, 'message' => 'Item already submitted.']);
            }
            
            $itemDetails = DB::table('tabItem')->where('name', $ps_details->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $ps_details->item_code . '</b> not found.']);
            }
            if($request->is_bundle == 0) {
                if($itemDetails->is_stock_item == 0){
                    return response()->json(['status' => 0, 'message' => 'Item  <b>' . $ps_details->item_code . '</b> is not a stock item.']);
                }
            }
            
            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => 'Invalid barcode for <b>' . $itemDetails->item_code . '</b>.']);
            }
            
            if($request->qty <= 0){
                return response()->json(['status' => 0, 'message' => 'Qty cannot be less than or equal to 0.']);
            }
            // if($request->qty > $ps_details->qty){
            //     return response()->json(['status' => 0, 'message' => 'Qty cannot be greater than ' . ($ps_details->qty * 1) .'.']);
            // }

            // $customer = DB::table('tabDelivery Note')->where('name', $ps_details->delivery_note)->pluck('customer')->first();

            $available_qty = $this->get_available_qty($ps_details->item_code, $request->warehouse);
            if($request->qty > $available_qty && $request->is_bundle == false && $request->deduct_reserve == 0){
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $ps_details->item_code . '</b> in <b>' . $request->warehouse . '</b><
                br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
            }

            $reserved_qty = $this->get_reserved_qty($ps_details->item_code, $request->warehouse);
            if($request->qty > $reserved_qty && $request->is_bundle == false && $request->deduct_reserve == 1){
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $ps_details->item_code . '</b> in <b>' . $request->warehouse . '</b><
                br><br>Available reserved qty is <b>' . $reserved_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
            }

            if($request->is_bundle){
                $query = DB::table('tabPacked Item as pi')
                    ->join('tabPacking Slip Item as psi', 'psi.pi_detail', 'pi.name')
                    ->where('pi.parent_detail_docname', $request->dri_name)
                    ->select('pi.*', 'psi.name as item_id')->get();

                foreach ($query as $row) {
                    $bundle_item_qty = (float) $row->qty;
                   
                    $actual_qty = $this->get_actual_qty($row->item_code, $row->warehouse);
    
                    $total_issued = $this->get_issued_qty($row->item_code, $row->warehouse);

                    $available_qty = ($actual_qty - $total_issued);

                    if($available_qty < $bundle_item_qty){
                        return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $row->item_code . '</b> in <b>' . $row->warehouse . '</b><br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . number_format($bundle_item_qty) . '</b>.']);
                    }

                    if($request->deduct_reserve == 1){
                        $reserved_qty = $this->get_reserved_qty($row->item_code, $row->warehouse);
                        if ($available_qty > 0) {
                            $available_qty = $available_qty - $reserved_qty;
                        }

                        $stock_reservation_details = [];
                        if($request->has_reservation && $request->has_reservation == 1) {
                            $so_details = DB::table('tabSales Order')->where('name', $request->sales_order)->first();
                            if($so_details) {
                                $stock_reservation_details = $this->get_stock_reservation($row->item_code, $row->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
                            }

                            if($stock_reservation_details && $request->deduct_reserve == 1){
                                $consumed_qty = $stock_reservation_details->consumed_qty + $bundle_item_qty;
                                $consumed_qty = ($consumed_qty > $stock_reservation_details->reserve_qty) ? $stock_reservation_details->reserve_qty : $consumed_qty;

                                $data = [
                                    'modified_by' => Auth::user()->wh_user,
                                    'modified' => Carbon::now()->toDateTimeString(),
                                    'consumed_qty' => $consumed_qty
                                ];

                                DB::table('tabStock Reservation')->where('name', $stock_reservation_details->name)->update($data);
                            }
                            
                            $this->update_reservation_status();
                        }
                    } else {
                        if($available_qty < $bundle_item_qty){
                            return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $row->item_code . '</b> in <b>' . $row->warehouse . '</b><br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . ($row->qty * 1) . '</b>.']);
                        }
                    }

                    $response = $this->erpOperation('put', 'Packing Slip Item', $row->item_id, ['status' => 'Issued']);
                    $this->insert_transaction_log('Picking Slip', $row->item_id);

                    if(!isset($response['data'])){
                        throw new Exception(isset($response['message']) ? $response['message'] : 'An error occured. Please try again');
                    }
                }
            }

            $stock_reservation_details = [];
            if($request->has_reservation && $request->has_reservation == 1) {
                $so_details = DB::table('tabSales Order')->where('name', $request->sales_order)->first();
                if($so_details) {
                    $stock_reservation_details = $this->get_stock_reservation($ps_details->item_code, $request->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
                }

                if($stock_reservation_details && $request->deduct_reserve == 1){
                    $consumed_qty = $stock_reservation_details->consumed_qty + $request->qty;
                    $consumed_qty = ($consumed_qty > $stock_reservation_details->reserve_qty) ? $stock_reservation_details->reserve_qty : $consumed_qty;

                    $data = [
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => Carbon::now()->toDateTimeString(),
                        'consumed_qty' => $consumed_qty
                    ];

                    DB::table('tabStock Reservation')->where('name', $stock_reservation_details->name)->update($data);
                }
                
                $this->update_reservation_status();
            }

            if($request->type != 'packed_item'){
                $now = Carbon::now();
                $values = [
                    'session_user' => Auth::user()->wh_user,
                    'status' => 'Issued',
                    'barcode' => $request->barcode,
                    'date_modified' => $now->toDateTimeString()
                ];

                // DB::table('tabPacking Slip Item')->where('name', $request->child_tbl_id)
                //     ->where('docstatus', '<', 2)->update($values);
                $response = $this->erpOperation('put', 'Packing Slip Item', $request->child_tbl_id, $values);
                if(!isset($response['data'])){
                    $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating picking slip';
                    throw new Exception($err);
                }

                $this->insert_transaction_log('Picking Slip', $request->child_tbl_id);
            }

            $items_for_checking = DB::connection('mysql')->table('tabPacking Slip Item')->where('name', '!=', $request->child_tbl_id)->where('parent', $ps_details->parent_ps)->where('status', 'For Checking')->exists();

            if(!$items_for_checking){
                // DB::connection('mysql')->table('tabPacking Slip')->where('name', $ps_details->parent_ps)->update();
                $parent_response = $this->erpOperation('put', 'Packing Slip', $ps_details->parent_ps, ['item_status' => 'Issued', 'docstatus' => 1]);
                if(!isset($parent_response['data'])){
                    $err = isset($parent_response['exception']) ? $parent_response['exception'] : 'An error occured while updating picking slip';
                    throw new Exception($err);
                }
            }

            DB::connection('mysql')->commit();

            if($request->deduct_reserve == 1) {
                return response()->json(['status' => 1, 'message' => 'Item ' . $itemDetails->item_code . ' has been deducted from reservation.']);
            }

            return response()->json(['status' => 1, 'message' => 'Item ' . $itemDetails->item_code . ' has been checked out.']);
        } catch (Exception $e) {
            DB::connection('mysql')->rollback();
            return response()->json([
                'status' => 0, 
                'modal_title' => 'Error', 
                'modal_message' => 'Error creating transaction.'
            ]);
        }
    }

    public function update_pending_ps_item_status($id){
        try {
            $items_for_checking = DB::table('tabPacking Slip Item')->where('parent', $id)->where('status', 'For Checking')->exists();

            if(!$items_for_checking){
                $this->erpOperation('put', 'Packing Slip', $id, ['item_status' => 'Issued', 'docstatus' => 1]);
            }

            return ['success' => 1, 'message' => 'Packing Slips updated!'];
        } catch (Exception $e) {
            return ['success' => 0, 'message' => $e->getMessage()];
        }
    }

    public function form_warehouse_location($item_code){
        try {
            $user = Auth::user()->frappe_userid;
            $allowed_warehouses = $this->user_allowed_warehouse($user);

            $warehouses = DB::table('tabBin')->whereIn('warehouse', $allowed_warehouses)->where('item_code', $item_code)->select('warehouse', 'location')->get();

            if(count($warehouses) <= 0){
                throw new \ErrorException('Item <b>'.$item_code.'</b> is not available on any warehouse.');
            }

            return view('form_warehouse_location', compact('warehouses', 'item_code'));
        } catch (\ErrorException $th) {
            return response()->json(['status' => 0, 'message' => $th->getMessage()], 400);
        } catch (\Exception $th) { // handle actual errors
            return response()->json(['status' => 0, 'message' => `Something went wrong. Please try again.`], 400);
        }
    }

    public function edit_warehouse_location(Request $request){
        DB::beginTransaction();
        try {
            $locations = $request->location;

            if ($locations) {
                foreach ($locations as $warehouse => $location) {
                    DB::table('tabBin')->where('warehouse', $warehouse)->where('item_code', $request->item_code)
                        ->update(['location' => strtoupper($location)]);
                }
            }
            
            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Warehouse location updated.', 'item_code' => $request->item_code]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Something went wrong. Please contact your system administrator.']);
        }
    }

    public function get_item_details(Request $request, $item_code){
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        if(!$item_details){
            abort(404);
        }

        if($request->json){
            return response()->json($item_details);
        }

        if($request->ajax()){
            $uoms = DB::table('tabUOM')->pluck('name');

            return view('item_information', compact('item_details', 'uoms'));
        }

        $bundled = DB::table('tabProduct Bundle')->where('name', $item_code)->exists();

        $user_department = Auth::user()->department;
        $user_group = Auth::user()->user_group;
        // get departments allowed to view prices
        $allowed_department = DB::table('tabDeparment with Price Access')->pluck('department')->toArray();

        $item_rate = $minimum_selling_price = $default_price = $last_purchase_rate = $manual_rate = $is_tax_included_in_rate = 0;
        $last_purchase_date = null;
        $website_price = [];
        $avgPurchaseRate = '₱ 0.00';
        if (in_array($user_department, $allowed_department) || in_array($user_group, ['Manager', 'Director'])) {
            $avgPurchaseRate = $this->avgPurchaseRate($item_code);
            $last_purchase_order = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
                ->where('po.docstatus', 1)->where('poi.item_code', $item_code)->select('poi.base_rate', 'po.supplier_group', 'po.creation')->orderBy('po.creation', 'desc')->first();

            if ($last_purchase_order) {
                $last_purchase_date = Carbon::parse($last_purchase_order->creation)->format('M. d, Y h:i:A');
                if ($last_purchase_order->supplier_group == 'Imported') {
                    $last_landed_cost_voucher = DB::table('tabLanded Cost Voucher as a')
                        ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                        ->where('a.docstatus', 1)->where('b.item_code', $item_code)
                        ->select('a.creation', 'a.name as purchase_order', 'b.item_code', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')
                        ->orderBy('transaction_date', 'desc')
                        ->first();
    
                    if ($last_landed_cost_voucher) {
                        $item_rate = $last_landed_cost_voucher->valuation_rate;
                    }
                } else {
                    $item_rate = $last_purchase_order->base_rate;
                }
            }

            $price_settings = DB::table('tabSingles')->where('doctype', 'Price Settings')
                ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])->pluck('value', 'field')->toArray();

            $minimum_price_computation = array_key_exists('minimum_price_computation', $price_settings) ? $price_settings['minimum_price_computation'] : 0;
            $standard_price_computation = array_key_exists('standard_price_computation', $price_settings) ? $price_settings['standard_price_computation'] : 0;
            $is_tax_included_in_rate = array_key_exists('is_tax_included_in_rate', $price_settings) ? $price_settings['is_tax_included_in_rate'] : 0;

            $last_purchase_rate = $item_rate;

            if ($item_rate <= 0) {
                $manual_rate = 1;
                $item_rate = $item_details->custom_item_cost;
            }

            $minimum_selling_price = $item_rate * $minimum_price_computation;
            $default_price = $item_rate * $standard_price_computation;

            if($is_tax_included_in_rate) {
                $default_price = ($item_rate * $standard_price_computation) * 1.12;
            }

            $website_price = DB::table('tabItem Price')
                ->where('price_list', 'Website Price List')->where('selling', 1)
                ->where('item_code', $item_code)->orderBy('modified', 'desc')
                ->select('price_list_rate', 'price_list')->first();

            $default_price = ($website_price) ? $website_price->price_list_rate : $default_price;
        }

        $item_stock_levels = $this->get_item_stock_levels($item_code, $request);

        $consignment_warehouses = $item_stock_levels['consignment_warehouses'];
        $site_warehouses = $item_stock_levels['site_warehouses'];

        $item_stock_available = collect($consignment_warehouses)->sum('available_qty');
        if($item_stock_available <= 0) {
            $item_stock_available = collect($site_warehouses)->sum('available_qty');
        }

        // get item images
        $item_images = DB::table('tabItem Images')->where('parent', $item_code)->orderBy('idx', 'asc')->pluck('image_path');

        $item_images = collect($item_images)->map(function($image){
            return $this->base64_image("/img/$image");
        });

        $no_img = $this->base64_image("/icon/no_img.png");
        // get item alternatives from production order item table in erp
        $item_alternatives = [];
        $production_item_alternatives = DB::table('tabWork Order Item as p')->join('tabItem as i', 'p.item_alternative_for', 'i.name')
            ->where('p.item_code', $item_details->name)->where('p.item_alternative_for', '!=', $item_details->name)->where('i.stock_uom', $item_details->stock_uom)
            ->select('i.item_code', 'i.description')->orderBy('p.modified', 'desc')->get()->toArray();

        $production_item_alternative_item_codes = array_column($production_item_alternatives, 'item_code');
        $item_alternative_images = DB::table('tabItem Images')->whereIn('parent', $production_item_alternative_item_codes)->orderBy('idx', 'asc')->pluck('image_path')->toArray();
        $production_item_alt_actual_stock = DB::table('tabBin')->whereIn('item_code', $production_item_alternative_item_codes)->selectRaw('SUM(actual_qty) as actual_qty, item_code')
            ->groupBy('item_code')->pluck('actual_qty', 'item_code')->toArray();
        foreach($production_item_alternatives as $a){
            // $item_alternative_image = array_key_exists($a->item_code, $item_alternative_images) ? $item_alternative_images[$a->item_code] : null;
            $item_alternative_image = isset($item_alternative_images[$a->item_code]) ? "/img/".$item_alternative_images[$a->item_code] : "/icon/no_img.png";
            $item_alternative_image = $this->base64_image($item_alternative_image);

            $actual_stocks = array_key_exists($a->item_code, $production_item_alt_actual_stock) ? $production_item_alt_actual_stock[$a->item_code] : 0;

            if(count($item_alternatives) < 7){
                $item_alternatives[] = [
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_alternative_image' => $item_alternative_image,
                    'actual_stocks' => $actual_stocks
                ];
            }
        }

        $variants_price_arr = $variants_cost_arr = $variants_min_price_arr = $actual_variant_stocks = $manual_price_input = $attribute_names = $attributes = $co_variants = $item_attributes = [];

        if(!$bundled){
            $item_attributes = DB::table('tabItem Variant Attribute')->where('parent', $item_code)->orderBy('idx', 'asc')->pluck('attribute_value', 'attribute')->toArray();
            // get item alternatives based on parent item code
            $q = DB::table('tabItem')->where('variant_of', $item_details->variant_of)->where('name', '!=', $item_details->name)->orderBy('modified', 'desc')->get();
            $alternative_item_codes = collect($q)->pluck('name');
            
            // get actual stock qty of all item alternatives
            $actual_stocks_query = DB::table('tabBin')->whereIn('item_code', $alternative_item_codes)->selectRaw('item_code, SUM(actual_qty) as actual_qty')->groupBy('item_code')->get();
            $actual_stocks = collect($actual_stocks_query)->groupBy('item_code');
            
            // get total reserved and consumed qty of all item alternatives
            $stock_reserves_query = StockReservation::whereIn('item_code', $alternative_item_codes)->whereIn('status', ['Active', 'Partially Issued'])->selectRaw('SUM(reserve_qty) as reserved_qty, SUM(consumed_qty) as consumed_qty, item_code')->groupBy('item_code')->get();
            $alternative_reserves = collect($stock_reserves_query)->groupBy('item_code');
            
            // get draft issued ste of all item alternatives
            $ste_issued_query = DB::table('tabStock Entry Detail')->where('docstatus', 0)->whereIn('item_code', $alternative_item_codes)->where('status', 'Issued')->selectRaw('SUM(qty) as qty, item_code')->groupBy('item_code')->get();
            $alternatives_issued_ste = collect($ste_issued_query)->groupBy('item_code');
            
            // get draft issued packing slip/drs of all item alternatives
            $at_issued_query = DB::table('tabAthena Transactions as at')
                ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
                ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
                ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)
                ->where('psi.status', 'Issued')->where('at.status', 'Issued')
                ->whereIn('at.item_code', $alternative_item_codes)
                ->whereRaw('psi.item_code = at.item_code')
                ->selectRaw('SUM(at.issued_qty) as qty, at.item_code')->groupBy('at.item_code')
                ->get();
            $alternatives_issued_at = collect($at_issued_query)->groupBy('item_code');

            $item_alternative_images = DB::table('tabItem Images')->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');
            
            foreach($q as $a){
                // $item_alternative_image = isset($item_alternative_images[$a->item_code]) ? $item_alternative_images[$a->item_code] : null;
                $item_alternative_image = isset($item_alternative_images[$a->item_code]) ? "/img/".$item_alternative_images[$a->item_code] : "/icon/no_img.png";
                $item_alternative_image = $this->base64_image($item_alternative_image);

                $total_reserved = $total_consumed = 0;
                if(isset($alternative_reserves[$a->item_code])){
                    $total_reserved = $alternative_reserves[$a->item_code]->sum('reserved_qty');
                    $total_consumed = $alternative_reserves[$a->item_code]->sum('consumed_qty');
                }
            
                $total_issued_ste = isset($alternatives_issued_ste[$a->item_code]) ? $alternatives_issued_ste[$a->item_code]->sum('qty') : 0;
                $total_isset_at = isset($alternatives_issued_at[$a->item_code]) ? $alternatives_issued_at[$a->item_code]->sum('qty') : 0;
                
                $total_issued = $total_issued_ste + $total_isset_at;
                $remaining_reserved = $total_reserved - $total_consumed;
            
                $actual_qty = isset($actual_stocks[$a->item_code]) ? $actual_stocks[$a->item_code][0]->actual_qty : 0;
                $available_qty = $actual_qty - ($total_issued + $remaining_reserved); // get available qty by subtracting the sum of reserved qty and draft issued picking slip/dr's to the actual qty
                $available_qty = $available_qty > 0 ? $available_qty : 0;
            
                if(count($item_alternatives) < 7){
                    $item_alternatives[] = [
                        'item_code' => $a->item_code,
                        'description' => $a->description,
                        'item_alternative_image' => $item_alternative_image,
                        'actual_stocks' => $available_qty
                    ];
                }
            }
            
            // $item_alternatives = [];
            if(count($item_alternatives) <= 0) {
                $q = DB::table('tabItem')->where('item_classification', $item_details->item_classification)
                    ->where('name', '!=', $item_details->name)->limit(100)->orderBy('modified', 'desc')->get();
                $item_alternative_images = DB::table('tabItem Images')->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');

                foreach($q as $a){
                    $item_alternative_image = isset($item_alternative_images[$a->item_code]) ? "/img/".$item_alternative_images[$a->item_code] : "/icon/no_img.png";
                    $item_alternative_image = $this->base64_image($item_alternative_image);

                    $total_reserved = $total_consumed = 0;
                    if(isset($alternative_reserves[$a->item_code])){
                        $total_reserved = $alternative_reserves[$a->item_code]->sum('reserved_qty');
                        $total_consumed = $alternative_reserves[$a->item_code]->sum('consumed_qty');
                    }
                    
                    $total_issued_ste = isset($alternatives_issued_ste[$a->item_code]) ? $alternatives_issued_ste[$a->item_code]->sum('qty') : 0;
                    $total_isset_at = isset($alternatives_issued_at[$a->item_code]) ? $alternatives_issued_at[$a->item_code]->sum('qty') : 0;
                    
                    $total_issued = $total_issued_ste + $total_isset_at;
                    $remaining_reserved = $total_reserved - $total_consumed;
            
                    $actual_qty = isset($actual_stocks[$a->item_code]) ? $actual_stocks[$a->item_code][0]->actual_qty : 0;
                    $available_qty = $actual_qty - ($total_issued + $remaining_reserved); // get available qty by subtracting the sum of reserved qty and draft issued picking slip/dr's to the actual qty
                    $available_qty = $available_qty > 0 ? $available_qty : 0;
            
                    if(count($item_alternatives) < 7){
                        $item_alternatives[] = [
                            'item_code' => $a->item_code,
                            'description' => $a->description,
                            'item_alternative_image' => $item_alternative_image,
                            'actual_stocks' => $available_qty
                        ];
                    }
                }
            }

            $item_alternatives = collect($item_alternatives)->sortByDesc('actual_stocks')->toArray();

            // variants
            $co_variants = DB::table('tabItem')->where('variant_of', $item_details->variant_of)->where('name', '!=', $item_details->name)->where('disabled', 0)->select('name', 'item_name', 'custom_item_cost')->paginate(10);
            $variant_item_codes = array_column($co_variants->items(), 'name');

            if (in_array($user_department, $allowed_department) || in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
                // get item cost for items with 0 last purchase rate
                $item_custom_cost = [];
                foreach ($co_variants->items() as $row) {
                    $item_custom_cost[$row->name] = $row->custom_item_cost;
                }

                $variants_last_purchase_order = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
                    ->where('po.docstatus', 1)->whereIn('poi.item_code', $variant_item_codes)->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')->orderBy('po.creation', 'desc')->get();

                $variants_last_landed_cost_voucher = DB::table('tabLanded Cost Voucher as a')->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                    ->where('a.docstatus', 1)->whereIn('b.item_code', $variant_item_codes)->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')->orderBy('transaction_date', 'desc')->get();
                
                $variants_last_purchase_order_rates = collect($variants_last_purchase_order)->groupBy('item_code')->toArray();
                $variants_last_landed_cost_voucher_rates = collect($variants_last_landed_cost_voucher)->groupBy('item_code')->toArray();

                $variants_website_prices = DB::table('tabItem Price')->where('price_list', 'Website Price List')->where('selling', 1)
                    ->whereIn('item_code', $variant_item_codes)->orderBy('modified', 'desc')->pluck('price_list_rate', 'item_code')->toArray();

                foreach($variant_item_codes as $variant){
                    $variants_default_price = 0;
                    $variant_rate = 0;
                    if(array_key_exists($variant, $variants_last_purchase_order_rates)){
                        if($variants_last_purchase_order_rates[$variant][0]->supplier_group == 'Imported'){
                            $variant_rate = isset($variants_last_landed_cost_voucher[$variant]) ? $variants_last_landed_cost_voucher[$variant][0]->valuation_rate : 0;
                        }else{
                            $variant_rate = $variants_last_purchase_order_rates[$variant][0]->base_rate;
                        }
                    }

                    $is_manual_rate = 0;
                    // custom item cost 
                    if ($variant_rate <= 0) {
                        if (array_key_exists($variant, $item_custom_cost)) {
                            $variant_rate = $item_custom_cost[$variant];
                            $is_manual_rate = 1;
                        } else {
                            $variant_rate = 0;
                        }
                    }

                    if($is_tax_included_in_rate) {
                        $variants_default_price = ($variant_rate * $standard_price_computation) * 1.12;
                    }

                    $variants_default_price = array_key_exists($variant, $variants_website_prices) ? $variants_website_prices[$variant] : $variants_default_price;
                    $variants_price_arr[$variant] = $variants_default_price;
                    $variants_cost_arr[$variant] = $variant_rate;
                    $variants_min_price_arr[$variant] = $variant_rate * $minimum_price_computation;
                    $manual_price_input[$variant] = $is_manual_rate;
                }
            }

            $actual_variant_stocks = DB::table('tabBin')->whereIn('item_code', $variant_item_codes)->selectRaw('SUM(actual_qty) as actual_qty, item_code')->groupBy('item_code')->pluck('actual_qty', 'item_code')->toArray();

            array_push($variant_item_codes, $item_details->name);

            $attributes_query = DB::table('tabItem Variant Attribute')->whereIn('parent', $variant_item_codes)->select('parent', 'attribute', 'attribute_value')->orderBy('idx', 'asc')->get();

            $attribute_names = collect($attributes_query)->pluck('attribute')->unique();

            $attributes = [];
            foreach ($attributes_query as $row) {
                $attributes[$row->parent][$row->attribute] = $row->attribute_value;
            }
        }
        

        $consignment_branches = [];
        if (Auth::user()->user_group == 'Promodiser') {
            $consignment_branches = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        }

        return view('item_profile', compact('is_tax_included_in_rate', 'item_details', 'item_attributes', 'site_warehouses', 'item_images', 'item_alternatives', 'consignment_warehouses', 'user_group', 'minimum_selling_price', 'default_price', 'attribute_names', 'co_variants', 'attributes', 'variants_price_arr', 'item_rate', 'last_purchase_date', 'allowed_department', 'user_department', 'avgPurchaseRate', 'last_purchase_rate', 'variants_cost_arr', 'variants_min_price_arr', 'actual_variant_stocks', 'item_stock_available', 'manual_rate', 'manual_price_input', 'consignment_branches', 'bundled', 'no_img'));
    }

    public function save_item_information(Request $request, $item_code){
        DB::beginTransaction();
        try {
            foreach($request->except('_token') as $dimension => $value){
                if(!in_array($dimension, ['package_dimension_uom'])){
                    if(!is_numeric($value)){
                        return response()->json(['success' => 0, 'message' => str_replace('_', ' ', $dimension).' must be a number.']);
                    }

                    if((float)$value <= 0){
                        return response()->json(['success' => 0, 'message' => str_replace('_', ' ', $dimension).' cannot be less than 0.']);
                    }
                }
            }

            $update_arr = $request->except('_token');
            $update_arr['modified'] = Carbon::now()->toDateTimeString();
            $update_arr['modified_by'] = Auth::user()->wh_user;

            DB::table('tabItem')->where('name', $item_code)->update($update_arr);

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Package dimension saved.']);
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollback();
            return response()->json(['success' => 0, 'message' => 'An error occured. Please try again later.']);
        }
    }

    public function get_athena_transactions(Request $request, $item_code){
        $user_group = Auth::user()->user_group;

        $req_wh_user = str_replace('null', null, $request->wh_user);
        $req_src_wh = str_replace('null', null, $request->src_wh);
        $req_trg_wh = str_replace('null', null, $request->trg_wh);
        $req_ath_dates = str_replace('null', null, $request->ath_dates);

        $logs = DB::table('tabAthena Transactions')->where('item_code', $item_code)->where('status', 'Issued')
            ->when($req_wh_user, function($q) use ($request){
                return $q->where('warehouse_user', $request->wh_user);
            })
            ->when($req_src_wh, function($q) use ($request){
                return $q->where('source_warehouse', $request->src_wh);
            })
            ->when($req_trg_wh, function($q) use ($request){
                return $q->where('target_warehouse', $request->trg_wh);
            })
            ->when($req_ath_dates, function($q) use ($request){
                $dates = explode(' to ', $request->ath_dates);
                $from = Carbon::parse($dates[0]);
                $to = Carbon::parse($dates[1])->endOfDay();
                return $q->whereBetween('transaction_date',[$from, $to]);
            })
            ->orderBy('transaction_date', 'desc')->paginate(15);

        $production_logs = collect($logs->items())->groupBy('purpose');
        $production_logs = isset($production_logs['Material Transfer for Manufacture']) ? $production_logs['Material Transfer for Manufacture'] : [];

        $production_orders = DB::table('tabStock Entry')->whereIn('name', collect($production_logs)->pluck('reference_parent'))->pluck('work_order', 'name');

        $ste_names = array_column($logs->items(), 'reference_parent');

        $ste_remarks = DB::table('tabStock Entry')->whereIn('name', $ste_names)
            ->select('purpose', 'transfer_as', 'receive_as', 'issue_as', 'name')->get();
        
        $ste_remarks = collect($ste_remarks)->groupBy('name')->toArray();

        $list = [];
        foreach($logs as $row){
            $ps_ref = ['Packing Slip', 'Picking Slip'];
            $reference_type = (in_array($row->reference_type, $ps_ref)) ? 'Packing Slip' : $row->reference_type;

            $existing_reference_no = DB::table('tab'.$reference_type)->where('name', $row->reference_parent)->first();
            if(!$existing_reference_no){
                $status = 'DELETED';
            }else{
                if ($existing_reference_no->docstatus == 2 or $row->docstatus == 2) {
                    $status = 'CANCELLED';
                } elseif ($existing_reference_no->docstatus == 0) {
                    $status = 'DRAFT';
                } else {
                    $status = 'SUBMITTED';
                }
            }

            $remarks = [];
            $remarks = (array_key_exists($row->reference_parent, $ste_remarks)) ? $ste_remarks[$row->reference_parent] : [];
            if (count($remarks) > 0) {
                if($remarks[0]->purpose == 'Material Issue') {
                    $remarks = $remarks[0]->issue_as;
                } elseif ($remarks[0]->purpose == 'Material Transfer') {
                    $remarks = $remarks[0]->transfer_as;
                } elseif ($remarks[0]->purpose == 'Material Receipt') {
                    $remarks = $remarks[0]->receive_as;
                } elseif ($remarks[0]->purpose == 'Material Transfer for Manufacture') {
                    $remarks = 'Materials Withdrawal';
                } else {
                    $remarks = '-';
                }
            } else {
                $remarks = null;
            }
            
            $list[] = [
                'reference_name' => $row->reference_name,
                'item_code' => $row->item_code,
                'reference_parent' => $row->reference_parent,
                'source_warehouse' => $row->source_warehouse,
                'target_warehouse' => $row->target_warehouse,
                'reference_type' => $row->purpose,
                'issued_qty' => $row->issued_qty * 1,
                'reference_no' => $row->reference_no,
                'transaction_date' => $row->transaction_date,
                'production_order' => isset($production_orders[$row->reference_parent]) ? $production_orders[$row->reference_parent] : null,
                'warehouse_user' => $row->warehouse_user,
                'status' => $status,
                'remarks' => $remarks
            ];
        }

        return view('tbl_athena_transactions', compact('list', 'logs', 'item_code', 'user_group'));
    }

    public function cancel_issued_item(Request $request){
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            switch ($request->reference) {
                case 'Stock Entry':
                    $q = DB::table('tabStock Entry Detail as sted')
                        ->join('tabStock Entry as ste', 'ste.name', 'sted.parent')
                        ->where('sted.name', $request->name)->first();

                    if(!$q){
                        return response()->json(['success' => 0, 'message' => 'Stock Entry not found.']);
                    }

                    if($q->status == 'For Checking'){
                        return response()->json(['success' => 0, 'message' => 'Item already cancelled.']);
                    }

                    DB::table('tabStock Entry Detail')->where('name', $request->name)->update([
                        'status' => 'For Checking',
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => $now->toDateTimeString()
                    ]);

                    if($q->item_status == 'Issued'){
                        DB::table('tabStock Entry')->where('name', $q->name)->update([
                            'item_status' => 'For Checking',
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user
                        ]);
                    }
                    break;
                case 'Delivery Note':
                    $q = DB::table('tabDelivery Note as dr')->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
                        ->where('dr.is_return', 1)->where('dr.docstatus', 0)->where('dri.name', $request->name)
                        ->select('dri.barcode_return', 'dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status', 'dri.stock_uom', 'dr.owner', 'dr.docstatus', 'dri.barcode', 'dri.parent', 'dri.session_user', 'dri.item_status')->first();

                    if(!$q){
                        return response()->json(['success' => 0, 'message' => 'Delivery Receipt not found.']);
                    }

                    if($q->item_status == 'For Return'){
                        return response()->json(['success' => 0, 'message' => 'Item already cancelled.']);
                    }

                    DB::table('tabDelivery Note Item')->where('name', $request->name)->update([
                        'item_status' => 'For Return',
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => $now->toDateTimeString()
                    ]);
                    break;
                default:
                    $q = DB::table('tabPacking Slip as ps')
                        ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                        ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                        ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')->whereRaw(('dri.item_code = psi.item_code'))->where('psi.name', $request->name)
                        ->select('psi.name', 'psi.parent', 'psi.item_code', 'psi.description', 'ps.delivery_note', 'dri.warehouse', 'psi.qty', 'psi.barcode', 'psi.session_user', 'psi.stock_uom', 'psi.parent')
                        ->first();

                    if(!$q){
                        return response()->json(['success' => 0, 'message' => 'Delivery Receipt not found.']);
                    }

                    if($q->status == 'For Checking'){
                        return response()->json(['success' => 0, 'message' => 'Item already cancelled.']);
                    }

                    DB::table('tabPacking Slip Item')->where('name', $request->name)->update([
                        'status' => 'For Checking',
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user
                    ]);

                    if($q->item_status == 'Issued'){
                        DB::table('tabPacking Slip')->where('name', $q->parent)->update([
                            'item_status' => 'For Checking',
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user
                        ]); 
                    }
                    break;
            }

            DB::table('tabAthena Transactions')->where('reference_name', $request->name)->update([
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ]);
            
            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Issued item(s) cancelled.']);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['success' => 0, 'message' => 'An error occured. Please try again.']);
        }
    }

    public function cancel_athena_transaction(Request $request){
        DB::beginTransaction();
        try{
            $ATstatus_update = [
                'docstatus' => 2
            ];

            $SEstatus_update = [
                'item_status' => 'For Checking'
            ];

            $SEDstatus_update = [
                'status' => 'For Checking',
                'session_user' => "",
                'issued_qty' => 0,
                'date_modified' => null
            ];

            $PSIstatus_update = [
                'status' => 'For Checking',
                'session_user' => "",
                'barcode' => "",
                'date_modified' => null
            ];

            $ATcancel = DB::table('tabAthena Transactions')->where('reference_parent', $request->athena_transaction_number)->update($ATstatus_update);
            $SEcancel = DB::table('tabStock Entry')->where('name', $request->athena_transaction_number)->update($SEstatus_update);
            $SEDcancel = DB::table('tabStock Entry Detail')->where('parent', $request->athena_transaction_number)->update($SEDstatus_update);
            $PSstatus_update = DB::table('tabPacking Slip')->where('name', $request->athena_transaction_number)->update($SEstatus_update);
            $PSIstatus_update = DB::table('tabPacking Slip Item')->where('name', $request->athena_reference_name)->update($PSIstatus_update);

            DB::commit();
            
            return response()->json(['status' => 1, 'message' => '<b>'. $request->athena_transaction_number . '</b> has been cancelled.', 'item_code' => $request->itemCode ]);
        }catch(\Exception $e){
            DB::rollback();
            
            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
        }
    }

    public function get_stock_ledger(Request $request, $item_code){
        $warehouse_user = [];
        if($request->wh_user != '' and $request->wh_user != 'null'){
            $user_qry = DB::table('tabWarehouse Users')->where('full_name', 'LIKE', '%'.$request->wh_user.'%')->orWhere('wh_user', 'LIKE', '%'.$request->wh_user.'%')->first();

            $warehouse_user = $user_qry ? [$user_qry->wh_user, $user_qry->full_name] : [];
            $warehouse_user = $warehouse_user ? $warehouse_user : [$request->wh_user];
        }

        $logs = DB::table('tabStock Ledger Entry as sle')->where('sle.item_code', $item_code)
            ->select(DB::raw('(SELECT GROUP_CONCAT(name) FROM `tabPacking Slip` where delivery_note = sle.voucher_no) as dr_voucher_no'))
            ->addSelect(DB::raw('
                (CASE
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(date_modified, modified) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) in ("Manufacture") THEN (SELECT modified FROM `tabStock Entry` where name = sle.voucher_no)
                    WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.date_modified, psi.modified) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                ELSE
                    sle.posting_date
                END) as ste_date_modified'
            ))
            ->addSelect(DB::raw('
                (CASE
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(session_user, modified_by) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                    WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.session_user, psi.modified_by) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                ELSE
                    sle.modified_by
                END) as ste_session_user'
            ))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) as ste_purpose'))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(sales_order_no) FROM `tabStock Entry` where name = sle.voucher_no) as ste_sales_order'))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(DISTINCT purchase_order) FROM `tabPurchase Receipt Item` where parent = sle.voucher_no and item_code = sle.item_code) as pr_voucher_no'))
            ->addSelect('sle.voucher_type', 'sle.voucher_no', 'sle.warehouse', 'sle.actual_qty', 'sle.qty_after_transaction', 'sle.posting_date')
            ->when($request->wh_user != '' and $request->wh_user != 'null', function($q) use ($warehouse_user){
				return $q->whereIn(DB::raw('
                    (CASE
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(session_user, modified_by) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                        WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.session_user, psi.modified_by) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                    ELSE
                        sle.modified_by
                    END)'
                ), $warehouse_user);
            })
            ->when($request->erp_wh != '' and $request->erp_wh != 'null', function($q) use ($request){
				return $q->where('sle.warehouse', $request->erp_wh);
            })
            ->when($request->erp_d != '' and $request->erp_d != 'null', function($q) use ($request){
                $dates = explode(' to ', $request->erp_d);

				return $q->whereBetween(DB::raw('
                    (CASE
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(date_modified, modified) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) in ("Manufacture") THEN (SELECT modified FROM `tabStock Entry` where name = sle.voucher_no)
                        WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT psi.date_modified FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                    ELSE
                        sle.posting_date
                    END)'
                ), [Carbon::parse($dates[0]), Carbon::parse($dates[1])]);
            })
            ->orderBy('sle.posting_date', 'desc')->orderBy('sle.posting_time', 'desc')
            ->orderBy('sle.name', 'desc')->paginate(20);

        $picking_slips = array_filter(collect($logs->items())->pluck('dr_voucher_no')->toArray());

        $overrided_ps = DB::connection('mysql')->table('tabPacking Slip Item')
            ->whereIn('parent', $picking_slips)->where('status', 'For Checking')
            ->distinct()->pluck('parent')->toArray();

        $list = [];
        foreach($logs as $row){
            if($row->voucher_type == 'Delivery Note'){
                $voucher_no = $row->dr_voucher_no;
                $transaction = 'Picking Slip';
            }elseif($row->voucher_type == 'Purchase Receipt'){
                $transaction = $row->voucher_type;
            }elseif($row->voucher_type == 'Stock Reconciliation'){
                $transaction = $row->voucher_type;
                $voucher_no = $row->voucher_no;
            }else{
                $transaction = $row->ste_purpose;
                $voucher_no = $row->voucher_no;
            }

            if($row->voucher_type == 'Delivery Note'){
                $ref_no = $row->voucher_no;
            }elseif($row->voucher_type == 'Purchase Receipt'){
                $voucher_no = $row->pr_voucher_no;
                $ref_no = $voucher_no;
            }elseif($row->voucher_type == 'Stock Entry'){
                $ref_no = $row->ste_sales_order;
            }elseif($row->voucher_type == 'Stock Reconciliation'){
                $ref_no = $voucher_no;
            }else{
                $ref_no = null;
            }

            $status = null;
            if (in_array($voucher_no, $overrided_ps)) {
                $status = 'Override';
            }

            $date_modified = $row->ste_date_modified;
            $session_user = $row->ste_session_user;

            if($date_modified and $date_modified != '--'){
                $date_modified = Carbon::parse($date_modified);
            }

            $list[] = [
                'voucher_no' => $voucher_no,
                'warehouse' => $row->warehouse,
                'transaction' => $transaction,
                'actual_qty' => $row->actual_qty * 1,
                'qty_after_transaction' => $row->qty_after_transaction * 1,
                'ref_no' => $ref_no,
                'date_modified' => $date_modified,
                'session_user' => $session_user,
                'posting_date' => $row->posting_date,
                'status' => $status
            ];
        }

        return view('tbl_stock_ledger', compact('list', 'logs', 'item_code'));
    }

    public function print_barcode($item_code){
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        return view('print_barcode', compact('item_details'));
    }

    public function upload_item_image(Request $request){
        // get item removed image file names for delete
        $existing_images = $request->existing_images ? $request->existing_images : [];
        $removed_images = DB::table('tabItem Images')->where('parent', $request->item_code)
            ->when($existing_images, function ($q) use ($existing_images){
                $q->whereNotIn('name', $existing_images);
            })
            ->pluck('image_path');

        foreach($removed_images as $img) {
            // delete from file directory
            Storage::delete('/img/' . $img);
        } 

        // delete from table item images
        DB::table('tabItem Images')->where('parent', $request->item_code)
            ->when($existing_images, function ($q) use ($existing_images){
                $q->whereNotIn('name', $existing_images);
            })
            ->delete();

        $now = Carbon::now();
        if($request->hasFile('item_image')){
            $files = $request->file('item_image');

            $item_images_arr = [];
            foreach ($files as $i => $file) {
                $microTime = round(microtime(true));
                $fileIndex = 0; // Initialize an index to handle multiple files, if needed

                $filename = "{$microTime}{$fileIndex}-{$request->item_code}";
                $originalExtension = $file->getClientOriginalExtension();

                // Paths for storage
                $storagePath = 'img/';
                $jpegFilename = "$filename.$originalExtension";
                $webpFilename = "$filename.webp";

                // Save the original file
                // Storage::putFileAs($storagePath, $file, $jpegFilename);

                // Create and save the WebP version
                $webp = Webp::make($file);

                if(!File::exists(public_path('temp'))){
                    File::makeDirectory(public_path('temp'), 0755, true);
                }

                $webp_path = public_path("temp/$webpFilename");

                // Storage::put("$storagePath$webpFilename", $webp);
                $webp->save($webp_path);

                $web_contents = file_get_contents($webp_path);
                Storage::put("/img/$webpFilename", $web_contents);

                unlink($webp_path);

                $item_images_arr[] = [
                    'name' => uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'idx' => $i + 1,
                    'parent' => $request->item_code,
                    'parentfield' => 'item_images',
                    'parenttype' => 'Item',
                    'image_path' => $webpFilename
                ];
            }
            
            DB::table('tabItem Images')->insert($item_images_arr);

            return response()->json(['message' => 'Item image for ' . $request->item_code . ' has been uploaded.']);
        }else{
            return response()->json(['message' => 'Item image for ' . $request->item_code . ' has been updated.']);
        }
    }

    public function load_item_images($item_code, Request $request){
        $images = ItemImages::where('parent', $item_code)->select('image_path', 'owner', 'modified_by', 'creation', 'modified')->orderBy('idx', 'asc')->get();

        if(count($images) <= 0){
            return response()->json([], 404);
        }

        $selected = $request->idx ? $request->idx : 0;

        $images = collect($images)->map(function ($image){
            $image->image = $image->image_path;
            $image->image_path = $image->image_path ? '/img/'.$image->image_path : '/icon/no_img.png';

            $image->original = 1;
            if(Storage::disk('public')->exists(explode('.', $image->image_path)[0].'.webp')){
                $image->original = 0;
                $image->image = explode('.', $image->image)[0].'.webp';
                $image->image_path = explode('.', $image->image_path)[0].'.webp';
            }
            
            return $image;
        });

        return view('images_container', compact('images', 'selected'));
    }

    public function count_production_to_receive(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        return DB::connection('mysql_mes')->table('production_order AS po')
            ->whereNotIn('po.status', ['Cancelled', 'Stopped'])
            ->whereIn('po.fg_warehouse', $allowed_warehouses)
            ->where('po.fg_warehouse', 'P2 - Housing Temporary - FI')
            ->where('po.produced_qty', '>', 0)
            ->whereRaw('po.produced_qty > feedback_qty')
            ->count();
    }

    // /production_to_receive
    public function view_production_to_receive(Request $request){
        if(!$request->arr){
            return view('production_to_receive');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);
        $list = [];

        $q = MESProductionOrder::whereNotIn('status', ['Cancelled'])->whereIn('fg_warehouse', $allowed_warehouses)
            ->where('fg_warehouse', 'P2 - Housing Temporary - FI')->where('produced_qty', '>', 0)
            ->whereRaw('produced_qty > feedback_qty')->get();

        foreach ($q as $row) {
            $parent_warehouse = $this->get_warehouse_parent($row->fg_warehouse);

            $owner = ucwords(str_replace('.', ' ', explode('@', $row->created_by)[0]));

            $operation_id = ($row->operation_id) ? $row->operation_id : 0;
            $operation_name = MESOperation::find($operation_id);
            $operation_name = ($operation_name) ? $operation_name->operation_name : '--';

            $list[] = [
                'production_order' => $row->production_order,
                'fg_warehouse' => $row->fg_warehouse,
                'sales_order_no' => $row->sales_order,
                'material_request' => $row->material_request,
                'customer' => $row->customer,
                'item_code' => $row->item_code,
                'description' => $row->description,
                'qty_to_receive' => number_format($row->produced_qty - $row->feedback_qty),
                'qty_to_manufacture' => number_format($row->qty_to_manufacture),
                'stock_uom' => $row->stock_uom,
                'parent_warehouse' => $parent_warehouse,
                'owner' => $owner,
                'created_at' =>  Carbon::parse($row->created_at)->format('M-d-Y h:i A'),
                'operation_name' => $operation_name,
                'delivery_date' => ($row->delivery_date) ? Carbon::parse($row->delivery_date)->format('M-d-Y') : null,
                'delivery_status' => ($row->delivery_date) ? ((Carbon::parse($row->delivery_date) < Carbon::now()) ? 'late' : null) : null
            ];
        }

        return response()->json(['records' => $list]);
    }

    public function create_stock_ledger_entry($stock_entry){
        try {
            $now = Carbon::now();
            $stock_entry_qry = DB::table('tabStock Entry')->where('name', $stock_entry)->first();

            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry)->get();

            if (in_array($stock_entry_qry->purpose, ['Material Transfer for Manufacture', 'Material Transfer'])) {                
                $s_data = [];
                $t_data = [];
                foreach ($stock_entry_detail as $row) {
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->s_warehouse)
                        ->where('item_code', $row->item_code)->first();
                    
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
					    'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                    
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();

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
                $stock_ledger_entry = [];
                foreach ($stock_entry_detail as $row) {
                    $warehouse = ($row->s_warehouse) ? $row->s_warehouse : $row->t_warehouse;
    
                    $bin_qry = DB::table('tabBin')->where('warehouse', $warehouse)
                        ->where('item_code', $row->item_code)->first();

                    $stock_ledger_entry[] = [
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
                        'actual_qty' => ($row->s_warehouse) ? ($row->qty * -1) : $row->qty,
                        'stock_value' => $bin_qry->actual_qty * $bin_qry->valuation_rate,
                        '_comments' => null,
                        'incoming_rate' => ($row->t_warehouse) ? ($row->basic_rate) : 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $bin_qry->valuation_rate,
                        'project' => $stock_entry_qry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $bin_qry->actual_qty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => ($row->s_warehouse) ? ($row->qty * $row->valuation_rate) * -1  : $row->qty * $row->valuation_rate,
                        'posting_date' => $now->format('Y-m-d'),
					    'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stock_ledger_entry);
                }
            }

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function update_bin($stock_entry){
        try {
            $now = Carbon::now();

            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry)->get();

            $latest_id = DB::connection('mysql')->table('tabBin')->where('name', 'like', '%BINM%')->max('name');
            $latest_id = ($latest_id) ? $latest_id : 0;
            $latest_id_exploded = explode("/", $latest_id);
            $new_id = (array_key_exists(1, $latest_id_exploded)) ? $latest_id_exploded[1] + 1 : 1;

            $stock_entry_qry = DB::table('tabStock Entry')->where('name', $stock_entry)->first();

            $stock_entry_detail = DB::table('tabStock Entry Detail')->where('parent', $stock_entry)->get();
            
            $s_data_insert = [];
            $d_data = [];
            foreach($stock_entry_detail as $row){
                if($row->s_warehouse){
                    $bin_qry = DB::table('tabBin')->where('warehouse', $row->s_warehouse)
                    ->where('item_code', $row->item_code)->first();
                    if (!$bin_qry) {
                               
                        $new_id = $new_id + 1;
                        $new_id = str_pad($new_id, 7, '0', STR_PAD_LEFT);
                        $id = 'BINM/'.$new_id;

                        $bin = [
                            'name' => $id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'idx' => 0,
                            'reserved_qty_for_production' => 0,
                            '_liked_by' => null,
                            'fcfs_rate' => 0,
                            'reserved_qty' => 0,
                            '_assign' => null,
                            'planned_qty' => 0,
                            'item_code' => $row->item_code,
                            'actual_qty' => $row->transfer_qty,
                            'projected_qty' => $row->transfer_qty,
                            'ma_rate' => 0,
                            'stock_uom' => $row->stock_uom,
                            '_comments' => null,
                            'ordered_qty' => 0,
                            'reserved_qty_for_sub_contract' => 0,
                            'indented_qty' => 0,
                            'warehouse' => $row->s_warehouse,
                            'stock_value' => $row->valuation_rate * $row->transfer_qty,
                            '_user_tags' => null,
                            'valuation_rate' => $row->valuation_rate,
                        ];

                        DB::table('tabBin')->insert($bin);
                    }else{
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $bin_qry->actual_qty - $row->transfer_qty,
                            'stock_value' => $bin_qry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $bin_qry->valuation_rate,
                        ];
        
                        DB::table('tabBin')->where('name', $bin_qry->name)->update($bin);
                    }
                }

                if($row->t_warehouse){
                    $bin_qry = DB::table('tabBin')->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();
                    if (!$bin_qry) {
                        
                        $new_id = $new_id + 1;
                        $new_id = str_pad($new_id, 7, '0', STR_PAD_LEFT);
                        $id = 'BINM/'.$new_id;

                        $bin = [
                            'name' => $id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'idx' => 0,
                            'reserved_qty_for_production' => 0,
                            '_liked_by' => null,
                            'fcfs_rate' => 0,
                            'reserved_qty' => 0,
                            '_assign' => null,
                            'planned_qty' => 0,
                            'item_code' => $row->item_code,
                            'actual_qty' => $row->transfer_qty,
                            'projected_qty' => $row->transfer_qty,
                            'ma_rate' => 0,
                            'stock_uom' => $row->stock_uom,
                            '_comments' => null,
                            'ordered_qty' => 0,
                            'reserved_qty_for_sub_contract' => 0,
                            'indented_qty' => 0,
                            'warehouse' => $row->t_warehouse,
                            'stock_value' => $row->valuation_rate * $row->transfer_qty,
                            '_user_tags' => null,
                            'valuation_rate' => $row->valuation_rate,
                        ];

                        DB::table('tabBin')->insert($bin);
                    }else{
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $bin_qry->actual_qty + $row->transfer_qty,
                            'stock_value' => $bin_qry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $bin_qry->valuation_rate,
                        ];
        
                        DB::table('tabBin')->where('name', $bin_qry->name)->update($bin);
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => 0, "error" => $e->getMessage(), 'id' => $stock_entry]);
        }
    }
	
	public function create_gl_entry($stock_entry){
        try {
            $now = Carbon::now();
            $stock_entry_qry = DB::table('tabStock Entry')->where('name', $stock_entry)->first();
            $stock_entry_detail = DB::table('tabStock Entry Detail')
                ->where('parent', $stock_entry)
                ->select('s_warehouse', 't_warehouse', DB::raw('SUM((basic_rate * qty)) as basic_amount'), 'parent', 'cost_center', 'expense_account')
                ->groupBy('s_warehouse', 't_warehouse', 'parent', 'cost_center', 'expense_account')
                ->get();
    
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
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
	}

    public function generate_stock_entry($production_order){
        try {
            $now = Carbon::now();

            $stock_entry = StockEntry::with('items')->where('docstatus', 0)->where('work_order', $production_order)->first();
            $stock_entry_items = collect($stock_entry->items)->groupBy('item_code');

            $production_order_details = WorkOrder::with('items')->find($production_order);
            $production_order_items = collect($production_order_details->items)->whereIn('item_code', collect($stock_entry->items)->pluck('item_code'));

            if(!$production_order_items){
                throw new Exception('No item(s) found');
            }

            $stock_entry_detail = [];
            $item_status = 'For Checking';
            foreach($production_order_items as $item){
                $item_status = 'For Checking';
                $docstatus = 0;
                $item_code = $item->item_code;

                $remaining_qty = $item->required_qty - $item->transferred_qty;

                if($remaining_qty < 0){
                    continue;
                }

                $issued_qty = isset($stock_entry_items[$item_code][0]) ? $stock_entry_items[$item_code][0]->qty : 0;

                $actual_qty = Bin::where('item_code', $item->item_code)->where('warehouse', $item->source_warehouse)->sum('actual_qty');

                if(in_array($item->source_warehouse, ['Fabrication - FI', 'Spotwelding Warehouse - FI']) && $actual_qty > $item->required_qty){
                    $item_status = 'Issued';
                    $docstatus = 1;
                }

                $stock_entry_detail[] = [
                    't_warehouse' => $production_order_details->wip_warehouse,
                    'transfer_qty' => (float) $remaining_qty,
                    'expense_account' => 'Cost of Goods Sold - FI',
                    'cost_center' => 'Main - FI',
                    's_warehouse' => $item->source_warehouse,
                    'item_code' => $item->item_code,
                    'qty' => (float) $remaining_qty,
                    'status' => $item_status,
                    'date_modified' => ($item_status == 'Issued') ? $now->toDateTimeString() : null,
                    'session_user' => ($item_status == 'Issued') ? Auth::user()->full_name : null,
                    'remarks' => ($item_status == 'Issued') ? 'MES' : null,
                ];
            }

            if(!$stock_entry_detail){
                return ['success' => 1, 'message' => 'No items found.'];
            }

            $stock_entry_data = [
                'docstatus' => $docstatus,
                'naming_series' => 'STE-',
                'posting_time' => $now->format('h:i:a'),
                'to_warehouse' => $production_order_details->wip_warehouse,
                'company' => 'FUMACO Inc.',
                'purpose' => 'Material Transfer for Manufacture',
                'item_status' => $item_status,
                'posting_date' => $now->format('M d, Y'),
                'posting_datetime' => $now->format('M d, Y h:i:a'),
                'fg_completed_qty' => (float) $production_order_details->qty,
                'title' => 'Material Transfer for Manufacture',
                'project' => $production_order_details->project,
                'work_order' => $production_order,
                'stock_entry_type' => 'Material Transfer for Manufacture',
                'material_request' => $production_order_details->material_request,
                'sales_order_no' => $production_order_details->sales_order_no,
                'transfer_as' => 'Internal Transfer',
                'so_customer_name' => $production_order_details->customer,
                'order_type' => $production_order_details->classification,
                'items' => $stock_entry_detail,
            ];

            $stock_entry_response = $this->erpOperation('post', 'Stock Entry', null, $stock_entry_data);
            if(!isset($stock_entry_response['data'])){
                $err = isset($stock_entry_response['exception']) ? $stock_entry_response['exception'] : 'An error occured while generating Stock Entry';
                throw new Exception($err);
            }

            return ['success' => 1, 'message' => 'Stock Entry has been created.'];
        } catch (Exception $e) {
            return ['success' => 0, 'message' => $e->getMessage()];
        }
    }

    public function submit_stock_entry($id, $system_generated = 0){
        try {
            $now = Carbon::now();
            $draft_ste = StockEntry::with('items')->find($id);
    
            if (!$draft_ste) {
                throw new Exception('Stock Entry not found');
            }
    
            if ($draft_ste->docstatus == 1) {
                throw new Exception('Stock Entry already submitted');
            }
    
            if ($draft_ste->purpose) {
                $count_not_issued_items = collect($draft_ste->items)
                    ->whereNotIn('status', ['Issued', 'Returned'])
                    ->count();
    
                if ($count_not_issued_items) {
                    throw new Exception('All item(s) must be issued.');
                }
            }
    
            $stock_entry_data = ['docstatus' => 1];
    
            $stock_entry_response = $this->erpOperation('put', 'Stock Entry', $id, $stock_entry_data);
    
            if (!isset($stock_entry_response['data'])) {
                $err = isset($stock_entry_response['exception']) ? $stock_entry_response['exception'] : 'An error occurred while submitting Stock Entry';
                throw new Exception($err);
            }
    
            return ['error' => 0, 'modal_title' => 'Success', 'modal_message' => 'Stock Entry Submitted.'];
        } catch (Exception $e) {
            if ($system_generated) {
                return ['error' => 1, 'modal_title' => 'Warning', 'modal_message' => $e->getMessage()];
            }
            return response()->json(['error' => 1, 'modal_title' => 'Warning', 'modal_message' => $e->getMessage()]);
        }
    }

    public function update_production_order_items($production_order){
        if ($production_order) {
            $production_order_items = DB::table('tabWork Order Item')->where('parent', $production_order)->get();
            foreach ($production_order_items as $row) {
                $transferred_qty = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->where('ste.work_order', $production_order)->where('ste.purpose', 'Material Transfer for Manufacture')
                    ->where('ste.docstatus', 1)->where('item_code', $row->item_code)->sum('qty');
    
                $returned_qty = DB::connection('mysql')->table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->where('ste.purpose', 'Material Transfer')->where('ste.transfer_as', 'For Return')
                    ->where('ste.work_order', $production_order)
                    ->where('sted.item_code', $row->item_code)->where('ste.docstatus', 1)
                    ->sum('sted.qty');
                    
                DB::table('tabWork Order Item')
                    ->where('parent', $production_order)->where('item_code', $row->item_code)
                    ->update(['transferred_qty' => $transferred_qty, 'returned_qty' => $returned_qty]);
            }
        }
    }
    
    public function update_stock_entry(Request $request){
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $ste = DB::table('tabStock Entry')->where('name', $request->ste_no)->first();
            
            $ste_items = DB::table('tabStock Entry Detail')->where('parent', $request->ste_no)->get();

            foreach($ste_items as $item){
                $qty = $item->qty / $ste->fg_completed_qty;
                $qty = $qty * $request->qty;
                $rm = [
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'transfer_qty' => $qty,
                    'basic_amount' => $item->basic_rate * $qty,
                    'basic_rate' => $item->basic_rate,
                    'description' => $item->description,
                    'qty' => $qty,
                    'amount' => $item->basic_rate * $qty,
                    'valuation_rate' => $item->basic_rate,
                ];

                DB::table('tabStock Entry Detail')->where('name', $item->name)->update($rm);
            }

            $basic_amount = DB::table('tabStock Entry Detail')->where('parent', $request->ste_no)->sum('basic_amount');

            $stock_entry_data = [
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'fg_completed_qty' => $request->qty,
                'posting_time' => $now->format('H:i:s'),
                'total_outgoing_value' => $basic_amount,
                'total_amount' => $basic_amount,
                'total_incoming_value' => $basic_amount,
                'posting_date' => $now->format('Y-m-d'),
            ];

            DB::table('tabStock Entry')->where('name', $request->ste_no)->update($stock_entry_data);


            $items = DB::table('tabStock Entry Detail')->where('parent', $request->ste_no)->get();
            foreach ($items as $row) {
                if($row->s_warehouse){
                    $actual_qty = DB::table('tabBin')->where('item_code', $row->item_code)
                        ->where('warehouse', $row->s_warehouse)->sum('actual_qty');

                    if($row->qty > $actual_qty){
                        return response()->json(['error' => 1, 'modal_title' => 'Insufficient Stock', 'modal_message' => 'Insufficient stock for ' . $row->item_code . ' in ' . $row->s_warehouse]);
                    }
                }                
            }

            $this->submit_stock_entry($request->ste_no);
            
            DB::commit();

            return response()->json(['error' => 0, 'modal_title' => 'Item Received', 'modal_message' => 'Item has been received.']);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 1, 'modal_title' => 'Warning', 'modal_message' => 'There was a problem creating transaction.']);
        }
    }

    public function get_items(Request $request){
        return DB::table('tabItem')->where('disabled', 0)
            ->where('has_variants', 0)->where('is_stock_item', 1)
            ->when($request->q, function($q) use ($request){
				return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->selectRaw('name as id, name as text, description, stock_uom')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function get_warehouses(Request $request){
        return DB::table('tabWarehouse')
            ->where('disabled', 0)->where('is_group', 0)
            ->when($request->q, function($q) use ($request){
				return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function get_projects(Request $request){
        return DB::table('tabProject')
            ->when($request->q, function($q) use ($request){
				return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function get_sales_persons(Request $request){
        return DB::table('tabSales Person')
            ->where('enabled', 1)->where('is_group', 0)
            ->when($request->q, function($q) use ($request){
				return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function dashboard_data(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $goods_in_transit = 0;
        if (in_array('Goods In Transit - FI', $allowed_warehouses->toArray())) {
            $feedbacked_production_orders_mreq = DB::table('tabMaterial Request as so')
                ->join('tabMaterial Request Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.material_request', 'so.name')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)->where('so.per_ordered', '<', 100)->where('so.company', 'FUMACO Inc.')->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.ordered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)->where('wo.status', '!=', 'Stopped')->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->pluck('wo.name')->toArray();
                
            $feedbacked_production_orders = DB::table('tabSales Order as so')
                ->join('tabSales Order Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.sales_order', 'so.name')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)->where('so.per_delivered', '<', 100)->where('so.company', 'FUMACO Inc.')->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.delivered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)->where('wo.status', '!=', 'Stopped')->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->pluck('wo.name')->toArray();

            $production_orders = array_merge($feedbacked_production_orders_mreq, $feedbacked_production_orders);

            $goods_in_transit = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where([
                    'ste.docstatus' => 1,
                    'ste.purpose' => 'Manufacture',
                    'ste.company' => 'FUMACO Inc.',
                    'sted.status' => 'For Checking',
                    'sted.t_warehouse' => 'Goods in Transit - FI',
                ])
                ->whereIn('ste.work_order', $production_orders)
                ->count();
        }

        $pending_stock_entries = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowed_warehouses)->where('se.docstatus', 0)->where('se.purpose', 'Material Issue')
            ->where('se.issue_as', 'Customer Replacement')->count();

        return [
            'goods_in_transit' => $goods_in_transit,
            'p_replacements' => $pending_stock_entries,
        ];
    }

    public function get_reserved_qty($item_code, $warehouse){
        // $reserved_qty_for_website = DB::table('tabBin')->where('item_code', $item_code)
        //     ->where('warehouse', $warehouse)->sum('website_reserved_qty');
        $reserved_qty_for_website = 0;

        $stock_reservation_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

        $consumed_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');

        return ($reserved_qty_for_website + $stock_reservation_qty) - $consumed_qty;
    }

    public function get_item_images($item_code){
        $images = DB::table('tabItem Images')->where('parent', $item_code)->orderBy('idx', 'asc')->pluck('image_path', 'name');

        return collect($images)->map(function ($image){
            return $this->base64_image("/img/$image");
        });
    }

    public function set_reservation_as_expired(){
        return DB::table('tabStock Reservation')->where('type', 'In-house')
            ->where('status', 'Active')->whereDate('valid_until', '<=', Carbon::now())
            ->update(['status' => 'Expired']);
    }

    public function get_low_stock_level_items(Request $request){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $item_default_warehouses = DB::table('tabItem Default')->whereIn('default_warehouse', $allowed_warehouses)->pluck('parent');
        
        $query = DB::table('tabItem as i')->join('tabItem Reorder as ir', 'i.name', 'ir.parent')
            ->select('ir.name as id', 'i.item_code', 'i.description', 'ir.warehouse', 'ir.warehouse_reorder_level', 'i.stock_uom', 'ir.warehouse_reorder_qty', 'i.item_classification')
            ->whereIn('i.name', $item_default_warehouses)->get();

        $item_images = DB::table('tabItem Images')->whereIn('parent', collect($query)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');

        $low_level_stocks = [];
        foreach ($query as $a) {
            $actual_qty = $this->get_actual_qty($a->item_code, $a->warehouse);

            if($actual_qty <= $a->warehouse_reorder_level) {
                $existing_mr = DB::table('tabMaterial Request as mr')
                    ->join('tabMaterial Request Item as mri', 'mr.name', 'mri.parent')
                    ->where('mr.docstatus', '<', 2)->where('mr.status', 'Pending')->where('mri.item_code', $a->item_code)
                    ->whereBetween('mr.transaction_date', [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
                    ->where('mri.warehouse', $a->warehouse)->select('mr.name')->first();

                $item_image = isset($item_images[$a->item_code]) ? '/img/'.$item_images[$a->item_code] : '/icon/no_img.webp';
                $item_image = $this->base64_image($item_image);

                $low_level_stocks[] = [
                    'id' => $a->id,
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_classification' => $a->item_classification,
                    'stock_uom' => $a->stock_uom,
                    'warehouse' => $a->warehouse,
                    'warehouse_reorder_level' => $a->warehouse_reorder_level,
                    'warehouse_reorder_qty' => $a->warehouse_reorder_qty,
                    'actual_qty' => $actual_qty,
                    'image' => $item_image,
                    'existing_mr' => ($existing_mr) ? $existing_mr->name : null
                ];
            }
        }

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($low_level_stocks);
        // Define how many items we want to be visible in each page
        $perPage = 6;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $low_level_stocks = $paginatedItems;

        return view('tbl_low_level_stocks', compact('low_level_stocks'));
    }

    public function get_reserved_items(Request $request){ // reserved items
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);
        
        $q = DB::table('tabStock Reservation as sr')
            ->join('tabItem as ti', 'sr.item_code', 'ti.name')
            ->groupby('sr.item_code', 'sr.warehouse', 'sr.description', 'sr.stock_uom', 'ti.item_classification')
            ->whereIn('sr.warehouse', $allowed_warehouses)
            ->whereNotIn('sr.status', ['Cancelled', 'Expired'])
            ->orderBy('sr.creation', 'desc')
            ->select('sr.item_code', DB::raw('sum(sr.reserve_qty) as qty'), 'sr.warehouse', 'sr.description', 'sr.stock_uom', 'ti.item_classification')
            ->get();

        $item_images = DB::table('tabItem Images')->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');

        $list = [];
        foreach($q as $row){
            // $item_image_path = DB::table('tabItem Images')->where('parent', $row->item_code)->orderBy('idx', 'asc')->first();
            $image = isset($item_images[$row->item_code]) ? '/img/'.$item_images[$row->item_code] : '/icon/no_icon.png';
            $image = $this->base64_image($image);

            $list[] = [
                'item_code' => $row->item_code,
                'item_classification' => $row->item_classification,
                'description' => $row->description,
                'qty' => $row->qty * 1,
                'warehouse' => $row->warehouse,
                'stock_uom' => $row->stock_uom,
                'image' => $image
            ];
        }

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($list);
        // Define how many items we want to be visible in each page
        $perPage = 8;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems= new LengthAwarePaginator($currentPageItems , count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $list = $paginatedItems;

        return view('reserved_items', compact('list')); // reserved items
    }

    public function invAccuracyChart($year){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $chart_data = [];
        $months = ['0', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $month_no = $year == date('Y') ? date('m') : 12;
        for ($i = 1; $i <= $month_no; $i++) {
            $inv_audit = DB::table('tabMonthly Inventory Audit')
                ->whereIn('warehouse', $allowed_warehouses)
                ->select('name', 'item_classification', 'average_accuracy_rate', 'warehouse', 'percentage_sku')
                ->whereYear('from', $year)->whereMonth('from', $i)
                ->where('docstatus', '<', 2)->get();

            $average = collect($inv_audit)->avg('average_accuracy_rate');

            $chart_data[] = [
                'month_no' => $i,
                'month' => $months[$i],
                'audit_per_month' => $inv_audit,
                'average' => round($average, 2),
            ];
        }

        return response()->json($chart_data);
    }

    public function monthly_inventory_audit(Request $request){
        $assigned_consignment_store = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $from = $request->date ? Carbon::parse(explode(' - ', $request->date)[0]) : Carbon::now()->subDays(30);
        $to = $request->date ? Carbon::parse(explode(' - ', $request->date)[1]) : Carbon::now();

        $inv_audit = DB::table('tabMonthly Inventory Audit')->whereIn('warehouse', $assigned_consignment_store)->where('docstatus', 1)->whereDate('from', '>=', $from)->whereDate('to', '<=', $to)
            ->when($request->search, function ($q) use ($request){
                return $q->where('name', 'like', '%'.$request->search.'%');
            })
            ->when($request->store, function ($q) use ($request){
                return $q->where('warehouse', $request->store);
            })
            ->select('name', 'warehouse', 'from', 'to', 'audited_by', 'employee_name', 'average_accuracy_rate')->orderBy('creation', 'desc')->paginate(20);

        return view('monthly_inv_audit', compact('inv_audit', 'assigned_consignment_store'));
    }

    public function returns(){
        return view('returns');
    }

    // /replacements
    public function replacements(Request $request){
        if(!$request->arr){
            return view('replacement');
         }
         
         $user = Auth::user()->frappe_userid;
         $allowed_warehouses = $this->user_allowed_warehouse($user);
 
         $q = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowed_warehouses)->where('se.docstatus', 0)
            ->where('se.purpose', 'Material Issue')->where('se.issue_as', 'Customer Replacement')
            ->select('sed.status', 'sed.validate_item_code', 'se.sales_order_no', 'sed.parent', 'sed.name', 'sed.t_warehouse', 'sed.s_warehouse', 'sed.item_code', 'sed.description', 'sed.uom', 'sed.qty', 'sed.owner', 'se.material_request', 'se.creation', 'se.delivery_date', 'se.issue_as')
            ->orderByRaw("FIELD(sed.status, 'For Checking', 'Issued') ASC")
            ->get();
 
         $list = [];
         foreach ($q as $d) {
             $available_qty = $this->get_available_qty($d->item_code, $d->s_warehouse);
 
             if($d->material_request){
                 $customer = DB::table('tabMaterial Request')->where('name', $d->material_request)->first();
             }else{
                 $customer = DB::table('tabSales Order')->where('name', $d->sales_order_no)->first();
             }
 
             $ref_no = ($customer) ? $customer->name : null;
             $customer = ($customer) ? $customer->customer : null;
 
             $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');
             $part_nos = implode(', ', $part_nos->toArray());
 
             $owner = DB::table('tabUser')->where('email', $d->owner)->first();
             $owner = ($owner) ? $owner->full_name : null;
 
             $parent_warehouse = $this->get_warehouse_parent($d->s_warehouse);
 
             $list[] = [
                 'customer' => $customer,
                 'item_code' => $d->item_code,
                 'description' => $d->description,
                 's_warehouse' => $d->s_warehouse,
                 't_warehouse' => $d->t_warehouse,
                 'uom' => $d->uom,
                 'name' => $d->name,
                 'owner' => $owner,
                 'parent' => $d->parent,
                 'part_nos' => $part_nos,
                 'qty' => $d->qty,
                 'validate_item_code' => $d->validate_item_code,
                 'status' => $d->status,
                 'available_qty' => $available_qty,
                 'ref_no' => $ref_no,
                 'issue_as' => $d->issue_as,
                 'parent_warehouse' => $parent_warehouse,
                 'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                 'delivery_date' => ($d->delivery_date) ? Carbon::parse($d->delivery_date)->format('M-d-Y') : null,
                    'delivery_status' => ($d->delivery_date) ? ((Carbon::parse($d->delivery_date) < Carbon::now()) ? 'late' : null) : null
             ];
         }

         return response()->json(['records' => $list]);
    }

    public function feedbacked_in_transit(Request $request){
        if(!$request->arr){
           return view('goods_in_transit');
        }
        
        $list = [];
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        if(in_array('Goods In Transit - FI', collect($allowed_warehouses)->toArray())){
            // Get List of Transfered from In Transit to Finished Goods
            $stes_to_fg = DB::table('tabStock Entry')->where('docstatus', '<', 2)->where('company', 'FUMACO Inc.')->where('from_warehouse', 'Goods in Transit - FI')->where('to_warehouse', 'Finished Goods - FI')->where('purpose', 'Material Transfer')->get()->groupBy('docstatus');

            $transferred_to_fg = isset($stes_to_fg[1]) ? collect($stes_to_fg[1])->pluck('name')->unique() : [];
            $draft_fg = [];
            if(isset($stes_to_fg[0])){
                $draft_fg = collect($stes_to_fg[0])->groupBy('reference_no')->map(function ($q){
                    return $q[0];
                });
            }

            $sales_orders = DB::table('tabSales Order as so')
                ->join('tabSales Order Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.sales_order', 'so.name')
                ->join('tabStock Entry as ste', 'ste.work_order', 'wo.name')
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                ->where('ste.purpose', 'Manufacture')->where('ste.docstatus', 1)->where('ste.company', 'FUMACO Inc.')
                ->where('sted.t_warehouse', 'Goods in Transit - FI')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)->where('so.per_delivered', '<', 100)->where('so.company', 'FUMACO Inc.')->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.delivered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)->where('wo.status', '!=', 'Stopped')->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->when($transferred_to_fg, function ($q) use ($transferred_to_fg){
                    return $q->whereNotIn('soi.name', $transferred_to_fg);
                })
                ->select('so.name as so_name', 'so.customer', 'wo.sales_order as wo_so', 'wo.name as name', 'wo.owner', 'wo.production_item as production_item', 'wo.modified', 'soi.name as soi_name', 'soi.item_code as soi_item_code', 'so.status as so_status', 'soi.qty as so_qty', 'wo.produced_qty as qty', 'soi.delivered_qty', 'so.creation', 'soi.item_code', 'soi.description', 'soi.uom', 'sted.name as sted_name', 'sted.status as sted_status', 'sted.date_modified', 'sted.session_user', 'sted.qty as ste_qty', 'ste.name as ste_name');

            $q = DB::table('tabMaterial Request as so')
                ->join('tabMaterial Request Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.material_request', 'so.name')
                ->join('tabStock Entry as ste', 'ste.work_order', 'wo.name')
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                ->where('ste.purpose', 'Manufacture')->where('ste.docstatus', 1)->where('ste.company', 'FUMACO Inc.')
                ->where('sted.t_warehouse', 'Goods in Transit - FI')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)->where('so.per_ordered', '<', 100)->where('so.company', 'FUMACO Inc.')->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.ordered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)->where('wo.status', '!=', 'Stopped')->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->when($transferred_to_fg, function ($q) use ($transferred_to_fg){
                    return $q->whereNotIn('soi.name', $transferred_to_fg);
                })
                ->select('so.name as so_name', 'so.customer', 'wo.material_request as wo_so', 'wo.name as name', 'wo.owner', 'wo.production_item as production_item', 'wo.modified', 'soi.name as soi_name', 'soi.item_code as soi_item_code', 'so.status as so_status', 'soi.qty as so_qty', 'wo.produced_qty as qty', 'soi.ordered_qty as delivered_qty', 'so.creation', 'soi.item_code', 'soi.description', 'soi.uom', 'sted.name as sted_name', 'sted.status as sted_status', 'sted.date_modified', 'sted.session_user', 'sted.qty as ste_qty', 'ste.name as ste_name')
                ->unionAll($sales_orders)
                ->orderBy('modified')->get();

            $production_orders = collect($q)->pluck('name');

            $mes_feedback_logs = DB::connection('mysql_mes')
                ->table('feedbacked_logs')->whereIn('production_order', $production_orders)
                ->where('status', 'Submitted')
                ->select('ste_no', DB::raw('CONCAT(transaction_date, " ", transaction_time) as feedback_date'), 'feedbacked_qty', 'created_by')
                ->orderByDesc('last_modified_at')->get()->groupBy('ste_no');

            $owners = DB::table('tabUser')->whereIn('email', collect($q)->pluck('owner'))->pluck('full_name', 'email');

            foreach ($q as $d) {
                $feedback_date = Carbon::parse($d->modified)->format('M. d, Y - h:i A');
                $feedback_qty = 0;
                $feedback_by = null;
                if(isset($mes_feedback_logs[$d->ste_name])) {
                    $feedback_details = $mes_feedback_logs[$d->ste_name][0];
                    $feedback_date = Carbon::parse($feedback_details->feedback_date)->format('M. d, Y - h:i A');
                    $feedback_qty = $feedback_details->feedbacked_qty;
                    $feedback_by = $feedback_details->created_by;
                }

                $duration_in_transit = $date_confirmed = $received_by = null;

                $sted_name = $d->sted_name;
                $sted_status = $d->sted_status == 'For Checking' ? 'Pending to Receive' : $d->sted_status;
                if(in_array($sted_status, ['Received', 'Issued'])){
                    $date_confirmed = Carbon::parse($d->date_modified);
                    $received_by = $d->session_user;
                    $duration_in_transit = Carbon::parse($date_confirmed)->diff(Carbon::now())->days.' Day(s)';
                }

                $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');
                $part_nos = implode(', ', $part_nos->toArray());

                $owner = isset($owners[$d->owner]) ? $owners[$d->owner] : $d->owner;
                $owner = ucwords(str_replace('.', ' ', explode('@', $owner)[0]));
                $feedback_by = ucwords(str_replace('.', ' ', explode('@', $feedback_by)[0]));
                $received_by = ucwords(str_replace('.', ' ', explode('@', $received_by)[0]));

                $list[] = [
                    'item_code' => $d->item_code,
                    'description' => strip_tags($d->description),
                    'uom' => $d->uom,
                    'name' => $d->name, // work/production order number
                    'reference' => $d->so_name, // sales_order
                    'owner' => $owner,
                    'qty' => number_format($d->ste_qty),
                    'feedback_qty' => $feedback_qty,
                    'feedback_date' => $feedback_date,
                    'feedback_by' => $feedback_by,
                    'received_by' => $received_by,
                    'duration_in_transit' => $duration_in_transit,
                    'date_confirmed' => $date_confirmed ? $date_confirmed->format('M. d, Y - h:i A') : null,
                    'status' => $sted_status,
                    'sted_name' => $sted_name,
                    'soi_name' => $d->soi_name,
                    'customer' => $d->customer,
                    'reference_to_fg' => $sted_status == 'Issued' && isset($draft_fg[$d->soi_name]) ? $draft_fg[$d->soi_name]->name : null
                ];
            }
        }

        $list = collect($list)->sortBy([
            ['status', 'asc'],
            ['duration_in_transit', 'desc'],
        ])->values()->all();
        
        return response()->json(['records' => $list]);
    }

    // /in_transit/receive
    public function receive_transit_stocks(Request $request, $id){
        DB::beginTransaction();
        try {
            $update = [
                'status' => 'Received',
                'modified' => Carbon::now()->toDateTimeString(),
                'date_modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'session_user' => Auth::user()->wh_user
            ];

            DB::table('tabStock Entry Detail')->where('name', $id)->update($update);

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Stocks Received!']);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['success' => 0, 'message' => 'An error occured. Please try again.']);
        }
    }

    // /in_transit/transfer
    public function transfer_transit_stocks($id, Request $request){
        DB::beginTransaction();
        try {
            $doctype = $request->reference_doctype == 'SO' ? 'tabStock Entry' : 'tabMaterial Request';
            $doctype_child = $request->reference_doctype == 'SO' ? 'tabStock Entry Detail' : 'tabMaterial Request Item';

            $stock_entry_detail = DB::table("$doctype as ste")
                ->join("$doctype_child as sted", 'sted.parent', 'ste.name')
                ->leftJoin('tabItem Images as img', function ($q){
                    return $q->on('img.parent', 'sted.item_code');
                })
                ->where('sted.name', $id)
                ->select('ste.*', 'sted.*', 'img.image_path as image')
                ->first();

            if(!$stock_entry_detail){
                return response()->json(['success' => 0, 'message' => 'Stock Entry not found.']);
            }

            $image = $stock_entry_detail->image ? $stock_entry_detail->image : '/icon/no_img.png';

            $sales_order = DB::table('tabSales Order')->where('name', $stock_entry_detail->sales_order_no)->first();

            if(!$sales_order){
                return response()->json(['success' => 0, 'message' => 'Sales Order '.$stock_entry_detail->sales_order_no.' not found.']);
            }

            $response = $this->erpOperation('post', 'Stock Entry', null, $body = [
                'doctype' => 'Stock Entry',
                'purpose' => 'Material Transfer',
                'stock_entry_type' => 'Material Transfer',
                'docstatus' => 0,
                'item_status' => 'For Checking',
                'company' => 'FUMACO Inc.',
                'order_from' => 'Other Reference',
                'transfer_as' => 'Internal Transfer',
                'reference_no' => $request->ref_no,
                'from_warehouse' => 'Goods in Transit - FI',
                'to_warehouse' => 'Finished Goods - FI',
                'owner' => Auth::user()->wh_user,
                'items' => [
                    [
                        'item_code' => $stock_entry_detail->item_code,
                        'qty' => $stock_entry_detail->qty,
                        'transfer_qty' => $stock_entry_detail->transfer_qty,
                        's_warehouse' => 'Goods in Transit - FI',
                        't_warehouse' => 'Finished Goods - FI'
                    ]
                ]
            ]);

            if(!isset($response['data'])){
                return response()->json(['success' => 0, 'message' => 'An error occured. Please try again.']);
            }

            $data = $response['data'];

            DB::table($doctype_child)->where('name', $id)->update([
                'status' => 'Issued',
                'modified' => Carbon::now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user
            ]);

            $email_data = [
                'id' => $data['name'],
                'uom' => $stock_entry_detail->uom,
                'user' => Auth::user()->wh_user,
                'image' => $image,
                'status' => 'For Checking',
                'purpose' => 'Material Transfer',
                'item_code' => $stock_entry_detail->item_code,
                'description' => $stock_entry_detail->description,
                'transfer_qty' => $stock_entry_detail->transfer_qty,
                'transaction_date' => Carbon::now()->format('M. d, Y'),
                'source_warehouse' => $data['from_warehouse'],
                'target_warehouse' => $data['to_warehouse']
            ];

            $email_sent = 1;
            try {
                Mail::mailer('local_mail')->send('mail_template.transit_to_fg', $email_data, function($message) use ($sales_order){
                    $message->to($sales_order->owner);
                    $message->subject('AthenaERP - Material Transfer');
                });
            } catch (\Throwable $th) {
                $email_sent = 0;
            }
            

            DB::commit();

            return response()->json(['success' => 1, 'message' => 'Stock Entry created!', 'email_sent' => $email_sent]);
        } catch (Exception $th) {
            // throw $th;
            DB::rollback();
            return response()->json(['success' => 0, 'message' => 'An error occured. Please try again.']);
        }
    }

    public function get_available_qty($item_code, $warehouse){
        $reserved_qty = $this->get_reserved_qty($item_code, $warehouse);
        $actual_qty = $this->get_actual_qty($item_code, $warehouse);
        $issued_qty = $this->get_issued_qty($item_code, $warehouse);

        $available_qty = ($actual_qty - $issued_qty);
        $available_qty = ($available_qty - $reserved_qty);

        return ($available_qty < 0) ? 0 : $available_qty;
    }

    public function view_deliveries(Request $request){
        if (!$request->arr) {
            return view('picking_slip');
        }

        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $pickingSlipQueryProductBundle = DB::table('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->join('tabPacked Item as pi', 'pi.name', '=', 'psi.pi_detail')
            ->where([
                'dr.docstatus' => 0, 'ps.docstatus' => 0
            ])
            ->whereIn('dri.warehouse', $allowed_warehouses)
            ->select([
                'dr.delivery_date', 'ps.sales_order', DB::raw('NULL as sales_order_no'), 'psi.name AS id', 'psi.status', 'ps.name',
                'ps.delivery_note', 'pi.parent_item', 'pi.name as piName', 'dri.qty', 'pi.item_code', 'pi.description',
                'dri.uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation', 'pi.qty as piQty', 'pi.warehouse as piWarehouse', 'pi.uom as piUom', DB::raw('"packed_item" as type')
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC")
            ->get();

            // Fetch picking slips
        $pickingSlipQuery = DB::table('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->whereRaw('dri.item_code = psi.item_code')
            ->where([
                'dr.docstatus' => 0, 'ps.docstatus' => 0
            ])
            ->whereIn('dri.warehouse', $allowed_warehouses)
            ->select([

                'dr.delivery_date', 'ps.sales_order', 'ps.sales_order', DB::raw('NULL as sales_order_no'), 'psi.name AS id', 'psi.status', 'ps.name',
                'ps.delivery_note', 'psi.item_code', 'psi.description', DB::raw('SUM(dri.qty) as qty'),
                'dri.uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation', DB::raw('"picking_slip" as type')
            ])
            ->groupBy([
                'dr.delivery_date', 'ps.sales_order', 'sales_order_no', 'psi.name', 'psi.status', 'ps.name',
                'ps.delivery_note', 'psi.item_code', 'psi.description', 'dri.uom',
                'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation', 'type'
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC")
            ->get();
        
        // Fetch stock entries
        $stockEntryQuery = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowed_warehouses)
            ->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->select([
                'ste.delivery_date', 'sted.status', 'sted.validate_item_code', DB::raw('NULL as sales_order'), 'ste.sales_order_no',
                'ste.customer_1', 'sted.parent', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse',
                'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 
                'ste.material_request', 'ste.creation', 'ste.transfer_as', 'sted.name as id', 'sted.stock_uom', DB::raw('"stock_entry" as type')
            ])
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();
        
        $allItems = $pickingSlipQuery->merge($stockEntryQuery);
        
        // Gather all item codes to minimize queries for supplier part numbers and owners
        $itemCodes = $allItems->pluck('item_code')->unique();
        $owners = $allItems->pluck('owner')->unique();
        
        if ($pickingSlipQueryProductBundle->count() > 0) {
            $itemCodes = $itemCodes->merge($pickingSlipQueryProductBundle->pluck('parent_item')->unique());
            $owners = $owners->merge($pickingSlipQueryProductBundle->pluck('owner')->unique());
        } 
        
        // Fetch supplier part numbers for all items in one query
        $supplierPartNos = DB::table('tabItem Supplier')
            ->whereIn('parent', $itemCodes)->pluck('supplier_part_no', 'parent');
        
        // Fetch owners' full names in one query
        $ownerNames = DB::table('tabUser')
            ->whereIn('email', $owners)->pluck('full_name', 'email');
        
        $list = [];
        foreach ($allItems as $d) {
            $part_nos = $supplierPartNos->get($d->item_code, '');
            $owner = $ownerNames->get($d->owner, null);
            $parent_warehouse = $this->get_warehouse_parent($d->warehouse ?? $d->s_warehouse);
        
            $list[] = [
                'owner' => $owner,
                'warehouse' => $d->warehouse ?? $d->s_warehouse,
                'customer' => $d->customer ?? $d->customer_1,
                'sales_order' => $d->sales_order ?? $d->sales_order_no,
                'id' => $d->id,
                'part_nos' => $part_nos,
                'status' => $d->status,
                'name' => $d->name,
                'delivery_note' => $d->delivery_note ?? null,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'qty' => $d->qty,
                'stock_uom' => $d->uom ?? $d->stock_uom,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'type' => $d->type, // isset($d->sales_order_no) && $d->sales_order_no ? 'stock_entry' : 'picking_slip',
                'classification' => $d->transfer_as ?? 'Customer Order',
                'delivery_date' => Carbon::parse($d->delivery_date)->format('M-d-Y'),
                'delivery_status' => (Carbon::parse($d->delivery_date) < Carbon::now()) ? 'late' : null,
            ];
        }

        if ($pickingSlipQueryProductBundle->count() > 0) {
            $pickingSlipQueryProductBundle = $pickingSlipQueryProductBundle->groupBy('parent_item');
            foreach ($pickingSlipQueryProductBundle as $parent_item => $rows) {
                $part_nos = $supplierPartNos->get($parent_item, '');
                $owner = $ownerNames->get($rows[0]->owner, null);
                $parent_warehouse = $this->get_warehouse_parent($rows[0]->warehouse);
    
                $list[] = [
                    'owner' => $owner,
                    'warehouse' => $rows[0]->warehouse,
                    'customer' => $rows[0]->customer,
                    'sales_order' => $rows[0]->sales_order,
                    'id' => $rows[0]->id,
                    'part_nos' => $part_nos,
                    'status' => $rows[0]->status,
                    'name' => $rows[0]->name,
                    'delivery_note' => $rows[0]->delivery_note ?? null,
                    'item_code' => $parent_item,
                    'description' => $rows[0]->description,
                    'qty' => $rows[0]->qty,
                    'stock_uom' => $rows[0]->uom,
                    'parent_warehouse' => $parent_warehouse,
                    'creation' => Carbon::parse($rows[0]->creation)->format('M-d-Y h:i:A'),
                    'type' => 'packed_item',
                    'classification' => 'Customer Order',
                    'delivery_date' => Carbon::parse($rows[0]->delivery_date)->format('M-d-Y'),
                    'delivery_status' => (Carbon::parse($rows[0]->delivery_date) < Carbon::now()) ? 'late' : null,
                ];
            }
        }
        
        return response()->json(['picking' => $list]);
    }

    // /picking_slip
    public function view_picking_slip() {
        return view('picking_slip');
    }

    public function get_athena_logs(Request $request) {
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $date = Carbon::now();

        $startOfYear = $date->copy()->startOfYear();
        $endOfYear = $date->copy()->endOfYear();

        $stock_adjustments_query =  DB::table('tabStock Ledger Entry as sle')
        ->where('voucher_type', 'Stock Reconciliation')->join('tabItem as i', 'i.name', 'sle.item_code')
            ->whereIn('sle.warehouse', $allowed_warehouses)->whereBetween('sle.creation', [$startOfYear, $endOfYear])
            ->whereMonth('sle.creation', $request->month)
            ->select('sle.creation as transaction_date', 'voucher_type as transaction_type', 'sle.item_code', 'i.description', 'sle.warehouse as s_warehouse', 'sle.warehouse as t_warehouse', 'sle.qty_after_transaction as qty', 'sle.voucher_no as reference_no', 'sle.voucher_no as reference_parent', 'sle.owner as user');

        // check in transactions 
        $checkin_transactions = DB::table('tabAthena Transactions')->whereIn('target_warehouse', $allowed_warehouses)
            ->whereNull('source_warehouse')->whereBetween('transaction_date', [$startOfYear, $endOfYear])
            ->whereMonth('transaction_date', $request->month)->where('status', 'Issued')
            ->select('transaction_date', 'transaction_type', 'item_code', 'description', 'source_warehouse as s_warehouse', 'target_warehouse as t_warehouse', 'qty', 'reference_no', 'reference_parent', 'warehouse_user as user')
            ->orderBy('transaction_date', 'desc');

        $list = DB::table('tabAthena Transactions')->whereIn('source_warehouse', $allowed_warehouses)
            ->whereBetween('transaction_date', [$startOfYear, $endOfYear])
            ->whereMonth('transaction_date', $request->month)->where('status', 'Issued')
            ->select('transaction_date', 'transaction_type', 'item_code', 'description', 'source_warehouse as s_warehouse', 'target_warehouse as t_warehouse', 'qty', 'reference_no', 'reference_parent', 'warehouse_user as user')
            ->orderBy('transaction_date', 'desc')->union($stock_adjustments_query)->union($checkin_transactions)->orderBy('transaction_date', 'desc')->get();

        return view('tbl_athena_logs', compact('list'));
    }

    public function update_reservation_status(){
        // update status expired
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
    }

    public function create_material_request($id){
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $latest_mr = DB::table('tabMaterial Request')->max('name');
            $latest_mr_exploded = explode("-", $latest_mr);
            $new_id = $latest_mr_exploded[1] + 1;
            $new_id = str_pad($new_id, 5, '0', STR_PAD_LEFT);
            $new_id = 'PREQ-'.$new_id;
    
            $itemDetails = DB::table('tabItem as i')->join('tabItem Reorder as ir', 'i.name', 'ir.parent')->where('ir.name', $id)->first();
            
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $itemDetails->item_code . '</b> not found.']);
            }
    
            if($itemDetails->is_stock_item == 0){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $itemDetails->item_code . '</b> is not a stock item.']);
            }
    
            $actual_qty = $this->get_actual_qty($itemDetails->item_code, $itemDetails->warehouse);

            $mr = [
                'name' => $new_id,
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'naming_series' => 'PREQ-',
                'title' => $itemDetails->material_request_type,
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'company' => 'FUMACO Inc.',
                'schedule_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'material_request_type' => $itemDetails->material_request_type,
                'purchase_request' => 'Local',
                'notes00' => 'Generated from AthenaERP',
            ];
    
            $mr_item = [
                'name' => 'ath'.uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'parent' => $new_id,
                'parentfield' => 'items',
                'parenttype' => 'Material Request',
                'idx' => 1,
                'stock_qty' => abs($itemDetails->warehouse_reorder_qty),
                'qty' => abs($itemDetails->warehouse_reorder_qty),
                'actual_qty' => $actual_qty,
                'schedule_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'item_name' => $itemDetails->item_name,
                'stock_uom' => $itemDetails->stock_uom,
                'warehouse' => $itemDetails->warehouse,
                'uom' => $itemDetails->stock_uom,
                'description' => $itemDetails->description,
                'conversion_factor' => 1,
                'item_code' => $itemDetails->item_code,
                'item_group' => $itemDetails->item_group,
            ];
    
            DB::table('tabMaterial Request')->insert($mr);
            DB::table('tabMaterial Request Item')->insert($mr_item);

            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Material Request for <b>' . $itemDetails->item_code . '</b> has been created.']);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
        }
    }

    public function consignment_warehouses(Request $request){
        return DB::table('tabWarehouse')
            ->where('disabled', 0)->where('is_group', 0)
            ->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->when($request->q, function($q) use ($request){
				return $q->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')->limit(10)->get();
    }

    public function get_dr_return_details($id){
        $q = DB::table('tabDelivery Note as dr')->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
            ->where('dr.is_return', 1)->where('dr.docstatus', 0)->where('dri.name', $id)
            ->select('dri.barcode_return', 'dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status', 'dri.stock_uom', 'dr.owner', 'dr.docstatus')->first();

        $img = DB::table('tabItem Images')->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64_image($img);

        $owner = ucwords(str_replace('.', ' ', explode('@', $q->owner)[0]));

        $available_qty = $this->get_available_qty($q->item_code, $q->warehouse);

        $data = [
            'name' => $q->c_name,
            't_warehouse' => $q->warehouse,
            'available_qty' => $available_qty,
            'validate_item_code' => $q->barcode_return,
            'img' => $img,
            'item_code' => $q->item_code,
            'description' => $q->description,
            'ref_no' => $q->against_sales_order . '<br>' . $q->name,
            'stock_uom' => $q->stock_uom,
            'qty' => abs($q->qty * 1),
            'owner' => $owner,
            'status' => $q->item_status,
            'reference' => 'Delivery Note',
            'docstatus' => $q->docstatus
        ];

        $is_stock_entry = false;
        return view('return_modal_content', compact('data', 'is_stock_entry'));
    }

    public function submit_dr_sales_return_api(Request $request){
        try {
            $child_id = $request->child_tbl_id;
            $delivery_note = DeliveryNote::with('items')->whereHas('items', function ($item) use ($child_id){
                return $item->where('name', $child_id);
            })->first();

            $now = Carbon::now();

            if(!$delivery_note){
                throw new Exception('Record not found');
            }

            if(in_array($delivery_note->item_status, ['Issued', 'Returned'])){
                throw new Exception("Item already $delivery_note->item_status.");
            }

            $delivery_note->qty = (float) $delivery_note->qty;

            $delivery_note_item = collect($delivery_note->items)->where('name', $child_id)->first();
            $item_code = $delivery_note_item->item_code;
            $itemDetails = Item::find($item_code);

            if(!$itemDetails){
                throw new Exception("Item <b>$item_code</b> not found.");
            }

            if(!$itemDetails->is_stock_item){
                throw new Exception("Item <b>$item_code</b> is not a stock item.");
            }

            if($request->barcode != $itemDetails->item_code){
                throw new Exception("Invalid barcode for <b>$itemDetails->item_code</b>.");
            }

            if($request->qty <= 0){
                throw new Exception("Qty cannot be less than or equal to 0.");
            }

            foreach ($delivery_note->items as $item) { // Update only the specific child
                if ($item->name === $child_id) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->item_status = 'Returned';
                    $item->barcode_return = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    break;
                }
            }

            $pending_items = collect($delivery_note->items)->where('name', $child_id)->where('status', 'For Checking')->count();
            if($pending_items <= 0){
                $delivery_note->item_status = 'Returned';
            }

            $response = $this->erpOperation('put', 'Delivery Note', $delivery_note->name, collect($delivery_note)->toArray());
            if(!isset($response['data'])){
                $err = isset($response['exception']) ? $response['exception'] : 'An error occured while updating Delivery Note';
                throw new Exception($err);
            }

            $this->insert_transaction_log('Delivery Note', $request->child_tbl_id);
            return response()->json(['status' => 1, 'message' => "Item <b>$item_code</b> has been returned"]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
        }
    }

     // /submit_dr_sales_return
    public function submit_dr_sales_return(Request $request){
        DB::beginTransaction();
        try {
            $driDetails = DB::table('tabDelivery Note as dr')->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')->where('dri.name', $request->child_tbl_id)
                ->select('dr.name as parent_dr', 'dr.*', 'dri.*', 'dri.item_status as per_item_status', 'dr.docstatus as dr_status')->first();

            if(!$driDetails){
                return response()->json(['status' => 0, 'message' => 'Record not found.']);
            }

            if(in_array($driDetails->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $driDetails->per_item_status . '.']);
            }

            if($driDetails->dr_status == 1){
                return response()->json(['status' => 0, 'message' => 'Item already returned.']);
            }

            $itemDetails = DB::table('tabItem')->where('name', $driDetails->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $driDetails->item_code . '</b> not found.']);
            }

            if($itemDetails->is_stock_item == 0){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $driDetails->item_code . '</b> is not a stock item.']);
            }

            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => 'Invalid barcode for <b>' . $itemDetails->item_code . '</b>.']);
            }

            $values = [
                'session_user' => Auth::user()->wh_user,
                'item_status' => 'Returned',
                'barcode_return' => $request->barcode,
                'date_modified' => Carbon::now()->toDateTimeString()
            ];

            DB::table('tabDelivery Note Item')->where('name', $request->child_tbl_id)->update($values);

            $this->update_pending_dr_item_status();
            $this->insert_transaction_log('Delivery Note', $request->child_tbl_id);

            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Item <b>' . $driDetails->item_code . '</b> has been returned.']);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
        }
    }

    public function update_pending_dr_item_status(){
        $for_return_dr = DB::table('tabDelivery Note')->where('return_status', 'For Return')->where('docstatus', 0)->pluck('name');

        foreach($for_return_dr as $dr){
            $items_for_return = DB::table('tabDelivery Note Item')
                ->where('parent', $dr)->where('item_status', 'For Return')->exists();

            if(!$items_for_return){
                DB::table('tabDelivery Note')->where('name', $dr)->where('docstatus', 0)->update(['return_status' => 'Returned']);
            }
        }
    }
    
    // /create_feedback
    public function create_feedback(Request $request){
        DB::beginTransaction();
		try {
            $now = Carbon::now();
            $production_order = $request->production_order;
            $existing_ste_transfer = StockEntry::where('work_order', $production_order)
                ->where('purpose', 'Material Transfer for Manufacture')
                ->where('docstatus', 1)->exists();

            if(!$existing_ste_transfer){
                throw new Exception('Materials Unavailable');
            }

            if($request->fg_completed_qty <= 0){
                throw new Exception('Please enter received qty');
            }

            $production_order_details = WorkOrder::with('items')->find($production_order);
            $mes_production_order_details = MESProductionOrder::find($production_order);

            if(!$production_order_details || !$mes_production_order_details){
                throw new Exception("Production Order $production_order not found.");
            }

            $item_code = $mes_production_order_details->item_code;
            if($item_code != $request->barcode){
                throw new Exception("Invalid barcode for $item_code.");
            }

			$produced_qty = $production_order_details->produced_qty + $request->fg_completed_qty;

            if($produced_qty >= (int)$production_order_details->qty && $production_order_details->material_transferred_for_manufacturing > 0){
				$pending_mtfm_count = StockEntry::where('work_order', $production_order)->where('purpose', 'Material Transfer for Manufacture')->where('docstatus', 0)->count();
				
				if($pending_mtfm_count){
                    throw new Exception('There are pending material request for issue.');
				}
			}

            $remaining_for_feedback = $mes_production_order_details->produced_qty - $mes_production_order_details->feedback_qty;
            if($remaining_for_feedback < $request->fg_completed_qty){
                throw new Exception("Received quantity cannot be greater than <b>$remaining_for_feedback</b>");
            }

            $remarks_override = $produced_qty > $mes_production_order_details->produced_qty ? "Override" : null;

            if(!$mes_production_order_details->is_stock_item){
				return redirect('/create_bundle_feedback/'. $production_order .'/' . $request->fg_completed_qty);
			}

			$docstatus = $mes_production_order_details->fg_warehouse == 'P2 - Housing Temporary - FI' ? 1 : 0;

			$production_order_items = $this->feedback_production_order_items($production_order, $mes_production_order_details->qty_to_manufacture, $request->fg_completed_qty);

            if(!$production_order_items){
                throw new Exception('No items found.');
            }

            $stock_entry_detail = [];
            foreach($production_order_items as $item){
				$qty = $item['required_qty'];

                $child_item_code = $item['item_code'];

                $stock_entry_detail[] = [
                    'transfer_qty' => $qty,
                    'qty' => $qty,
                    'expense_account' => 'Cost of Goods Sold - FI',
                    's_warehouse' => $production_order_details->wip_warehouse,
                    'cost_center' => 'Main - FI',
                    'item_code' => $child_item_code
                ];
            }

            $stock_entry_detail[] = [
                'expense_account' => 'Cost of Goods Sold - FI',
                'cost_center' => 'Main - FI',
				'item_code' => $production_order_details->production_item,
                't_warehouse' => $mes_production_order_details->fg_warehouse,
				'transfer_qty' => (float) $request->fg_completed_qty,
				'qty' => (float) $request->fg_completed_qty,
            ];

            $stock_entry_data = [
				'naming_series' => 'STE-',
                'docstatus' => $docstatus,
				'fg_completed_qty' => (float) $request->fg_completed_qty,
				'to_warehouse' => $production_order_details->fg_warehouse,
				'company' => 'FUMACO Inc.',
				'bom_no' => $production_order_details->bom_no,
				'project' => $production_order_details->project,
				'work_order' => $production_order,
				'purpose' => 'Manufacture',
                'stock_entry_type' => 'Manufacture',
				'material_request' => $production_order_details->material_request,
				'item_status' => 'Issued',
				'sales_order_no' => $mes_production_order_details->sales_order,
				'transfer_as' => 'Internal Transfer',
				'item_classification' => $production_order_details->item_classification,
				'so_customer_name' => $mes_production_order_details->customer,
				'order_type' => $mes_production_order_details->classification,
                'items' => $stock_entry_detail
            ];

            // MES Transactions
            $manufactured_qty = $production_order_details->produced_qty + $request->fg_completed_qty;
            $status = $manufactured_qty == $production_order_details->qty ? 'Completed' : $mes_production_order_details->status;

            $production_data_mes = [
                'last_modified_at' => $now->toDateTimeString(),
                'last_modified_by' => Auth::user()->wh_user,
                'feedback_qty' => $manufactured_qty,
                'remarks' => $remarks_override
            ];

            if($status == 'Completed'){
                $production_data_mes['status'] = 'Completed';
            }

            if($remarks_override == 'Override'){
                DB::connection('mysql')->table('job_ticket')->where('production_order', $production_order_details->name)
                    ->where('status', '!=', 'Completed')->update([
                        'completed_qty' => $manufactured_qty,
                        'remarks' => $remarks_override,
                        'status' => 'Completed',
                        'last_modified_by' => Auth::user()->wh_user,
                    ]);
            }

            DB::connection('mysql_mes')->table('production_order')->where('production_order', $production_order_details->name)->update($production_data_mes);
            $this->insert_production_scrap($production_order_details->name, $request->fg_completed_qty);

            $feedbacked_timelogs = [
                'production_order'  => $mes_production_order_details->production_order,
                'item_code'     => $production_order_details->production_item,
                'item_name'     => $production_order_details->item_name,
                'feedbacked_qty' => $request->fg_completed_qty, 
                'from_warehouse'=> $production_order_details->wip_warehouse,
                'to_warehouse' => $mes_production_order_details->fg_warehouse,
                'transaction_date'=>$now->format('Y-m-d'),
                'transaction_time' =>$now->format('G:i:s'),
                'created_at'  => $now->toDateTimeString(),
                'created_by'  =>  Auth::user()->wh_user,
            ];

            DB::connection('mysql_mes')->table('feedbacked_logs')->insert($feedbacked_timelogs);

            $stock_entry_response = $this->erpOperation('post', 'Stock Entry', null, $stock_entry_data);
            if(!isset($stock_entry_response['data'])){
                $err = isset($stock_entry_response['exception']) ? $stock_entry_response['exception'] : 'An error occured while creating stock entry';
                throw new Exception($err);
            }

            $this->insert_transaction_log('Stock Entry', $stock_entry_response['data']['name']);
                
			DB::commit();
			return response()->json(['status' => 1, 'message' => 'Stock Entry has been created.']);
		} catch (Exception $e) {
			DB::rollback();
            // throw $e;
			return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
		}
    }

    public function feedback_production_order_items($production_order, $qty_to_manufacture, $fg_completed_qty){
        $production_order_items_qry = DB::table('tabWork Order Item')
            ->where('parent', $production_order)
            ->where(function($q) {
                $q->where('item_alternative_for', 'new_item')
                ->orWhereNull('item_alternative_for');
            })
            ->orderBy('idx', 'asc')->get();

        $arr = [];
        foreach ($production_order_items_qry as $index => $row) {
            $item_required_qty = $row->required_qty;
            $item_required_qty += DB::table('tabWork Order Item')
                ->where('parent', $production_order)
                ->where('item_alternative_for', $row->item_code)
                ->whereNotNull('item_alternative_for')
                ->sum('required_qty');

            $consumed_qty = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.work_order', $production_order)->whereNull('sted.t_warehouse')
                ->where('sted.item_code', $row->item_code)->where('purpose', 'Manufacture')
                ->where('ste.docstatus', 1)->sum('qty');

            $balance_qty = ($row->transferred_qty - $consumed_qty);

            $remaining_required_qty = ($fg_completed_qty - $balance_qty);

            if($balance_qty <= 0 || $fg_completed_qty > $balance_qty){
                $alternative_items_qry = $this->get_alternative_items($production_order, $row->item_code, $remaining_required_qty);
            }else{
                $alternative_items_qry = [];
            }

            $qty_per_item = $item_required_qty / $qty_to_manufacture;
            $per_item = $qty_per_item * $fg_completed_qty;

            $required_qty = ($balance_qty > $per_item) ? $per_item : $balance_qty;

            foreach ($alternative_items_qry as $ai_row) {
                if ($ai_row['required_qty'] > 0) {
                    $arr[] = [
                        'item_code' => $ai_row['item_code'],
                        'item_name' => $ai_row['item_name'],
                        'description' => $ai_row['description'],
                        'stock_uom' => $ai_row['stock_uom'],
                        'required_qty' => $ai_row['required_qty'],
                        'transferred_qty' => $ai_row['transferred_qty'],
                        'consumed_qty' => $ai_row['consumed_qty'],
                        'balance_qty' => $ai_row['balance_qty'],
                    ];
                }
            }

            if($balance_qty > 0){
                $arr[] = [
                    'item_code' => $row->item_code,
                    'item_name' => $row->item_name,
                    'description' => $row->description,
                    'stock_uom' => $row->stock_uom,
                    'required_qty' => $required_qty,
                    'transferred_qty' => $row->transferred_qty,
                    'consumed_qty' => $consumed_qty,
                    'balance_qty' => $balance_qty,
                ];
            }
        }

        return $arr;
    }

    public function get_alternative_items($production_order, $item_code, $remaining_required_qty){
        $q = DB::table('tabWork Order Item')
			->where('parent', $production_order)->where('item_alternative_for', $item_code)
            ->orderBy('required_qty', 'asc')->get();

        $remaining = $remaining_required_qty;
        $arr = [];
        foreach ($q as $row) {
            if($remaining > 0){
                $consumed_qty = DB::table('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->where('ste.work_order', $production_order)->whereNull('sted.t_warehouse')
                    ->where('sted.item_code', $row->item_code)->where('purpose', 'Manufacture')
                    ->where('ste.docstatus', 1)->sum('qty');

                $balance_qty = ($row->transferred_qty - $consumed_qty);
                    
                $required_qty = ($balance_qty > $remaining) ? $remaining : $balance_qty;
                $arr[] = [
                    'item_code' => $row->item_code,
                    'required_qty' => $required_qty,
                    'item_name' => $row->item_name,
                    'description' => $row->description,
                    'stock_uom' => $row->stock_uom,
                    'transferred_qty' => $row->transferred_qty,
                    'consumed_qty' => $consumed_qty,
                    'balance_qty' => $balance_qty
                ];

                $remaining = $remaining - $balance_qty;
            }
        }

        return $arr;
    }

    public function insert_production_scrap($production_order, $qty){
        $production_order_details = DB::connection('mysql_mes')->table('production_order')
                ->where('production_order', $production_order)->first();
        if (!$production_order_details) {
            return response()->json(['success' => 0, 'message' => 'Production Order ' . $production_order . ' not found.']);
        }

        $bom_scrap_details = DB::table('tabBOM Scrap Item')->where('parent', $production_order_details->bom_no)->first();
        if (!$bom_scrap_details) {
            return response()->json(['success' => 0, 'message' => 'BOM ' . $production_order_details->bom_no . ' not found.']);
        }

        $uom_details = DB::connection('mysql_mes')->table('uom')->where('uom_name', 'Kilogram')->first();
        if (!$uom_details) {
            return response()->json(['success' => 0, 'message' => 'UoM Kilogram not found.']);
        }

        $thickness = DB::table('tabItem Variant Attribute')
            ->where('parent', $bom_scrap_details->item_code)->where('attribute', 'like', '%thickness%')->first();

        if($thickness){
            $thickness = $thickness->attribute_value;

            $thickness = str_replace(' ', '', preg_replace("/[^0-9,.]/", "", ($thickness)));

            $material = strtok($bom_scrap_details->item_name, ' ');

            $scrap_qty = $qty * $bom_scrap_details->stock_qty;

            if($material == 'CRS'){
                // get uom conversion
                $uom_arr_1 = DB::connection('mysql_mes')->table('uom_conversion')->join('uom', 'uom.uom_id', 'uom_conversion.uom_id')
                    ->where('uom.uom_name', $bom_scrap_details->stock_uom)->pluck('uom_conversion_id')->toArray();

                $uom_arr_2 = DB::connection('mysql_mes')->table('uom_conversion')
                    ->where('uom_id', $uom_details->uom_id)->pluck('uom_conversion_id')->toArray();

                $uom_conversion_id = array_intersect($uom_arr_1, $uom_arr_2);

                $uom_1_conversion_factor = DB::connection('mysql_mes')->table('uom_conversion')
                    ->where('uom_conversion_id', $uom_conversion_id[0])
                    ->where('uom_id', '!=', $uom_details->uom_id)->sum('conversion_factor');

                $uom_2_conversion_factor = DB::connection('mysql_mes')->table('uom_conversion')
                    ->where('uom_conversion_id', $uom_conversion_id[0])
                    ->where('uom_id', $uom_details->uom_id)->sum('conversion_factor');

                // calculate scrap qty
                $conversion_factor = $uom_2_conversion_factor / $uom_1_conversion_factor;

                $scrap_qty = $scrap_qty * $conversion_factor;

                // get scrap id
                $existing_scrap = DB::connection('mysql_mes')->table('scrap')
                    ->where('material', $material)->where('uom_id', $uom_details->uom_id)
                    ->where('thickness', $thickness)->first();

                if ($existing_scrap) {
                    $scrap_qty = $scrap_qty + $existing_scrap->scrap_qty;
                    $values = [
                        'scrap_qty' => $scrap_qty,
                        'last_modified_by' => Auth::user()->full_name,
                    ];

                    DB::connection('mysql_mes')->table('scrap')->where('scrap_id', $existing_scrap->scrap_id)->update($values);

                    $scrap_id = $existing_scrap->scrap_id;
                }else{
                    $values = [
                        'uom_conversion_id' => $uom_conversion_id[0],
                        'uom_id' => $uom_details->uom_id,
                        'material' => $material,
                        'thickness' => $thickness,
                        'scrap_qty' => $scrap_qty,
                        'created_by' => Auth::user()->full_name,
                    ];
    
                    $scrap_id = DB::connection('mysql_mes')->table('scrap')->insertGetId($values);
                }

                $existing_scrap_reference = DB::connection('mysql_mes')->table('scrap_reference')
                    ->where('reference_type', 'Production Order')->where('reference_id', $production_order)
                    ->where('scrap_id', $scrap_id)->first();

                if ($existing_scrap_reference) {
                    $scrap_qty = $scrap_qty + $existing_scrap->scrap_qty;
                    $values = [
                        'scrap_qty' => $scrap_qty,
                        'last_modified_by' => Auth::user()->full_name,
                    ];

                    DB::connection('mysql_mes')->table('scrap_reference')
                        ->where('scrap_id', $existing_scrap_reference->scrap_reference_id)->update($values);
                }else{
                    $values = [
                        'reference_type' => 'Production Order',
                        'reference_id' => $production_order,
                        'uom_id' => $uom_details->uom_id,
                        'scrap_id' => $scrap_id,
                        'scrap_qty' => $scrap_qty,
                        'created_by' => Auth::user()->full_name,
                    ];
    
                    DB::connection('mysql_mes')->table('scrap_reference')->insert($values);
                }
            }
        }
    }

    // /consignment_sales/{warehouse}
    public function consignmentSalesReport($warehouse, Request $request) {
        $month_names = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $month_name_short = [null, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
        $month_now = (int)Carbon::now()->format('m');
        $year = $request->year ? $request->year : Carbon::now()->format('Y');
        $query = DB::table('tabConsignment Monthly Sales Report')
            ->where('status', '!=', 'Cancelled')
            ->where('fiscal_year', $year)->where('warehouse', $warehouse)
            ->pluck('total_amount', 'month')->toArray();
        
        $result = [];
        for ($i=1; $i <= $month_now; $i++) { 
            $month_index = $month_names[$i];
            $month_i = $month_name_short[$i];
            $result[$month_i] = array_key_exists($month_index, $query) ? $query[$month_index] : 0;
        }

        return [
            'labels' => collect($result)->keys(),
            'data' => array_values($result)
        ];
    }

    public function purchaseRateHistory($item_code) {
        $item_valuation_rates = [];
        $list = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)->where('poi.item_code', $item_code)
            ->select('po.supplier', 'po.name', 'po.transaction_date', 'poi.base_rate', 'po.supplier_group', 'poi.qty', 'poi.stock_uom')
            ->orderBy('po.creation', 'desc')->paginate(10);

        $imported = collect($list->items())->where('supplier_group', 'Imported')->toArray();
        $po_names = array_column($imported, 'name');
        if (count($po_names) > 0) {
            $purchase_receipts = DB::table('tabPurchase Receipt as pr')->join('tabPurchase Receipt Item as pri', 'pr.name', 'pri.parent')
                ->where('pr.docstatus', 1)->whereIn('pri.purchase_order', $po_names)->where('pri.item_code', $item_code)
                ->pluck('pri.purchase_order', 'pr.name')->toArray();

            $purchase_receipt_arr = array_keys($purchase_receipts);

            $last_landed_cost_vouchers = DB::table('tabLanded Cost Voucher as a')->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                ->where('a.docstatus', 1)->where('b.item_code', $item_code)->whereIn('b.receipt_document', $purchase_receipt_arr)
                ->pluck('b.valuation_rate', 'b.receipt_document');

            foreach ($last_landed_cost_vouchers as $pr => $vr) {
                $po = $purchase_receipts[$pr];
                $item_valuation_rates[$po] = $vr;
            }
        }

        return view('tbl_item_purchase_history', compact('list', 'item_valuation_rates'));
    }

    public function avgPurchaseRate($item_code) {
        $list = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)->where('poi.item_code', $item_code)
            ->select('po.supplier', 'po.name', 'po.transaction_date', 'poi.base_rate', 'po.supplier_group')
            ->orderBy('po.creation', 'desc')->get();

        $sum = collect($list)->sum('base_rate');
        $count = collect($list)->count();

        $average = ($sum > 0) ? $sum / $count : 0;

        return '₱ ' . number_format($average, 2, '.', ',');
    }

    public function updateItemCost($item_code, Request $request) {
        if ($request->price > 0) {
            DB::table('tabItem')->where('name', $item_code)->update(['custom_item_cost' => $request->price]);
        }

        $price_settings = DB::table('tabSingles')->where('doctype', 'Price Settings')
            ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])->pluck('value', 'field')->toArray();

        $minimum_price_computation = array_key_exists('minimum_price_computation', $price_settings) ? $price_settings['minimum_price_computation'] : 0;
        $standard_price_computation = array_key_exists('standard_price_computation', $price_settings) ? $price_settings['standard_price_computation'] : 0;
        $is_tax_included_in_rate = array_key_exists('is_tax_included_in_rate', $price_settings) ? $price_settings['is_tax_included_in_rate'] : 0;

        $price = $request->price;

        $standard_price = $price * $standard_price_computation;
        $min_price = $price * $minimum_price_computation;
        if ($is_tax_included_in_rate) {
            $standard_price = ($price * $standard_price_computation) * 1.12;
        }

        $item_cost = '₱ ' . number_format($price, 2, '.', ',');
        $standard_price = '₱ ' . number_format($standard_price, 2, '.', ',');
        $min_price = '₱ ' . number_format($min_price, 2, '.', ',');

        return [
            'item_cost' => $item_cost,
            'standard_price' => $standard_price,
            'min_price' => $min_price
        ];
    }

    public function itemCostList(Request $request) {
        if (!in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            return redirect('/');
        }

        $item_groups = DB::table('tabItem Group')->where('parent_item_group', 'All Item Groups')->select('name', 'is_group')->get();

        return view('search_item_cost', compact('item_groups'));
    }

    public function itemGroupPerParent($parent) {
        $item_groups = DB::table('tabItem Group')->where('parent_item_group', $parent)->selectRaw('name as id, name as text, is_group')->get()->toArray();
     
        return response()->json($item_groups);
    }

    public function getParentItems(Request $request) {
        $item_group = $request->itemgroup;
        $item_group_level1 = $request->itemgroup1;
        $item_group_level2 = $request->itemgroup2;
        $item_group_level3 = $request->itemgroup3;
        $item_group_level4 = $request->itemgroup4;
        $item_group_level5 = $request->itemgroup5;
        $variant_of = $request->variant_of;

        $templates = DB::table('tabItem')->where('has_variants', 1)
            ->where('disabled', 0)->where('is_stock_item', 1)
            ->where('name','LIKE', '%'.$request->q.'%')
            ->when($item_group, function($q) use ($item_group){
                return $q->where('item_group', $item_group);
            })
            ->when($item_group_level1, function($q) use ($item_group_level1){
                return $q->where('item_group_level_1', $item_group_level1);
            })
            ->when($item_group_level2, function($q) use ($item_group_level2){
                return $q->where('item_group_level_2', $item_group_level2);
            })
            ->when($item_group_level3, function($q) use ($item_group_level3){
                return $q->where('item_group_level_3', $item_group_level3);
            })
            ->when($item_group_level4, function($q) use ($item_group_level4){
                return $q->where('item_group_level_4', $item_group_level4);
            })
            ->when($item_group_level5, function($q) use ($item_group_level5){
                return $q->where('item_group_level_5', $item_group_level5);
            });

        if ($request->list) {
            $list = $templates->when($variant_of, function($q) use ($variant_of){
                    return $q->where('name', $variant_of);
                })
                ->select('name', 'description')->orderBy('name', 'asc')->paginate(30);

            return view('tbl_item_templates', compact('list'));
        }

        $template_items = $templates->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')->limit(20)->get();

        return response()->json($template_items);
    }

    public function itemVariants($variant_of) {
        if (!in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            return redirect('/');
        }

        $item_variants = DB::table('tabItem')->where('has_variants', 0)
            ->where('disabled', 0)->where('is_stock_item', 1)
            ->where('variant_of', $variant_of)->select('name', 'custom_item_cost')
            ->get()->toArray();

        $item_codes = array_column($item_variants, 'name');

        $attributes_query = DB::table('tabItem Variant Attribute')->whereIn('parent', $item_codes)->select('parent', 'attribute', 'attribute_value')->orderBy('idx', 'asc')->get();

        $attribute_names = collect($attributes_query)->map(function ($q){
            return $q->attribute;
        })->unique();

        $attributes = [];
        foreach ($attributes_query as $row) {
            $attributes[$row->parent][$row->attribute] = $row->attribute_value;
        }

        $user_department = Auth::user()->department;
        $allowed_department = DB::table('tabDeparment with Price Access')->pluck('department')->toArray();

        $prices = [];
    
        $last_purchase_order = DB::table('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)->whereIn('poi.item_code', $item_codes)->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')->orderBy('po.creation', 'desc')->get();

        $last_landed_cost_voucher = DB::table('tabLanded Cost Voucher as a')->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
            ->where('a.docstatus', 1)->whereIn('b.item_code', $item_codes)->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')->orderBy('transaction_date', 'desc')->get();
        
        $last_purchase_order_rates = collect($last_purchase_order)->groupBy('item_code')->toArray();
        $last_landed_cost_voucher_rates = collect($last_landed_cost_voucher)->groupBy('item_code')->toArray();

        $website_prices = DB::table('tabItem Price')->where('price_list', 'Website Price List')->where('selling', 1)
            ->whereIn('item_code', $item_codes)->orderBy('modified', 'desc')->pluck('price_list_rate', 'item_code')->toArray();

        $price_settings = DB::table('tabSingles')->where('doctype', 'Price Settings')
            ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])->pluck('value', 'field')->toArray();

        $minimum_price_computation = array_key_exists('minimum_price_computation', $price_settings) ? $price_settings['minimum_price_computation'] : 0;
        $standard_price_computation = array_key_exists('standard_price_computation', $price_settings) ? $price_settings['standard_price_computation'] : 0;
        $is_tax_included_in_rate = array_key_exists('is_tax_included_in_rate', $price_settings) ? $price_settings['is_tax_included_in_rate'] : 0;
            
        foreach($item_variants as $row){
            $rate = 0;
            $standard_price = 0;
            $min_price = 0;
            if(array_key_exists($row->name, $last_purchase_order_rates)){
                if($last_purchase_order_rates[$row->name][0]->supplier_group == 'Imported'){
                    $rate = isset($last_landed_cost_voucher_rates[$row->name]) ? $last_landed_cost_voucher_rates[$row->name][0]->valuation_rate : 0;
                }else{
                    $rate = $last_purchase_order_rates[$row->name][0]->base_rate;
                }
            }
            // custom item cost 
            if ($rate <= 0) {
                $rate = $row->custom_item_cost ? $row->custom_item_cost : 0;
            }

            $d_rate = ($rate * $standard_price_computation);
            $min_price = ($rate * $minimum_price_computation);
            if ($is_tax_included_in_rate) {
                $d_rate = ($rate * $standard_price_computation) * 1.12;
            }

            $standard_price = array_key_exists($row->name, $website_prices) ? $website_prices[$row->name] : $d_rate;
            
            $prices[$row->name] = [
                'rate' => $rate,
                'standard' => $standard_price,
                'minimum' => $min_price
            ];
        }

        return view('view_item_variants', compact('attributes', 'attribute_names', 'item_codes', 'variant_of', 'prices'));
    }

    public function updateRate(Request $request) {
        DB::beginTransaction();
        try {
            foreach($request->price as $item_code => $value) {
                if ($value && $value > 0) {
                    DB::table('tabItem')->where('name', $item_code)->update(['custom_item_cost' => $value]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Item prices has been updated.');
        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'There was a problem updating prices. Please try again.');
        }
    }

    public function import_from_ecommerce(){
        return view('import_from_ecommerce');
    }

    public function import_images(Request $request){
        DB::beginTransaction();
        try{
            if($request->hasFile('import_zip')){
                $file = $request->file('import_zip');
                if(!in_array($file->getClientOriginalExtension(), ['zip', 'ZIP'])){
                    return redirect()->back()->with('error', 'Only .zip files are allowed.');
                }
                
                if(!Storage::disk('public')->exists('/export/')){
                    Storage::disk('public')->makeDirectory('/export/');
                }

                $file->storeAs('/public/export/', 'imported_athena_images.zip');

                $now = Carbon::now();
                $zip = new ZipArchive;      
                if(Storage::disk('public')->exists('/export/imported_athena_images.zip') and $zip->open(storage_path('/app/public/export/imported_athena_images.zip')) === TRUE){
                    $zip->extractTo(storage_path('/app/public/export/'));
                    $zip->close();

                    Storage::disk('public')->delete('/export/imported_athena_images.zip');
                }

                $imported_files = Storage::disk('public')->files('/export/');

                // Collect image files to save in DB
                $collect_images_arr = collect($imported_files)->map(function ($q){
                    $image = explode('/', $q)[1];

                    $exploded = explode('.', $image);
                    $image_name = isset($exploded[0]) ? $exploded[0] : null;
                    $image_extension = isset($exploded[1]) ? $exploded[1] : null;

                    if(!in_array($image_extension, ['webp', 'WEBP', 'zip', 'ZIP'])){
                        return [
                            'item_code' => isset(explode('-', $image_name)[1]) ? explode('-', $image_name)[1] : null,
                            'image' => $image
                        ];
                    }
                });

                $collect_images_arr = $collect_images_arr->filter(function ($q){
                    return !is_null($q);
                });

                $images_arr = collect($collect_images_arr)->groupBy('item_code');
                $new_images = [];
                if($images_arr){
                    $item_codes = array_keys($images_arr->toArray());
                    
                    $collect_athena_images = DB::table('tabItem Images')->whereIn('parent', $item_codes)->get();
                    $athena_images = collect($collect_athena_images)->groupBy('parent');

                    foreach($item_codes as $item_code){
                        // Update order sequence of existing images
                        if(isset($athena_images[$item_code])){
                            $new_idx = isset($images_arr[$item_code]) ? count($images_arr[$item_code]) : 0;
                            foreach($athena_images[$item_code] as $i => $ath){
                                $i = $i + 1;
                                DB::table('tabItem Images')->where('parent', $item_code)->where('name', $ath->name)->update(['idx' => $new_idx + $i]);
                            }
                        }

                        // Save new images in DB
                        if(isset($images_arr[$item_code])){
                            foreach($images_arr[$item_code] as $a => $image){
                                $a = $a + 1;
                                $jpg = explode('-', $image['image'])[0].$a.'-'.str_replace(explode('-', $image['image'])[0].'-', null, $image['image']);
                                $webp = explode('.', $jpg)[0].'.webp';
        
                                $new_images[] = [
                                    'name' => uniqid(),
                                    'creation' => $now->toDateTimeString(),
                                    'modified' => $now->toDateTimeString(),
                                    'modified_by' => Auth::user()->wh_user,
                                    'owner' => Auth::user()->wh_user,
                                    'idx' => $a,
                                    'from_ecommerce' => 1,
                                    'parent' => $image['item_code'],
                                    'parentfield' => 'item_images',
                                    'parenttype' => 'Item',
                                    'image_path' => $jpg
                                ];
        
                                if(Storage::disk('public')->exists('/export/'.$image['image']) and !Storage::disk('public')->exists('/img/'.$jpg)){
                                    Storage::disk('public')->move('/export/'.$image['image'], '/img/'.$jpg);
                                }

                                if(Storage::disk('public')->exists('/export/'.explode('.', $image['image'])[0].'.webp') and !Storage::disk('public')->exists('/img/'.$webp)){
                                    Storage::disk('public')->move('/export/'.explode('.', $image['image'])[0].'.webp', '/img/'.$webp);
                                }
                            }
                        }
                    }
                }

                DB::table('tabItem Images')->insert($new_images);
                DB::commit();
                return redirect()->back()->with('success', 'E-Commerce Image(s) Imported');
            }
            return redirect()->back();
        }catch(\Exception $e){
            DB::rollback();
        
            if(Storage::disk('public')->exists('/export/')){
                Storage::disk('public')->deleteDirectory('/export/');
            }
            return redirect()->back()->with('error', 'An error occured. Please try again later.');
        }
    }

    public function get_item_stock_levels($item_code, Request $request) {
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();
        $allow_warehouse = [];
        $is_promodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
        if ($is_promodiser) {
            $allowed_parent_warehouse_for_promodiser = DB::table('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.parent_warehouse')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $allowed_warehouse_for_promodiser = DB::table('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.name')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $consignment_stores = DB::table('tabAssigned Consignment Warehouse')->where('parent', Auth::user()->frappe_userid)->pluck('warehouse')->toArray();

            $allow_warehouse = array_merge($allowed_parent_warehouse_for_promodiser, $allowed_warehouse_for_promodiser);
            $allow_warehouse = array_merge($allow_warehouse, $consignment_stores);
        }
        // get item inventory stock list
        $item_inventory = DB::table('tabBin')->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')->where('item_code', $item_code)
            ->when($is_promodiser, function($q) use ($allow_warehouse) {
                return $q->whereIn('warehouse', $allow_warehouse);
            })
            ->where('stock_warehouse', 1)->where('tabWarehouse.disabled', 0)
            ->select('item_code', 'warehouse', 'location', 'actual_qty', 'consigned_qty', 'tabBin.stock_uom', 'parent_warehouse')
            ->get()->toArray();

        $stock_warehouses = array_column($item_inventory, 'warehouse');

        $stock_reserves = [];
        $issued_qty_but_not_submitted = [];
        if (count($stock_warehouses) > 0) {
            $stock_reserves = StockReservation::where('item_code', $item_code)
                ->whereIn('warehouse', $stock_warehouses)->whereIn('status', ['Active', 'Partially Issued'])
                ->selectRaw('SUM(reserve_qty) as reserved_qty, SUM(consumed_qty) as consumed_qty, warehouse')
                ->groupBy('warehouse')->get();

            $stock_reserves = collect($stock_reserves)->groupBy('warehouse')->toArray();

            $ste_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
                ->where('item_code', $item_code)->whereIn('s_warehouse', $stock_warehouses)
                ->selectRaw('SUM(qty) as qty, s_warehouse')->groupBy('s_warehouse')
                ->pluck('qty', 's_warehouse')->toArray();

            $at_issued = DB::table('tabAthena Transactions as at')
                ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
                ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
                ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
                ->where('psi.status', 'Issued')->where('at.item_code', $item_code)
                ->where('psi.item_code', $item_code)->whereIn('at.source_warehouse', $stock_warehouses)
                ->selectRaw('SUM(at.issued_qty) as qty, at.source_warehouse')->groupBy('at.source_warehouse')
                ->pluck('qty', 'source_warehouse')->toArray();
        }

        $site_warehouses = [];
        $consignment_warehouses = [];
        foreach ($item_inventory as $value) {
            $reserved_qty = array_key_exists($value->warehouse, $stock_reserves) ? $stock_reserves[$value->warehouse][0]['reserved_qty'] : 0;
            
            $consumed_qty = array_key_exists($value->warehouse, $stock_reserves) ? $stock_reserves[$value->warehouse][0]['consumed_qty'] : 0;
            $ste_issued_qty = array_key_exists($value->warehouse, $ste_issued) ? $ste_issued[$value->warehouse] : 0;
            $at_issued_qty = array_key_exists($value->warehouse, $at_issued) ? $at_issued[$value->warehouse] : 0;

            $issued_qty = $at_issued_qty + $ste_issued_qty;
            $reserved_qty = $reserved_qty - $consumed_qty;
            $reserved_qty = $reserved_qty > 0 ? $reserved_qty : 0;

            $issued_reserved_qty = ($reserved_qty + $issued_qty) - $consumed_qty;

            $actual_qty = $value->actual_qty;
            $available_qty = ($actual_qty > $issued_reserved_qty) ? $actual_qty - $issued_reserved_qty : 0;
            if($value->parent_warehouse == "P2 Consignment Warehouse - FI" && !$is_promodiser) {
                $consignment_warehouses[] = [
                    'warehouse' => $value->warehouse,
                    'location' => $value->location,
                    'reserved_qty' => $reserved_qty,
                    'actual_qty' => $value->actual_qty,
                    'available_qty' => $available_qty,
                    'stock_uom' => $value->stock_uom,
                ];
            }else{
                if(Auth::user()->user_group == 'Promodiser' && $value->parent_warehouse == "P2 Consignment Warehouse - FI"){
                    $available_qty = $value->consigned_qty > 0 ? $value->consigned_qty : 0;
                }

                $site_warehouses[] = [
                    'warehouse' => $value->warehouse,
                    'location' => $value->location,
                    'reserved_qty' => $reserved_qty,
                    'actual_qty' => $value->actual_qty,
                    'available_qty' => $available_qty,
                    'stock_uom' => $value->stock_uom,
                ];
            }
        }

        if ($request->ajax()) {
            return view('item_stock_level', compact('consignment_warehouses', 'site_warehouses', 'item_details'));
        }

        return [
            'consignment_warehouses' => $consignment_warehouses,
            'site_warehouses' => $site_warehouses
        ];
    }
    
    public function get_bundled_item_stock_levels(Request $request, $item_code){
        $product_bundle = DB::table('tabProduct Bundle as p')
            ->join('tabProduct Bundle Item as c', 'c.parent', 'p.name')
            ->where('p.name', $item_code)
            ->select('p.name as bundle_id', 'p.*', 'c.*')
            ->get();

        $items = collect($product_bundle)->pluck('item_code');
        $grouped = collect($product_bundle)->groupBy('item_code');

        $stocks = [];
        foreach($items as $item){
            $description = isset($grouped[$item]) ? $grouped[$item][0]->description : null;
            $details = $this->get_item_stock_levels($item, $request);

            unset($details['consignment_warehouses']);

            $details['site_warehouses'] = collect($details['site_warehouses'])->where('actual_qty', '>', 0);
            $details['description'] = $description;

            $stocks[$item] = $details;
        }

        return view('item_stock_level_bundled', compact('stocks'));
    }

    private function get_guide_images($path, $title){
        $files = Storage::files($path);

        $files = collect($files)->filter(function ($file) use ($path, $title) {
            return Str::startsWith($file, "$path/$title");
        })->all();

        $images = collect($files)->mapWithKeys(function ($image){
            [$directory, $filename] = explode('/', $image);
            [$id] = explode('.', $filename);

            return [$id => $this->base64_image($image)];
        });

        $no_img = $this->base64_image('icon/no_img.png');
        $images['no_img'] = $no_img;

        return $images;
    }

    public function guide_beginning_inventory(){
        $images = $this->get_guide_images('user_manual_img', 'beginning_inventory');
        
        return view('consignment.user_manual.beginning_inventory', compact('images'));
    }

    public function guide_sales_report_entry(){
        $images = $this->get_guide_images('user_manual_img', 'sales_report');
        
        return view('consignment.user_manual.sales_report', compact('images'));
    }

    public function guide_stock_transfer(){
        $images = $this->get_guide_images('user_manual_img', 'stock_transfer');

        return view('consignment.user_manual.stock_transfer', compact('images'));
    }

    public function guide_damaged_items(){
        $images = $this->get_guide_images('user_manual_img', 'damaged_items');

        return view('consignment.user_manual.damaged_items', compact('images'));
    }

    public function guide_stock_receiving(){
        $images = $this->get_guide_images('user_manual_img', 'stock_receiving');

        return view('consignment.user_manual.stock_receiving', compact('images'));
    }

    public function guide_inventory_audit(){
        $images = $this->get_guide_images('user_manual_img', 'inventory_audit');

        return view('consignment.user_manual.inventory_audit', compact('images'));
    }
    
    public function guide_consignment_dashboard(){
        $images = $this->get_guide_images('user_manual_img', 'cs');

        return view('consignment.user_manual.consignment_dashboard', compact('images'));
    }
    
    public function guide_beginning_entries(){
        $images = $this->get_guide_images('user_manual_img', 'cs');

        return view('consignment.user_manual.beginning_entries', compact('images'));
    }

    public function guide_inventory_report(){
        $images = $this->get_guide_images('user_manual_img', 'cs');

        return view('consignment.user_manual.inventory_report', compact('images'));
    }
    
    public function guide_inventory_summary(){
        $images = $this->get_guide_images('user_manual_img', 'cs');

        return view('consignment.user_manual.inventory_summary', compact('images'));
    }

    public function guide_stock_to_receive(){
        $images = $this->get_guide_images('user_manual_img', 'cs');

        return view('consignment.user_manual.stock_to_receive', compact('images'));
    }

    public function guide_consignment_stock_transfer(){
        $images = $this->get_guide_images('user_manual_img', 'cs');
        $images['receiving-of-stocks'] = $this->base64_image('user_manual_img/receiving-of-stocks.png');

        return view('consignment.user_manual.consignment_stock_transfer', compact('images'));
    }

    public function download_image($webp){
        $webpPath = storage_path("app/public/img/$webp");

        if (!file_exists($webpPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $image = imagecreatefromwebp($webpPath);

        if (!$image) {
            return response()->json(['message' => 'Failed to convert the image'], 500);
        }

        ob_start();
        imagejpeg($image);
        $jpgData = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);

        $name = explode('.', $webp)[0];

        return Response::make($jpgData, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "attachment; filename=$name.jpg"
        ]);
    }

    public function get_customers(Request $request){
        $term = $request->term;
        $customers = Customer::where('name', 'like', "%$term%")->select('name')->get();

        return response()->json($customers);
    }

    public function get_erp_projects(Request $request){
        $term = $request->term;
        $projects = Project::where('name', 'like', "%$term%")->select('name')->get();

        return response()->json($projects);
    }

    public function get_customer_address(Request $request){
        $term = $request->term;
        $customer = $request->customer;

        $customer_details = DB::table('tabCustomer as c')
            ->join('tabDynamic Link as d', 'd.link_name', 'c.name')
            ->join('tabAddress as a', 'a.name', 'd.parent')
            ->where('d.link_doctype', 'Customer')
            ->where('d.parenttype', 'Address')
            ->when($customer, function ($q) use ($customer){
                return $q->where('c.name', $customer);
            })
            ->where('a.name', 'LIKE', "%$term%")
            ->select('a.address_title', 'a.name', 'a.address_line1', 'a.address_line2', 'a.city')->limit(10)
            ->get();

        $customer_details = collect($customer_details)->map(function ($customer){
            $customer->address_display = trim($customer->address_line1);
            if($customer->address_line2){
                $customer->address_display .= ", $customer->address_line2";
            }
    
            if($customer->city){
                $customer->address_display .= ", $customer->city";
            }

            return $customer;
        });

        return response()->json($customer_details);
    }

    public function get_manuals(){
        $files = collect(Storage::files('Manuals'))->map(function ($file) {
            return basename($file);
        });
        
        $consignment_promodiser_manuals = $files->filter(function ($file) {
            return str_contains($file, 'Consignment') && str_contains($file, 'Promodiser');
        });
        
        $consignment_supervisor_manuals = $files->filter(function ($file) {
            return str_contains($file, 'Consignment') && str_contains($file, 'Supervisors');
        });
        
        $generic_manuals = $files->reject(function ($file) {
            return str_contains($file, 'Consignment');
        });

        return view('user_manual', compact('consignment_promodiser_manuals', 'consignment_supervisor_manuals', 'generic_manuals'));
    }
}