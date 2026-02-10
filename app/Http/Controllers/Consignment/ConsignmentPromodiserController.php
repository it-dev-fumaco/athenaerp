<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\ActivityLog;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\Bin;
use App\Models\ConsignmentDamagedItems;
use App\Models\ERPUser;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Exception;

class ConsignmentPromodiserController extends Controller
{
    use GeneralTrait, ERPTrait;

    // /promodiser/delivery_report/{type}
    public function promodiserDeliveryReport($type, Request $request)
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $beginningInventoryStart = BeginningInventory::orderBy('transaction_date', 'asc')->pluck('transaction_date')->first();
        $beginningInventoryStartDate = $beginningInventoryStart ? Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

        $deliveryReport = StockEntry::whereHas('items', function ($items) use ($assignedConsignmentStore) {
            $items->whereIn('t_warehouse', $assignedConsignmentStore);
        })
            ->with('items', function ($items) use ($assignedConsignmentStore) {
                $items->with('defaultImage')->whereIn('t_warehouse', $assignedConsignmentStore)->select('name', 'parent', 't_warehouse', 's_warehouse', 'item_code', 'description', 'transfer_qty', 'stock_uom', 'basic_rate', 'consignment_status', 'consignment_date_received', 'consignment_received_by');
            })
            ->whereDate('delivery_date', '>=', $beginningInventoryStartDate)
            ->whereIn('transfer_as', ['Consignment', 'Store Transfer'])
            ->where('purpose', 'Material Transfer')
            ->where('docstatus', 1)
            ->whereIn('item_status', ['For Checking', 'Issued'])
            ->when($type == 'pending_to_receive', function ($query) {
                return $query->where(function ($subQuery) {
                    return $subQuery->whereNull('consignment_status')->orWhere('consignment_status', 'To Receive');
                });
            })
            ->select('name', 'delivery_date', 'item_status', 'from_warehouse', 'to_warehouse', 'creation', 'posting_time', 'consignment_status', 'transfer_as', 'docstatus', 'consignment_date_received', 'consignment_received_by')
            ->orderBy('creation', 'desc')
            ->orderByRaw("FIELD(consignment_status, '', 'Received') ASC")
            ->paginate(10);

        $itemCodes = collect($deliveryReport->items())->flatMap(fn($stockEntry) => $stockEntry->items->pluck('item_code'))->unique()->values();
        $targetWarehouses = collect($deliveryReport->items())->flatMap(fn($stockEntry) => $stockEntry->items->pluck('t_warehouse'))->unique()->values();

        $itemPrices = Bin::whereIn('warehouse', $targetWarehouses)->whereIn('item_code', $itemCodes)->select('warehouse', 'consignment_price', 'item_code')->get()->groupBy(['item_code', 'warehouse']);

        $steArr = collect($deliveryReport->items())->map(function ($stockEntry) use ($itemPrices, $targetWarehouses) {
            $stockEntry->items = collect($stockEntry->items)->map(function ($item) use ($itemPrices) {
                $itemCode = $item->item_code;
                $warehouse = $item->t_warehouse;
                $price = isset($itemPrices[$itemCode][$warehouse]) ? $itemPrices[$itemCode][$warehouse][0]->consignment_price : $item->basic_rate;
                $price = (float) $price;

                $item->transfer_qty = (int) $item->transfer_qty;
                $item->image = isset($item->defaultImage->image_path) ? '/img/' . $item->defaultImage->image_path : '/icon/no_img.png';
                if (Storage::disk('public')->exists(explode('.', $item->image)[0] . '.webp')) {
                    $item->image = explode('.', $item->image)[0] . '.webp';
                }
                $item->price = $price;
                return $item;
            });
            $stockEntry->to_warehouse = collect($targetWarehouses)->first();

            $status = 'Pending';
            if ($stockEntry->item_status == 'Issued' && Carbon::parse($stockEntry->delivery_date)->lt(now())) {
                $status = 'Delivered';
            }
            $stockEntry->status = $status;

            return $stockEntry;
        });

        $blade = $request->ajax() ? 'delivery_report_tbl' : 'promodiser_delivery_report';

        return view('consignment.' . $blade, compact('deliveryReport', 'steArr', 'type'));
    }

    public function promodiserInquireDelivery(Request $request)
    {
        $deliveryReport = [];
        if ($request->ajax()) {
            $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

            $deliveryReport = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->whereIn('ste.item_status', ['For Checking', 'Issued'])
                ->where('ste.name', $request->ste)
                ->where(function ($query) use ($assignedConsignmentStores) {
                    return $query->whereIn('ste.to_warehouse', $assignedConsignmentStores)->orWhereIn('sted.t_warehouse', $assignedConsignmentStores);
                })
                ->select('ste.name', 'ste.delivery_date', 'ste.item_status', 'ste.from_warehouse', 'sted.t_warehouse', 'sted.s_warehouse', 'ste.creation', 'ste.posting_time', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.consignment_status', 'ste.transfer_as', 'ste.docstatus', 'sted.consignment_date_received', 'sted.consignment_received_by')
                ->orderBy('ste.creation', 'desc')
                ->get();

            $itemImages = ItemImages::whereIn('parent', collect($deliveryReport)->pluck('item_code'))->pluck('image_path', 'parent');
            $itemImages = collect($itemImages)->map(fn($image) => $this->base64Image("img/$image"));
            $itemImages['no_img'] = $this->base64Image('icon/no_img.png');

            return view('consignment.promodiser_delivery_inquire_tbl', compact('deliveryReport', 'itemImages'));
        }

        return view('consignment.promodiser_delivery_inquire', compact('deliveryReport'));
    }

    // /promodiser/receive/{id}
    public function promodiserReceiveDelivery(Request $request, $id)
    {
        $stateBeforeUpdate = [];
        try {
            $stockEntry = $this->erpGet('Stock Entry', $id);

            if (!isset($stockEntry['data'])) {
                throw new Exception('Stock Entry not found.');
            }

            $stockEntry = $stockEntry['data'];

            if (isset($stockEntry['consignment_status']) && $stockEntry['consignment_status'] == 'Received') {
                throw new Exception("$id already received.");
            }

            $itemPrices = [];
            foreach ($request->price as $itemCode => $p) {
                $price = preg_replace('/[^0-9 .]/', '', $p);
                $itemPrices[$itemCode] = $price;
                if ($stockEntry['transfer_as'] != 'For Return') {
                    if (!is_numeric($price) || $price <= 0) {
                        throw new Exception('Item prices cannot be less than or equal to 0.');
                    }
                }
            }

            if (!isset($stockEntry['to_warehouse']) && $stockEntry['items'][0]['t_warehouse']) {
                $stockEntry['to_warehouse'] = $stockEntry['items'][0]['t_warehouse'];
            }

            $defaultSourceWarehouse = $stockEntry['from_warehouse'] ?? null;
            $defaultTargetWarehouse = $stockEntry['to_warehouse'] ?? null;

            $steItems = $stockEntry['items'];
            $itemCodes = collect($steItems)->pluck('item_code');
            $sourceWarehouses = collect($steItems)->pluck('s_warehouse')->push($defaultSourceWarehouse)->filter()->unique();
            $targetWarehouses = collect($steItems)->pluck('t_warehouse')->push($request->target_warehouse)->push($defaultTargetWarehouse)->filter()->unique();

            $targetWarehouseDetails = Bin::whereIn('warehouse', $targetWarehouses)
                ->whereIn('item_code', $itemCodes)
                ->select('name', 'warehouse', 'item_code', 'actual_qty', 'consigned_qty', 'consignment_price', 'modified', 'modified_by')
                ->get()
                ->groupBy(['warehouse', 'item_code']);

            $sourceWarehouseDetails = Bin::whereIn('warehouse', $sourceWarehouses)
                ->whereIn('item_code', $itemCodes)
                ->select('name', 'warehouse', 'item_code', 'actual_qty', 'consigned_qty', 'consignment_price', 'modified', 'modified_by')
                ->get()
                ->groupBy(['warehouse', 'item_code']);

            $validateStocks = collect($steItems)->map(function ($item) use ($sourceWarehouseDetails, $targetWarehouseDetails, $request, $defaultSourceWarehouse, $defaultTargetWarehouse) {
                $sourceWarehouse = $item['s_warehouse'] ?? $defaultSourceWarehouse;
                $targetWarehouse = $item['t_warehouse'] ?? ($request->target_warehouse ?? $defaultTargetWarehouse);
                $itemCode = $item['item_code'];
                if (!isset($sourceWarehouseDetails[$sourceWarehouse][$itemCode])) {
                    return "Item $itemCode does not exist in $sourceWarehouse";
                }
                if (!isset($targetWarehouseDetails[$targetWarehouse][$itemCode])) {
                    return "Item $itemCode does not exist in $targetWarehouse";
                }
                return null;
            })->filter();

            if (count($validateStocks) > 0) {
                throw new Exception(collect($validateStocks)->first());
            }

            $now = now();
            $data['details'] = ['reference' => $id, 'transaction_date' => $now->toDateTimeString()];
            $receivedItems = $expectedQtyAfterTransaction = [];

            foreach ($steItems as $item) {
                $itemCode = $item['item_code'];
                $srcBranch = $item['s_warehouse'] ?? $defaultSourceWarehouse;
                $targetBranch = $request->target_warehouse ?? ($item['t_warehouse'] ?? $defaultTargetWarehouse);

                $sourceBinDetails = $sourceWarehouseDetails[$srcBranch][$itemCode][0];
                $sourceConsignedQty = $sourceBinDetails->consigned_qty;
                $sourceBinId = $sourceBinDetails->name;
                $sourceUpdatedConsignedQty = $sourceConsignedQty > $item['transfer_qty'] ? $sourceConsignedQty - $item['transfer_qty'] : 0;

                $stateBeforeUpdate['Bin'][$sourceBinId] = $sourceBinDetails;
                $data[$srcBranch][$itemCode]['quantity'] = ['previous' => $sourceConsignedQty, 'transferred_qty' => $item['transfer_qty'], 'new' => $sourceUpdatedConsignedQty];

                $binResponse = $this->erpPut('Bin', $sourceBinId, ['consigned_qty' => $sourceUpdatedConsignedQty]);
                if (!isset($binResponse['data'])) {
                    throw new Exception('An error occured while updating Bin.');
                }

                $expectedQtyAfterTransaction['source'][$srcBranch][$itemCode] = $sourceUpdatedConsignedQty;

                $targetBinDetails = $targetWarehouseDetails[$targetBranch][$itemCode][0];
                $targetConsignedQty = $targetBinDetails->consigned_qty;
                $targetConsignmentPrice = $targetBinDetails->consignment_price;
                $targetBinId = $targetBinDetails->name;
                $basicRate = $item['basic_rate'];
                if ($stockEntry['transfer_as'] != 'For Return') {
                    $basicRate = $itemPrices[$itemCode] ?? $basicRate;
                }
                $targetUpdatedConsignedQty = $targetConsignedQty + $item['transfer_qty'];

                $updateBin = ['consigned_qty' => $targetUpdatedConsignedQty, 'consignment_price' => $targetConsignmentPrice];
                $data[$targetBranch][$itemCode]['quantity'] = ['previous' => $sourceConsignedQty, 'transferred_qty' => $item['transfer_qty'], 'new' => $sourceUpdatedConsignedQty];
                if (isset($itemPrices[$itemCode])) {
                    $updateBin['consignment_price'] = $basicRate;
                    $data[$targetBranch][$itemCode]['price'] = ['previous' => $targetConsignmentPrice, 'new' => $basicRate];
                }

                $stateBeforeUpdate['Bin'][$targetBinId] = $targetBinDetails;
                $binResponse = $this->erpPut('Bin', $targetBinId, $updateBin);
                if (!isset($binResponse['data'])) {
                    throw new Exception('Bin: ' . ($binResponse['exception'] ?? 'An error occured.'));
                }

                $expectedQtyAfterTransaction['target'][$targetBranch][$itemCode] = $targetUpdatedConsignedQty;

                $steDetailsUpdate = ['status' => 'Issued'];
                if (!isset($item['consignment_status']) || $item['consignment_status'] != 'Received') {
                    $steDetailsUpdate['consignment_status'] = 'Received';
                    $steDetailsUpdate['consignment_date_received'] = $now->toDateTimeString();
                    $steDetailsUpdate['consignment_received_by'] = Auth::user()->wh_user;
                }
                if ($request->target_warehouse) {
                    $steDetailsUpdate['t_warehouse'] = $request->target_warehouse;
                    $steDetailsUpdate['target_warehouse_location'] = $request->target_warehouse;
                }

                $stateBeforeUpdate['Stock Entry Detail'][$item['name']] = $item;
                $stockEntryDetailResponse = $this->erpPut('Stock Entry Detail', $item['name'], $steDetailsUpdate);
                if (!isset($stockEntryDetailResponse['data'])) {
                    throw new Exception('Stock Entry Detail: ' . ($stockEntryDetailResponse['exception'] ?? 'An error occured.'));
                }

                $receivedItems[] = [
                    'item_code' => $itemCode,
                    'qty' => $item['transfer_qty'],
                    'price' => $basicRate,
                    'amount' => $basicRate * $item['transfer_qty']
                ];
            }

            $warehousesArr = collect($sourceWarehouses)->merge($targetWarehouses);
            $actualQtyAfterTransaction = Bin::whereIn('warehouse', $warehousesArr)->whereIn('item_code', $itemCodes)->get(['item_code', 'consigned_qty', 'warehouse'])->groupBy(['warehouse', 'item_code']);

            foreach ($steItems as $item) {
                $itemCode = $item['item_code'];
                $src = $item['s_warehouse'] ?: $stockEntry['from_warehouse'];
                $isConsigned = false;
                if ($src != 'Consignment Warehouse - FI') {
                    $isConsigned = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')->where('is_group', 0)->where('disabled', 0)->where('name', $src)->exists();
                }
                if ($isConsigned) {
                    $expectedQtyInSource = $expectedQtyAfterTransaction['source'][$src][$itemCode] ?? 0;
                    $actualConsignedQtyInSource = $actualQtyAfterTransaction[$src][$itemCode][0]->consigned_qty ?? 0;
                    if ($expectedQtyInSource != $actualConsignedQtyInSource) {
                        throw new Exception("Error: Expected qty of item $itemCode did not match the actual qty in source warehouse");
                    }
                }
                $trg = $item['t_warehouse'] ?: $stockEntry['to_warehouse'];
                if (isset($request->receive_delivery)) {
                    $expectedQtyInTarget = $expectedQtyAfterTransaction['target'][$trg][$itemCode] ?? 0;
                    $actualConsignedQtyInTarget = $actualQtyAfterTransaction[$trg][$itemCode][0]->consigned_qty ?? 0;
                    if ($expectedQtyInTarget != $actualConsignedQtyInTarget) {
                        throw new Exception("Error: Expected qty of $itemCode did not match the actual qty in target warehouse");
                    }
                }
            }

            $sourceWarehouse = $stockEntry['from_warehouse'] ?? collect($sourceWarehouses)->first();
            $targetWarehouse = $request->target_warehouse ?? ($stockEntry['to_warehouse'] ?? collect($targetWarehouses)->first());

            $logs = [
                'subject' => 'Stock Transfer from ' . $sourceWarehouse . ' to ' . $targetWarehouse . ' has been received by ' . Auth::user()->full_name . ' at ' . $now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($data, true)
            ];

            $this->erpPost('Activity Log', $logs);

            $stockEntryResponse = $this->erpPut('Stock Entry', $id, [
                'consignment_status' => 'Received',
                'consignment_date_received' => $now->toDateTimeString(),
                'consignment_received_by' => Auth::user()->wh_user,
            ]);

            if (!isset($stockEntryResponse['data'])) {
                throw new Exception('Stock Entry: ' . ($stockEntryResponse['exception'] ?? 'An error occured.'));
            }

            $message = null;
            if (isset($request->receive_delivery)) {
                $t = $stockEntry['transfer_as'] != 'For Return' ? 'your store inventory!' : (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) ? $targetWarehouse : 'Quarantine Warehouse!');
                $message = collect($receivedItems)->sum('qty') . ' Item(s) is/are successfully received and added to ' . $t;
            }

            $receivedItems['message'] = $message;
            $receivedItems['branch'] = $targetWarehouse;
            $receivedItems['action'] = 'received';

            return ApiResponse::successLegacy($message);
        } catch (\Throwable $e) {
            Log::error('ConsignmentPromodiserController promodiserReceiveDelivery failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->revertChanges($stateBeforeUpdate);
            return ApiResponse::failureLegacy($e->getMessage());
        }
    }

    // /promodiser/cancel/received/{id}
    public function promodiserCancelReceivedDelivery($id)
    {
        DB::beginTransaction();
        try {
            $stockEntry = StockEntry::find($id);
            $receivedItems = StockEntryDetail::where('parent', $id)->get();

            $itemCodes = collect($receivedItems)->pluck('item_code');
            $targetWarehouses = collect($receivedItems)->pluck('t_warehouse')->unique()->toArray();
            $sourceWarehouses = collect($receivedItems)->pluck('s_warehouse')->unique()->toArray();
            $stWarehouses = [$stockEntry->from_warehouse, $stockEntry->to_warehouse];
            $branches = array_merge($targetWarehouses, $sourceWarehouses, $stWarehouses);

            $binConsignedQty = Bin::whereIn('item_code', $itemCodes)->whereIn('warehouse', $branches)->select('warehouse', 'item_code', 'consigned_qty')->get();

            $consignedQty = [];
            foreach ($binConsignedQty as $bin) {
                $consignedQty[$bin->warehouse][$bin->item_code] = ['consigned_qty' => $bin->consigned_qty];
            }

            $cancelledArr = [];
            foreach ($receivedItems as $item) {
                $branch = $stockEntry->to_warehouse ?: $item->t_warehouse;
                if ($item->consignment_status != 'Received') {
                    return redirect()->back()->with('error', $id . ' is not yet received.');
                }
                if (!isset($consignedQty[$branch][$item->item_code])) {
                    return redirect()->back()->with('error', 'Item not found.');
                }
                if ($consignedQty[$branch][$item->item_code]['consigned_qty'] < $item->transfer_qty) {
                    return redirect()->back()->with('error', 'Cannot cancel received items.<br/> Available qty is ' . number_format($consignedQty[$branch][$item->item_code]['consigned_qty']) . ', received qty is ' . number_format($item->transfer_qty));
                }

                if ($stockEntry->transfer_as == 'Store Transfer') {
                    $srcBranch = $stockEntry->from_warehouse ?: $item->s_warehouse;
                    Bin::where('item_code', $item->item_code)->where('warehouse', $srcBranch)->update([
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => $consignedQty[$srcBranch][$item->item_code]['consigned_qty'] + $item->transfer_qty
                    ]);
                }

                Bin::where('item_code', $item->item_code)->where('warehouse', $branch)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $consignedQty[$branch][$item->item_code]['consigned_qty'] - $item->transfer_qty
                ]);

                StockEntryDetail::where('parent', $id)->where('item_code', $item->item_code)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consignment_status' => null,
                    'consignment_date_received' => null
                ]);

                $cancelledArr[] = [
                    'item_code' => $item->item_code,
                    'qty' => $item->transfer_qty,
                    'price' => $item->basic_rate,
                    'amount' => $item->basic_rate * $item->transfer_qty
                ];
            }

            $sourceWarehouse = $stockEntry->from_warehouse ?: ($receivedItems[0]->s_warehouse ?? null);
            $targetWarehouse = $stockEntry->to_warehouse ?: ($receivedItems[0]->t_warehouse ?? null);

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Transfer from ' . $sourceWarehouse . ' to ' . $targetWarehouse . ' has been cancelled by ' . Auth::user()->full_name . ' at ' . now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ]);

            $cancelledArr['message'] = 'Stock transfer cancelled.';
            $cancelledArr['branch'] = $targetWarehouse;
            $cancelledArr['action'] = 'canceled';

            DB::commit();
            return redirect()->back()->with('success', $cancelledArr);
        } catch (Exception $e) {
            Log::error('ConsignmentPromodiserController promodiserCancelReceivedDelivery failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            return redirect()->back()->with('error', 'An error occured. Please try again later');
        }
    }

    // /consignment/pending_to_receive - redirect to view deliveries (fixes missing method)
    public function pendingToReceive(Request $request)
    {
        return redirect('/view_consignment_deliveries' . ($request->query() ? '?' . http_build_query($request->query()) : ''));
    }

    // /view_consignment_deliveries
    public function viewDeliveries(Request $request)
    {
        if ($request->ajax()) {
            $list = StockEntry::with('mreq')
                ->with('items', function ($items) {
                    $items->with('defaultImage')->select('name', 'parent', 't_warehouse', 'item_code', 'description', 'qty', 'transfer_qty', 'basic_rate', 'basic_amount');
                })
                ->whereDate('delivery_date', '>=', '2022-06-25')
                ->whereIn('transfer_as', ['Consignment', 'Store Transfer'])
                ->where('purpose', 'Material Transfer')
                ->where('docstatus', 1)
                ->when($request->status == 'Received', fn($q) => $q->where('consignment_status', 'Received'))
                ->when($request->status == 'To Receive', fn($q) => $q->where(fn($sq) => $sq->whereNull('consignment_status')->orWhere('consignment_status', 'To Receive')))
                ->when($request->store, fn($q) => $q->where('to_warehouse', $request->store))
                ->orderByRaw("FIELD(consignment_status, '', 'To Receive', 'Received') ASC")
                ->orderByDesc('creation')
                ->paginate(20);

            $itemCodes = collect($list->items())->flatMap(fn($items) => $items->items->pluck('item_code'))->unique()->values();
            $targetWarehouses = collect($list->items())->flatMap(fn($items) => $items->items->pluck('t_warehouse'))->unique()->values();

            $itemDetails = Bin::with('item')
                ->whereIn('item_code', $itemCodes)
                ->whereIn('warehouse', $targetWarehouses)
                ->select('name', 'item_code', 'warehouse', 'consignment_price')
                ->get()
                ->groupBy(['warehouse', 'item_code']);

            $result = collect($list->items())->map(function ($stockEntry) use ($itemDetails) {
                $stockEntry->items = collect($stockEntry->items)->map(function ($item) use ($itemDetails) {
                    $item->image = $item->defaultImage ? '/img/' . $item->defaultImage->image_path : 'icon/no_img.png';
                    if (Storage::disk('public')->exists('/img/' . explode('.', $item->image)[0] . '.webp')) {
                        $item->image = explode('.', $item->image)[0] . '.webp';
                    }
                    $item->price = isset($itemDetails[$item->t_warehouse][$item->item_code]) ? (float) $itemDetails[$item->t_warehouse][$item->item_code][0]->consignment_price : 0;
                    $item->amount = $item->price * $item->qty;
                    return $item;
                });
                $stockEntry->created_by = optional($stockEntry->mreq)->owner ? ucwords(str_replace('.', ' ', explode('@', $stockEntry->mreq->owner)[0])) : null;
                return $stockEntry;
            });

            return view('consignment.supervisor.view_pending_to_receive', compact('list', 'result'));
        }

        return view('consignment.supervisor.view_deliveries');
    }

    public function promodiserDamageForm()
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.promodiser_damage_report_form', compact('assignedConsignmentStore'));
    }

    // /promodiser/damage_report/submit
    public function submitDamagedItem(Request $request)
    {
        $stateBeforeUpdate = [];
        try {
            $itemCodes = $request->item_code;
            $damagedQty = preg_replace('/[^0-9 .]/', '', $request->damaged_qty);
            $reason = $request->reason;
            $branch = $request->branch;

            $now = now();

            if (collect($damagedQty)->min() <= 0) {
                throw new Exception('Items cannot be less than or equal to zero.');
            }

            $items = Item::whereIn('item_code', $itemCodes)
                ->with('bin', fn($q) => $q->where('warehouse', $branch)->select('item_code', 'consigned_qty', 'stock_uom', 'warehouse'))
                ->select('item_code', 'description', 'stock_uom')
                ->get();

            $validateItems = collect($items)->map(function ($item) use ($itemCodes, $damagedQty, $branch) {
                $binDetails = collect($item->bin)->first();
                $itemCode = $item->item_code;
                $consignedQty = $binDetails->consigned_qty;

                if (!in_array($itemCode, $itemCodes)) {
                    return "Item $itemCode not found on $branch";
                }
                if (isset($damagedQty[$itemCode]) && $damagedQty[$itemCode] > $consignedQty) {
                    return "Damaged qty of Item $itemCode is more than its available qty on $branch";
                }
                return null;
            })->filter()->first();

            if ($validateItems) {
                throw new Exception($validateItems);
            }

            $items = collect($items)->groupBy('item_code');
            $user = Auth::user()->full_name;

            foreach ($itemCodes as $itemCode) {
                $itemDetails = $items[$itemCode][0];
                $qty = $damagedQty[$itemCode] ?? 0;
                $uom = $itemDetails->stock_uom;

                $data = [
                    'transaction_date' => $now->toDateTimeString(),
                    'branch_warehouse' => $branch,
                    'item_code' => $itemCode,
                    'description' => $itemDetails->description,
                    'qty' => $qty,
                    'stock_uom' => $uom,
                    'damage_description' => $reason[$itemCode] ?? 0,
                    'promodiser' => Auth::user()->name
                ];

                $response = $this->erpPost('Consignment Damaged Item', $data);

                if (!isset($response['data'])) {
                    throw new Exception($response['exception'] ?? 'An error occured.');
                }

                $activityLogData[] = $data;
                $stateBeforeUpdate['Consignment Damaged Item'][$response['data']['name']] = 'delete';
            }

            $logs = [
                'subject' => "Damaged Item Report from $branch has been created by $user at $now",
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Damaged Item',
                'reference_name' => 'Consignment Damaged Item',
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => $user,
                'data' => json_encode($activityLogData ?? [], true)
            ];

            $this->erpPost('Activity Log', $logs);

            return redirect()->back()->with('success', 'Damage report submitted.');
        } catch (Exception $e) {
            Log::error('ConsignmentPromodiserController submitDamagedItem failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->revertChanges($stateBeforeUpdate);

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /damage_report/list
    public function damagedItems()
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $damagedItems = ConsignmentDamagedItems::whereIn('branch_warehouse', $assignedConsignmentStore)->orderBy('creation', 'desc')->paginate(10);

        $itemCodes = collect($damagedItems->items())->pluck('item_code');
        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(fn($image) => $this->base64Image("img/$image"));
        $noImg = $this->base64Image('/icon/no_img.png');

        $damagedArr = [];
        foreach ($damagedItems as $item) {
            $img = Arr::get($itemImages, $item->item_code, $noImg);
            $damagedArr[] = [
                'name' => $item->name,
                'item_code' => $item->item_code,
                'item_description' => $item->description,
                'damaged_qty' => $item->qty,
                'uom' => $item->stock_uom,
                'damage_description' => $item->damage_description,
                'promodiser' => $item->promodiser,
                'creation' => $item->creation,
                'store' => $item->branch_warehouse,
                'image' => $img,
                'status' => $item->status
            ];
        }

        return view('consignment.promodiser_damaged_list', compact('damagedArr', 'damagedItems'));
    }

    // /damaged_items_list - supervisor view
    public function viewDamagedItemsList(Request $request)
    {
        $list = ConsignmentDamagedItems::query()
            ->when($request->search, fn($q) => $q->where('item_code', 'like', '%' . $request->search . '%')->orWhere('description', 'like', '%' . $request->search . '%'))
            ->when($request->store, fn($q) => $q->where('branch_warehouse', $request->store))
            ->orderBy('creation', 'desc')
            ->paginate(20);

        $itemCodes = collect($list->items())->pluck('item_code');
        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(fn($image) => $this->base64Image("img/$image"));
        $noImg = $this->base64Image('icon/no_img.png');

        $result = [];
        foreach ($list as $item) {
            $img = Arr::get($itemImages, $item->item_code, $noImg);
            $result[] = [
                'item_code' => $item->item_code,
                'description' => $item->description,
                'damaged_qty' => ($item->qty * 1),
                'uom' => $item->stock_uom,
                'store' => $item->branch_warehouse,
                'damage_description' => $item->damage_description,
                'promodiser' => $item->promodiser,
                'image' => $img,
                'image_slug' => Str::slug(explode('.', $item->description)[0], '-'),
                'item_status' => $item->status,
                'creation' => Carbon::parse($item->creation)->format('M d, Y - h:i A'),
            ];
        }

        return view('consignment.supervisor.tbl_damaged_items', compact('result', 'list'));
    }

    // /damaged/return/{id}
    public function returnDamagedItem($id)
    {
        DB::beginTransaction();
        try {
            $damagedItem = ConsignmentDamagedItems::find($id);
            $existingSource = Bin::where('warehouse', $damagedItem->branch_warehouse)->where('item_code', $damagedItem->item_code)->first();

            if (!$damagedItem || !$existingSource) {
                return redirect()->back()->with('error', 'Item not found.');
            }

            if ($damagedItem->status == 'Returned') {
                return redirect()->back()->with('error', 'Item is already returned.');
            }

            $existingTarget = Bin::where('warehouse', 'Quarantine Warehouse - FI')->where('item_code', $damagedItem->item_code)->first();
            if ($existingTarget) {
                Bin::where('name', $existingTarget->name)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $existingTarget->consigned_qty + $damagedItem->qty
                ]);
            } else {
                $latestBin = Bin::where('name', 'like', '%bin/%')->max('name');
                $latestBinExploded = explode('/', $latestBin);
                $binId = str_pad((($latestBin ? $latestBinExploded[1] : 0) + 1), 7, '0', STR_PAD_LEFT);
                $binId = 'BIN/' . $binId;

                Bin::insert([
                    'name' => $binId,
                    'creation' => now()->toDateTimeString(),
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 0,
                    'idx' => 0,
                    'warehouse' => 'Quarantine Warehouse - FI',
                    'item_code' => $damagedItem->item_code,
                    'stock_uom' => $damagedItem->stock_uom,
                    'valuation_rate' => $existingSource->consignment_price,
                    'consigned_qty' => $damagedItem->qty,
                    'consignment_price' => $existingSource->consignment_price
                ]);
            }

            Bin::where('name', $existingSource->name)->update([
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'consigned_qty' => $existingSource->consigned_qty - $damagedItem->qty
            ]);

            ConsignmentDamagedItems::where('name', $id)->update([
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Returned'
            ]);

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Damaged Item Report for ' . number_format($damagedItem->qty) . ' ' . $damagedItem->stock_uom . ' of ' . $damagedItem->item_code . ' from ' . $damagedItem->branch_warehouse . ' has been returned to Quarantine Warehouse - FI by ' . Auth::user()->full_name . ' at ' . now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Damaged Items',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Item Returned.');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function getConsignmentWarehouses(Request $request)
    {
        $searchStr = $request->q ? explode(' ', $request->q) : [];

        $warehouses = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->where('docstatus', '<', 2)
            ->when($request->q, function ($query) use ($request, $searchStr) {
                return $query->where(function ($subQuery) use ($searchStr, $request) {
                    foreach ($searchStr as $str) {
                        $subQuery->where('name', 'LIKE', '%' . $str . '%');
                    }
                    $subQuery->orWhere('name', 'LIKE', '%' . $request->q . '%');
                });
            })
            ->select('name as id', 'name as text')
            ->get();

        return response()->json($warehouses);
    }

    public function viewSalesReport()
    {
        $selectYear = [];
        for ($i = 2022; $i <= date('Y'); $i++) {
            $selectYear[] = $i;
        }

        return view('consignment.supervisor.view_product_sold_list', compact('selectYear'));
    }

    // /get_activity_logs
    public function activityLogs(Request $request)
    {
        $dates = $request->date ? explode(' to ', $request->date) : [];

        $logs = ActivityLog::where('content', 'Consignment Activity Log')
            ->when($request->warehouse, fn($q) => $q->where('subject', 'like', "%$request->warehouse%"))
            ->when($dates, fn($q) => $q->whereBetween('creation', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]))
            ->when($request->user, fn($q) => $q->where('full_name', $request->user))
            ->select('creation', 'subject', 'reference_name', 'full_name')
            ->orderBy('creation', 'desc')
            ->paginate(20);

        return view('consignment.supervisor.tbl_activity_logs', compact('logs'));
    }

    // /view_promodisers
    public function viewPromodisersList()
    {
        if (!in_array(Auth::user()->user_group, ['Director', 'Consignment Supervisor'])) {
            return redirect('/');
        }

        $userDetails = ERPUser::where('enabled', 1)
            ->whereHas('whUser', fn($user) => $user->where('user_group', 'Promodiser'))
            ->with('social', fn($user) => $user->select('parent', 'userid'))
            ->with('whUser', function ($user) {
                $user
                    ->select('wh_user', 'name', 'frappe_userid', 'full_name', 'frappe_userid', 'enabled')
                    ->with('assignedWarehouses', fn($warehouse) => $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name'));
            })
            ->select('name', 'full_name')
            ->get();

        $totalPromodisers = count($userDetails);

        $result = collect($userDetails)->map(function ($user) {
            $loginStatus = Cache::has('user-is-online-' . $user->name)
                ? '<span class="text-success font-weight-bold">ONLINE NOW</span>'
                : ($user->last_login ? Carbon::parse($user->last_login)->format('F d, Y h:i A') : null);

            return [
                'id' => $user->name,
                'promodiser_name' => $user->full_name,
                'stores' => collect($user->whUser->assignedWarehouses)->pluck('warehouse'),
                'login_status' => $loginStatus,
                'enabled' => $user->whUser->enabled
            ];
        });

        $storesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->select('branch_warehouse', DB::raw('MIN(transaction_date) as transaction_date'))
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        return view('consignment.supervisor.view_promodisers_list', compact('result', 'totalPromodisers', 'storesWithBeginningInventory'));
    }

    public function addPromodiserForm()
    {
        $consignmentStores = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->where('is_group', 0)
            ->where('disabled', 0)
            ->orderBy('warehouse_name', 'asc')
            ->pluck('name');

        $notIncluded = User::whereIn('user_group', ['Promodiser', 'Consignment Supervisor', 'Director'])->pluck('wh_user');
        $notIncluded = collect($notIncluded)->push('Administrator')->push('Guest')->all();

        $users = User::query()
            ->from('tabUser as u')
            ->join('tabUser Social Login as s', 'u.name', 's.parent')
            ->whereNotIn('u.name', $notIncluded)
            ->where('enabled', 1)
            ->select('u.name', 'u.full_name')
            ->get();

        return view('consignment.supervisor.add_promodiser', compact('consignmentStores', 'users'));
    }

    public function addPromodiser(Request $request)
    {
        try {
            $user = $request->user;
            $warehouses = $request->warehouses;

            $userDetails = ERPUser::where('name', $user)
                ->where('enabled', 1)
                ->with('social', fn($u) => $u->select('parent', 'userid'))
                ->with('whUser', function ($u) {
                    $u
                        ->select('wh_user', 'name', 'frappe_userid', 'user_group', 'modified', 'modified_by', 'price_list')
                        ->with('assignedWarehouses', fn($w) => $w->select('parent', 'name', 'warehouse', 'warehouse_name'));
                })
                ->select('name', 'full_name')
                ->first();

            if (!$userDetails) {
                return redirect()->back()->with('error', 'User not found.');
            }

            $frappeUserid = $userDetails->social->userid;
            $whUser = $userDetails->whUser;

            $data = [
                'user_group' => 'Promodiser',
                'price_list' => 'Consignment Price',
                'wh_user' => $userDetails->name,
                'full_name' => $userDetails->full_name,
                'frappe_userid' => $frappeUserid
            ];

            $method = 'post';
            $reference = null;
            if ($whUser) {
                $method = 'put';
                $reference = $whUser->name;
                $frappeUserid = $whUser->name;
                unset($data['frappe_userid']);
                AssignedWarehouses::where('parent', $frappeUserid)->delete();
            }

            $warehouseDetails = Warehouse::whereIn('name', $warehouses)->select('name as warehouse', 'warehouse_name')->get();
            $data['consignment_store'] = collect($warehouseDetails)->toArray();
            $data['warehouse'] = collect($warehouseDetails)->toArray();

            $response = $this->erpCall($method, 'Warehouse Users', $reference, $data);

            if (!isset($response['data'])) {
                throw new Exception($response['exception'] ?? 'An error occured.');
            }
            $this->erpPut('Warehouse Users', $response['data']['name'], ['frappe_userid' => $response['data']['name']]);

            return redirect('/view_promodisers')->with('success', 'Promodiser Added.');
        } catch (\Throwable $e) {
            Log::error('ConsignmentPromodiserController addPromodiser failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function editPromodiserForm($id)
    {
        $userDetails = ERPUser::where('name', $id)
            ->where('enabled', 1)
            ->with('whUser', function ($user) {
                $user
                    ->select('wh_user', 'name', 'frappe_userid', 'enabled')
                    ->with('assignedWarehouses', fn($w) => $w->select('parent', 'name', 'warehouse', 'warehouse_name'));
            })
            ->select('name', 'full_name')
            ->first();

        if (!$userDetails) {
            return redirect()->back()->with('error', 'User not found');
        }

        $assignedWarehouses = collect($userDetails->whUser->assignedWarehouses)->pluck('warehouse');

        $consignmentStores = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->where('is_group', 0)
            ->where('disabled', 0)
            ->orderBy('warehouse_name', 'asc')
            ->pluck('name');

        return view('consignment.supervisor.edit_promodiser', compact('assignedWarehouses', 'userDetails', 'consignmentStores', 'id'));
    }

    public function editPromodiser($id, Request $request)
    {
        try {
            $userDetails = ERPUser::where('name', $id)
                ->where('enabled', 1)
                ->with('whUser', function ($user) {
                    $user
                        ->select('wh_user', 'name', 'frappe_userid', 'user_group', 'modified', 'modified_by', 'price_list')
                        ->with('assignedWarehouses', fn($w) => $w->select('parent', 'name', 'warehouse', 'warehouse_name'));
                })
                ->select('name', 'full_name')
                ->first();

            if (!$userDetails) {
                throw new Exception('User not found');
            }

            $frappeUserid = $userDetails->whUser->name;
            $assignedWarehouses = collect($userDetails->whUser->assignedWarehouses)->pluck('warehouse')->toArray();
            $warehousesEntry = $request->warehouses ?? [];

            $a = array_diff($assignedWarehouses, $warehousesEntry);
            $b = array_diff($warehousesEntry, $assignedWarehouses);
            $warehouses = [];
            if (count($a) > 0 || count($b) > 0) {
                AssignedWarehouses::where('parent', $frappeUserid)->delete();
                $warehouses = Warehouse::whereIn('name', $warehousesEntry)->select('name as warehouse', 'warehouse_name')->get();
            }

            $data = ['enabled' => isset($request->enabled) ? 1 : 0];
            if ($warehouses) {
                $data['consignment_store'] = $warehouses;
            }

            $response = $this->erpPut('Warehouse Users', $frappeUserid, $data);

            if (!isset($response['data'])) {
                throw new Exception(data_get($response, 'exception', 'An error occured while updating user.'));
            }

            if ($request->ajax()) {
                return ['success' => 1, 'message' => 'Promodiser details updated.'];
            }
            return redirect('/view_promodisers')->with('success', 'Promodiser details updated.');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return ['success' => 0, 'message' => 'An error occured. Please contact your system administrator.', 500];
            }
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function getAuditDeliveries(Request $request)
    {
        $store = $request->store;
        $cutoff = $request->cutoff;
        $cutoffStart = $cutoffEnd = null;
        if ($cutoff) {
            $cutoff = explode('/', $request->cutoff);
            $cutoffStart = $cutoff[0];
            $cutoffEnd = $cutoff[1];
        }

        $list = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->whereBetween('ste.delivery_date', [$cutoffStart, $cutoffEnd])
            ->where('sted.t_warehouse', $store)
            ->select('ste.name', 'ste.delivery_date', 'sted.s_warehouse', 'sted.t_warehouse', 'ste.creation', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.basic_amount', 'ste.owner')
            ->orderBy('ste.creation', 'desc')
            ->get();

        return view('consignment.supervisor.tbl_audit_deliveries', compact('list'));
    }

    public function getAuditReturns(Request $request)
    {
        $store = $request->store;
        $cutoff = $request->cutoff;
        $cutoffStart = $cutoffEnd = null;
        if ($cutoff) {
            $cutoff = explode('/', $request->cutoff);
            $cutoffStart = $cutoff[0];
            $cutoffEnd = $cutoff[1];
        }

        $list = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('ste.delivery_date', [$cutoffStart, $cutoffEnd])
            ->where('sted.t_warehouse', $store)
            ->where(fn($q) => $q->whereIn('ste.transfer_as', ['For Return', 'Store Transfer'])->orWhereIn('ste.receive_as', ['Sales Return']))
            ->whereIn('ste.purpose', ['Material Transfer', 'Material Receipt'])
            ->where('ste.docstatus', 1)
            ->select('ste.name', 'ste.delivery_date', 'sted.s_warehouse', 'sted.t_warehouse', 'ste.creation', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.basic_amount', 'ste.owner')
            ->orderBy('ste.creation', 'desc')
            ->get();

        return view('consignment.supervisor.tbl_audit_returns', compact('list'));
    }
}
