<?php

namespace App\Http\Controllers;

use DB;
use Exception;
use Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;

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
}