<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\ConsignmentItemBarcode;
use App\Models\ConsignmentStockAdjustment;
use App\Models\Customer;
use App\Models\GLEntry;
use App\Models\Item;
use App\Models\Project;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\StockLedgerEntry;
use App\Models\Warehouse;
use App\Models\WarehouseUsers;
use App\Pipelines\ConsignmentLedgerPipeline;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReaderXlsx;

class ConsignmentController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function __construct(
        protected ConsignmentLedgerPipeline $consignmentLedgerPipeline
    ) {}

    // /consignment_stores
    public function consignmentStores(Request $request)
    {
        if ($request->ajax()) {
            if ($request->has('assigned_to_me') && $request->assigned_to_me == 1) {  // only get warehouses assigned to the promodiser
                return AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->where('warehouse', 'LIKE', '%'.$request->q.'%')->select('warehouse as id', 'warehouse as text')->limit(20)->orderBy('warehouse', 'asc')->get();
            } else {  // get all warehouses
                return Warehouse::where('parent_warehouse', 'P2 Consignment Warehouse - FI')
                    ->where('is_group', 0)
                    ->where('disabled', 0)
                    ->where('name', 'LIKE', '%'.$request->q.'%')
                    ->select('name as id', 'warehouse_name as text')
                    ->limit(20)
                    ->orderBy('warehouse_name', 'asc')
                    ->get();
            }
        }
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

    public function getErpItems(Request $request)
    {
        $searchTerms = explode(' ', $request->q);

        return Item::query()
            ->where('disabled', 0)
            ->where('has_variants', 0)
            ->where('is_stock_item', 1)
            ->when($request->q, function ($query) use ($request, $searchTerms) {
                return $query->where(function ($subQuery) use ($searchTerms, $request) {
                    foreach ($searchTerms as $term) {
                        $subQuery->where('description', 'LIKE', '%'.$term.'%');
                    }

                    $subQuery->orWhere('item_code', 'LIKE', '%'.$request->q.'%');
                });
            })
            ->select('item_code as id', 'description')
            ->orderBy('item_code', 'asc')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'text' => $item->id.' - '.strip_tags($item->description),
                'description' => $item->description,
            ]);
    }

    public function consignmentLedger(Request $request)
    {
        if (! $request->ajax()) {
            return view('consignment.consignment_ledger');
        }

        $passable = (object) [
            'branchWarehouse' => $request->branch_warehouse,
            'itemCode' => $request->item_code,
        ];

        return $this->consignmentLedgerPipeline->run($passable);
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
                    'creation' => $r->creation,
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
                if (! $owner) {
                    $owner = $a->parent_received_by ? $a->parent_received_by : $a->modified_by;
                }

                $result[] = [
                    'qty' => number_format($a->transfer_qty),
                    'type' => 'Stocks Received',
                    'transaction_date' => $dateReceived,
                    'branch_warehouse' => $a->t_warehouse,
                    'reference' => $a->name,
                    'owner' => $owner,
                    'creation' => $a->creation,
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
                    'qty' => '-'.number_format($v->transfer_qty),
                    'type' => 'Store Transfer',
                    'transaction_date' => $dateTransferred,
                    'branch_warehouse' => $v->s_warehouse,
                    'reference' => $v->name,
                    'owner' => $v->owner,
                    'creation' => $v->creation,
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
                    'qty' => '-'.number_format($a->transfer_qty),
                    'type' => 'Stocks Returned',
                    'transaction_date' => $dateReturned,
                    'branch_warehouse' => $a->s_warehouse,
                    'reference' => $a->name,
                    'owner' => $a->owner,
                    'creation' => $a->creation,
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
                    'creation' => $sa->creation,
                ];
            }
        }

        if ($request->get_users == 1) {
            $all[] = [
                'id' => 'Select All',
                'text' => 'Select All',
            ];

            $users = collect($result)->map(function ($row) {
                if ($row['owner']) {
                    return [
                        'id' => $row['owner'],
                        'text' => $row['owner'],
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
                    'name' => 'ath'.uniqid(),
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
                        'name' => 'ath'.uniqid(),
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
                        'name' => 'ath'.uniqid(),
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
                        'posting_datetime' => $now->format('Y-m-d H:i:s'),
                    ];
                }

                $stockLedgerEntry = array_merge($sData, $tData);

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (! $existing) {
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
                        'name' => 'ath'.uniqid(),
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
                        'posting_datetime' => $now->format('Y-m-d H:i:s'),
                    ];
                }

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $row->parent)->exists();
                if (! $existing) {
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
                    'name' => 'cn'.uniqid(),
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
                    'posting_datetime' => $now->format('Y-m-d H:i:s'),
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
                    'name' => 'ge'.uniqid(),
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
                    'remarks' => 'On cancellation of '.$r->voucher_no,
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
                return $query->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->limit(15)
            ->orderBy('name')
            ->get();
        $project = Project::query()
            ->when($request->q, function ($query) use ($request) {
                return $query->where('name', 'like', '%'.$request->q.'%');
            })
            ->select('name as id', 'name as text')
            ->limit(15)
            ->orderBy('name')
            ->get();

        return response()->json([
            'customer' => $customer,
            'project' => $project,
        ]);
    }

    public function readFile(Request $request)
    {
        try {
            $customer = $request->customer;
            $project = $request->project;
            $branch = $request->branch;
            $customerPurchaseOrder = $request->cpo;
            $path = request()->file('selected_file')->storeAs('tmp', request()->file('selected_file')->getClientOriginalName().uniqid(), 'upcloud');

            $file = Storage::disk('upcloud')->path($path);
            $reader = new XlsxReaderXlsx;
            $spreadsheet = $reader->load($file);

            $sheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column numbers referenced in the worksheet
            $highestRow = $sheet->getHighestRow();  // e.g. 10
            $highestColumn = 'D';  // e.g 'F'

            $sheetArr = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $sheetArr['barcode'][] = trim($sheet->getCell('A'.$row)->getValue());
                $sheetArr['description'][] = trim($sheet->getCell('B'.$row)->getValue());
                $sheetArr['sold'][] = (float) $sheet->getCell('C'.$row)->getValue();
                $sheetArr['amount'][] = (float) $sheet->getCell('D'.$row)->getValue();
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
                if (! $i) {
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

                if (! $description) {
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
                if (! $itemCode) {
                    return ApiResponse::failure('Unable to find item code for Row #'.$row);
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
                'rate' => 12,
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
                'custom_remarks' => 'Generated from AthenaERP Consignment Sales Report Import Tool. Created by: '.$currentUser,
                'branch_warehouse' => $request->branch_warehouse,
                'project' => $request->project,
                'items' => $salesOrderItemData,
                'taxes' => $salesTaxes,
                'payment_terms_template' => 'CASH',
            ];

            $erpApiBaseUrl = config('services.erp.api_base_url');
            $response = $this->erpPost('Sales Order', $salesOrderData, true);

            if (isset($response['data']['name'])) {
                $salesOrder = $response['data']['name'];

                return ApiResponse::success('Sales Order <a href="'.$erpApiBaseUrl.'/app/sales-order/'.$salesOrder.'" target="_blank">'.$salesOrder.'</a> has been created.');
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
                if (! $itemCodes[$b]) {
                    return ApiResponse::failure('Please select item code for <b>'.$barcode.'</b>.');
                }

                if (isset($assignedBarcodes[$barcode])) {
                    return ApiResponse::failure('Barcode <b>'.$barcode.'</b> is already assigned to item <b>'.$assignedBarcodes[$barcode].'</b>');
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
                    'barcode' => $barcode,
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
                        $searchTerms = explode(' ', $searchString);
                        foreach ($searchTerms as $term) {
                            $query->where('name', 'LIKE', "%$term%");
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

    public function generateConsignmentID($table, $series, $count)
    {
        $latestRecord = DB::table($table)->orderBy('creation', 'desc')->first();

        $latestId = 0;
        if ($latestRecord) {
            if (! $latestRecord->title) {
                $lastSerialName = DB::table($table)->where('name', 'like', '%'.strtolower($series).'-000%')->orderBy('creation', 'desc')->pluck('name')->first();

                $latestId = $lastSerialName ? explode('-', $lastSerialName)[1] : 0;
            } else {
                $latestId = $latestRecord->title ? explode('-', $latestRecord->title)[1] : 0;
            }
        }

        $newId = $latestId + 1;
        $newId = str_pad($newId, $count, 0, STR_PAD_LEFT);
        $newId = strtoupper($series).'-'.$newId;

        return $newId;
    }
}
