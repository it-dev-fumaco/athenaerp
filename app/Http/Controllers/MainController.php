<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\StockReservation;
use Auth;
use DB;

class MainController extends Controller
{
    public function allowed_parent_warehouses(){
        $user = Auth::user()->frappe_userid;
        return DB::table('tabWarehouse Access')
            ->where('parent', $user)->pluck('warehouse');
    }

    public function index(Request $request){
        $this->update_reservation_status();
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);
        if(Auth::user()->user_group == 'User'){
            return redirect('/search_results');
        }

        return view('index');
    }

    public function search_results(Request $request){        
        $search_str = explode(' ', $request->searchString);

        $itemClass = DB::table('tabItem')->select('item_classification')
            ->where('description', 'LIKE', "%".$request->searchString."%" )
            ->orWhere('name', 'LIKE', "%".$request->searchString."%")
            ->orWhere('stock_uom', 'LIKE', "%".$request->searchString."%")
            ->orWhere('item_group', 'LIKE', "%".$request->searchString."%")
            ->orWhere('manufacturer_part_no', 'LIKE', "%".$request->searchString."%")
            ->orderby('item_classification','asc')
            ->distinct('created_at')
            ->get();

        $itemClassCount = count($itemClass);

        if($itemClassCount >= 2){
            $getFirst = $itemClass->keys()->first();
            $itemClass = $itemClass->forget($getFirst); // First item is null, first item is removed
        }

        $items = DB::table('tabItem')->where('disabled', 0)
            ->where('has_variants', 0)->where('is_stock_item', 1)
            ->when($request->searchString, function ($query) use ($search_str, $request) {
                return $query->where(function($q) use ($search_str, $request) {
                    foreach ($search_str as $str) {
                        $q->where('description', 'LIKE', "%".$str."%");
                    }

                    $q->orWhere('name', 'LIKE', "%".$request->searchString."%")
                        ->orWhere('item_classification', 'LIKE', "%".$request->searchString."%")
                        ->orWhere('stock_uom', 'LIKE', "%".$request->searchString."%")
                        ->orWhere('item_group', 'LIKE', "%".$request->searchString."%")
                        ->orWhere('manufacturer_part_no', 'LIKE', "%".$request->searchString."%")
                        ->orWhere(DB::raw('(SELECT GROUP_CONCAT(DISTINCT supplier_part_no SEPARATOR "; ") FROM `tabItem Supplier` WHERE parent = `tabItem`.name)'), 'LIKE', "%".$request->searchString."%");
                });
            })
            ->when($request->wh, function($q) use ($request){
				return $q->where('default_warehouse', $request->wh);
            })
            ->when($request->group, function($q) use ($request){
				return $q->where('item_group', $request->group);
            })
            ->when($request->classification, function($q) use ($request){
				return $q->where('item_classification', $request->classification);
            })
            ->when($request->check_qty, function($q) use ($request){
				return $q->where(DB::raw('(SELECT SUM(actual_qty) FROM `tabBin` WHERE item_code = `tabItem`.name)'), '>', 0);
			})
            ->orderBy('modified', 'desc')->paginate(20);

        $url = $request->fullUrl();

        $items->withPath($url);

        if($request->get_total){
            return number_format($items->total());
        }

        $item_list = [];
        foreach ($items as $row) {
            $item_inventory = DB::table('tabBin')->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')->where('item_code', $row->name)
                ->select('item_code', 'warehouse', 'actual_qty', 'stock_uom', 'parent_warehouse')->get();

            $item_image_paths = DB::table('tabItem Images')->where('parent', $row->name)->get();

            $site_warehouses = [];
            $consignment_warehouses = [];
            foreach ($item_inventory as $value) {
                $reserved_qty = StockReservation::where('item_code', $value->item_code)
                    ->where('warehouse', $value->warehouse)->where('status', 'Active')->sum('reserve_qty');

                $consumed_qty = StockReservation::where('item_code', $value->item_code)
                    ->where('warehouse', $value->warehouse)->where('status', 'Active')->sum('consumed_qty');

                $reserved_qty = $reserved_qty - $consumed_qty;

                $actual_qty = $value->actual_qty - $this->get_issued_qty($value->item_code, $value->warehouse);

                $lowLevelStock = DB::table('tabItem Reorder')->select('parent', 'warehouse')
                        ->where('parent', $value->item_code)
                        ->where('warehouse', $value->warehouse)->sum('warehouse_reorder_level');
                        
                if($value->parent_warehouse == "P2 Consignment Warehouse - FI") {
                    
                    $consignment_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $actual_qty,
                        'available_qty' => ($actual_qty > $reserved_qty) ? $actual_qty - $reserved_qty : 0,
                        'stock_uom' => $value->stock_uom,
                        'warehouse_reorder_level' => $lowLevelStock,
                    ];
                    
                }else{
                    $site_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $actual_qty,
                        'available_qty' => ($actual_qty > $reserved_qty) ? $actual_qty - $reserved_qty : 0,
                        'stock_uom' => $value->stock_uom,
                        'warehouse_reorder_level' => $lowLevelStock,
                    ];
                }
            }

            $part_nos = DB::table('tabItem Supplier')->where('parent', $row->name)->pluck('supplier_part_no');

            $part_nos = implode(', ', $part_nos->toArray());

            $item_list[] = [
                'name' => $row->name,
                'description' => $row->description,
                'item_image_paths' => $item_image_paths,
                'part_nos' => $part_nos,
                'item_group' => $row->item_group,
                'stock_uom' => $row->stock_uom,
                'item_classification' => $row->item_classification,
                'item_inventory' => $site_warehouses,
                'consignment_warehouses' => $consignment_warehouses,
                'default_warehouse' => $row->default_warehouse
            ];
            
        }
        return view('search_results', compact('item_list', 'items', 'itemClass'));
    }
    
    public function count_ste_for_issue($purpose){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        return DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', $purpose)
            ->whereNotIn('sted.status', ['Issued', 'Returned'])
            ->when($purpose == 'Material Issue', function($q){
				return $q->whereNotIn('ste.issue_as', ['Customer Replacement', 'Sample']);
            })
            ->when($purpose == 'Material Transfer', function($q){
				return $q->whereNotin('ste.transfer_as', ['Consignment', 'Sample Item']);
            })
            ->when($purpose == 'Material Receipt', function($q){
				return $q->where('ste.receive_as', 'Sales Return');
            })
            ->whereIn('sted.s_warehouse', $allowed_warehouses)->count();
    }

    public function count_ps_for_issue(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q_1 = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))
                ->where('ps.docstatus', 0)
                ->where('dri.docstatus', 0)
                ->whereIn('dri.warehouse', $allowed_warehouses)
                ->select('ps.sales_order', 'psi.name AS id', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', DB::raw('SUM(dri.qty) as qty'), 'psi.stock_uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation')
                ->groupBy('ps.sales_order', 'psi.name', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.stock_uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation')
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
            ->whereIn('parent_warehouse', $allowed_parent_warehouses)->pluck('name');
    }

    public function load_suggestion_box(Request $request){
        $q = DB::table('tabItem')->leftJoin('tabItem Supplier', 'tabItem Supplier.parent', 'tabItem.name')->where('disabled', 0)
            ->where('has_variants', 0)->where('is_stock_item', 1)
            ->where(function($q) use ($request) {
                $q->where('tabItem.name', 'like', '%'.$request->search_string.'%')
                ->orWhere('item_classification', 'like', '%'.$request->search_string.'%')
                ->orWhere('item_group', 'like', '%'.$request->search_string.'%')
                ->orWhere('stock_uom', 'like', '%'.$request->search_string.'%')
                ->orWhere('supplier_part_no', 'like', '%'.$request->search_string.'%')
                ->orWhere('manufacturer_part_no', 'like', '%'.$request->search_string.'%');
            })
            ->select('tabItem.name', 'description', 'item_image_path')
            ->orderBy('tabItem.modified', 'desc')->paginate(8);

        return view('suggestion_box', compact('q'));
    }

    public function get_select_filters(){
        $warehouses = DB::table('tabWarehouse')->where('is_group', 0)
            ->where('category', 'Physical')
            ->selectRaw('name as id, name as text')->orderBy('name', 'asc')->get();

        $item_groups = DB::table('tabItem Group')->where('is_group', 0)
            ->where('show_in_erpinventory', 1)->orderBy('name', 'asc')->pluck('name');

        $item_classification = DB::table('tabItem Classification')
            ->orderBy('name', 'asc')->pluck('name');

        return response()->json([
            // 'parent_warehouses' => $allowed_warehouses,
            'warehouses' => $warehouses,
            'item_groups' => $item_groups,
            'item_classification' => $item_classification,
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
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)
            ->where('at.item_code', $item_code)->where('at.source_warehouse', $warehouse)
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
        $q = DB::table('tabWarehouse')->where('name', $child_warehouse)->first();
        if($q){
            return $q->parent_warehouse;
        }

        return null;
    }

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
                'balance' => $balance,
                'sales_order_no' => $d->sales_order_no,
                'issue_as' => $d->issue_as,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
            ];
        }

        return response()->json(['records' => $list]);
    }

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
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'ste.owner', 'ste.material_request', 'ste.production_order', 'ste.creation', 'ste.so_customer_name')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

        $list = [];
        foreach ($q as $d) {
            $actual_qty = $this->get_actual_qty($d->item_code, $d->s_warehouse);

            $total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
                ->where('item_code', $d->item_code)->where('s_warehouse', $d->s_warehouse)->sum('qty');
            
            $balance = $actual_qty - $total_issued;

            $ref_no = ($d->material_request) ? $d->material_request : $d->sales_order_no;

            $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');

            $part_nos = implode(', ', $part_nos->toArray());

            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $parent_warehouse = $this->get_warehouse_parent($d->s_warehouse);

            // check if production order exist
            $production_order = DB::table('tabProduction Order')->where('name', $d->production_order)->first();

            $delivery_date = $production_order->delivery_date;
            $order_status = 'Unknown Status';
            if($production_order){
                if($production_order->sales_order) {
                    $per_item_delivery_date = DB::table('tabSales Order Item')->where('parent', $production_order->sales_order)
                        ->where('item_code', $production_order->parent_item_code)->first();

                    if($per_item_delivery_date){
                        $delivery_date = $per_item_delivery_date->rescheduled_delivery_date;
                    }

                    $sales_order_query = DB::table('tabSales Order')->where('name', $production_order->sales_order)->first();
                    if($sales_order_query) {
                        $order_status = ($sales_order_query->per_delivered > 0 && $sales_order_query->per_delivered < 100) ? 'Partially Delivered' : 'Fully Delivered';
                        $order_status = ($sales_order_query->per_delivered <= 0) ? 'To Deliver' : $order_status;
                    }
                } else {
                    $material_request_query = DB::table('tabMaterial Request')->where('name', $production_order->material_request)->first();
                    if($material_request_query) {
                        $order_status = $material_request_query->status;
                    }
                }
            }

            $list[] = [
                'delivery_date' => Carbon::parse($delivery_date)->format('M-d-Y'),
                'customer' => $d->so_customer_name,
                'delivery_status' => $order_status,
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
                'balance' => $balance,
                'ref_no' => $ref_no,
                'parent_warehouse' => $parent_warehouse,
                'production_order' => $d->production_order,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
            ];
        }
        
        return response()->json(['records' => $list]);
    }

    public function view_material_transfer(Request $request){
        if(!$request->arr){
            return view('material_transfer');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowed_warehouses)->whereNotin('transfer_as', ['Consignment', 'Sample Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

        $list = [];
        foreach ($q as $d) {
            $actual_qty = $this->get_actual_qty($d->item_code, $d->s_warehouse);

            $total_issued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
                ->where('item_code', $d->item_code)->where('s_warehouse', $d->s_warehouse)->sum('qty');
            
            $balance = $actual_qty - $total_issued;

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
                'transfer_as' => $d->transfer_as,
                'actual_qty' => $actual_qty,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'balance' => $balance,
                'ref_no' => $ref_no,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
            ];
        }
        
        return response()->json(['records' => $list]);
    }

    public function get_mr_sales_return(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('ste.purpose', 'Material Receipt')
            ->where('ste.receive_as', 'Sales Return')->whereIn('sted.s_warehouse', $allowed_warehouses)
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner', 'ste.creation')
            ->get();

        $list = [];
        foreach ($q as $d) {
            $owner = DB::table('tabUser')->where('email', $d->owner)->first();
            $owner = ($owner) ? $owner->full_name : null;

            $list[] = [
                'stedname' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->t_warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => $d->transfer_qty,
                'sales_order_no' => $d->sales_order_no,
                'status' => $d->status,
                'so_customer_name' => $d->so_customer_name,
            ];
        }
        
        return response()->json(['mr_return' => $list]);
    }

    public function get_ste_details($id){
        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('sted.name', $id)
            ->select('ste.production_order', 'ste.transfer_as', 'ste.purpose', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.actual_qty', 'sted.validate_item_code', 'sted.owner', 'sted.status', 'sted.remarks', 'sted.stock_uom', 'ste.sales_order_no', 'ste.material_request', 'ste.issue_as')
            ->first();

        $ref_no = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;

        $owner = ucwords(str_replace('.', ' ', explode('@', $q->owner)[0]));

        $img = DB::table('tabItem')->where('name', $q->item_code)->first()->item_image_path;

        $available_qty = $this->get_available_qty($q->item_code, $q->s_warehouse);
    
        $stock_reservation_details = [];
        $so_details = DB::table('tabSales Order')->where('name', $ref_no)->first();

        $sales_person = ($so_details) ? $so_details->sales_person : null;
        $project = ($so_details) ? $so_details->project : null;
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
            'stock_reservation' => $stock_reservation_details
        ];

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
            return view('return_modal_content', compact('data'));
        }

        if($q->purpose == 'Material Transfer'){
            return view('internal_transfer_modal_content', compact('data'));
        }

        return response()->json($data);
    }

    public function get_stock_reservation($item_code, $warehouse, $sales_person, $project, $consignment_warehouse){
        $query = DB::table('tabStock Reservation')
            ->when($sales_person, function($q) use ($sales_person){
                return $q->where('sales_person', $sales_person)->where('project', $project)
                    ->where('item_code', $item_code)->where('warehouse', $warehouse);
            })
            ->when($consignment_warehouse, function($q) use ($consignment_warehouse){
				return $q->where('consignment_warehouse', $consignment_warehouse);
            })
            ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();

        return ($query) ? $query : [];
    }

    public function get_ps_details($id){
        $q = DB::table('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
            ->whereRaw(('dri.item_code = psi.item_code'))
            ->where('ps.docstatus', '<', 2)
            ->where('ps.item_status', 'For Checking')
            ->where('psi.name', $id)
            ->where('dri.docstatus', 0)
            ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.qty', 'psi.stock_uom', 'psi.name as id', 'dri.warehouse', 'psi.status', 'psi.stock_uom', 'psi.qty', 'dri.name as dri_name', 'dr.reference as sales_order')
            ->first();

        if(!$q){
            return response()->json([
                'error' => 1,
                'modal_title' => 'Not Found', 
                'modal_message' => 'Item not found. Please reload the page.'
            ]);
        }

        $item_details = DB::table('tabItem')->where('name', $q->item_code)->first();

        $is_bundle = false;
        if(!$item_details->is_stock_item){
            $is_bundle = DB::table('tabProduct Bundle')->where('name', $q->item_code)->exists();
        }

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
            }
        }

        $available_qty = $this->get_available_qty($q->item_code, $q->warehouse);

        $stock_reservation_details = [];
        $so_details = DB::table('tabSales Order')->where('name', $q->sales_order)->first();
        if($so_details) {
            $stock_reservation_details = $this->get_stock_reservation($q->item_code, $q->warehouse, $so_details->sales_person, $so_details->project, null);
        }

        $data = [
            'id' => $q->id,
	        'barcode' => $q->barcode,
            'item_image' => $item_details->item_image_path,
            'delivery_note' => $q->delivery_note,
            'description' => $q->description,
            'item_code' => $q->item_code,
            'name' => $q->name,
            'sales_order' => $q->sales_order,
            'status' => $q->status,
            'stock_uom' => $q->stock_uom,
            'qty' => ($q->qty * 1),
            'warehouse' => $q->warehouse,
            'available_qty' => $available_qty,
            'is_bundle' => $is_bundle,
            'product_bundle_items' => $product_bundle_items,
            'dri_name' => $q->dri_name,
            'stock_reservation' => $stock_reservation_details
        ];

        $is_stock_entry = false;
        return view('deliveries_modal_content', compact('data', 'is_stock_entry'));
    }

    public function submit_transaction(Request $request){
        DB::beginTransaction();
        
        try {
            $steDetails = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')->where('sed.name', $request->child_tbl_id)
                ->select('se.name as parent_se', 'se.*', 'sed.*', 'sed.status as per_item_status', 'se.docstatus as se_status')->first();
            if(!$steDetails){
                return response()->json(['status' => 0, 'message' => 'Record not found.']);
            }

            if(in_array($steDetails->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $steDetails->per_item_status . '.']);
            }

            if($steDetails->se_status == 1){
                return response()->json(['status' => 0, 'message' => 'Item already issued.']);
            }

            $itemDetails = DB::table('tabItem')->where('name', $steDetails->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $steDetails->item_code . '</b> not found.']);
            }

            if($itemDetails->is_stock_item == 0){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $steDetails->item_code . '</b> is not a stock item.']);
            }

            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => 'Invalid barcode for <b>' . $itemDetails->item_code . '</b>.']);
            }

            if($request->qty <= 0){
                return response()->json(['status' => 0, 'message' => 'Qty cannot be less than or equal to 0.']);
            }

            // if($request->qty > $steDetails->qty){
            //     return response()->json(['status' => 0, 'message' => 'Qty cannot be greater than ' . ($steDetails->qty * 1) .'.']);
            // }

            $available_qty = $this->get_available_qty($steDetails->item_code, $steDetails->s_warehouse);
            if($steDetails->purpose != 'Material Receipt' && $request->deduct_reserve == 0){
                if($request->qty > $available_qty){
                    return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><
                    br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
                }
            }

            $reserved_qty = $this->get_reserved_qty($steDetails->item_code, $steDetails->s_warehouse);
            if($request->qty > $reserved_qty && $request->deduct_reserve == 1){
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><
                br><br>Available reserved qty is <b>' . $reserved_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
            }

            $status = $steDetails->status;
            if($steDetails->purpose == 'Material Receipt' && $steDetails->receive_as == 'Sales Return') {
                $status = 'Returned';
            }else {
                $status = 'Issued';
            }

            $values = [
                'session_user' => Auth::user()->full_name,
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

            if ($steDetails->purpose == 'Material Transfer for Manufacture') {
                $cancelled_production_order = DB::table('tabProduction Order')
                    ->where('name', $steDetails->production_order)->where('docstatus', 2)->first();

                if($cancelled_production_order){
                    return response()->json(['status' => 0, 'message' => 'Production Order ' . $cancelled_production_order->name . ' was cancelled. Please reload the page.']);
                }

                $this->submit_stock_entry($steDetails->parent_se);
                $this->generate_stock_entry($steDetails->production_order);
            }

            if ($steDetails->purpose == 'Material Transfer') {
                if($steDetails->transfer_as == 'For Return' && $status_result == 'Returned'){
                    $this->submit_stock_entry($steDetails->parent_se);
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
        
            DB::commit();

            if($request->deduct_reserve == 1) {
                return response()->json(['status' => 1, 'message' => 'Item ' . $steDetails->item_code . ' has been deducted from reservation.']);
            }

            if (($steDetails->transfer_as == 'For Return') || $steDetails->purpose == 'Material Receipt') {
                return response()->json(['status' => 1, 'message' => 'Item <b>' . $steDetails->item_code . '</b> has been returned.']);
            }else{
                return response()->json(['status' => 1, 'message' => 'Item <b>' . $steDetails->item_code . '</b> has been checked out.']);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.']);
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
            $type = 'Check Out - Delivered';
            $purpose = 'Picking Slip';
            $barcode = $q->barcode;
            $remarks = null;
            $s_warehouse = $q->warehouse;
            $t_warehouse = null;
            $reference_no = $q->delivery_note;
        }else{
            $q = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')->where('sted.name', $id)
                ->select('sted.*', 'ste.sales_order_no', 'ste.material_request', 'ste.purpose', 'ste.transfer_as', 'ste.issue_as', 'ste.receive_as')
                ->first();

            $type = null;
            if($q->purpose == 'Material Transfer for Manufacture') {
                $type = 'Check Out - Issued';
            }

            if($q->purpose == 'Material Transfer' && $q->transfer_as == 'Internal Transfer') {
                $type = 'Check Out - Transferred';
            }

            if($q->purpose == 'Material Transfer' && $q->transfer_as == 'For Return') {
                $type = 'Check In - Returned';
            }

            if($q->purpose == 'Material Issue' && $q->issue_as == 'Customer Replacement') {
                $type = 'Check Out - Replaced';
            }

            if($q->purpose == 'Material Receipt' && $q->receive_as == 'Sales Return') {
                $type = 'Check Out - Received';
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
            ->where('reference_name', $q->name)->where('reference_parent', $q->parent)
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
        } catch (Exception $e) {
            DB::rollback();
        }
    }

    public function checkout_picking_slip_item(Request $request){
        DB::beginTransaction();
        try {
            $ps_details = DB::table('tabPacking Slip as ps')->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')->where('psi.name', $request->child_tbl_id)
            ->select('ps.name as parent_ps', 'ps.*', 'psi.*', 'psi.status as per_item_status', 'ps.docstatus as ps_status')->first();
            if(!$ps_details){
                return response()->json(['status' => 0, 'message' => 'Record not found.']);
            }

            if(in_array($ps_details->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $steDetails->per_item_status . '.']);
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

            $sales_order = DB::table('tabSales Order')->where('name', $request->sales_order)->first();
            if($sales_order && in_array($sales_order->order_type, ['Shopping Cart', 'Online Shop'])) {
                $bin_details = DB::connection('mysql')->table('tabBin')
                    ->where('item_code', $ps_details->item_code)
                    ->where('warehouse', $request->warehouse)
                    ->first();

                if($bin_details) {
                    $new_reserved_qty = $bin_details->website_reserved_qty - $request->qty;
                    $new_reserved_qty = ($new_reserved_qty <= 0) ? 0 : $new_reserved_qty;

                    $values = [
                        "modified" => Carbon::now()->toDateTimeString(),
                        "modified_by" => Auth::user()->wh_user,
                        "website_reserved_qty" => $new_reserved_qty,
                    ];
            
                    DB::connection('mysql')->table('tabBin')->where('name', $bin_details->name)->update($values);
                }
            }

            if($request->is_bundle){
                $query = DB::table('tabPacked Item')->where('parent_detail_docname', $request->dri_name)->get();
                foreach ($query as $row) {
                    $bundle_item_qty = $row->qty;
                   
                    $actual_qty = $this->get_actual_qty($row->item_code, $row->warehouse);
    
                    $total_issued = $this->get_issued_qty($row->item_code, $row->warehouse);
                    
                    $available_qty = $actual_qty - $total_issued;

                    if($available_qty < $bundle_item_qty){
                        return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $row->item_code . '</b> in <b>' . $row->warehouse . '</b><br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . ($row->qty * 1) . '</b>.']);
                    }
                }
            }

            $stock_reservation_details = [];
            if($request->has_reservation && $request->has_reservation == 1) {
                $so_details = DB::table('tabSales Order')->where('name', $request->sales_order)->first();
                if($so_details) {
                    $stock_reservation_details = $this->get_stock_reservation($ps_details->item_code, $request->warehouse, $so_details->sales_person, $so_details->project, null);
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

            $now = Carbon::now();
            $values = [
                'session_user' => Auth::user()->full_name,
                'status' => 'Issued',
                'barcode' => $request->barcode,
                'date_modified' => $now->toDateTimeString()
            ];

            DB::table('tabPacking Slip Item')->where('name', $request->child_tbl_id)
                ->where('docstatus', 0)->update($values);

            $this->insert_transaction_log('Picking Slip', $request->child_tbl_id);

            $this->update_pending_ps_item_status();

            DB::commit();

            if($request->deduct_reserve == 1) {
                return response()->json(['status' => 1, 'message' => 'Item ' . $itemDetails->item_code . ' has been deducted from reservation.']);
            }

            return response()->json(['status' => 1, 'message' => 'Item ' . $itemDetails->item_code . ' has been checked out.']);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 1, 
                'modal_title' => 'Error', 
                'modal_message' => 'Error creating transaction.'
            ]);
        }
    }

    public function update_pending_ps_item_status(){
        DB::beginTransaction();
        try {
            $for_checking_ps = DB::table('tabPacking Slip')
                ->whereIn('item_status', ['For Checking', 'Issued'])->where('docstatus', 0)
                ->orderBy('modified', 'desc')
                ->pluck('name');

            foreach($for_checking_ps as $ps){
                $items_for_checking = DB::table('tabPacking Slip Item')
                    ->where('parent', $ps)->where('status', 'For Checking')->exists();

                if(!$items_for_checking){
                    DB::table('tabPacking Slip')
                        ->where('name', $ps)->where('docstatus', 0)
                        ->update(['item_status' => 'Issued', 'docstatus' => 1]);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }
    }

    public function get_item_details(Request $request, $item_code){
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        if($request->json){
            return response()->json($item_details);
        }

        $item_attributes = DB::table('tabItem Variant Attribute')->where('parent', $item_code)->orderBy('idx', 'asc')->get();

        // get item inventory stock list
        $item_inventory = DB::table('tabBin')->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')->where('item_code', $item_code)
                ->select('item_code', 'warehouse', 'actual_qty', 'stock_uom', 'parent_warehouse')->get();
        $site_warehouses = [];
        $consignment_warehouses = [];
        foreach ($item_inventory as $value) {
            $reserved_qty = StockReservation::where('item_code', $value->item_code)
                ->where('warehouse', $value->warehouse)->where('status', 'Active')->sum('reserve_qty');

            $consumed_qty = StockReservation::where('item_code', $value->item_code)
                ->where('warehouse', $value->warehouse)->where('status', 'Active')->sum('consumed_qty');

            $reserved_qty = $reserved_qty - $consumed_qty;

            $actual_qty = $value->actual_qty - $this->get_issued_qty($value->item_code, $value->warehouse);
            if($value->parent_warehouse == "P2 Consignment Warehouse - FI") {
                $consignment_warehouses[] = [
                    'warehouse' => $value->warehouse,
                    'reserved_qty' => $reserved_qty,
                    'actual_qty' => $actual_qty,
                    'available_qty' => ($actual_qty > $reserved_qty) ? $actual_qty - $reserved_qty : 0,
                    'stock_uom' => $value->stock_uom,
                ];
            }else{
                $site_warehouses[] = [
                    'warehouse' => $value->warehouse,
                    'reserved_qty' => $reserved_qty,
                    'actual_qty' => $actual_qty,
                    'available_qty' => ($actual_qty > $reserved_qty) ? $actual_qty - $reserved_qty : 0,
                    'stock_uom' => $value->stock_uom,
                ];
            }
        }

        // get item images
        $item_images = DB::table('tabItem Images')->where('parent', $item_code)->pluck('image_path')->toArray();
        // get item alternatives from production order item table in erp
        $item_alternatives = [];
        $production_item_alternatives = DB::table('tabProduction Order Item as p')->join('tabItem as i', 'p.item_alternative_for', 'i.name')
            ->where('p.item_code', $item_details->name)->where('p.item_alternative_for', '!=', $item_details->name)
            ->select('i.item_code', 'i.description')->orderBy('p.modified', 'desc')->get();
        foreach($production_item_alternatives as $a){
            $item_alternative_image = DB::table('tabItem Images')->where('parent', $a->item_code)->first();

            $actual_stocks = DB::table('tabBin')->where('item_code', $a->item_code)->sum('actual_qty');

            if(count($item_alternatives) < 7){
                $item_alternatives[] = [
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_alternative_image' => ($item_alternative_image) ? $item_alternative_image->image_path : null,
                    'actual_stocks' => $actual_stocks
                ];
            }
        }

        // get item alternatives based on parent item code
        $q = DB::table('tabItem')->where('variant_of', $item_details->variant_of)->where('name', '!=', $item_details->name)->orderBy('modified', 'desc')->get();
        foreach($q as $a){
            $item_alternative_image = DB::table('tabItem Images')->where('parent', $a->item_code)->first();

            $actual_stocks = DB::table('tabBin')->where('item_code', $a->item_code)->sum('actual_qty');

            if(count($item_alternatives) < 7){
                $item_alternatives[] = [
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_alternative_image' => ($item_alternative_image) ? $item_alternative_image->image_path : null,
                    'actual_stocks' => $actual_stocks
                ];
            }
        }

        if(count($item_alternatives) <= 0) {
            $q = DB::table('tabItem')->where('item_classification', $item_details->item_classification)->where('name', '!=', $item_details->name)->orderBy('modified', 'desc')->get();
            foreach($q as $a){
                $item_alternative_image = DB::table('tabItem Images')->where('parent', $a->item_code)->first();

                $actual_stocks = DB::table('tabBin')->where('item_code', $a->item_code)->sum('actual_qty');

                if(count($item_alternatives) < 7){
                    $item_alternatives[] = [
                        'item_code' => $a->item_code,
                        'description' => $a->description,
                        'item_alternative_image' => ($item_alternative_image) ? $item_alternative_image->image_path : null,
                        'actual_stocks' => $actual_stocks
                    ];
                }
            }
        }

        $item_alternatives = collect($item_alternatives)->sortByDesc('actual_stocks')->toArray();

        return view('tbl_item_details', compact('item_details', 'item_attributes', 'site_warehouses', 'item_images', 'item_alternatives', 'consignment_warehouses'));
    }

    public function get_athena_transactions($item_code){
        $logs = DB::table('tabAthena Transactions')->where('item_code', $item_code)->orderBy('transaction_date', 'desc')->paginate(10);

        $list = [];
        foreach($logs as $row){
            $ps_ref = ['Packing Slip', 'Picking Slip'];
            $reference_type = (in_array($row->reference_type, $ps_ref)) ? 'Packing Slip' : $row->reference_type;

            $existing_reference_no = DB::table('tab'.$reference_type)->where('name', $row->reference_parent)->first();
            if(!$existing_reference_no){
                $status = 'DELETED';
            }else{
                if ($existing_reference_no->docstatus == 2) {
                    $status = 'CANCELLED';
                } elseif ($existing_reference_no->docstatus == 0) {
                    $status = 'DRAFT';
                } else {
                    $status = 'SUBMITTED';
                }
            }
            $list[] = [
                'reference_parent' => $row->reference_parent,
                'source_warehouse' => $row->source_warehouse,
                'target_warehouse' => $row->target_warehouse,
                'reference_type' => $row->reference_type,
                'issued_qty' => $row->issued_qty * 1,
                'reference_no' => $row->reference_no,
                'transaction_date' => $row->transaction_date,
                'warehouse_user' => $row->warehouse_user,
                'status' => $status
            ];
        }

        return view('tbl_athena_transactions', compact('list', 'logs', 'item_code'));
    }

    public function get_stock_ledger($item_code, Request $request){
        $logs = DB::table('tabStock Ledger Entry')->where('item_code', $item_code)
            ->orderBy('posting_date', 'desc')->orderBy('posting_time', 'desc')->orderBy('name', 'desc')->paginate(10);

        $list = [];
        foreach($logs as $row){

            if($row->voucher_type == 'Delivery Note'){
                $voucher_no = DB::table('tabPacking Slip')->where('delivery_note', $row->voucher_no)->pluck('name');
                $voucher_no = implode(', ', $voucher_no->toArray());
                $transaction = 'Picking Slip';
            }elseif($row->voucher_type == 'Purchase Receipt'){
                $transaction = $row->voucher_type;
            }elseif($row->voucher_type == 'Stock Reconciliation'){
                $transaction = $row->voucher_type;
                $voucher_no = $row->voucher_no;
            }else{
                $transaction = DB::table('tabStock Entry')->where('name', $row->voucher_no)->first()->purpose;
                $voucher_no = $row->voucher_no;
            }

            if($row->voucher_type == 'Delivery Note'){
                $ref_no = $voucher_no;
            }elseif($row->voucher_type == 'Purchase Receipt'){
                $voucher_no = DB::table('tabPurchase Receipt Item')->where('parent', $row->voucher_no)->where('item_code', $item_code)->distinct()->pluck('purchase_order');
                $voucher_no = implode(', ', $voucher_no->toArray());
                $ref_no = $voucher_no;
            }elseif($row->voucher_type == 'Stock Entry'){
                $ref_no = DB::table('tabStock Entry')->where('name', $row->voucher_no)->pluck('sales_order_no');
                $ref_no = implode(', ', $ref_no->toArray());;
            }elseif($row->voucher_type == 'Stock Reconciliation'){
                $ref_no = $voucher_no;
            }else{
                $ref_no = null;
            }

            if(in_array($transaction, ['Material Transfer for Manufacture', 'Material Transfer', 'Material Issue'])){
                $ste_details = DB::table('tabStock Entry Detail')->where('parent', $row->voucher_no)->where('item_code', $item_code)->first();
                $date_modified = $ste_details->date_modified;
                $session_user = $ste_details->session_user;
            }elseif($transaction == 'Manufacture'){
                $ste_details = DB::table('tabStock Entry')->where('name', $row->voucher_no)->first();
                $date_modified = $ste_details->modified;
                $session_user = null;
            }elseif(in_array($transaction, ['Picking Slip', 'Packing Slip', 'Delivery Note'])){
                $ps_details = DB::table('tabPacking Slip as ps')->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                    ->where('ps.delivery_note', $row->voucher_no)->where('item_code', $item_code)->first();

                $date_modified = ($ps_details) ? $ps_details->date_modified : '--';
                $session_user = ($ps_details) ? $ps_details->session_user : '--';
            }else{
                $date_modified = $row->posting_date;
                $session_user = null;
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
                'posting_date' => $row->posting_date,//cccc
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
        $removed_images = DB::table('tabItem Images')->where('parent', $request->item_code)
            ->whereNotIn('name', $request->existing_images)->pluck('image_path');

        foreach($removed_images as $img) {
            // delete from file directory
            Storage::delete('/public/img/' . $img);
        } 

        // delete from table item images
        DB::table('tabItem Images')->where('parent', $request->item_code)
            ->whereNotIn('name', $request->existing_images)->delete();

        $now = Carbon::now();
        if($request->hasFile('item_image')){
            $files = $request->file('item_image');

            $item_images_arr = [];
            foreach ($files as $i => $file) {
               //get filename with extension
                $filenamewithextension = $file->getClientOriginalName();
                //get filename without extension
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
                //get file extension
                $extension = $file->getClientOriginalExtension();
                //filename to store
                $filenametostore = round(microtime(true)) . $i . '-'. $request->item_code . '.' . $extension;
                // Storage::put('public/employees/'. $filenametostore, fopen($file, 'r+'));
                Storage::put('public/img/'. $filenametostore, fopen($file, 'r+'));

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
                    'image_path' => $filenametostore
                ];
            }

            DB::table('tabItem Images')->insert($item_images_arr);

            return response()->json(['message' => 'Item image for ' . $request->item_code . ' has been uploaded.']);
        }else{
            return response()->json(['message' => 'Item image for ' . $request->item_code . ' has been updated.']);
        }
    }

    public function count_production_to_receive(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        return DB::table('tabStock Entry as ste')
            ->join('tabProduction Order as pro', 'ste.production_order', 'pro.name')
            ->where('ste.purpose', 'Manufacture')->where('ste.docstatus', 0)
            ->select('ste.*', 'pro.production_item', 'pro.description', 'pro.stock_uom')
            ->whereIn('pro.fg_warehouse', $allowed_warehouses)
            ->count();
    }

    public function view_production_to_receive(Request $request){
        if(!$request->arr){
            return view('production_to_receive');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::connection('mysql_mes')->table('production_order AS po')
            ->whereNotIn('po.status', ['Cancelled'])
            ->where('po.produced_qty', '>', 0)
            ->whereRaw('po.produced_qty > feedback_qty')
            ->select('po.*')->get();

        $list = [];
        foreach ($q as $row) {
            $parent_warehouse = $this->get_warehouse_parent($row->fg_warehouse);

            $owner = ucwords(str_replace('.', ' ', explode('@', $row->created_by)[0]));

            $operation_id = ($row->operation_id) ? $row->operation_id : 0;
            $operation_name = DB::connection('mysql_mes')->table('operation')->where('operation_id', $operation_id)->first();
            $operation_name = ($operation_name) ? $operation_name->operation_name : '--';

            $list[] = [
                'production_order' => $row->production_order,
                'fg_warehouse' => $row->fg_warehouse,
                'sales_order_no' => $row->sales_order,
                'material_request' => $row->material_request,
                'customer' => $row->customer,
                'item_code' => $row->item_code,
                'description' => $row->description,
                'qty_to_receive' => $row->produced_qty - $row->feedback_qty,
                'stock_uom' => $row->stock_uom,
                'parent_warehouse' => $parent_warehouse,
                'owner' => $owner,
                'created_at' =>  Carbon::parse($row->created_at)->format('M-d-Y h:i A'),
                'operation_name' => $operation_name
            ];
        }

        return response()->json(['records' => $list]);
    }

    public function create_stock_ledger_entry($stock_entry){
    	try {
            $now = Carbon::now();
            $latest_id = DB::connection('mysql')->table('tabStock Ledger Entry')->max('name');
            $latest_id_exploded = explode("/", $latest_id);
            $new_id = $latest_id_exploded[1] + 1;

            $stock_entry_qry = DB::connection('mysql')->table('tabStock Entry')->where('name', $stock_entry)->first();

            $stock_entry_detail = DB::connection('mysql')->table('tabStock Entry Detail')->where('parent', $stock_entry)->get();

            $s_data = [];
            $t_data = [];
            foreach ($stock_entry_detail as $row) {
                $new_id = $new_id + 1;
                $new_id = str_pad($new_id, 8, '0', STR_PAD_LEFT);
                $id = 'SLEM/'.$new_id;
                
                $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->s_warehouse)
                    ->where('item_code', $row->item_code)->first();
                
                if ($bin_qry) {
                    $actual_qty = $bin_qry->actual_qty;
                    $valuation_rate = $bin_qry->valuation_rate;
                }
                    
                $s_data[] = [
                    'name' => $id,
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'parent' => null,
                    'parentfield' => null,
                    'parenttype' => null,
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
                    'is_cancelled' => 'No',
                    'qty_after_transaction' => $actual_qty,
                    '_user_tags' => null,
                    'batch_no' => $row->batch_no,
                    'stock_value_difference' => ($row->qty * $row->valuation_rate) * -1,
                    'posting_date' => $now->format('Y-m-d'),
                ];
                
                $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
                    ->where('item_code', $row->item_code)->first();

                if ($bin_qry) {
                    $actual_qty = $bin_qry->actual_qty;
                    $valuation_rate = $bin_qry->valuation_rate;
                }
                
                $new_id = $new_id + 1;
                $new_id = str_pad($new_id, 8, '0', STR_PAD_LEFT);
                $id = 'SLEM/'.$new_id;

                $t_data[] = [
                    'name' => $id,
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'parent' => null,
                    'parentfield' => null,
                    'parenttype' => null,
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
                    'is_cancelled' => 'No',
                    'qty_after_transaction' => $actual_qty,
                    '_user_tags' => null,
                    'batch_no' => $row->batch_no,
                    'stock_value_difference' => $row->qty * $row->valuation_rate,
                    'posting_date' => $now->format('Y-m-d'),
                ];
            }

            $stock_ledger_entry = array_merge($s_data, $t_data);

            DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stock_ledger_entry);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage(), 'id' => $stock_entry]);
        }
    }

    public function update_bin($stock_entry){
        try {
            $now = Carbon::now();

            $stock_entry_detail = DB::connection('mysql')->table('tabStock Entry Detail')->where('parent', $stock_entry)->get();

            $latest_id = DB::connection('mysql')->table('tabBin')->max('name');
            $latest_id_exploded = explode("/", $latest_id);
            $new_id = $latest_id_exploded[1] + 1;

            $stock_entry_qry = DB::connection('mysql')->table('tabStock Entry')->where('name', $stock_entry)->first();

            $stock_entry_detail = DB::connection('mysql')->table('tabStock Entry Detail')->where('parent', $stock_entry)->get();
            
            $s_data_insert = [];
            $d_data = [];
            foreach($stock_entry_detail as $row){
               
                    if($row->s_warehouse){
                        $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->s_warehouse)
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
                            'parent' => null,
                            'parentfield' => null,
                            'parenttype' => null,
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

                        DB::connection('mysql')->table('tabBin')->insert($bin);
                    }else{
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $bin_qry->actual_qty - $row->transfer_qty,
                            'stock_value' => $bin_qry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $bin_qry->valuation_rate,
                        ];
        
                        DB::connection('mysql')->table('tabBin')->where('name', $bin_qry->name)->update($bin);
                    }
                    
                }

                if($row->t_warehouse){
                    $bin_qry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
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
                            'parent' => null,
                            'parentfield' => null,
                            'parenttype' => null,
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

                        DB::connection('mysql')->table('tabBin')->insert($bin);
                    }else{
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $bin_qry->actual_qty + $row->transfer_qty,
                            'stock_value' => $bin_qry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $bin_qry->valuation_rate,
                        ];
        
                        DB::connection('mysql')->table('tabBin')->where('name', $bin_qry->name)->update($bin);
                    }
                }
            }
            
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage(), 'id' => $stock_entry]);
        }
    }
	
	public function create_gl_entry($stock_entry){
        try {
            $now = Carbon::now();
            $stock_entry_qry = DB::connection('mysql')->table('tabStock Entry')->where('name', $stock_entry)->first();
            $credit_qry = DB::connection('mysql')->table('tabStock Entry Detail')->where('parent', $stock_entry)
                ->select('s_warehouse', DB::raw('SUM(basic_amount) as basic_amount'), 'parent', 'cost_center', 'expense_account')
                ->groupBy('s_warehouse', 'parent', 'cost_center', 'expense_account')
                ->get();

            $debit_qry = DB::connection('mysql')->table('tabStock Entry Detail')->where('parent', $stock_entry)
                ->select('t_warehouse', DB::raw('SUM(basic_amount) as basic_amount'), 'parent', 'cost_center', 'expense_account')
                ->groupBy('t_warehouse', 'parent', 'cost_center', 'expense_account')
                ->get();
            
            $latest_name = DB::connection('mysql')->table('tabGL Entry')->max('name');
            $latest_name_exploded = explode("L", $latest_name);
            $new_id = $latest_name_exploded[1] + 1;

            $id = [];
            $credit_data = [];
            $debit_data = [];

            foreach ($credit_qry as $row) {
                $new_id = $new_id + 1;
                $new_id = str_pad($new_id, 7, '0', STR_PAD_LEFT);

                $credit_data[] = [
                    'name' => 'MGL'.$new_id,
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'parent' => null,
                    'parentfield' => null,
                    'parenttype' => null,
                    'idx' => 0,
                    'fiscal_year' => $now->format('Y'),
                    'voucher_no' => $row->parent,
                    'cost_center' => $row->cost_center,
                    'credit' => $row->basic_amount,
                    'party_type' => null,
                    'transaction_date' => null,
                    'debit' => 0,
                    'party' => null,
                    '_liked_by' => null,
                    'company' => 'FUMACO Inc.',
                    '_assign' => null,
                    'voucher_type' => 'Stock Entry',
                    '_comments' => null,
                    'is_advance' => 'No',
                    'remarks' => 'Accounting Entry for Stock',
                    'account_currency' => 'PHP',
                    'debit_in_account_currency' => 0,
                    '_user_tags' => null,
                    'account' => $row->s_warehouse,
                    'against_voucher_type' => null,
                    'against' => $row->expense_account,
                    'project' => $stock_entry_qry->project,
                    'against_voucher' => null,
                    'is_opening' => 'No',
                    'posting_date' => $stock_entry_qry->posting_date,
                    'credit_in_account_currency' => $row->basic_amount,
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

            foreach ($debit_qry as $row) {
                $new_id = $new_id + 1;
                $new_id = str_pad($new_id, 7, '0', STR_PAD_LEFT);

                $debit_data[] = [
                    'name' => 'MGL'.$new_id,
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'parent' => null,
                    'parentfield' => null,
                    'parenttype' => null,
                    'idx' => 0,
                    'fiscal_year' => $now->format('Y'),
                    'voucher_no' => $row->parent,
                    'cost_center' => $row->cost_center,
                    'credit' => 0,
                    'party_type' => null,
                    'transaction_date' => null,
                    'debit' => $row->basic_amount,
                    'party' => null,
                    '_liked_by' => null,
                    'company' => 'FUMACO Inc.',
                    '_assign' => null,
                    'voucher_type' => 'Stock Entry',
                    '_comments' => null,
                    'is_advance' => 'No',
                    'remarks' => 'Accounting Entry for Stock',
                    'account_currency' => 'PHP',
                    'debit_in_account_currency' => $row->basic_amount,
                    '_user_tags' => null,
                    'account' => $row->t_warehouse,
                    'against_voucher_type' => null,
                    'against' => $row->expense_account,
                    'project' => $stock_entry_qry->project,
                    'against_voucher' => null,
                    'is_opening' => 'No',
                    'posting_date' => $stock_entry_qry->posting_date,
                    'credit_in_account_currency' => 0,
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

            $gl_entry = array_merge($credit_data, $debit_data);

            DB::connection('mysql')->table('tabGL Entry')->insert($gl_entry);
            
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage(), 'id' => $stock_entry]);
        }
    }

    public function generate_stock_entry($production_order){
        DB::connection('mysql')->beginTransaction();
        try {
            $now = Carbon::now();
            $production_order_details = DB::table('tabProduction Order')
                ->where('name', $production_order)->first();

            // get raw materials from production order items in erp
            $production_order_items = DB::connection('mysql')->table('tabProduction Order Item')
                ->where('parent', $production_order)->orderBy('idx', 'asc')->get();

            foreach ($production_order_items as $index => $row) {
                $pending_ste = DB::connection('mysql')->table('tabStock Entry Detail as sted')
                    ->join('tabStock Entry as ste', 'ste.name', 'sted.parent')
                    ->where('sted.item_code', $row->item_code)->where('ste.production_order', $row->parent)
                    ->where('ste.docstatus', 0)->first();

                if(!$pending_ste){
                    $remaining_qty = $row->required_qty - $row->transferred_qty;

                    $issued_qty = DB::connection('mysql')->table('tabStock Entry as ste')
                        ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                        ->where('ste.production_order', $row->parent)
                        ->where('sted.item_code', $row->item_code)
                        ->where('sted.s_warehouse', $row->source_warehouse)
                        ->where('ste.docstatus', 0)
                        ->where('sted.status', 'Issued')->sum('sted.qty');

                    $remaining_qty = $remaining_qty - $issued_qty;
                    if($remaining_qty > 0){
                        $latest_ste = DB::connection('mysql')->table('tabStock Entry')->max('name');
                        $latest_ste_exploded = explode("-", $latest_ste);
                        $new_id = $latest_ste_exploded[1] + 1;
                        $new_id = str_pad($new_id, 5, '0', STR_PAD_LEFT);
                        $new_id = 'STEM-'.$new_id;
                        
                        $bom_material = DB::connection('mysql')->table('tabBOM Item')
                            ->where('parent', $production_order_details->bom_no)
                            ->where('item_code', $row->item_code)->first();

                        if(!$bom_material){
                            $valuation_rate = DB::connection('mysql')->table('tabBin')
                                ->where('item_code', $row->item_code)
                                ->where('warehouse', $row->source_warehouse)
                                ->sum('valuation_rate');
                        }

                        $conversion_factor = (!$bom_material) ? 1 : $bom_material->conversion_factor;

                        $base_rate = ($bom_material) ? $bom_material->base_rate : $valuation_rate;

                        $actual_qty = DB::connection('mysql')->table('tabBin')
                            ->where('item_code', $row->item_code)->where('warehouse', $row->source_warehouse)
                            ->sum('actual_qty');

                        if(in_array($row->source_warehouse, ['Fabrication - FI', 'Spotwelding Warehouse - FI']) && $actual_qty > $row->required_qty){
                            $item_status = 'Issued';
                        }else{
                            $item_status = 'For Checking';
                        }

                        $docstatus = ($item_status == 'Issued') ? 1 : 0;
            
                        $stock_entry_detail = [
                            'name' =>  uniqid(),
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => $production_order_details->owner,
                            'docstatus' => $docstatus,
                            'parent' => $new_id,
                            'parentfield' => 'items',
                            'parenttype' => 'Stock Entry',
                            'idx' => $index + 1,
                            't_warehouse' => $production_order_details->wip_warehouse,
                            'transfer_qty' => $remaining_qty,
                            'serial_no' => null,
                            'expense_account' => 'Cost of Goods Sold - FI',
                            'cost_center' => 'Main - FI',
                            'actual_qty' => $actual_qty,
                            's_warehouse' => $row->source_warehouse,
                            'item_name' => $row->item_name,
                            'image' => null,
                            'additional_cost' => 0,
                            'stock_uom' => $row->stock_uom,
                            'basic_amount' => $base_rate * $remaining_qty,
                            'sample_quantity' => 0,
                            'uom' => $row->stock_uom,
                            'basic_rate' => $base_rate,
                            'description' => $row->description,
                            'barcode' => null,
                            'conversion_factor' => $conversion_factor,
                            'item_code' => $row->item_code,
                            'retain_sample' => 0,
                            'qty' => $remaining_qty,
                            'bom_no' => null,
                            'allow_zero_valuation_rate' => 0,
                            'material_request_item' => null,
                            'amount' => $base_rate * $remaining_qty,
                            'batch_no' => null,
                            'valuation_rate' => $base_rate,
                            'material_request' => null,
                            't_warehouse_personnel' => null,
                            's_warehouse_personnel' => null,
                            'target_warehouse_location' => null,
                            'source_warehouse_location' => null,
                            'status' => $item_status,
                            'date_modified' => ($item_status == 'Issued') ? $now->toDateTimeString() : null,
                            'session_user' => ($item_status == 'Issued') ? Auth::user()->full_name : null,
                            'remarks' => ($item_status == 'Issued') ? 'MES' : null,
                        ];

                        $stock_entry_data = [
                            'name' => $new_id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => $production_order_details->owner,
                            'docstatus' => $docstatus,
                            'parent' => null,
                            'parentfield' => null,
                            'parenttype' => null,
                            'idx' => 0,
                            'use_multi_level_bom' => 1,
                            'delivery_note_no' => null,
                            'naming_series' => 'STE-',
                            'fg_completed_qty' => $production_order_details->qty,
                            'letter_head' => null,
                            '_liked_by' => null,
                            'purchase_receipt_no' => null,
                            'posting_time' => $now->format('H:i:s'),
                            'customer_name' => null,
                            'to_warehouse' => $production_order_details->wip_warehouse,
                            'title' => 'Material Transfer for Manufacture',
                            '_comments' => null,
                            'from_warehouse' => null,
                            'set_posting_time' => 0,
                            'purchase_order' => null,
                            'from_bom' => 1,
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
                            'customer_address' => null,
                            'total_outgoing_value' => collect($stock_entry_detail)->sum('basic_amount'),
                            'supplier_name' => null,
                            'remarks' => null,
                            '_user_tags' => null,
                            'total_additional_costs' => 0,
                            'customer' => null,
                            'bom_no' => $production_order_details->bom_no,
                            'amended_from' => null,
                            'total_amount' => collect($stock_entry_detail)->sum('basic_amount'),
                            'total_incoming_value' => collect($stock_entry_detail)->sum('basic_amount'),
                            'project' => $production_order_details->project,
                            '_assign' => null,
                            'select_print_heading' => null,
                            'posting_date' => $now->format('Y-m-d'),
                            'target_address_display' => null,
                            'production_order' => $production_order,
                            'purpose' => 'Material Transfer for Manufacture',
                            'shipping_address_contact_person' => null,
                            'customer_1' => null,
                            'material_request' => $production_order_details->material_request,
                            'reference_no' => null,
                            'delivery_date' => null,
                            'delivery_address' => null,
                            'city' => null,
                            'address_line_2' => null,
                            'address_line_1' => null,
                            'item_status' => $item_status,
                            'sales_order_no' => $production_order_details->sales_order_no,
                            'transfer_as' => 'Internal Transfer',
                            'workflow_state' => null,
                            'item_classification' => $production_order_details->item_classification,
                            'bom_repack' => null,
                            'qty_repack' => 0,
                            'issue_as' => null,
                            'receive_as' => null,
                            'so_customer_name' => $production_order_details->customer,
                            'order_type' => $production_order_details->classification,
                        ];
            
                        DB::connection('mysql')->table('tabStock Entry Detail')->insert($stock_entry_detail);
                        DB::connection('mysql')->table('tabStock Entry')->insert($stock_entry_data);
                        
                        if ($docstatus == 1) {
                            $production_order_item = [
                                'transferred_qty' => $row->required_qty
                            ];
            
                            DB::connection('mysql')->table('tabProduction Order Item')->where('name', $row->name)->update($production_order_item);

                            if($production_order_details->status == 'Not Started'){
                                DB::connection('mysql')->table('tabProduction Order')
                                    ->where('name', $production_order_details->name)
                                    ->update(['status' => 'In Process', 'material_transferred_for_manufacturing' => $production_order_details->qty]);
                            }
                
                            $this->update_bin($new_id);
                            $this->create_stock_ledger_entry($new_id);
                            $this->create_gl_entry($new_id);
                        }
                    }
                }
            }

            DB::connection('mysql')->commit();

            return response()->json(['success' => 1, 'message' => 'Stock Entry has been created.']);
        } catch (Exception $e) {
            DB::connection('mysql')->rollback();
            return response()->json(['success' => 0, 'message' => 'There was a problem creating stock entries.']);
        }
    }

    public function submit_stock_entry($id){
        try {
            $now = Carbon::now();
            $draft_ste = DB::table('tabStock Entry')->where('name', $id)->where('docstatus', 0)->first();
            if($draft_ste){
                if ($draft_ste->purpose != 'Manufacture') {
                     // check if all items are issued
                    $count_not_issued_items = DB::table('tabStock Entry Detail')->whereNotIn('status', ['Issued', 'Returned'])->where('parent', $draft_ste->name)->count();
                    if($count_not_issued_items > 0){
                        return response()->json(['success' => 0, 'message' => 'All item(s) must be issued.']);
                    }
                }

                if($draft_ste->purpose == 'Material Transfer for Manufacture'){
                    $production_order_details = DB::table('tabProduction Order')->where('name', $draft_ste->production_order)->first();

                    // get total "for quantity" (submitted)
                    $transferred_qty = DB::table('tabStock Entry')
                        ->where('production_order', $draft_ste->production_order)->where('docstatus', 1)
                        ->where('purpose', 'Material Transfer for Manufacture')->sum('fg_completed_qty');
                    
                    $total_transferred_qty = $transferred_qty + $draft_ste->fg_completed_qty;
                    if ($total_transferred_qty > $production_order_details->qty) {
                        $fg_completed_qty = $production_order_details->qty - $transferred_qty;
                    }else{
                        $fg_completed_qty = $draft_ste->fg_completed_qty;
                    }

                    $material_transferred_for_manufacturing = $transferred_qty + $fg_completed_qty;

                    DB::table('tabProduction Order')->where('name', $draft_ste->production_order)
                        ->update(['status' => 'In Process', 'material_transferred_for_manufacturing' => $material_transferred_for_manufacturing]);
                
                    $values = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'fg_completed_qty' => $fg_completed_qty
                    ];
                }else{
                    $values = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'docstatus' => 1
                    ];
                }
               
                DB::table('tabStock Entry')->where('name', $id)->update($values);
                DB::table('tabStock Entry Detail')->where('parent', $id)->update([
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'docstatus' => 1
                ]);

                if($draft_ste->purpose == 'Material Transfer for Manufacture'){
                    $this->update_production_order_items($production_order_details->name);

                    if($production_order_details->status == 'Not Started'){
                        $values = [
                            'status' => 'In Process',
                            'material_transferred_for_manufacturing' => $production_order_details->qty
                        ];
                    }else{
                        $values = [
                            'material_transferred_for_manufacturing' => $production_order_details->qty
                        ];
                    }
    
                    DB::connection('mysql')->table('tabProduction Order')
                        ->where('name', $production_order_details->name)
                        ->update($values);
                }

                $this->update_bin($id);
                $this->create_stock_ledger_entry($id);
                $this->create_gl_entry($id);
            }
        } catch (Exception $e) {
            // DB::rollback();
            // return response()->json(['error' => 1, 'modal_title' => 'Warning', 'modal_message' => 'There was a problem creating transaction.']);
        }
    }

    public function update_production_order_items($production_order){
        $production_order_items = DB::table('tabProduction Order Item')->where('parent', $production_order)->get();
        foreach ($production_order_items as $row) {
            $transferred_qty = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.production_order', $production_order)->where('ste.purpose', 'Material Transfer for Manufacture')
                ->where('ste.docstatus', 1)->where('item_code', $row->item_code)->sum('qty');
            
                
                DB::table('tabProduction Order Item')
                    ->where('parent', $production_order)
                    ->where('item_code', $row->item_code)->update(['transferred_qty' => $transferred_qty]);
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

            DB::connection('mysql')->table('tabStock Entry')->where('name', $request->ste_no)->update($stock_entry_data);


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
        } catch (Exception $e) {
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
        $start = Carbon::now()->startOfDay()->toDateTimeString();
		$end = Carbon::now()->endOfDay()->toDateTimeString();

        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $stock_entries = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowed_warehouses)->where('se.docstatus', '<', 2)->where('sed.status', '!=', 'For Checking')
            ->whereBetween('sed.date_modified', [$start, $end])
            ->select('se.purpose', 'sed.date_modified', 'sed.status', 'se.docstatus', 'se.transfer_as', 'se.issue_as')->get();

        $stock_entries_for_return = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.t_warehouse', $allowed_warehouses)->where('se.docstatus', '<', 2)->where('sed.status', '!=', 'For Checking')
            ->where('transfer_as', 'For Return')->whereBetween('sed.date_modified', [$start, $end])
            ->select('se.purpose', 'sed.date_modified', 'sed.status', 'se.docstatus', 'se.transfer_as', 'se.issue_as')->count();

        $picking_slips = DB::table('tabPacking Slip as ps')->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->where('psi.session_user', Auth::user()->full_name)->where('ps.docstatus', '<', 2)->where('psi.status', '!=', 'For Checking')
            ->whereBetween('psi.date_modified', [$start, $end])->count();

        $feedbacks = DB::table('tabStock Entry as ste')->join('tabProduction Order as pro', 'ste.production_order', 'pro.name')
            ->where('ste.purpose', 'Manufacture')->where('ste.docstatus', 1)->whereIn('pro.fg_warehouse', $allowed_warehouses)
            ->whereBetween('ste.posting_date', [$start, $end])->count();

        $sales_returns = DB::table('tabDelivery Note as dn')->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
            ->where('dn.is_return', 1)->where('dn.docstatus', 1)->whereIn('dni.warehouse', $allowed_warehouses)
            ->whereBetween('dn.posting_date', [$start, $end])->count();

        $purchase_receipts = DB::table('tabPurchase Receipt as pr')->join('tabPurchase Receipt Item as pri', 'pr.name', 'pri.parent')
            ->where('pr.docstatus', 1)->whereIn('pri.warehouse', $allowed_warehouses)->whereBetween('pr.creation', [Carbon::now()->subDays(7), Carbon::now()])->whereBetween('pr.posting_date', [$start, $end])->count();

        $purchase_orders = DB::table('tabPurchase Receipt as pr')
            ->join('tabPurchase Receipt Item as pri', 'pr.name', 'pri.parent')->where('pr.docstatus', 0)
            ->whereIn('pri.warehouse', $allowed_warehouses)
            ->whereBetween('pr.creation', [Carbon::now()->subDays(7), Carbon::now()])->count();

        $pending_stock_entries = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowed_warehouses)->where('se.docstatus', 0)->where('sed.status', '!=', 'For Checking')
            ->select('se.purpose', 'sed.date_modified', 'sed.status', 'se.docstatus', 'se.transfer_as', 'se.issue_as')->get();

        $pending_stock_entries_for_return = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.t_warehouse', $allowed_warehouses)->where('se.docstatus', 0)->where('sed.status', '!=', 'For Checking')
            ->where('transfer_as', 'For Return')->select('se.purpose', 'sed.date_modified', 'sed.status', 'se.docstatus', 'se.transfer_as', 'se.issue_as')->count();

        $pending_sales_returns = DB::table('tabDelivery Note as dn')->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
            ->where('dn.is_return', 1)->where('dn.docstatus', 0)->whereIn('dni.warehouse', $allowed_warehouses)->count();

        return [
            'd_withdrawals' => collect($stock_entries)->where('purpose', 'Material Transfer for Manufacture')->count(),
            'd_material_issues' => collect($stock_entries)->where('purpose', 'Material Issue')->where('issue_as', '!=', 'Customer Replacement')->count(),
            'd_replacements' => collect($stock_entries)->where('purpose', 'Material Issue')->where('issue_as', 'Customer Replacement')->count(),
            'd_internal_transfers' => collect($stock_entries)->where('purpose', 'Material Transfer')->where('transfer_as', '!=', 'For Return')->count(),
            'd_returns' => $stock_entries_for_return + $sales_returns,
            'd_picking_slips' => $picking_slips,
            'd_feedbacks' => $feedbacks,
            'd_purchase_receipts' => $purchase_receipts,
            'p_purchase_receipts' => $purchase_orders,
            'p_replacements' => collect($pending_stock_entries)->where('purpose', 'Material Issue')->where('issue_as', 'Customer Replacement')->count(),
            'p_returns' => $pending_stock_entries_for_return + $pending_sales_returns,
        ];
    }

    public function get_reserved_qty($item_code, $warehouse){
        $reserved_qty_for_website = DB::table('tabBin')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->sum('website_reserved_qty');

        $stock_reservation_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->where('type', 'In-house')->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

        $consumed_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->where('type', 'In-house')->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');

        return ($reserved_qty_for_website + $stock_reservation_qty) + $consumed_qty;
    }

    public function get_item_images($item_code){
        return DB::table('tabItem Images')->where('parent', $item_code)->pluck('image_path', 'name');
    }

    public function set_reservation_as_expired(){
        return DB::table('tabStock Reservation')->where('type', 'In-house')
            ->where('status', 'Active')->whereDate('valid_until', '<=', Carbon::now())
            ->update(['status' => 'Expired']);
    }

    public function get_low_stock_level_items(Request $request){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);
        
        $query = DB::table('tabItem as i')->join('tabItem Reorder as ir', 'i.name', 'ir.parent')
            ->select('ir.name as id', 'i.item_code', 'i.description', 'ir.warehouse', 'ir.warehouse_reorder_level', 'i.stock_uom', 'ir.warehouse_reorder_qty', 'i.item_classification')
            ->whereIn('default_warehouse', $allowed_warehouses)->get();

        $low_level_stocks = [];
        foreach ($query as $a) {
            $actual_qty = $this->get_actual_qty($a->item_code, $a->warehouse);

            if($actual_qty <= $a->warehouse_reorder_level) {

                $existing_mr = DB::table('tabMaterial Request as mr')
                    ->join('tabMaterial Request Item as mri', 'mr.name', 'mri.parent')
                    ->where('mr.docstatus', '<', 2)->where('mr.status', 'Pending')->where('mri.item_code', $a->item_code)
                    ->where('mri.warehouse', $a->warehouse)->select('mr.name')->first();

                $item_image_path = DB::table('tabItem Images')->where('parent', $a->item_code)->first();

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
                    'image' => ($item_image_path) ? $item_image_path->image_path : null,
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

    public function get_recently_added_items(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabItem')->where('disabled', 0)
            ->where('has_variants', 0)->where('is_stock_item', 1)
            ->whereIn('default_warehouse', $allowed_warehouses)
            ->orderBy('creation', 'desc')->limit(5)->get();

        $list = [];
        foreach($q as $row){
            $item_image_path = DB::table('tabItem Images')->where('parent', $row->name)->first();

            $list[] = [
                'item_code' => $row->item_code,
                'description' => $row->description,
                'default_warehouse' => $row->default_warehouse, //CCCCC
                'image' => ($item_image_path) ? $item_image_path->image_path : null
            ];
        }

        return view('recently_added_items', compact('list'));
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

    public function returns(){
        return view('returns');
    }

    public function replacements(Request $request){
        if(!$request->arr){
            return view('replacement');
         }
         
         $user = Auth::user()->frappe_userid;
         $allowed_warehouses = $this->user_allowed_warehouse($user);
 
         $q = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowed_warehouses)->where('se.docstatus', 0)
            ->where('se.purpose', 'Material Issue')->where('se.issue_as', 'Customer Replacement')
            ->select('sed.status', 'sed.validate_item_code', 'se.sales_order_no', 'sed.parent', 'sed.name', 'sed.t_warehouse', 'sed.s_warehouse', 'sed.item_code', 'sed.description', 'sed.uom', 'sed.qty', 'sed.owner', 'se.material_request', 'se.creation')
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
                 'parent_warehouse' => $parent_warehouse,
                 'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
             ];
         }

         return response()->json(['records' => $list]);
    }

    public function receipts(Request $request){
        if(!$request->arr){
           return view('receipt');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabPurchase Receipt as pr')
            ->join('tabPurchase Receipt Item as pri', 'pr.name', 'pri.parent')->where('pr.docstatus', 0)
            ->whereIn('pri.warehouse', $allowed_warehouses)
            ->whereBetween('pr.creation', [Carbon::now()->subDays(7), Carbon::now()])
            ->select('pri.parent', 'pri.name', 'pri.warehouse', 'pri.item_code', 'pri.description', 'pri.uom', 'pri.qty', 'pri.owner', 'pr.creation', 'pr.purchase_order')
            ->get();

        $list = [];
        foreach ($q as $d) {
            $available_qty = $this->get_available_qty($d->item_code, $d->warehouse);

            $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');
            $part_nos = implode(', ', $part_nos->toArray());

            $owner = DB::table('tabUser')->where('email', $d->owner)->first();
            $owner = ($owner) ? $owner->full_name : null;

            $parent_warehouse = $this->get_warehouse_parent($d->warehouse);

            $list[] = [
                'item_code' => $d->item_code,
                'description' => $d->description,
                'warehouse' => $d->warehouse,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'status' => 'To Receive',
                'available_qty' => $available_qty,
                'ref_no' => $d->purchase_order,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A')
            ];
        }
        
        return response()->json(['records' => $list]);
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
        if(!$request->arr){
            return view('picking_slip');
        }
        
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))
                ->where('ps.docstatus', 0)
                ->where('dri.docstatus', 0)
                ->whereIn('dri.warehouse', $allowed_warehouses)
                ->select('ps.sales_order', 'psi.name AS id', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', DB::raw('SUM(dri.qty) as qty'), 'psi.stock_uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation')
                ->groupBy('ps.sales_order', 'psi.name', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.stock_uom', 'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation')
                ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC")->get();

        $list = [];
        foreach ($q as $d) {
            $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');

            $part_nos = implode(', ', $part_nos->toArray());

            $owner = DB::table('tabUser')->where('email', $d->owner)->first();
            $owner = ($owner) ? $owner->full_name : null;

            $parent_warehouse = $this->get_warehouse_parent($d->warehouse);

            $list[] = [
                'owner' => $owner,
                'warehouse' => $d->warehouse,
                'customer' => $d->customer,
                'sales_order' => $d->sales_order,
                'id' => $d->id,
                'status' => $d->status,
                'name' => $d->name,
                'delivery_note' => $d->delivery_note,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'qty' => $d->qty,
                'stock_uom' => $d->stock_uom,
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'type' => 'picking_slip',
                'classification' => 'Customer Order'
            ];
        }

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowed_warehouses)->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'ste.customer_1', 'sted.parent', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'sted.name as id', 'sted.stock_uom')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();

            foreach ($q as $d) {
                $part_nos = DB::table('tabItem Supplier')->where('parent', $d->item_code)->pluck('supplier_part_no');
    
                $part_nos = implode(', ', $part_nos->toArray());
    
                $owner = DB::table('tabUser')->where('email', $d->owner)->first();
                $owner = ($owner) ? $owner->full_name : null;
    
                $parent_warehouse = $this->get_warehouse_parent($d->s_warehouse);
    
                $list[] = [
                    'owner' => $owner,
                    'warehouse' => $d->s_warehouse,
                    'customer' => $d->customer_1,
                    'sales_order' => $d->sales_order_no,
                    'id' => $d->id,
                    'status' => $d->status,
                    'name' => $d->name,
                    'delivery_note' => null,
                    'item_code' => $d->item_code,
                    'description' => $d->description,
                    'qty' => $d->qty,
                    'stock_uom' => $d->stock_uom,
                    'parent_warehouse' => $parent_warehouse,
                    'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                    'type' => 'stock_entry',
                    'classification' => $d->transfer_as
                ];
            }
        
        return response()->json(['picking' => $list]);
    }

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
            ->select('sle.creation as transaction_date', 'voucher_type as transaction_type', 'sle.item_code', 'i.description', 'sle.warehouse', 'sle.qty_after_transaction as qty', 'sle.voucher_no as reference_no', 'sle.voucher_no as reference_parent', 'sle.owner as user');

        $list = DB::table('tabAthena Transactions')->whereIn('source_warehouse', $allowed_warehouses)
            ->whereBetween('transaction_date', [$startOfYear, $endOfYear])
            ->whereMonth('transaction_date', $request->month)
            ->select('transaction_date', 'transaction_type', 'item_code', 'description', 'source_warehouse as warehouse', 'qty', 'reference_no', 'reference_parent', 'warehouse_user as user')
            ->orderBy('transaction_date', 'desc')->union($stock_adjustments_query)->orderBy('transaction_date', 'desc')->get();

        return view('tbl_athena_logs', compact('list'));
    }

    public function update_reservation_status(){
        // update status expired
        DB::table('tabStock Reservation')->whereIn('status', ['Active', 'Partially Issued'])
            ->where('type', 'In-house')->where('valid_until', '<', Carbon::now())->update(['status' => 'Expired']);
        // update status partially issued
        DB::table('tabStock Reservation')
            ->whereNotIn('status', ['Cancelled', 'Issued', 'Expired'])
            ->where('consumed_qty', '>', 0)->whereRaw('consumed_qty < reserve_qty')
            ->where('type', 'In-house')->update(['status' => 'Partially Issued']);
        // update status issued
        DB::table('tabStock Reservation')->whereNotIn('status', ['Cancelled', 'Expired'])
         ->where('consumed_qty', '>', 0)->whereRaw('consumed_qty >= reserve_qty')
         ->where('type', 'In-house')->update(['status' => 'Issued']);
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
                'modified_by' => Auth::user()->email,
                'owner' => Auth::user()->email,
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
                'modified_by' => Auth::user()->email,
                'owner' => Auth::user()->email,
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
        } catch (Exception $e) {
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
}