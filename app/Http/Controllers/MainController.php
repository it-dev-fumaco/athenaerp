<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ProductionController;
use App\Http\Helpers\ApiResponse;
use App\Models\AssignedWarehouses;
use App\Models\AthenaTransaction;
use App\Models\BeginningInventory;
use App\Models\Bin;
use App\Models\ConsignmentInventoryAuditReport;
use App\Models\ConsignmentSalesReportDeadline;
use App\Models\ConsignmentStockEntry;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DepartmentWithPriceAccess;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\ItemImages;
use App\Models\ItemPrice;
use App\Models\ItemSupplier;
use App\Models\ItemVariantAttribute;
use App\Models\LandedCostVoucher;
use App\Models\MaterialRequest;
use App\Models\MESProductionOrder;
use App\Models\PackedItem;
use App\Models\PackingSlip;
use App\Models\PackingSlipItem;
use App\Models\ProductBundle;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceiptItem;
use App\Models\SalesOrder;
use App\Models\Singles;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\StockLedgerEntry;
use App\Models\StockReservation;
use App\Models\UOMConversionDetail;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseUsers;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\CutoffDateService;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Exception;
use ZipArchive;

class MainController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function allowedParentWarehouses()
    {
        return Auth::user()->allowedParentWarehouses();
    }

    public function getCutoffDate($transactionDate)
    {
        return app(CutoffDateService::class)->getCutoffPeriod($transactionDate);
    }

    public function index(Request $request)
    {
        $this->updateReservationStatus();
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();
        if (Auth::user()->user_group == 'User') {
            return redirect('/search_results');
        }

        if (Auth::user()->user_group == 'Promodiser') {
            $assignedConsignmentStore = AssignedWarehouses::where('parent', $user)->orderBy('warehouse', 'asc')->pluck('warehouse');

            if (count($assignedConsignmentStore) > 0) {
                $cutoffDisplayInfo = app(CutoffDateService::class)->getCutoffDisplayInfo();
                $due = $cutoffDisplayInfo['due'];

                $invSummary = Bin::query()
                    ->from('tabBin as b')
                    ->join('tabItem as i', 'i.name', 'b.item_code')
                    ->where('i.disabled', 0)
                    ->where('i.is_stock_item', 1)
                    ->whereIn('b.warehouse', $assignedConsignmentStore)
                    ->where('b.consigned_qty', '>', 0)
                    ->select('b.warehouse', 'b.consigned_qty')
                    ->get()
                    ->toArray();

                $invSummary = collect($invSummary)->groupBy('warehouse');

                $inventorySummary = [];
                foreach ($invSummary as $warehouse => $row) {
                    $inventorySummary[$warehouse] = [
                        'items_on_hand' => collect($row)->count(),
                        'total_qty' => collect($row)->sum('consigned_qty'),
                    ];
                }

                // get total pending inventory audit
                $storesWithBeginningInventory = BeginningInventory::query()
                    ->where('status', 'Approved')
                    ->whereIn('branch_warehouse', $assignedConsignmentStore)
                    ->orderBy('branch_warehouse', 'asc')
                    ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                    ->groupBy('branch_warehouse')
                    ->pluck('transaction_date', 'branch_warehouse')
                    ->toArray();

                $inventoryAuditPerWarehouse = ConsignmentInventoryAuditReport::query()
                    ->whereIn('branch_warehouse', array_keys($storesWithBeginningInventory))
                    ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                    ->groupBy('branch_warehouse')
                    ->pluck('transaction_date', 'branch_warehouse')
                    ->toArray();

                // get total stock transfer
                $totalStockTransfer = ConsignmentStockEntry::query()
                    ->whereIn('source_warehouse', $assignedConsignmentStore)
                    ->where('status', 'Pending')
                    ->count();

                // get total consignment orders
                $totalConsignmentOrders = MaterialRequest::where('custom_purpose', 'Consignment Order')->where('transfer_as', 'Consignment')->whereIn('branch_warehouse', $assignedConsignmentStore)->where('consignment_status', 'For Approval')->count();

                // get incoming / to receive items
                $beginningInventoryStart = BeginningInventory::orderBy('transaction_date', 'asc')->value('transaction_date');
                $beginningInventoryStartDate = $beginningInventoryStart ? Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

                $now = now();

                $branchesWithBeginningInventory = BeginningInventory::query()
                    ->whereIn('branch_warehouse', $assignedConsignmentStore)
                    ->where('status', '!=', 'Cancelled')
                    ->distinct()
                    ->pluck('branch_warehouse')
                    ->toArray();

                $branchesWithPendingBeginningInventory = [];
                foreach ($assignedConsignmentStore as $store) {
                    if (!in_array($store, $branchesWithBeginningInventory)) {
                        $branchesWithPendingBeginningInventory[] = $store;
                    }
                }

                return view('consignment.index_promodiser', compact('assignedConsignmentStore', 'inventorySummary', 'totalStockTransfer', 'totalConsignmentOrders', 'branchesWithPendingBeginningInventory', 'due'));
            }

            return redirect('/search_results');
        }

        if (Auth::user()->user_group == 'Consignment Supervisor') {
            return $this->viewConsignmentDashboard();
        }

        return view('index');
    }

    public function viewConsignmentDashboard()
    {
        $cutoffDisplayInfo = app(CutoffDateService::class)->getCutoffDisplayInfo();
        $duration = Carbon::parse($cutoffDisplayInfo['durationFrom'])->format('M d, Y') . ' - ' . Carbon::parse($cutoffDisplayInfo['durationTo'])->format('M d, Y');

        $consignmentBranches = User::query()
            ->from('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->join('tabWarehouse as w', 'w.name', 'acw.warehouse')
            ->where('wu.user_group', 'Promodiser')
            ->where('w.disabled', 0)
            ->where('w.is_group', 0)
            ->select('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->groupBy('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->orderBy('w.warehouse_name', 'asc')
            ->get()
            ->toArray();

        $activeConsignmentBranches = collect($consignmentBranches)->where('is_group', 0)->where('disabled', 0);

        $promodisers = User::where('user_group', 'Promodiser')->where('enabled', 1)->count();

        $consignmentBranchesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->whereIn('branch_warehouse', array_column($consignmentBranches, 'name'))
            ->distinct()
            ->pluck('branch_warehouse')
            ->count();

        if (count($consignmentBranches) > 0) {
            $beginningInvPercentage = number_format(($consignmentBranchesWithBeginningInventory / count($consignmentBranches)) * 100, 2);
        } else {
            $beginningInvPercentage = 0;
        }

        // get total stock transfer
        $totalStockTransfers = ConsignmentStockEntry::where('purpose', '!=', 'Item Return')->where('status', 'Pending')->count();

        $pendingToReceive = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereDate('ste.delivery_date', '>=', '2022-06-25')
            ->whereIn('ste.transfer_as', ['Consignment', 'For Return', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where(function ($query) {
                $query
                    ->whereNull('sted.consignment_status')
                    ->orWhere('sted.consignment_status', '!=', 'Received');
            })
            ->count();

        // get total consignment orders
        $totalConsignmentOrders = MaterialRequest::where('custom_purpose', 'Consignment Order')->where('transfer_as', 'Consignment')->where('consignment_status', 'For Approval')->count();

        $totalPendingInventoryAudit = 0;
        // get total pending inventory audit
        $storesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->orderBy('branch_warehouse', 'asc')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $inventoryAuditPerWarehouse = ConsignmentInventoryAuditReport::query()
            ->whereIn('branch_warehouse', array_keys($storesWithBeginningInventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = now()->endOfDay();
        $cutoffDay = app(CutoffDateService::class)->getCutoffDay();

        $firstCutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') . '/' . $cutoffDay . '/' . $end->format('Y'))->endOfDay();

        if ($firstCutoff->gt($end)) {
            $end = $firstCutoff;
        }

        $cutoffDate = $this->getCutoffDate(now()->endOfDay());
        $periodFrom = $cutoffDate[0]->addDay();
        $periodTo = $cutoffDate[1];

        $pending = [];
        foreach (array_keys($storesWithBeginningInventory) as $store) {
            $beginningInventoryTransactionDate = Arr::get($storesWithBeginningInventory, $store);
            $lastInventoryAuditDate = Arr::get($inventoryAuditPerWarehouse, $store);

            if ($beginningInventoryTransactionDate) {
                $start = Carbon::parse($beginningInventoryTransactionDate);
            }

            if ($lastInventoryAuditDate) {
                $start = Carbon::parse($lastInventoryAuditDate);
            }

            $lastAuditDate = $start;

            $start = $start->startOfDay();

            $check = Carbon::parse($start)->between($periodFrom, $periodTo);
            if (Carbon::parse($start)->addDay()->startOfDay()->lt(Carbon::parse($periodTo)->startOfDay())) {
                if ($lastAuditDate->endOfDay()->lt($end) && $beginningInventoryTransactionDate) {
                    if (!$check) {
                        $totalPendingInventoryAudit++;
                    }
                }
            }
        }

        $startDate = Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');
        $endDate = now();

        $period = CarbonPeriod::create($startDate, '28 days', $endDate);

        $salesReportDeadline = ConsignmentSalesReportDeadline::first();

        $cutoffFilters = [];
        if ($salesReportDeadline) {
            $cutoffDay = $salesReportDeadline->{'1st_cutoff_date'};

            $cutoffPeriod = [];
            foreach ($period as $monthIndex => $date) {
                $from = $to = null;
                $dateWithCutoff = $date->day($cutoffDay);
                if ($dateWithCutoff >= $startDate && $dateWithCutoff <= $endDate) {
                    $cutoffPeriod[] = $date->format('d-m-Y');
                }

                if ($monthIndex == 0) {
                    $febCutoff = $cutoffDay <= 28 ? $cutoffDay : 28;
                    $cutoffPeriod[] = $febCutoff . '-02-' . now()->format('Y');
                }
            }

            $cutoffPeriod[] = $endDate->format('d-m-Y');
            usort($cutoffPeriod, function ($time1, $time2) {
                return strtotime($time1) - strtotime($time2);
            });

            foreach ($cutoffPeriod as $index => $cutoffDateItem) {
                if (Arr::exists($cutoffPeriod, $index + 1)) {
                    $cutoffFilters[] = [
                        'id' => $cutoffPeriod[$index] . '/' . $cutoffPeriod[$index + 1],
                        'cutoff_start' => Carbon::parse($cutoffPeriod[$index])->format('M. d, Y'),
                        'cutoff_end' => Carbon::parse($cutoffPeriod[$index + 1])->format('M. d, Y'),
                    ];
                }
            }
        }

        $salesReportIncludedYears = [];
        for ($year = 2022; $year <= date('Y'); $year++) {
            $salesReportIncludedYears[] = $year;
        }

        return view('consignment.index_consignment_supervisor', compact('duration', 'pendingToReceive', 'beginningInvPercentage', 'promodisers', 'activeConsignmentBranches', 'consignmentBranches', 'consignmentBranchesWithBeginningInventory', 'totalStockTransfers', 'totalPendingInventoryAudit', 'totalConsignmentOrders', 'cutoffFilters', 'salesReportIncludedYears'));
    }

    public function recentlyReceivedItems(Request $request)
    {
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $list = PurchaseReceiptItem::query()
            ->from('tabPurchase Receipt Item as item')
            ->join('tabPurchase Receipt as parent', 'parent.name', 'item.parent')
            ->whereIn('item.warehouse', $allowedWarehouses)
            ->where('item.docstatus', 1)
            ->whereDate('parent.posting_date', '>', now()->subDays(7)->startOfDay())
            ->select('parent.name', 'item.item_code', 'item.description', 'item.image', 'item.warehouse', 'item.qty', 'item.uom', 'item.creation', 'parent.posting_date', 'parent.posting_time', 'parent.owner')
            ->orderBy('parent.posting_date', 'desc')
            ->paginate(10);

        $itemImages = ItemImages::whereIn('parent', collect($list->items())->pluck('item_code'))->get();
        $itemImage = collect($itemImages)->groupBy('parent');

        return view('tbl_recently_received_items', compact('itemImage', 'list'));
    }

    public function reserved_qty(Request $request)
    {
        $reservedQty = StockReservation::select('item_code', 'warehouse', 'reserve_qty')->get();

        return view('index', compact('reservedQty'));
    }

    public function countSteForIssue($purpose)
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $count = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', $purpose)
            ->whereNotIn('sted.status', ['Issued', 'Returned'])
            ->when($purpose == 'Material Issue', function ($query) use ($allowedWarehouses) {
                return $query
                    ->whereNotIn('ste.issue_as', ['Customer Replacement', 'Sample'])
                    ->whereIn('sted.s_warehouse', $allowedWarehouses);
            })
            ->when($purpose == 'Material Transfer', function ($query) use ($allowedWarehouses) {
                return $query
                    ->whereNotIn('ste.transfer_as', ['Consignment', 'Sample Item', 'For Return'])
                    ->whereIn('sted.s_warehouse', $allowedWarehouses);
            })
            ->when($purpose == 'Material Transfer for Manufacture', function ($query) use ($allowedWarehouses) {
                return $query->whereIn('sted.s_warehouse', $allowedWarehouses);
            })
            ->when($purpose == 'Material Receipt', function ($query) use ($allowedWarehouses) {
                return $query
                    ->where('ste.receive_as', 'Sales Return')
                    ->whereIn('sted.t_warehouse', $allowedWarehouses);
            })
            ->count();

        if ($purpose == 'Material Receipt') {
            $count += DeliveryNote::query()
                ->from('tabDelivery Note as dn')
                ->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
                ->where('dn.is_return', 1)
                ->where('dn.docstatus', 0)
                ->whereIn('dni.warehouse', $allowedWarehouses)
                ->count();

            $count += StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', 0)
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.transfer_as', 'For Return')
                ->whereIn('sted.t_warehouse', $allowedWarehouses)
                ->where('ste.naming_series', 'STEC-')
                ->count();
        }

        if ($purpose == 'Material Transfer') {
            $count += StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', 0)
                ->where('purpose', 'Material Transfer')
                ->whereNotIn('sted.status', ['Issued', 'Returned'])
                ->whereIn('t_warehouse', $allowedWarehouses)
                ->whereIn('transfer_as', ['For Return', 'Internal Transfer'])
                ->count();
        }

        return $count;
    }

    public function countPsForIssue()
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $q1 = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
            ->where('psi.status', 'For Checking')
            ->whereRaw(('dri.item_code = psi.item_code'))
            ->where('ps.docstatus', 0)
            ->where('dri.docstatus', 0)
            ->whereIn('dri.warehouse', $allowedWarehouses)
            ->count();

        $q2 = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->where('sted.status', 'For Checking')
            ->whereIn('s_warehouse', $allowedWarehouses)
            ->whereIn('transfer_as', ['Consignment', 'Sample Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'ste.customer_1', 'sted.parent', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'sted.name as id', 'sted.stock_uom')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->count();

        return ($q1 + $q2);
    }

    public function userAllowedWarehouse($user)
    {
        return User::getAllowedWarehouseIdsFor($user);
    }

    public function getMrSalesReturn()
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        // $drSalesReturn = DeliveryNote::with('items')->whereHas('items', function ($item) use ($allowedWarehouses){
        //         $item->whereIn('warehouse', $allowedWarehouses)->select('parent', 'name', 'item_code', 'description', 'qty', 'item_status');
        //     })->where('docstatus', 0)->where('is_return', 1)
        //     ->select('name', 'reference', 'customer', 'owner', 'creation')
        //     ->orderByRaw("FIELD(dni.item_status, 'For Checking', 'For Return', 'Returned') ASC")->get();

        // Remove

        $drSalesReturn = DeliveryNote::query()
            ->from('tabDelivery Note as dn')
            ->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
            ->where('dn.docstatus', 0)
            ->where('is_return', 1)
            ->whereIn('dni.warehouse', $allowedWarehouses)
            ->select('dni.name as c_name', 'dn.name', 'dni.warehouse', 'dni.item_code', 'dni.description', 'dni.qty', 'dn.reference', 'dni.item_status', 'dn.customer', 'dn.owner', 'dn.creation')
            ->orderByRaw("FIELD(dni.item_status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $mrSalesReturn = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('ste.purpose', 'Material Receipt')
            ->where('ste.receive_as', 'Sales Return')
            ->whereIn('sted.t_warehouse', $allowedWarehouses)
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner', 'ste.creation')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $consignmentReturn = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.transfer_as', 'For Return')
            ->whereIn('sted.t_warehouse', $allowedWarehouses)
            ->where('ste.naming_series', 'STEC-')
            ->select('sted.name as stedname', 'ste.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'ste.sales_order_no', 'sted.status', 'ste.so_customer_name', 'sted.owner', 'ste.creation', 'sted.consignment_received_by', 'sted.consignment_date_received')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'For Return', 'Returned') ASC")
            ->get();

        $warehouseNames = collect($mrSalesReturn)
            ->pluck('t_warehouse')
            ->merge(collect($drSalesReturn)->pluck('warehouse'))
            ->merge(collect($consignmentReturn)->pluck('t_warehouse'))
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $parentWarehouses = $this->getWarehouseParentsBulk($warehouseNames);

        $list = [];
        foreach ($mrSalesReturn as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->t_warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format($d->transfer_qty),
                'sales_order_no' => $d->sales_order_no,
                'status' => $d->status,
                'so_customer_name' => $d->so_customer_name,
                'parent_warehouse' => Arr::get($parentWarehouses, $d->t_warehouse, null),
                'reference_doc' => 'stock_entry',
                'transaction_date' => $d->creation
            ];
        }

        foreach ($drSalesReturn as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->c_name,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format(abs($d->qty)),
                'sales_order_no' => $d->reference,
                'status' => $d->item_status,
                'so_customer_name' => $d->customer,
                'parent_warehouse' => Arr::get($parentWarehouses, $d->warehouse, null),
                'reference_doc' => 'delivery_note',
                'transaction_date' => $d->creation
            ];
        }

        foreach ($consignmentReturn as $d) {
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $list[] = [
                'c_name' => $d->stedname,
                'owner' => $owner,
                'name' => $d->name,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i A'),
                't_warehouse' => $d->t_warehouse,
                'item_code' => $d->item_code,
                'description' => $d->description,
                'transfer_qty' => number_format($d->transfer_qty),
                'sales_order_no' => $d->sales_order_no,
                'status' => $d->status,
                'so_customer_name' => $d->s_warehouse,
                'parent_warehouse' => Arr::get($parentWarehouses, $d->t_warehouse, null),
                'reference_doc' => 'stock_entry',
                'transaction_date' => $d->creation
            ];
        }

        return response()->json(['mr_return' => $list]);
    }

    public function feedbackDetails($id)
    {
        // $user = Auth::user()->frappe_userid;
        // $allowedWarehouses = $this->userAllowedWarehouse($user);
        $try = DB::connection('mysql_mes')
            ->table('production_order AS po')
            ->where('po.production_order', $id)
            ->get();

        if (count($try) == 1) {
            $data = DB::connection('mysql_mes')
                ->table('production_order AS po')
                ->where('po.production_order', $id)
                ->select('po.*')
                ->first();

            $img = ItemImages::where('parent', $data->item_code)->orderBy('idx', 'asc')->value('image_path');
            $img = $img ? "/img/$img" : '/icon/no_img.png';
            $img = $this->base64Image($img);

            $q = [
                'production_order' => $data->production_order,
                'fg_warehouse' => $data->fg_warehouse,
                'src_warehouse' => $data->wip_warehouse,
                'sales_order' => $data->sales_order,
                'status' => $data->status,
                'material_request' => $data->material_request,
                'img' => $img,
                'customer' => $data->customer,
                'item_code' => $data->item_code,
                'description' => $data->description,
                'qty_to_receive' => $data->produced_qty - $data->feedback_qty,
                'feedback_qty' => $data->feedback_qty,
                'stock_uom' => $data->stock_uom,
            ];
        } else {
            $se = StockEntry::query()
                ->from('tabStock Entry as se')
                ->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
                ->where('se.work_order', $id)
                ->first();

            $q[] = [
                'production_order' => $se->work_order,
                'fg_warehouse' => $se->to_warehouse,
                'sales_order_no' => $se->sales_order_no,
                'status' => $se->item_status,
                'material_request' => $se->material_request,
                'customer' => $se->customer,
                'item_code' => $se->item_code,
                'description' => $se->description,
                'qty_to_receive' => $se->actual_qty,
                'feedback_qty' => $se->transfer_qty,
                'stock_uom' => $se->stock_uom,
            ];
        }

        // return $q;
        return view('feedback_details_modal', compact('q'));
    }

    public function feedbackSubmit(Request $request)
    {
        DB::beginTransaction();
        try {
            $now = now();

            $erpUpdate = [];

            $erpUpdate = [
                'produced_qty' => $request->r_qty + $request->ofeedback_qty,
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => $request->r_qty == $request->f_qty ? 'Completed' : 'In Process'
            ];

            $erpProd = WorkOrder::where('name', $request->prod_order)->where('docstatus', 1)->update($erpUpdate);

            $mesUpdate = [];

            $mesUpdate = [
                'feedback_qty' => $request->r_qty + $request->ofeedback_qty,
                'last_modified_by' => Auth::user()->wh_user,
                'last_modified_at' => $now->toDateTimeString()
            ];

            // return $mesUpdate;
            $mesProd = DB::connection('mysql_mes')->table('production_order as po')->where('po.production_order', $request->prod_order)->update($mesUpdate);

            $feedbackLog = [];

            $feedbackLog = [
                'production_order' => $request->prod_order,
                'ste_no' => '',
                'item_code' => $request->itemCode,
                'item_name' => $request->itemDesc,
                'feedbacked_qty' => $request->r_qty,
                'from_warehouse' => $request->src_wh,
                'to_warehouse' => $request->to_wh,
                'transaction_date' => $now->format('Y-m-d'),
                'transaction_time' => $now->format('H:i:s'),
                'status' => '',
                'created_at' => $now->toDateTimeString(),
                'created_by' => Auth::user()->wh_user
            ];

            // return $feedbackLog;

            $mesLog = DB::connection('mysql_mes')->table('feedbacked_logs')->insert($feedbackLog);

            DB::commit();
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();
        }
    }

    public function getPsDetails(Request $request, $id)
    {
        if ($request->type == 'packed_item') {
            $q = PackingSlip::query()
                ->from('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabPacked Item as pi', 'pi.name', 'psi.pi_detail')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = pi.parent_item'))
                ->where('ps.docstatus', '<', 2)
                ->where('ps.item_status', 'For Checking')
                ->where('psi.name', $id)
                ->where('dri.docstatus', 0)
                ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'dri.item_code', 'psi.description', 'psi.qty', 'psi.name as id', 'dri.warehouse', 'psi.status', 'dri.stock_uom', 'psi.qty', 'dri.name as dri_name', 'dr.reference as sales_order', 'dri.uom', 'ps.docstatus')
                ->first();
        } else {
            $q = PackingSlip::query()
                ->from('tabPacking Slip as ps')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                ->whereRaw(('dri.item_code = psi.item_code'))
                ->where('ps.docstatus', '<', 2)
                ->where('ps.item_status', 'For Checking')
                ->where('psi.name', $id)
                ->where('dri.docstatus', 0)
                ->select('psi.barcode', 'psi.status', 'ps.name', 'ps.delivery_note', 'psi.item_code', 'psi.description', 'psi.qty', 'psi.name as id', 'dri.warehouse', 'psi.status', 'dri.stock_uom', 'psi.qty', 'dri.name as dri_name', 'dr.reference as sales_order', 'dri.uom', 'ps.docstatus')
                ->first();
        }

        if (!$q) {
            return ApiResponse::modal(false, 'Not Found', 'Item not found. Please reload the page.', 422);
        }

        $itemDetails = Item::query()->where('name', $q->item_code)->first();

        $img = ItemImages::query()->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64Image($img);

        $isBundle = false;
        if (!$itemDetails->is_stock_item) {
            $isBundle = ProductBundle::query()->where('name', $q->item_code)->exists();
        }
        $stockReservationDetails = [];
        $productBundleItems = [];
        if ($isBundle) {
            $query = PackedItem::query()->where('parent_detail_docname', $q->dri_name)->get();

            $itemWarehousePairs = $query->map(fn($row) => [$row->item_code, $row->warehouse])->unique()->values()->toArray();
            $availableQtyMap = $this->getAvailableQtyBulk($itemWarehousePairs);

            $soDetails = SalesOrder::query()->where('name', $q->sales_order)->first();

            foreach ($query as $row) {
                $availableQtyRow = $availableQtyMap["{$row->item_code}-{$row->warehouse}"] ?? 0;

                $productBundleItems[] = [
                    'item_code' => $row->item_code,
                    'description' => $row->description,
                    'uom' => $row->uom,
                    'qty' => ($row->qty * 1),
                    'available_qty' => $availableQtyRow,
                    'warehouse' => $row->warehouse
                ];

                $stockReservationDetails = [];
                if ($soDetails) {
                    $stockReservationDetails = $this->getStockReservation($row->item_code, $q->warehouse, $soDetails->sales_person, $soDetails->project, null, $soDetails->order_type, $soDetails->po_no);
                }
            }
        }

        if (!$stockReservationDetails) {
            $stockReservationDetails = [];
            $soDetails = SalesOrder::query()->where('name', $q->sales_order)->first();
            if ($soDetails) {
                $stockReservationDetails = $this->getStockReservation($q->item_code, $q->warehouse, $soDetails->sales_person, $soDetails->project, null, $soDetails->order_type, $soDetails->po_no);
            }
        }

        $availableQty = $this->getAvailableQty($q->item_code, $q->warehouse);

        $uomConversion = [];
        if ($q->uom != $q->stock_uom) {
            $uomConversion = UOMConversionDetail::query()
                ->where('parent', $q->item_code)
                ->whereIn('uom', [$q->uom, $q->stock_uom])
                ->orderBy('idx', 'asc')
                ->get();
        }

        $data = [
            'id' => $q->id,
            'type' => $request->type,
            'barcode' => $q->barcode,
            'item_image' => $img,  // $itemDetails->item_image_path,
            'delivery_note' => $q->delivery_note,
            'description' => $q->description,
            'item_code' => $q->item_code,
            'name' => $q->name,
            'sales_order' => $q->sales_order,
            'status' => $q->status,
            'stock_uom' => $q->stock_uom,
            'uom' => $q->uom,
            'qty' => ($q->qty * 1),
            'warehouse' => $q->warehouse,
            'available_qty' => $availableQty,
            'is_bundle' => $isBundle,
            'product_bundle_items' => $productBundleItems,
            'dri_name' => $q->dri_name,
            'stock_reservation' => $stockReservationDetails,
            'uom_conversion' => $uomConversion,
            'reference' => 'Picking Slip',
            'docstatus' => $q->docstatus
        ];

        $isStockEntry = false;
        return view('deliveries_modal_content', compact('data', 'isStockEntry'));
    }

    public function submitSalesReturn(Request $request)
    {
        try {
            $childId = $request->child_tbl_id;
            $stockEntry = StockEntry::with('items')->whereHas('items', function ($item) use ($childId) {
                return $item->where('name', $childId);
            })->first();

            $now = now();

            if (!$stockEntry) {
                throw new Exception('Record not found');
            }

            $stockEntry->qty = (float) $stockEntry->qty;

            $stockEntryItem = collect($stockEntry->items)->where('name', $childId)->first();
            $itemCode = $stockEntryItem->item_code;
            $itemDetails = Item::find($itemCode);

            if (in_array($stockEntry->item_status, ['Issued', 'Returned'])) {
                throw new Exception("Item already $stockEntry->item_status");
            }

            if ($stockEntry->docstatus == 1) {
                throw new Exception('Item already issued.');
            }

            if (!$itemDetails) {
                throw new Exception("Item <b>$itemCode</b> not found.");
            }

            if ($itemDetails->is_stock_item == 0) {
                throw new Exception("Item <b>$itemCode</b> is not a stock item.");
            }

            if ($request->barcode != $itemCode) {
                throw new Exception("Invalid barcode for <b>$itemCode</b>.");
            }

            if ($request->qty <= 0) {
                throw new Exception('Qty cannot be less than or equal to 0.');
            }

            foreach ($stockEntry->items as $item) {  // Update only the specific child
                if ($item->name === $childId) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Returned';
                    $item->transfer_qty = $request->qty;
                    $item->qty = $request->qty;
                    $item->issued_qty = $request->qty;
                    $item->validate_item_code = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                    break;
                }
            }

            $pendingItems = collect($stockEntry->items)->where('name', $childId)->where('status', 'For Checking')->count();
            if ($pendingItems <= 0) {
                $stockEntry->item_status = 'Returned';
            }

            $response = $this->erpPut('Stock Entry', $stockEntry->name, collect($stockEntry)->toArray());
            if (!Arr::has($response, 'data')) {
                if (Arr::has($response, 'exc_type') && $response['exc_type'] == 'TimestampMismatchError') {
                    $stockEntryData = $this->erpGet('Stock Entry', $stockEntry->name);
                    $stockEntryData = $stockEntryData['data'] ?? null;
                    if ($stockEntryData) {
                        $stockEntryData['items'] = collect($stockEntryData['items'])->map(function ($item) use ($request, $now, $childId) {
                            $item['qty'] = (float) ($item['qty'] ?? 0);
                            if (($item['name'] ?? '') === $childId) {
                                $item['session_user'] = Auth::user()->wh_user;
                                $item['status'] = 'Returned';
                                $item['transfer_qty'] = $request->qty;
                                $item['qty'] = (float) $request->qty;
                                $item['issued_qty'] = $request->qty;
                                $item['validate_item_code'] = $request->barcode;
                                $item['date_modified'] = $now->toDateTimeString();
                            }
                            return $item;
                        })->toArray();
                        $pendingItems = collect($stockEntryData['items'])->where('name', '!=', $childId)->where('status', 'For Checking')->count();
                        if ($pendingItems <= 0) {
                            $stockEntryData['item_status'] = 'Returned';
                        }
                        $response = $this->erpPut('Stock Entry', $stockEntryData['name'], $stockEntryData);
                    }
                }
                if (!Arr::has($response, 'data')) {
                    $err = data_get($response, 'exception', 'An error occured while updating Stock Entry');
                    throw new Exception($err);
                }
            }

            return ApiResponse::success("Item <b>$itemCode</b> has been returned");
        } catch (Exception $e) {
            Log::error('MainController returnItem failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::failure($e->getMessage());
        }
    }

    public function update_pending_ste_item_status()
    {
        DB::beginTransaction();
        try {
            $forCheckingSte = StockEntry::query()
                ->where('item_status', 'For Checking')
                ->where('docstatus', 0)
                ->select('name', 'transfer_as', 'receive_as')
                ->get();

            $itemStatus = null;
            foreach ($forCheckingSte as $ste) {
                $itemsForChecking = StockEntryDetail::query()
                    ->where('parent', $ste->name)
                    ->where('status', 'For Checking')
                    ->exists();

                if (!$itemsForChecking) {
                    if ($ste->receive_as == 'Sales Return') {
                        StockEntry::query()->where('name', $ste->name)->where('docstatus', 0)->update(['item_status' => 'Returned']);
                    } else {
                        $itemStatus = ($ste->transfer_as == 'For Return') ? 'Returned' : 'Issued';
                        StockEntry::query()->where('name', $ste->name)->where('docstatus', 0)->update(['item_status' => $itemStatus]);
                    }
                }
            }

            DB::commit();

            return $itemStatus;
        } catch (Exception $e) {
            DB::rollback();
        }
    }

    public function update_pending_ps_item_status($id)
    {
        try {
            $itemsForChecking = PackingSlipItem::query()->where('parent', $id)->where('status', 'For Checking')->exists();

            if (!$itemsForChecking) {
                $this->erpPut('Packing Slip', $id, ['item_status' => 'Issued', 'docstatus' => 1]);
            }

            return ['success' => 1, 'message' => 'Packing Slips updated!'];
        } catch (Exception $e) {
            Log::error('MainController checkoutPickingSlip failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => 0, 'message' => $e->getMessage()];
        }
    }

    public function getAthenaTransactions(Request $request, $itemCode)
    {
        $userGroup = Auth::user()->user_group;

        $reqWhUser = str_replace('null', '', (string) $request->wh_user);
        $reqWhUser = $reqWhUser !== '' ? $reqWhUser : null;
        $reqSrcWh = str_replace('null', '', (string) $request->src_wh);
        $reqSrcWh = $reqSrcWh !== '' ? $reqSrcWh : null;
        $reqTrgWh = str_replace('null', '', (string) $request->trg_wh);
        $reqTrgWh = $reqTrgWh !== '' ? $reqTrgWh : null;
        $reqAthDates = str_replace('null', '', (string) $request->ath_dates);
        $reqAthDates = $reqAthDates !== '' ? $reqAthDates : null;

        $logs = AthenaTransaction::query()
            ->where('item_code', $itemCode)
            ->where('status', 'Issued')
            ->when($reqWhUser, function ($query) use ($request) {
                return $query->where('warehouse_user', $request->wh_user);
            })
            ->when($reqSrcWh, function ($query) use ($request) {
                return $query->where('source_warehouse', $request->src_wh);
            })
            ->when($reqTrgWh, function ($query) use ($request) {
                return $query->where('target_warehouse', $request->trg_wh);
            })
            ->when($reqAthDates, function ($query) use ($request) {
                $dates = explode(' to ', $request->ath_dates);
                $from = Carbon::parse($dates[0]);
                $to = Carbon::parse($dates[1])->endOfDay();
                return $query->whereBetween('transaction_date', [$from, $to]);
            })
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);

        $productionLogs = collect($logs->items())->groupBy('purpose');
        $productionLogs = Arr::get($productionLogs, 'Material Transfer for Manufacture', []);

        $productionOrders = StockEntry::query()->whereIn('name', collect($productionLogs)->pluck('reference_parent'))->pluck('work_order', 'name');

        $steNames = array_column($logs->items(), 'reference_parent');

        $steRemarks = StockEntry::query()
            ->whereIn('name', $steNames)
            ->select('purpose', 'transfer_as', 'receive_as', 'issue_as', 'name')
            ->get();

        $steRemarks = collect($steRemarks)->groupBy('name')->toArray();

        // Batch load reference docs by type to avoid N+1
        $psRef = ['Packing Slip', 'Picking Slip'];
        $refsByType = [];
        foreach ($logs as $row) {
            $referenceType = (in_array($row->reference_type, $psRef)) ? 'Packing Slip' : $row->reference_type;
            $refsByType[$referenceType] = ($refsByType[$referenceType] ?? []) + [$row->reference_parent => true];
        }
        $referencesByType = [];
        foreach ($refsByType as $referenceType => $ids) {
            $ids = array_keys($ids);
            $referencesByType[$referenceType] = DB::table('tab' . $referenceType)
                ->whereIn('name', $ids)
                ->get()
                ->keyBy('name');
        }

        $list = [];
        foreach ($logs as $row) {
            $referenceType = (in_array($row->reference_type, $psRef)) ? 'Packing Slip' : $row->reference_type;

            $existingReferenceNo = data_get($referencesByType, "{$referenceType}.{$row->reference_parent}");
            if (!$existingReferenceNo) {
                $status = 'DELETED';
            } else {
                if ($existingReferenceNo->docstatus == 2 or $row->docstatus == 2) {
                    $status = 'CANCELLED';
                } elseif ($existingReferenceNo->docstatus == 0) {
                    $status = 'DRAFT';
                } else {
                    $status = 'SUBMITTED';
                }
            }

            $remarks = Arr::get($steRemarks, $row->reference_parent, []);
            if (count($remarks) > 0) {
                $firstRemark = $remarks[0];
                $purpose = data_get($firstRemark, 'purpose');
                if ($purpose == 'Material Issue') {
                    $remarks = data_get($firstRemark, 'issue_as');
                } elseif ($purpose == 'Material Transfer') {
                    $remarks = data_get($firstRemark, 'transfer_as');
                } elseif ($purpose == 'Material Receipt') {
                    $remarks = data_get($firstRemark, 'receive_as');
                } elseif ($purpose == 'Material Transfer for Manufacture') {
                    $remarks = 'Materials Withdrawal';
                } else {
                    $remarks = '-';
                }
            } else {
                $remarks = null;
            }

            $list[] = [
                'reference_name' => $row->reference_name,
                'item_code' => $row->item_code,
                'reference_parent' => $row->reference_parent,
                'source_warehouse' => $row->source_warehouse,
                'target_warehouse' => $row->target_warehouse,
                'reference_type' => $row->purpose,
                'issued_qty' => $row->issued_qty * 1,
                'reference_no' => $row->reference_no,
                'transaction_date' => $row->transaction_date,
                'production_order' => Arr::get($productionOrders, $row->reference_parent),
                'warehouse_user' => $row->warehouse_user,
                'status' => $status,
                'remarks' => $remarks
            ];
        }

        return view('tbl_athena_transactions', compact('list', 'logs', 'itemCode', 'userGroup'));
    }

    public function cancelIssuedItem(Request $request)
    {
        DB::beginTransaction();
        try {
            $now = now();
            switch ($request->reference) {
                case 'Stock Entry':
                    $q = StockEntryDetail::query()
                        ->from('tabStock Entry Detail as sted')
                        ->join('tabStock Entry as ste', 'ste.name', 'sted.parent')
                        ->where('sted.name', $request->name)
                        ->first();

                    if (!$q) {
                        return ApiResponse::failureLegacy('Stock Entry not found.');
                    }

                    if ($q->status == 'For Checking') {
                        return ApiResponse::failureLegacy('Item already cancelled.');
                    }

                    StockEntryDetail::query()->where('name', $request->name)->update([
                        'status' => 'For Checking',
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => $now->toDateTimeString()
                    ]);

                    if ($q->item_status == 'Issued') {
                        StockEntry::query()->where('name', $q->name)->update([
                            'item_status' => 'For Checking',
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user
                        ]);
                    }
                    break;
                case 'Delivery Note':
                    $q = DeliveryNote::query()
                        ->from('tabDelivery Note as dr')
                        ->join('tabDelivery Note Item as dri', 'dri.parent', 'dr.name')
                        ->where('dr.is_return', 1)
                        ->where('dr.docstatus', 0)
                        ->where('dri.name', $request->name)
                        ->select('dri.barcode_return', 'dri.name as c_name', 'dr.name', 'dr.customer', 'dri.item_code', 'dri.description', 'dri.warehouse', 'dri.qty', 'dri.against_sales_order', 'dr.dr_ref_no', 'dri.item_status', 'dri.stock_uom', 'dr.owner', 'dr.docstatus', 'dri.barcode', 'dri.parent', 'dri.session_user', 'dri.item_status')
                        ->first();

                    if (!$q) {
                        return ApiResponse::failureLegacy('Delivery Receipt not found.');
                    }

                    if ($q->item_status == 'For Return') {
                        return ApiResponse::failureLegacy('Item already cancelled.');
                    }

                    DeliveryNoteItem::query()->where('name', $request->name)->update([
                        'item_status' => 'For Return',
                        'modified_by' => Auth::user()->wh_user,
                        'modified' => $now->toDateTimeString()
                    ]);
                    break;
                default:
                    $q = PackingSlip::query()
                        ->from('tabPacking Slip as ps')
                        ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                        ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
                        ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
                        ->whereRaw(('dri.item_code = psi.item_code'))
                        ->where('psi.name', $request->name)
                        ->select('psi.name', 'psi.parent', 'psi.item_code', 'psi.description', 'ps.delivery_note', 'dri.warehouse', 'psi.qty', 'psi.barcode', 'psi.session_user', 'psi.stock_uom', 'psi.parent')
                        ->first();

                    if (!$q) {
                        return ApiResponse::failureLegacy('Delivery Receipt not found.');
                    }

                    if ($q->status == 'For Checking') {
                        return ApiResponse::failureLegacy('Item already cancelled.');
                    }

                    PackingSlipItem::query()->where('name', $request->name)->update([
                        'status' => 'For Checking',
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user
                    ]);

                    if ($q->item_status == 'Issued') {
                        PackingSlip::query()->where('name', $q->parent)->update([
                            'item_status' => 'For Checking',
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user
                        ]);
                    }
                    break;
            }

            AthenaTransaction::query()->where('reference_name', $request->name)->update([
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ]);

            DB::commit();
            return ApiResponse::successLegacy('Issued item(s) cancelled.');
        } catch (\Throwable $th) {
            Log::error('MainController cancelIssuedItem failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::rollback();
            return ApiResponse::failureLegacy('An error occured. Please try again.');
        }
    }

    public function cancelAthenaTransaction(Request $request)
    {
        DB::beginTransaction();
        try {
            $aTstatusUpdate = [
                'docstatus' => 2
            ];

            $sEstatusUpdate = [
                'item_status' => 'For Checking'
            ];

            $sEDstatusUpdate = [
                'status' => 'For Checking',
                'session_user' => '',
                'issued_qty' => 0,
                'date_modified' => null
            ];

            $pSIstatusUpdate = [
                'status' => 'For Checking',
                'session_user' => '',
                'barcode' => '',
                'date_modified' => null
            ];

            $ATcancel = AthenaTransaction::query()->where('reference_parent', $request->athena_transaction_number)->update($aTstatusUpdate);
            $SEcancel = StockEntry::query()->where('name', $request->athena_transaction_number)->update($sEstatusUpdate);
            $SEDcancel = StockEntryDetail::query()->where('parent', $request->athena_transaction_number)->update($sEDstatusUpdate);
            $pSstatusUpdate = PackingSlip::query()->where('name', $request->athena_transaction_number)->update($sEstatusUpdate);
            $pSIstatusUpdate = PackingSlipItem::query()->where('name', $request->athena_reference_name)->update($pSIstatusUpdate);

            DB::commit();

            return ApiResponse::success('<b>' . $request->athena_transaction_number . '</b> has been cancelled.', ['item_code' => $request->itemCode]);
        } catch (Exception $e) {
            Log::error('MainController cancelAthenaTransaction failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.');
        }
    }

    public function getStockLedger(Request $request, $itemCode)
    {
        $warehouseUser = [];
        if ($request->wh_user != '' and $request->wh_user != 'null') {
            $userQry = WarehouseUsers::query()->where('full_name', 'LIKE', '%' . $request->wh_user . '%')->orWhere('wh_user', 'LIKE', '%' . $request->wh_user . '%')->first();

            $warehouseUser = $userQry ? [$userQry->wh_user, $userQry->full_name] : [];
            $warehouseUser = $warehouseUser ? $warehouseUser : [$request->wh_user];
        }

        $logs = StockLedgerEntry::query()
            ->from('tabStock Ledger Entry as sle')
            ->where('sle.item_code', $itemCode)
            ->select(DB::raw('(SELECT GROUP_CONCAT(name) FROM `tabPacking Slip` where delivery_note = sle.voucher_no) as dr_voucher_no'))
            ->addSelect(DB::raw('
                (CASE
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(date_modified, modified) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) in ("Manufacture") THEN (SELECT modified FROM `tabStock Entry` where name = sle.voucher_no)
                    WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.date_modified, psi.modified) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                ELSE
                    sle.posting_date
                END) as ste_date_modified'))
            ->addSelect(DB::raw('
                (CASE
                    WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(session_user, modified_by) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                    WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.session_user, psi.modified_by) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                ELSE
                    sle.modified_by
                END) as ste_session_user'))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) as ste_purpose'))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(sales_order_no) FROM `tabStock Entry` where name = sle.voucher_no) as ste_sales_order'))
            ->addSelect(DB::raw('(SELECT GROUP_CONCAT(DISTINCT purchase_order) FROM `tabPurchase Receipt Item` where parent = sle.voucher_no and item_code = sle.item_code) as pr_voucher_no'))
            ->addSelect('sle.voucher_type', 'sle.voucher_no', 'sle.warehouse', 'sle.actual_qty', 'sle.qty_after_transaction', 'sle.posting_date')
            ->when($request->wh_user != '' and $request->wh_user != 'null', function ($query) use ($warehouseUser) {
                return $query->whereIn(DB::raw('
                    (CASE
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(session_user, modified_by) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                        WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT IFNULL(psi.session_user, psi.modified_by) FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                    ELSE
                        sle.modified_by
                    END)'), $warehouseUser);
            })
            ->when($request->erp_wh != '' and $request->erp_wh != 'null', function ($query) use ($request) {
                return $query->where('sle.warehouse', $request->erp_wh);
            })
            ->when($request->erp_d != '' and $request->erp_d != 'null', function ($query) use ($request) {
                $dates = explode(' to ', $request->erp_d);

                return $query->whereBetween(DB::raw('
                    (CASE
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) IN ("Material Transfer for Manufacture", "Material Transfer", "Material Issue") THEN (SELECT IFNULL(date_modified, modified) FROM `tabStock Entry Detail` where parent = sle.voucher_no and item_code = sle.item_code limit 1)
                        WHEN (SELECT GROUP_CONCAT(purpose) FROM `tabStock Entry` where name = sle.voucher_no) in ("Manufacture") THEN (SELECT modified FROM `tabStock Entry` where name = sle.voucher_no)
                        WHEN sle.voucher_type in ("Picking Slip", "Packing Slip", "Delivery Note") THEN (SELECT psi.date_modified FROM `tabPacking Slip` as ps join `tabPacking Slip Item` as psi on ps.name = psi.parent where ps.delivery_note = sle.voucher_no and item_code = sle.item_code limit 1)
                    ELSE
                        sle.posting_date
                    END)'), [Carbon::parse($dates[0]), Carbon::parse($dates[1])]);
            })
            ->orderBy('sle.posting_date', 'desc')
            ->orderBy('sle.posting_time', 'desc')
            ->orderBy('sle.name', 'desc')
            ->paginate(20);

        $pickingSlips = array_filter(collect($logs->items())->pluck('dr_voucher_no')->toArray());

        $overridedPs = DB::connection('mysql')
            ->table('tabPacking Slip Item')
            ->whereIn('parent', $pickingSlips)
            ->where('status', 'For Checking')
            ->distinct()
            ->pluck('parent')
            ->toArray();

        $list = [];
        foreach ($logs as $row) {
            if ($row->voucher_type == 'Delivery Note') {
                $voucherNo = $row->dr_voucher_no;
                $transaction = 'Picking Slip';
            } elseif ($row->voucher_type == 'Purchase Receipt') {
                $transaction = $row->voucher_type;
            } elseif ($row->voucher_type == 'Stock Reconciliation') {
                $transaction = $row->voucher_type;
                $voucherNo = $row->voucher_no;
            } else {
                $transaction = $row->ste_purpose;
                $voucherNo = $row->voucher_no;
            }

            if ($row->voucher_type == 'Delivery Note') {
                $refNo = $row->voucher_no;
            } elseif ($row->voucher_type == 'Purchase Receipt') {
                $voucherNo = $row->pr_voucher_no;
                $refNo = $voucherNo;
            } elseif ($row->voucher_type == 'Stock Entry') {
                $refNo = $row->ste_sales_order;
            } elseif ($row->voucher_type == 'Stock Reconciliation') {
                $refNo = $voucherNo;
            } else {
                $refNo = null;
            }

            $status = null;
            if (in_array($voucherNo, $overridedPs)) {
                $status = 'Override';
            }

            $dateModified = $row->ste_date_modified;
            $sessionUser = $row->ste_session_user;

            if ($dateModified and $dateModified != '--') {
                $dateModified = Carbon::parse($dateModified);
            }

            $list[] = [
                'voucher_no' => $voucherNo,
                'warehouse' => $row->warehouse,
                'transaction' => $transaction,
                'actual_qty' => $row->actual_qty * 1,
                'qty_after_transaction' => $row->qty_after_transaction * 1,
                'ref_no' => $refNo,
                'date_modified' => $dateModified,
                'session_user' => $sessionUser,
                'posting_date' => $row->posting_date,
                'status' => $status
            ];
        }

        return view('tbl_stock_ledger', compact('list', 'logs', 'itemCode'));
    }

    public function update_production_order_items($productionOrder)
    {
        if (!$productionOrder) {
            return;
        }

        $productionOrderItems = WorkOrderItem::where('parent', $productionOrder)->get();
        if ($productionOrderItems->isEmpty()) {
            return;
        }

        $itemCodes = $productionOrderItems->pluck('item_code')->unique()->values()->all();

        $transferredQtyByItem = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.work_order', $productionOrder)
            ->where('ste.purpose', 'Material Transfer for Manufacture')
            ->where('ste.docstatus', 1)
            ->whereIn('sted.item_code', $itemCodes)
            ->selectRaw('sted.item_code, sum(sted.qty) as qty')
            ->groupBy('sted.item_code')
            ->pluck('qty', 'item_code');

        $returnedQtyByItem = DB::connection('mysql')
            ->table('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.transfer_as', 'For Return')
            ->where('ste.work_order', $productionOrder)
            ->whereIn('sted.item_code', $itemCodes)
            ->where('ste.docstatus', 1)
            ->selectRaw('sted.item_code, sum(sted.qty) as qty')
            ->groupBy('sted.item_code')
            ->pluck('qty', 'item_code');

        foreach ($productionOrderItems as $row) {
            $transferredQty = (float) ($transferredQtyByItem[$row->item_code] ?? 0);
            $returnedQty = (float) ($returnedQtyByItem[$row->item_code] ?? 0);

            WorkOrderItem::where('parent', $productionOrder)
                ->where('item_code', $row->item_code)
                ->update(['transferred_qty' => $transferredQty, 'returned_qty' => $returnedQty]);
        }
    }

    public function dashboardData()
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $goodsInTransit = 0;
        if (in_array('Goods In Transit - FI', $allowedWarehouses->toArray())) {
            $feedbackedProductionOrdersMreq = DB::table('tabMaterial Request as so')
                ->join('tabMaterial Request Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.material_request', 'so.name')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)
                ->where('so.per_ordered', '<', 100)
                ->where('so.company', 'FUMACO Inc.')
                ->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.ordered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)
                ->where('wo.status', '!=', 'Stopped')
                ->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->pluck('wo.name')
                ->toArray();

            $feedbackedProductionOrders = DB::table('tabSales Order as so')
                ->join('tabSales Order Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.sales_order', 'so.name')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)
                ->where('so.per_delivered', '<', 100)
                ->where('so.company', 'FUMACO Inc.')
                ->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.delivered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)
                ->where('wo.status', '!=', 'Stopped')
                ->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->pluck('wo.name')
                ->toArray();

            $productionOrders = array_merge($feedbackedProductionOrdersMreq, $feedbackedProductionOrders);

            $goodsInTransit = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where([
                    'ste.docstatus' => 1,
                    'ste.purpose' => 'Manufacture',
                    'ste.company' => 'FUMACO Inc.',
                    'sted.status' => 'For Checking',
                    'sted.t_warehouse' => 'Goods in Transit - FI',
                ])
                ->whereIn('ste.work_order', $productionOrders)
                ->count();
        }

        $pendingStockEntries = StockEntry::query()
            ->from('tabStock Entry as se')
            ->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowedWarehouses)
            ->where('se.docstatus', 0)
            ->where('se.purpose', 'Material Issue')
            ->where('se.issue_as', 'Customer Replacement')
            ->count();

        return [
            'goods_in_transit' => $goodsInTransit,
            'p_replacements' => $pendingStockEntries,
        ];
    }

    public function set_reservation_as_expired()
    {
        return DB::table('tabStock Reservation')
            ->where('type', 'In-house')
            ->where('status', 'Active')
            ->whereDate('valid_until', '<=', now())
            ->update(['status' => 'Expired']);
    }

    public function getLowStockLevelItems(Request $request)
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $itemDefaultWarehouses = DB::table('tabItem Default')->whereIn('default_warehouse', $allowedWarehouses)->pluck('parent');

        $query = DB::table('tabItem as i')
            ->join('tabItem Reorder as ir', 'i.name', 'ir.parent')
            ->select('ir.name as id', 'i.item_code', 'i.description', 'ir.warehouse', 'ir.warehouse_reorder_level', 'i.stock_uom', 'ir.warehouse_reorder_qty', 'i.item_classification')
            ->whereIn('i.name', $itemDefaultWarehouses)
            ->get();

        $itemImages = ItemImages::query()->whereIn('parent', collect($query)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');

        $itemWarehousePairs = $query->map(fn($a) => [$a->item_code, $a->warehouse])->unique()->values()->toArray();
        $actualQtyMap = $this->getActualQtyBulk($itemWarehousePairs);

        $lowStockPairs = [];
        foreach ($query as $a) {
            $key = "{$a->item_code}-{$a->warehouse}";
            $actualQty = $actualQtyMap[$key] ?? 0;
            if ($actualQty <= $a->warehouse_reorder_level) {
                $lowStockPairs[] = ['item_code' => $a->item_code, 'warehouse' => $a->warehouse];
            }
        }

        $existingMrMap = [];
        if (!empty($lowStockPairs)) {
            $dateFrom = now()->subDays(30)->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');

            $existingMrs = DB::table('tabMaterial Request as mr')
                ->join('tabMaterial Request Item as mri', 'mr.name', 'mri.parent')
                ->where('mr.docstatus', '<', 2)
                ->where('mr.status', 'Pending')
                ->whereBetween('mr.transaction_date', [$dateFrom, $dateTo])
                ->where(function ($q) use ($lowStockPairs) {
                    foreach ($lowStockPairs as $pair) {
                        $q->orWhere(function ($sub) use ($pair) {
                            $sub
                                ->where('mri.item_code', $pair['item_code'])
                                ->where('mri.warehouse', $pair['warehouse']);
                        });
                    }
                })
                ->select('mr.name', 'mri.item_code', 'mri.warehouse')
                ->get();

            foreach ($existingMrs as $row) {
                $key = "{$row->item_code}-{$row->warehouse}";
                $existingMrMap[$key] = $row->name;
            }
        }

        $lowLevelStocks = [];
        foreach ($query as $a) {
            $key = "{$a->item_code}-{$a->warehouse}";
            $actualQty = $actualQtyMap[$key] ?? 0;

            if ($actualQty <= $a->warehouse_reorder_level) {
                $existingMr = $existingMrMap[$key] ?? null;

                $itemImage = Arr::get($itemImages, $a->item_code) ? '/img/' . $itemImages[$a->item_code] : '/icon/no_img.webp';
                $itemImage = $this->base64Image($itemImage);

                $lowLevelStocks[] = [
                    'id' => $a->id,
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_classification' => $a->item_classification,
                    'stock_uom' => $a->stock_uom,
                    'warehouse' => $a->warehouse,
                    'warehouse_reorder_level' => $a->warehouse_reorder_level,
                    'warehouse_reorder_qty' => $a->warehouse_reorder_qty,
                    'actual_qty' => $actualQty,
                    'image' => $itemImage,
                    'existing_mr' => $existingMr
                ];
            }
        }

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($lowLevelStocks);
        // Define how many items we want to be visible in each page
        $perPage = 6;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $lowLevelStocks = $paginatedItems;

        return view('tbl_low_level_stocks', compact('lowLevelStocks'));
    }

    public function getReservedItems(Request $request)
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $q = DB::table('tabStock Reservation as sr')
            ->join('tabItem as ti', 'sr.item_code', 'ti.name')
            ->groupby('sr.item_code', 'sr.warehouse', 'sr.description', 'sr.stock_uom', 'ti.item_classification')
            ->whereIn('sr.warehouse', $allowedWarehouses)
            ->whereNotIn('sr.status', ['Cancelled', 'Expired'])
            ->orderBy('sr.creation', 'desc')
            ->select('sr.item_code', DB::raw('sum(sr.reserve_qty) as qty'), 'sr.warehouse', 'sr.description', 'sr.stock_uom', 'ti.item_classification')
            ->get();

        $itemImages = ItemImages::query()->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent');

        $list = [];
        foreach ($q as $row) {
            // $itemImagePath = ItemImages::query()->where('parent', $row->item_code)->orderBy('idx', 'asc')->first();
            $image = Arr::get($itemImages, $row->item_code) ? '/img/' . $itemImages[$row->item_code] : '/icon/no_icon.png';
            $image = $this->base64Image($image);

            $list[] = [
                'item_code' => $row->item_code,
                'item_classification' => $row->item_classification,
                'description' => $row->description,
                'qty' => $row->qty * 1,
                'warehouse' => $row->warehouse,
                'stock_uom' => $row->stock_uom,
                'image' => $image
            ];
        }

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($list);
        // Define how many items we want to be visible in each page
        $perPage = 8;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $list = $paginatedItems;

        return view('reserved_items', compact('list'));  // reserved items
    }

    public function invAccuracyChart($year)
    {
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $chartData = [];
        $months = ['0', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthNo = $year == date('Y') ? date('m') : 12;
        for ($monthIndex = 1; $monthIndex <= $monthNo; $monthIndex++) {
            $invAudit = DB::table('tabMonthly Inventory Audit')
                ->whereIn('warehouse', $allowedWarehouses)
                ->select('name', 'item_classification', 'average_accuracy_rate', 'warehouse', 'percentage_sku')
                ->whereYear('from', $year)
                ->whereMonth('from', $monthIndex)
                ->where('docstatus', '<', 2)
                ->get();

            $average = collect($invAudit)->avg('average_accuracy_rate');

            $chartData[] = [
                'month_no' => $monthIndex,
                'month' => $months[$monthIndex],
                'audit_per_month' => $invAudit,
                'average' => round($average, 2),
            ];
        }

        return response()->json($chartData);
    }

    public function monthly_inventory_audit(Request $request)
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        $from = $request->date ? Carbon::parse(explode(' - ', $request->date)[0]) : now()->subDays(30);
        $to = $request->date ? Carbon::parse(explode(' - ', $request->date)[1]) : now();

        $invAudit = DB::table('tabMonthly Inventory Audit')
            ->whereIn('warehouse', $assignedConsignmentStore)
            ->where('docstatus', 1)
            ->whereDate('from', '>=', $from)
            ->whereDate('to', '<=', $to)
            ->when($request->search, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->store, function ($query) use ($request) {
                return $query->where('warehouse', $request->store);
            })
            ->select('name', 'warehouse', 'from', 'to', 'audited_by', 'employee_name', 'average_accuracy_rate')
            ->orderBy('creation', 'desc')
            ->paginate(20);

        return view('monthly_inv_audit', compact('invAudit', 'assignedConsignmentStore'));
    }

    public function returns()
    {
        return view('returns');
    }

    // /replacements
    public function replacements(Request $request)
    {
        if (!$request->arr) {
            return view('replacement');
        }

        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        $q = StockEntry::query()
            ->from('tabStock Entry as se')
            ->join('tabStock Entry Detail as sed', 'se.name', 'sed.parent')
            ->whereIn('sed.s_warehouse', $allowedWarehouses)
            ->where('se.docstatus', 0)
            ->where('se.purpose', 'Material Issue')
            ->where('se.issue_as', 'Customer Replacement')
            ->select('sed.status', 'sed.validate_item_code', 'se.sales_order_no', 'sed.parent', 'sed.name', 'sed.t_warehouse', 'sed.s_warehouse', 'sed.item_code', 'sed.description', 'sed.uom', 'sed.qty', 'sed.owner', 'se.material_request', 'se.creation', 'se.delivery_date', 'se.issue_as')
            ->orderByRaw("FIELD(sed.status, 'For Checking', 'Issued') ASC")
            ->get();

        $materialRequestNames = $q->where('material_request', '!=', null)->pluck('material_request')->unique()->filter()->values()->toArray();
        $salesOrderNos = $q->where('sales_order_no', '!=', null)->pluck('sales_order_no')->unique()->filter()->values()->toArray();
        $itemCodes = $q->pluck('item_code')->unique()->values()->toArray();
        $ownerIdentifiers = $q->pluck('owner')->unique()->filter()->values()->toArray();
        $warehouses = $q->pluck('s_warehouse')->unique()->filter()->values()->toArray();

        $mrCustomers = DB::table('tabMaterial Request')
            ->whereIn('name', $materialRequestNames)
            ->pluck('customer', 'name')
            ->toArray();

        $mrRefs = DB::table('tabMaterial Request')
            ->whereIn('name', $materialRequestNames)
            ->pluck('name', 'name')
            ->toArray();

        $soRecords = SalesOrder::query()
            ->whereIn('name', $salesOrderNos)
            ->get()
            ->keyBy('name');

        $partNosQuery = ItemSupplier::query()
            ->whereIn('parent', $itemCodes)
            ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))
            ->groupBy('parent')
            ->pluck('supplier_part_nos', 'parent')
            ->toArray();

        $ownerNames = User::query()
            ->whereIn('wh_user', $ownerIdentifiers)
            ->pluck('full_name', 'wh_user')
            ->toArray();

        $itemWarehousePairs = $q->map(fn($d) => [$d->item_code, $d->s_warehouse])->unique()->values()->toArray();
        $availableQtyMap = $this->getAvailableQtyBulk($itemWarehousePairs);

        $parentWarehouses = $this->getWarehouseParentsBulk($warehouses);

        $list = [];
        foreach ($q as $d) {
            $availableQty = $availableQtyMap["{$d->item_code}-{$d->s_warehouse}"] ?? 0;

            if ($d->material_request) {
                $refNo = $mrRefs[$d->material_request] ?? null;
                $customer = $mrCustomers[$d->material_request] ?? null;
            } else {
                $soRecord = $soRecords[$d->sales_order_no] ?? null;
                $refNo = $soRecord ? $soRecord->name : null;
                $customer = $soRecord ? $soRecord->customer : null;
            }

            $partNos = Arr::get($partNosQuery, $d->item_code, '');

            $owner = Arr::get($ownerNames, $d->owner, null);
            $parentWarehouse = Arr::get($parentWarehouses, $d->s_warehouse, null);

            $list[] = [
                'customer' => $customer,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $partNos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'available_qty' => $availableQty,
                'ref_no' => $refNo,
                'issue_as' => $d->issue_as,
                'parent_warehouse' => $parentWarehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'delivery_date' => ($d->delivery_date) ? Carbon::parse($d->delivery_date)->format('M-d-Y') : null,
                'delivery_status' => ($d->delivery_date) ? ((Carbon::parse($d->delivery_date) < now()) ? 'late' : null) : null
            ];
        }

        return response()->json(['records' => $list]);
    }

    public function feedbackedInTransit(Request $request)
    {
        if (!$request->arr) {
            return view('goods_in_transit');
        }

        $list = [];
        $user = Auth::user()->frappe_userid;
        $allowedWarehouses = $this->getAllowedWarehouseIds();

        if (in_array('Goods In Transit - FI', collect($allowedWarehouses)->toArray())) {
            // Get List of Transfered from In Transit to Finished Goods
            $stesToFg = StockEntry::query()->where('docstatus', '<', 2)->where('company', 'FUMACO Inc.')->where('from_warehouse', 'Goods in Transit - FI')->where('to_warehouse', 'Finished Goods - FI')->where('purpose', 'Material Transfer')->get()->groupBy('docstatus');

            $transferredToFg = data_get($stesToFg, 1) ? collect($stesToFg[1])->pluck('name')->unique() : [];
            $draftFg = [];
            if (Arr::has($stesToFg, 0)) {
                $draftFg = collect($stesToFg[0])->groupBy('reference_no')->map(function ($group) {
                    return $group[0];
                });
            }

            $salesOrders = DB::table('tabSales Order as so')
                ->join('tabSales Order Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.sales_order', 'so.name')
                ->join('tabStock Entry as ste', 'ste.work_order', 'wo.name')
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                ->where('ste.purpose', 'Manufacture')
                ->where('ste.docstatus', 1)
                ->where('ste.company', 'FUMACO Inc.')
                ->where('sted.t_warehouse', 'Goods in Transit - FI')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)
                ->where('so.per_delivered', '<', 100)
                ->where('so.company', 'FUMACO Inc.')
                ->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.delivered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)
                ->where('wo.status', '!=', 'Stopped')
                ->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->when($transferredToFg, function ($query) use ($transferredToFg) {
                    return $query->whereNotIn('soi.name', $transferredToFg);
                })
                ->select('so.name as so_name', 'so.customer', 'wo.sales_order as wo_so', 'wo.name as name', 'wo.owner', 'wo.production_item as production_item', 'wo.modified', 'soi.name as soi_name', 'soi.item_code as soi_item_code', 'so.status as so_status', 'soi.qty as so_qty', 'wo.produced_qty as qty', 'soi.delivered_qty', 'so.creation', 'soi.item_code', 'soi.description', 'soi.uom', 'sted.name as sted_name', 'sted.status as sted_status', 'sted.date_modified', 'sted.session_user', 'sted.qty as ste_qty', 'ste.name as ste_name');

            $q = DB::table('tabMaterial Request as so')
                ->join('tabMaterial Request Item as soi', 'soi.parent', 'so.name')
                ->join('tabWork Order as wo', 'wo.material_request', 'so.name')
                ->join('tabStock Entry as ste', 'ste.work_order', 'wo.name')
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                ->where('ste.purpose', 'Manufacture')
                ->where('ste.docstatus', 1)
                ->where('ste.company', 'FUMACO Inc.')
                ->where('sted.t_warehouse', 'Goods in Transit - FI')
                ->whereRaw('wo.production_item = soi.item_code')
                ->where('so.docstatus', 1)
                ->where('so.per_ordered', '<', 100)
                ->where('so.company', 'FUMACO Inc.')
                ->whereNotIn('so.status', ['Cancelled', 'Closed', 'Completed'])
                ->whereRaw('soi.ordered_qty < soi.qty')
                ->where('wo.produced_qty', '>', 0)
                ->where('wo.status', '!=', 'Stopped')
                ->where('wo.fg_warehouse', 'Goods in Transit - FI')
                ->when($transferredToFg, function ($query) use ($transferredToFg) {
                    return $query->whereNotIn('soi.name', $transferredToFg);
                })
                ->select('so.name as so_name', 'so.customer', 'wo.material_request as wo_so', 'wo.name as name', 'wo.owner', 'wo.production_item as production_item', 'wo.modified', 'soi.name as soi_name', 'soi.item_code as soi_item_code', 'so.status as so_status', 'soi.qty as so_qty', 'wo.produced_qty as qty', 'soi.ordered_qty as delivered_qty', 'so.creation', 'soi.item_code', 'soi.description', 'soi.uom', 'sted.name as sted_name', 'sted.status as sted_status', 'sted.date_modified', 'sted.session_user', 'sted.qty as ste_qty', 'ste.name as ste_name')
                ->unionAll($salesOrders)
                ->orderBy('modified')
                ->get();

            $productionOrders = collect($q)->pluck('name');

            $mesFeedbackLogs = DB::connection('mysql_mes')
                ->table('feedbacked_logs')
                ->whereIn('production_order', $productionOrders)
                ->where('status', 'Submitted')
                ->select('ste_no', DB::raw('CONCAT(transaction_date, " ", transaction_time) as feedback_date'), 'feedbacked_qty', 'created_by')
                ->orderByDesc('last_modified_at')
                ->get()
                ->groupBy('ste_no');

            $owners = User::query()->whereIn('email', collect($q)->pluck('owner'))->pluck('full_name', 'email');

            foreach ($q as $d) {
                $feedbackDate = Carbon::parse($d->modified)->format('M. d, Y - h:i A');
                $feedbackQty = 0;
                $feedbackBy = null;
                if (Arr::has($mesFeedbackLogs, $d->ste_name)) {
                    $feedbackDetails = $mesFeedbackLogs[$d->ste_name][0];
                    $feedbackDate = Carbon::parse($feedbackDetails->feedback_date)->format('M. d, Y - h:i A');
                    $feedbackQty = $feedbackDetails->feedbacked_qty;
                    $feedbackBy = $feedbackDetails->created_by;
                }

                $durationInTransit = $dateConfirmed = $receivedBy = null;

                $stedName = $d->sted_name;
                $stedStatus = $d->sted_status == 'For Checking' ? 'Pending to Receive' : $d->sted_status;
                if (in_array($stedStatus, ['Received', 'Issued'])) {
                    $dateConfirmed = Carbon::parse($d->date_modified);
                    $receivedBy = $d->session_user;
                    $durationInTransit = Carbon::parse($dateConfirmed)->diff(now())->days . ' Day(s)';
                }

                $partNos = ItemSupplier::query()->where('parent', $d->item_code)->pluck('supplier_part_no');
                $partNos = implode(', ', $partNos->toArray());

                $owner = Arr::get($owners, $d->owner, $d->owner);
                $owner = ucwords(str_replace('.', ' ', explode('@', $owner)[0]));
                $feedbackBy = ucwords(str_replace('.', ' ', explode('@', $feedbackBy)[0]));
                $receivedBy = ucwords(str_replace('.', ' ', explode('@', $receivedBy)[0]));

                $list[] = [
                    'item_code' => $d->item_code,
                    'description' => strip_tags($d->description),
                    'uom' => $d->uom,
                    'name' => $d->name,  // work/production order number
                    'reference' => $d->so_name,  // sales_order
                    'owner' => $owner,
                    'qty' => number_format($d->ste_qty),
                    'feedback_qty' => $feedbackQty,
                    'feedback_date' => $feedbackDate,
                    'feedback_by' => $feedbackBy,
                    'received_by' => $receivedBy,
                    'duration_in_transit' => $durationInTransit,
                    'date_confirmed' => $dateConfirmed ? $dateConfirmed->format('M. d, Y - h:i A') : null,
                    'status' => $stedStatus,
                    'sted_name' => $stedName,
                    'soi_name' => $d->soi_name,
                    'customer' => $d->customer,
                    'reference_to_fg' => $stedStatus == 'Issued' ? optional(data_get($draftFg, $d->soi_name))->name : null
                ];
            }
        }

        $list = collect($list)->sortBy([
            ['status', 'asc'],
            ['duration_in_transit', 'desc'],
        ])->values()->all();

        return response()->json(['records' => $list]);
    }

    // /in_transit/receive
    public function receiveTransitStocks(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $update = [
                'status' => 'Received',
                'modified' => now()->toDateTimeString(),
                'date_modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'session_user' => Auth::user()->wh_user
            ];

            StockEntryDetail::query()->where('name', $id)->update($update);

            DB::commit();
            return ApiResponse::successLegacy('Stocks Received!');
        } catch (\Throwable $th) {
            Log::error('MainController receiveTransitStocks failed', [
                'id' => $id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::rollback();
            return ApiResponse::failureLegacy('An error occured. Please try again.');
        }
    }

    // /in_transit/transfer
    public function transferTransitStocks($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $doctype = $request->reference_doctype == 'SO' ? 'tabStock Entry' : 'tabMaterial Request';
            $doctypeChild = $request->reference_doctype == 'SO' ? 'tabStock Entry Detail' : 'tabMaterial Request Item';

            $stockEntryDetail = DB::table("$doctype as ste")
                ->join("$doctypeChild as sted", 'sted.parent', 'ste.name')
                ->leftJoin('tabItem Images as img', function ($query) {
                    $query->on('img.parent', 'sted.item_code');
                })
                ->where('sted.name', $id)
                ->select('ste.*', 'sted.*', 'img.image_path as image')
                ->first();

            if (!$stockEntryDetail) {
                return ApiResponse::failureLegacy('Stock Entry not found.');
            }

            $image = $stockEntryDetail->image ? $stockEntryDetail->image : '/icon/no_img.png';

            $salesOrder = SalesOrder::query()->where('name', $stockEntryDetail->sales_order_no)->first();

            if (!$salesOrder) {
                return ApiResponse::failureLegacy('Sales Order ' . $stockEntryDetail->sales_order_no . ' not found.');
            }

            $response = $this->erpPost('Stock Entry', $body = [
                'doctype' => 'Stock Entry',
                'purpose' => 'Material Transfer',
                'stock_entry_type' => 'Material Transfer',
                'docstatus' => 0,
                'item_status' => 'For Checking',
                'company' => 'FUMACO Inc.',
                'order_from' => 'Other Reference',
                'transfer_as' => 'Internal Transfer',
                'reference_no' => $request->ref_no,
                'from_warehouse' => 'Goods in Transit - FI',
                'to_warehouse' => 'Finished Goods - FI',
                'owner' => Auth::user()->wh_user,
                'items' => [
                    [
                        'item_code' => $stockEntryDetail->item_code,
                        'qty' => $stockEntryDetail->qty,
                        'transfer_qty' => $stockEntryDetail->transfer_qty,
                        's_warehouse' => 'Goods in Transit - FI',
                        't_warehouse' => 'Finished Goods - FI'
                    ]
                ]
            ]);

            if (!Arr::has($response, 'data')) {
                return ApiResponse::failureLegacy('An error occured. Please try again.');
            }

            $data = $response['data'];

            DB::table($doctypeChild)->where('name', $id)->update([
                'status' => 'Issued',
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user
            ]);

            $emailData = [
                'id' => $data['name'],
                'uom' => $stockEntryDetail->uom,
                'user' => Auth::user()->wh_user,
                'image' => $image,
                'status' => 'For Checking',
                'purpose' => 'Material Transfer',
                'item_code' => $stockEntryDetail->item_code,
                'description' => $stockEntryDetail->description,
                'transfer_qty' => $stockEntryDetail->transfer_qty,
                'transaction_date' => now()->format('M. d, Y'),
                'source_warehouse' => $data['from_warehouse'],
                'target_warehouse' => $data['to_warehouse']
            ];

            $emailSent = 1;
            try {
                Mail::mailer('local_mail')->send('mail_template.transit_to_fg', $emailData, function ($message) use ($salesOrder) {
                    $message->to($salesOrder->owner);
                    $message->subject('AthenaERP - Material Transfer');
                });
            } catch (\Throwable $th) {
                $emailSent = 0;
            }

            DB::commit();

            return ApiResponse::successLegacy('Stock Entry created!', ['email_sent' => $emailSent]);
        } catch (Exception $th) {
            Log::error('MainController transferTransitStocks failed', [
                'id' => $id ?? null,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::rollback();
            return ApiResponse::failureLegacy('An error occured. Please try again.');
        }
    }

    public function createMaterialRequest($id)
    {
        DB::beginTransaction();
        try {
            $now = now();
            $latestMr = DB::table('tabMaterial Request')->max('name');
            $latestMrExploded = explode('-', $latestMr);
            $newId = $latestMrExploded[1] + 1;
            $newId = str_pad($newId, 5, '0', STR_PAD_LEFT);
            $newId = 'PREQ-' . $newId;

            $itemDetails = DB::table('tabItem as i')->join('tabItem Reorder as ir', 'i.name', 'ir.parent')->where('ir.name', $id)->first();

            if (!$itemDetails) {
                return ApiResponse::failure('Item <b>' . $id . '</b> not found.');
            }

            if ($itemDetails->is_stock_item == 0) {
                return ApiResponse::failure('Item  <b>' . $itemDetails->item_code . '</b> is not a stock item.');
            }

            $actualQty = $this->getActualQty($itemDetails->item_code, $itemDetails->warehouse);

            $mr = [
                'name' => $newId,
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'naming_series' => 'PREQ-',
                'title' => $itemDetails->material_request_type,
                'transaction_date' => $now->toDateTimeString(),
                'status' => 'Pending',
                'company' => 'FUMACO Inc.',
                'schedule_date' => now()->addDays(7)->format('Y-m-d'),
                'material_request_type' => $itemDetails->material_request_type,
                'purchase_request' => 'Local',
                'notes00' => 'Generated from AthenaERP',
            ];

            $mrItem = [
                'name' => 'ath' . uniqid(),
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'parent' => $newId,
                'parentfield' => 'items',
                'parenttype' => 'Material Request',
                'idx' => 1,
                'stock_qty' => abs($itemDetails->warehouse_reorder_qty),
                'qty' => abs($itemDetails->warehouse_reorder_qty),
                'actual_qty' => $actualQty,
                'schedule_date' => now()->addDays(7)->format('Y-m-d'),
                'item_name' => $itemDetails->item_name,
                'stock_uom' => $itemDetails->stock_uom,
                'warehouse' => $itemDetails->warehouse,
                'uom' => $itemDetails->stock_uom,
                'description' => $itemDetails->description,
                'conversion_factor' => 1,
                'item_code' => $itemDetails->item_code,
                'item_group' => $itemDetails->item_group,
            ];

            DB::table('tabMaterial Request')->insert($mr);
            DB::table('tabMaterial Request Item')->insert($mrItem);

            DB::commit();

            return ApiResponse::success('Material Request for <b>' . $itemDetails->item_code . '</b> has been created.');
        } catch (Exception $e) {
            Log::error('MainController createMaterialRequest failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.');
        }
    }

    public function consignmentWarehouses(Request $request)
    {
        return Warehouse::query()
            ->where('disabled', 0)
            ->where('is_group', 0)
            ->where('parent_warehouse', 'P2 Consignment Warehouse - FI')
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->orderBy('modified', 'desc')
            ->limit(10)
            ->get();
    }

    // /create_feedback
    public function createFeedback(Request $request)
    {
        DB::beginTransaction();
        try {
            $now = now();
            $productionOrder = $request->production_order;
            $existingSteTransfer = StockEntry::where('work_order', $productionOrder)
                ->where('purpose', 'Material Transfer for Manufacture')
                ->where('docstatus', 1)
                ->exists();

            if (!$existingSteTransfer) {
                throw new Exception('Materials Unavailable');
            }

            if ($request->fg_completed_qty <= 0) {
                throw new Exception('Please enter received qty');
            }

            $productionOrderDetails = WorkOrder::with('items')->find($productionOrder);
            $mesProductionOrderDetails = MESProductionOrder::find($productionOrder);

            if (!$productionOrderDetails || !$mesProductionOrderDetails) {
                throw new Exception("Production Order $productionOrder not found.");
            }

            $itemCode = $mesProductionOrderDetails->item_code;
            if ($itemCode != $request->barcode) {
                throw new Exception("Invalid barcode for $itemCode.");
            }

            $producedQty = $productionOrderDetails->produced_qty + $request->fg_completed_qty;

            if ($producedQty >= (int) $productionOrderDetails->qty && $productionOrderDetails->material_transferred_for_manufacturing > 0) {
                $pendingMtfmCount = StockEntry::where('work_order', $productionOrder)->where('purpose', 'Material Transfer for Manufacture')->where('docstatus', 0)->count();

                if ($pendingMtfmCount) {
                    throw new Exception('There are pending material request for issue.');
                }
            }

            $remainingForFeedback = $mesProductionOrderDetails->produced_qty - $mesProductionOrderDetails->feedback_qty;
            if ($remainingForFeedback < $request->fg_completed_qty) {
                throw new Exception("Received quantity cannot be greater than <b>$remainingForFeedback</b>");
            }

            $remarksOverride = $producedQty > $mesProductionOrderDetails->produced_qty ? 'Override' : null;

            if (!$mesProductionOrderDetails->is_stock_item) {
                return redirect('/create_bundle_feedback/' . $productionOrder . '/' . $request->fg_completed_qty);
            }

            $docstatus = $mesProductionOrderDetails->fg_warehouse == 'P2 - Housing Temporary - FI' ? 1 : 0;

            $productionOrderItems = app(ProductionController::class)->feedbackProductionOrderItems($productionOrder, $mesProductionOrderDetails->qty_to_manufacture, $request->fg_completed_qty);

            if (!$productionOrderItems) {
                throw new Exception('No items found.');
            }

            $stockEntryDetail = [];
            foreach ($productionOrderItems as $item) {
                $qty = $item['required_qty'];

                $childItemCode = $item['item_code'];

                $stockEntryDetail[] = [
                    'transfer_qty' => $qty,
                    'qty' => $qty,
                    'expense_account' => 'Cost of Goods Sold - FI',
                    's_warehouse' => $productionOrderDetails->wip_warehouse,
                    'cost_center' => 'Main - FI',
                    'item_code' => $childItemCode
                ];
            }

            $stockEntryDetail[] = [
                'expense_account' => 'Cost of Goods Sold - FI',
                'cost_center' => 'Main - FI',
                'item_code' => $productionOrderDetails->production_item,
                't_warehouse' => $mesProductionOrderDetails->fg_warehouse,
                'transfer_qty' => (float) $request->fg_completed_qty,
                'qty' => (float) $request->fg_completed_qty,
            ];

            $stockEntryData = [
                'naming_series' => 'STE-',
                'docstatus' => $docstatus,
                'fg_completed_qty' => (float) $request->fg_completed_qty,
                'to_warehouse' => $productionOrderDetails->fg_warehouse,
                'company' => 'FUMACO Inc.',
                'bom_no' => $productionOrderDetails->bom_no,
                'project' => $productionOrderDetails->project,
                'work_order' => $productionOrder,
                'purpose' => 'Manufacture',
                'stock_entry_type' => 'Manufacture',
                'material_request' => $productionOrderDetails->material_request,
                'item_status' => 'Issued',
                'sales_order_no' => $mesProductionOrderDetails->sales_order,
                'transfer_as' => 'Internal Transfer',
                'item_classification' => $productionOrderDetails->item_classification,
                'so_customer_name' => $mesProductionOrderDetails->customer,
                'order_type' => $mesProductionOrderDetails->classification,
                'items' => $stockEntryDetail
            ];

            // MES Transactions
            $manufacturedQty = $mesProductionOrderDetails->feedback_qty + $request->fg_completed_qty;
            $status = $manufacturedQty == $productionOrderDetails->qty ? 'Completed' : $mesProductionOrderDetails->status;

            $productionDataMes = [
                'last_modified_at' => $now->toDateTimeString(),
                'last_modified_by' => Auth::user()->wh_user,
                'feedback_qty' => $manufacturedQty,
                'remarks' => $remarksOverride
            ];

            if ($status == 'Completed') {
                $productionDataMes['status'] = 'Completed';
            }

            if ($remarksOverride == 'Override') {
                DB::connection('mysql')
                    ->table('job_ticket')
                    ->where('production_order', $productionOrderDetails->name)
                    ->where('status', '!=', 'Completed')
                    ->update([
                        'completed_qty' => $manufacturedQty,
                        'remarks' => $remarksOverride,
                        'status' => 'Completed',
                        'last_modified_by' => Auth::user()->wh_user,
                    ]);
            }

            DB::connection('mysql_mes')->table('production_order')->where('production_order', $productionOrderDetails->name)->update($productionDataMes);
            app(ProductionController::class)->insertProductionScrap($productionOrderDetails->name, $request->fg_completed_qty);

            $itemCode = $productionOrderDetails->production_item;
            $feedbackedTimelogs = [
                'production_order' => $mesProductionOrderDetails->production_order,
                'item_code' => $itemCode,
                'item_name' => $productionOrderDetails->item_name,
                'feedbacked_qty' => $request->fg_completed_qty,
                'from_warehouse' => $productionOrderDetails->wip_warehouse,
                'to_warehouse' => $mesProductionOrderDetails->fg_warehouse,
                'transaction_date' => $now->format('Y-m-d'),
                'transaction_time' => $now->format('G:i:s'),
                'created_at' => $now->toDateTimeString(),
                'created_by' => Auth::user()->wh_user,
            ];

            $feedbackLogId = DB::connection('mysql_mes')->table('feedbacked_logs')->insertGetId($feedbackedTimelogs, 'feedbacked_log_id');

            $stockEntryResponse = $this->erpPost('Stock Entry', $stockEntryData);
            if (!Arr::has($stockEntryResponse, 'data')) {
                $err = Arr::get($stockEntryResponse, 'exception', 'An error occured while creating stock entry');
                throw new Exception($err);
            }

            $stockEntryData = $stockEntryResponse['data'];

            $filteredItem = collect($stockEntryData['items'])->filter(function ($item) use ($itemCode) {
                return $item['item_code'] == $itemCode;
            })->first();

            DB::connection('mysql_mes')->table('feedbacked_logs')->where('feedbacked_log_id', $feedbackLogId)->update(['ste_no' => $stockEntryData['name']]);

            $values = [
                'name' => uniqid(date('mdY')),
                'reference_type' => 'Stock Entry',
                'reference_name' => $filteredItem['name'],
                'reference_parent' => $filteredItem['parent'],
                'item_code' => $filteredItem['item_code'],
                'qty' => $filteredItem['qty'],
                'barcode' => $filteredItem['item_code'],
                'transaction_date' => $now->toDateTimeString(),
                'warehouse_user' => Auth::user()->wh_user,
                'issued_qty' => $filteredItem['qty'],
                'remarks' => data_get($stockEntryData, 'remarks'),
                'source_warehouse' => data_get($filteredItem, 's_warehouse'),
                'target_warehouse' => data_get($filteredItem, 't_warehouse'),
                'description' => $filteredItem['description'],
                'reference_no' => $stockEntryData['sales_order_no'] ?? $stockEntryData['material_request'],
                'creation' => $now->toDateTimeString(),
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'uom' => $filteredItem['stock_uom'],
                'purpose' => 'Manufacture',
                'transaction_type' => 'Check In - Received'
            ];
            AthenaTransaction::query()->insert($values);

            DB::commit();
            return ApiResponse::success('Stock Entry has been created.');
        } catch (Exception $e) {
            Log::error('MainController feedProductionOrder failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            return ApiResponse::failure($e->getMessage(), 500);
        }
    }

    // /consignment_sales/{warehouse}
    public function consignmentSalesReport($warehouse, Request $request)
    {
        $monthNames = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $monthNameShort = [null, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
        $monthNow = (int) now()->format('m');
        $year = $request->year ? $request->year : now()->format('Y');
        $query = DB::table('tabConsignment Monthly Sales Report')
            ->where('status', '!=', 'Cancelled')
            ->where('fiscal_year', $year)
            ->where('warehouse', $warehouse)
            ->pluck('total_amount', 'month')
            ->toArray();

        $result = [];
        for ($i = 1; $i <= $monthNow; $i++) {
            $monthIndex = $monthNames[$i];
            $monthI = $monthNameShort[$i];
            $result[$monthI] = Arr::get($query, $monthIndex, 0);
        }

        return [
            'labels' => collect($result)->keys(),
            'data' => array_values($result)
        ];
    }

    public function purchaseRateHistory($itemCode)
    {
        $itemValuationRates = [];
        $list = PurchaseOrder::query()
            ->from('tabPurchase Order as po')
            ->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)
            ->where('poi.item_code', $itemCode)
            ->select('po.supplier', 'po.name', 'po.transaction_date', 'poi.base_rate', 'po.supplier_group', 'poi.qty', 'poi.stock_uom')
            ->orderBy('po.creation', 'desc')
            ->paginate(10);

        $imported = collect($list->items())->where('supplier_group', 'Imported')->toArray();
        $poNames = array_column($imported, 'name');
        if (count($poNames) > 0) {
            $purchaseReceipts = DB::table('tabPurchase Receipt as pr')
                ->join('tabPurchase Receipt Item as pri', 'pr.name', 'pri.parent')
                ->where('pr.docstatus', 1)
                ->whereIn('pri.purchase_order', $poNames)
                ->where('pri.item_code', $itemCode)
                ->pluck('pri.purchase_order', 'pr.name')
                ->toArray();

            $purchaseReceiptArr = array_keys($purchaseReceipts);

            $lastLandedCostVouchers = LandedCostVoucher::query()
                ->from('tabLanded Cost Voucher as a')
                ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                ->where('a.docstatus', 1)
                ->where('b.item_code', $itemCode)
                ->whereIn('b.receipt_document', $purchaseReceiptArr)
                ->pluck('b.valuation_rate', 'b.receipt_document');

            foreach ($lastLandedCostVouchers as $pr => $vr) {
                $po = $purchaseReceipts[$pr];
                $itemValuationRates[$po] = $vr;
            }
        }

        return view('tbl_item_purchase_history', compact('list', 'itemValuationRates'));
    }

    public function updateItemCost($itemCode, Request $request)
    {
        if ($request->price > 0) {
            Item::query()->where('name', $itemCode)->update(['custom_item_cost' => $request->price]);
        }

        $priceSettings = Singles::query()
            ->where('doctype', 'Price Settings')
            ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])
            ->pluck('value', 'field')
            ->toArray();

        $minimumPriceComputation = Arr::get($priceSettings, 'minimum_price_computation', 0);
        $standardPriceComputation = Arr::get($priceSettings, 'standard_price_computation', 0);
        $isTaxIncludedInRate = Arr::get($priceSettings, 'is_tax_included_in_rate', 0);

        $price = $request->price;

        $standardPrice = $price * $standardPriceComputation;
        $minPrice = $price * $minimumPriceComputation;
        if ($isTaxIncludedInRate) {
            $standardPrice = ($price * $standardPriceComputation) * 1.12;
        }

        $itemCost = '₱ ' . number_format($price, 2, '.', ',');
        $standardPrice = '₱ ' . number_format($standardPrice, 2, '.', ',');
        $minPrice = '₱ ' . number_format($minPrice, 2, '.', ',');

        return [
            'item_cost' => $itemCost,
            'standard_price' => $standardPrice,
            'min_price' => $minPrice
        ];
    }

    public function itemCostList(Request $request)
    {
        if (!in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            return redirect('/');
        }

        $itemGroups = ItemGroup::query()->where('parent_item_group', 'All Item Groups')->select('name', 'is_group')->get();

        return view('search_item_cost', compact('itemGroups'));
    }

    public function itemGroupPerParent($parent)
    {
        $itemGroups = ItemGroup::query()->where('parent_item_group', $parent)->selectRaw('name as id, name as text, is_group')->get()->toArray();

        return response()->json($itemGroups);
    }

    public function getParentItems(Request $request)
    {
        $itemGroup = $request->itemgroup;
        $itemGroupLevel1 = $request->itemgroup1;
        $itemGroupLevel2 = $request->itemgroup2;
        $itemGroupLevel3 = $request->itemgroup3;
        $itemGroupLevel4 = $request->itemgroup4;
        $itemGroupLevel5 = $request->itemgroup5;
        $variantOf = $request->variant_of;

        $templates = Item::query()
            ->where('has_variants', 1)
            ->enabled()
            ->stockItem()
            ->where('name', 'LIKE', '%' . $request->q . '%')
            ->when($itemGroup, function ($query) use ($itemGroup) {
                return $query->where('item_group', $itemGroup);
            })
            ->when($itemGroupLevel1, function ($query) use ($itemGroupLevel1) {
                return $query->where('item_group_level_1', $itemGroupLevel1);
            })
            ->when($itemGroupLevel2, function ($query) use ($itemGroupLevel2) {
                return $query->where('item_group_level_2', $itemGroupLevel2);
            })
            ->when($itemGroupLevel3, function ($query) use ($itemGroupLevel3) {
                return $query->where('item_group_level_3', $itemGroupLevel3);
            })
            ->when($itemGroupLevel4, function ($query) use ($itemGroupLevel4) {
                return $query->where('item_group_level_4', $itemGroupLevel4);
            })
            ->when($itemGroupLevel5, function ($query) use ($itemGroupLevel5) {
                return $query->where('item_group_level_5', $itemGroupLevel5);
            });

        if ($request->list) {
            $list = $templates
                ->when($variantOf, function ($query) use ($variantOf) {
                    return $query->where('name', $variantOf);
                })
                ->select('name', 'description')
                ->orderBy('name', 'asc')
                ->paginate(30);

            return view('tbl_item_templates', compact('list'));
        }

        $templateItems = $templates
            ->selectRaw('name as id, name as text')
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get();

        return response()->json($templateItems);
    }

    public function itemVariants($variantOf)
    {
        if (!in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            return redirect('/');
        }

        $itemVariants = Item::query()
            ->leafVariants()
            ->enabled()
            ->stockItem()
            ->where('variant_of', $variantOf)
            ->select('name', 'custom_item_cost')
            ->get()
            ->toArray();

        $itemCodes = array_column($itemVariants, 'name');

        $attributesQuery = ItemVariantAttribute::query()->whereIn('parent', $itemCodes)->select('parent', 'attribute', 'attribute_value')->orderBy('idx', 'asc')->get();

        $attributeNames = collect($attributesQuery)->map(function (object $attribute) {
            return $attribute->attribute;
        })->unique();

        $attributes = [];
        foreach ($attributesQuery as $row) {
            $attributes[$row->parent][$row->attribute] = $row->attribute_value;
        }

        $userDepartment = Auth::user()->department;
        $allowedDepartment = DepartmentWithPriceAccess::query()->pluck('department')->toArray();

        $prices = [];

        $lastPurchaseOrder = PurchaseOrder::query()
            ->from('tabPurchase Order as po')
            ->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)
            ->whereIn('poi.item_code', $itemCodes)
            ->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')
            ->orderBy('po.creation', 'desc')
            ->get();

        $lastLandedCostVoucher = LandedCostVoucher::query()
            ->from('tabLanded Cost Voucher as a')
            ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
            ->where('a.docstatus', 1)
            ->whereIn('b.item_code', $itemCodes)
            ->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')
            ->orderBy('transaction_date', 'desc')
            ->get();

        $lastPurchaseOrderRates = collect($lastPurchaseOrder)->groupBy('item_code')->toArray();
        $lastLandedCostVoucherRates = collect($lastLandedCostVoucher)->groupBy('item_code')->toArray();

        $websitePrices = ItemPrice::query()
            ->where('price_list', 'Website Price List')
            ->where('selling', 1)
            ->whereIn('item_code', $itemCodes)
            ->orderBy('modified', 'desc')
            ->pluck('price_list_rate', 'item_code')
            ->toArray();

        $priceSettings = Singles::query()
            ->where('doctype', 'Price Settings')
            ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])
            ->pluck('value', 'field')
            ->toArray();

        $minimumPriceComputation = Arr::get($priceSettings, 'minimum_price_computation', 0);
        $standardPriceComputation = Arr::get($priceSettings, 'standard_price_computation', 0);
        $isTaxIncludedInRate = Arr::get($priceSettings, 'is_tax_included_in_rate', 0);

        foreach ($itemVariants as $row) {
            $rowName = is_array($row) ? ($row['name'] ?? '') : $row->name;
            $rate = 0;
            $standardPrice = 0;
            $minPrice = 0;
            if (Arr::has($lastPurchaseOrderRates, $rowName)) {
                $poItem = $lastPurchaseOrderRates[$rowName][0] ?? null;
                $supplierGroup = $poItem ? (is_array($poItem) ? ($poItem['supplier_group'] ?? null) : $poItem->supplier_group) : null;
                if ($supplierGroup == 'Imported') {
                    $lcItem = $lastLandedCostVoucherRates[$rowName][0] ?? null;
                    $rate = $lcItem ? (is_array($lcItem) ? ($lcItem['valuation_rate'] ?? 0) : $lcItem->valuation_rate) : 0;
                } else {
                    $rate = $poItem ? (is_array($poItem) ? ($poItem['base_rate'] ?? 0) : $poItem->base_rate) : 0;
                }
            }
            // custom item cost
            if ($rate <= 0) {
                $customCost = is_array($row) ? ($row['custom_item_cost'] ?? 0) : $row->custom_item_cost;
                $rate = $customCost ?: 0;
            }

            $dRate = ($rate * $standardPriceComputation);
            $minPrice = ($rate * $minimumPriceComputation);
            if ($isTaxIncludedInRate) {
                $dRate = ($rate * $standardPriceComputation) * 1.12;
            }

            $standardPrice = Arr::get($websitePrices, $rowName, $dRate);

            $prices[$rowName] = [
                'rate' => $rate,
                'standard' => $standardPrice,
                'minimum' => $minPrice
            ];
        }

        return view('view_item_variants', compact('attributes', 'attributeNames', 'itemCodes', 'variantOf', 'prices'));
    }

    public function updateRate(Request $request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->price as $itemCode => $value) {
                if ($value && $value > 0) {
                    Item::query()->where('name', $itemCode)->update(['custom_item_cost' => $value]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Item prices has been updated.');
        } catch (Exception $e) {
            Log::error('MainController updateRate failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return redirect()->back()->with('error', 'There was a problem updating prices. Please try again.');
        }
    }

    public function importFromEcommerce()
    {
        return view('import_from_ecommerce');
    }

    public function importImages(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->hasFile('import_zip')) {
                $file = $request->file('import_zip');
                if (!in_array($file->getClientOriginalExtension(), ['zip', 'ZIP'])) {
                    return redirect()->back()->with('error', 'Only .zip files are allowed.');
                }

                if (!Storage::disk('public')->exists('/export/')) {
                    Storage::disk('public')->makeDirectory('/export/');
                }

                $file->storeAs('/public/export/', 'imported_athena_images.zip');

                $now = now();
                $zip = new ZipArchive;
                if (Storage::disk('public')->exists('/export/imported_athena_images.zip') and $zip->open(storage_path('/app/public/export/imported_athena_images.zip')) === TRUE) {
                    $zip->extractTo(storage_path('/app/public/export/'));
                    $zip->close();

                    Storage::disk('public')->delete('/export/imported_athena_images.zip');
                }

                $importedFiles = Storage::disk('public')->files('/export/');

                // Collect image files to save in DB
                $collectImagesArr = collect($importedFiles)->map(function ($filePath) {
                    $image = explode('/', $filePath)[1];

                    $exploded = explode('.', $image);
                    $imageName = Arr::get($exploded, 0);
                    $imageExtension = Arr::get($exploded, 1);

                    if (!in_array($imageExtension, ['webp', 'WEBP', 'zip', 'ZIP'])) {
                        return [
                            'item_code' => Arr::get(explode('-', $imageName), 1),
                            'image' => $image
                        ];
                    }
                });

                $collectImagesArr = $collectImagesArr->filter(function ($value) {
                    return !is_null($value);
                });

                $imagesArr = collect($collectImagesArr)->groupBy('item_code');
                $newImages = [];
                if ($imagesArr) {
                    $itemCodes = array_keys($imagesArr->toArray());

                    $collectAthenaImages = ItemImages::query()->whereIn('parent', $itemCodes)->get();
                    $athenaImages = collect($collectAthenaImages)->groupBy('parent');

                    foreach ($itemCodes as $itemCode) {
                        // Update order sequence of existing images
                        if (Arr::has($athenaImages, $itemCode)) {
                            $newIdx = count(Arr::get($imagesArr, $itemCode, []));
                            foreach ($athenaImages[$itemCode] as $i => $ath) {
                                /** @var object $ath */
                                $i = $i + 1;
                                ItemImages::query()->where('parent', $itemCode)->where('name', $ath->name)->update(['idx' => $newIdx + $i]);
                            }
                        }

                        // Save new images in DB
                        if (Arr::has($imagesArr, $itemCode)) {
                            foreach ($imagesArr[$itemCode] as $a => $image) {
                                if (empty($image['image'])) {
                                    continue;
                                }
                                $a = $a + 1;
                                $imageName = $image['image'];
                                $imagePrefix = explode('-', $imageName)[0];
                                $jpg = $imagePrefix . $a . '-' . str_replace($imagePrefix . '-', '', $imageName);
                                $webp = explode('.', $jpg)[0] . '.webp';

                                $newImages[] = [
                                    'name' => uniqid(),
                                    'creation' => $now->toDateTimeString(),
                                    'modified' => $now->toDateTimeString(),
                                    'modified_by' => Auth::user()->wh_user,
                                    'owner' => Auth::user()->wh_user,
                                    'idx' => $a,
                                    'from_ecommerce' => 1,
                                    'parent' => $image['item_code'],
                                    'parentfield' => 'item_images',
                                    'parenttype' => 'Item',
                                    'image_path' => $jpg
                                ];

                                if (Storage::disk('public')->exists('/export/' . $image['image']) and !Storage::disk('public')->exists('/img/' . $jpg)) {
                                    Storage::disk('public')->move('/export/' . $image['image'], '/img/' . $jpg);
                                }

                                if (Storage::disk('public')->exists('/export/' . explode('.', $image['image'])[0] . '.webp') and !Storage::disk('public')->exists('/img/' . $webp)) {
                                    Storage::disk('public')->move('/export/' . explode('.', $image['image'])[0] . '.webp', '/img/' . $webp);
                                }
                            }
                        }
                    }
                }

                ItemImages::query()->insert($newImages);
                DB::commit();
                return redirect()->back()->with('success', 'E-Commerce Image(s) Imported');
            }
            return redirect()->back();
        } catch (Exception $e) {
            DB::rollback();

            if (Storage::disk('public')->exists('/export/')) {
                Storage::disk('public')->deleteDirectory('/export/');
            }
            return redirect()->back()->with('error', 'An error occured. Please try again later.');
        }
    }

    public function downloadImage($webp)
    {
        $webpPath = storage_path("app/public/img/$webp");

        if (!file_exists($webpPath)) {
            return ApiResponse::failure('File not found', 404);
        }

        $image = imagecreatefromwebp($webpPath);

        if (!$image) {
            return ApiResponse::failure('Failed to convert the image', 500);
        }

        ob_start();
        imagejpeg($image);
        $jpgData = ob_get_contents();
        ob_end_clean();

        $name = explode('.', $webp)[0];

        return Response::make($jpgData, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "attachment; filename=$name.jpg"
        ]);
    }

    public function getCustomers(Request $request)
    {
        $term = $request->term;
        $customers = Customer::where('name', 'like', "%$term%")->select('name')->get();

        return response()->json($customers);
    }

    public function getErpProjects(Request $request)
    {
        $term = $request->term;
        $projects = Project::where('name', 'like', "%$term%")->select('name')->get();

        return response()->json($projects);
    }

    public function getCustomerAddress(Request $request)
    {
        $term = $request->term;
        $customer = $request->customer;

        $customerDetails = DB::table('tabCustomer as c')
            ->join('tabDynamic Link as d', 'd.link_name', 'c.name')
            ->join('tabAddress as a', 'a.name', 'd.parent')
            ->where('d.link_doctype', 'Customer')
            ->where('d.parenttype', 'Address')
            ->when($customer, function ($query) use ($customer) {
                return $query->where('c.name', $customer);
            })
            ->where('a.name', 'LIKE', "%$term%")
            ->select('a.address_title', 'a.name', 'a.address_line1', 'a.address_line2', 'a.city')
            ->limit(10)
            ->get();

        $customerDetails = collect($customerDetails)->map(function (object $customer) {
            $customer->address_display = trim($customer->address_line1);
            if ($customer->address_line2) {
                $customer->address_display .= ", $customer->address_line2";
            }

            if ($customer->city) {
                $customer->address_display .= ", $customer->city";
            }

            return $customer;
        });

        return response()->json($customerDetails);
    }

    public function getBranchWarehouses(Request $request)
    {
        return $this->consignmentWarehouses($request);
    }

    public function getManuals()
    {
        $files = collect(Storage::files('Manuals'))->map(function ($file) {
            return basename($file);
        });

        $consignmentPromodiserManuals = $files->filter(function ($file) {
            return str_contains($file, 'Consignment') && str_contains($file, 'Promodiser');
        });

        $consignmentSupervisorManuals = $files->filter(function ($file) {
            return str_contains($file, 'Consignment') && str_contains($file, 'Supervisors');
        });

        $genericManuals = $files->reject(function ($file) {
            return str_contains($file, 'Consignment');
        });

        return view('user_manual', compact('consignmentPromodiserManuals', 'consignmentSupervisorManuals', 'genericManuals'));
    }
}
