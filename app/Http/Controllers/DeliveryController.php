<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\AthenaTransaction;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\StockLedgerEntry;
use App\Pipelines\ViewDeliveriesPipeline;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function __construct(
        protected ViewDeliveriesPipeline $viewDeliveriesPipeline
    ) {}

    public function viewDeliveries(Request $request)
    {
        if (! $request->arr) {
            return view('picking_slip');
        }

        $passable = (object) [
            'allowedWarehouses' => $this->getAllowedWarehouseIds(),
            'search' => $request->search ?? '',
            'page' => (int) $request->input('page', 1),
            'getWarehouseParentsBulk' => fn (array $warehouseNames) => $this->getWarehouseParentsBulk($warehouseNames),
        ];

        return $this->viewDeliveriesPipeline->run($passable);
    }

    public function viewPickingSlip()
    {
        return view('picking_slip');
    }

    public function getAthenaLogs(Request $request)
    {
        try {
            $allowedWarehouses = $this->getAllowedWarehouseIds();

            $date = Carbon::parse($request->month);
            $start = (clone $date)->startOfMonth();
            $end = (clone $date)->endOfMonth();

            $stockAdjustmentsQuery = StockLedgerEntry::query()
                ->from('tabStock Ledger Entry as sle')
                ->where('voucher_type', 'Stock Reconciliation')
                ->join('tabItem as i', 'i.name', 'sle.item_code')
                ->whereIn('sle.warehouse', $allowedWarehouses)
                ->whereBetween('sle.creation', [$start, $end])
                ->select('sle.creation as transaction_date', 'voucher_type as transaction_type', 'sle.item_code', 'i.description', 'sle.warehouse as s_warehouse', 'sle.warehouse as t_warehouse', 'sle.qty_after_transaction as qty', 'sle.voucher_no as reference_no', 'sle.voucher_no as reference_parent', 'sle.owner as user');

            $checkinTransactions = AthenaTransaction::query()
                ->whereIn('target_warehouse', $allowedWarehouses)
                ->whereNull('source_warehouse')
                ->whereBetween('transaction_date', [$start, $end])
                ->where('status', 'Issued')
                ->select('transaction_date', 'transaction_type', 'item_code', 'description', 'source_warehouse as s_warehouse', 'target_warehouse as t_warehouse', 'qty', 'reference_no', 'reference_parent', 'warehouse_user as user')
                ->orderBy('transaction_date', 'desc');

            $list = AthenaTransaction::query()
                ->whereIn('source_warehouse', $allowedWarehouses)
                ->whereBetween('transaction_date', [$start, $end])
                ->where('status', 'Issued')
                ->select('transaction_date', 'transaction_type', 'item_code', 'description', 'source_warehouse as s_warehouse', 'target_warehouse as t_warehouse', 'qty', 'reference_no', 'reference_parent', 'warehouse_user as user')
                ->orderBy('transaction_date', 'desc')
                ->union($stockAdjustmentsQuery)
                ->union($checkinTransactions)
                ->orderBy('transaction_date', 'desc')
                ->get();

            return view('tbl_athena_logs', compact('list'));
        } catch (\Throwable $th) {
            Log::error('DeliveryController updatePendingDrItemStatus failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return ApiResponse::failureLegacy('An error occured. Please contact the system administrator', 500, ['error' => $th->getMessage()]);
        }
    }

    public function getDrReturnDetails($id)
    {
        $q = DeliveryNote::query()
            ->from('tabDelivery Note as dr')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
            ->where('dr.is_return', 1)
            ->where('dr.docstatus', 0)
            ->where('dri.name', $id)
            ->select('dri.barcode_return', 'dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status', 'dri.stock_uom', 'dr.owner', 'dr.docstatus')
            ->first();

        if (! $q) {
            throw new ErrorException('Delivery Note Item not found.');
        }

        $img = ItemImages::query()->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64Image($img);

        $owner = ucwords(str_replace('.', ' ', explode('@', $q->owner)[0]));

        $availableQty = $this->getAvailableQty($q->item_code, $q->warehouse);

        $data = [
            'name' => $q->c_name,
            't_warehouse' => $q->warehouse,
            'available_qty' => $availableQty,
            'validate_item_code' => $q->barcode_return,
            'img' => $img,
            'item_code' => $q->item_code,
            'description' => $q->description,
            'ref_no' => $q->against_sales_order.'<br>'.$q->name,
            'stock_uom' => $q->stock_uom,
            'qty' => abs($q->qty * 1),
            'owner' => $owner,
            'status' => $q->item_status,
            'reference' => 'Delivery Note',
            'docstatus' => $q->docstatus,
        ];

        $isStockEntry = false;

        return view('return_modal_content', compact('data', 'isStockEntry'));
    }

    public function submitDrSalesReturnApi(Request $request)
    {
        try {
            $childId = $request->child_tbl_id;
            $deliveryNote = DeliveryNote::with('items')->whereHas('items', function ($item) use ($childId) {
                return $item->where('name', $childId);
            })->first();

            $now = now();

            if (! $deliveryNote) {
                throw new Exception('Record not found');
            }

            if (in_array($deliveryNote->item_status, ['Issued', 'Returned'])) {
                throw new Exception("Item already $deliveryNote->item_status.");
            }

            $deliveryNote->qty = (float) $deliveryNote->qty;

            $deliveryNoteItem = collect($deliveryNote->items)->where('name', $childId)->first();
            $itemCode = $deliveryNoteItem->item_code;
            $itemDetails = Item::find($itemCode);

            if (! $itemDetails) {
                throw new Exception("Item <b>$itemCode</b> not found.");
            }

            if (! $itemDetails->is_stock_item) {
                throw new Exception("Item <b>$itemCode</b> is not a stock item.");
            }

            if ($request->barcode != $itemDetails->item_code) {
                throw new Exception("Invalid barcode for <b>$itemDetails->item_code</b>.");
            }

            if ($request->qty <= 0) {
                throw new Exception('Qty cannot be less than or equal to 0.');
            }

            foreach ($deliveryNote->items as $item) {
                if ($item->name === $childId) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->item_status = 'Returned';
                    $item->barcode_return = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    break;
                }
            }

            $otherItemsPending = collect($deliveryNote->items)
                ->where('name', '!=', $childId)
                ->where('item_status', 'For Return')
                ->count();
            if ($otherItemsPending === 0) {
                $deliveryNote->item_status = 'Returned';
            }

            $response = $this->erpPut('Delivery Note', $deliveryNote->name, collect($deliveryNote)->toArray());
            if (! Arr::has($response, 'data')) {
                $err = $response['exception'] ?? 'An error occured while updating Delivery Note';
                throw new Exception($err);
            }

            $this->insertTransactionLog('Delivery Note', $request->child_tbl_id);

            return ApiResponse::success("Item <b>$itemCode</b> has been returned");
        } catch (\Throwable $th) {
            Log::error('DeliveryController updateDeliveryNote failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.');
        }
    }

    public function submitDrSalesReturn(Request $request)
    {
        DB::beginTransaction();
        try {
            $driDetails = DeliveryNote::query()
                ->from('tabDelivery Note as dr')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
                ->where('dri.name', $request->child_tbl_id)
                ->select('dr.name as parent_dr', 'dr.*', 'dri.*', 'dri.item_status as per_item_status', 'dr.docstatus as dr_status')
                ->first();

            if (! $driDetails) {
                return ApiResponse::failure('Record not found.');
            }

            if (in_array($driDetails->per_item_status, ['Issued', 'Returned'])) {
                return ApiResponse::failure('Item already '.$driDetails->per_item_status.'.');
            }

            if ($driDetails->dr_status == 1) {
                return ApiResponse::failure('Item already returned.');
            }

            $itemDetails = Item::query()->where('name', $driDetails->item_code)->first();
            if (! $itemDetails) {
                return ApiResponse::failure('Item  <b>'.$driDetails->item_code.'</b> not found.');
            }

            if ($itemDetails->is_stock_item == 0) {
                return ApiResponse::failure('Item  <b>'.$driDetails->item_code.'</b> is not a stock item.');
            }

            if ($request->barcode != $itemDetails->item_code) {
                return ApiResponse::failure('Invalid barcode for <b>'.$itemDetails->item_code.'</b>.');
            }

            $values = [
                'session_user' => Auth::user()->wh_user,
                'item_status' => 'Returned',
                'barcode_return' => $request->barcode,
                'date_modified' => now()->toDateTimeString(),
            ];

            DeliveryNoteItem::query()->where('name', $request->child_tbl_id)->update($values);

            $this->updatePendingDrItemStatus();
            $this->insertTransactionLog('Delivery Note', $request->child_tbl_id);

            DB::commit();

            return ApiResponse::success('Item <b>'.$driDetails->item_code.'</b> has been returned.');
        } catch (Exception $e) {
            Log::error('DeliveryController updatePendingDrItemStatus (batch) failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.');
        }
    }

    public function updatePendingDrItemStatus()
    {
        $forReturnDr = DeliveryNote::query()
            ->where('return_status', 'For Return')
            ->where('docstatus', 0)
            ->pluck('name');

        if ($forReturnDr->isEmpty()) {
            return;
        }

        $drsWithItemsForReturn = DeliveryNoteItem::query()
            ->whereIn('parent', $forReturnDr)
            ->where('item_status', 'For Return')
            ->pluck('parent')
            ->unique();

        $drsToUpdate = $forReturnDr->diff($drsWithItemsForReturn);

        if ($drsToUpdate->isNotEmpty()) {
            DeliveryNote::query()
                ->whereIn('name', $drsToUpdate)
                ->where('docstatus', 0)
                ->update(['return_status' => 'Returned']);
        }
    }
}
