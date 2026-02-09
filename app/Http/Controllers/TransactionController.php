<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Item;
use App\Models\PackingSlip;
use App\Models\PackingSlipItem;
use App\Models\SalesOrder;
use App\Models\StockEntryDetail;
use App\Models\StockReservation;
use App\Models\WorkOrder;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionController extends Controller
{
    use ERPTrait, GeneralTrait;

    // /submit_transaction
    public function submitTransaction(Request $request)
    {
        DB::beginTransaction();
        try {
            $steDetails = DB::table('tabStock Entry as se')
                ->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
                ->where('sed.name', $request->child_tbl_id)
                ->select('se.name as parent_se', 'se.*', 'se.owner as requested_by', 'sed.*', 'sed.status as per_item_status', 'se.docstatus as se_status', 'se.material_request as mreq')
                ->first();

            if (!$steDetails) {
                return ApiResponse::failure('Record not found.', 500);
            }

            if (in_array($steDetails->per_item_status, ['Issued', 'Returned'])) {
                return ApiResponse::failure("Item already $steDetails->per_item_status .", 500);
            }

            if ($steDetails->se_status == 1) {
                return ApiResponse::failure('Item already issued.', 500);
            }

            $itemDetails = Item::find($steDetails->item_code);
            if (!$itemDetails) {
                return ApiResponse::failure("Item  <b> $steDetails->item_code </b> not found.", 500);
            }

            if ($itemDetails->disabled) {
                return ApiResponse::failure("Item Code <b> $itemDetails->item_code </b> is disabled.", 500);
            }

            if ($request->barcode != $itemDetails->item_code) {
                return ApiResponse::failure("Invalid barcode for <b> {$itemDetails->item_code} </b>.", 500);
            }

            if ($itemDetails->is_stock_item == 0) {
                return ApiResponse::failure("Item  <b> $steDetails->item_code </b> is not a stock item.", 500);
            }

            if ($request->qty <= 0) {
                return ApiResponse::failure('Qty cannot be less than or equal to 0.', 500);
            }

            if ($steDetails->purpose != 'Material Transfer for Manufacture' && $request->qty > $steDetails->qty) {
                return ApiResponse::failure('Qty cannot be greater than ' . ($steDetails->qty * 1) . '.', 500);
            }

            $availableQty = $this->getAvailableQty($steDetails->item_code, $steDetails->s_warehouse);
            if ($steDetails->purpose != 'Material Receipt' && $request->deduct_reserve == 0) {
                if ($request->qty > $availableQty) {
                    return ApiResponse::failure('Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><br><br>Available qty is <b>' . $availableQty . '</b>, you need <b>' . $request->qty . '</b>.', 500);
                }
            }

            $salesPerson = SalesOrder::where('name', $steDetails->sales_order_no)->value('sales_person');

            $reservedQty = StockReservation::where('item_code', $steDetails->item_code)
                ->where('warehouse', $steDetails->s_warehouse)
                ->where('sales_person', $salesPerson)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])
                ->sum('reserve_qty');

            $consumedQty = StockReservation::where('item_code', $steDetails->item_code)
                ->where('warehouse', $steDetails->s_warehouse)
                ->where('sales_person', $salesPerson)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])
                ->sum('consumed_qty');

            $remainingReserved = $reservedQty - $consumedQty;
            $remainingReserved = $remainingReserved > 0 ? $remainingReserved : 0;

            if ($request->qty > $remainingReserved && $request->deduct_reserve == 1) {  // For deduct from reserved, if requested qty is more than the reserved qty
                return ApiResponse::failure('Qty not available for <b> ' . $steDetails->item_code . '</b> in <b>' . $steDetails->s_warehouse . '</b><br><br>Reserved qty is <b>' . $remainingReserved . '</b>, you need <b>' . $request->qty . '</b>.', 500);
            }

            if ($steDetails->purpose == 'Material Transfer for Manufacture') {
                $cancelledProductionOrder = WorkOrder::where('name', $steDetails->work_order)->where('docstatus', 2)->first();

                if ($cancelledProductionOrder) {
                    return ApiResponse::failure('Production Order ' . $cancelledProductionOrder->name . ' was cancelled. Please reload the page.', 500);
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
                'date_modified' => now()->toDateTimeString()
            ];

            StockEntryDetail::where('name', $request->child_tbl_id)->update($values);

            $this->insertTransactionLog('Stock Entry', $request->child_tbl_id);

            $this->updateSteStatus($steDetails->parent_se);

            $stockReservationDetails = [];
            if ($request->has_reservation && $request->has_reservation == 1) {
                $refNo = $steDetails->sales_order_no ?? $steDetails->material_request;

                $soDetails = SalesOrder::find($refNo);

                $salesPerson = $soDetails ? $soDetails->sales_person : null;
                $project = $soDetails ? $soDetails->project : null;
                $consignmentWarehouse = null;
                if ($steDetails->transfer_as == 'Consignment') {
                    $salesPerson = null;
                    $project = null;
                    $consignmentWarehouse = $steDetails->t_warehouse;
                }

                $stockReservationDetails = $this->getStockReservation($steDetails->item_code, $steDetails->s_warehouse, $salesPerson, $project, $consignmentWarehouse);

                if ($stockReservationDetails && $request->deduct_reserve == 1) {
                    $consumedQty = $stockReservationDetails->consumed_qty + $request->qty;
                    $consumedQty = ($consumedQty > $stockReservationDetails->reserve_qty) ? $stockReservationDetails->reserve_qty : $consumedQty;

                    $data = [
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => now()->toDateTimeString(),
                        'consumed_qty' => $consumedQty
                    ];

                    StockReservation::where('name', $stockReservationDetails->name)->update($data);
                }

                $this->updateReservationStatus();
            }

            DB::commit();

            $submitResult = $this->submitStockEntry($steDetails->parent_se, 1);
            if (is_array($submitResult) && ($submitResult['error'] ?? 0)) {
                return ApiResponse::failure($submitResult['modal_message'] ?? 'An error occurred.', 500);
            }

            if ($request->deduct_reserve == 1) {
                return ApiResponse::success("Item $steDetails->item_code has been deducted from reservation.");
            }

            if (($steDetails->transfer_as == 'For Return') || $steDetails->purpose == 'Material Receipt') {
                return ApiResponse::success("Item <b> $steDetails->item_code </b> has been returned.");
            } else {
                return ApiResponse::success("Item <b> $steDetails->item_code </b> has been checked out.");
            }
        } catch (Exception $e) {
            Log::error('TransactionController submitTransaction failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.', 500);
        }
    }

    public function checkoutPickingSlip(Request $request)
    {
        $childId = $request->child_tbl_id;
        $packingSlip = PackingSlip::with(['items' => function ($item) {
            $item->with('packed');
        }])->whereHas('items', function ($item) use ($childId) {
            $item->where('name', $childId);
        })->first();

        if ($request->type == 'packed_item') {
            return $this->checkoutPickingSlipBundled($packingSlip, $childId, $request);
        }

        DB::connection('mysql')->beginTransaction();
        try {
            if (!$packingSlip) {
                return ApiResponse::failure('Record not found.');
            }

            $packingSlipItem = collect($packingSlip->items)->where('name', $childId);
            $index = collect($packingSlipItem)->search(function ($item) use ($childId) {
                return $item['name'] === $childId;
            });

            $packingSlipItem = $packingSlipItem->first();

            if (in_array($packingSlipItem->status, ['Issued', 'Returned'])) {
                return ApiResponse::failure('Item already ' . $packingSlipItem->status . '.');
            }

            if ($packingSlip->docstatus == 1) {
                return ApiResponse::failure('Item already submitted.');
            }

            $itemDetails = Item::find($packingSlipItem->item_code);
            if (!$itemDetails) {
                return ApiResponse::failure('Item  <b>' . $packingSlipItem->item_code . '</b> not found.');
            }

            if ($itemDetails->disabled) {
                return ApiResponse::failure('Item  <b>' . $packingSlipItem->item_code . '</b> is disabled.');
            }

            if ($request->is_bundle == 0) {
                if ($itemDetails->is_stock_item == 0) {
                    return ApiResponse::failure('Item  <b>' . $packingSlipItem->item_code . '</b> is not a stock item.');
                }
            }

            if ($request->barcode != $itemDetails->name) {
                return ApiResponse::failure('Invalid barcode for <b>' . $itemDetails->name . '</b>.');
            }

            if ($request->qty <= 0) {
                return ApiResponse::failure('Qty cannot be less than or equal to 0.');
            }

            $availableQty = $this->getAvailableQty($packingSlipItem->item_code, $request->warehouse);
            if ($request->qty > $availableQty && $request->is_bundle == false && $request->deduct_reserve == 0) {
                return ApiResponse::failure('Qty not available for <b> ' . $packingSlipItem->item_code . '</b> in <b>' . $request->warehouse . '</b><br><br>Available qty is <b>' . $availableQty . '</b>, you need <b>' . $request->qty . '</b>.');
            }

            $reservedQty = $this->getReservedQty($packingSlipItem->item_code, $request->warehouse);
            if ($request->qty > $reservedQty && $request->is_bundle == false && $request->deduct_reserve == 1) {
                return ApiResponse::failure('Qty not available for <b> ' . $packingSlipItem->item_code . '</b> in <b>' . $request->warehouse . '</b><br><br>Available reserved qty is <b>' . $reservedQty . '</b>, you need <b>' . $request->qty . '</b>.');
            }

            $countPendingItems = PackingSlipItem::where('parent', $packingSlip->name)
                ->where('status', 'For Checking')
                ->where('name', '!=', $childId)
                ->count();

            $values = $packingSlip;
            $values->items[$index]->session_user = Auth::user()->wh_user;
            $values->items[$index]->status = 'Issued';
            $values->items[$index]->barcode = $request->barcode;
            $values->items[$index]->date_modified = now()->toDateTimeString();
            $values->items[$index]->qty = (float) $values->items[$index]->qty;
            $values->net_weight_pkg = (float) $values->net_weight_pkg;
            $values->gross_weight_pkg = (float) $values->gross_weight_pkg;
            $values->qty = (float) $values->qty;
            if ($countPendingItems < 1) {
                $values->item_status = 'Issued';
                $values->docstatus = 1;
            }

            // Ensure ALL item qty are not string
            $values->items = collect($values->items)->map(function ($item) {
                $item->net_weight = (float) $item->net_weight;
                $item->qty = (float) $item->qty;
                return $item;
            });

            $stockReservationDetails = [];
            if ($request->has_reservation && $request->has_reservation == 1) {
                $soDetails = SalesOrder::find($request->sales_order);
                if ($soDetails) {
                    $stockReservationDetails = $this->getStockReservation($packingSlipItem->item_code, $request->warehouse, $soDetails->sales_person, $soDetails->project, null, $soDetails->order_type, $soDetails->po_no);
                }

                if ($stockReservationDetails && $request->deduct_reserve == 1) {
                    $consumedQty = $stockReservationDetails->consumed_qty + $request->qty;
                    $consumedQty = ($consumedQty > $stockReservationDetails->reserve_qty) ? $stockReservationDetails->reserve_qty : $consumedQty;

                    $data = [
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => now()->toDateTimeString(),
                        'consumed_qty' => $consumedQty
                    ];

                    StockReservation::where('name', $stockReservationDetails->name)->update($data);
                }

                $this->updateReservationStatus();
            }

            $response = $this->erpPut('Packing Slip', $packingSlip->name, $values->toArray(), true);
            if (!isset($response['data'])) {
                $err = $response['exception'] ?? 'An error occured while updating picking slip';
                throw new Exception($err);
            }

            $this->insertTransactionLog('Picking Slip', $childId);

            DB::connection('mysql')->commit();

            if ($request->deduct_reserve == 1) {
                return ApiResponse::success('Item ' . $itemDetails->name . ' has been deducted from reservation.');
            }

            return ApiResponse::success('Item ' . $itemDetails->name . ' has been checked out.');
        } catch (Exception $e) {
            Log::error('TransactionController checkoutPickingSlip failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::connection('mysql')->rollback();

            return ApiResponse::modal(false, 'Error', 'Error creating transaction.', 422);
        }
    }

    public function checkoutPickingSlipBundled($packingSlip, $childId, $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $now = now();

            $packedItems = collect($packingSlip->items)->filter(function ($item) use ($request) {
                return $item->packed && $item->packed->parent_item == $request->barcode;
            });

            if ($packedItems->isEmpty()) {
                throw new Exception('Item(s) not found');
            }

            $packedItemNames = collect($packedItems)->pluck('name');

            $countPendingItems = PackingSlipItem::where('parent', $packingSlip->name)
                ->where('status', 'For Checking')
                ->whereNotIn('name', $packedItemNames)
                ->count();

            $itemsWithPacked = collect($packingSlip->items)->filter(fn ($item) => $item->packed);
            $itemWarehousePairs = $itemsWithPacked->map(fn ($item) => [$item->item_code, $item->packed->warehouse])->unique()->values()->toArray();
            $availableQtyMap = $this->getAvailableQtyBulk($itemWarehousePairs);

            foreach ($packingSlip->items as $item) {
                $item->qty = (float) $item->qty;

                if ($item->packed) {
                    $key = "{$item->item_code}-{$item->packed->warehouse}";
                    $availableQty = $availableQtyMap[$key] ?? 0;
                    if ($item->qty > $availableQty && $request->deduct_reserve == 0) {
                        return ApiResponse::failure('Qty not available for <b> ' . $item->item_code . '</b> in <b>' . $item->warehouse . '</b><br><br>Available qty is <b>' . $availableQty . '</b>, you need <b>' . $item->qty . '</b>.');
                    }

                    if ($item->packed->parent_item === $request->barcode) {
                        $item->session_user = Auth::user()->wh_user;
                        $item->status = 'Issued';
                        $item->barcode = $request->barcode;
                        $item->date_modified = $now->toDateTimeString();
                        $item->qty = (float) $request->qty;
                    }

                    unset($item->packed);
                }
            }

            if ($countPendingItems < 1) {
                $packingSlip->item_status = 'Issued';
                $packingSlip->docstatus = 1;
            }

            if ($request->has_reservation && $request->deduct_reserve) {
                $soDetails = SalesOrder::find($request->sales_order);
                foreach ($packedItems as $packed) {
                    if ($soDetails) {
                        $stockReservationDetails = $this->getStockReservation($packed->item_code, $request->warehouse, $soDetails->sales_person, $soDetails->project, null, $soDetails->order_type, $soDetails->po_no);
                    }

                    if ($stockReservationDetails) {
                        $consumedQty = $stockReservationDetails->consumed_qty + $request->qty;
                        $consumedQty = ($consumedQty > $stockReservationDetails->reserve_qty) ? $stockReservationDetails->reserve_qty : $consumedQty;

                        $data = [
                            'modified_by' => Auth::user()->wh_user,
                            'modified' => now()->toDateTimeString(),
                            'consumed_qty' => $consumedQty
                        ];

                        StockReservation::where('name', $stockReservationDetails->name)->update($data);
                    }
                }
            }

            $response = $this->erpPut('Packing Slip', $packingSlip->name, collect($packingSlip)->toArray());
            if (!isset($response['data'])) {
                $err = $response['exception'] ?? 'An error occured while updating Packing Slip';
                throw new Exception($err);
            }

            DB::connection('mysql')->commit();

            $message = $request->deduct_reserve ? "Item <b>$request->barcode</b> has been deducted from reservation" : "Item <b>$request->barcode</b> has been checked out";

            return ApiResponse::success($message);
        } catch (\Throwable $th) {
            Log::error('TransactionController returnPickingSlipItem failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::connection('mysql')->rollback();
            return ApiResponse::modal(false, 'Error', $th->getMessage(), 422);
        }
    }
}
