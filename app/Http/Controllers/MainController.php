<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\StockReservation;
use Auth;
use DB;

class MainController extends Controller
{
    public function index(Request $request){
        $user = Auth::user()->frappe_userid;

        $allowed_warehouses = DB::table('tabWarehouse Access')->where('parent', $user)->distinct()->pluck('warehouse_name')->toArray();

        return view('index', compact('allowed_warehouses'));
    }

    public function search_results(Request $request){
        $search_str = explode(' ', $request->searchString);
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
                $warehouses = DB::table('tabWarehouse')->where('parent_warehouse', $request->wh)->pluck('name');
				return $q->whereIn('default_warehouse', $warehouses);
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
                ->where('actual_qty', '>', 0)->select('item_code', 'warehouse', 'actual_qty', 'stock_uom', 'is_consignment_warehouse')->get();

            $site_warehouses = [];
            $consignment_warehouses = [];
            $consignment_warehouse_count = 0;
            foreach ($item_inventory as $value) {
                $reserved_qty = StockReservation::where('item_code', $value->item_code)
                    ->where('warehouse', $value->warehouse)->sum('reserve_qty');

                $consignment_warehouse_count += $value->is_consignment_warehouse;
                if($value->is_consignment_warehouse < 1) {
                    $site_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $value->actual_qty - $this->get_issued_qty($value->item_code, $value->warehouse),
                        'stock_uom' => $value->stock_uom,
                    ];
                }else{
                    $consignment_warehouses[] = [
                        'warehouse' => $value->warehouse,
                        'reserved_qty' => $reserved_qty,
                        'actual_qty' => $value->actual_qty - $this->get_issued_qty($value->item_code, $value->warehouse),
                        'stock_uom' => $value->stock_uom,
                    ];
                }
            }

            $part_nos = DB::table('tabItem Supplier')->where('parent', $row->name)->pluck('supplier_part_no');

            $part_nos = implode(', ', $part_nos->toArray());

            $item_list[] = [
                'name' => $row->name,
                'description' => $row->description,
                'item_image_path' => $row->item_image_path,
                'part_nos' => $part_nos,
                'item_group' => $row->item_group,
                'stock_uom' => $row->stock_uom,
                'item_classification' => $row->item_classification,
                'item_inventory' => $site_warehouses,
                'consignment_warehouses' => $consignment_warehouses,
                'consignment_warehouse_count' => $consignment_warehouse_count
            ];
        }

        return view('search_results', compact('item_list', 'items'));
    }

    public function count_ste_for_issue($purpose){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        return DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('purpose', $purpose)
            ->where('sted.status', '!=', 'Issued')
            ->whereIn('s_warehouse', $allowed_warehouses)->count();
    }

    public function count_ps_for_issue(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        return DB::table('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
            ->whereRaw(('dri.item_code = psi.item_code'))
            ->where('ps.docstatus', 0)->where('psi.status', 'For Checking')
            ->where('dri.docstatus', 0)->whereIn('dri.warehouse', $allowed_warehouses)->count();
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
        $warehouses = DB::table('tabWarehouse')->where('is_group', 1)
            ->where('parent_warehouse', 'All Warehouses - FI')->orderBy('name', 'asc')->pluck('name');

        $item_groups = DB::table('tabItem Group')->where('is_group', 0)
            ->where('show_in_erpinventory', 1)->orderBy('name', 'asc')->pluck('name');

        $item_classification = DB::table('tabItem Classification')
            ->orderBy('name', 'asc')->pluck('name');

        return response()->json([
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
        
        // $total_issued += DB::table('tabPacking Slip as ps')
        //     ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
        //     ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
        //     ->whereRaw(('dri.item_code = psi.item_code'))
        //     ->where('ps.docstatus', 0)->where('dri.docstatus', 0)->where('psi.status', 'Issued')
        //     ->where('psi.item_code', $item_code)->where('dri.warehouse', $warehouse)->sum('psi.qty');

        $total_issued += DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            // ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
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
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.creation')
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
                'parent_warehouse' => $parent_warehouse,
                'creation' => Carbon::parse($d->creation)->format('Y-m-d h:i:A')
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
            if($production_order){
                $per_item_delivery_date = DB::table('tabSales Order Item')->where('parent', $production_order->sales_order)
                    ->where('item_code', $production_order->parent_item_code)->first();

                if($per_item_delivery_date){
                    $delivery_date = $per_item_delivery_date->rescheduled_delivery_date;
                }
            }

            $list[] = [
                'delivery_date' => Carbon::parse($delivery_date)->format('M-d-Y'),
                'customer' => $d->so_customer_name,
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
                'creation' => Carbon::parse($d->creation)->format('Y-m-d h:i:A')
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
            ->whereIn('s_warehouse', $allowed_warehouses)->where('ste.transfer_as', '!=', 'For Return')
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation')
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
                'creation' => Carbon::parse($d->creation)->format('Y-m-d h:i:A')
            ];
        }
        
        return response()->json(['records' => $list]);
    }

    public function view_picking_slip(Request $request){
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
                'creation' => Carbon::parse($d->creation)->format('Y-m-d h:i:A')
            ];
        }
        
        return response()->json(['picking' => $list]);
    }

    public function get_pending_item_request_for_issue(){
        $q = DB::table('tabProduction Order as pro')
            ->join('tabProduction Order Item as prod_item', 'pro.name', 'prod_item.parent')
            ->where('pro.status', '!=', 'Completed')->where('pro.sales_order', '!=', '')
            ->select('pro.sales_order', 'pro.customer', 'pro.qty as qty_to_manufacture', 'prod_item.item_code as bom_item', DB::raw('required_qty - transferred_qty as pending1'), 'pro.name as production_order', 'prod_item.transferred_qty', 'prod_item.description', 'prod_item.required_qty')
            ->whereRaw('required_qty > transferred_qty')
            ->where('transferred_qty', '>', 0)
            ->get();

        $list = [];
        foreach ($q as $r) {
            $ste_nos = DB::table('tabStock Entry as ste')->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('purpose', 'Material Transfer for Manufacture')->where('ste.docstatus', 1)
                ->where('production_order', $r->production_order)->where('sted.item_code', $r->bom_item)->pluck('ste.name', 'ste.owner');
            $ste = '';
            $owners = '';
            foreach($ste_nos as $owner => $ste_no){
                $ste .= $ste_no . ' ';
                $owners .= $owner . ' ';
            }
            $list[] = [
                'sales_order' => $r->sales_order,
                'customer' => $r->customer,
                'qty_to_manufacture' => $r->qty_to_manufacture,
                'bom_item' => $r->bom_item,
                'pending1' => $r->pending1,
                'production_order' => $r->production_order,
                'transferred_qty' => $r->transferred_qty,
                'description' => $r->description,
                'required_qty' => $r->required_qty,
                'ste_nos' => $ste,
                'rqst' => $owners
            ];
        }

        return response()->json(['pending' => $list]);
    }

    public function get_items_for_return(){
        $user = Auth::user()->frappe_userid;
        $allowed_warehouses = $this->user_allowed_warehouse($user);

        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->whereIn('purpose', ['Material Transfer', 'Material Transfer for Manufacture'])
            ->where('ste.transfer_as', 'For Return')->whereIn('sted.t_warehouse', $allowed_warehouses)
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.actual_qty', 'ste.material_request', 'ste.owner', 'ste.transfer_as', 'ste.so_customer_name')
            ->orderBy('sted.status', 'asc')->get();

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

            $parent_warehouse = $this->get_warehouse_parent($d->t_warehouse);

            $list[] = [
                'customer' => $d->so_customer_name,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'actual_qty' => $actual_qty,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'transfer_as' => $d->transfer_as,
                'parent' => $d->parent,
                'part_nos' => $part_nos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'sales_order_no' => $d->sales_order_no,
                'balance' => $balance,
                'ref_no' => $ref_no,
                'parent_warehouse' => $parent_warehouse
            ];
        }
        
        return response()->json(['return' => $list]);
    }

    public function get_dr_return(){
        $q = DB::table('tabDelivery Note as dr')
            ->join('tabDelivery Note Item as dri', 'dr.name', 'dri.parent')
            ->where('dr.is_return', 1)->where('dr.docstatus', 0)
            ->select('dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status', 'dri.owner')->get();

        $list = [];
        foreach ($q as $d) {
            $owner = DB::table('tabUser')->where('email', $d->owner)->first();
            $owner = ($owner) ? $owner->full_name : null;

            $list[] = [
                'owner' => $owner,
                'c_name' => $d->c_name,
                'name' => $d->name,
                'customer' => $d->customer,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'warehouse' => $d->warehouse,
                'qty' => $d->qty,
                'against_sales_order' => $d->against_sales_order,
                'dr_ref_no' => $d->dr_ref_no,
                'item_status' => $d->item_status,
            ];
        }
        
        return response()->json(['return' => $list]);
    }

    public function get_mr_sales_return(){
        $q = DB::table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)->where('ste.purpose', 'Material Receipt')
            ->where('ste.receive_as', 'Sales Return')
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner')
            ->get();

        $list = [];
        foreach ($q as $d) {
            $owner = DB::table('tabUser')->where('email', $d->owner)->first();
            $owner = ($owner) ? $owner->full_name : null;

            $list[] = [
                'stedname' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
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
            ->select('ste.production_order', 'ste.transfer_as', 'ste.purpose', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.actual_qty', 'sted.validate_item_code', 'sted.owner', 'sted.status', 'sted.remarks', 'sted.stock_uom', 'ste.sales_order_no', 'ste.material_request')
            ->first();

        $ref_no = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;

        $owner = DB::table('tabUser')->where('email', $q->owner)->first();
        $owner = ($owner) ? $owner->full_name : null;

        $img = DB::table('tabItem')->where('name', $q->item_code)->first()->item_image_path;

        $actual_qty = $this->get_actual_qty($q->item_code, $q->s_warehouse);

        $total_issued = $this->get_issued_qty($q->item_code, $q->s_warehouse);
        
        $balance = $actual_qty - $total_issued;

        $data = [
            'production_order' => $q->production_order,
            'parent' => $q->parent,
            'purpose' => $q->purpose,
            'name' => $q->name,
            's_warehouse' => $q->s_warehouse,
            'total_issued_qty' => $balance,
            'item_code' => $q->item_code,
            't_warehouse' => $q->t_warehouse,
            'transfer_as' => $q->transfer_as,
            'validate_item_code' => $q->validate_item_code,
            'img' => $img,
            'qty' => $q->qty,
            'description' => $q->description,
            'owner' => $owner,
            't_warehouse' => $q->t_warehouse,
            'ref_no' => $ref_no,
            'status' => $q->status,
            'stock_uom' => $q->stock_uom,
            'remarks' => $q->remarks,
        ];

        return response()->json($data);
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
            ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.qty', 'psi.stock_uom', 'psi.name as id', 'dri.warehouse', 'psi.status', 'psi.stock_uom', 'psi.qty', 'dri.name as dri_name')
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
                $actual_qty = $this->get_actual_qty($row->item_code, $row->warehouse);

                $total_issued = $this->get_issued_qty($row->item_code, $row->warehouse);
                
                $available_qty = $actual_qty - $total_issued;

                $product_bundle_items[] = [
                    'item_code' => $row->item_code,
                    'description' => $row->description,
                    'uom' => $row->uom,
                    'qty' => ($row->qty * $q->qty),
                    'available_qty' => $available_qty,
                    'warehouse' => $row->warehouse
                ];
            }
        }

        $actual_qty = $this->get_actual_qty($q->item_code, $q->warehouse);

        $total_issued = $this->get_issued_qty($q->item_code, $q->warehouse);
        
        $balance = $actual_qty - $total_issued;

        $data = [
            'id' => $q->id,
	        'barcode' => $q->barcode,
            'item_image' => $item_details->item_image_path,
            'delivery_note' => $q->delivery_note,
            'description' => $q->description,
            'item_code' => $q->item_code,
            'name' => $q->name,
            'status' => $q->status,
            'stock_uom' => $q->stock_uom,
            'qty' => $q->qty,
            'wh' => $q->warehouse,
            'actual_qty' => $actual_qty * 1,
            'is_bundle' => $is_bundle,
            'product_bundle_items' => $product_bundle_items,
            'dri_name' => $q->dri_name
        ];

        return response()->json($data);
    }

    public function get_dr_return_details($id){
        $q = DB::table('tabDelivery Note as dr')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
            ->where('dr.is_return', 1)
            ->where('dr.docstatus', 0)
            ->where('dri.name', $id)
            ->select('dri.barcode_return', 'dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status')
            ->first();

        $img = DB::table('tabItem')->where('name', $q->item_code)->first()->item_image_path;

        $data = [
            'barcode_return' => $q->barcode_return,
            'c_name' => $q->c_name,
            'name' => $q->name,
            'item_image' => $img,
            'customer' => $q->customer,
            'description' => $q->description,
            'item_code' => $q->item_code,
            'warehouse' => $q->warehouse,
            'dr_ref_no' => $q->dr_ref_no,
            'qty' => $q->qty,
            'item_status' => $q->item_status,
            'against_sales_order' => $q->against_sales_order,
        ];

        return response()->json($data);
    }

    public function checkout_ste_item(Request $request){
        DB::beginTransaction();
        try {

            $item_details = DB::table('tabItem')->where('name', $request->item_code)->first();
            if(!$item_details){
                return response()->json(['error' => 1, 'modal_title' => 'Not Found', 'modal_message' => 'Item  <b>' . $request->item_code . '</b> not found.']);
            }

            if($item_details->is_stock_item == 0){
                return response()->json(['error' => 1, 'modal_title' => 'Not Stock Item', 'modal_message' => 'Item  <b>' . $request->item_code . '</b> is not a stock item.']);
            }

            if($request->barcode != $request->item_code){
                return response()->json(['error' => 1, 'modal_title' => 'Invalid Barcode', 'modal_message' => 'Invalid barcode for ' . $request->item_code]);
            }

            if($request->barcode != $request->item_code){
                return response()->json(['error' => 1, 'modal_title' => 'Invalid Barcode', 'modal_message' => 'Invalid barcode for ' . $request->item_code]);
            }

            if(!$request->is_material_receipt){
                if($request->balance < $request->qty && $request->transfer_as != 'For Return'){
                    return response()->json([
                        'error' => 1,
                        'modal_title' => 'Insufficient Stock', 
                        'modal_message' => 'Qty not available for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b><br><br>Available qty is <b>' . $request->balance . '</b>, you need <b>' . $request->qty . '</b>'
                    ]);
                }
            }

            if($request->qty <= 0){
                return response()->json([
                    'error' => 1, 
                    'modal_title' => 'Invalid Qty', 
                    'modal_message' => 'Qty cannot be less than or equal to 0 for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b>'
                ]);
            }

            if($request->qty > ($request->requested_qty * 1)){
                return response()->json([
                    'error' => 1, 
                    'modal_title' => 'Invalid Qty', 
                    'modal_message' => 'Qty cannot be greater than ' . $request->requested_qty * 1 . ' for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b>'
                ]);
            }
    
            $status = ($request->transfer_as == 'For Return') ? 'Returned' : 'Issued';

            $now = Carbon::now();
            $values = [
                'session_user' => Auth::user()->full_name,
                'status' => $status, 
                'transfer_qty' => $request->qty, 
                'qty' => $request->qty, 
                'issued_qty' => $request->qty, 
                'remarks' => $request->remarks, 
                'validate_item_code' => $request->barcode, 
                'date_modified' => $now->toDateTimeString()
            ];

            DB::table('tabStock Entry Detail')->where('name', $request->sted_id)->update($values);

            $this->insert_transaction_log('Stock Entry', $request->sted_id);

            $status_result = $this->update_pending_ste_item_status();

            if ($request->purpose == 'Material Transfer for Manufacture') {
                $production_order_details = DB::table('tabProduction Order')
                    ->where('name', $request->production_order)->first();

                if($production_order_details && $production_order_details->docstatus > 1){
                    return response()->json([
                        'error' => 1, 
                        'modal_title' => 'Message', 
                        'modal_message' => 'Production Order ' . $request->production_order . ' was cancelled. Please reload the page.'
                    ]);
                }

                $this->submit_stock_entry($request->ste_name);
                $this->generate_stock_entry($request->production_order);
            }

            if ($request->purpose == 'Material Transfer') {
                if($request->transfer_as == 'For Return' && $status_result == 'Returned'){
                    $this->submit_stock_entry($request->ste_name);
                }
            }

            DB::commit();

            if ($request->transfer_as == 'For Return') {
                return response()->json([
                    'error' => 0, 
                    'modal_title' => 'Returned', 
                    'modal_message' => 'Item ' . $request->item_code . ' has been returned.'
                ]);
            }else{
                return response()->json([
                    'error' => 0, 
                    'modal_title' => 'Checked Out', 
                    'modal_message' => 'Item ' . $request->item_code . ' has been checked out.'
                ]);
            }
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 1, 
                'modal_title' => 'Error', 
                'modal_message' => 'Error creating transaction.'
            ]);
        }
    }

    public function insert_transaction_log($transaction_type, $id){
        if($transaction_type == 'Picking Slip'){
            $q = DB::table('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))
                ->where('ps.item_status', 'For Checking')->where('dri.docstatus', 0)->where('psi.name', $id)
                ->select('psi.name', 'psi.parent', 'psi.item_code', 'psi.description', 'ps.delivery_note', 'dri.warehouse', 'psi.qty', 'psi.barcode', 'psi.session_user')
               ->first();
            $barcode = $q->barcode;
            $remarks = null;
            $s_warehouse = $q->warehouse;
            $t_warehouse = null;
            $reference_no = $q->delivery_note;
        }else{
            $q = DB::table('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')->where('sted.name', $id)
                ->select('sted.*', 'ste.sales_order_no', 'ste.material_request')
                ->first();

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
            'reference_no' => $reference_no
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
            if($request->barcode != $request->item_code){
                return response()->json(['error' => 1, 'modal_title' => 'Invalid Barcode', 'modal_message' => 'Invalid barcode for ' . $request->item_code]);
            }

            if($request->balance < $request->qty && !$request->is_bundle){
                return response()->json([
                    'error' => 1,
                    'modal_title' => 'Insufficient Stock', 
                    'modal_message' => 'Qty not available for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b><br><br>Available qty is <b>' . $request->balance . '</b>, you need <b>' . $request->qty . '</b>'
                ]);
            }

            if($request->qty <= 0){
                return response()->json([
                    'error' => 1, 
                    'modal_title' => 'Invalid Qty', 
                    'modal_message' => 'Qty cannot be less than or equal to 0 for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b>'
                ]);
            }

            if($request->is_bundle){
                $query = DB::table('tabPacked Item')->where('parent_detail_docname', $request->dri_name)->get();
                foreach ($query as $row) {
                    $bundle_item_qty = $row->qty * $request->qty;
                   
                    $actual_qty = $this->get_actual_qty($row->item_code, $row->warehouse);
    
                    $total_issued = $this->get_issued_qty($row->item_code, $row->warehouse);
                    
                    $available_qty = $actual_qty - $total_issued;

                    if($available_qty < $bundle_item_qty){
                        return response()->json([
                            'error' => 1,
                            'modal_title' => 'Insufficient Stock', 
                            'modal_message' => 'Qty not available for <b> ' . $row->item_code . '</b> in <b>' . $row->warehouse . '</b><br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . ($row->qty * 1) . '</b>'
                        ]);
                    }
                }
            }

            $now = Carbon::now();
            $values = [
                'session_user' => Auth::user()->full_name,
                'status' => 'Issued',
                'barcode' => $request->barcode,
                'date_modified' => $now->toDateTimeString()
            ];

            DB::table('tabPacking Slip Item')->where('name', $request->psi_id)
                ->where('docstatus', 0)->update($values);

            $this->insert_transaction_log('Picking Slip', $request->psi_id);

            $this->update_pending_ps_item_status();

            DB::commit();

            return response()->json([
                'error' => 0, 
                'modal_title' => 'Checked Out', 
                'modal_message' => 'Item ' . $request->item_code . 'has been checked out.'
            ]);
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

    public function return_dr_item(Request $request){
        DB::beginTransaction();
        try {
            if($request->barcode != $request->item_code){
                return response()->json(['error' => 1, 'modal_title' => 'Invalid Barcode', 'modal_message' => 'Invalid barcode for ' . $request->item_code]);
            }

            $now = Carbon::now();
            $values = [
                'session_user' => Auth::user()->full_name,
                'item_status' => 'Returned',
                'barcode_return' => $request->barcode,
                'date_modified' => $now->toDateTimeString()
            ];

            DB::table('tabDelivery Note Item')->where('name', $request->dri_id)->where('docstatus', 0)->update($values);

            $this->update_pending_dr_item_status();

            DB::commit();

            return response()->json([
                'error' => 0, 
                'modal_title' => 'Checked Out', 
                'modal_message' => 'Item ' . $request->item_code . ' has been received.'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 1, 
                'modal_title' => 'Error', 
                'modal_message' => 'Error creating transaction.'
            ]);
        }
    }

    public function update_pending_dr_item_status(){
        try {
            $for_return_dr = DB::table('tabDelivery Note')
                ->where('return_status', 'For Return')->where('docstatus', 0)
                ->pluck('name');

            foreach($for_return_dr as $dr){
                $items_for_return = DB::table('tabDelivery Note Item')
                    ->where('parent', $dr)->where('item_status', 'For Return')->exists();

                if(!$items_for_return){
                    DB::table('tabDelivery Note')->where('name', $dr)->where('docstatus', 0)->update(['return_status' => 'Returned']);
                }
            }

        } catch (Exception $e) {
        }
    }

    public function get_item_details(Request $request, $item_code){
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        if($request->json){
            return response()->json($item_details);
        }

        $item_attributes = DB::table('tabItem Variant Attribute')->where('parent', $item_code)->orderBy('idx', 'asc')->get();

        $stock_level = DB::table('tabBin')->where('item_code', $item_code)->get(); 

        return view('tbl_item_details', compact('item_details', 'item_attributes', 'stock_level'));
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
            ];
        }

        return view('tbl_stock_ledger', compact('list', 'logs', 'item_code'));
    }

    public function print_barcode($item_code){
        $item_details = DB::table('tabItem')->where('name', $item_code)->first();

        return view('print_barcode', compact('item_details'));
    }

    public function upload_item_image(Request $request){
        if($request->hasFile('item_image')){
            $file = $request->file('item_image');
            //get filename with extension
            $filenamewithextension = $file->getClientOriginalName();
            //get filename without extension
            $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
            //get file extension
            $extension = $file->getClientOriginalExtension();
            //filename to store
            $filenametostore = round(microtime(true)) . '-'. $request->item_code . '.' . $extension;
            // Storage::put('public/employees/'. $filenametostore, fopen($file, 'r+'));
            Storage::put('public/img/'. $filenametostore, fopen($file, 'r+'));

            DB::table('tabItem')->where('name', $request->item_code)->update(['item_image_path' => $filenametostore]);

            return response()->json(['message' => 'Item image for ' . $request->item_code . ' has been uploaded.']);
        }else{
            return response()->json(['message' => 'No changes made.']);
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

        return [];
        
        // $user = Auth::user()->frappe_userid;
        // $allowed_warehouses = $this->user_allowed_warehouse($user);

        // $q = DB::table('tabStock Entry as ste')
        //     ->join('tabProduction Order as pro', 'ste.production_order', 'pro.name')
        //     ->where('ste.purpose', 'Manufacture')->where('ste.docstatus', 0)
        //     ->select('ste.*', 'pro.production_item', 'pro.description', 'pro.stock_uom')
        //     ->whereIn('pro.fg_warehouse', $allowed_warehouses)
        //     ->get();

        // $list = [];
        // foreach ($q as $row) {
        //     $parent_warehouse = $this->get_warehouse_parent($row->to_warehouse);

        //     $owner = DB::table('tabUser')->where('email', $row->owner)->first();
        //     $owner = ($owner) ? $owner->full_name : null;

        //     $operation_id = DB::connection('mysql_mes')->table('production_order')->where('production_order', $row->production_order)->first();
        //     $operation_id = ($operation_id) ? $operation_id->operation_id : 0;
        //     $operation_name = DB::connection('mysql_mes')->table('operation')->where('operation_id', $operation_id)->first();
        //     $operation_name = ($operation_name) ? $operation_name->operation_name : '--';

        //     $list[] = [
        //         'ste_no' => $row->name,
        //         'production_order' => $row->production_order,
        //         'fg_warehouse' => $row->to_warehouse,
        //         'sales_order_no' => $row->sales_order_no,
        //         'material_request' => $row->material_request,
        //         'customer' => $row->so_customer_name,
        //         'item_code' => $row->production_item,
        //         'description' => $row->description,
        //         'qty_to_receive' => $row->fg_completed_qty * 1,
        //         'stock_uom' => $row->stock_uom,
        //         'parent_warehouse' => $parent_warehouse,
        //         'owner' => $owner,
        //         'created_at' =>  Carbon::parse($row->creation)->format('m-d-Y h:i A'),
        //         'operation_name' => $operation_name
        //     ];
        // }

        // return response()->json(['records' => $list]);
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
            ->where('pr.docstatus', 1)->whereIn('pri.warehouse', $allowed_warehouses)->whereBetween('pr.posting_date', [$start, $end])->count();

        $purchase_orders = DB::table('tabPurchase Order as pr')->join('tabPurchase Order Item as pri', 'pr.name', 'pri.parent')
            ->where('pr.docstatus', 1)->whereRaw('pri.received_qty < pri.qty')->whereIn('pri.warehouse', $allowed_warehouses)->count();

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
}