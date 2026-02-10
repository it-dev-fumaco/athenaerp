<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\AssignedWarehouses;
use App\Models\Bin;
use App\Models\ConsignmentStockEntry;
use App\Models\ConsignmentStockEntryDetail;
use App\Models\Item;
use App\Models\ItemImages;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Exception;

class ConsignmentStockTransferController extends Controller
{
    use GeneralTrait, ERPTrait;

    public function count($purpose)
    {
        return ConsignmentStockEntry::where('purpose', $purpose)->where('status', 'Pending')->count();
    }

    public function generateStockTransferEntry(Request $request)
    {
        try {
            $id = $request->cste;
            $table = 'Consignment Stock Entry';
            $details = $this->erpGet($table, $id);
            if (!isset($details['data'])) {
                throw new Exception('Record not found.');
            }

            $details = $details['data'];

            if (in_array($details['status'], ['Cancelled', 'Completed'])) {
                throw new Exception('Stock Transfer is ' . $details['status']);
            }

            $sourceWarehouse = $details['source_warehouse'];
            $targetWarehouse = $details['target_warehouse'];
            $items = $details['items'];

            $itemCodes = collect($items)->pluck('item_code');

            $itemDetails = Item::whereIn('item_code', $itemCodes)
                ->with('bin', function ($bin) use ($sourceWarehouse, $targetWarehouse) {
                    $bin->whereIn('warehouse', [$sourceWarehouse, $targetWarehouse]);
                })
                ->get()
                ->groupBy('item_code');

            $validateItems = collect($items)->map(function ($item) use ($itemDetails, $sourceWarehouse) {
                $itemCode = $item['item_code'];

                if (!Arr::exists($itemDetails, $itemCode)) {
                    return "Item $itemCode does not exist in $sourceWarehouse";
                }

                $binDetails = $itemDetails[$itemCode][0]->bin;
                $binDetails = collect($binDetails)->groupBy('warehouse');

                if (!isset($binDetails[$sourceWarehouse])) {
                    return "Item $itemCode does not exist in $sourceWarehouse";
                }

                return null;
            })->unique()->first();

            if ($validateItems) {
                throw new Exception($validateItems);
            }

            $inventoryAmount = collect($items)->sum('amount');

            $now = now();
            $stockEntryDetail = [];
            foreach ($items as $item) {
                $itemCode = $item['item_code'];
                $stockEntryDetail[] = [
                    't_warehouse' => $targetWarehouse,
                    'transfer_qty' => $item['qty'],
                    'expense_account' => 'Cost of Goods Sold - FI',
                    'cost_center' => 'Main - FI',
                    's_warehouse' => $sourceWarehouse,
                    'custom_basic_amount' => $item['amount'],
                    'custom_basic_rate' => $item['price'],
                    'item_code' => $itemCode,
                    'validate_item_code' => $itemCode,
                    'qty' => $item['qty'],
                    'status' => 'Issued',
                    'session_user' => Auth::user()->full_name,
                    'issued_qty' => $item['qty'],
                    'date_modified' => $now->toDateTimeString(),
                    'return_reason' => $item['reason'] ?? null,
                    'remarks' => 'Generated in AthenaERP'
                ];
            }

            $stockEntryData = [
                'docstatus' => 0,
                'naming_series' => 'STEC-',
                'posting_time' => $now->format('H:i:s'),
                'to_warehouse' => $targetWarehouse,
                'from_warehouse' => $sourceWarehouse,
                'company' => 'FUMACO Inc.',
                'total_outgoing_value' => $inventoryAmount,
                'total_amount' => $inventoryAmount,
                'total_incoming_value' => $inventoryAmount,
                'posting_date' => $now->format('Y-m-d'),
                'purpose' => 'Material Transfer',
                'stock_entry_type' => 'Material Transfer',
                'item_status' => 'Issued',
                'transfer_as' => $details['purpose'] == 'Pull Out' ? 'Pull Out Item' : 'Store Transfer',
                'delivery_date' => $now->format('Y-m-d'),
                'remarks' => 'Generated in AthenaERP. ' . ($details['remarks'] ?? ''),
                'order_from' => 'Other Reference',
                'reference_no' => '-',
                'items' => $stockEntryDetail
            ];

            $response = $this->erpPost('Stock Entry', $stockEntryData);

            if (!isset($response['data'])) {
                throw new Exception($response['exception']);
            }

            $response = $response['data'];

            $consignmentResponse = $this->erpPut($table, $details['name'], ['references' => $response['name']]);

            if (!isset($consignmentResponse['data'])) {
                session()->flash('error', $consignmentResponse['exception']);
            }

            $data = [
                'stock_entry_name' => $response['name'],
                'link' => 'http://10.0.0.83/app/stock-entry/' . $response['name']
            ];

            return ApiResponse::success('Stock Entry has been created.', $data);
        } catch (\Throwable $th) {
            return ApiResponse::failure('An error occured. Please contact your system administrator.', 400);
        }
    }

    public function report(Request $request)
    {
        if (!in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) {
            return redirect('/')->with('error', 'Unauthorized');
        }

        if ($request->ajax()) {
            $purpose = $request->purpose;

            $list = ConsignmentStockEntry::with('items')
                ->with('stock_entry', function ($stockEntry) {
                    $stockEntry->select('docstatus', 'name', 'consignment_status', 'consignment_received_by', 'consignment_date_received');
                })
                ->where('purpose', $purpose)
                ->when($request->q, function ($query) use ($request) {
                    return $query->where('name', 'like', "%{$request->q}%");
                })
                ->when($request->source_warehouse, function ($query) use ($request) {
                    return $query->where('source_warehouse', $request->source_warehouse);
                })
                ->when($request->target_warehouse, function ($query) use ($request) {
                    return $query->where('target_warehouse', $request->target_warehouse);
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('status', $request->status);
                })
                ->orderBy('creation', 'desc')
                ->paginate(20);

            $itemCodes = collect($list->items())->flatMap(function ($stockTransfer) {
                return $stockTransfer->items->pluck('item_code');
            })->unique()->values();

            $warehouses = collect($list->items())->pluck($purpose == 'Item Return' ? 'target_warehouse' : 'source_warehouse');

            $flattenItemCodes = $itemCodes->implode("','");

            $binDetails = Bin::with('defaultImage')
                ->whereRaw("item_code in ('$flattenItemCodes')")
                ->whereIn('warehouse', $warehouses)
                ->select('item_code', 'warehouse', 'consigned_qty')
                ->get()
                ->groupBy(['warehouse', 'item_code']);

            $result = collect($list->items())->map(function ($stockTransfer) use ($binDetails, $purpose) {
                $warehouse = $purpose == 'Item Return' ? $stockTransfer->target_warehouse : $stockTransfer->source_warehouse;
                $bin = $binDetails[$warehouse] ?? collect();

                $stockTransfer->submitted_by = ucwords(str_replace('.', ' ', explode('@', $stockTransfer->owner)[0]));

                $stockTransfer->items = collect($stockTransfer->items)->map(function ($item) use ($bin) {
                    $itemCode = $item->item_code;
                    $consignmentDetails = isset($bin[$itemCode][0]) ? $bin[$itemCode][0] : null;

                    if (!$consignmentDetails) {
                        $item->consigned_qty = 0;
                        $item->image = '/icon/no_img.png';
                    } else {
                        $item->consigned_qty = (int) $consignmentDetails->consigned_qty;
                        $item->image = isset($consignmentDetails->defaultImage->image_path)
                            ? '/img/' . $consignmentDetails->defaultImage->image_path
                            : '/icon/no_img.png';
                        if (Storage::disk('public')->exists(explode('.', $item->image)[0] . '.webp')) {
                            $item->image = explode('.', $item->image)[0] . '.webp';
                        }
                    }

                    $item->qty = (int) $item->qty;
                    $item->price = (float) $item->price;
                    $item->amount = (float) $item->amount;

                    return $item;
                });

                return $stockTransfer;
            });

            return view('consignment.supervisor.tbl_stock_transfer', compact('result', 'list', 'purpose'));
        }

        return view('consignment.supervisor.view_stock_transfers');
    }

    public function submit(Request $request)
    {
        try {
            $now = now();

            $itemCodes = array_filter(collect($request->item_code)->unique()->toArray());
            $transferQty = collect($request->item)->map(function ($item) {
                return is_array($item) ? array_map(fn($v) => preg_replace('/[^0-9 .]/', '', (string) $v), $item) : preg_replace('/[^0-9 .]/', '', (string) $item);
            });
            $purpose = $request->transfer_as == 'Pull Out' ? 'Pull Out' : 'Store-to-Store Transfer';

            $itemTransferDetails = $request->item;

            $sourceWarehouse = $request->source_warehouse;
            $targetWarehouse = $request->transfer_as == 'Pull Out' ? 'Quarantine Warehouse - FI' : $request->target_warehouse;

            if (!$itemCodes || !$transferQty->isNotEmpty()) {
                return redirect()->back()->with('error', 'Please select an item to return');
            }

            $hasInvalidQty = $transferQty->contains(function ($item) {
                $qty = is_array($item) ? ($item['transfer_qty'] ?? 0) : $item;
                return (float) $qty <= 0;
            });
            if ($hasInvalidQty) {
                return redirect()->back()->with('error', 'Return Qty cannot be less than or equal to 0');
            }

            $bin = Bin::query()
                ->from('tabBin as bin')
                ->join('tabItem as item', 'item.item_code', 'bin.item_code')
                ->where('bin.warehouse', $sourceWarehouse)
                ->whereIn('bin.item_code', $itemCodes)
                ->select('item.item_code', 'item.description as item_description', 'item.stock_uom as uom', 'bin.consignment_price as price')
                ->get();

            $items = collect($bin)->map(function ($item) use ($transferQty, $itemTransferDetails) {
                $itemCode = $item->item_code;
                $qty = isset($transferQty[$itemCode]['transfer_qty']) ? (float) $transferQty[$itemCode]['transfer_qty'] : 0;
                $item->qty = $qty;
                $item->amount = $qty * $item->price;
                $item->cost_center = 'Main - FI';
                $item->item_description = strip_tags($item->item_description);
                $item->remarks = 'Generated in AthenaERP';
                $item->reason = $itemTransferDetails[$itemCode]['reason'] ?? null;

                return $item;
            });

            $data = [
                'source_warehouse' => $sourceWarehouse,
                'target_warehouse' => $targetWarehouse,
                'purpose' => $request->transfer_as,
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpPost('Consignment Stock Entry', $data);

            if (!isset($response['data'])) {
                throw new Exception($response['exc_type']);
            }

            $user = Auth::user()->full_name;
            $logs = [
                'subject' => "$purpose request from $sourceWarehouse to $targetWarehouse has been created by $user at $now",
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $log = $this->erpPost('Activity Log', $logs);

            if (!isset($log['data'])) {
                session()->flash('warning', 'Activity Log not posted.');
            }

            return redirect()->route('stock_transfers', ['purpose' => $purpose])->with('success', 'Stock transfer request has been submitted.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function form(Request $request)
    {
        $action = $request->action;
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.stock_transfer_form', compact('assignedConsignmentStores', 'action'));
    }

    public function itemReturnForm()
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.item_returns_form', compact('assignedConsignmentStore'));
    }

    public function itemReturnSubmit(Request $request)
    {
        try {
            $items = $request->item;
            $now = now();

            $itemDetails = Item::query()
                ->from('tabItem as p')
                ->join('tabBin as c', 'p.name', 'c.item_code')
                ->where('c.warehouse', $request->target_warehouse)
                ->whereIn('p.name', array_keys($items))
                ->get(['p.name', 'p.description', 'p.stock_uom', 'c.consignment_price', 'c.consigned_qty', 'c.name as bin_id']);

            $steDetails = [];
            $activityLogsDetails = [];
            foreach ($itemDetails as $item) {
                if (!isset($items[$item->name])) {
                    continue;
                }

                $transferDetail = $items[$item->name];
                $itemCode = $item->name;

                $this->erpPut('Bin', $item->bin_id, ['consigned_qty' => (float) $item->consigned_qty + (float) $transferDetail['qty']]);

                $steDetails[] = [
                    'item_code' => $itemCode,
                    'item_description' => $item->description ?? null,
                    'uom' => $item->stock_uom ?? null,
                    'qty' => (float) $transferDetail['qty'],
                    'price' => (float) $item->consignment_price,
                    'amount' => $item->consignment_price * $transferDetail['qty'],
                    'reason' => $transferDetail['reason'] ?? null
                ];

                $activityLogsDetails[$itemCode]['quantity'] = [
                    'previous' => $item->consigned_qty,
                    'new' => $item->consigned_qty + (float) $transferDetail['qty'],
                    'returned' => (float) $transferDetail['qty']
                ];
            }

            $data = [
                'target_warehouse' => $request->target_warehouse,
                'purpose' => 'Item Return',
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'remarks' => $request->remarks,
                'items' => $steDetails
            ];

            $response = $this->erpPost('Consignment Stock Entry', $data);

            if (!isset($response['data'])) {
                throw new Exception($response['exc_type']);
            }

            $logData = [
                'subject' => 'Item Return  to ' . $request->target_warehouse . ' has been created by ' . Auth::user()->full_name . ' at ' . $now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogsDetails, true)
            ];

            $log = $this->erpPost('Activity Log', $logData);

            if (!isset($log['data'])) {
                session()->flash('warning', 'Activity Log not posted');
            }

            return redirect()->back()->with('success', 'Transaction Recorded.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            $now = now();
            $stockEntry = ConsignmentStockEntry::find($id);
            if (!$stockEntry) {
                return redirect()->back()->with('error', 'Record not found.');
            }

            if ($stockEntry->status == 'Completed') {
                return redirect()->back()->with('error', 'Unable to cancel. Request is already COMPLETED.');
            }

            $response = $this->erpPut('Consignment Stock Entry', $stockEntry->name, ['status' => 'Cancelled']);

            if (!isset($response['data'])) {
                throw new Exception($response['exc_type']);
            }

            if ($stockEntry->purpose == 'Item Return') {
                $stockEntryItems = ConsignmentStockEntryDetail::where('parent', $id)->get();

                $items = Bin::where('warehouse', $stockEntry->target_warehouse)
                    ->whereIn('item_code', collect($stockEntryItems)->pluck('item_code'))
                    ->get()
                    ->groupBy('item_code');

                foreach ($stockEntryItems as $item) {
                    if (isset($items[$item->item_code])) {
                        $itemDetails = $items[$item->item_code][0];
                        $this->erpPut('Bin', $itemDetails->name, [
                            'consigned_qty' => $itemDetails->consigned_qty > $item->qty ? $itemDetails->consigned_qty - $item->qty : 0
                        ]);
                    }
                }
            }

            $sourceWarehouse = $stockEntry->source_warehouse;
            $targetWarehouse = $stockEntry->target_warehouse;
            $transaction = $stockEntry->purpose;

            $logs = [
                'subject' => $transaction . ' request from ' . $sourceWarehouse . ' to ' . $targetWarehouse . ' has been cancelled by ' . Auth::user()->full_name . ' at ' . $now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Entry',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $log = $this->erpPost('Activity Log', $logs);

            if (!isset($log['data'])) {
                session()->flash('warning', 'Activity Log not posted');
            }

            return redirect()->route('stock_transfers', ['purpose' => $stockEntry->purpose])->with('success', $transaction . ' has been cancelled.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
        }
    }

    public function list(Request $request)
    {
        $purpose = $request->purpose;

        $consignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        if ($request->ajax()) {
            $refWarehouse = $purpose == 'Item Return' ? 'target_warehouse' : 'source_warehouse';
            $stockTransfers = ConsignmentStockEntry::query()
                ->whereIn($refWarehouse, $consignmentStores)
                ->where('purpose', $purpose)
                ->orderBy('creation', 'desc')
                ->paginate(10);

            $warehouses = collect($stockTransfers->items())->map(function ($item) use ($refWarehouse) {
                return $item->$refWarehouse;
            });

            $referenceSte = collect($stockTransfers->items())->map(function ($item) {
                return $item->name;
            });

            $stockTransferItems = ConsignmentStockEntryDetail::whereIn('parent', $referenceSte)->get();
            $stockTransferItem = collect($stockTransferItems)->groupBy('parent');

            $itemCodes = collect($stockTransferItems)->map(function ($item) {
                return $item->item_code;
            });

            $bin = Bin::whereIn('warehouse', $warehouses)->whereIn('item_code', $itemCodes)->get();
            $binArr = [];
            foreach ($bin as $b) {
                $binArr[$b->warehouse][$b->item_code] = [
                    'consigned_qty' => $b->consigned_qty
                ];
            }

            $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
            $itemImages = collect($itemImages)->map(function ($image) {
                return $this->base64Image("img/$image");
            });

            $noImg = $this->base64Image('/icon/no_img.png');

            $steArr = [];
            foreach ($stockTransfers as $ste) {
                $itemsArr = [];
                if (isset($stockTransferItem[$ste->name])) {
                    foreach ($stockTransferItem[$ste->name] as $item) {
                        $img = Arr::get($itemImages, $item->item_code, $noImg);

                        $itemsArr[] = [
                            'item_code' => $item->item_code,
                            'description' => $item->item_description,
                            'consigned_qty' => $binArr[$ste->$refWarehouse][$item->item_code]['consigned_qty'] ?? 0,
                            'transfer_qty' => $item->qty,
                            'uom' => $item->uom,
                            'image' => $img,
                            'return_reason' => $item->reason
                        ];
                    }
                }

                $steArr[] = [
                    'name' => $ste->name,
                    'title' => $ste->title,
                    'from_warehouse' => $ste->source_warehouse,
                    'to_warehouse' => $ste->target_warehouse,
                    'status' => $ste->status,
                    'items' => $itemsArr,
                    'owner' => ucwords(str_replace('.', ' ', explode('@', $ste->owner)[0])),
                    'docstatus' => $ste->docstatus,
                    'transfer_type' => $ste->purpose,
                    'date' => $ste->creation,
                    'remarks' => $ste->remarks,
                ];
            }

            return view('consignment.stock_transfers_table', compact('stockTransfers', 'steArr', 'purpose'));
        }

        return view('consignment.stock_transfers_list', compact('purpose'));
    }
}
