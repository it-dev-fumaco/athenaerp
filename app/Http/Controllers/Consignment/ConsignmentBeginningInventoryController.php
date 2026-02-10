<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\ActivityLog;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\BeginningInventoryItem;
use App\Models\Bin;
use App\Models\Item;
use App\Models\ItemImages;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsignmentBeginningInventoryController extends Controller
{
    use GeneralTrait, ERPTrait;

    // /validate_beginning_inventory
    public function checkBeginningInventory(Request $request)
    {
        $existingInventory = BeginningInventory::query()
            ->where('branch_warehouse', $request->branch_warehouse)
            ->whereDate('transaction_date', '<=', Carbon::parse($request->date))
            ->where('status', 'Approved')
            ->exists();

        if (!$existingInventory) {
            return ApiResponse::failure('No beginning inventory entry found on <br>' . Carbon::parse($request->date)->format('F d, Y'));
        }

        return ApiResponse::success('Beginning inventory found.');
    }

    // /beginning_inv_list
    public function beginningInventoryApproval(Request $request)
    {
        $fromDate = $request->date ? Carbon::parse(explode(' to ', $request->date)[0])->startOfDay() : null;
        $toDate = $request->date ? Carbon::parse(explode(' to ', $request->date)[1])->endOfDay() : null;

        $consignmentStores = [];
        $status = $request->status ? $request->status : 'All';
        if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) {
            $status = $request->status ?? 'For Approval';

            $beginningInventory = BeginningInventory::with('items')
                ->when($request->search, function ($query) use ($request) {
                    return $query
                        ->where('name', 'LIKE', "%$request->search%")
                        ->orWhere('owner', 'LIKE', "%$request->search%");
                })
                ->when($request->date, function ($query) use ($fromDate, $toDate) {
                    return $query->whereBetween('transaction_date', [$fromDate, $toDate]);
                })
                ->when($request->store, function ($query) use ($request) {
                    return $query->where('branch_warehouse', $request->store);
                })
                ->when($status != 'All', function ($query) use ($status) {
                    return $query->where('status', $status);
                })
                ->orderBy('creation', 'desc')
                ->paginate(10);
        } else {
            $consignmentStores = AssignedWarehouses::query()
                ->when(Auth::user()->frappe_userid, function ($query) {
                    return $query->where('parent', Auth::user()->frappe_userid);
                })
                ->pluck('warehouse');
            $consignmentStores = collect($consignmentStores)->unique();

            $beginningInventory = BeginningInventory::with('items')
                ->when($request->search, function ($query) use ($request) {
                    return $query
                        ->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('owner', 'LIKE', '%' . $request->search . '%');
                })
                ->when($request->date, function ($query) use ($fromDate, $toDate) {
                    return $query->whereDate('transaction_date', '>=', $fromDate)->whereDate('transaction_date', '<=', $toDate);
                })
                ->when(Auth::user()->user_group == 'Promodiser', function ($query) use ($consignmentStores) {
                    return $query->whereIn('branch_warehouse', $consignmentStores);
                })
                ->when($request->store, function ($query) use ($request) {
                    return $query->where('branch_warehouse', $request->store);
                })
                ->orderBy('creation', 'desc')
                ->paginate(10);
        }

        $itemCodes = collect($beginningInventory->items())->flatMap(function ($stockTransfer) {
            return $stockTransfer->items->pluck('item_code');
        })->unique()->values();

        $warehouses = collect($beginningInventory->items())->pluck('branch_warehouse');

        $flattenItemCodes = $itemCodes->implode("','");

        $binDetails = Bin::with('defaultImage')
            ->whereRaw("item_code in ('$flattenItemCodes')")
            ->whereIn('warehouse', $warehouses)
            ->select('item_code', 'warehouse', 'consignment_price')
            ->get()
            ->groupBy(['warehouse', 'item_code']);

        $invArr = collect($beginningInventory->items())->map(function ($inventory) use ($binDetails) {
            $bin = $binDetails[$inventory->branch_warehouse] ?? collect();

            $inventory->owner = ucwords(str_replace('.', ' ', explode('@', $inventory->owner)[0]));
            $inventory->transaction_date = Carbon::parse($inventory->transaction_date)->format('M. d, Y');

            $inventory->qty = collect($inventory->items)->sum('opening_stock');
            $inventory->amount = collect($inventory->items)->sum('amount');
            $inventory->items = collect($inventory->items)->map(function ($item) use ($bin) {
                $itemCode = $item->item_code;

                $item->image = '/icon/no_img.png';
                $price = 0;

                $item->opening_stock = (int) $item->opening_stock;
                $item->amount = (float) $item->amount;

                if (isset($bin[$itemCode][0])) {
                    $consignmentDetails = $bin[$itemCode][0];
                    $price = $item->status == 'For Approval' ? $item->price : $consignmentDetails->consignment_price;

                    $item->image = isset($consignmentDetails->defaultImage->image_path) ? '/img/' . $consignmentDetails->defaultImage->image_path : '/icon/no_img.png';
                    if (Storage::disk('public')->exists(explode('.', $item->image)[0] . '.webp')) {
                        $item->image = explode('.', $item->image)[0] . '.webp';
                    }
                }

                return $item;
            });

            return $inventory;
        });

        $lastRecord = collect($beginningInventory->items()) ? collect($beginningInventory->items())->sortByDesc('creation')->last() : [];
        $earliestDate = $lastRecord ? Carbon::parse($lastRecord->creation)->format('Y-M-d') : now()->format('Y-M-d');

        $activityLogsUsers = ActivityLog::where('content', 'Consignment Activity Log')->distinct()->pluck('full_name');

        if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) {
            return view('consignment.supervisor.view_stock_adjustments', compact('consignmentStores', 'invArr', 'beginningInventory', 'activityLogsUsers'));
        }

        if ($request->ajax()) {
            return view('consignment.partials.beginning_inventory_table', compact('invArr', 'beginningInventory'));
        }

        return view('consignment.beginning_inventory_list', compact('consignmentStores', 'invArr', 'beginningInventory', 'earliestDate'));
    }

    // /approve_beginning_inv/{id}
    public function approveBeginningInventory(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $branch = BeginningInventory::where('name', $id)->value('branch_warehouse');
            $prices = $request->price;
            $qty = $request->qty;

            $itemCodes = array_keys($prices);

            if (count($itemCodes) <= 0) {
                return redirect()->back()->with('error', 'Please Enter an Item');
            }

            if (!$branch) {
                return redirect()->back()->with('error', 'Inventory record not found.');
            }

            $now = now()->toDateTimeString();

            $updateValues = [
                'modified_by' => Auth::user()->wh_user,
                'modified' => $now
            ];

            if ($request->has('status') && in_array($request->status, ['Approved', 'Cancelled'])) {
                $updateValues['status'] = $request->status;
            }

            if ($request->status == 'Approved' || !$request->has('status')) {
                BeginningInventoryItem::where('parent', $id)->whereNotIn('item_code', $itemCodes)->delete();

                $items = BeginningInventoryItem::where('parent', $id)->get();
                $items = collect($items)->groupBy('item_code');

                $itemDetails = Item::whereIn('name', $itemCodes)->select('name', 'description', 'stock_uom')->get();
                $itemDetails = collect($itemDetails)->groupBy('name');

                $bin = Bin::where('warehouse', $branch)->whereIn('item_code', $itemCodes)->get();
                $binItems = collect($bin)->groupBy('item_code');

                $skippedItems = [];
                foreach ($itemCodes as $i => $itemCode) {
                    if (isset($items[$itemCode]) && $items[$itemCode][0]->status != 'For Approval') {
                        $skippedItems = collect($skippedItems)->merge($itemCode)->toArray();
                        continue;
                    }

                    $price = isset($prices[$itemCode]) ? preg_replace('/[^0-9 .]/', '', $prices[$itemCode][0]) * 1 : 0;
                    if (!$price) {
                        return redirect()->back()->with('error', 'Item price cannot be empty');
                    }

                    if ($request->has('status') && $request->status == 'Approved') {
                        if (isset($binItems[$itemCode])) {
                            Bin::where('item_code', $itemCode)->where('warehouse', $branch)->update([
                                'consigned_qty' => isset($qty[$itemCode]) ? $qty[$itemCode][0] : 0,
                                'consignment_price' => $price,
                                'modified' => $now,
                                'modified_by' => Auth::user()->wh_user
                            ]);
                        } else {
                            $latestBin = Bin::where('name', 'like', '%bin/%')->max('name');
                            $latestBinExploded = explode('/', $latestBin);
                            $binId = (($latestBin) ? $latestBinExploded[1] : 0) + 1;
                            $binId = str_pad($binId, 7, '0', STR_PAD_LEFT);
                            $binId = 'BIN/' . $binId;

                            Bin::insert([
                                'name' => $binId,
                                'creation' => $now,
                                'modified' => $now,
                                'modified_by' => Auth::user()->wh_user,
                                'owner' => Auth::user()->wh_user,
                                'docstatus' => 0,
                                'idx' => 0,
                                'warehouse' => $branch,
                                'item_code' => $itemCode,
                                'stock_uom' => isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode][0]->stock_uom : null,
                                'valuation_rate' => $price,
                                'consigned_qty' => isset($qty[$itemCode]) ? $qty[$itemCode][0] : 0,
                                'consignment_price' => $price
                            ]);
                        }
                    }

                    if (isset($items[$itemCode]) || in_array($itemCode, $skippedItems)) {
                        if (isset($prices[$itemCode])) {
                            $updateValues['price'] = $price;
                            $updateValues['idx'] = $i + 1;
                        }

                        if (in_array($itemCode, $skippedItems) && $request->has('status')) {
                            $updateValues['status'] = $request->status;
                        }

                        BeginningInventoryItem::where('parent', $id)->where('item_code', $itemCode)->update($updateValues);
                    } else {
                        $itemQty = isset($qty[$itemCode]) ? preg_replace('/[^0-9 .]/', '', $qty[$itemCode][0]) : 0;

                        if (!$itemQty) {
                            return redirect()->back()->with('error', 'Opening qty cannot be empty');
                        }

                        $insert = [
                            'name' => uniqid(),
                            'creation' => $now,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'parent' => $id,
                            'idx' => $i + 1,
                            'item_code' => $itemCode,
                            'item_description' => isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode][0]->description : null,
                            'stock_uom' => isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode][0]->stock_uom : null,
                            'opening_stock' => $itemQty,
                            'stocks_displayed' => 0,
                            'price' => $price,
                            'amount' => $price * $itemQty,
                            'modified' => $now,
                            'modified_by' => Auth::user()->wh_user,
                            'parentfield' => 'items',
                            'parenttype' => 'Consignment Beginning Inventory'
                        ];

                        if ($request->has('status') && $request->status == 'Approved') {
                            $insert['status'] = $request->status;
                        }

                        BeginningInventoryItem::insert($insert);
                    }
                }
            } else {
                BeginningInventoryItem::where('parent', $id)->update($updateValues);
            }

            if (isset($updateValues['price'])) {
                unset($updateValues['price']);
            }

            if (isset($updateValues['idx'])) {
                unset($updateValues['idx']);
            }

            if ($request->status == 'Approved') {
                $updateValues['approved_by'] = Auth::user()->full_name;
                $updateValues['date_approved'] = $now;
            }

            if ($request->has('remarks')) {
                $updateValues['remarks'] = $request->remarks;
            }

            BeginningInventory::where('name', $id)->update($updateValues);

            DB::commit();
            if ($request->ajax()) {
                return ApiResponse::success('Beginning Inventory for ' . $branch . ' was ' . ($request->has('status') ? $request->status : 'Updated') . '.');
            }

            return redirect()->back()->with('success', 'Beginning Inventory for ' . $branch . ' was ' . ($request->has('status') ? $request->status : 'Updated') . '.');
        } catch (Exception $e) {
            Log::error('ConsignmentBeginningInventoryController approveBeginningInventory failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            if ($request->ajax()) {
                return ApiResponse::failure('Something went wrong. Please try again later.');
            }

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /cancel/approved_beginning_inv/{id}
    public function cancelApprovedBeginningInventory($id)
    {
        DB::beginTransaction();
        try {
            $inventory = BeginningInventory::find($id);

            if (!$inventory) {
                return redirect()->back()->with('error', 'Beginning inventory record does not exist.');
            }

            if ($inventory->status == 'Cancelled') {
                return redirect()->back()->with('error', 'Beginning inventory record is already cancelled.');
            }

            $items = BeginningInventoryItem::where('parent', $id)->get();

            if (count($items) > 0) {
                $activityLogsData = [];
                foreach ($items as $item) {
                    Bin::where('warehouse', $inventory->branch_warehouse)->where('item_code', $item->item_code)->update([
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'consigned_qty' => 0
                    ]);

                    $activityLogsData[$item->item_code]['opening_stock'] = (float) $item->opening_stock;
                }
            }

            $updateValues = [
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ];

            BeginningInventory::where('name', $id)->update($updateValues);
            BeginningInventoryItem::where('parent', $id)->update($updateValues);

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Approved Beginning Inventory Record for ' . $inventory->branch_warehouse . ' has been cancelled by ' . $inventory->owner . ' at ' . now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Beginning Inventory',
                'reference_name' => $inventory->name,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogsData ?? [], true)
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Beginning Inventory for ' . $inventory->branch_warehouse . ' was cancelled.');
        } catch (Exception $e) {
            Log::error('ConsignmentBeginningInventoryController cancelApprovedBeginningInventory failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /beginning_inventory_list
    public function beginningInventoryList(Request $request)
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $beginningInventory = BeginningInventory::whereIn('branch_warehouse', $assignedConsignmentStore)->orderBy('creation', 'desc')->paginate(10);

        return view('consignment.beginning_inv_list', compact('beginningInventory'));
    }

    // /beginning_inventory/{inv?}
    public function beginningInventory($inv = null)
    {
        $invRecord = [];
        if ($inv) {
            $invRecord = BeginningInventory::where('name', $inv)->where('status', 'For Approval')->first();

            if (!$invRecord) {
                return redirect()->back()->with('error', 'Inventory Record Not Found.');
            }
        }

        $branch = $invRecord ? $invRecord->branch_warehouse : null;
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.beginning_inventory', compact('assignedConsignmentStore', 'inv', 'branch', 'invRecord'));
    }

    // /get_items/{branch}
    public function getItems(Request $request, $branch)
    {
        $searchStr = explode(' ', $request->q);

        $items = Bin::query()
            ->from('tabBin as bin')
            ->join('tabItem as item', 'item.item_code', 'bin.item_code')
            ->when($request->q, function ($query) use ($request, $searchStr) {
                return $query->where(function ($subQuery) use ($searchStr, $request) {
                    foreach ($searchStr as $str) {
                        $subQuery->where('item.description', 'LIKE', '%' . $str . '%');
                    }

                    $subQuery->orWhere('item.item_code', 'LIKE', '%' . $request->q . '%');
                });
            })
            ->select('item.item_code', 'item.description', 'item.item_image_path', 'item.item_classification', 'item.stock_uom')
            ->groupBy('item.item_code', 'item.description', 'item.item_image_path', 'item.item_classification', 'item.stock_uom')
            ->limit(8)
            ->get();

        $itemCodes = collect($items)->pluck('item_code');

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image) {
            return $this->base64Image("img/$image");
        });

        $noImg = $this->base64Image('icon/no_img.png');

        $itemsArr = [];
        foreach ($items as $item) {
            $image = Arr::get($itemImages, $item->item_code, $noImg);

            $itemsArr[] = [
                'id' => $item->item_code,
                'text' => $item->item_code . ' - ' . strip_tags($item->description),
                'description' => strip_tags($item->description),
                'classification' => $item->item_classification,
                'image' => $image,
                'alt' => Str::slug(strip_tags($item->description), '-'),
                'uom' => $item->stock_uom
            ];
        }

        return response()->json([
            'items' => $itemsArr
        ]);
    }

    // /beginning_inv_items/{action}/{branch}/{id?}
    public function beginningInvItems(Request $request, $action, $branch, $id = null)
    {
        if ($request->ajax()) {
            $items = [];
            $invName = null;
            $remarks = null;
            $itemsWithConsignedQty = Bin::where('warehouse', $branch)->where('consigned_qty', '>', 0)->pluck('item_code');

            $invRecords = BeginningInventory::where('branch_warehouse', $branch)->whereIn('status', ['For Approval', 'Approved'])->pluck('name');
            $invItems = BeginningInventoryItem::whereIn('parent', $invRecords)->pluck('item_code');

            $invItems = collect($invItems)->merge($itemsWithConsignedQty);

            if ($action == 'update') {
                $invName = $id;
                $cbi = BeginningInventory::find($id);
                $remarks = $cbi ? $cbi->remarks : null;

                $inventory = BeginningInventoryItem::where('parent', $id)
                    ->select('item_code', 'item_description', 'stock_uom', 'opening_stock', 'stocks_displayed', 'price')
                    ->orderBy('item_description', 'asc')
                    ->get();

                foreach ($inventory as $inv) {
                    $items[] = [
                        'item_code' => $inv->item_code,
                        'item_description' => trim(strip_tags($inv->item_description)),
                        'stock_uom' => $inv->stock_uom,
                        'opening_stock' => $inv->opening_stock * 1,
                        'stocks_displayed' => $inv->stocks_displayed * 1,
                        'price' => $inv->price * 1
                    ];
                }
            } else {
                $binItems = Bin::query()
                    ->from('tabBin as bin')
                    ->join('tabItem as item', 'bin.item_code', 'item.name')
                    ->where('bin.warehouse', $branch)
                    ->where('bin.actual_qty', '>', 0)
                    ->where('bin.consigned_qty', 0)
                    ->whereNotIn('bin.item_code', $invItems)
                    ->select('bin.warehouse', 'bin.item_code', 'bin.actual_qty', 'bin.stock_uom', 'item.description')
                    ->orderBy('bin.actual_qty', 'desc')
                    ->get();

                foreach ($binItems as $item) {
                    $items[] = [
                        'item_code' => $item->item_code,
                        'item_description' => trim(strip_tags($item->description)),
                        'stock_uom' => $item->stock_uom,
                        'opening_stock' => 0,
                        'stocks_displayed' => 0,
                        'price' => 0
                    ];
                }
            }

            $items = collect($items)->sortBy('item_description');

            $itemCodes = collect($items)->pluck('item_code');

            $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
            $itemImages = collect($itemImages)->map(function ($image) {
                return $this->base64Image("img/$image");
            });

            $noImg = $this->base64Image('icon/no_img.png');
            $itemImages['no_img'] = $noImg;

            $detail = [];
            if ($id) {
                $detail = BeginningInventory::find($id);
            }

            return view('consignment.beginning_inv_items', compact('items', 'branch', 'itemImages', 'invName', 'invItems', 'remarks', 'detail'));
        }
    }

    // /save_beginning_inventory
    public function saveBeginningInventory(Request $request)
    {
        try {
            if (!$request->branch) {
                return redirect()->back()->with('error', 'Please select a store');
            }

            $openingStock = $request->opening_stock;
            $openingStock = preg_replace('/[^0-9 .]/', '', $openingStock);

            $price = $request->price;
            $price = preg_replace('/[^0-9 .]/', '', $price);

            $itemCodes = $request->item_code;
            $itemCodes = collect(array_filter($itemCodes))->unique();
            $branch = $request->branch;

            if (!$itemCodes) {
                return redirect()->back()->with('error', 'Please select an item to save');
            }

            $maxOpeningStock = max($openingStock);
            $maxPrice = max($price);
            $hasOpeningStock = array_filter($openingStock);
            $hasPrice = array_filter($price);

            if ($maxOpeningStock <= 0 || $maxPrice <= 0 || !$hasOpeningStock || !$hasPrice) {
                $nullValue = ($maxOpeningStock <= 0 || !$hasOpeningStock) ? 'Opening Stock' : 'Price';
                return redirect()->back()->with('error', 'Please input values to ' . $nullValue);
            }

            $now = now();

            $items = Item::whereIn('name', $itemCodes)->select('name', 'item_code', 'description', 'stock_uom')->get();
            $items = collect($items)->map(function ($item) use ($openingStock, $price) {
                unset($item->name);
                $qty = isset($openingStock[$item->item_code]) ? $openingStock[$item->item_code] : 1;
                $qty = (float) $qty;
                $value = isset($openingStock[$item->item_code]) ? $price[$item->item_code] : 1;
                $value = (float) $value;

                $item->item_description = strip_tags($item->description);
                $item->opening_stock = $qty;
                $item->status = 'For Approval';
                $item->price = $value;
                $item->amount = $qty * $value;

                return $item;
            });

            $body = [
                'docstatus' => 0,
                'status' => 'For Approval',
                'branch_warehouse' => $branch,
                'transaction_date' => $now->toDateTimeString(),
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpPost('Consignment Beginning Inventory', $body);

            if (!isset($response['data'])) {
                throw new Exception('An error occured. Please try again.');
            }

            $subject = 'For Approval Beginning Inventory Entry for ' . $branch . ' has been created by ' . Auth::user()->full_name . ' at ' . $now;
            $logs = [
                'docstatus' => 0,
                'subject' => $subject,
                'content' => 'Consignment Activity Log',
                'communication_date' => $now,
                'reference_doctype' => 'Beginning Inventory',
                'reference_name' => $response['data']['name'],
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
            ];

            $this->erpPost('Activity Log', $logs);

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Saved! Please wait for approval');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /cancel_beginning_inventory/{id}
    public function cancelDraftBeginningInventory($beginningInventoryId)
    {
        try {
            $response = $this->erpDelete('Consignment Beginning Inventory', $beginningInventoryId);

            if (!isset($response['data'])) {
                throw new Exception('An error occured.');
            }

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Canceled.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    // /update_beginning_inventory/{id}
    public function updateDraftBeginningInventory($id, Request $request)
    {
        try {
            $openingStock = $request->opening_stock;
            $openingStock = preg_replace('/[^0-9 .]/', '', $openingStock);

            $price = $request->price;
            $price = preg_replace('/[^0-9 .]/', '', $price);

            $itemCodes = $request->item_code;
            $itemCodes = collect(array_filter($itemCodes))->unique();
            $branch = $request->branch;

            if (!$itemCodes) {
                return redirect()->back()->with('error', 'Please select an item to save');
            }

            $maxOpeningStock = max($openingStock);
            $maxPrice = max($price);
            $hasOpeningStock = array_filter($openingStock);
            $hasPrice = array_filter($price);

            if ($maxOpeningStock <= 0 || $maxPrice <= 0 || !$hasOpeningStock || !$hasPrice) {
                $nullValue = ($maxOpeningStock <= 0 || !$hasOpeningStock) ? 'Opening Stock' : 'Price';
                return redirect()->back()->with('error', 'Please input values to ' . $nullValue);
            }

            $items = Item::whereIn('name', $itemCodes)->select('name', 'item_code', 'description', 'stock_uom')->get();
            $items = collect($items)->map(function ($item) use ($openingStock, $price) {
                unset($item->name);
                $qty = isset($openingStock[$item->item_code]) ? $openingStock[$item->item_code] : 1;
                $qty = (float) $qty;
                $value = isset($openingStock[$item->item_code]) ? $price[$item->item_code] : 1;
                $value = (float) $value;

                $item->item_description = strip_tags($item->description);
                $item->opening_stock = $qty;
                $item->status = 'For Approval';
                $item->price = $value;
                $item->amount = $qty * $value;

                return $item;
            });

            $body = [
                'branch_warehouse' => $branch,
                'remarks' => $request->remarks,
                'items' => $items
            ];

            $response = $this->erpPut('Consignment Beginning Inventory', $id, $body);

            if (!isset($response['data'])) {
                throw new Exception('An error occured. Please try again.');
            }

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory entry updated!');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    // /beginning_inv/get_received_items/{branch}
    public function getReceivedItems(Request $request, $branch)
    {
        $searchStr = explode(' ', $request->q);

        $soldItemCodes = [];
        $soldQty = [];

        $items = Bin::query()
            ->from('tabBin as bin')
            ->join('tabItem as item', 'item.item_code', 'bin.item_code')
            ->when($request->q, function ($query) use ($request, $searchStr) {
                return $query->where(function ($subQuery) use ($searchStr, $request) {
                    foreach ($searchStr as $str) {
                        $subQuery->where('item.description', 'LIKE', '%' . $str . '%');
                    }

                    $subQuery->orWhere('item.item_code', 'LIKE', '%' . $request->q . '%');
                });
            })
            ->when(Auth::user()->user_group == 'Promodiser', function ($query) use ($branch) {
                return $query->where('bin.warehouse', $branch);
            })
            ->select('bin.*', 'item.*')
            ->get()
            ->groupBy('item_code');

        $itemCodes = collect($items)->keys();

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image) {
            return "img/$image";
        });

        $noImg = '/icon/no_img.png';

        $defaultImages = Item::whereIn('name', $itemCodes)->whereNotNull('item_image_path')->select('name as item_code', 'item_image_path as image_path')->get();
        $defaultImage = collect($defaultImages)->groupBy('item_code');

        $inventoryArr = BeginningInventory::query()
            ->from('tabConsignment Beginning Inventory as inv')
            ->join('tabConsignment Beginning Inventory Item as item', 'item.parent', 'inv.name')
            ->where('inv.branch_warehouse', $branch)
            ->where('inv.status', 'Approved')
            ->where('item.status', 'Approved')
            ->whereIn('item.item_code', $itemCodes)
            ->select('item.item_code', 'item.price', 'inv.transaction_date')
            ->get();

        $inventory = collect($inventoryArr)->groupBy('item_code');

        $itemsArr = [];
        foreach ($itemCodes as $itemCode) {
            if (!isset($items[$itemCode])) {
                continue;
            }

            $item = $items[$itemCode][0];
            $img = Arr::get($itemImages, $itemCode, $noImg);
            $img = asset("storage/$img");

            $max = $item->consigned_qty * 1;

            $itemsArr[] = [
                'id' => $itemCode,
                'text' => $itemCode . ' - ' . strip_tags($item->description),
                'description' => strip_tags($item->description),
                'max' => $max,
                'uom' => $item->stock_uom,
                'price' => '₱ ' . number_format($item->consignment_price, 2),
                'transaction_date' => isset($inventory[$itemCode]) ? $inventory[$itemCode][0]->transaction_date : null,
                'img' => $img,
                'alt' => Str::slug(explode('.', $img)[0], '-')
            ];
        }

        $itemsArr = collect($itemsArr)->sortByDesc('max')->values()->all();

        return response()->json($itemsArr);
    }
}
