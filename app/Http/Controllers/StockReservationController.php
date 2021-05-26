<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\StockReservation;
use DB;
use Carbon\Carbon;
use Auth;

class StockReservationController extends Controller
{
   // public function view_form(){
   //    return view('stock_reservation.form');
   // }

   public function create_reservation(Request $request){
      DB::connection('mysql')->beginTransaction();
      try {
         if($request->reserve_qty <= 0) {
            return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Reserve Qty must be greater than or equal to 0.']);
         }

         $latest_name = StockReservation::max('name');
			$latest_name_exploded = explode("-", $latest_name);
			$new_id = (!$latest_name) ? 1 : $latest_name_exploded[1] + 1;
			$new_id = str_pad($new_id, 5, '0', STR_PAD_LEFT);
			$new_id = 'STR-'.$new_id;

         $now = Carbon::now();
         $stock_reservation = new StockReservation;
         $stock_reservation->name = $new_id;
         $stock_reservation->creation = $now->toDateTimeString();
         $stock_reservation->modified = null;
         $stock_reservation->modified_by = null;
         $stock_reservation->owner = Auth::user()->wh_user;
         $stock_reservation->description = $request->description;
         $stock_reservation->notes = $request->notes;
         $stock_reservation->created_by = Auth::user()->full_name;
         $stock_reservation->stock_uom = $request->stock_uom;
         $stock_reservation->item_code = $request->item_code;
         $stock_reservation->warehouse = $request->warehouse;
         $stock_reservation->type = $request->type;
         $stock_reservation->reserve_qty = $request->reserve_qty;
         $stock_reservation->valid_until = ($request->type == 'In-house') ? Carbon::createFromFormat('Y-m-d', $request->valid_until) : null;
         $stock_reservation->sales_person = ($request->type == 'In-house') ? $request->sales_person : null;
         $stock_reservation->project = ($request->type == 'In-house') ? $request->project : null;
         $stock_reservation->save();

         if($request->type == 'Website Stocks'){
            $bin_details = DB::connection('mysql')->table('tabBin')
               ->where('item_code', $request->item_code)
               ->where('warehouse', $request->warehouse)
               ->first();

            if($bin_details) {
               $new_reserved_qty = $request->reserve_qty + $bin_details->reserved_qty;

               $values = [
                  "modified" => Carbon::now()->toDateTimeString(),
                  "modified_by" => Auth::user()->wh_user,
                  "website_reserved_qty" => $new_reserved_qty,
               ];
      
               DB::connection('mysql')->table('tabBin')->where('name', $bin_details->name)->update($values);
            }
         }

         DB::connection('mysql')->commit();

         return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Stock Reservation No. ' . $new_id . ' has been created.']);
      } catch (Exception $e) {
         DB::connection('mysql')->rollback();

         return response()->json(['error' => 1, 'modal_title' => 'Stock Reservation', 'modal_message' => 'There was a problem creating Stock Reservation.']);
      }
   }

   public function get_stock_reservation(Request $request, $item_code = null){
      $list = StockReservation::when($item_code, function($q) use ($item_code){
         $q->where('item_code', $item_code);
      })->paginate(10);

      return view('stock_reservation.list', compact('list', 'item_code'));
   }

   public function cancel_reservation(Request $request){
      DB::connection('mysql')->beginTransaction();
      try {
         $now = Carbon::now();
         $stock_reservation = StockReservation::find($request->stock_reservation_id);
         $stock_reservation->modified = $now->toDateTimeString();
         $stock_reservation->modified_by = Auth::user()->wh_user;
         $stock_reservation->status = 'Cancelled';
         $stock_reservation->save();

         if($stock_reservation->type == 'Website Stocks'){
            $bin_details = DB::connection('mysql')->table('tabBin')
               ->where('item_code', $stock_reservation->item_code)
               ->where('warehouse', $stock_reservation->warehouse)
               ->first();

            if($bin_details) {
               $new_reserved_qty = $bin_details->website_reserved_qty - $stock_reservation->reserve_qty;

               $new_reserved_qty = ($new_reserved_qty <= 0) ? 0 : $new_reserved_qty;

               $values = [
                  "modified" => Carbon::now()->toDateTimeString(),
                  "modified_by" => Auth::user()->wh_user,
                  "website_reserved_qty" => $new_reserved_qty,
               ];
      
               DB::connection('mysql')->table('tabBin')->where('name', $bin_details->name)->update($values);
            }
         }

         DB::connection('mysql')->commit();

         return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Stock Reservation No. ' . $request->stock_reservation_id . ' has been cancelled.']);
      } catch (Exception $e) {
         DB::connection('mysql')->rollback();

         return response()->json(['error' => 1, 'modal_title' => 'Stock Reservation', 'modal_message' => 'There was a problem cancelling Stock Reservation.']);
      }
   }

   public function get_stock_reservation_details($id){
      return StockReservation::find($id);
   }

   public function update_reservation(Request $request){
      DB::connection('mysql')->beginTransaction();
      try {
         if($request->reserve_qty <= 0) {
            return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Reserve Qty must be greater than or equal to 0.']);
         }

         $now = Carbon::now();
         $stock_reservation = StockReservation::find($request->id);
         $stock_reservation->modified = $now->toDateTimeString();
         $stock_reservation->modified_by = Auth::user()->wh_user;
         $stock_reservation->notes = $request->notes;
         $stock_reservation->warehouse = $request->warehouse;
         $stock_reservation->type = $request->type;
         $stock_reservation->reserve_qty = $request->reserve_qty;
         $stock_reservation->valid_until = ($request->type == 'In-house') ? Carbon::createFromFormat('Y-m-d', $request->valid_until) : null;
         $stock_reservation->sales_person = ($request->type == 'In-house') ? $request->sales_person : null;
         $stock_reservation->project = ($request->type == 'In-house') ? $request->project : null;

         if($request->type == 'Website Stocks'){

            $reserved_qty = abs($stock_reservation->reserve_qty - $request->reserve_qty);

            $bin_details = DB::connection('mysql')->table('tabBin')
               ->where('item_code', $request->item_code)
               ->where('warehouse', $request->warehouse)
               ->first();

            if($bin_details) {
               $new_reserved_qty = $bin_details->reserved_qty;
               if($stock_reservation->reserve_qty > $request->reserve_qty){
                  $new_reserved_qty = $bin_details->reserved_qty - $reserved_qty;
               }

               if($stock_reservation->reserve_qty < $request->reserve_qty){
                  $new_reserved_qty = $bin_details->website_reserved_qty + $reserved_qty;
               }

               $new_reserved_qty = ($new_reserved_qty <= 0) ? 0 : $new_reserved_qty;

               $values = [
                  "modified" => Carbon::now()->toDateTimeString(),
                  "modified_by" => Auth::user()->wh_user,
                  "website_reserved_qty" => $new_reserved_qty,
               ];
      
               DB::connection('mysql')->table('tabBin')->where('name', $bin_details->name)->update($values);
            }
         }

         $stock_reservation->save();

         DB::connection('mysql')->commit();

         return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Stock Reservation No. ' . $request->id . ' has been updated.']);
      } catch (Exception $e) {
         DB::connection('mysql')->rollback();

         return response()->json(['error' => 1, 'modal_title' => 'Stock Reservation', 'modal_message' => 'There was a problem updating Stock Reservation.']);
      }
   }
}
