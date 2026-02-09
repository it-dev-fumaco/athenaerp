<?php

namespace App\Traits;

use App\Models\StockEntry;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait GeneralTrait
{
    /**
     * Get the current user's allowed warehouse IDs.
     */
    protected function getAllowedWarehouseIds(): \Illuminate\Support\Collection
    {
        return Auth::user()->allowedWarehouseIds();
    }

    public function getStockReservation($itemCode, $warehouse, $salesPerson, $project, $consignmentWarehouse, $orderType = null, $poNo = null)
    {
        $query = null;
        if ($salesPerson) {
            $query = StockReservation::query()
                ->where('warehouse', $warehouse)->where('item_code', $itemCode)
                ->where('sales_person', trim($salesPerson))
                ->where('project', $project)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }

        if ($consignmentWarehouse) {
            $query = StockReservation::query()
                ->where('warehouse', $warehouse)->where('item_code', $itemCode)
                ->where('consignment_warehouse', $consignmentWarehouse)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }

        if ($orderType == 'Shopping Cart') {
            $query = StockReservation::query()
                ->where('warehouse', $warehouse)->where('item_code', $itemCode)
                ->where('reference_no', $poNo)
                ->whereIn('status', ['Active', 'Partially Issued'])->orderBy('creation', 'asc')->first();
        }

        return $query ?: null;
    }

    public function getWarehouseParent($childWarehouse)
    {
        $warehouse = Warehouse::where('disabled', 0)->where('name', $childWarehouse)->first();
        return $warehouse?->parent_warehouse;
    }

    /**
     * Bulk fetch parent warehouse for multiple warehouses. Returns map of warehouse_name => parent_warehouse.
     *
     * @param  array<string>  $warehouseNames
     * @return array<string, string|null>
     */
    public function getWarehouseParentsBulk(array $warehouseNames): array
    {
        if (empty($warehouseNames)) {
            return [];
        }

        $warehouses = Warehouse::where('disabled', 0)
            ->whereIn('name', array_unique(array_filter($warehouseNames)))
            ->pluck('parent_warehouse', 'name');

        return $warehouses->toArray();
    }

    /**
     * Bulk fetch actual qty for multiple item-warehouse pairs. Returns map of "item_code-warehouse" => qty.
     *
     * @param  array<array{0: string, 1: string}>  $itemWarehousePairs  Array of [item_code, warehouse] pairs
     * @return array<string, float>
     */
    public function getActualQtyBulk(array $itemWarehousePairs): array
    {
        if (empty($itemWarehousePairs)) {
            return [];
        }

        $itemCodes = array_unique(array_column($itemWarehousePairs, 0));
        $warehouses = array_unique(array_column($itemWarehousePairs, 1));

        $results = DB::table('tabBin')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('warehouse', $warehouses)
            ->selectRaw('item_code, warehouse, SUM(actual_qty) as actual_qty')
            ->groupBy('item_code', 'warehouse')
            ->get();

        $map = [];
        foreach ($results as $row) {
            $map["{$row->item_code}-{$row->warehouse}"] = (float) $row->actual_qty;
        }

        return $map;
    }

    /**
     * Bulk fetch available qty for multiple item-warehouse pairs. Returns map of "item_code-warehouse" => qty.
     *
     * @param  array<array{0: string, 1: string}>  $itemWarehousePairs  Array of [item_code, warehouse] pairs
     * @return array<string, float>
     */
    public function getAvailableQtyBulk(array $itemWarehousePairs): array
    {
        if (empty($itemWarehousePairs)) {
            return [];
        }

        $actualQtyMap = $this->getActualQtyBulk($itemWarehousePairs);

        $itemCodes = array_unique(array_column($itemWarehousePairs, 0));
        $warehouses = array_unique(array_column($itemWarehousePairs, 1));

        $reservedResults = DB::table('tabStock Reservation')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('warehouse', $warehouses)
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->whereIn('status', ['Active', 'Partially Issued'])
            ->selectRaw('item_code, warehouse, SUM(reserve_qty) as reserve_qty, SUM(consumed_qty) as consumed_qty')
            ->groupBy('item_code', 'warehouse')
            ->get();

        $reservedMap = [];
        foreach ($reservedResults as $row) {
            $key = "{$row->item_code}-{$row->warehouse}";
            $reservedMap[$key] = (float) $row->reserve_qty - (float) $row->consumed_qty;
        }

        $steIssuedResults = DB::table('tabStock Entry Detail')
            ->where('docstatus', 0)
            ->where('status', 'Issued')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('s_warehouse', $warehouses)
            ->selectRaw('item_code, s_warehouse as warehouse, SUM(qty) as qty')
            ->groupBy('item_code', 's_warehouse')
            ->get();

        $athenaIssuedResults = DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)
            ->where('ps.docstatus', '<', 2)
            ->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')
            ->whereIn('at.item_code', $itemCodes)
            ->whereIn('psi.item_code', $itemCodes)
            ->whereIn('at.source_warehouse', $warehouses)
            ->selectRaw('at.item_code, at.source_warehouse as warehouse, SUM(at.issued_qty) as qty')
            ->groupBy('at.item_code', 'at.source_warehouse')
            ->get();

        $issuedMap = [];
        foreach ($steIssuedResults as $row) {
            $key = "{$row->item_code}-{$row->warehouse}";
            $issuedMap[$key] = ($issuedMap[$key] ?? 0) + (float) $row->qty;
        }
        foreach ($athenaIssuedResults as $row) {
            $key = "{$row->item_code}-{$row->warehouse}";
            $issuedMap[$key] = ($issuedMap[$key] ?? 0) + (float) $row->qty;
        }

        $availableMap = [];
        foreach ($itemWarehousePairs as [$itemCode, $warehouse]) {
            $key = "{$itemCode}-{$warehouse}";
            $actualQty = $actualQtyMap[$key] ?? 0;
            $reservedQty = $reservedMap[$key] ?? 0;
            $issuedQty = $issuedMap[$key] ?? 0;
            $available = ($actualQty - $issuedQty - $reservedQty);
            $availableMap[$key] = $available < 0 ? 0 : $available;
        }

        return $availableMap;
    }

    public function base64Image($file, $original = 0)
    {
        // $file = explode('.', $file);
        // $file = $file[0].'.webp';
        return asset("storage/$file");
        // if(!$file){
        //     return null;
        // }

        // $webpFile = explode('.', $file)[0].'.webp';
        // if(Storage::exists($webpFile) && !$original){
        //     $path = $webpFile;
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
            Log::error('GeneralTrait sendEmail failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return ['success' => 0, 'message' => $th->getMessage()];
        }
    }

    public function updateSteStatus($id = null) {
        try {
            $pendingSte = StockEntry::with('items')
                ->whereHas('items', function (Builder $query) {
                    $query->where('status', 'Issued');
                })
                ->when($id, function ($query) use ($id) {
                    return $query->where('name', $id);
                })
                ->whereIn('stock_entry_type', ['Material Transfer for Manufacture'])
                ->where('item_status', 'For Checking')->where('docstatus', 0)
                ->select('name', 'transfer_as', 'receive_as')->get();

            foreach ($pendingSte as $row) {
                $countPendingItem = collect($row->items)->where('status', 'For Checking')->count();
                if ($countPendingItem <= 0) {
                    switch ($row->receive_as) {
                        case 'Sales Return':
                            $itemStatus = 'Returned';
                            break;

                        default:
                            $itemStatus = ($row->transfer_as == 'For Return') ? 'Returned' : 'Issued';
                            break;
                    }
                } else {
                    $itemStatus = 'For Checking';
                }

                $row->item_status = $itemStatus;
                $row->save();
            }

            return 1;
        } catch (Exception $e) {
            Log::error('GeneralTrait updateSteStatus failed', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    public function getAvailableQty($itemCode, $warehouse)
    {
        $reservedQty = $this->getReservedQty($itemCode, $warehouse);
        $actualQty = $this->getActualQty($itemCode, $warehouse);
        $issuedQty = $this->getIssuedQty($itemCode, $warehouse);

        $availableQty = ($actualQty - $issuedQty);
        $availableQty = ($availableQty - $reservedQty);

        return ($availableQty < 0) ? 0 : $availableQty;
    }

    public function getReservedQty($itemCode, $warehouse)
    {
        $reservedQtyForWebsite = 0;

        $stockReservationQty = DB::table('tabStock Reservation')->where('item_code', $itemCode)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('reserve_qty');

        $consumedQty = DB::table('tabStock Reservation')->where('item_code', $itemCode)
            ->where('warehouse', $warehouse)->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])->whereIn('status', ['Active', 'Partially Issued'])->sum('consumed_qty');

        return ($reservedQtyForWebsite + $stockReservationQty) - $consumedQty;
    }

    public function getActualQty($itemCode, $warehouse)
    {
        return DB::table('tabBin')->where('item_code', $itemCode)
            ->where('warehouse', $warehouse)->sum('actual_qty');
    }

    public function getIssuedQty($itemCode, $warehouse)
    {
        $totalIssued = DB::table('tabStock Entry Detail')->where('docstatus', 0)->where('status', 'Issued')
            ->where('item_code', $itemCode)->where('s_warehouse', $warehouse)->sum('qty');

        $totalIssued += DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)->where('ps.docstatus', '<', 2)->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')->where('at.item_code', $itemCode)
            ->where('psi.item_code', $itemCode)->where('at.source_warehouse', $warehouse)
            ->sum('at.issued_qty');

        return $totalIssued;
    }

    public function insertTransactionLog($transactionType, $id)
    {
        if($transactionType == 'Picking Slip'){
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
            $sWarehouse = $q->warehouse;
            $tWarehouse = null;
            $referenceNo = $q->delivery_note;
        } else if($transactionType == 'Delivery Note') {
            $q = DB::table('tabDelivery Note as dn')
                ->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
                ->where('dni.name', $id)->select('dni.name', 'dni.parent', 'dni.item_code', 'dni.description', 'dn.name as delivery_note', 'dni.warehouse', 'dni.qty', 'dni.barcode', 'dni.session_user', 'dni.stock_uom')
                ->first();

            $type = 'Check In - Received';
            $purpose = 'Sales Return';
            $barcode = $q->barcode;
            $remarks = null;
            $sWarehouse = null;
            $tWarehouse = $q->warehouse;
            $referenceNo = $q->delivery_note;
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
            $sWarehouse = $q->s_warehouse;
            $tWarehouse = $q->t_warehouse;
            $referenceNo = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;
        }
       
        $now = now();
        
        $values = [
            'name' => uniqid(date('mdY')),
            'reference_type' => $transactionType,
            'reference_name' => $q->name,
            'reference_parent' => $q->parent,
            'item_code' => $q->item_code,
            'qty' => $q->qty,
            'barcode' => $barcode,
            'transaction_date' => $now->toDateTimeString(),
            'warehouse_user' => $q->session_user,
            'issued_qty' => $q->qty,
            'remarks' => $remarks,
            'source_warehouse' => $sWarehouse,
            'target_warehouse' => $tWarehouse,
            'description' => $q->description,
            'reference_no' => $referenceNo,
            'creation' => $now->toDateTimeString(),
            'modified' => $now->toDateTimeString(),
            'modified_by' => Auth::user()->wh_user,
            'owner' => Auth::user()->wh_user,
            'uom' => $q->stock_uom,
            'purpose' => $purpose,
            'transaction_type' => $type
        ];

        $existingLog = DB::table('tabAthena Transactions')
            ->where('reference_name', $q->name)->where('reference_parent', $q->parent)->where('status', 'Issued')
            ->exists();

        if(!$existingLog){
            DB::table('tabAthena Transactions')->insert($values);
        }
    }

    /**
     * Update stock reservation statuses (expired, partially issued, issued).
     * Alias: update_reservation_status for backward compatibility.
     */
    public function updateReservationStatus(): void
    {
        DB::table('tabStock Reservation')
            ->whereIn('status', ['Active', 'Partially Issued'])
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->where('valid_until', '<', now())
            ->update(['status' => 'Expired']);

        DB::table('tabStock Reservation')
            ->whereNotIn('status', ['Cancelled', 'Issued', 'Expired'])
            ->where('consumed_qty', '>', 0)
            ->whereRaw('consumed_qty < reserve_qty')
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->update(['status' => 'Partially Issued']);

        DB::table('tabStock Reservation')
            ->whereNotIn('status', ['Cancelled', 'Expired', 'Issued'])
            ->where('consumed_qty', '>', 0)
            ->whereRaw('consumed_qty >= reserve_qty')
            ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
            ->update(['status' => 'Issued']);
    }

    /** @deprecated Use updateReservationStatus() */
    public function update_reservation_status(): void
    {
        $this->updateReservationStatus();
    }

    public function submitStockEntry($id, $systemGenerated = 0)
    {
        try {
            $draftSte = StockEntry::with('items')->find($id);
    
            if (!$draftSte) {
                throw new Exception('Stock Entry not found');
            }
    
            if ($draftSte->docstatus == 1) {
                throw new Exception('Stock Entry already submitted');
            }
    
            if ($draftSte->purpose) {
                $countNotIssuedItems = collect($draftSte->items)
                    ->whereNotIn('status', ['Issued', 'Returned'])
                    ->count();
    
                if ($countNotIssuedItems) {
                    throw new Exception('All item(s) must be issued.');
                }
            }
    
            if($draftSte->transfer_as != 'Consignment'){
                $stockEntryData = ['docstatus' => 1];
    
                return $stockEntryResponse = $this->erpPut('Stock Entry', $id, $stockEntryData);
            }
    
            if (!isset($stockEntryResponse['data'])) {
                $err = $stockEntryResponse['exception'] ?? 'An error occurred while submitting Stock Entry';
                throw new Exception($err);
            }
    
            return ['error' => 0, 'modal_title' => 'Success', 'modal_message' => 'Stock Entry Submitted.'];
        } catch (Exception $e) {
            Log::error('GeneralTrait submitStockEntry failed', [
                'ste_id' => $id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($systemGenerated) {
                return ['error' => 1, 'modal_title' => 'Warning', 'modal_message' => $e->getMessage()];
            }
            return response()->json(['error' => 1, 'modal_title' => 'Warning', 'modal_message' => $e->getMessage()]);
        }
    }
}