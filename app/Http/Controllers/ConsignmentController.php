<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\ActivityLog;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\BeginningInventoryItem;
use App\Models\Bin;
use App\Models\ConsignmentDamagedItems;
use App\Models\ConsignmentInventoryAuditReport;
use App\Models\ConsignmentInventoryAuditReportItem;
use App\Models\ConsignmentItemBarcode;
use App\Models\ConsignmentMonthlySalesReport;
use App\Models\ConsignmentSalesReportDeadline;
use App\Models\ConsignmentStockAdjustment;
use App\Models\ConsignmentStockAdjustmentItem;
use App\Models\ConsignmentStockEntry;
use App\Models\ConsignmentStockEntryDetail;
use App\Models\Customer;
use App\Models\ERPUser;
use App\Models\GLEntry;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\StockLedgerEntry;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseUsers;
use App\Services\CutoffDateService;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Exception;

class ConsignmentController extends Controller
{
    use GeneralTrait, ERPTrait;

    public function viewSalesReportList($branch, Request $request)
    {
        $months = collect(range(1, 12))->map(function ($m) {
            return date('F', mktime(0, 0, 0, $m, 1, date('Y')));
        });

        $currentYear = now()->format('Y');
        $currentMonth = now()->format('m');

        $years = range(2021, $currentYear);

        if ($request->ajax()) {
            $requestYear = $request->input('year', $currentYear);
            $salesPerMonth = Cache::remember("sales_report_{$requestYear}_{$branch}", 600, function () use ($requestYear, $branch) {
                return ConsignmentMonthlySalesReport::query()
                    ->where('fiscal_year', $requestYear)
                    ->where('warehouse', $branch)
                    ->get()
                    ->groupBy('month');
            });

            return view('consignment.tbl_sales_report', compact('months', 'salesPerMonth', 'currentMonth', 'currentYear', 'requestYear', 'branch'));
        }

        return view('consignment.view_sales_report_list', compact('years', 'currentYear', 'branch'));
    }

    public function salesReportDeadline(Request $request)
    {
        $salesReportDeadline = Cache::remember('sales_report_deadline', 600, function () {
            return ConsignmentSalesReportDeadline::first();
        });

        $salesReportDeadline = ConsignmentSalesReportDeadline::first();
        if ($salesReportDeadline) {
            $cutoffDay = $salesReportDeadline->{'1st_cutoff_date'};

            if ($cutoffDay && $request->has('month') && $request->has('year')) {
                try {
                    // Create the first cutoff date
                    $firstCutoff = Carbon::createFromFormat('m/d/Y', $request->month . '/' . $cutoffDay . '/' . $request->year)
                        ->format('F d, Y');

                    return 'Deadline: ' . $firstCutoff;
                } catch (Exception $e) {
                    // Handle invalid date format
                    return 'Invalid date format.';
                }
            }
        }

        return 'No deadline data available.';
    }

    public function checkBeginningInventory(Request $request)
    {
        // count beginnning inventory based on selected date and branch warehouse
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

    // /view_inventory_audit_form/{branch}/{transaction_date}
    public function viewInventoryAuditForm($branch, $transactionDate)
    {
        // get last inventory audit date

        $lastInventoryDate = ConsignmentInventoryAuditReport::query()
            ->where('branch_warehouse', $branch)
            ->max('audit_date_to');

        if (!$lastInventoryDate) {
            // get beginning inventory date if last inventory date is not null
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

        $duration = $dateFrom->format('F d, Y') . ' - ' . Carbon::parse($inventoryAuditTo)->format('F d, Y');

        $start = $dateFrom->format('Y-m-d');
        $end = Carbon::parse($inventoryAuditTo)->format('Y-m-d');

        $items = Bin::query()
            ->from('tabBin as b')
            ->join('tabItem as i', 'i.name', 'b.item_code')
            ->where('b.warehouse', $branch)
            ->where('b.consigned_qty', '>', 0)
            ->select('b.item_code', 'i.description', 'b.consignment_price as price', 'i.item_classification')  // ->union($soldOutItems)
            ->orderBy('i.description', 'asc')
            ->get();

        $items = collect($items)->unique('item_code');
        $items = $items->sortBy('description');
        $itemCount = count($items);

        $itemCodes = collect($items)->pluck('item_code');

        $consignedStocks = Bin::whereIn('item_code', $itemCodes)->where('warehouse', $branch)->pluck('consigned_qty', 'item_code')->toArray();

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->select('parent', 'image_path')->orderBy('idx', 'asc')->get();
        $itemImages = collect($itemImages)->groupBy('parent')->toArray();

        $itemClassification = collect($items)->groupBy('item_classification');

        return view('consignment.inventory_audit_form', compact('branch', 'transactionDate', 'items', 'itemImages', 'duration', 'inventoryAuditFrom', 'inventoryAuditTo', 'consignedStocks', 'itemClassification', 'itemCount'));
    }

    // /consignment_stores
    public function consignmentStores(Request $request)
    {
        if ($request->ajax()) {
            if ($request->has('assigned_to_me') && $request->assigned_to_me == 1) {  // only get warehouses assigned to the promodiser
                return AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->where('warehouse', 'LIKE', '%' . $request->q . '%')->select('warehouse as id', 'warehouse as text')->limit(20)->orderBy('warehouse', 'asc')->get();
            } else {  // get all warehouses
                return Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
                    ->where('is_group', 0)
                    ->where('disabled', 0)
                    ->where('name', 'LIKE', '%' . $request->q . '%')
                    ->select('name as id', 'warehouse_name as text')
                    ->limit(20)
                    ->orderBy('warehouse_name', 'asc')
                    ->get();
            }
        }
    }

    // /submit_inventory_audit_form
    public function submitInventoryAuditForm(Request $request)
    {
        $data = $request->all();
        $stateBeforeUpdate = $itemsWithInsufficientStocks = [];

        try {
            $cutoffDate = $this->getCutoffDate($data['transaction_date']);
            $periodFrom = $cutoffDate[0];
            $periodTo = $cutoffDate[1];

            // If user submits without qty input
            $nullQtyItems = collect($data['item'])->where('qty', null);
            if (count($nullQtyItems) > 0) {
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
                ->pluck('name')
                ->first();

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

                if (!Arr::exists($itemDetails, $itemCode)) {
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
                        'amount' => ((float) $price * (float) $soldQty)
                    ];
                }

                $activityLogData[$itemCode] = [
                    'consigned_qty_before_transaction' => (float) $consignedQty,
                    'sold_qty' => $soldQty,
                    'expected_qty_after_transaction' => (float) $qty
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
                        'modified_by' => $row->modified_by
                    ];

                    $binResponse = $this->erpPut('Bin', $row->bin_id, $binUpdate);

                    if (!isset($binResponse['data'])) {
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
                    'available_stock_on_transaction' => $consignedQty
                ];
            }

            if (count($itemsWithInsufficientStocks) > 0) {
                throw new Exception('There are items with insufficient stocks.');
            }

            $data = [
                'transaction_date' => $data['transaction_date'],
                'branch_warehouse' => $data['branch_warehouse'],
                'grand_total' => collect($items)->sum('amount'),
                'promodiser' => Auth::user()->full_name,
                'status' => $status,
                'cutoff_period_from' => $periodFrom,
                'cutoff_period_to' => $periodTo,
                'audit_date_from' => $data['audit_date_from'],
                'audit_date_to' => $data['audit_date_to'],
                'items' => $items
            ];

            $iarResponse = $this->erpCall($method, 'Consignment Inventory Audit Report', $iarExistingRecord, $data);

            if (!isset($iarResponse['data'])) {
                throw new Exception($iarResponse['exception']);
            }

            // get actual qty
            $updatedBin = Bin::whereIn('item_code', array_keys($data['items']))->where('warehouse', $data['branch_warehouse'])->pluck('consigned_qty', 'item_code');
            foreach ($updatedBin as $itemCode => $actualQty) {
                $activityLogData[$itemCode]['actual_qty_after_transaction'] = (float) $actualQty;
            }

            $logs = [
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Inventory Audit Report of ' . $data['branch_warehouse'] . ' for cutoff periods ' . $periodFrom . ' - ' . $periodTo . '  has been created by ' . Auth::user()->full_name . ' at ' . now()->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => now()->toDateTimeString(),
                'reference_doctype' => 'Inventory Audit Report',
                'reference_name' => $iarExistingRecord,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogData, true)
            ];

            ActivityLog::insert($logs);

            try {
                $emailData = [
                    'reference' => $iarExistingRecord,
                    'cutoff_period' => $periodFrom . ' - ' . $periodTo,
                    'audit_period' => $data['audit_date_from'] . ' - ' . $data['audit_date_to'],
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
                'transaction_date' => $data['transaction_date']
            ]);
        } catch (\Throwable $th) {
            Log::error('ConsignmentController submitInventoryAudit failed', [
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

    // /view_monthly_sales_form/{branch}/{date}
    public function viewMonthlySalesForm($branch, $date)
    {
        $days = Carbon::parse($date)->daysInMonth;
        $exploded = explode('-', $date);
        $month = $exploded[0];
        $year = $exploded[1];

        $report = ConsignmentMonthlySalesReport::query()
            ->where('fiscal_year', $year)
            ->where('month', $month)
            ->where('warehouse', $branch)
            ->first();

        $salesPerDay = $report ? collect(json_decode($report->sales_per_day)) : [];

        return view('consignment.tbl_sales_report_form', compact('branch', 'salesPerDay', 'month', 'year', 'report', 'days'));
    }

    public function submitMonthlySaleForm(Request $request)
    {
        DB::beginTransaction();
        try {
            $now = now();
            $salesPerDay = [];
            foreach ($request->day as $day => $detail) {
                $amount = preg_replace('/[^0-9 .]/', '', $detail['amount']);
                if (!is_numeric($amount)) {
                    return redirect()->back()->with('error', 'Amount should be a number.');
                }
                $salesPerDay[$day] = (float) $amount;
            }

            $transactionMonth = new Carbon('last day of ' . $request->month . ' ' . $request->year);
            $cutoffDate = $this->getCutoffDate($transactionMonth)[1];

            $status = isset($request->draft) && $request->draft ? 'Draft' : 'Submitted';

            $submissionStatus = $dateSubmitted = $submittedBy = null;
            if ($now->gt($cutoffDate) && $status == 'Submitted') {
                $submissionStatus = 'Late';
            }

            if ($status == 'Submitted') {
                $submittedBy = Auth::user()->wh_user;
                $emailData = [
                    'warehouse' => $request->branch,
                    'month' => $request->month,
                    'total_amount' => collect($salesPerDay)->sum(),
                    'remarks' => $request->remarks,
                    'year' => $request->year,
                    'status' => $status,
                    'submission_status' => $submissionStatus,
                    'date_submitted' => $dateSubmitted
                ];

                $recipient = str_replace('.local', '.com', Auth::user()->wh_user);
                $this->sendMail('mail_template.consignment_sales_report', $emailData, $recipient, 'AthenaERP - Sales Report');
            }

            $salesReport = ConsignmentMonthlySalesReport::where('fiscal_year', $request->year)->where('month', $request->month)->where('warehouse', $request->branch)->first();

            $reference = null;
            $method = 'post';
            if ($salesReport) {
                $reference = $salesReport->name;
                $method = 'put';
            }

            $data = [
                'warehouse' => $request->branch,
                'month' => $request->month,
                'sales_per_day' => json_encode($salesPerDay, true),
                'total_amount' => collect($salesPerDay)->sum(),
                'remarks' => $request->remarks,
                'fiscal_year' => $request->year,
                'status' => $status,
                'submission_status' => $submissionStatus,
                'date_submitted' => $dateSubmitted,
                'submitted_by' => $submittedBy
            ];

            $response = $this->erpCall($method, 'Consignment Monthly Sales Report', $reference, $data);

            if (!Arr::has($response, 'data')) {
                $err = data_get($response, 'exception', 'An error occured while submitting Sales Report');
                throw new Exception($err);
            }

            return redirect()->back()->with('success', 'Sales Report for the month of <b>' . $request->month . '</b> has been ' . ($salesReport ? 'updated!' : 'added!'));
        } catch (Exception $th) {
            Log::error('ConsignmentController submitSalesReport failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function getCutoffDate($transactionDate)
    {
        return app(CutoffDateService::class)->getCutoffPeriod($transactionDate);
    }

    public function salesReport(Request $request)
    {
        $explodedDate = explode(' to ', $request->daterange);
        $requestStartDate = data_get($explodedDate, 0, now()->startOfMonth());
        $requestEndDate = data_get($explodedDate, 1, now());

        $requestStartDate = Carbon::parse($requestStartDate);
        $requestEndDate = Carbon::parse($requestEndDate);

        $monthsArray = [null, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $period = CarbonPeriod::create($requestStartDate, $requestEndDate);

        $includedDates = $includedMonths = [];
        foreach ($period as $date) {
            $includedMonths[] = $monthsArray[(int) Carbon::parse($date)->format('m')];
            $includedDates[] = Carbon::parse($date)->format('Y-m-d');
        }

        $salesReport = ConsignmentMonthlySalesReport::query()
            ->whereIn('fiscal_year', [$requestStartDate->format('Y'), $requestEndDate->format('Y')])
            ->whereIn('month', $includedMonths)
            ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') ASC")
            ->get();

        $report = [];
        foreach ($salesReport as $details) {
            $monthIndex = array_search($details->month, $monthsArray);
            $salesPerDay = collect(json_decode($details->sales_per_day));
            foreach ($salesPerDay as $day => $amount) {
                $saleDate = Carbon::parse($details->fiscal_year . '-' . $monthIndex . '-' . $day)->format('Y-m-d');
                if (in_array($saleDate, $includedDates)) {
                    $report[$details->warehouse][$saleDate] = $amount;
                }
            }
        }

        $warehousesWithData = collect($salesReport)->pluck('warehouse');

        return view('consignment.supervisor.tbl_sales_report', compact('report', 'includedDates', 'warehousesWithData'));
    }

    // /inventory_items/{branch}
    public function inventoryItems($branch)
    {
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')->pluck('warehouse');

        $invSummary = Item::whereHas('bin', function ($bin) use ($branch) {
            $bin->where('warehouse', $branch)->where('consigned_qty', '>', 0);
        })
            ->with('defaultImage')
            ->with('bin', function ($bin) use ($branch) {
                $bin->where('warehouse', $branch)->where('consigned_qty', '>', 0)->select('name', 'warehouse', 'item_code', 'consigned_qty', 'consignment_price');
            })
            ->where('disabled', 0)
            ->where('is_stock_item', 1)
            ->select('name', 'item_code', 'description', 'stock_uom')
            ->orderBy('item_code')
            ->get();

        return view('consignment.promodiser_warehouse_items', compact('invSummary', 'branch', 'assignedConsignmentStores'));
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
            $bin = $binDetails[$inventory->branch_warehouse];

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
                    if (isset($items[$itemCode]) && $items[$itemCode][0]->status != 'For Approval') {  // Skip the approved/cancelled items
                        $skippedItems = collect($skippedItems)->merge($itemCode)->toArray();
                        continue;
                    }

                    $price = isset($prices[$itemCode]) ? preg_replace('/[^0-9 .]/', '', $prices[$itemCode][0]) * 1 : 0;
                    if (!$price) {
                        return redirect()->back()->with('error', 'Item price cannot be empty');
                    }

                    // Update Bin if approved
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

                    // Beginning Inventory
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
                // update item status' to cancelled
                BeginningInventoryItem::where('parent', $id)->update($updateValues);
            }

            if (isset($updateValues['price'])) {  // remove price/idx in updates array, parent table of beginning inventory does not have price/idx
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
            Log::error('ConsignmentController updateBeginningInventory failed', [
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
                // Update each item in Bin and Product Sold
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
                'data' => json_encode($activityLogsData, true)
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Beginning Inventory for ' . $inventory->branch_warehouse . ' was cancelled.');
        } catch (Exception $e) {
            Log::error('ConsignmentController cancelBeginningInventory failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

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

        $itemCodes = collect($deliveryReport->items())->flatMap(function ($stockEntry) {
            return $stockEntry->items->pluck('item_code');
        })->unique()->values();

        $targetWarehouses = collect($deliveryReport->items())->flatMap(function ($stockEntry) {
            return $stockEntry->items->pluck('t_warehouse');
        })->unique()->values();

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
        $itemImage = [];
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
            $itemImages = collect($itemImages)->map(function ($image) {
                return $this->base64Image("img/$image");
            });

            $noImg = $this->base64Image('icon/no_img.png');
            $itemImages['no_img'] = $noImg;

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

            // check the prices
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

            $defaultSourceWarehouse = isset($stockEntry['from_warehouse']) ? $stockEntry['from_warehouse'] : null;
            $defaultTargetWarehouse = isset($stockEntry['to_warehouse']) ? $stockEntry['to_warehouse'] : null;

            $steItems = $stockEntry['items'];
            // Get item codes, source warehouse/s, and target warehouse/s
            $itemCodes = collect($steItems)->pluck('item_code');
            $sourceWarehouses = collect($steItems)->pluck('s_warehouse')->push($defaultSourceWarehouse)->filter()->unique();
            $targetWarehouses = collect($steItems)->pluck('t_warehouse')->push($request->target_warehouse)->push($defaultTargetWarehouse)->filter()->unique();

            // get all items for each source and target warehouses
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

                // $sourceWarehouseConsignedQty = $sourceWarehouseDetails[$sourceWarehouse][$itemCode][0]->consigned_qty;

                // if($item['transfer_qty'] > $sourceWarehouseConsignedQty){
                //     return "Insufficient stocks for item $itemCode from $sourceWarehouse";
                // }

                return null;
            })->filter();

            if ((count($validateStocks) > 0)) {
                throw new Exception(collect($validateStocks)->first());
            }

            $now = now();

            // set the details of the json data for activity logs
            $data['details'] = [
                'reference' => $id,
                'transaction_date' => $now->toDateTimeString()
            ];

            $receivedItems = $expectedQtyAfterTransaction = $actualQtyAfterTransaction = [];

            foreach ($steItems as $item) {
                $itemCode = $item['item_code'];
                $srcBranch = $item['s_warehouse'] ?? $defaultSourceWarehouse;
                $targetBranch = $request->target_warehouse ?? ($item['t_warehouse'] ?? $defaultTargetWarehouse);

                // Source Warehouse
                $sourceBinDetails = $sourceWarehouseDetails[$srcBranch][$itemCode][0];
                $sourceConsignedQty = $sourceBinDetails->consigned_qty;
                $sourceBinId = $sourceBinDetails->name;

                $sourceUpdatedConsignedQty = $sourceConsignedQty > $item['transfer_qty'] ? $sourceConsignedQty - $item['transfer_qty'] : 0;

                $updateBin = ['consigned_qty' => $sourceUpdatedConsignedQty];

                // revert changes if there are errors
                $stateBeforeUpdate['Bin'][$sourceBinId] = $sourceBinDetails;

                // activity logs
                $data[$srcBranch][$itemCode]['quantity'] = [
                    'previous' => $sourceConsignedQty,
                    'transferred_qty' => $item['transfer_qty'],
                    'new' => $sourceUpdatedConsignedQty
                ];

                $binResponse = $this->erpPut('Bin', $sourceBinId, $updateBin);

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
                    $basicRate = isset($itemPrices[$itemCode]) ? $itemPrices[$itemCode] : $basicRate;
                }

                $targetUpdatedConsignedQty = $targetConsignedQty + $item['transfer_qty'];

                $updateBin = [
                    'consigned_qty' => $targetUpdatedConsignedQty,
                    'consignment_price' => $targetConsignmentPrice
                ];

                // activity logs
                $data[$targetBranch][$itemCode]['quantity'] = [
                    'previous' => $sourceConsignedQty,
                    'transferred_qty' => $item['transfer_qty'],
                    'new' => $sourceUpdatedConsignedQty
                ];

                if (isset($itemPrices[$itemCode])) {
                    $updateBin['consignment_price'] = $basicRate;

                    $data[$targetBranch][$itemCode]['price'] = [
                        'previous' => $targetConsignmentPrice,
                        'new' => $basicRate
                    ];
                }

                // revert changes if there are errors
                $stateBeforeUpdate['Bin'][$targetBinId] = $targetBinDetails;

                $binResponse = $this->erpPut('Bin', $targetBinId, $updateBin);

                if (!isset($binResponse['data'])) {
                    throw new Exception('Bin: ' . $binResponse['exception']);
                }

                $expectedQtyAfterTransaction['target'][$targetBranch][$itemCode] = $targetUpdatedConsignedQty;

                // Stock Entry Detail
                $steDetailsUpdate = ['status' => 'Issued'];

                if (!isset($item['consignment_status']) || $item['consignment_status'] != 'Received') {
                    $steDetailsUpdate['consignment_status'] = 'Received';
                    $steDetailsUpdate['consignment_date_received'] = $now->toDateTimeString();
                    $steDetailsUpdate['consignment_received_by'] = Auth::user()->wh_user;
                }

                // Update the target warehouse, if needed. This is only available for the consignment supervisor
                if ($request->target_warehouse) {
                    $steDetailsUpdate['t_warehouse'] = $request->target_warehouse;
                    $steDetailsUpdate['target_warehouse_location'] = $request->target_warehouse;
                }

                $stateBeforeUpdate['Stock Entry Detail'][$item['name']] = $item;

                $stockEntryDetailResponse = $this->erpPut('Stock Entry Detail', $item['name'], $steDetailsUpdate);

                if (!isset($stockEntryDetailResponse['data'])) {
                    throw new Exception('Stock Entry Detail: ' . $stockEntryDetailResponse['exception']);
                }

                $receivedItems[] = [
                    'item_code' => $itemCode,
                    'qty' => $item['transfer_qty'],
                    'price' => $basicRate,
                    'amount' => $basicRate * $item['transfer_qty']
                ];
            }

            $warehousesArr = collect($sourceWarehouses)->merge($targetWarehouses);

            // get the actual qty after update for the source and target warehouse
            $actualQtyAfterTransaction = Bin::whereIn('warehouse', $warehousesArr)->whereIn('item_code', $itemCodes)->get(['item_code', 'consigned_qty', 'warehouse']);
            $actualQtyAfterTransaction = $actualQtyAfterTransaction->groupBy(['warehouse', 'item_code']);

            // Compare the expected qty vs actual qty after transaction for both source warehouse and target warehouse
            foreach ($steItems as $item) {
                // source warehouse
                $itemCode = $item['item_code'];
                $src = $item['s_warehouse'] ? $item['s_warehouse'] : $stockEntry['from_warehouse'];
                $isConsigned = false;
                if ($src != 'Consignment Warehouse - FI') {
                    $isConsigned = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')->where('is_group', 0)->where('disabled', 0)->where('name', $src)->exists();
                }

                if ($isConsigned) {
                    $expectedQtyInSource = isset($expectedQtyAfterTransaction['source'][$src][$itemCode]) ? $expectedQtyAfterTransaction['source'][$src][$itemCode] : 0;
                    $actualConsignedQtyInSource = isset($actualQtyAfterTransaction[$src][$itemCode]) ? $actualQtyAfterTransaction[$src][$itemCode][0]->consigned_qty : 0;

                    if ($expectedQtyInSource != $actualConsignedQtyInSource) {
                        throw new Exception("Error: Expected qty of item $itemCode did not match the actual qty in source warehouse");
                    }
                }

                // target warehouse
                $trg = $item['t_warehouse'] ? $item['t_warehouse'] : $stockEntry['to_warehouse'];
                if (isset($request->receive_delivery)) {
                    $expectedQtyInTarget = isset($expectedQtyAfterTransaction['target'][$trg][$itemCode]) ? $expectedQtyAfterTransaction['target'][$trg][$itemCode] : 0;
                    $actualConsignedQtyInTarget = isset($actualQtyAfterTransaction[$trg][$itemCode]) ? $actualQtyAfterTransaction[$trg][$itemCode][0]->consigned_qty : 0;

                    if ($expectedQtyInTarget != $actualConsignedQtyInTarget) {
                        throw new Exception("Error: Expected qty of $itemCode did not match the actual qty in target warehouse");
                    }
                }
            }

            // Set source and target warehouse for logs
            $sourceWarehouse = isset($stockEntry['from_warehouse']) ? $stockEntry['from_warehouse'] : null;
            if (!$sourceWarehouse) {
                $sourceWarehouse = isset($sourceWarehouses[0]) ? $sourceWarehouses[0] : null;
            }

            if ($request->target_warehouse) {
                $targetWarehouse = $request->target_warehouse;
            } else {
                $targetWarehouse = $stockEntry['to_warehouse'] ? $stockEntry['to_warehouse'] : null;
                if (!$targetWarehouse) {
                    $targetWarehouse = isset($targetWarehouses[0]) ? $targetWarehouses[0] : null;
                }
            }

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

            $log = $this->erpPost('Activity Log', $logs);

            if (!isset($log['data'])) {
                session()->flash('warning', 'Activity Log not posted');
            }

            $stockEntryResponse['data'] = $this->erpPut('Stock Entry', $id, [
                'consignment_status' => 'Received',
                'consignment_date_received' => $now->toDateTimeString(),
                'consignment_received_by' => Auth::user()->wh_user,
            ]);

            if (!isset($stockEntryResponse['data'])) {
                throw new Exception('Stock Entry: ' . $stockEntryResponse['exception']);
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
            Log::error('ConsignmentController submitBeginningInventory failed', [
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

            $itemCodes = collect($receivedItems)->map(function ($item) {
                return $item->item_code;
            });

            $branches = [];

            $targetWarehouses = collect($receivedItems)->map(function ($item) {
                return $item->t_warehouse;
            })->unique()->toArray();

            $sourceWarehouses = collect($receivedItems)->map(function ($item) {
                return $item->s_warehouse;
            })->unique()->toArray();

            $stWarehouses = [$stockEntry->from_warehouse, $stockEntry->to_warehouse];

            $branches = array_merge($targetWarehouses, $sourceWarehouses, $stWarehouses);

            $binConsignedQty = Bin::whereIn('item_code', $itemCodes)->whereIn('warehouse', $branches)->select('warehouse', 'item_code', 'consigned_qty')->get();

            $consignedQty = [];
            foreach ($binConsignedQty as $bin) {
                $consignedQty[$bin->warehouse][$bin->item_code] = [
                    'consigned_qty' => $bin->consigned_qty
                ];
            }

            foreach ($receivedItems as $item) {
                $branch = $stockEntry->to_warehouse ? $stockEntry->to_warehouse : $item->t_warehouse;
                if ($item->consignment_status != 'Received') {
                    return redirect()->back()->with('error', $id . ' is not yet received.');
                }

                if (!isset($consignedQty[$branch][$item->item_code])) {
                    return redirect()->back()->with('error', 'Item not found.');
                }

                if ($consignedQty[$branch][$item->item_code]['consigned_qty'] < $item->transfer_qty) {
                    return redirect()->back()->with('error', 'Cannot cancel received items.<br/> Available qty is ' . number_format($consignedQty[$branch][$item->item_code]['consigned_qty']) . ', received qty is ' . number_format($item->transfer_qty));
                }

                if ($stockEntry->transfer_as == 'Store Transfer') {  // return stocks to source warehouse
                    $srcBranch = $stockEntry->from_warehouse ? $stockEntry->from_warehouse : $item->s_warehouse;
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

            $sourceWarehouse = $stockEntry->from_warehouse ? $stockEntry->from_warehouse : null;
            if (!$sourceWarehouse) {
                $sourceWarehouse = isset($receivedItems[0]) ? $receivedItems[0]->s_warehouse : null;
            }

            $targetWarehouse = $stockEntry->to_warehouse ? $stockEntry->to_warehouse : null;
            if (!$targetWarehouse) {
                $targetWarehouse = isset($receivedItems[0]) ? $receivedItems[0]->t_warehouse : null;
            }

            $logs = [
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
            ];

            ActivityLog::insert($logs);

            $cancelledArr['message'] = 'Stock transfer cancelled.';
            $cancelledArr['branch'] = $targetWarehouse;
            $cancelledArr['action'] = 'canceled';

            DB::commit();
            return redirect()->back()->with('success', $cancelledArr);
        } catch (Exception $e) {
            Log::error('ConsignmentController cancelStockTransfer failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();
            return redirect()->back()->with('error', 'An error occured. Please try again later');
        }
    }

    // /beginning_inventory_list
    public function beginningInventoryList(Request $request)
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $beginningInventory = BeginningInventory::whereIn('branch_warehouse', $assignedConsignmentStore)->orderBy('creation', 'desc')->paginate(10);

        return view('consignment.beginning_inv_list', compact('beginningInventory'));
    }

    // /beginning_inventory
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
            // get approved, for approval records and items with consigned qty
            $itemsWithConsignedQty = Bin::where('warehouse', $branch)->where('consigned_qty', '>', 0)->pluck('item_code');

            $invRecords = BeginningInventory::where('branch_warehouse', $branch)->whereIn('status', ['For Approval', 'Approved'])->pluck('name');
            $invItems = BeginningInventoryItem::whereIn('parent', $invRecords)->pluck('item_code');

            $invItems = collect($invItems)->merge($itemsWithConsignedQty);

            if ($action == 'update') {  // If 'For Approval' beginning inventory record exists for this branch
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
            } else {  // Create new beginning inventory entry
                $binItems = Bin::query()
                    ->from('tabBin as bin')
                    ->join('tabItem as item', 'bin.item_code', 'item.name')
                    ->where('bin.warehouse', $branch)
                    ->where('bin.actual_qty', '>', 0)
                    ->where('bin.consigned_qty', 0)
                    ->whereNotIn('bin.item_code', $invItems)  // do not include approved and for approval items
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
            $itemCodes = collect(array_filter($itemCodes))->unique();  // remove null values
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

            $athenaLogs = $this->erpPost('Activity Log', $logs);

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Saved! Please wait for approval');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function cancelDraftBeginningInventory($beginningInventoryId)
    {
        try {
            $response = $this->erpDelete('Consignment Beginning Inventory', $beginningInventoryId);

            if (!isset($response['data'])) {
                throw new Exception('An error occured.');
            }

            return redirect('/beginning_inv_list')->with('success', 'Beginning Inventory Canceled.');
        } catch (\Throwable $th) {
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function updateDraftBeginningInventory($id, Request $request)
    {
        try {
            $openingStock = $request->opening_stock;
            $openingStock = preg_replace('/[^0-9 .]/', '', $openingStock);

            $price = $request->price;
            $price = preg_replace('/[^0-9 .]/', '', $price);

            $itemCodes = $request->item_code;
            $itemCodes = collect(array_filter($itemCodes))->unique();  // remove null values
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
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function viewDamagedItemsList(Request $request)
    {
        $list = ConsignmentDamagedItems::query()
            ->when($request->search, function ($query) use ($request) {
                $query
                    ->where('item_code', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->store, function ($query) use ($request) {
                $query->where('branch_warehouse', $request->store);
            })
            ->orderBy('creation', 'desc')
            ->paginate(20);

        $itemCodes = collect($list->items())->map(function ($item) {
            return $item->item_code;
        });

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image) {
            return $this->base64Image("img/$image");
        });

        $noImg = $this->base64Image('icon/no_img.png');

        $result = [];
        foreach ($list as $item) {
            $origExists = $webpExists = 0;

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

    public function countStockTransfer($purpose)
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
                $itemDetail = isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode] : [];
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
                    'return_reason' => isset($item['reason']) ? $item['reason'] : null,
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
                'remarks' => 'Generated in AthenaERP. ' . $details['remarks'],
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

    // /stocks_report/list
    public function stockTransferReport(Request $request)
    {
        if (!in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])) {  // for supervisor stock transfers list
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
                    return $query->where('name', 'like', "%$request->q%");
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
                $bin = $binDetails[$warehouse];

                $stockTransfer->submitted_by = ucwords(str_replace('.', ' ', explode('@', $stockTransfer->owner)[0]));

                $stockTransfer->items = collect($stockTransfer->items)->map(function ($item) use ($bin) {
                    $itemCode = $item->item_code;
                    $consignmentDetails = $bin[$itemCode][0];

                    $item->consigned_qty = (int) $consignmentDetails->consigned_qty;
                    $item->qty = (int) $item->qty;
                    $item->price = (float) $item->price;
                    $item->amount = (float) $item->amount;

                    $item->image = isset($consignmentDetails->defaultImage->image_path) ? '/img/' . $consignmentDetails->defaultImage->image_path : '/icon/no_img.png';
                    if (Storage::disk('public')->exists(explode('.', $item->image)[0] . '.webp')) {
                        $item->image = explode('.', $item->image)[0] . '.webp';
                    }

                    return $item;
                });

                return $stockTransfer;
            });

            return view('consignment.supervisor.tbl_stock_transfer', compact('result', 'list', 'purpose'));
        }

        return view('consignment.supervisor.view_stock_transfers');
    }

    public function replenishIndexPromodiser($request)
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
                ->when($request['status'], function ($query) use ($request) {
                    return $query->where('consignment_status', $request['status']);
                })
                ->when($request['search'], function ($query) use ($request) {
                    $searchString = $request['search'];
                    return $query->where('name', 'like', "%$searchString%");
                })
                ->orderByDesc('creation')
                ->paginate(20);

            return view('consignment.replenish_tbl', compact('list'));
        }
        return view('consignment.replenish_index', compact('assignedConsignmentStores'));
    }

    public function replenishIndex(Request $request)
    {
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ?? 0;
        if ($isPromodiser) {
            return $this->replenishIndexPromodiser($request);
        }

        $consignmentStores = Warehouse::where([
            'disabled' => 0,
            'parent_warehouse' => 'P2 Consignment Warehouse - FI'
        ])->orderBy('name')->pluck('name');

        if ($request->ajax()) {
            $targetWarehouses = $consignmentStores;
            $targetWarehouses = $request->branch ? [$request->branch] : $targetWarehouses;

            $order = "'Draft', 'For Approval', 'Approved', 'Delivered', 'Cancelled'";
            $list = MaterialRequest::with('items')
                ->where(['transfer_as' => 'Consignment', 'custom_purpose' => 'Consignment Order'])
                ->when($targetWarehouses, function ($query) use ($targetWarehouses) {
                    return $query->whereIn('branch_warehouse', $targetWarehouses);
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query->where('consignment_status', $request->status);
                })
                ->when($request->search, function ($query) use ($request) {
                    return $query->where('name', 'like', "%$request->search%");
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
                $name = isset($request->name[$index]) ? $request->name[$index] : null;
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
            } else {
                return redirect()->back()->with('success', "$id successfully updated!");
            }
        } catch (\Throwable $th) {
            Log::error('ConsignmentController updateMaterialRequest failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'An error occured. Please contact your system administrator.');
        }
    }

    public function replenishModalContents($id)
    {
        $stockEntry = ConsignmentStockEntry::with('items')->find($id);

        $itemImages = $inventory = [];
        $itemCodes = collect($stockEntry->items)->pluck('item_code');

        $flattenItemCodes = $itemCodes->implode("','");
        $itemImages = ItemImages::whereRaw("parent IN ('$flattenItemCodes')")->pluck('image_path', 'parent');

        $allowedWarehouses = Warehouse::where('parent_warehouse', 'like', 'P2%')->where('parent_warehouse', '!=', 'P2 Consignment Warehouse - FI')->pluck('parent_warehouse')->unique();

        $inventory = Bin::whereHas('warehouse', function ($warehouse) use ($allowedWarehouses) {
            $warehouse->whereIn('parent_warehouse', $allowedWarehouses);
        })->whereRaw("item_code IN ('$flattenItemCodes')")->select('warehouse', 'item_code')->get();

        $itemWarehousePairs = $inventory->map(fn ($item) => [$item->item_code, $item->warehouse])->unique()->values()->toArray();
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

    public function replenishForm(Request $request, $id = null)
    {
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        $materialRequest = $itemImages = [];

        if ($id) {
            $materialRequest = MaterialRequest::with('items')->find($id);
            $itemImages = ItemImages::whereIn('parent', collect($materialRequest->items)->pluck('item_code'))->pluck('image_path', 'parent');
        }

        return view('consignment.replenish_form', compact('assignedConsignmentStores', 'materialRequest', 'itemImages'));
    }

    public function replenishDelete($id)
    {
        try {
            $response = $this->erpDelete('Material Request', $id);

            if (!isset($response['data'])) {
                $err = data_get($response, 'exception', 'An error occured while deleting the document');
                throw new Exception($err);
            }

            return redirect('consignment/replenish')->with('success', "$id Deleted.");
        } catch (Exception $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function replenishSubmit(Request $request)
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
                $scheduleDate = (Clone $now)->addDays($consignmentReason == 'Stock Replenishment' ? 5 : 3)->format('Y-m-d');
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

            return redirect('consignment/replenish')->with('success', 'Request submitted.');
        } catch (Exception $th) {
            throw $th;
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function replenishApprove($id, Request $request)
    {
        try {
            $issue = $request->issue;
            $items = $request->items;

            $stockEntry = ConsignmentStockEntry::with('items')->find($id);

            // $data = $childData = [];
            // $hasPending = false;
            $columnsToExclude = ['parent', 'creation', 'modified', 'modified_by', 'owner', 'docstatus', 'parentfield', 'parenttype'];
            $mappedItems = collect($stockEntry->items)->map(function ($item) use ($items, $issue, $columnsToExclude, $stockEntry, &$childData, &$hasPending) {
                $itemCode = $item->item_code;

                foreach ($columnsToExclude as $column) {
                    unset($item->$column);
                }

                $qty = (float) $item->qty;
                // $issuanceDetails = $items[$itemCode];

                // if ($issue == 'selected' && isset($items[$itemCode]['issue']) && $items[$itemCode]['issue'] != 'on') {
                $item->status = 'Issued';
                // $childData[] = [
                //     'item_code' => $itemCode,
                //     'qty' => $qty,
                //     'source_warehouse' => $issuanceDetails['source_warehouse'],
                //     'target_warehouse' => $stockEntry->target_warehouse
                // ];
                // } else {
                //     $item->status = 'Issued';
                // }

                // if ($item->status == 'Pending') {
                //     $hasPending = true;
                // }

                return $item;
            })->values();

            $stockEntry->setRelation('items', $mappedItems);

            // return $hasPending;

            // $data = [
            //     'transfer_as' => 'Consignment',
            //     'purpose' => 'Consignment Order',
            //     'branch_warehouse' => $stockEntry->target_warehouse,
            //     'sales_person' => 'Plant 2',
            //     'company' => 'FUMACO Inc.',
            //     'customer' => 'WILCON DEPOT, INC.',
            //     'customer_address' => 'WILCON DEPOT-Shipping',
            //     'project' => 'WILCON STOCKS',
            //     'items' => $childData
            // ];

            // return $request->all();
            // return 1;
        } catch (Exception $e) {
            throw $e;
            return ApiResponse::failureLegacy($e->getMessage(), 500);
        }
    }

    public function replenishUpdate(Request $request, $id)
    {
        $stateBeforeUpdate = [];
        try {
            $branch = $request->branch;
            $items = $request->items;
            $now = now();

            $statuses = ['Draft', 'For Approval', 'Cancelled'];

            $status = isset($statuses[$request->status]) ? $statuses[$request->status] : 'Draft';
            $customer = 'CW MARKETING AND DEVELOPMENT CORPORATION';
            $project = 'CW HOME DEPOT';
            if (Str::contains($branch, 'WILCON DEPOT')) {
                $customer = 'WILCON DEPOT, INC.';
                $project = 'WILCON STOCKS';
            }

            $itemsData = [];
            foreach ($items as $itemCode => $item) {
                $name = isset($item['name']) ? $item['name'] : null;
                $qty = (int) $item['qty'];
                $remarks = $item['remarks'];
                $consignmentReason = $item['reason'];
                $scheduleDate = (Clone $now)->addDays($consignmentReason == 'Stock Replenishment' ? 5 : 3)->format('Y-m-d');
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
                ->with('bin', function ($query) use ($branch) {
                    $query
                        ->where('warehouse', $branch)
                        ->select('item_code', 'consigned_qty', 'stock_uom', 'warehouse');
                })
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

                $qty = isset($damagedQty[$itemCode]) ? $damagedQty[$itemCode] : 0;
                $uom = $itemDetails->stock_uom;

                $data = [
                    'transaction_date' => $now->toDateTimeString(),
                    'branch_warehouse' => $branch,
                    'item_code' => $itemCode,
                    'description' => $itemDetails->description,
                    'qty' => $qty,
                    'stock_uom' => $uom,
                    'damage_description' => isset($reason[$itemCode]) ? $reason[$itemCode] : 0,
                    'promodiser' => Auth::user()->name
                ];

                $response = $this->erpPost('Consignment Damaged Item', $data);

                if (!isset($response['data'])) {
                    throw new Exception($response['exception']);
                }

                $activityLogData[] = $data;

                $response = $response['data'];
                $stateBeforeUpdate['Consignment Damaged Item'][$response['name']] = 'delete';
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
                'data' => json_encode($activityLogData, true)
            ];

            $log = $this->erpPost('Activity Log', $logs);

            if (isset($log['data'])) {
                session()->flash('error', 'Activity Log not posted');
            }

            return redirect()->back()->with('success', 'Damage report submitted.');
        } catch (Exception $e) {
            Log::error('ConsignmentController submitDamageReport failed', [
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

        $itemCodes = collect($damagedItems->items())->map(function ($item) {
            return $item->item_code;
        });

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image) {
            return $this->base64Image("img/$image");
        });

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
                // add qty to target quarantine wareghouse
                Bin::where('name', $existingTarget->name)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $existingTarget->consigned_qty + $damagedItem->qty
                ]);
            } else {
                $latestBin = Bin::where('name', 'like', '%bin/%')->max('name');
                $latestBinExploded = explode('/', $latestBin);
                $binId = (($latestBin) ? $latestBinExploded[1] : 0) + 1;
                $binId = str_pad($binId, 7, '0', STR_PAD_LEFT);
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

            // deduct qty to source warehouse
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

            $logs = [
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
            ];

            ActivityLog::insert($logs);

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

        $defaultImages = Item::whereIn('name', $itemCodes)->whereNotNull('item_image_path')->select('name as item_code', 'item_image_path as image_path')->get();  // in case there are no saved images in Item Images
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

    // /stock_transfer/submit
    public function stockTransferSubmit(Request $request)
    {
        try {
            $now = now();

            $itemCodes = array_filter(collect($request->item_code)->unique()->toArray());
            $transferQty = collect($request->item)->map(function ($item) {
                return preg_replace('/[^0-9 .]/', '', $item);
            });
            $purpose = $request->transfer_as == 'Pull Out' ? 'Pull Out' : 'Store-to-Store Transfer';

            $itemTransferDetails = $request->item;

            $sourceWarehouse = $request->source_warehouse;
            $targetWarehouse = $request->transfer_as == 'Pull Out' ? 'Quarantine Warehouse - FI' : $request->target_warehouse;

            if (!$itemCodes || !$transferQty) {
                return redirect()->back()->with('error', 'Please select an item to return');
            }

            $min = collect($transferQty)->min();
            if ($min['transfer_qty'] <= 0) {  // if there are 0 return qty
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
                $transferQty = isset($transferQty[$itemCode]['transfer_qty']) ? (float) $transferQty[$itemCode]['transfer_qty'] : 0;
                $item->qty = $transferQty;
                $item->amount = $transferQty * $item->price;
                $item->cost_center = 'Main - FI';
                $item->item_description = strip_tags($item->item_description);
                $item->remarks = 'Generated in AthenaERP';
                $item->reason = isset($itemTransferDetails[$itemCode]['reason']) ? $itemTransferDetails[$itemCode]['reason'] : null;

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
            // throw $e;
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /stock_transfer/form
    public function stockTransferForm(Request $request)
    {
        $action = $request->action;
        $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');

        return view('consignment.stock_transfer_form', compact('assignedConsignmentStores', 'action'));
    }

    // /item_return/form
    public function itemReturnForm()
    {
        $assignedConsignmentStore = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        return view('consignment.item_returns_form', compact('assignedConsignmentStore'));
    }

    // /item_return/submit
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
            foreach ($itemDetails as $item) {
                if (!isset($items[$item->name])) {
                    continue;
                }

                $transferDetail = $items[$item->name];
                $itemCode = $item->name;

                $bin = $this->erpPut('Bin', $item->bin_id, ['consigned_qty' => (float) $item->consigned_qty + (float) $transferDetail['qty']]);

                $steDetails[] = [
                    'item_code' => $itemCode,
                    'item_description' => isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode][0]->description : null,
                    'uom' => isset($itemDetails[$itemCode]) ? $itemDetails[$itemCode][0]->stock_uom : null,
                    'qty' => (float) $transferDetail['qty'],
                    'price' => (float) $item->consignment_price,
                    'amount' => $item->consignment_price * $transferDetail['qty'],
                    'reason' => $transferDetail['reason']
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
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    // /stock_transfer/cancel/{id}
    public function stockTransferCancel($id)
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

                $items = Bin::where('warehouse', $stockEntry->target_warehouse)->whereIn('item_code', collect($stockEntryItems)->pluck('item_code'))->get()->groupBy('item_code');

                foreach ($stockEntryItems as $item) {
                    if (isset($items[$item->item_code]))
                        $itemDetails = $items[$item->item_code][0];
                    $bin = $this->erpPut('Bin', $itemDetails->name, ['consigned_qty' => $itemDetails->consigned_qty > $item->qty ? $itemDetails->consigned_qty - $item->qty : 0]);
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
            // throw $e;
            return redirect()->back()->with('error', 'Something went wrong. Please try again later.');
        }
    }

    // stock_transfer/list
    public function stockTransferList(Request $request)
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
                            'consigned_qty' => isset($binArr[$ste->$refWarehouse][$item->item_code]) ? $binArr[$ste->$refWarehouse][$item->item_code]['consigned_qty'] : 0,
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

    // /inventory_audit
    public function viewInventoryAuditList(Request $request)
    {
        $selectYear = [];
        for ($i = 2022; $i <= date('Y'); $i++) {
            $selectYear[] = $i;
        }

        $assignedConsignmentStores = [];
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? true : false;
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

            $firstCutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') . '/' . $cutoff1 . '/' . $end->format('Y'))->endOfDay();

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

                    $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . now()->format('F d, Y');
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

                if (!$beginningInventoryTransactionDate || !$lastInventoryAuditDate) {
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

        // get previous cutoff
        $currentCutoff = $this->getCutoffDate(now()->endOfDay());
        $previousCutoff = $this->getCutoffDate($currentCutoff[0]);

        $previousCutoffStart = $previousCutoff[0];
        $previousCutoffEnd = $previousCutoff[1];

        $previousCutoffDisplay = Carbon::parse($previousCutoffStart)->format('M. d, Y') . ' - ' . Carbon::parse($previousCutoffEnd)->format('M. d, Y');
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
            'total_sales' => '₱ ' . number_format($previousCutoffSales, 2)
        ];

        $promodisers = User::where('enabled', 1)->where('user_group', 'Promodiser')->pluck('full_name');

        return view('consignment.supervisor.view_inventory_audit', compact('assignedConsignmentStores', 'selectYear', 'displayedData', 'promodisers'));
    }

    // /submitted_inventory_audit
    public function getSubmittedInvAudit(Request $request)
    {
        $store = $request->store;
        $year = $request->year;

        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? true : false;
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
                    'date_submitted' => $row->transaction_date
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
                'promodiser' => $row->promodiser
            ];
        }

        return view('consignment.supervisor.tbl_inventory_audit_history', compact('list', 'result'));
    }

    public function viewInventoryAuditItems($store, $from, $to, Request $request)
    {
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? true : false;

        if ($isPromodiser) {
            $assignedConsignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->orderBy('warehouse', 'asc')->pluck('warehouse')->toArray();

            if (!in_array($store, $assignedConsignmentStores)) {
                return redirect('/')->with('error', 'No access to selected branch.');
            }
        }

        $list = ConsignmentInventoryAuditReport::query()
            ->join('tabConsignment Inventory Audit Report Item as ciar', 'cia.name', 'ciar.parent')
            ->join('tabItem as i', 'i.name', 'ciar.item_code')
            ->where('branch_warehouse', $store)
            ->where('audit_date_from', $from)
            ->where('audit_date_to', $to)
            ->select('cia.name as inventory_audit_id', 'cia.*', 'i.*', 'ciar.*')
            ->get();

        $activityLogs = ActivityLog::whereIn('reference_name', collect($list)->pluck('inventory_audit_id'))->select('reference_name', 'data')->orderBy('creation', 'desc')->first();

        $activityLogsData = $activityLogs ? collect(json_decode($activityLogs->data)) : [];

        if (count($list) <= 0) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        $firstRecord = collect($list)->first();

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

        $duration = Carbon::parse($from)->format('F d, Y') . ' - ' . Carbon::parse($to)->format('F d, Y');

        $itemCodes = collect($list)->pluck('item_code');

        $beginningInventory = BeginningInventory::query()
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

            if (!$isPromodiser) {
                $description = explode(',', strip_tags($row->description));

                $descriptionPart1 = Arr::exists($description, 0) ? trim($description[0]) : null;
                $descriptionPart2 = Arr::exists($description, 1) ? trim($description[1]) : null;
                $descriptionPart3 = Arr::exists($description, 2) ? trim($description[2]) : null;
                $descriptionPart4 = Arr::exists($description, 3) ? trim($description[3]) : null;

                $displayedDescription = $descriptionPart1 . ', ' . $descriptionPart2 . ', ' . $descriptionPart3 . ', ' . $descriptionPart4;
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
                // 'img_webp' => $webp,
                // 'img_count' => $imgCount,
                'opening_qty' => number_format($openingQty),
                'previous_qty' => number_format($row->available_stock_on_transaction),
                'audit_qty' => number_format($row->qty),
                'sold_qty' => isset($activityLogsData[$row->item_code]) ? collect($activityLogsData[$row->item_code])['sold_qty'] : 0
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
            $nextRecordLink = '/view_inventory_audit_items/' . $store . '/' . $nextRecord->audit_date_from . '/' . $nextRecord->audit_date_to;
        }

        if ($previousRecord) {
            $previousRecordLink = '/view_inventory_audit_items/' . $store . '/' . $previousRecord->audit_date_from . '/' . $previousRecord->audit_date_to;

            $previousSalesRecord = $this->getSalesAmount(Carbon::parse($previousRecord->audit_date_from)->startOfDay(), Carbon::parse($previousRecord->audit_date_to)->endOfDay(), $store);

            $salesIncrease = $totalSales > $previousSalesRecord ? true : false;
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
                'received_by' => $row->consignment_received_by
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
                't_warehouse' => $row->t_warehouse
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
                'received_by' => $row->consignment_received_by
            ];
        }

        $damagedItemList = [];
        foreach ($damagedItems as $row) {
            $damagedItemList[$row->item_code][] = [
                'qty' => $row->qty * 1,
                'transaction_date' => Carbon::parse($row->creation)->format('M. d, Y h:i A'),
                'damage_description' => $row->damage_description,
                'stock_uom' => $row->stock_uom
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

    private function checkItemTransactions($itemCode, $branch, $date, $csaId = null)
    {
        $transactionDate = Carbon::parse($date);
        $now = now();

        $hasStockEntry = StockEntry::whereHas('items', function ($item) use ($now, $transactionDate, $itemCode, $branch) {
            $item->where('item_code', $itemCode)->whereBetween('consignment_date_received', [$transactionDate, $now])->where('s_warehouse', $branch);
        })
            ->whereIn('transfer_as', ['Consignment', 'For Return', 'Store Transfer'])
            ->where('item_status', ['For Checking', 'Issued'])
            ->where('purpose', 'Material Transfer')
            ->where('docstatus', 1)
            ->exists();

        $hasDamagedItems = ConsignmentDamagedItems::where('branch_warehouse', $branch)->where('item_code', $itemCode)->whereBetween('transaction_date', [$transactionDate, $now])->exists();

        $hasStockAdjustments = ConsignmentStockAdjustment::whereHas('items', function ($item) use ($itemCode) {
            $item->where('item_code', $itemCode);
        })
            ->whereBetween('creation', [$transactionDate, $now])
            ->where('warehouse', $branch)
            ->where('status', '!=', 'Cancelled')
            ->when($csaId != null, function ($query) use ($csaId) {
                return $query->where('name', '!=', $csaId);
            })
            ->exists();

        return [
            'ste_transactions' => $hasStockEntry,
            'damaged_transactions' => $hasDamagedItems,
            'stock_adjustment_transactions' => $hasStockAdjustments
        ];
    }

    public function cancelStockAdjustment($id)
    {
        DB::beginTransaction();
        try {
            $adjustmentDetails = ConsignmentStockAdjustment::find($id);

            if (!$adjustmentDetails) {
                return redirect()->back()->with('error', 'Stock adjustment record not found.');
            }

            if ($adjustmentDetails->status == 'Cancelled') {
                return redirect()->back()->with('error', 'Stock adjustment is already cancelled');
            }

            $adjustedItems = ConsignmentStockAdjustmentItem::where('parent', $adjustmentDetails->name)->get();

            if (!$adjustedItems) {
                return redirect()->back()->with('error', 'Items not found.');
            }

            foreach ($adjustedItems as $item) {
                $hasTransactions = $this->checkItemTransactions($item->item_code, $adjustmentDetails->warehouse, $adjustmentDetails->creation, $id);

                if (collect($hasTransactions)->max() > 0) {
                    return redirect()->back()->with('error', 'Cannot cancel stock adjustment record. Item ' . $item->item_code . ' has existing transaction(s).');
                }

                Bin::where('item_code', $item->item_code)->where('warehouse', $adjustmentDetails->warehouse)->update([
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'consigned_qty' => $item->previous_qty,
                    'consignment_price' => $item->previous_price
                ]);
            }

            ConsignmentStockAdjustment::where('name', $id)->update([
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'status' => 'Cancelled'
            ]);

            $logs = [
                'name' => uniqid(),
                'creation' => now()->toDateTimeString(),
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'docstatus' => 0,
                'idx' => 0,
                'subject' => 'Stock Adjustment ' . $adjustmentDetails->name . ' has been cancelled by ' . Auth::user()->full_name . ' at ' . now()->toDateTimeString(),
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

                $item->image = Arr::exists($itemImages, $itemCode) ? '/img/' . $itemImages[$itemCode] : '/icon/no_img.png';
                if (Storage::disk('public')->exists(explode('.', $item->image)[0] . '.webp')) {
                    $item->image = explode('.', $item->image)[0] . '.webp';
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
            if (!$request->warehouse) {
                throw new Exception('Please select a warehouse');
            }

            $now = now();
            $branch = $request->warehouse;
            $itemCodes = $request->item_codes;
            $input = $request->item;

            if (!$itemCodes || !$input) {
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

            if (!$itemDetails) {
                throw new Exception('No items found.');
            }

            $consignmentItems = $activityLogs = [];
            foreach ($itemDetails as $item) {
                $itemCode = $item->item_code;
                $bin = collect($item->bin)->first();

                $binId = $bin->name;
                unset($bin->name, $bin->item_code);

                $stateBeforeUpdate['Bin'][$binId] = $bin;

                $newStock = preg_replace('/[^0-9]/', '', $input[$itemCode]['qty']);
                $newStock = $newStock ? $newStock * 1 : 0;

                $newPrice = preg_replace('/[^0-9 .]/', '', $input[$itemCode]['price']);
                $newPrice = $newPrice ? $newPrice * 1 : 0;

                $update = [];

                if ($bin->consigned_qty != $newStock) {
                    $update['consigned_qty'] = $newStock;
                    $activityLogs[$branch][$itemCode]['quantity'] = [
                        'previous' => $bin->consigned_qty,
                        'new' => $newStock
                    ];
                }

                if ($bin->consignment_price != $newPrice) {
                    $update['consignment_price'] = $newPrice;
                    $activityLogs[$branch][$itemCode]['price'] = [
                        'previous' => $bin->consignment_price,
                        'new' => $newPrice
                    ];
                }

                $itemRemarks = isset($itemDetails[$itemCode]['remarks']) ? $itemDetails[$itemCode]['remarks'] : null;

                if (!$update) {
                    continue;
                }

                $binResponse = $this->erpPut('Bin', $binId, $update);

                if (!isset($binResponse['data'])) {
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
                    'remarks' => $itemRemarks
                ];
            }

            $consignmentData = [
                'warehouse' => $request->warehouse,
                'created_by' => Auth::user()->wh_user,
                'transaction_date' => $now->toDateString(),
                'transaction_time' => $now->toTimeString(),
                'remarks' => $request->notes,
                'items' => $consignmentItems
            ];

            $consignmentResponse = $this->erpPost('Consignment Stock Adjustment', $consignmentData);

            if (!isset($consignmentResponse['data'])) {
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
                'subject' => 'Stock Adjustment for ' . $request->warehouse . ' has been created by ' . Auth::user()->full_name . ' at ' . $now->toDateTimeString(),
                'content' => 'Consignment Activity Log',
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Consignment Stock Adjustment',
                'reference_name' => $consignmentId,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogs, true)
            ]);

            // Send Email Notification to assigned Promodisers
            $images = ItemImages::whereIn('parent', $itemCodes)->get()->groupBy('parent');

            $promodisers = AssignedWarehouses::query()
                ->join('tabWarehouse Users as wu', 'wu.frappe_userid', 'acw.parent')
                ->where('acw.warehouse', $branch)
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
                'notes' => $request->notes
            ];

            if ($promodisers) {
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
            Log::error('ConsignmentController addPromodiser failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->revertChanges($stateBeforeUpdate);
            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    // /stock_adjust/submit/{id}
    public function submitStockAdjustment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $itemCodes = array_keys($request->item);
            $stocks = $request->item;

            $now = now();

            $beginningInventory = BeginningInventory::find($id);
            if (!$beginningInventory) {
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
                        'modified_by' => Auth::user()->wh_user
                    ];

                    if ($previousStock != $openingQty) {
                        $totalReceived = isset($totalReceivedQty[$itemCode]) ? $totalReceivedQty[$itemCode][0]->qty : 0;

                        $updatedStocks = $openingQty + $totalReceived;
                        $updatedStocks = $updatedStocks > 0 ? $updatedStocks : 0;

                        $binStockArray = ['consigned_qty' => $updatedStocks];
                        $cbiStockArray = ['opening_stock' => $openingQty];

                        $activityLogsData[$itemCode]['previous_qty'] = $previousStock;
                        $activityLogsData[$itemCode]['new_qty'] = $openingQty;
                    }

                    if ($previousPrice != $price) {
                        $binStockArray = ['consignment_price' => $price];
                        $cbiPriceArray = [
                            'price' => $price,
                            'amount' => $price * $openingQty
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
                'subject' => 'Stock Adjustment for ' . $beginningInventory->branch_warehouse . ' has been created by ' . Auth::user()->full_name . ' at ' . $now->toDateTimeString(),
                'communication_date' => $now->toDateTimeString(),
                'reference_doctype' => 'Stock Adjustment',
                'reference_name' => $id,
                'reference_owner' => Auth::user()->wh_user,
                'user' => Auth::user()->wh_user,
                'full_name' => Auth::user()->full_name,
                'data' => json_encode($activityLogsData, true)
            ]);

            $grandTotal = BeginningInventoryItem::where('parent', $id)->sum('amount');

            BeginningInventory::where('name', $id)->update([
                'modified' => $now,
                'modified_by' => Auth::user()->wh_user,
                'grand_total' => $grandTotal,
                'remarks' => $request->remarks
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse Stocks Adjusted.');
        } catch (\Throwable $e) {
            Log::error('ConsignmentController submitStockAdjustment failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return redirect()->back()->with('error', 'Something went wrong. Please try again later');
        }
    }

    public function getPendingSubmissionInventoryAudit(Request $request)
    {
        $store = $request->store;

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
            ->when($store, function ($query) use ($store) {
                return $query->where('branch_warehouse', $store);
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

        $firstCutoff = Carbon::createFromFormat('m/d/Y', $end->format('m') . '/' . $cutoff1 . '/' . $end->format('Y'))->endOfDay();

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

            $duration = null;
            if ($beginningInventoryTransactionDate) {
                $start = Carbon::parse($beginningInventoryTransactionDate);
            }

            if ($lastInventoryAuditDate) {
                $start = Carbon::parse($lastInventoryAuditDate);
            }

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

            $duration = Carbon::parse($start)->addDay()->format('F d, Y') . ' - ' . now()->format('F d, Y');
            $check = Carbon::parse($start)->between($periodFrom, $periodTo);
            if (Carbon::parse($start)->addDay()->startOfDay()->lt(now()->startOfDay())) {
                if ($lastAuditDate->endOfDay()->lt($end) && $beginningInventoryTransactionDate) {
                    if (!$check) {
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

            if (!$beginningInventoryTransactionDate) {
                $pending[] = [
                    'store' => $store,
                    'beginning_inventory_date' => $beginningInventoryTransactionDate,
                    'last_inventory_audit_date' => $lastInventoryAuditDate,
                    'duration' => $duration,
                    'is_late' => $isLate,
                    'promodisers' => $promodisers
                ];
            }
        }

        return view('consignment.supervisor.tbl_pending_submission_inventory_audit', compact('pending'));
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
            ->when($request->warehouse, function ($query) use ($request) {
                return $query->where('subject', 'like', "%$request->warehouse%");
            })
            ->when($dates, function ($query) use ($dates) {
                return $query->whereBetween('creation', [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->endOfDay()]);
            })
            ->when($request->user, function ($query) use ($request) {
                return $query->where('full_name', $request->user);
            })
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
            ->whereHas('whUser', function ($user) {
                $user->where('user_group', 'Promodiser');
            })
            ->with('social', function ($user) {
                $user->select('parent', 'userid');
            })
            ->with('whUser', function ($user) {
                $user
                    ->select('wh_user', 'name', 'frappe_userid', 'full_name', 'frappe_userid', 'enabled')
                    ->with('assignedWarehouses', function ($warehouse) {
                        $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                    });
            })
            ->select('name', 'full_name')
            ->get();

        $totalPromodisers = count($userDetails);

        $result = collect($userDetails)->map(function ($user) {
            if (Cache::has('user-is-online-' . $user->name)) {
                $loginStatus = '<span class="text-success font-weight-bold">ONLINE NOW</span>';
            } else {
                $loginStatus = Carbon::parse($user->last_login)->format('F d, Y h:i A');
            }

            return [
                'id' => $user->name,
                'promodiser_name' => $user->full_name,
                'stores' => collect($user->whUser->assignedWarehouses)->pluck('warehouse'),
                'login_status' => $user->last_login ? $loginStatus : null,
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
        $notIncluded = collect($notIncluded)
            ->push('Administrator')
            ->push('Guest')
            ->all();

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
                ->with('social', function ($user) {
                    $user->select('parent', 'userid');
                })
                ->with('whUser', function ($user) {
                    $user
                        ->select('wh_user', 'name', 'frappe_userid', 'user_group', 'modified', 'modified_by', 'price_list')
                        ->with('assignedWarehouses', function ($warehouse) {
                            $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                        });
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
                throw new Exception($response['exception']);
            }
            $response = $this->erpPut('Warehouse Users', $response['data']['name'], ['frappe_userid' => $response['data']['name']]);

            return redirect('/view_promodisers')->with('success', 'Promodiser Added.');
        } catch (\Throwable $e) {
            Log::error('ConsignmentController addPromodiser (Warehouse Users) failed', [
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
                    ->with('assignedWarehouses', function ($warehouse) {
                        $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                    });
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
                        ->with('assignedWarehouses', function ($warehouse) {
                            $warehouse->select('parent', 'name', 'warehouse', 'warehouse_name');
                        });
                })
                ->select('name', 'full_name')
                ->first();

            if (!$userDetails) {
                throw new Exception('User not found');
            }

            $frappeUserid = $userDetails->whUser->name;
            $assignedWarehouses = $userDetails->whUser->assignedWarehouses;

            $warehousesEntry = $request->warehouses;

            $a = array_diff($assignedWarehouses->toArray(), $warehousesEntry);
            $b = array_diff($warehousesEntry, $assignedWarehouses->toArray());
            $warehouses = [];
            if (count($a) > 0 || count($b) > 0) {  // if changes are made to the assigned warehouses
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
            ->where(function ($query) {
                $query
                    ->whereIn('ste.transfer_as', ['For Return', 'Store Transfer'])
                    ->orWhereIn('ste.receive_as', ['Sales Return']);
            })
            ->whereIn('ste.purpose', ['Material Transfer', 'Material Receipt'])
            ->where('ste.docstatus', 1)
            ->select('ste.name', 'ste.delivery_date', 'sted.s_warehouse', 'sted.t_warehouse', 'ste.creation', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.basic_rate', 'sted.basic_amount', 'ste.owner')
            ->orderBy('ste.creation', 'desc')
            ->get();

        return view('consignment.supervisor.tbl_audit_returns', compact('list'));
    }

    // /view_consignment_deliveries
    public function viewDeliveries(Request $request)
    {
        if ($request->ajax()) {
            $status = $request->status;

            $list = StockEntry::with('mreq')
                ->with('items', function ($items) {
                    $items->with('defaultImage')->select('name', 'parent', 't_warehouse', 'item_code', 'description', 'qty', 'transfer_qty', 'basic_rate', 'basic_amount');
                })
                ->whereDate('delivery_date', '>=', '2022-06-25')
                ->whereIn('transfer_as', ['Consignment', 'Store Transfer'])
                ->where('purpose', 'Material Transfer')
                ->where('docstatus', 1)
                ->when($status == 'Received', function ($query) {
                    return $query->where('consignment_status', 'Received');
                })
                ->when($status == 'To Receive', function ($query) {
                    return $query->where(function ($subQuery) {
                        $subQuery->whereNull('consignment_status')->orWhere('consignment_status', 'To Receive');
                    });
                })
                ->when($request->store, function ($query) use ($request) {
                    return $query->where('to_warehouse', $request->store);
                })
                ->orderByRaw("FIELD(consignment_status, '', 'To Receive', 'Received') ASC")
                ->orderByDesc('creation')
                ->paginate(20);

            $itemCodes = collect($list->items())->flatMap(function ($items) {
                return $items->items->pluck('item_code');
            })->unique()->values();

            $targetWarehouses = collect($list->items())->flatMap(function ($items) {
                return $items->items->pluck('t_warehouse');
            })->unique()->values();

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

                $stockEntry->created_by = isset($stockEntry->mreq->owner) ? ucwords(str_replace('.', ' ', explode('@', $stockEntry->mreq->owner)[0])) : null;

                return $stockEntry;
            });

            return view('consignment.supervisor.view_pending_to_receive', compact('list', 'result'));
        }

        return view('consignment.supervisor.view_deliveries');
    }

    public function getErpItems(Request $request)
    {
        $searchStr = explode(' ', $request->q);

        return Item::query()
            ->where('disabled', 0)
            ->where('has_variants', 0)
            ->where('is_stock_item', 1)
            ->when($request->q, function ($query) use ($request, $searchStr) {
                return $query->where(function ($subQuery) use ($searchStr, $request) {
                    foreach ($searchStr as $str) {
                        $subQuery->where('description', 'LIKE', '%' . $str . '%');
                    }

                    $subQuery->orWhere('item_code', 'LIKE', '%' . $request->q . '%');
                });
            })
            ->select('item_code as id', DB::raw('CONCAT(item_code, "-", description) as text '))
            ->orderBy('item_code', 'asc')
            ->limit(8)
            ->get();
    }

    public function consignmentLedger(Request $request)
    {
        if ($request->ajax()) {
            $branchWarehouse = $request->branch_warehouse;
            $itemCode = $request->item_code;

            $result = $itemDescriptions = [];
            if ($branchWarehouse) {
                $itemOpeningStock = BeginningInventory::query()
                    ->from('tabConsignment Beginning Inventory as cb')
                    ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                    ->where('cb.status', 'Approved')
                    ->where('branch_warehouse', $branchWarehouse)
                    ->when($itemCode, function ($query) use ($itemCode) {
                        return $query->where('cbi.item_code', $itemCode);
                    })
                    ->select('cbi.item_code', 'cbi.opening_stock', 'cb.transaction_date', 'cb.branch_warehouse', 'cb.name', 'cb.owner', 'cbi.item_description')
                    ->orderBy('cb.transaction_date', 'asc')
                    ->get();

                foreach ($itemOpeningStock as $r) {
                    $result[$r->item_code][$r->transaction_date][] = [
                        'qty' => number_format($r->opening_stock),
                        'type' => 'Beginning Inventory',
                        'transaction_date' => $r->transaction_date,
                        'reference' => $r->name,
                        'owner' => $r->owner
                    ];

                    $itemDescriptions[$r->item_code] = $r->item_description;
                }

                $beginningInventoryStart = BeginningInventory::query()
                    ->where('branch_warehouse', $branchWarehouse)
                    ->orderBy('transaction_date', 'asc')
                    ->pluck('transaction_date')
                    ->first();

                $beginningInventoryStartDate = $beginningInventoryStart ? Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

                $itemReceive = StockEntry::query()
                    ->from('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                        return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                    })
                    ->when($itemCode, function ($query) use ($itemCode) {
                        return $query->where('sted.item_code', $itemCode);
                    })
                    ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.consignment_status', 'Received')
                    ->where('sted.t_warehouse', $branchWarehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.consignment_date_received', 'sted.item_code', 'sted.transfer_qty', 'sted.consignment_received_by', 'sted.description')
                    ->orderBy('sted.consignment_date_received', 'desc')
                    ->get();

                foreach ($itemReceive as $a) {
                    $dateReceived = Carbon::parse($a->consignment_date_received)->format('Y-m-d');
                    $result[$a->item_code][$dateReceived][] = [
                        'qty' => number_format($a->transfer_qty),
                        'type' => 'Stocks Received',
                        'transaction_date' => $dateReceived,
                        'reference' => $a->name,
                        'owner' => $a->consignment_received_by
                    ];

                    $itemDescriptions[$a->item_code] = $a->description;
                }

                $itemTransferred = StockEntry::query()
                    ->from('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                        return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                    })
                    ->when($itemCode, function ($query) use ($itemCode) {
                        return $query->where('sted.item_code', $itemCode);
                    })
                    ->whereIn('ste.transfer_as', ['Store Transfer'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.s_warehouse', $branchWarehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
                    ->orderBy('sted.creation', 'desc')
                    ->get();

                foreach ($itemTransferred as $v) {
                    $dateTransferred = Carbon::parse($v->creation)->format('Y-m-d');
                    $result[$v->item_code][$dateTransferred][] = [
                        'qty' => number_format($v->transfer_qty),
                        'type' => 'Store Transfer',
                        'transaction_date' => $dateTransferred,
                        'reference' => $v->name,
                        'owner' => $v->owner
                    ];

                    $itemDescriptions[$v->item_code] = $v->description;
                }

                $itemReturned = StockEntry::query()
                    ->from('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                        return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                    })
                    ->when($itemCode, function ($query) use ($itemCode) {
                        return $query->where('sted.item_code', $itemCode);
                    })
                    ->whereIn('ste.transfer_as', ['For Return'])
                    ->where('ste.purpose', 'Material Transfer')
                    ->where('ste.docstatus', 1)
                    ->where('sted.s_warehouse', $branchWarehouse)
                    ->select('ste.name', 'sted.t_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner', 'sted.description')
                    ->orderBy('sted.creation', 'desc')
                    ->get();

                foreach ($itemReturned as $a) {
                    $dateReturned = Carbon::parse($a->creation)->format('Y-m-d');
                    $result[$a->item_code][$dateReturned][] = [
                        'qty' => number_format($a->transfer_qty),
                        'type' => 'Stocks Returned',
                        'transaction_date' => $dateReturned,
                        'reference' => $a->name,
                        'owner' => $a->owner
                    ];

                    $itemDescriptions[$a->item_code] = $a->description;
                }
            }

            return view('consignment.tbl_consignment_ledger', compact('result', 'branchWarehouse', 'itemDescriptions'));
        }

        return view('consignment.consignment_ledger');
    }

    public function consignmentStockMovement($itemCode, Request $request)
    {
        $branchWarehouse = $request->branch_warehouse;

        $dates = $request->date_range ? explode(' to ', $request->date_range) : [];
        $user = $request->user != 'Select All' ? $request->user : null;

        $result = [];
        if ($itemCode) {
            $itemOpeningStock = BeginningInventory::query()
                ->from('tabConsignment Beginning Inventory as cb')
                ->join('tabConsignment Beginning Inventory Item as cbi', 'cb.name', 'cbi.parent')
                ->where('cb.status', 'Approved')
                ->where('cbi.item_code', $itemCode)
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('branch_warehouse', $branchWarehouse);
                })
                ->when($request->date_range, function ($query) use ($dates) {
                    return $query->whereDate('cb.transaction_date', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('cb.transaction_date', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($query) use ($user) {
                    return $query->where('cb.owner', $user);
                })
                ->select('cbi.item_code', 'cbi.opening_stock', 'cb.transaction_date', 'cb.branch_warehouse', 'cb.name', 'cb.owner', 'cb.creation')
                ->orderBy('cb.transaction_date', 'asc')
                ->get();

            foreach ($itemOpeningStock as $r) {
                $result[] = [
                    'qty' => number_format($r->opening_stock),
                    'type' => 'Beginning Inventory',
                    'transaction_date' => $r->transaction_date,
                    'branch_warehouse' => $r->branch_warehouse,
                    'reference' => $r->name,
                    'owner' => $r->owner,
                    'creation' => $r->creation
                ];
            }

            $beginningInventoryStart = BeginningInventory::query()
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('branch_warehouse', $branchWarehouse);
                })
                ->orderBy('transaction_date', 'asc')
                ->pluck('transaction_date')
                ->first();

            $beginningInventoryStartDate = $beginningInventoryStart ? Carbon::parse($beginningInventoryStart)->startOfDay()->format('Y-m-d') : Carbon::parse('2022-06-25')->startOfDay()->format('Y-m-d');

            $itemReceive = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                    return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                })
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('sted.t_warehouse', $branchWarehouse);
                })
                ->when($request->date_range, function ($query) use ($dates) {
                    return $query->whereDate('sted.consignment_date_received', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.consignment_date_received', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($query) use ($user) {
                    return $query->where(function ($query) use ($user) {
                        return $query->where('sted.consignment_received_by', $user)->orWhere('ste.consignment_received_by', $user)->orWhere('sted.modified_by', $user);
                    });
                })
                ->whereIn('ste.transfer_as', ['Consignment', 'Store Transfer'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.consignment_status', 'Received')
                ->where('sted.item_code', $itemCode)
                ->select('ste.name', 'sted.t_warehouse', 'sted.consignment_date_received', 'sted.item_code', 'sted.transfer_qty', 'ste.consignment_received_by as parent_received_by', 'sted.consignment_received_by as child_received_by', 'sted.modified_by', 'ste.creation')
                ->orderBy('sted.consignment_date_received', 'desc')
                ->get();

            foreach ($itemReceive as $a) {
                $dateReceived = Carbon::parse($a->consignment_date_received)->format('Y-m-d');

                $owner = $a->child_received_by;
                if (!$owner) {
                    $owner = $a->parent_received_by ? $a->parent_received_by : $a->modified_by;
                }

                $result[] = [
                    'qty' => number_format($a->transfer_qty),
                    'type' => 'Stocks Received',
                    'transaction_date' => $dateReceived,
                    'branch_warehouse' => $a->t_warehouse,
                    'reference' => $a->name,
                    'owner' => $owner,
                    'creation' => $a->creation
                ];
            }

            $itemTransferred = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                    return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                })
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('sted.s_warehouse', $branchWarehouse);
                })
                ->when($request->date_range, function ($query) use ($dates) {
                    return $query->whereDate('sted.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($query) use ($user) {
                    return $query->where('ste.owner', $user);
                })
                ->whereIn('ste.transfer_as', ['Store Transfer'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.item_code', $itemCode)
                ->select('ste.name', 'sted.s_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner')
                ->orderBy('sted.creation', 'desc')
                ->get();

            foreach ($itemTransferred as $v) {
                $dateTransferred = Carbon::parse($v->creation)->format('Y-m-d');
                $result[] = [
                    'qty' => '-' . number_format($v->transfer_qty),
                    'type' => 'Store Transfer',
                    'transaction_date' => $dateTransferred,
                    'branch_warehouse' => $v->s_warehouse,
                    'reference' => $v->name,
                    'owner' => $v->owner,
                    'creation' => $v->creation
                ];
            }

            $itemReturned = StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->when($beginningInventoryStartDate, function ($query) use ($beginningInventoryStartDate) {
                    return $query->whereDate('ste.delivery_date', '>=', $beginningInventoryStartDate);
                })
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('sted.s_warehouse', $branchWarehouse);
                })
                ->when($request->date_range, function ($query) use ($dates) {
                    return $query->whereDate('sted.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('sted.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($query) use ($user) {
                    return $query->where('ste.owner', $user);
                })
                ->whereIn('ste.transfer_as', ['For Return'])
                ->where('ste.purpose', 'Material Transfer')
                ->where('ste.docstatus', 1)
                ->where('sted.item_code', $itemCode)
                ->select('ste.name', 'sted.s_warehouse', 'sted.creation', 'sted.item_code', 'sted.transfer_qty', 'ste.owner')
                ->orderBy('sted.creation', 'desc')
                ->get();

            foreach ($itemReturned as $a) {
                $dateReturned = Carbon::parse($a->creation)->format('Y-m-d');
                $result[] = [
                    'qty' => '-' . number_format($a->transfer_qty),
                    'type' => 'Stocks Returned',
                    'transaction_date' => $dateReturned,
                    'branch_warehouse' => $a->s_warehouse,
                    'reference' => $a->name,
                    'owner' => $a->owner,
                    'creation' => $a->creation
                ];
            }

            $stockAdjustments = ConsignmentStockAdjustment::query()
                ->from('tabConsignment Stock Adjustment as csa')
                ->join('tabConsignment Stock Adjustment Items as csai', 'csa.name', 'csai.parent')
                ->where('csai.item_code', $itemCode)
                ->whereRaw('csai.previous_qty != csai.new_qty')
                ->when($branchWarehouse, function ($query) use ($branchWarehouse) {
                    return $query->where('csa.warehouse', $branchWarehouse);
                })
                ->when($request->date_range, function ($query) use ($dates) {
                    return $query->whereDate('csa.creation', '>=', Carbon::parse($dates[0])->startOfDay())->whereDate('csa.creation', '<=', Carbon::parse($dates[1])->endOfDay());
                })
                ->when($user, function ($query) use ($user) {
                    return $query->where('csa.owner', $user);
                })
                ->select('csa.name', 'csai.new_qty', 'csa.transaction_date', 'csa.warehouse', 'csa.owner', 'csa.creation')
                ->orderBy('csa.creation', 'desc')
                ->get();

            foreach ($stockAdjustments as $sa) {
                $result[] = [
                    'qty' => number_format($sa->new_qty),
                    'type' => 'Stock Adjustment',
                    'transaction_date' => Carbon::parse($sa->transaction_date)->format('Y-m-d'),
                    'branch_warehouse' => $sa->warehouse,
                    'reference' => $sa->name,
                    'owner' => $sa->owner,
                    'creation' => $sa->creation
                ];
            }
        }

        if ($request->get_users == 1) {
            $all[] = [
                'id' => 'Select All',
                'text' => 'Select All'
            ];

            $users = collect($result)->map(function ($row) {
                if ($row['owner']) {
                    return [
                        'id' => $row['owner'],
                        'text' => $row['owner']
                    ];
                }
            })->filter()->unique();

            $users = collect($all)->merge($users);

            return response()->json($users);
        }

        $result = collect($result)->sortBy('creation')->reverse()->toArray();

        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $itemCollection = collect($result);
        // Define how many items we want to be visible in each page
        $perPage = 20;
        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator($currentPageItems, count($itemCollection), $perPage);
        // set url path for generted links
        $paginatedItems->setPath($request->url());

        $result = $paginatedItems;

        return view('tbl_consignment_stock_movement', compact('result'));
    }

    private function generateGlEntries($stockEntry)
    {
        try {
            $now = now();
            $stockEntryQry = StockEntry::query()->where('name', $stockEntry)->first();
            $stockEntryDetail = StockEntryDetail::query()
                ->where('parent', $stockEntry)
                ->select('s_warehouse', 't_warehouse', DB::raw('SUM((basic_rate * qty)) as basic_amount'), 'parent', 'cost_center', 'expense_account')
                ->groupBy('s_warehouse', 't_warehouse', 'parent', 'cost_center', 'expense_account')
                ->get();

            $basicAmount = 0;
            foreach ($stockEntryDetail as $row) {
                $basicAmount += ($row->t_warehouse) ? $row->basic_amount : 0;
            }

            $glEntry = [];
            foreach ($stockEntryDetail as $row) {
                if ($row->s_warehouse) {
                    $credit = $basicAmount;
                    $debit = 0;
                    $account = $row->expense_account;
                    $expenseAccount = $row->s_warehouse;
                } else {
                    $credit = 0;
                    $debit = $basicAmount;
                    $account = $row->t_warehouse;
                    $expenseAccount = $row->expense_account;
                }

                $glEntry[] = [
                    'name' => 'ath' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => 1,
                    'idx' => 0,
                    'fiscal_year' => $now->format('Y'),
                    'voucher_no' => $row->parent,
                    'cost_center' => $row->cost_center,
                    'credit' => $credit,
                    'party_type' => null,
                    'transaction_date' => null,
                    'debit' => $debit,
                    'party' => null,
                    '_liked_by' => null,
                    'company' => 'FUMACO Inc.',
                    '_assign' => null,
                    'voucher_type' => 'Stock Entry',
                    '_comments' => null,
                    'is_advance' => 'No',
                    'remarks' => 'Accounting Entry for Stock',
                    'account_currency' => 'PHP',
                    'debit_in_account_currency' => $debit,
                    '_user_tags' => null,
                    'account' => $account,
                    'against_voucher_type' => null,
                    'against' => $expenseAccount,
                    'project' => $stockEntryQry->project,
                    'against_voucher' => null,
                    'is_opening' => 'No',
                    'posting_date' => $stockEntryQry->posting_date,
                    'credit_in_account_currency' => $credit,
                    'total_allocated_amount' => 0,
                    'reference_no' => null,
                    'mode_of_payment' => null,
                    'order_type' => null,
                    'po_no' => null,
                    'reference_date' => null,
                    'cr_ref_no' => null,
                    'or_ref_no' => null,
                    'dr_ref_no' => null,
                    'pr_ref_no' => null,
                ];
            }

            GLEntry::query()->insert($glEntry);

            return ['success' => true, 'message' => 'GL Entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateLedgerEntries($stockEntry)
    {
        try {
            $now = now();
            $stockEntryQry = StockEntry::query()->where('name', $stockEntry)->first();

            $stockEntryDetail = StockEntryDetail::query()->where('parent', $stockEntry)->get();

            if (in_array($stockEntryQry->purpose, ['Material Transfer'])) {
                $sData = $tData = [];
                foreach ($stockEntryDetail as $row) {
                    $binQry = DB::connection('mysql')
                        ->table('tabBin')
                        ->where('warehouse', $row->s_warehouse)
                        ->where('item_code', $row->item_code)
                        ->first();

                    $actualQty = $valuationRate = 0;
                    if ($binQry) {
                        $actualQty = $binQry->actual_qty;
                        $valuationRate = $binQry->valuation_rate;
                    }

                    $sData[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty * -1,
                        'stock_value' => $actualQty * $valuationRate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => $row->name,
                        'incoming_rate' => 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->s_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuationRate,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actualQty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => ($row->qty * $row->valuation_rate) * -1,
                        'posting_date' => $now->format('Y-m-d'),
                    ];

                    $binQry = DB::connection('mysql')
                        ->table('tabBin')
                        ->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)
                        ->first();

                    $actualQty = $valuationRate = 0;
                    if ($binQry) {
                        $actualQty = $binQry->actual_qty;
                        $valuationRate = $binQry->valuation_rate;
                    }

                    $tData[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty,
                        'stock_value' => $actualQty * $valuationRate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => null,
                        'incoming_rate' => $row->basic_rate,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->t_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuationRate,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actualQty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => $row->qty * $row->valuation_rate,
                        'posting_date' => $now->format('Y-m-d'),
                        'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $stockLedgerEntry = array_merge($sData, $tData);

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stockLedgerEntry);
                }
            } else {
                $tData = [];
                foreach ($stockEntryDetail as $row) {
                    $binQry = DB::connection('mysql')
                        ->table('tabBin')
                        ->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)
                        ->first();

                    $actualQty = $valuationRate = 0;
                    if ($binQry) {
                        $actualQty = $binQry->actual_qty;
                        $valuationRate = $binQry->valuation_rate;
                    }

                    $tData[] = [
                        'name' => 'ath' . uniqid(),
                        'creation' => $now->toDateTimeString(),
                        'modified' => $now->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                        'owner' => Auth::user()->wh_user,
                        'docstatus' => 1,
                        'idx' => 0,
                        'serial_no' => $row->serial_no,
                        'fiscal_year' => $now->format('Y'),
                        'voucher_type' => 'Stock Entry',
                        'posting_time' => $now->format('H:i:s'),
                        'actual_qty' => $row->qty,
                        'stock_value' => $actualQty * $valuationRate,
                        '_comments' => null,
                        'dependant_sle_voucher_detail_no' => null,
                        'incoming_rate' => $row->basic_rate,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->t_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuationRate,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actualQty,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => $row->qty * $row->valuation_rate,
                        'posting_date' => $now->format('Y-m-d'),
                        'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($tData);
                }
            }

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateCancelledLedgerEntries($stockEntry)
    {
        try {
            $now = now();
            $sle = StockLedgerEntry::query()->where('voucher_no', $stockEntry)->get();

            StockLedgerEntry::query()->where('voucher_no', $stockEntry)->update(['is_cancelled' => 1]);

            $data = [];
            foreach ($sle as $r) {
                $binQry = DB::connection('mysql')
                    ->table('tabBin')
                    ->where('warehouse', $r->warehouse)
                    ->where('item_code', $r->item_code)
                    ->first();

                $actualQty = $valuationRate = 0;
                if ($binQry) {
                    $actualQty = $binQry->actual_qty;
                    $valuationRate = $binQry->valuation_rate;
                }

                $data[] = [
                    'name' => 'cn' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => $r->docstatus,
                    'parent' => $r->parent,
                    'parentfield' => $r->parentfield,
                    'parenttype' => $r->parenttype,
                    'idx' => $r->idx,
                    'serial_no' => $r->serial_no,
                    'fiscal_year' => $r->fiscal_year,
                    'voucher_type' => $r->voucher_type,
                    'posting_time' => $r->posting_time,
                    'actual_qty' => $r->actual_qty * -1,
                    'stock_value' => $actualQty * $valuationRate,
                    '_comments' => null,
                    'dependant_sle_voucher_detail_no' => $r->dependant_sle_voucher_detail_no,
                    'incoming_rate' => $r->incoming_rate,
                    'voucher_detail_no' => $r->voucher_detail_no,
                    'stock_uom' => $r->stock_uom,
                    'warehouse' => $r->warehouse,
                    '_liked_by' => null,
                    'company' => $r->company,
                    '_assign' => null,
                    'item_code' => $r->item_code,
                    'valuation_rate' => $valuationRate,
                    'project' => $r->project,
                    'voucher_no' => $r->voucher_no,
                    'outgoing_rate' => $r->outgoing_rate,
                    'is_cancelled' => 1,
                    'qty_after_transaction' => $actualQty,
                    '_user_tags' => null,
                    'batch_no' => $r->batch_no,
                    'stock_value_difference' => ($r->actual_qty * $r->valuation_rate) * -1,
                    'posting_date' => $r->posting_date,
                    'posting_datetime' => $now->format('Y-m-d H:i:s')
                ];
            }

            DB::connection('mysql')->table('tabStock Ledger Entry')->insert($data);

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateCancelledGlEntries($stockEntry)
    {
        try {
            $now = now();
            $sle = GLEntry::query()->where('voucher_no', $stockEntry)->get();

            GLEntry::query()->where('voucher_no', $stockEntry)->update(['is_cancelled' => 1]);

            $data = [];
            foreach ($sle as $r) {
                $data[] = [
                    'name' => 'ge' . uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'docstatus' => $r->docstatus,
                    'parent' => $r->parent,
                    'parentfield' => $r->parentfield,
                    'parenttype' => $r->parenttype,
                    'idx' => $r->idx,
                    'fiscal_year' => $r->fiscal_year,
                    'voucher_no' => $r->voucher_no,
                    'cost_center' => $r->cost_center,
                    'credit' => $r->debit,
                    'party_type' => $r->party_type,
                    'transaction_date' => $r->transaction_date,
                    'debit' => $r->credit,
                    'party' => $r->party,
                    '_liked_by' => null,
                    'company' => $r->company,
                    '_assign' => null,
                    'voucher_type' => $r->voucher_type,
                    '_comments' => null,
                    'is_advance' => $r->is_advance,
                    'remarks' => 'On cancellation of ' . $r->voucher_no,
                    'account_currency' => $r->account_currency,
                    'debit_in_account_currency' => $r->credit_in_account_currency,
                    '_user_tags' => null,
                    'account' => $r->account,
                    'against_voucher_type' => $r->against_voucher_type,
                    'against' => $r->against,
                    'project' => $r->project,
                    'against_voucher' => $r->against_voucher,
                    'is_opening' => $r->is_opening,
                    'posting_date' => $r->posting_date,
                    'credit_in_account_currency' => $r->debit_in_account_currency,
                    'total_allocated_amount' => $r->total_allocated_amount,
                    'is_cancelled' => 1,
                    'reference_no' => null,
                    'mode_of_payment' => null,
                    'order_type' => null,
                    'po_no' => null,
                    'reference_date' => null,
                    'cr_ref_no' => null,
                    'or_ref_no' => null,
                    'dr_ref_no' => null,
                    'pr_ref_no' => null,
                ];
            }

            GLEntry::query()->insert($data);

            return ['success' => true, 'message' => 'GL Entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function importTool()
    {
        return view('consignment.supervisor.Import_tool.index');
    }

    public function selectValues(Request $request)
    {
        $customer = Customer::query()
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->limit(15)
            ->orderBy('name')
            ->get();
        $project = Project::query()
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%' . $request->q . '%');
            })
            ->select('name as id', 'name as text')
            ->limit(15)
            ->orderBy('name')
            ->get();

        return response()->json([
            'customer' => $customer,
            'project' => $project
        ]);
    }

    public function readFile(Request $request)
    {
        try {
            $customer = $request->customer;
            $project = $request->project;
            $branch = $request->branch;
            $customerPurchaseOrder = $request->cpo;
            $path = request()->file('selected_file')->storeAs('tmp', request()->file('selected_file')->getClientOriginalName() . uniqid(), 'local');

            $path = storage_path() . '/app/' . $path;
            $reader = new ReaderXlsx();
            $spreadsheet = $reader->load($path);

            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column numbers referenced in the worksheet
            $highestRow = $sheet->getHighestRow();  // e.g. 10
            $highestColumn = 'D';  // e.g 'F'

            $sheetArr = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $sheetArr['barcode'][] = trim($sheet->getCell('A' . $row)->getValue());
                $sheetArr['description'][] = trim($sheet->getCell('B' . $row)->getValue());
                $sheetArr['sold'][] = (float) $sheet->getCell('C' . $row)->getValue();
                $sheetArr['amount'][] = (float) $sheet->getCell('D' . $row)->getValue();
            }

            $itemDetails = Item::query()
                ->from('tabItem as i')
                ->join('tabConsignment Item Barcode as b', 'b.parent', 'i.name')
                ->where('b.customer', $customer)
                ->select('b.barcode', 'b.customer', 'i.name', 'i.item_name', 'i.description', 'i.stock_uom')
                ->get();

            $itemDetails = collect($itemDetails)->groupBy('barcode');

            $items = [];
            foreach ($sheetArr['barcode'] as $i => $barcode) {
                if (!$i) {
                    continue;
                }

                $active = 0;
                $itemCode = $erpDescription = $uom = null;
                $defaultDescription = $barcode;
                $explodeBarcodeColumn = explode(' ', $barcode);
                foreach ($explodeBarcodeColumn as $code) {
                    if (isset($itemDetails[$code])) {
                        $barcode = trim($code);
                        $itemCode = $itemDetails[$barcode][0]->name;
                        $erpDescription = $itemDetails[$barcode][0]->description;
                        $uom = $itemDetails[$barcode][0]->stock_uom;
                        $active = 1;
                        break;
                    }
                }

                $description = isset($sheetArr['description'][$i]) && $sheetArr['description'][$i] != '' ? $sheetArr['description'][$i] : ($active ? $defaultDescription : null);

                if (!$description) {
                    continue;
                }

                $sold = isset($sheetArr['sold'][$i]) ? $sheetArr['sold'][$i] : 0;
                $amount = isset($sheetArr['amount'][$i]) ? $sheetArr['amount'][$i] : 0;
                $items[$barcode] = [
                    'barcode' => $barcode,
                    'active' => $active,
                    'item_code' => $itemCode,
                    'erp_description' => $erpDescription,
                    'description' => $description,
                    'sold' => isset($items[$barcode]['sold']) ? $items[$barcode]['sold'] += $sold : $sold,
                    'amount' => isset($items[$barcode]['amount']) ? $items[$barcode]['amount'] += $amount : $amount,
                    'uom' => $uom,
                ];
            }

            return view('consignment.supervisor.Import_tool.tbl', compact('items', 'customer', 'project', 'branch', 'customerPurchaseOrder'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createSalesOrder(Request $request)
    {
        try {
            $salesOrderItemData = [];

            $requestItems = $request->items;
            $requestCustomer = $request->customer;
            $requestBranchWarehouse = $request->branch_warehouse;

            $currentTimestamp = now();
            $currentTimestampString = $currentTimestamp->toDateTimeString();
            $currentUser = Auth::user()->full_name;

            // get addresses name in dynamic link based on customer
            $addressesName = DB::connection('mysql')
                ->table('tabDynamic Link as dl')
                ->join('tabAddress as a', 'dl.parent', 'a.name')
                ->where('dl.link_doctype', 'Customer')
                ->where('dl.link_name', $requestCustomer)
                ->where('a.address_type', 'Shipping')
                ->where('a.disabled', 0)
                ->orderBy('dl.parent', 'asc')
                ->pluck('a.name');

            $shippingAddressName = null;
            $currentIntersectCount = 0;
            $requestBranchWarehouseArr = explode(' ', $requestBranchWarehouse);
            foreach ($addressesName as $address) {
                $addressArr = array_map('trim', explode('-', str_replace(' ', '-', $address)));
                $intersectCount = count(array_intersect($addressArr, $requestBranchWarehouseArr));
                if ($intersectCount > $currentIntersectCount) {
                    $currentIntersectCount = $intersectCount;
                    $shippingAddressName = $address;
                }
            }

            $itemsClassification = DB::connection('mysql')
                ->table('tabItem')
                ->whereIn('name', array_filter(array_column($requestItems, 'item_code')))
                ->pluck('item_classification', 'name')
                ->toArray();

            foreach ($requestItems as $i => $item) {
                $row = $i + 1;
                $itemCode = $item['item_code'];
                if (!$itemCode) {
                    return ApiResponse::failure('Unable to find item code for Row #' . $row);
                }

                $itemClassification = Arr::get($itemsClassification, $itemCode);

                $salesOrderItemData[] = [
                    'item_code' => $itemCode,
                    'delivery_date' => $currentTimestampString,
                    'qty' => $item['qty'],
                    'rate' => $item['rate'],
                    'warehouse' => $request->branch_warehouse,
                    'item_classification' => $itemClassification,
                ];
            }

            $salesTaxes[] = [
                'charge_type' => 'On Net Total',
                'account_head' => 'Output tax - FI',
                'description' => 'Output tax',
                'rate' => 12
            ];

            $salesOrderData = [
                'customer' => $request->customer,
                'order_type' => 'Sales',
                'company' => 'FUMACO Inc.',
                'delivery_date' => $currentTimestampString,
                'po_no' => $request->po_no,
                'shipping_address_name' => $shippingAddressName,
                'disable_rounded_total' => 1,
                'order_type_1' => 'Vatable',
                'sales_type' => 'Sales on Consignment',
                'sales_person' => 'Plant 2',
                'custom_remarks' => 'Generated from AthenaERP Consignment Sales Report Import Tool. Created by: ' . $currentUser,
                'branch_warehouse' => $request->branch_warehouse,
                'project' => $request->project,
                'items' => $salesOrderItemData,
                'taxes' => $salesTaxes,
                'payment_terms_template' => 'CASH'
            ];

            $erpApiBaseUrl = config('services.erp.api_base_url');
            $response = $this->erpPost('Sales Order', $salesOrderData, true);

            if (isset($response['data']['name'])) {
                $salesOrder = $response['data']['name'];
                return ApiResponse::success('Sales Order <a href="' . $erpApiBaseUrl . '/app/sales-order/' . $salesOrder . '" target="_blank">' . $salesOrder . '</a> has been created.');
            }

            return ApiResponse::failure(data_get($response, 'message', 'Something went wrong. Please contact your system administrator.'));
        } catch (\Throwable $th) {
            return ApiResponse::failure('Something went wrong. Please contact your system administrator.');
        }
    }

    public function assignBarcodes(Request $request)
    {
        DB::beginTransaction();
        try {
            $assignedBarcodes = Item::query()
                ->from('tabItem as i')
                ->join('tabConsignment Item Barcode as b', 'b.parent', 'i.name')
                ->whereIn('b.barcode', $request->barcode)
                ->where('b.customer', $request->customer)
                ->pluck('i.name', 'b.barcode');

            $barcodes = $request->barcode;
            $itemCodes = $request->item_code;
            foreach ($barcodes as $b => $barcode) {
                if (!$itemCodes[$b]) {
                    return ApiResponse::failure('Please select item code for <b>' . $barcode . '</b>.');
                }

                if (isset($assignedBarcodes[$barcode])) {
                    return ApiResponse::failure('Barcode <b>' . $barcode . '</b> is already assigned to item <b>' . $assignedBarcodes[$barcode] . '</b>');
                }

                $insertArr[] = [
                    'name' => uniqid(),
                    'creation' => now()->toDateTimeString(),
                    'modified' => now()->toDateTimeString(),
                    'owner' => Auth::user()->wh_user,
                    'modified_by' => Auth::user()->wh_user,
                    'docstatus' => 0,
                    'idx' => 1,
                    'parent' => $itemCodes[$b],
                    'parentfield' => 'barcodes',
                    'parenttype' => 'Item',
                    'customer' => $request->customer,
                    'barcode' => $barcode
                ];
            }

            ConsignmentItemBarcode::query()->insert($insertArr);

            DB::commit();
            return ApiResponse::success('Success!');
        } catch (\Throwable $th) {
            Log::error('ConsignmentController updateItemBarcodes failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::failure('An error occured while updating item barcodes. Please contact your system administrator.');
        }
    }

    public function consignmentBranches(Request $request)
    {
        if ($request->ajax()) {
            $searchString = $request->search;
            $branches = Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
                ->where('name', '!=', 'Consignment Warehouse - FI')
                ->where('disabled', 0)
                ->with('bin', function ($bin) {
                    $bin->select('name', 'warehouse', 'item_code', 'stock_uom', 'consigned_qty', 'consignment_price', 'actual_qty', DB::raw('consigned_qty * consignment_price as amount'));
                })
                ->when($request->search, function ($query) use ($searchString) {
                    return $query->where(function ($query) use ($searchString) {
                        $searchStr = explode(' ', $searchString);
                        foreach ($searchStr as $str) {
                            $query->where('name', 'LIKE', "%$str%");
                        }

                        $query->orWhere('name', 'LIKE', "%$searchString%");
                    });
                })
                ->paginate(20);

            $warehouses = collect($branches->items())->pluck('name');

            $itemCodes = collect($branches->items())->flatMap(function ($branch) {
                return $branch->bin->pluck('item_code');
            })->unique()->values();

            $flattenItemCodes = $itemCodes->implode("','");

            $itemDetails = Item::whereRaw("name IN ('$flattenItemCodes')")
                ->where('disabled', 0)
                ->with('defaultImage', function ($image) {
                    $image->select('parent', 'image_path');
                })
                ->select('name', 'item_code', 'item_classification', 'description', 'item_name')
                ->get()
                ->groupBy('item_code');

            $promodisers = WarehouseUsers::query()
                ->from('tabWarehouse Users as wu')
                ->join('tabAssigned Consignment Warehouse as acw', 'acw.parent', 'wu.frappe_userid')
                ->whereIn('acw.warehouse', $warehouses)
                ->select('wu.*', 'acw.warehouse')
                ->get()
                ->groupBy('warehouse');

            return view('consignment.supervisor.tbl_branches', compact('branches', 'itemDetails', 'promodisers'));
        }

        return view('consignment.supervisor.branches');
    }

    public function exportToExcel($branch)
    {
        $items = Item::query()
            ->from('tabItem as i')
            ->join('tabBin as b', 'i.name', 'b.item_code')
            ->where('b.warehouse', $branch)
            ->where('i.disabled', 0)
            ->where(function ($query) {
                $query->where('b.actual_qty', '>', 0)->orWhere('b.consigned_qty', '>', 0);
            })
            ->select('i.item_code', 'i.description', 'i.item_classification', 'b.consigned_qty', 'b.warehouse', 'b.actual_qty', 'b.stock_uom', 'b.consignment_price', DB::raw('b.consigned_qty * b.consignment_price as amount'))
            ->orderBy('b.warehouse', 'asc')
            ->orderBy('b.actual_qty', 'desc')
            ->get();

        return view('consignment.supervisor.export.warehouse_items', compact('branch', 'items'));
    }

    private function getSalesAmount($start, $end, $warehouse)
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
                $saleDate = Carbon::parse($details->fiscal_year . '-' . $monthIndex . '-' . $day)->format('Y-m-d');
                if (in_array($saleDate, $includedDates)) {
                    $salesAmount += $amount;
                }
            }
        }

        return $salesAmount;
    }

    public function generateConsignmentID($table, $series, $count)
    {
        $latestRecord = DB::table($table)->orderBy('creation', 'desc')->first();

        $latestId = 0;
        if ($latestRecord) {
            if (!$latestRecord->title) {
                $lastSerialName = DB::table($table)->where('name', 'like', '%' . strtolower($series) . '-000%')->orderBy('creation', 'desc')->pluck('name')->first();

                $latestId = $lastSerialName ? explode('-', $lastSerialName)[1] : 0;
            } else {
                $latestId = $latestRecord->title ? explode('-', $latestRecord->title)[1] : 0;
            }
        }

        $newId = $latestId + 1;
        $newId = str_pad($newId, $count, 0, STR_PAD_LEFT);
        $newId = strtoupper($series) . '-' . $newId;

        return $newId;
    }
}
