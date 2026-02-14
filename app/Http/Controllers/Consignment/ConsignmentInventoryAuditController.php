<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\Bin;
use App\Models\ConsignmentDamagedItems;
use App\Models\ConsignmentInventoryAuditReport;
use App\Models\ConsignmentInventoryAuditReportItem;
use App\Models\ConsignmentMonthlySalesReport;
use App\Models\ConsignmentSalesReportDeadline;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\StockEntry;
use App\Models\User;
use App\Services\CutoffDateService;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConsignmentInventoryAuditController extends Controller
{
    use ERPTrait, GeneralTrait;

    private function getCutoffDate($transactionDate): array
    {
        return app(CutoffDateService::class)->getCutoffPeriod($transactionDate);
    }

    private function getSalesAmount($start, $end, $warehouse): float
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $monthsArray = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $period = CarbonPeriod::create($start, $end);

        $includedDates = $includedMonths = [];
        foreach ($period as $date) {
            $includedMonths[] = $monthsArray[(int) Carbon::parse($date)->format('m')];
            $includedDates[] = Carbon::parse($date)->format('Y-m-d');
        }

        $salesReport = ConsignmentMonthlySalesReport::query()
            ->whereIn('fiscal_year', [$start->format('Y'), $end->format('Y')])
            ->whereIn('month', $includedMonths)
            ->when($warehouse, function ($query) use ($warehouse) {
                return $query->where('warehouse', $warehouse);
            })
            ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') ASC")
            ->get();

        $salesAmount = 0;
        foreach ($salesReport as $details) {
            $monthIndex = array_search($details->month, $monthsArray);
            $salesPerDay = collect(json_decode($details->sales_per_day));
            foreach ($salesPerDay as $day => $amount) {
                $saleDate = Carbon::parse($details->fiscal_year.'-'.$monthIndex.'-'.$day)->format('Y-m-d');
                if (in_array($saleDate, $includedDates)) {
                    $salesAmount += $amount;
                }
            }
        }

        return $salesAmount;
    }

    public function viewInventoryAuditForm($branch, $transactionDate)
    {
        $lastInventoryDate = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $branch)
            ->max('audit_date_to');

        if (! $lastInventoryDate) {
            $lastInventoryDate = BeginningInventory::query()
                ->where('status', 'Approved')
                ->where('branch_warehouse', $branch)
                ->max('transaction_date');
        }

        $inventoryAuditFrom = $lastInventoryDate ?: now()->format('Y-m-d');
        $inventoryAuditTo = $transactionDate;

        $dateFrom = Carbon::parse($inventoryAuditFrom);

        if ($dateFrom->startOfDay() < now()->startOfDay()) {
            $dateFrom = $dateFrom->addDay();
        }

        $duration = $dateFrom->format('F d, Y').' - '.Carbon::parse($inventoryAuditTo)->format('F d, Y');

        $start = $dateFrom->format('Y-m-d');
        $end = Carbon::parse($inventoryAuditTo)->format('Y-m-d');

        $items = DB::table('tabBin as b')
            ->join('tabItem as i', 'i.name', 'b.item_code')
            ->where('b.warehouse', $branch)
            ->where('b.consigned_qty', '>', 0)
            ->select('b.item_code', 'i.description', 'b.consignment_price as price', 'i.item_classification')
            ->orderBy('i.description', 'asc')
            ->get();

        $items = collect($items)->unique('item_code');
        $items = $items->sortBy('description');
        $itemCount = $items->count();

        $itemCodes = $items->pluck('item_code');

        $consignedStocks = Bin::whereIn('item_code', $itemCodes)->where('warehouse', $branch)->pluck('consigned_qty', 'item_code')->toArray();

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $itemImages = collect($itemImages)->groupBy('parent')->toArray();

        $itemClassification = collect($items)->groupBy('item_classification');

        return view('consignment.inventory_audit_form', compact('branch', 'transactionDate', 'items', 'itemImages', 'duration', 'inventoryAuditFrom', 'inventoryAuditTo', 'consignedStocks', 'itemClassification', 'itemCount'));
    }

    public function submitInventoryAuditForm(Request $request)
    {
        $data = $request->all();
        $stateBeforeUpdate = $itemsWithInsufficientStocks = [];
        $activityLogData = [];

        try {
            $cutoffDate = $this->getCutoffDate($data['transaction_date']);
            $periodFrom = $cutoffDate[0];
            $periodTo = $cutoffDate[1];

            $nullQtyItems = collect($data['item'])->where('qty', null);
            if ($nullQtyItems->isNotEmpty()) {
                throw new Exception('Please enter the qty of all items.');
            }

            if ($request->price && collect($request->price)->min() <= 0) {
                throw new Exception('Price cannot be less than or equal to 0');
            }

            $currentDateTime = now();

            $status = 'On Time';
            if ($currentDateTime->gt($periodTo)) {
                $status = 'Late';
            }

            $periodFrom = Carbon::parse($cutoffDate[0])->format('Y-m-d');
            $periodTo = Carbon::parse($cutoffDate[1])->format('Y-m-d');

            $iarExistingRecord = ConsignmentInventoryAuditReport::query()
                ->where('transaction_date', $data['transaction_date'])
                ->where('branch_warehouse', $data['branch_warehouse'])
                ->value('name');

            $method = $iarExistingRecord ? 'put' : 'post';

            $itemDetails = $data['item'];

            $binItems = Item::query()
                ->from('tabItem as p')
                ->join('tabBin as c', 'c.item_code', 'p.name')
                ->whereIn('p.item_code', array_keys($itemDetails))
                ->where('warehouse', $data['branch_warehouse'])
                ->select('c.consigned_qty', 'p.item_code', 'p.description', 'c.consignment_price as price', 'c.name as bin_id', 'c.modified', 'c.modified_by')
                ->get();

            $items = $soldArr = [];
            foreach ($binItems as $row) {
                $itemCode = $row->item_code;

                if (! Arr::exists($itemDetails, $itemCode)) {
                    throw new Exception("Item $itemCode not found.");
                }

                $itemDetail = $itemDetails[$itemCode];

                $qty = 0;
                if (isset($itemDetail['qty'])) {
                    $qty = preg_replace('/[^0-9 .]/', '', $itemDetail['qty']);
                }

                $itemDescription = $itemDetail['description'];

                $consignedQty = $row->consigned_qty;
                $price = $row->price;

                $soldQty = ($consignedQty - (float) $qty);

                if ($soldQty) {
                    $soldArr[] = [
                        'item' => $itemCode,
                        'sold_qty' => $soldQty,
                        'amount' => ((float) $price * (float) $soldQty),
                    ];
                }

                $activityLogData[$itemCode] = [
                    'consigned_qty_before_transaction' => (float) $consignedQty,
                    'sold_qty' => $soldQty,
                    'expected_qty_after_transaction' => (float) $qty,
                ];

                if ($consignedQty < (float) $qty) {
                    $itemsWithInsufficientStocks[] = $itemCode;
                }

                $binUpdate = [];

                if ($qty != $row->consigned_qty) {
                    $binUpdate['consigned_qty'] = (float) $qty;
                }

                if ($price <= 0 && isset($request->price[$itemCode])) {
                    $price = preg_replace('/[^0-9 .]/', '', $request->price[$itemCode]);
                    $binUpdate['consignment_price'] = $price;
                }

                if ($binUpdate) {
                    $stateBeforeUpdate['Bin'][$row->bin_id] = [
                        'consigned_qty' => $row->consigned_qty,
                        'consignment_price' => $row->price,
                        'modified' => $row->modified,
                        'modified_by' => $row->modified_by,
                    ];

                    $binResponse = $this->erpPut('Bin', $row->bin_id, $binUpdate);

                    if (! isset($binResponse['data'])) {
                        throw new Exception($binResponse['exception']);
                    }
                }

                $iarAmount = (float) $price * (float) $qty;

                $items[] = [
                    'item_code' => $itemCode,
                    'description' => $itemDescription,
                    'qty' => (float) $qty,
                    'price' => (float) $price,
                    'amount' => $iarAmount,
                    'available_stock_on_transaction' => $consignedQty,
                ];
            }

            if (count($itemsWithInsufficientStocks) > 0) {
                throw new Exception('There are items with insufficient stocks.');
            }

            $iarPayload = [
                'transaction_date' => $data['transaction_date'],
                'branch_warehouse' => $data['branch_warehouse'],
                'grand_total' => collect($items)->sum('amount'),
                'promodiser' => Auth::user()->full_name,
                'status' => $status,
                'cutoff_period_from' => $periodFrom,
                'cutoff_period_to' => $periodTo,
                'audit_date_from' => $data['audit_date_from'],
                'audit_date_to' => $data['audit_date_to'],
                'items' => $items,
            ];

            $iarResponse = $this->erpCall($method, 'Consignment Inventory Audit Report', $iarExistingRecord, $iarPayload);

            if (! isset($iarResponse['data'])) {
                throw new Exception($iarResponse['exception']);
            }

            $referenceName = $iarExistingRecord ?? ($iarResponse['data']['name'] ?? null);

            $updatedBin = Bin::whereIn('item_code', array_keys($iarPayload['items']))->where('warehouse', $data['branch_warehouse'])->pluck('consigned_qty', 'item_code');
            foreach ($updatedBin as $itemCode => $actualQty) {
                $activityLogData[$itemCode]['actual_qty_after_transaction'] = (float) $actualQty;
            }

            ActivityLog::insert([
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Inventory Audit Report of '.$data['branch_warehouse'].' for cutoff periods '.$periodFrom.' - '.$periodTo.'  has been created by '.Auth::user()->full_name.' at '.now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Inventory Audit Report',
                'reference_name' => $referenceName,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogData, true),
            ]);

            try {
                $emailData = [
                    'reference' => $referenceName,
                    'cutoff_period' => $periodFrom.' - '.$periodTo,
                    'audit_period' => $data['audit_date_from'].' - '.$data['audit_date_to'],
                    'branch_warehouse' => $data['branch_warehouse'],
                    'transaction_date' => $data['transaction_date'],
                ];

                Mail::send('mail_template.consignment_inventory_audit', $emailData, function ($message) {
                    $message->to(str_replace('.local', '.com', Auth::user()->wh_user));
                    $message->subject('AthenaERP - Inventory Audit Report');
                });
            } catch (\Throwable $th) {
            }

            return redirect()->back()->with([
                'success' => 'Record successfully updated',
                'total_qty_sold' => $soldArr ? collect($soldArr)->sum('sold_qty') : 0,
                'grand_total' => $soldArr ? collect($soldArr)->sum('amount') : 0,
                'branch' => $data['branch_warehouse'],
                'old_data' => $data,
                'transaction_date' => $data['transaction_date'],
            ]);
        } catch (\Throwable $th) {
            Log::error('ConsignmentInventoryAuditController submitInventoryAuditForm failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            $this->revertChanges($stateBeforeUpdate);

            return redirect()
                ->back()
                ->withInput($request->input())
                ->with(['old_data' => $data, 'item_codes' => $itemsWithInsufficientStocks])
                ->with('error', $th->getMessage());
        }
    }

    public function viewInventoryAuditList(Request $request)
    {
        $selectYear = [];
        for ($i = 2022; $i <= date('Y'); $i++) {
            $selectYear[] = $i;
        }

        $assignedConsignmentStores = [];
        $isPromodiser = Auth::user()->user_group == 'Promodiser';

        if ($isPromodiser) {
            $assignedConsignmentStores = AssignedWarehouses::query()
                ->where('parent', Auth::user()->frappe_userid)
                ->orderBy('warehouse', 'asc')
                ->distinct()
                ->pluck('warehouse');

            $storesWithBeginningInventory = BeginningInventory::query()
                ->where('status', 'Approved')
                ->whereIn('branch_warehouse', $assignedConsignmentStores)
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

            $end = now()->endOfDay();

            $salesReportDeadline = ConsignmentSalesReportDeadline::first();
            $cutoff1 = $salesReportDeadline ? $salesReportDeadline->{'1st_cutoff_date'} : 0;

            $firstCutoff = Carbon::createFromFormat('m/d/Y', $end->format('m').'/'.$cutoff1.'/'.$end->format('Y'))->endOfDay();

            if ($firstCutoff->gt($end)) {
                $end = $firstCutoff;
            }

            $cutoffDate = $this->getCutoffDate($end->endOfDay());
            $periodFrom = $cutoffDate[0];
            $periodTo = $cutoffDate[1];

            $pendingArr = [];
            foreach ($assignedConsignmentStores as $store) {
                $beginningInventoryTransactionDate = Arr::get($storesWithBeginningInventory, $store);
                $lastInventoryAuditDate = Arr::get($inventoryAuditPerWarehouse, $store);

                $duration = $start = null;
                if ($beginningInventoryTransactionDate) {
                    $start = Carbon::parse($beginningInventoryTransactionDate);
                }

                if ($lastInventoryAuditDate) {
                    $start = Carbon::parse($lastInventoryAuditDate);
                }

                if ($start) {
                    $lastAuditDate = $start;
                    $start = $start->startOfDay();

                    $isLate = 0;
                    $period = CarbonPeriod::create($start, '28 days', $end);
                    foreach ($period as $date) {
                        $date1 = $date->day($cutoff1);
                        if ($date1 >= $start && $date1 <= $end) {
                            $isLate++;
                        }
                    }

                    $duration = Carbon::parse($start)->addDay()->format('F d, Y').' - '.now()->format('F d, Y');
                    if (Carbon::parse($start)->addDay()->startOfDay()->lte(now()->startOfDay())) {
                        if ($lastAuditDate->endOfDay()->lt($end) && $beginningInventoryTransactionDate) {
                            $pendingArr[] = [
                                'store' => $store,
                                'beginning_inventory_date' => $beginningInventoryTransactionDate,
                                'last_inventory_audit_date' => $lastInventoryAuditDate,
                                'duration' => $duration,
                                'is_late' => $isLate,
                                'today' => now()->format('Y-m-d'),
                            ];
                        }
                    }
                }

                if (! $beginningInventoryTransactionDate || ! $lastInventoryAuditDate) {
                    $pendingArr[] = [
                        'store' => $store,
                        'beginning_inventory_date' => $beginningInventoryTransactionDate,
                        'last_inventory_audit_date' => null,
                        'duration' => $duration,
                        'is_late' => 0,
                        'today' => now()->format('Y-m-d'),
                    ];
                }
            }

            $pending = collect($pendingArr)->groupBy('store');

            return view('consignment.promodiser_inventory_audit_list', compact('pending', 'assignedConsignmentStores', 'selectYear'));
        }

        $currentCutoff = $this->getCutoffDate(now()->endOfDay());
        $previousCutoff = $this->getCutoffDate($currentCutoff[0]);

        $previousCutoffStart = $previousCutoff[0];
        $previousCutoffEnd = $previousCutoff[1];

        $previousCutoffDisplay = Carbon::parse($previousCutoffStart)->format('M. d, Y').' - '.Carbon::parse($previousCutoffEnd)->format('M. d, Y');
        $previousCutoffSales = $this->getSalesAmount(Carbon::parse($previousCutoffStart)->format('Y-m-d'), Carbon::parse($previousCutoffEnd)->format('Y-m-d'), null);

        $consignmentBranches = User::query()
            ->from('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->join('tabWarehouse as w', 'w.name', 'acw.warehouse')
            ->where('wu.user_group', 'Promodiser')
            ->where('w.is_group', 0)
            ->where('w.disabled', 0)
            ->distinct()
            ->pluck('w.name')
            ->count();

        $storesWithSubmittedReport = ConsignmentInventoryAuditReport::query()
            ->where('cutoff_period_from', Carbon::parse($previousCutoffStart)->format('Y-m-d'))
            ->where('cutoff_period_to', Carbon::parse($previousCutoffEnd)->format('Y-m-d'))
            ->distinct()
            ->pluck('branch_warehouse')
            ->count();

        $displayedData = [
            'recent_period' => $previousCutoffDisplay,
            'stores_submitted' => $storesWithSubmittedReport,
            'stores_pending' => $consignmentBranches - $storesWithSubmittedReport,
            'total_sales' => '₱ '.number_format($previousCutoffSales, 2),
        ];

        $promodisers = User::where('enabled', 1)->where('user_group', 'Promodiser')->pluck('full_name');

        return view('consignment.supervisor.view_inventory_audit', compact('assignedConsignmentStores', 'selectYear', 'displayedData', 'promodisers'));
    }

    public function getSubmittedInvAudit(Request $request)
    {
        $store = $request->store;
        $year = $request->year;

        $isPromodiser = Auth::user()->user_group == 'Promodiser';

        if ($isPromodiser) {
            $assignedConsignmentStores = AssignedWarehouses::query()
                ->where('parent', Auth::user()->frappe_userid)
                ->orderBy('warehouse', 'asc')
                ->distinct()
                ->pluck('warehouse')
                ->toArray();

            $query = ConsignmentInventoryAuditReport::query()
                ->when($store, function ($query) use ($store) {
                    return $query->where('branch_warehouse', $store);
                })
                ->when($year, function ($query) use ($year) {
                    return $query->whereYear('audit_date_to', $year);
                })
                ->whereIn('branch_warehouse', $assignedConsignmentStores)
                ->select('audit_date_from', 'audit_date_to', 'branch_warehouse', 'status', 'promodiser', 'transaction_date', 'cutoff_period_from', 'cutoff_period_to')
                ->groupBy('branch_warehouse', 'audit_date_to', 'audit_date_from', 'status', 'promodiser', 'transaction_date', 'cutoff_period_from', 'cutoff_period_to')
                ->orderBy('audit_date_from', 'desc')
                ->paginate(10);

            $result = [];
            foreach ($query as $row) {
                $result[$row->branch_warehouse][] = [
                    'audit_date_from' => $row->audit_date_from,
                    'audit_date_to' => $row->audit_date_to,
                    'status' => $row->status,
                    'promodiser' => $row->promodiser,
                    'date_submitted' => $row->transaction_date,
                ];
            }

            return view('consignment.tbl_submitted_inventory_audit', compact('result', 'query'));
        }

        $list = ConsignmentInventoryAuditReport::query()
            ->when($store, function ($query) use ($store) {
                return $query->where('branch_warehouse', $store);
            })
            ->when($year, function ($query) use ($year) {
                return $query->whereYear('audit_date_from', $year)->orWhereYear('audit_date_to', $year);
            })
            ->when($request->promodiser, function ($query) use ($request) {
                return $query->where('promodiser', $request->promodiser);
            })
            ->selectRaw('name, audit_date_from, audit_date_to, branch_warehouse, transaction_date, promodiser')
            ->orderBy('audit_date_to', 'desc')
            ->paginate(25);

        $auditItems = ConsignmentInventoryAuditReportItem::query()
            ->whereIn('parent', collect($list->items())->pluck('name'))
            ->selectRaw('SUM(qty) as total_item_qty, COUNT(item_code) as total_items, parent')
            ->groupBy('parent')
            ->get()
            ->groupBy('parent')
            ->toArray();

        $result = [];
        foreach ($list as $row) {
            $totalItems = $totalItemQty = 0;
            if (isset($auditItems[$row->name])) {
                $totalItems = $auditItems[$row->name][0]->total_items;
                $totalItemQty = $auditItems[$row->name][0]->total_item_qty;
            }

            $result[] = [
                'transaction_date' => $row->transaction_date,
                'audit_date_from' => $row->audit_date_from,
                'audit_date_to' => $row->audit_date_to,
                'branch_warehouse' => $row->branch_warehouse,
                'total_items' => $totalItems,
                'total_item_qty' => $totalItemQty,
                'promodiser' => $row->promodiser,
            ];
        }

        return view('consignment.supervisor.tbl_inventory_audit_history', compact('list', 'result'));
    }

    public function viewInventoryAuditItems($store, $from, $to, Request $request)
    {
        $isPromodiser = Auth::user()->user_group == 'Promodiser';

        if ($isPromodiser) {
            $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')->pluck('warehouse')->toArray();

            if (! in_array($store, $assignedConsignmentStores)) {
                return redirect('/')->with('error', 'No access to selected branch.');
            }
        }

        $list = ConsignmentInventoryAuditReport::query()
            ->join('tabConsignment Inventory Audit Report Item as ciar', 'tabConsignment Inventory Audit Report.name', 'ciar.parent')
            ->join('tabItem as i', 'i.name', 'ciar.item_code')
            ->where('branch_warehouse', $store)
            ->where('audit_date_from', $from)
            ->where('audit_date_to', $to)
            ->select('tabConsignment Inventory Audit Report.name as inventory_audit_id', 'tabConsignment Inventory Audit Report.*', 'i.*', 'ciar.*')
            ->get();

        $activityLogs = ActivityLog::whereIn('reference_name', collect($list)->pluck('inventory_audit_id'))->select('reference_name', 'data')->orderBy('creation', 'desc')->first();

        $activityLogsData = $activityLogs ? collect(json_decode($activityLogs->data)) : [];

        if ($list->isEmpty()) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        $firstRecord = $list->first();

        $previousInventoryAudit = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $store)
            ->whereDate('transaction_date', '<', $firstRecord->transaction_date)
            ->orderBy('transaction_date', 'desc')
            ->first();

        $start = $from;
        if ($previousInventoryAudit) {
            $start = Carbon::parse($previousInventoryAudit->transaction_date)->addDays(1)->format('Y-m-d');
        }

        $totalSales = $this->getSalesAmount(Carbon::parse($start)->startOfDay(), Carbon::parse($to)->endOfDay(), $store);

        $duration = Carbon::parse($from)->format('F d, Y').' - '.Carbon::parse($to)->format('F d, Y');

        $itemCodes = $list->pluck('item_code');

        $beginningInventory = BeginningInventory::query()
            ->from('tabConsignment Beginning Inventory as cb')
            ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
            ->where('cb.status', 'Approved')
            ->whereIn('cbi.item_code', $itemCodes)
            ->where('cb.branch_warehouse', $store)
            ->whereDate('cb.transaction_date', '<=', Carbon::parse($to)->endOfDay())
            ->select('cbi.item_code', 'cb.transaction_date', 'opening_stock')
            ->orderBy('cb.transaction_date', 'desc')
            ->get();

        $beginningInventory = collect($beginningInventory)->groupBy('item_code')->toArray();

        $invAudit = ConsignmentInventoryAuditReport::query()
            ->join('tabConsignment Inventory Audit Report Item as ciar', 'tabConsignment Inventory Audit Report.name', 'ciar.parent')
            ->where('branch_warehouse', $store)
            ->where('transaction_date', '<', $from)
            ->select('item_code', 'qty', 'transaction_date')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $invAudit = collect($invAudit)->groupBy('item_code')->toArray();

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image): string {
            return "img/$image";
        });

        $noImg = 'icon/no_img.png';

        $result = [];
        foreach ($list as $row) {
            $id = $row->item_code;

            $img = Arr::get($itemImages, $id, $noImg);
            $openingQty = data_get($invAudit, "{$id}.0.qty", 0);

            if (Arr::exists($invAudit, $id)) {
                $openingQty = $invAudit[$id][0]->qty;
            } else {
                $openingQty = data_get($beginningInventory, "{$id}.0.opening_stock", 0);
            }

            if (! $isPromodiser) {
                $description = explode(',', strip_tags($row->description));

                $descriptionPart1 = Arr::exists($description, 0) ? trim($description[0]) : null;
                $descriptionPart2 = Arr::exists($description, 1) ? trim($description[1]) : null;
                $descriptionPart3 = Arr::exists($description, 2) ? trim($description[2]) : null;
                $descriptionPart4 = Arr::exists($description, 3) ? trim($description[3]) : null;

                $displayedDescription = $descriptionPart1.', '.$descriptionPart2.', '.$descriptionPart3.', '.$descriptionPart4;
            } else {
                $displayedDescription = $row->description;
            }

            $result[] = [
                'item_code' => $id,
                'description' => $displayedDescription,
                'item_classification' => $row->item_classification,
                'price' => $row->price,
                'amount' => $row->amount,
                'img' => $img,
                'opening_qty' => number_format($openingQty),
                'previous_qty' => number_format($row->available_stock_on_transaction),
                'audit_qty' => number_format($row->qty),
                'sold_qty' => isset($activityLogsData[$row->item_code]) ? collect($activityLogsData[$row->item_code])['sold_qty'] : 0,
            ];
        }

        if ($isPromodiser) {
            $itemClassification = collect($result)->groupBy('item_classification');

            return view('consignment.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'itemClassification'));
        }

        $nextRecord = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $store)
            ->where('transaction_date', '>', $list[0]->transaction_date)
            ->where('name', '!=', $list[0]->name)
            ->orderBy('transaction_date', 'asc')
            ->first();

        $previousRecord = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $store)
            ->where('transaction_date', '<', $list[0]->transaction_date)
            ->where('name', '!=', $list[0]->name)
            ->orderBy('transaction_date', 'desc')
            ->first();

        $nextRecordLink = $previousRecordLink = null;
        $salesIncrease = true;
        $previousSalesRecord = 0;
        if ($nextRecord) {
            $nextRecordLink = '/view_inventory_audit_items/'.$store.'/'.$nextRecord->audit_date_from.'/'.$nextRecord->audit_date_to;
        }

        if ($previousRecord) {
            $previousRecordLink = '/view_inventory_audit_items/'.$store.'/'.$previousRecord->audit_date_from.'/'.$previousRecord->audit_date_to;

            $previousSalesRecord = $this->getSalesAmount(Carbon::parse($previousRecord->audit_date_from)->startOfDay(), Carbon::parse($previousRecord->audit_date_to)->endOfDay(), $store);

            $salesIncrease = $totalSales > $previousSalesRecord;
        }

        $steReceivedItems = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.t_warehouse', $store)
            ->where('sted.consignment_status', 'Received')
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, sted.consignment_date_received, sted.consignment_received_by, ste.delivery_date')
            ->orderBy('sted.consignment_date_received', 'desc')
            ->get();

        $steReturnedItems = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['For Return'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $store)
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, ste.creation, sted.t_warehouse')
            ->orderBy('sted.creation', 'desc')
            ->get();

        $steTransferredItems = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereBetween('sted.consignment_date_received', [$from, $to])
            ->whereIn('ste.transfer_as', ['Store Transfer'])
            ->whereIn('ste.item_status', ['For Checking', 'Issued'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where('sted.s_warehouse', $store)
            ->selectRaw('sted.item_code, sted.description, sted.transfer_qty, sted.basic_rate, sted.basic_amount, ste.name, sted.t_warehouse, ste.creation, sted.consignment_date_received, sted.consignment_received_by')
            ->orderBy('sted.creation', 'desc')
            ->get();

        $damagedItems = ConsignmentDamagedItems::query()
            ->where('branch_warehouse', $store)
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $receivedItems = [];
        foreach ($steReceivedItems as $row) {
            $receivedItems[$row->item_code][] = [
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                'delivery_date' => Carbon::parse($row->delivery_date)->format('M. d, Y'),
                'date_received' => Carbon::parse($row->consignment_date_received)->format('M. d, Y h:i A'),
                'received_by' => $row->consignment_received_by,
            ];
        }

        $returnedItems = [];
        foreach ($steReturnedItems as $row) {
            $returnedItems[$row->item_code][] = [
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                't_warehouse' => $row->t_warehouse,
            ];
        }

        $transferredItems = [];
        foreach ($steTransferredItems as $row) {
            $transferredItems[$row->item_code][] = [
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'amount' => $row->basic_amount,
                'price' => $row->basic_rate,
                'qty' => $row->transfer_qty * 1,
                'reference' => $row->name,
                't_warehouse' => $row->t_warehouse,
                'date_received' => Carbon::parse($row->consignment_date_received)->format('M. d, Y h:i A'),
                'received_by' => $row->consignment_received_by,
            ];
        }

        $damagedItemList = [];
        foreach ($damagedItems as $row) {
            $damagedItemList[$row->item_code][] = [
                'qty' => $row->qty * 1,
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'damage_description' => $row->damage_description,
                'stock_uom' => $row->stock_uom,
            ];
        }

        $promodisers = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $store)
            ->where('audit_date_from', $from)
            ->where('audit_date_to', $to)
            ->distinct()
            ->pluck('promodiser')
            ->toArray();

        $promodisers = implode(', ', $promodisers);

        return view('consignment.supervisor.view_inventory_audit_items', compact('list', 'store', 'duration', 'result', 'promodisers', 'receivedItems', 'previousRecordLink', 'nextRecordLink', 'salesIncrease', 'transferredItems', 'returnedItems', 'damagedItemList', 'totalSales'));
    }

    public function getPendingSubmissionInventoryAudit(Request $request)
    {
        $storeFilter = $request->store;

        $promodisersQuery = User::query()
            ->from('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->where('wu.user_group', 'Promodiser')
            ->selectRaw('GROUP_CONCAT(DISTINCT wu.full_name ORDER BY wu.full_name ASC SEPARATOR ",") as full_name, acw.warehouse')
            ->groupBy('acw.warehouse')
            ->pluck('full_name', 'warehouse')
            ->toArray();

        $storesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->when($storeFilter, function ($query) use ($storeFilter) {
                return $query->where('branch_warehouse', $storeFilter);
            })
            ->orderBy('branch_warehouse', 'asc')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $inventoryAuditPerWarehouse = ConsignmentInventoryAuditReport::query()
            ->join('tabConsignment Inventory Audit Report Item as ciar', 'tabConsignment Inventory Audit Report.name', 'ciar.parent')
            ->whereIn('branch_warehouse', array_keys($storesWithBeginningInventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = now()->endOfDay();

        $salesReportDeadline = ConsignmentSalesReportDeadline::first();
        $cutoff1 = $salesReportDeadline ? $salesReportDeadline->{'1st_cutoff_date'} : 0;

        $firstCutoff = Carbon::createFromFormat('m/d/Y', $end->format('m').'/'.$cutoff1.'/'.$end->format('Y'))->endOfDay();

        if ($firstCutoff->gt($end)) {
            $end = $firstCutoff;
        }

        $cutoffDate = $this->getCutoffDate(now()->endOfDay());
        $periodFrom = $cutoffDate[0];
        $periodTo = $cutoffDate[1];

        $pending = [];
        foreach (array_keys($storesWithBeginningInventory) as $store) {
            $beginningInventoryTransactionDate = Arr::get($storesWithBeginningInventory, $store);
            $lastInventoryAuditDate = Arr::get($inventoryAuditPerWarehouse, $store);

            $promodisers = Arr::get($promodisersQuery, $store);

            $duration = $start = null;
            if ($beginningInventoryTransactionDate) {
                $start = Carbon::parse($beginningInventoryTransactionDate);
            }

            if ($lastInventoryAuditDate) {
                $start = Carbon::parse($lastInventoryAuditDate);
            }

            if ($start) {
                $lastAuditDate = $start;
                $start = $start->startOfDay();

                $isLate = 0;
                $period = CarbonPeriod::create($start, '28 days', $end);
                foreach ($period as $date) {
                    $date1 = $date->day($cutoff1);
                    if ($date1 >= $start && $date1 <= $end) {
                        $isLate++;
                    }
                }

                $duration = Carbon::parse($start)->addDay()->format('F d, Y').' - '.now()->format('F d, Y');
                $check = Carbon::parse($start)->between($periodFrom, $periodTo);
                if (Carbon::parse($start)->addDay()->startOfDay()->lt(now()->startOfDay())) {
                    if ($lastAuditDate->endOfDay()->lt($end) && $beginningInventoryTransactionDate) {
                        if (! $check) {
                            $pending[] = [
                                'store' => $store,
                                'beginning_inventory_date' => $beginningInventoryTransactionDate,
                                'last_inventory_audit_date' => $lastInventoryAuditDate,
                                'duration' => $duration,
                                'is_late' => $isLate,
                                'promodisers' => $promodisers,
                            ];
                        }
                    }
                }
            }

            if (! $beginningInventoryTransactionDate) {
                $pending[] = [
                    'store' => $store,
                    'beginning_inventory_date' => $beginningInventoryTransactionDate,
                    'last_inventory_audit_date' => $lastInventoryAuditDate,
                    'duration' => $duration,
                    'is_late' => 0,
                    'promodisers' => $promodisers,
                ];
            }
        }

        return view('consignment.supervisor.tbl_pending_submission_inventory_audit', compact('pending'));
    }
}
