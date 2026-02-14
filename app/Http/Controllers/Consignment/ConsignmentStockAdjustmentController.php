<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\BeginningInventoryItem;
use App\Models\Bin;
use App\Models\ConsignmentDamagedItems;
use App\Models\ConsignmentStockAdjustment;
use App\Models\ConsignmentStockAdjustmentItem;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\StockEntry;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ConsignmentStockAdjustmentController extends Controller
{
    use ERPTrait, GeneralTrait;

    private function checkItemTransactions(string $itemCode, string $branch, $date, ?string $csaId = null): array
    {
        $transactionDate = Carbon::parse($date);
        $now = now();

        $hasStockEntry = StockEntry::whereHas('items', function ($query) use ($now, $transactionDate, $itemCode, $branch) {
            $query
                ->where('item_code', $itemCode)
                ->whereBetween('consignment_date_received', [$transactionDate, $now])
                ->where('s_warehouse', $branch);
        })
            ->whereIn('transfer_as', ['Consignment', 'For Return', 'Store Transfer'])
            ->whereIn('item_status', ['For Checking', 'Issued'])
            ->where('purpose', 'Material Transfer')
            ->where('docstatus', 1)
            ->exists();

        $hasDamagedItems = ConsignmentDamagedItems::where('branch_warehouse', $branch)
            ->where('item_code', $itemCode)
            ->whereBetween('transaction_date', [$transactionDate, $now])
            ->exists();

        $hasStockAdjustments = ConsignmentStockAdjustment::whereHas('items', function ($query) use ($itemCode) {
            $query->where('item_code', $itemCode);
        })
            ->whereBetween('creation', [$transactionDate, $now])
            ->where('warehouse', $branch)
            ->where('status', '!=', 'Cancelled')
            ->when($csaId !== null, function ($query) use ($csaId) {
                return $query->where('name', '!=', $csaId);
            })
            ->exists();

        return [
            'ste_transactions' => $hasStockEntry,
            'damaged_transactions' => $hasDamagedItems,
            'stock_adjustment_transactions' => $hasStockAdjustments,
        ];
    }

    public function cancelStockAdjustment($id)
    {
        DB::beginTransaction();
        try {
            $adjustmentDetails = ConsignmentStockAdjustment::find($id);

            if (! $adjustmentDetails) {
                return redirect()->back()->with('error', 'Stock adjustment record not found.');
            }

            if ($adjustmentDetails->status == 'Cancelled') {
                return redirect()->back()->with('error', 'Stock adjustment is already cancelled');
            }

            $adjustedItems = ConsignmentStockAdjustmentItem::where('parent', $adjustmentDetails->name)->get();

            if (! $adjustedItems->isNotEmpty()) {
                return redirect()->back()->with('error', 'Items not found.');
            }

            foreach ($adjustedItems as $item) {
                $hasTransactions = $this->checkItemTransactions($item->item_code, $adjustmentDetails->warehouse, $adjustmentDetails->creation, $id);

                if (collect($hasTransactions)->max() > 0) {
                    return redirect()->back()->with('error', 'Cannot cancel stock adjustment record. Item '.$item->item_code.' has existing transaction(s).');
                }

                Bin::where('item_code', $item->item_code)->where('warehouse', $adjustmentDetails->warehouse)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $item->previous_qty,
                    'consignment_price' => $item->previous_price,
                ]);
            }

            ConsignmentStockAdjustment::where('name', $id)->update([
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled',
            ]);

            $logs = [
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Adjustment '.$adjustmentDetails->name.' has been cancelled by '.Auth::user()->full_name.' at '.now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Adjustment',
                'reference_name' => $adjustmentDetails->name,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            ActivityLog::insert($logs);

            DB::commit();

            return redirect()->back()->with('success', 'Stock Adjustment Cancelled.');
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function viewStockAdjustmentHistory(Request $request)
    {
        $stockAdjustments = ConsignmentStockAdjustment::with('items')
            ->when($request->branch_warehouse, function ($query) use ($request) {
                return $query->where('warehouse', $request->branch_warehouse);
            })
            ->orderBy('creation', 'desc')
            ->paginate(10);

        $itemCodes = collect($stockAdjustments->items())->flatMap(function ($stockAdjustment) {
            return $stockAdjustment->items->pluck('item_code');
        })->unique()->values();

        $flattenItemCodes = $itemCodes->implode("','");

        $itemImages = ItemImages::whereRaw("parent IN ('$flattenItemCodes')")->pluck('image_path', 'parent');

        $stockAdjustmentsArray = collect($stockAdjustments->items())->map(function ($stockAdjustment) use ($itemImages) {
            $warehouse = $stockAdjustment->warehouse;
            $creation = $stockAdjustment->creation;
            $stockAdjustment->items = collect($stockAdjustment->items)->map(function ($item) use ($itemImages, $warehouse, $creation) {
                $itemCode = $item->item_code;
                $transactions = $this->checkItemTransactions($itemCode, $warehouse, $creation, $item->parent);

                $item->transactions = $transactions;
                $item->has_transactions = in_array(true, $transactions);

                $item->previous_qty = (int) $item->previous_qty;
                $item->previous_price = (float) $item->previous_price;

                $item->new_qty = (int) $item->new_qty;
                $item->new_price = (float) $item->new_price;

                $item->item_description = strip_tags($item->item_description);

                $item->reason = $item->remarks;

                $item->image = Arr::exists($itemImages, $itemCode) ? '/img/'.$itemImages[$itemCode] : '/icon/no_img.png';
                if (Storage::disk('upcloud')->exists(explode('.', $item->image)[0].'.webp')) {
                    $item->image = explode('.', $item->image)[0].'.webp';
                }

                return $item;
            });

            $stockAdjustment->transaction_date = Carbon::parse("$stockAdjustment->transaction_date $stockAdjustment->transaction_time")->format('M. d, Y h:i A');
            $stockAdjustment->has_transactions = in_array(true, collect($stockAdjustment->items)->pluck('has_transactions')->toArray());

            return $stockAdjustment;
        });

        return view('consignment.supervisor.view_stock_adjustment_history', compact('stockAdjustments', 'stockAdjustmentsArray'));
    }

    public function viewStockAdjustmentForm()
    {
        $item = Bin::query()->join('tabItem', 'tabItem.name', 'tabBin.item_code')->select('tabItem.*')->orderByDesc('tabBin.creation')->first();

        return view('consignment.supervisor.adjust_stocks', compact('item'));
    }

    public function adjustStocks(Request $request)
    {
        $stateBeforeUpdate = [];
        try {
            if (! $request->warehouse) {
                throw new Exception('Please select a warehouse');
            }

            $now = now();
            $branch = $request->warehouse;
            $itemCodes = $request->item_codes;
            $input = $request->item;

            if (! $itemCodes || ! $input) {
                throw new Exception('Please select an Item.');
            }

            $itemDetails = Item::whereIn('name', $itemCodes)
                ->with('bin', function ($bin) use ($branch) {
                    $bin
                        ->where('warehouse', $branch)
                        ->select('name', 'item_code', 'consigned_qty', 'consignment_price', 'modified', 'modified_by');
                })
                ->select('item_code', 'description', 'stock_uom')
                ->get();

            if (! $itemDetails->isNotEmpty()) {
                throw new Exception('No items found.');
            }

            $consignmentItems = $activityLogs = [];
            foreach ($itemDetails as $item) {
                $itemCode = $item->item_code;
                $bin = collect($item->bin)->first();

                if (! $bin) {
                    continue;
                }

                $binId = $bin->name;
                unset($bin->name, $bin->item_code);

                $stateBeforeUpdate['Bin'][$binId] = $bin;

                $newStock = preg_replace('/[^0-9]/', '', $input[$itemCode]['qty'] ?? '');
                $newStock = $newStock ? $newStock * 1 : 0;

                $newPrice = preg_replace('/[^0-9 .]/', '', $input[$itemCode]['price'] ?? '');
                $newPrice = $newPrice ? $newPrice * 1 : 0;

                $update = [];

                if ($bin->consigned_qty != $newStock) {
                    $update['consigned_qty'] = $newStock;
                    $activityLogs[$branch][$itemCode]['quantity'] = [
                        'previous' => $bin->consigned_qty,
                        'new' => $newStock,
                    ];
                }

                if ($bin->consignment_price != $newPrice) {
                    $update['consignment_price'] = $newPrice;
                    $activityLogs[$branch][$itemCode]['price'] = [
                        'previous' => $bin->consignment_price,
                        'new' => $newPrice,
                    ];
                }

                $itemRemarks = $input[$itemCode]['remarks'] ?? null;

                if (! $update) {
                    continue;
                }

                $binResponse = $this->erpPut('Bin', $binId, $update);

                if (! isset($binResponse['data'])) {
                    throw new Exception($binResponse['exception']);
                }

                $consignmentItems[] = [
                    'item_code' => $itemCode,
                    'item_description' => $item->description,
                    'uom' => $item->stock_uom,
                    'previous_qty' => $bin->consigned_qty,
                    'new_qty' => $newStock,
                    'previous_price' => $bin->consignment_price,
                    'new_price' => $newPrice,
                    'remarks' => $itemRemarks,
                ];
            }

            $consignmentData = [
                'warehouse' => $request->warehouse,
                'created_by' => Auth::user()->wh_user,
                'transaction_date' => $now->toDateString(),
                'transaction_time' => $now->toTimeString(),
                'remarks' => $request->notes,
                'items' => $consignmentItems,
            ];

            $consignmentResponse = $this->erpPost('Consignment Stock Adjustment', $consignmentData);

            if (! isset($consignmentResponse['data'])) {
                throw new Exception($consignmentResponse['exception']);
            }

            $consignmentId = $consignmentResponse['data']['name'];

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Adjustment for '.$request->warehouse.' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Adjustment',
                'reference_name' => $consignmentId,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogs, true),
            ]);

            // Send Email Notification to assigned Promodisers
            $images = ItemImages::whereIn('parent', $itemCodes)->get()->groupBy('parent');

            $promodisers = AssignedWarehouses::query()
                ->join('tabWarehouse Users as wu', 'wu.frappe_userid', '=', 'tabAssigned Consignment Warehouse.parent')
                ->where('tabAssigned Consignment Warehouse.warehouse', $branch)
                ->pluck('wu.wh_user');

            $promodisers = collect($promodisers)->map(function ($promodiser) {
                return str_replace('.local', '.com', $promodiser);
            });

            $mailData = [
                'warehouse' => $branch,
                'images' => $images,
                'reference_no' => $consignmentId,
                'created_by' => Auth::user()->wh_user,
                'created_at' => now()->format('M d, Y h:i A'),
                'logs' => $activityLogs,
                'notes' => $request->notes,
            ];

            if ($promodisers->isNotEmpty()) {
                foreach ($promodisers as $promodiser) {
                    try {
                        Mail::send('mail_template.stock_adjustments', $mailData, function ($message) use ($promodiser) {
                            $message->to($promodiser);
                            $message->subject('AthenaERP - Stock Adjustment');
                        });
                    } catch (\Throwable $e) {
                        session()->flash('error', 'An error occured while sending notification email');
                    }
                }
            }

            session()->flash('success', 'Warehouse Stocks Adjusted.');

            return redirect('/beginning_inv_list');
        } catch (\Throwable $e) {
            Log::error('ConsignmentStockAdjustmentController adjustStocks failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->revertChanges($stateBeforeUpdate);

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function submitStockAdjustment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $itemCodes = array_keys($request->item);
            $stocks = $request->item;

            $now = now();

            $beginningInventory = BeginningInventory::find($id);
            if (! $beginningInventory) {
                return redirect()->back()->with('error', 'Record not found or has been deleted.');
            }

            $bin = BeginningInventoryItem::where('parent', $id)->get();
            $bin = collect($bin)->groupBy('item_code');

            $cbiItems = BeginningInventoryItem::where('parent', $id)->get();
            $cbiItems = collect($cbiItems)->groupBy('item_code');

            $beginningInventoryStart = BeginningInventory::orderBy('transaction_date', 'asc')->value('transaction_date');
            $beginningInventoryStartDate = $beginningInventoryStart ? Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

            $totalReceivedQty = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->whereDate('sted.consignment_date_received', '>=', $beginningInventoryStartDate)
                ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                ->whereIn('ste.item_status', ['For Checking', 'Issued'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->whereIn('sted.item_code', $itemCodes)
                ->where('sted.t_warehouse', $beginningInventory->branch_warehouse)
                ->where('sted.consignment_status', 'Received')
                ->selectRaw('sted.item_code, SUM(sted.transfer_qty) as qty')
                ->groupBy('sted.item_code')
                ->get();
            $totalReceivedQty = collect($totalReceivedQty)->groupBy('item_code');

            $activityLogsData = [];
            foreach ($itemCodes as $itemCode) {
                if (isset($stocks[$itemCode]) && isset($cbiItems[$itemCode])) {
                    $previousStock = isset($bin[$itemCode]) ? (float) $bin[$itemCode][0]->opening_stock : 0;
                    $previousPrice = (float) $cbiItems[$itemCode][0]->price;

                    $openingQty = (float) preg_replace('/[^0-9]/', '', $stocks[$itemCode]['qty']);
                    $price = (float) preg_replace('/[^0-9 .]/', '', $stocks[$itemCode]['price']);

                    if ($previousStock == $openingQty && $previousPrice == $price) {
                        continue;
                    }

                    $cbiArray = $cbiStockArray = $cbiPriceArray = [];
                    $binArray = $binStockArray = $binPriceArray = [];
                    $updateArray = [
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                    ];

                    if ($previousStock != $openingQty) {
                        $totalReceived = isset($totalReceivedQty[$itemCode]) ? $totalReceivedQty[$itemCode][0]->qty : 0;

                        $updatedStocks = $openingQty + $totalReceived;
                        $updatedStocks = $updatedStocks > 0 ? $updatedStocks : 0;

                        $binStockArray = array_merge($binStockArray, ['consigned_qty' => $updatedStocks]);
                        $cbiStockArray = ['opening_stock' => $openingQty];

                        $activityLogsData[$itemCode]['previous_qty'] = $previousStock;
                        $activityLogsData[$itemCode]['new_qty'] = $openingQty;
                    }

                    if ($previousPrice != $price) {
                        $binStockArray = array_merge($binStockArray, ['consignment_price' => $price]);
                        $cbiPriceArray = [
                            'price' => $price,
                            'amount' => $price * $openingQty,
                        ];

                        $activityLogsData[$itemCode]['previous_price'] = $previousPrice;
                        $activityLogsData[$itemCode]['new_price'] = $price;
                    }

                    $cbiArray = array_merge($updateArray, $cbiStockArray, $cbiPriceArray);
                    $binArray = array_merge($updateArray, $binStockArray, $binPriceArray);

                    BeginningInventoryItem::where('parent', $id)->where('item_code', $itemCode)->update($cbiArray);
                    Bin::where('warehouse', $beginningInventory->branch_warehouse)->where('item_code', $itemCode)->update($binArray);
                }
            }

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'content' => 'Consignment Activity Log',
                'subject' => 'Stock Adjustment for '.$beginningInventory->branch_warehouse.' has been created by '.Auth::user()->full_name.' at '.$now->toDateTimeString(),
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Adjustment',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogsData, true),
            ]);

            $grandTotal = BeginningInventoryItem::where('parent', $id)->sum('amount');

            BeginningInventory::where('name', $id)->update([
                'modified' => $now,
                'modified_by' => Auth::user()->wh_user,
                'grand_total' => $grandTotal,
                'remarks' => $request->remarks,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Warehouse Stocks Adjusted.');
        } catch (\Throwable $e) {
            Log::error('ConsignmentStockAdjustmentController submitStockAdjustment failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }
}
