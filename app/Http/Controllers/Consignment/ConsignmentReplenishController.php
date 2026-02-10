<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\AssignedWarehouses;
use App\Models\Bin;
use App\Models\ConsignmentStockEntry;
use App\Models\ItemImages;
use App\Models\MaterialRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;

class ConsignmentReplenishController extends Controller
{
    use GeneralTrait, ERPTrait;

    public function index(Request $request)
    {
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ?? 0;
        if ($isPromodiser) {
            return $this->indexPromodiser($request);
        }

        $consignmentStores = Warehouse::where([
            'disabled' => 0,
            'parent_warehouse' => 'P2 Consignment Warehouse - FI'
        ])->orderBy('name')->pluck('name');

        if ($request->ajax()) {
            $targetWarehouses = $consignmentStores;
            $targetWarehouses = $request->branch ? [$request->branch] : $targetWarehouses;

            $list = MaterialRequest::with('items')
                ->where(['transfer_as' => 'Consignment', 'custom_purpose' => 'Consignment Order'])
                ->when($targetWarehouses, function ($query) use ($targetWarehouses) {
                    return $query->whereIn('branch_warehouse', $targetWarehouses);
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('consignment_status', $request->status);
                })
                ->when($request->search, function ($query) use ($request) {
                    return $query->where('name', 'like', "%{$request->search}%");
                })
                ->orderByDesc('creation')
                ->paginate(20);

            $result = [];
            foreach ($list as $row) {
                $result[] = [
                    'name' => $row->name,
                    'branch_warehouse' => $row->branch_warehouse,
                    'owner' => ucwords(str_replace('.', ' ', explode('@', $row->owner)[0])),
                    'creation' => Carbon::parse($row->creation)->format('M. d, Y - h:i A'),
                    'status' => $row->consignment_status,
                ];
            }

            return view('consignment.supervisor.consignment_order_table', compact('result', 'list'));
        }

        return view('consignment.supervisor.consignment_order_index', compact('consignmentStores'));
    }

    public function indexPromodiser(Request $request)
    {
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        if ($request->ajax()) {
            $targetWarehouses = $assignedConsignmentStores;
            $targetWarehouses = $request->branch ? [$request->branch] : $targetWarehouses;

            $list = MaterialRequest::with('items')
                ->where(['transfer_as' => 'Consignment', 'custom_purpose' => 'Consignment Order'])
                ->when($targetWarehouses, function ($query) use ($targetWarehouses) {
                    return $query->whereIn('branch_warehouse', $targetWarehouses);
                })
                ->when($request['status'] ?? null, function ($query) use ($request) {
                    return $query->where('consignment_status', $request['status']);
                })
                ->when($request['search'] ?? null, function ($query) use ($request) {
                    $searchString = $request['search'];
                    return $query->where('name', 'like', "%{$searchString}%");
                })
                ->orderByDesc('creation')
                ->paginate(20);

            return view('consignment.replenish_tbl', compact('list'));
        }

        return view('consignment.replenish_index', compact('assignedConsignmentStores'));
    }

    public function editConsignmentOrder($id)
    {
        $details = MaterialRequest::with('items')->find($id);

        $consignmentStores = Warehouse::where([
            'disabled' => 0,
            'parent_warehouse' => 'P2 Consignment Warehouse - FI'
        ])->orderBy('name')->pluck('name');

        return view('consignment.supervisor.consignment_order_edit', compact('details', 'consignmentStores'));
    }

    public function updateConsignmentOrder($id, Request $request)
    {
        try {
            $items = [];

            $materialRequest = MaterialRequest::find($id);
            $consignmentStatus = $request->consignment_status;

            if (!$materialRequest) {
                throw new Exception("MREQ $id not found!");
            }

            if ($consignmentStatus == 'Cancelled' && $materialRequest->docstatus == 2) {
                throw new Exception("MREQ $id is already canceled!");
            }

            $method = $consignmentStatus == 'Cancelled' && !$materialRequest->docstatus ? 'delete' : 'put';

            switch ($consignmentStatus) {
                case 'Approved':
                    $docstatus = 1;
                    break;
                case 'Cancelled':
                    $docstatus = 2;
                    break;
                default:
                    $docstatus = 0;
                    break;
            }

            foreach ($request->item_code as $index => $itemCode) {
                $rate = (float) str_replace(',', '', $request->price[$index]);
                $qty = (int) str_replace(',', '', $request->quantity[$index]);
                $name = $request->name[$index] ?? null;
                $warehouse = $request->branch;
                $items[] = compact('name', 'itemCode', 'rate', 'qty', 'warehouse');
            }

            $data = [
                'delivery_date' => Carbon::parse($request->delivery_date)->format('Y-m-d'),
                'required_by' => Carbon::parse($request->required_by)->format('Y-m-d'),
                'customer_address' => $request->customer_address,
                'consignment_status' => $consignmentStatus,
                'material_request_type' => 'Material Transfer',
                'branch_warehouse' => $request->branch,
                'customer' => $request->customer,
                'project' => $request->project,
                'notes00' => $request->remarks,
                'docstatus' => $docstatus,
                'items' => $items
            ];

            $response = $this->erpCall($method, 'Material Request', $id, $data);
            if (!isset($response['data'])) {
                $err = $response['exception'] ?? 'An error occured while updating material request';
                throw new Exception($err);
            }

            if ($consignmentStatus == 'Cancelled' && !$materialRequest->docstatus) {
                return redirect('/consignment/replenish')->with('success', "MREQ $id successfully deleted");
            }

            return redirect()->back()->with('success', "$id successfully updated!");
        } catch (\Throwable $th) {
            Log::error('ConsignmentReplenishController updateConsignmentOrder failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function modalContents($id)
    {
        $stockEntry = ConsignmentStockEntry::with('items')->find($id);

        $itemImages = $inventory = [];
        $itemCodes = collect($stockEntry->items)->pluck('item_code');

        $flattenItemCodes = $itemCodes->implode("','");
        $itemImages = ItemImages::whereRaw("parent IN ('$flattenItemCodes')")->pluck('image_path', 'parent');

        $allowedWarehouses = Warehouse::where('parent_warehouse', 'like', 'P2%')
            ->where('parent_warehouse', '!=', 'P2 Consignment Warehouse - FI')
            ->pluck('parent_warehouse')
            ->unique();

        $inventory = Bin::whereHas('warehouse', function ($warehouse) use ($allowedWarehouses) {
            $warehouse->whereIn('parent_warehouse', $allowedWarehouses);
        })->whereRaw("item_code IN ('$flattenItemCodes')")->select('warehouse', 'item_code')->get();

        $itemWarehousePairs = $inventory->map(fn($item) => [$item->item_code, $item->warehouse])->unique()->values()->toArray();
        $availableQtyMap = $this->getAvailableQtyBulk($itemWarehousePairs);

        $inventory = collect($inventory)->map(function ($item) use ($availableQtyMap) {
            $itemCode = $item->item_code;
            $warehouse = $item->warehouse;
            $key = "{$itemCode}-{$warehouse}";
            $item->available_qty = $availableQtyMap[$key] ?? 0;

            if ($item->available_qty) {
                return $item;
            }
        })->filter()->groupBy('item_code');

        return view('consignment.replenish_modal', compact('stockEntry', 'inventory'));
    }

    public function form(Request $request, $id = null)
    {
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $materialRequest = $itemImages = [];

        if ($id) {
            $materialRequest = MaterialRequest::with('items')->find($id);
            $itemImages = ItemImages::whereIn('parent', collect($materialRequest->items)->pluck('item_code'))->pluck('image_path', 'parent');
        }

        return view('consignment.replenish_form', compact('assignedConsignmentStores', 'materialRequest', 'itemImages'));
    }

    public function delete($id)
    {
        try {
            $response = $this->erpDelete('Material Request', $id);

            if (!isset($response['data'])) {
                $err = data_get($response, 'exception', 'An error occured while deleting the document');
                throw new Exception($err);
            }

            return redirect('/consignment/replenish')->with('success', "$id Deleted.");
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function submit(Request $request)
    {
        try {
            $now = now();
            $items = $request->items;
            $branch = $request->branch;
            $status = $request->status ? 'For Approval' : 'Draft';

            $customer = 'CW MARKETING AND DEVELOPMENT CORPORATION';
            $project = 'CW HOME DEPOT';
            if (Str::contains($branch, 'WILCON DEPOT')) {
                $customer = 'WILCON DEPOT, INC.';
                $project = 'WILCON STOCKS';
            }

            $itemsData = [];
            foreach ($items as $itemCode => $item) {
                $qty = (int) $item['qty'];
                $remarks = $item['remarks'];
                $consignmentReason = $item['reason'];
                $scheduleDate = (clone $now)->addDays($consignmentReason == 'Stock Replenishment' ? 5 : 3)->format('Y-m-d');
                $warehouse = $branch;
                $itemsData[] = compact('itemCode', 'qty', 'remarks', 'consignmentReason', 'scheduleDate', 'warehouse');
            }

            $data = [
                'docstatus' => 0,
                'branch_warehouse' => $branch,
                'transfer_as' => 'Consignment',
                'custom_purpose' => 'Consignment Order',
                'material_request_type' => 'Material Transfer',
                'company' => 'FUMACO Inc.',
                'sales_person' => 'Plant 2',
                'purpose' => 'Consignment',
                'customer' => $customer,
                'project' => $project,
                'consignment_status' => $status,
                'items' => $itemsData,
                'transaction_date' => $now->toDateTimeString()
            ];

            $response = $this->erpPost('Material Request', $data);

            if (!isset($response['data'])) {
                $err = data_get($response, 'exception', 'An error occured while submitting your request');
                throw new Exception($err);
            }

            if ($status == 'For Approval') {
                $responseData = $response['data'];
                $responseData['branch'] = $branch;

                $users = User::where('user_group', 'Consignment Supervisor')->where('enabled', 1)->pluck('wh_user');

                foreach ($users as $user) {
                    $user = str_replace('.local', '.com', $user);
                    try {
                        Mail::send('mail_template.consignment_order', $responseData, function ($message) use ($user) {
                            $message->to($user);
                            $message->subject('AthenaERP - Consignment Order Notification');
                        });
                    } catch (\Throwable $th) {
                    }
                }
            }

            return redirect('/consignment/replenish')->with('success', 'Request submitted.');
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function approve($id, Request $request)
    {
        try {
            $issue = $request->issue;
            $items = $request->items;

            $stockEntry = ConsignmentStockEntry::with('items')->find($id);

            $columnsToExclude = ['parent', 'creation', 'modified', 'modified_by', 'owner', 'docstatus', 'parentfield', 'parenttype'];
            $mappedItems = collect($stockEntry->items)->map(function ($item) use ($items, $issue, $columnsToExclude) {
                $itemCode = $item->item_code;

                foreach ($columnsToExclude as $column) {
                    unset($item->$column);
                }

                $item->status = 'Issued';

                return $item;
            })->values();

            $stockEntry->setRelation('items', $mappedItems);

            return ApiResponse::success('Approved.');
        } catch (Exception $e) {
            return ApiResponse::failureLegacy($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $branch = $request->branch;
            $items = $request->items;
            $now = now();

            $statuses = ['Draft', 'For Approval', 'Cancelled'];
            $status = $statuses[$request->status] ?? 'Draft';

            $customer = 'CW MARKETING AND DEVELOPMENT CORPORATION';
            $project = 'CW HOME DEPOT';
            if (Str::contains($branch, 'WILCON DEPOT')) {
                $customer = 'WILCON DEPOT, INC.';
                $project = 'WILCON STOCKS';
            }

            $itemsData = [];
            foreach ($items as $itemCode => $item) {
                $name = $item['name'] ?? null;
                $qty = (int) $item['qty'];
                $remarks = $item['remarks'];
                $consignmentReason = $item['reason'];
                $scheduleDate = (clone $now)->addDays($consignmentReason == 'Stock Replenishment' ? 5 : 3)->format('Y-m-d');
                $warehouse = $branch;
                $itemsData[] = compact('name', 'itemCode', 'qty', 'remarks', 'consignmentReason', 'scheduleDate', 'warehouse');
            }

            $data = [
                'branch_warehouse' => $branch,
                'consignment_status' => $status,
                'transaction_date' => $now->toDateTimeString(),
                'customer' => $customer,
                'project' => $project,
                'items' => $itemsData,
            ];

            $response = $this->erpPut('Material Request', $id, $data);
            if (!isset($response['data'])) {
                $err = data_get($response, 'exception', 'An error occured while updating stock entry');
                throw new Exception($err);
            }

            if ($status == 'For Approval') {
                $responseData = $response['data'];
                $responseData['branch'] = $branch;

                $users = User::where('user_group', 'Consignment Supervisor')->where('enabled', 1)->pluck('wh_user');

                foreach ($users as $user) {
                    $user = str_replace('.local', '.com', $user);
                    try {
                        Mail::send('mail_template.consignment_order', $responseData, function ($message) use ($user) {
                            $message->to($user);
                            $message->subject('AthenaERP - Consignment Order Notification');
                        });
                    } catch (\Throwable $th) {
                    }
                }
            }

            return redirect()->back()->with('success', "$id successfully updated!");
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }
}
