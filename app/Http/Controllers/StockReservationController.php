<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\Bin;
use App\Traits\GeneralTrait;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Models\WarehouseAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class StockReservationController extends Controller
{
    use GeneralTrait;

    public function userAllowedWarehouse($user)
    {
        return \App\Models\User::getAllowedWarehouseIdsFor($user);
    }

    public function createReservation(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            // restrict zero qty
            if ($request->reserve_qty <= 0) {
                return ApiResponse::modal(false, 'Stock Reservation', 'Reserve Qty must be greater than 0.', 422);
            }

            $binDetails = Bin::forItemAndWarehouse($request->item_code, $request->warehouse)->first();

            if (!$binDetails) {
                return ApiResponse::modal(false, 'No Stock', 'No available stock.', 422);
            }

            $availableQty = $binDetails->getAvailableQty();

            if ($availableQty < $request->reserve_qty) {
                return ApiResponse::modal(false, 'Insufficient Stock', 'Qty not available for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b><br><br>Available qty is <b>' . $availableQty . '</b>, you need <b>' . $request->reserve_qty . '</b>', 422);
            }

            if ($request->type == 'In-house') {
                if (Carbon::createFromFormat('Y-m-d', $request->valid_until) <= now()) {
                    return ApiResponse::modal(false, 'Invalid Date', 'Validity date cannot be less than or equal to date today.', 422);
                }
            }

            if ($request->type == 'Consignment' && !$request->consignment_warehouse) {
                return ApiResponse::modal(false, 'Select Branch', 'Please select Branch.', 422);
            }

            $existingStockReservation = StockReservation::query()
                ->forItemAndWarehouse($request->item_code, $request->warehouse)
                ->where('sales_person', $request->sales_person)
                ->where('type', $request->type)
                ->where('project', $request->project)
                ->where('consignment_warehouse', $request->consignment_warehouse)
                ->active()
                ->exists();

            if ($existingStockReservation) {
                return ApiResponse::modal(false, 'Already Exists', 'Stock Reservation already exists.', 422);
            }

            $latestName = StockReservation::max('name');
            $latestNameExploded = explode('-', $latestName);
            $newId = (!$latestName) ? 1 : $latestNameExploded[1] + 1;
            $newId = str_pad($newId, 5, '0', STR_PAD_LEFT);
            $newId = 'STR-' . $newId;

            $now = now();
            $stockReservation = new StockReservation;
            $stockReservation->name = $newId;
            $stockReservation->creation = $now->toDateTimeString();
            $stockReservation->modified = null;
            $stockReservation->modified_by = null;
            $stockReservation->owner = Auth::user()->wh_user;
            $stockReservation->description = $request->description;
            $stockReservation->notes = $request->notes;
            $stockReservation->created_by = Auth::user()->full_name;
            $stockReservation->stock_uom = $request->stock_uom;
            $stockReservation->item_code = $request->item_code;
            $stockReservation->warehouse = $request->warehouse;
            $stockReservation->type = $request->type;
            $stockReservation->reserve_qty = $request->reserve_qty;
            $stockReservation->valid_until = ($request->type == 'In-house') ? Carbon::createFromFormat('Y-m-d', $request->valid_until) : null;
            $stockReservation->sales_person = ($request->type == 'In-house') ? $request->sales_person : null;
            $stockReservation->project = ($request->type == 'In-house') ? $request->project : null;
            $stockReservation->consignment_warehouse = ($request->type == 'Consignment') ? $request->consignment_warehouse : null;
            $stockReservation->save();

            if ($request->type == 'Website Stocks') {
                if ($binDetails) {
                    $newReservedQty = $request->reserve_qty + $binDetails->website_reserved_qty;

                    $values = [
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'website_reserved_qty' => $newReservedQty,
                    ];

                    Bin::where('name', $binDetails->name)->update($values);
                }
            }

            DB::connection('mysql')->commit();

            return ApiResponse::modal(true, 'Stock Reservation', 'Stock Reservation No. ' . $newId . ' has been created.');
        } catch (Exception $e) {
            DB::connection('mysql')->rollback();

            return ApiResponse::modal(false, 'Stock Reservation', 'There was a problem creating Stock Reservation.', 422);
        }
    }

    public function getStockReservation(Request $request, $itemCode = null)
    {
        $webList = StockReservation::when($itemCode, function ($query) use ($itemCode) {
            $query->where('item_code', $itemCode)->where('type', 'Website Stocks')->orderby('creation', 'desc');
        })->paginate(10, ['*'], 'tbl_1');

        $inhouseList = StockReservation::when($itemCode, function ($query) use ($itemCode) {
            $query->where('item_code', $itemCode)->where('type', 'In-house')->orderby('valid_until', 'desc');
        })->paginate(10, ['*'], 'tbl_3');

        $consignmentList = StockReservation::when($itemCode, function ($query) use ($itemCode) {
            $query->where('item_code', $itemCode)->where('type', 'Consignment')->orderby('valid_until', 'desc');
        })->paginate(10, ['*'], 'tbl_2');

        $stockEntryIssued = DB::table('tabStock Entry Detail as sted')
            ->join('tabStock Entry as ste', 'ste.name', 'sted.parent')
            ->where('sted.docstatus', 0)
            ->where('sted.status', 'Issued')
            ->where('sted.item_code', $itemCode)
            ->whereNotIn('ste.purpose', ['Manufacture', 'Material Receipt'])
            ->select('sted.parent', 'sted.s_warehouse', 'sted.qty', 'sted.uom', 'ste.owner', 'sted.session_user', 'sted.modified', 'ste.creation')
            ->orderBy('sted.modified', 'desc')
            ->get();

        $athenaIssued = DB::table('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)
            ->where('ps.docstatus', '<', 2)
            ->where('psi.status', 'Issued')
            ->where('at.item_code', $itemCode)
            ->where('psi.item_code', $itemCode)
            ->where('at.status', 'Issued')
            ->select('ps.name', 'at.source_warehouse', 'at.issued_qty', 'psi.stock_uom', 'ps.creation', 'ps.owner', 'psi.session_user', 'psi.modified')
            ->orderBy('psi.modified', 'desc')
            ->get();

        $pendingItems = [];
        foreach ($stockEntryIssued as $issuedItem) {
            $pendingItems[] = [
                'id' => $issuedItem->parent,
                'warehouse' => $issuedItem->s_warehouse,
                'qty' => $issuedItem->qty * 1,
                'uom' => $issuedItem->uom,
                'owner' => $issuedItem->owner,
                'issued_by' => $issuedItem->session_user,
                'issued_at' => $issuedItem->modified,
                'date' => $issuedItem->creation
            ];
        }

        foreach ($athenaIssued as $issuedItem) {
            $pendingItems[] = [
                'id' => $issuedItem->name,
                'warehouse' => $issuedItem->source_warehouse,
                'qty' => $issuedItem->issued_qty * 1,
                'uom' => $issuedItem->stock_uom,
                'owner' => $issuedItem->owner,
                'issued_by' => $issuedItem->session_user,
                'issued_at' => $issuedItem->modified,
                'date' => $issuedItem->creation
            ];
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($pendingItems);
        $perPage = 10;
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        $paginatedPendingItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        $paginatedPendingItems->setPath($request->url());

        return view('stock_reservation.list', compact('consignmentList', 'webList', 'inhouseList', 'itemCode', 'paginatedPendingItems'));
    }

    public function cancelReservation(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            $now = now();
            $stockReservation = StockReservation::find($request->stock_reservation_id);
            $stockReservation->modified = $now->toDateTimeString();
            $stockReservation->modified_by = Auth::user()->wh_user;
            $stockReservation->status = 'Cancelled';
            $stockReservation->save();

            if ($stockReservation->type == 'Website Stocks') {
                $binDetails = Bin::where('item_code', $stockReservation->item_code)
                    ->where('warehouse', $stockReservation->warehouse)
                    ->first();

                if ($binDetails) {
                    $newReservedQty = $binDetails->website_reserved_qty - $stockReservation->reserve_qty;

                    $newReservedQty = ($newReservedQty <= 0) ? 0 : $newReservedQty;

                    $values = [
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'website_reserved_qty' => $newReservedQty,
                    ];

                    Bin::where('name', $binDetails->name)->update($values);
                }
            }

            DB::connection('mysql')->commit();

            return ApiResponse::modal(true, 'Stock Reservation', 'Stock Reservation No. ' . $request->stock_reservation_id . ' has been cancelled.');
        } catch (Exception $e) {
            DB::connection('mysql')->rollback();

            return ApiResponse::modal(false, 'Stock Reservation', 'There was a problem cancelling Stock Reservation.', 422);
        }
    }

    public function getStockReservationDetails($id)
    {
        return StockReservation::find($id);
    }

    public function updateReservation(Request $request)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            if ($request->reserve_qty <= 0) {
                return ApiResponse::modal(false, 'Stock Reservation', 'Reserve Qty must be greater than 0.', 422);
            }

            // get total partially issued qty
            $partiallyIssuedQty = StockReservation::where('item_code', $request->item_code)->where('warehouse', $request->warehouse)->where('type', 'In-house')->where('status', 'Partially Issued')->where('sales_person', $request->sales_person)->sum('consumed_qty');

            if ($partiallyIssuedQty > 0 && $partiallyIssuedQty >= $request->reserve_qty) {
                return ApiResponse::modal(false, 'Stock Reservation', 'Reserve qty must be greater than the partially issued qty for this reservation.', 422);
            }

            $binDetails = Bin::where('item_code', $request->item_code)
                ->where('warehouse', $request->warehouse)
                ->first();

            if (!$binDetails) {
                return ApiResponse::modal(false, 'No Stock', 'No available stock.', 422);
            }
            // get total reserved qty from stock reservation table
            $stockReservationQty = StockReservation::where('item_code', $request->item_code)
                ->where('warehouse', $request->warehouse)
                ->where('type', 'In-house')
                ->where('status', 'Active')
                ->sum('reserve_qty');
            // total reserved qty = total reserved qty from stock reservation table + website reserved qty from tabbin table
            $totalReservedQty = $stockReservationQty + $binDetails->website_reserved_qty;

            $now = now();
            $stockReservation = StockReservation::find($request->id);
            $stockReservation->modified = $now->toDateTimeString();
            $stockReservation->modified_by = Auth::user()->wh_user;
            $stockReservation->notes = $request->notes;

            // calculate reserved qty
            $reservedQty = ($stockReservation->type == 'In house') ? $stockReservation->reserve_qty : $binDetails->website_reserved_qty;
            $availableQty = ($request->available_qty + ($reservedQty - $stockReservation->consumed_qty));

            if ($availableQty < $request->reserve_qty) {
                return ApiResponse::modal(false, 'Insufficient Stock', 'Qty not available for <b> ' . $request->item_code . '</b> in <b>' . $request->s_warehouse . '</b><br><br>Available qty is <b>' . $availableQty . '</b>, you need <b>' . $request->reserve_qty . '</b>', 422);
            }

            if ($stockReservation->type == 'Website Stocks') {
                $reservedQty = abs($stockReservation->reserve_qty - $request->reserve_qty);

                if ($binDetails) {
                    $newReservedQty = $binDetails->website_reserved_qty;
                    if ($stockReservation->reserve_qty > $request->reserve_qty) {
                        $newReservedQty = $binDetails->website_reserved_qty - $reservedQty;
                    }

                    if ($stockReservation->reserve_qty < $request->reserve_qty) {
                        $newReservedQty = $binDetails->website_reserved_qty + $reservedQty;
                    }

                    $newReservedQty = ($newReservedQty <= 0) ? 0 : $newReservedQty;

                    $values = [
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'website_reserved_qty' => $newReservedQty,
                    ];

                    Bin::where('name', $binDetails->name)->update($values);
                }
            }

            $stockReservation->warehouse = $request->warehouse;
            $stockReservation->reserve_qty = $request->reserve_qty;
            $stockReservation->valid_until = ($stockReservation->type == 'In-house') ? Carbon::parse($request->valid_until)->format('Y-m-d') : null;
            $stockReservation->sales_person = ($stockReservation->type == 'In-house') ? $request->sales_person : null;
            $stockReservation->project = ($stockReservation->type == 'In-house') ? $request->project : null;
            $stockReservation->consignment_warehouse = ($stockReservation->type == 'Consignment') ? $request->consignment_warehouse : null;
            $stockReservation->save();

            DB::connection('mysql')->commit();

            return ApiResponse::modal(true, 'Stock Reservation', 'Stock Reservation No. ' . $request->id . ' has been updated.');
        } catch (Exception $e) {
            DB::connection('mysql')->rollback();
            return ApiResponse::modal(false, 'Stock Reservation', 'There was a problem updating Stock Reservation.', 422);
        }
    }

    public function getWarehouseWithStocks(Request $request)
    {
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        return Warehouse::query()
            ->join('tabBin as b', 'b.warehouse', 'tabWarehouse.name')
            ->where('tabWarehouse.disabled', 0)
            ->where('tabWarehouse.is_group', 0)
            ->whereIn('tabWarehouse.name', $allowedWarehouses)
            ->where('b.item_code', $request->item_code)
            ->when($request->q, function ($query) use ($request) {
                return $query->where('tabWarehouse.name', 'like', '%' . $request->q . '%');
            })
            ->select('tabWarehouse.name as id', 'tabWarehouse.name as text')
            ->orderBy('tabWarehouse.modified', 'desc')
            ->limit(10)
            ->get();
    }
}
