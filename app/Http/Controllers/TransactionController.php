<?php

namespace App\Http\Controllers;

use DB;
use Exception;
use Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;

use App\Models\PackingSlip;
use App\Models\PackingSlipItem;
use App\Models\SalesOrder;

class TransactionController extends Controller
{
    use ERPTrait, GeneralTrait;
    // /submit_transaction
    public function submit_transaction(Request $request){
        DB::beginTransaction();
        try {
            $steDetails = DB::table('tabStock Entry as se')->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')->where('sed.name', $request->child_tbl_id)
                ->select('se.name as parent_se', 'se.*', 'se.owner as requested_by' , 'sed.*', 'sed.status as per_item_status', 'se.docstatus as se_status', 'se.material_request as mreq')->first();

            if(!$steDetails){
                return response()->json(['status' => 0, 'message' => 'Record not found.'], 500);
            }

            if(in_array($steDetails->per_item_status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => "Item already $steDetails->per_item_status ."], 500);
            }

            if($steDetails->se_status == 1){
                return response()->json(['status' => 0, 'message' => 'Item already issued.'], 500);
            }

            $itemDetails = DB::table('tabItem')->where('name', $steDetails->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => "Item  <b> $steDetails->item_code </b> not found."], 500);
            }

            if($itemDetails->disabled){
                return response()->json(['status' => 0, 'message' => "Item Code <b> $itemDetails->item_code </b> is disabled."], 500);
            }

            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => "Invalid barcode for <b> $itemDetails->item_code </b>."], 500);
            }

            if($itemDetails->is_stock_item == 0){
                return response()->json(['status' => 0, 'message' => "Item  <b> $steDetails->item_code </b> is not a stock item."], 500);
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

            $reserved_qty = DB::table('tabStock Reservation')->where('item_code', $steDetails->item_code)
                ->where('warehouse', $steDetails->s_warehouse)->where('sales_person', $sales_person)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

            $consumed_qty = DB::table('tabStock Reservation')->where('item_code', $steDetails->item_code)
                ->where('warehouse', $steDetails->s_warehouse)->where('sales_person', $sales_person)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');
            
            $remaining_reserved = $reserved_qty - $consumed_qty;
            $remaining_reserved = $remaining_reserved > 0 ? $remaining_reserved : 0;

            if($request->qty > $remaining_reserved && $request->deduct_reserve == 1){ // For deduct from reserved, if requested qty is more than the reserved qty
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><br><br>Reserved qty is <b>' . $remaining_reserved . '</b>, you need <b>' . $request->qty . '</b>.'], 500);
            }

            if ($steDetails->purpose == 'Material Transfer for Manufacture') {
                $cancelled_production_order = DB::table('tabWork Order')
                    ->where('name', $steDetails->work_order)->where('docstatus', 2)->first();

                if($cancelled_production_order){
                    return response()->json(['status' => 0, 'message' => 'Production Order ' . $cancelled_production_order->name . ' was cancelled. Please reload the page.'], 500);
                }
            }

            $status = 'Issued';
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

            $this->updateSteStatus($steDetails->parent_se);
            
            $stock_reservation_details = [];
            if($request->has_reservation && $request->has_reservation == 1) {
                $ref_no = $steDetails->sales_order_no ?? $steDetails->material_request;
                
                $so_details = DB::table('tabSales Order')->where('name', $ref_no)->first();

                $sales_person = $so_details ? $so_details->sales_person : null;
                $project = $so_details ? $so_details->project : null;
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

            $this->submit_stock_entry($steDetails->parent_se);

            if($request->deduct_reserve == 1) {
                return response()->json(['status' => 1, 'message' => "Item $steDetails->item_code has been deducted from reservation."]);
            }

            if (($steDetails->transfer_as == 'For Return') || $steDetails->purpose == 'Material Receipt') {
                return response()->json(['status' => 1, 'message' => "Item <b> $steDetails->item_code </b> has been returned."]);
            } else {
                return response()->json(['status' => 1, 'message' => "Item <b> $steDetails->item_code </b> has been checked out."]);
            }
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['status' => 0, 'message' => 'Error creating transaction. Please contact your system administrator.'], 500);
        }
    }
    public function checkout_picking_slip(Request $request){
        $child_id = $request->child_tbl_id;
        $packing_slip = PackingSlip::with(['items' => function ($item) {
            $item->with('packed');
        }])->whereHas('items', function ($item) use ($child_id){
            $item->where('name', $child_id);
        })->first();

        if($request->type == 'packed_item'){
            return $this->checkout_picking_slip_bundled($packing_slip, $child_id, $request);
        }
        
        DB::connection('mysql')->beginTransaction();
        try {
            if(!$packing_slip){
                return response()->json(['status' => 0, 'message' => 'Record not found.']);
            }

            $packing_slip_item = collect($packing_slip->items)->where('name', $child_id);
            $index = collect($packing_slip_item)->search(function ($item) use ($child_id) {
                return $item['name'] === $child_id;
            });

            $packing_slip_item = $packing_slip_item->first();

            if(in_array($packing_slip_item->status, ['Issued', 'Returned'])){
                return response()->json(['status' => 0, 'message' => 'Item already ' . $packing_slip_item->status . '.']);
            }

            if($packing_slip->docstatus == 1){
                return response()->json(['status' => 0, 'message' => 'Item already submitted.']);
            }
            
            $itemDetails = DB::table('tabItem')->where('name', $packing_slip_item->item_code)->first();
            if(!$itemDetails){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $packing_slip_item->item_code . '</b> not found.']);
            }

            if($itemDetails->disabled){
                return response()->json(['status' => 0, 'message' => 'Item  <b>' . $packing_slip_item->item_code . '</b> is disabled.']);
            }

            if($request->is_bundle == 0) {
                if($itemDetails->is_stock_item == 0){
                    return response()->json(['status' => 0, 'message' => 'Item  <b>' . $packing_slip_item->item_code . '</b> is not a stock item.']);
                }
            }
            
            if($request->barcode != $itemDetails->item_code){
                return response()->json(['status' => 0, 'message' => 'Invalid barcode for <b>' . $itemDetails->item_code . '</b>.']);
            }
            
            if($request->qty <= 0){
                return response()->json(['status' => 0, 'message' => 'Qty cannot be less than or equal to 0.']);
            }

            $available_qty = $this->get_available_qty($packing_slip_item->item_code, $request->warehouse);
            if($request->qty > $available_qty && $request->is_bundle == false && $request->deduct_reserve == 0){
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $packing_slip_item->item_code . '</b> in <b>' . $request->warehouse . '</b><
                br><br>Available qty is <b>' . $available_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
            }

            $reserved_qty = $this->get_reserved_qty($packing_slip_item->item_code, $request->warehouse);
            if($request->qty > $reserved_qty && $request->is_bundle == false && $request->deduct_reserve == 1){
                return response()->json(['status' => 0, 'message' => 'Qty not available for <b> ' . $packing_slip_item->item_code . '</b> in <b>' . $request->warehouse . '</b><
                br><br>Available reserved qty is <b>' . $reserved_qty . '</b>, you need <b>' . $request->qty . '</b>.']);
            }

            $countPendingItems = PackingSlipItem::where('parent', $packing_slip->name)
                ->where('status', 'For Checking')->where('name', '!=', $child_id)->count();

            $values = $packing_slip;
            $values->items[$index]->session_user = Auth::user()->wh_user;
            $values->items[$index]->status = 'Issued';
            $values->items[$index]->barcode = $request->barcode;
            $values->items[$index]->date_modified = Carbon::now()->toDateTimeString();
            if ($countPendingItems < 1) {
                $values->item_status = 'Issued';
                $values->docstatus = 1;
            } 

            $stock_reservation_details = [];
            if($request->has_reservation && $request->has_reservation == 1) {
                $so_details = DB::table('tabSales Order')->where('name', $request->sales_order)->first();
                if($so_details) {
                    $stock_reservation_details = $this->get_stock_reservation($packing_slip_item->item_code, $request->warehouse, $so_details->sales_person, $so_details->project, null, $so_details->order_type, $so_details->po_no);
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

            $response = $this->erpOperation('put', 'Packing Slip', $packing_slip->name, $values, true);
            if(!isset($response['data'])){
                $err = $response['exception'] ?? 'An error occured while updating picking slip';
                throw new Exception($err);
            }

            $this->insert_transaction_log('Picking Slip', $child_id);

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

            $packedItemNames = collect($packed_items)->pluck('name');

            $countPendingItems = PackingSlipItem::where('parent', $packing_slip->name)
                ->where('status', 'For Checking')->whereNotIn('name', $packedItemNames)->count();

            foreach ($packing_slip->items as $item) {
                $item->qty = (float) $item->qty;
                if ($item->packed->parent_item === $request->barcode) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Issued';
                    $item->barcode = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    $item->qty = (float) $request->qty;
                }

                unset($item->packed);
            }

            if ($countPendingItems < 1) {
                $packing_slip->item_status = 'Issued';
                $packing_slip->docstatus = 1;
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

            $response = $this->erpOperation('put', 'Packing Slip', $packing_slip->name, collect($packing_slip)->toArray());
            if(!isset($response['data'])){
                $err = $response['exception'] ?? 'An error occured while updating Packing Slip';
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
}