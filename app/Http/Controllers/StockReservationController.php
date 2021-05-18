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
   public function view_form(){
      return view('stock_reservation.form');
   }

   public function create_reservation(Request $request){
      DB::connection('mysql')->beginTransaction();
      try {
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
         $stock_reservation->valid_until = ($request->type == 'Online Shop') ? $request->valid_until : null;
         $stock_reservation->sales_person = ($request->type == 'In-house') ? $request->sales_person : null;
         $stock_reservation->project = ($request->type == 'In-house') ? $request->project : null;
         $stock_reservation->save();

         if($request->type == 'Online Shop'){
            $bin_details = DB::connection('mysql')->table('tabBin')
               ->where('item_code', $request->item_code)
               ->where('warehouse', $request->warehouse)
               ->first();

            if($bin_details) {
               $new_reserved_qty = $request->reserve_qty + $bin_details->reserved_qty;

               $values = [
                  "modified" => Carbon::now()->toDateTimeString(),
                  "modified_by" => Auth::user()->wh_user,
                  "e_commerce_reserve_qty" => $new_reserved_qty,
               ];
      
               DB::connection('mysql')->table('tabBin')->where('name', $bin_details->name)->update($values);
            }
         }

         DB::connection('mysql')->commit();

         return response()->json(['error' => 0, 'modal_title' => 'Stock Reservation', 'modal_message' => 'Stocks for ' . $request->item_code . 'has been reserved.']);
      } catch (Exception $e) {
         DB::connection('mysql')->rollback();
      }
   }

   public function get_stock_reservation(Request $request, $item_code = null){
      $list = StockReservation::when($item_code, function($q) use ($item_code){
         $q->where('item_code', $item_code);
      })->paginate(10);

      return view('stock_reservation.list', compact('list'));
   }

   public function update_reservation(){

   }
}
