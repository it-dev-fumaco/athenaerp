<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\AthenaTransaction;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\ItemSupplier;
use App\Models\PackingSlip;
use App\Models\StockEntry;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DeliveryController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function viewDeliveries(Request $request)
    {
        if (!$request->arr) {
            return view('picking_slip');
        }

        $allowedWarehouses = $this->getAllowedWarehouseIds();
        $search = $request->search;

        // Picking Slip - SINGLE ITEMS
        $pickingSlipQuery = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->whereRaw('dri.item_code = psi.item_code')
            ->where([
                'dr.docstatus' => 0,
                'ps.docstatus' => 0
            ])
            ->where(function ($query) use ($search) {
                $query
                    ->where('psi.item_code', 'like', "%{$search}%")
                    ->orWhere('psi.description', 'like', "%{$search}%")
                    ->orWhere('dr.customer', 'like', "%{$search}%")
                    ->orWhere('ps.sales_order', 'like', "%{$search}%")
                    ->orWhere('ps.name', 'like', "%{$search}%")
                    ->orWhere('dr.name', 'like', "%{$search}%")
                    ->orWhere('psi.name', 'like', "%{$search}%");
            })
            ->whereIn('dri.warehouse', $allowedWarehouses)
            ->select([
                'dr.delivery_date',
                'ps.sales_order',
                DB::raw('NULL as sales_order_no'),
                'psi.name AS id',
                'psi.status',
                'ps.name',
                'ps.delivery_note',
                'psi.item_code',
                'psi.description',
                DB::raw('SUM(dri.qty) as qty'),
                'dri.uom',
                'dri.warehouse',
                'psi.owner',
                'dr.customer',
                'ps.creation',
                DB::raw('NULL as parent_item'),
                DB::raw('NULL as piName'),
                DB::raw('NULL as piQty'),
                DB::raw('NULL as piWarehouse'),
                DB::raw('NULL as piUom'),
                DB::raw('"picking_slip" as type')
            ])
            ->groupBy([
                'dr.delivery_date', 'ps.sales_order', 'psi.name', 'psi.status', 'ps.name',
                'ps.delivery_note', 'psi.item_code', 'psi.description', 'dri.uom',
                'dri.warehouse', 'psi.owner', 'dr.customer', 'ps.creation'
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        // Stock Entry
        $stockEntryQuery = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', '=', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowedWarehouses)
            ->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->where(function ($query) use ($search) {
                $query
                    ->where('sted.item_code', 'like', "%{$search}%")
                    ->orWhere('sted.description', 'like', "%{$search}%")
                    ->orWhere('ste.customer_1', 'like', "%{$search}%")
                    ->orWhere('ste.sales_order_no', 'like', "%{$search}%")
                    ->orWhere('ste.name', 'like', "%{$search}%")
                    ->orWhere('ste.so_customer_name', 'like', "%{$search}%")
                    ->orWhere('sted.name', 'like', "%{$search}%");
            })
            ->select([
                'ste.delivery_date',
                DB::raw('NULL as sales_order'),
                'ste.sales_order_no',
                'sted.name AS id',
                'sted.status',
                'ste.name',
                DB::raw('NULL as delivery_note'),
                'sted.item_code',
                'sted.description',
                'sted.qty',
                'sted.uom',
                'sted.s_warehouse as warehouse',
                'sted.owner',
                'ste.customer_1 as customer',
                'ste.creation',
                DB::raw('NULL as parent_item'),
                DB::raw('NULL as piName'),
                DB::raw('NULL as piQty'),
                DB::raw('NULL as piWarehouse'),
                DB::raw('NULL as piUom'),
                DB::raw('"stock_entry" as type')
            ])
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC");

        // Product Bundles
        $productBundleQuery = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', '=', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', '=', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', '=', 'dr.name')
            ->join('tabPacked Item as pi', 'pi.name', '=', 'psi.pi_detail')
            ->where([
                'dr.docstatus' => 0,
                'ps.docstatus' => 0
            ])
            ->whereIn('dri.warehouse', $allowedWarehouses)
            ->where(function ($query) use ($search) {
                $query
                    ->where('pi.item_code', 'like', "%{$search}%")
                    ->orWhere('pi.description', 'like', "%{$search}%")
                    ->orWhere('dr.customer', 'like', "%{$search}%")
                    ->orWhere('ps.sales_order', 'like', "%{$search}%")
                    ->orWhere('ps.name', 'like', "%{$search}%")
                    ->orWhere('dr.name', 'like', "%{$search}%")
                    ->orWhere('psi.name', 'like', "%{$search}%");
            })
            ->select([
                'dr.delivery_date',
                'ps.sales_order',
                DB::raw('NULL as sales_order_no'),
                'psi.name AS id',
                'psi.status',
                'ps.name',
                'ps.delivery_note',
                'pi.item_code',
                'pi.description',
                'pi.qty as qty',
                'pi.uom',
                'pi.warehouse',
                'psi.owner',
                'dr.customer',
                'ps.creation',
                'pi.parent_item',
                'pi.name as piName',
                'pi.qty as piQty',
                'pi.warehouse as piWarehouse',
                'pi.uom as piUom',
                DB::raw('"packed_item" as type')
            ])
            ->orderByRaw("FIELD(psi.status, 'For Checking', 'Issued') ASC");

        // Union everything together
        $unionQuery = $pickingSlipQuery
            ->unionAll($stockEntryQuery)
            ->unionAll($productBundleQuery);

        // Apply pagination
        $paginatedData = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->orderByRaw("FIELD(status, 'For Checking', 'Issued') ASC")
            ->mergeBindings($unionQuery->getQuery())
            ->paginate(20);

        // Gather all item codes and owners
        $itemCodes = collect($paginatedData->items())->pluck('item_code')->unique();
        $owners = collect($paginatedData->items())->pluck('owner')->unique();

        // Fetch supplier part numbers in bulk
        $supplierPartNos = ItemSupplier::query()
            ->whereIn('parent', $itemCodes)
            ->pluck('supplier_part_no', 'parent');

        // Fetch owners' full names in bulk (tabWarehouse Users uses wh_user, not email)
        $ownerNames = User::query()
            ->whereIn('wh_user', $owners)
            ->pluck('full_name', 'wh_user');

        $warehouseNames = collect($paginatedData->items())
            ->map(fn ($d) => $d->warehouse ?? $d->s_warehouse ?? null)
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        $parentWarehouses = $this->getWarehouseParentsBulk($warehouseNames);

        $list = [];
        foreach ($paginatedData->items() as $d) {
            $partNos = $supplierPartNos->get($d->item_code, '');
            $owner = $ownerNames->get($d->owner, null);
            $warehouseKey = $d->warehouse ?? $d->s_warehouse ?? null;
            $parentWarehouse = $warehouseKey ? Arr::get($parentWarehouses, $warehouseKey, null) : null;

            $list[] = [
                'owner' => $owner,
                'warehouse' => $d->warehouse ?? $d->s_warehouse ?? null,
                'customer' => $d->customer ?? $d->customer_1 ?? null,
                'sales_order' => $d->sales_order ?? $d->sales_order_no ?? null,
                'id' => $d->id,
                'part_nos' => $partNos,
                'status' => $d->status,
                'name' => $d->name,
                'delivery_note' => $d->delivery_note ?? null,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'qty' => $d->qty,
                'stock_uom' => $d->uom ?? $d->stock_uom ?? null,
                'parent_warehouse' => $parentWarehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'type' => $d->type,
                'classification' => $d->transfer_as ?? 'Customer Order',
                'delivery_date' => Carbon::parse($d->delivery_date)->format('M-d-Y'),
                'delivery_status' => (Carbon::parse($d->delivery_date) < Carbon::now()) ? 'late' : null,
            ];
        }

        return response()->json([
            'picking' => $list,
            'pagination' => [
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage()
            ]
        ]);
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

        if (!$q) {
            throw new \ErrorException('Delivery Note Item not found.');
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
            'ref_no' => $q->against_sales_order . '<br>' . $q->name,
            'stock_uom' => $q->stock_uom,
            'qty' => abs($q->qty * 1),
            'owner' => $owner,
            'status' => $q->item_status,
            'reference' => 'Delivery Note',
            'docstatus' => $q->docstatus
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

            $now = Carbon::now();

            if (!$deliveryNote) {
                throw new Exception('Record not found');
            }

            if (in_array($deliveryNote->item_status, ['Issued', 'Returned'])) {
                throw new Exception("Item already $deliveryNote->item_status.");
            }

            $deliveryNote->qty = (float) $deliveryNote->qty;

            $deliveryNoteItem = collect($deliveryNote->items)->where('name', $childId)->first();
            $itemCode = $deliveryNoteItem->item_code;
            $itemDetails = Item::find($itemCode);

            if (!$itemDetails) {
                throw new Exception("Item <b>$itemCode</b> not found.");
            }

            if (!$itemDetails->is_stock_item) {
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
            if (!array_key_exists('data', $response)) {
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

            if (!$driDetails) {
                return ApiResponse::failure('Record not found.');
            }

            if (in_array($driDetails->per_item_status, ['Issued', 'Returned'])) {
                return ApiResponse::failure('Item already ' . $driDetails->per_item_status . '.');
            }

            if ($driDetails->dr_status == 1) {
                return ApiResponse::failure('Item already returned.');
            }

            $itemDetails = Item::query()->where('name', $driDetails->item_code)->first();
            if (!$itemDetails) {
                return ApiResponse::failure('Item  <b>' . $driDetails->item_code . '</b> not found.');
            }

            if ($itemDetails->is_stock_item == 0) {
                return ApiResponse::failure('Item  <b>' . $driDetails->item_code . '</b> is not a stock item.');
            }

            if ($request->barcode != $itemDetails->item_code) {
                return ApiResponse::failure('Invalid barcode for <b>' . $itemDetails->item_code . '</b>.');
            }

            $values = [
                'session_user' => Auth::user()->wh_user,
                'item_status' => 'Returned',
                'barcode_return' => $request->barcode,
                'date_modified' => now()->toDateTimeString()
            ];

            DeliveryNoteItem::query()->where('name', $request->child_tbl_id)->update($values);

            $this->updatePendingDrItemStatus();
            $this->insertTransactionLog('Delivery Note', $request->child_tbl_id);

            DB::commit();

            return ApiResponse::success('Item <b>' . $driDetails->item_code . '</b> has been returned.');
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
