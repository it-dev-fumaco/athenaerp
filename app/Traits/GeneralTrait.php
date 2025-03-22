<?php

namespace App\Traits;

use Mail;
use App\Models\StockEntry;
use Illuminate\Database\Eloquent\Builder;
use DB;
use Carbon\Carbon;
use Auth;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait GeneralTrait
{
    public function base64_image($file, $original = 0){
        // $file = explode('.', $file);
        // $file = $file[0].'.webp';
        return asset("storage/$file");
        // if(!$file){
        //     return null;
        // }

        // $webp_file = explode('.', $file)[0].'.webp';
        // if(Storage::exists($webp_file) && !$original){
        //     $path = $webp_file;
        // }else if(Storage::exists($file)){
        //     $path = $file;
        // }else{
        //     $path = "/icon/no_img.webp";
        // }

        // $data = Storage::get($path);
        // $base64 = base64_encode($data);
        // $mimetype = Storage::mimeType($path);

        // return "data:$mimetype;base64,$base64";
    }

    public function sendMail($template, $data, $recipient, $subject = null){
        try {
            Mail::send($template, $data, function($message) use ($recipient, $subject){
                $message->to($recipient);
                $message->subject($subject);
            });

            return ['success' => 1, 'message' => 'Email Sent!'];
        } catch (\Throwable $th) {
            return ['success' => 0, 'message' => $th->getMessage()];
        }
    }

    public function updateSteStatus($id = null) {
        try {
            $pendingSte = StockEntry::with('items')
                ->whereHas('items', function (Builder $query) {
                    $query->where('status', 'Issued');
                })
                ->when($id, function ($q) use ($id){
                    return $q->where('name', $id);
                })
                ->whereIn('stock_entry_type', ['Material Transfer for Manufacture'])
                ->where('item_status', 'For Checking')->where('docstatus', 0)
                ->select('name', 'transfer_as', 'receive_as')->get();

            foreach ($pendingSte as $row) {
                $countPendingItem = collect($row->items)->where('status', 'For Checking')->count();
                if ($countPendingItem <= 0) {
                    switch ($row->receive_as) {
                        case 'Sales Return':
                            $item_status = 'Returned';
                            break;
                        
                        default:
                            $item_status = ($row->transfer_as == 'For Return') ? 'Returned' : 'Issued';
                            break;
                    }
                } else {
                    $item_status = 'For Checking';
                }

                $stockEntry = StockEntry::find($row->name);
                $stockEntry->item_status = $item_status;
                $stockEntry->save();
            }

            return 1;
        } catch (\Exception $e) {
            info($e->getMessage());
            return 0;
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

    public function get_reserved_qty($item_code, $warehouse){
        
        $reserved_qty_for_website = 0;

        $stock_reservation_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

        $consumed_qty = DB::table('tabStock Reservation')->where('item_code', $item_code)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');

        return ($reserved_qty_for_website + $stock_reservation_qty) - $consumed_qty;
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

                // return $id;
                return collect($q);


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

    public function submit_stock_entry($id, $system_generated = 0){
        try {
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
    
            if($draft_ste->transfer_as != 'Consignment'){
                $stock_entry_data = ['docstatus' => 1];
    
                return $stock_entry_response = $this->erpOperation('put', 'Stock Entry', $id, $stock_entry_data);
            }
    
            if (!isset($stock_entry_response['data'])) {
                $err = $stock_entry_response['exception'] ?? 'An error occurred while submitting Stock Entry';
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

    public function slackNotification($message){
        try {
            $response = Http::withOptions([
                'Content-Type' => 'application/json',
                'verify' => false,
            ])->post("https://hooks.slack.com/services/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", [
                'text' => $message
            ]);

            if($response != 'ok'){
                info('Error sending Slack Notification');
                return new \ErrorException();
            }

            return 1;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getJson($file){
        try {
            $log_data = [];
            if(Storage::disk('public')->exists("$file.json")){
                $log_data = json_decode(file_get_contents(storage_path("/app/public/$file.json")), true);
            }

            return $log_data;
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function saveJson($file, $data){
        try {
            Storage::disk('public')->put("$file.json", json_encode($data));

            return 1;
        } catch (\Throwable $th) {
            return 0;
        }
    }
}