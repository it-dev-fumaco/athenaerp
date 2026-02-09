<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\GLEntry;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockEntryService
{
    /**
     * Create stock ledger entries for a stock entry.
     *
     * @param string $stockEntryName Stock entry name
     * @return array{success: bool, message: string}
     */
    public function createStockLedgerEntry(string $stockEntryName): array
    {
        try {
            $now = Carbon::now();
            $stockEntryQry = StockEntry::query()->where('name', $stockEntryName)->first();

            $stockEntryDetail = StockEntryDetail::query()->where('parent', $stockEntryName)->get();

            if (in_array($stockEntryQry->purpose, ['Material Transfer for Manufacture', 'Material Transfer'])) {
                $sData = [];
                $tData = [];
                foreach ($stockEntryDetail as $row) {
                    $binQry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->s_warehouse)
                        ->where('item_code', $row->item_code)->first();

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
                        'stock_value' => ($actualQty ?? 0) * ($valuationRate ?? 0),
                        '_comments' => null,
                        'incoming_rate' => 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->s_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuationRate ?? 0,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actualQty ?? 0,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => ($row->qty * ($row->valuation_rate ?? 0)) * -1,
                        'posting_date' => $now->format('Y-m-d'),
                        'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];

                    $binQry = DB::connection('mysql')->table('tabBin')->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();

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
                        'stock_value' => ($actualQty ?? 0) * ($valuationRate ?? 0),
                        '_comments' => null,
                        'incoming_rate' => $row->basic_rate ?? 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $row->t_warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $valuationRate ?? 0,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $actualQty ?? 0,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => $row->qty * ($row->valuation_rate ?? 0),
                        'posting_date' => $now->format('Y-m-d'),
                        'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $stockLedgerEntry = array_merge($sData, $tData);

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $stockEntryName)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stockLedgerEntry);
                }
            } else {
                $stockLedgerEntry = [];
                foreach ($stockEntryDetail as $row) {
                    $warehouse = ($row->s_warehouse) ? $row->s_warehouse : $row->t_warehouse;

                    $binQry = Bin::query()->where('warehouse', $warehouse)
                        ->where('item_code', $row->item_code)->first();

                    $stockLedgerEntry[] = [
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
                        'actual_qty' => ($row->s_warehouse) ? ($row->qty * -1) : $row->qty,
                        'stock_value' => ($binQry->actual_qty ?? 0) * ($binQry->valuation_rate ?? 0),
                        '_comments' => null,
                        'incoming_rate' => ($row->t_warehouse) ? ($row->basic_rate ?? 0) : 0,
                        'voucher_detail_no' => $row->name,
                        'stock_uom' => $row->stock_uom,
                        'warehouse' => $warehouse,
                        '_liked_by' => null,
                        'company' => 'FUMACO Inc.',
                        '_assign' => null,
                        'item_code' => $row->item_code,
                        'valuation_rate' => $binQry->valuation_rate ?? 0,
                        'project' => $stockEntryQry->project,
                        'voucher_no' => $row->parent,
                        'outgoing_rate' => 0,
                        'is_cancelled' => 0,
                        'qty_after_transaction' => $binQry->actual_qty ?? 0,
                        '_user_tags' => null,
                        'batch_no' => $row->batch_no,
                        'stock_value_difference' => ($row->s_warehouse) ? ($row->qty * ($row->valuation_rate ?? 0)) * -1 : $row->qty * ($row->valuation_rate ?? 0),
                        'posting_date' => $now->format('Y-m-d'),
                        'posting_datetime' => $now->format('Y-m-d H:i:s')
                    ];
                }

                $existing = DB::connection('mysql')->table('tabStock Ledger Entry')->where('voucher_no', $stockEntryName)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('tabStock Ledger Entry')->insert($stockLedgerEntry);
                }
            }

            return ['success' => true, 'message' => 'Stock ledger entries created.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update bin quantities for a stock entry.
     *
     * @param string $stockEntryName Stock entry name
     * @return array{success: bool, message?: string}
     */
    public function updateBin(string $stockEntryName): array
    {
        try {
            $now = Carbon::now();

            $latestId = DB::connection('mysql')->table('tabBin')->where('name', 'like', '%BINM%')->max('name');
            $latestId = ($latestId) ? $latestId : 0;
            $latestIdExploded = explode("/", $latestId);
            $newId = Arr::exists($latestIdExploded, 1) ? $latestIdExploded[1] + 1 : 1;

            $stockEntryDetail = StockEntryDetail::query()->where('parent', $stockEntryName)->get();

            foreach ($stockEntryDetail as $row) {
                if ($row->s_warehouse) {
                    $binQry = Bin::query()->where('warehouse', $row->s_warehouse)
                        ->where('item_code', $row->item_code)->first();
                    if (!$binQry) {
                        $newId = $newId + 1;
                        $newId = str_pad($newId, 7, '0', STR_PAD_LEFT);
                        $id = 'BINM/' . $newId;

                        $bin = [
                            'name' => $id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'idx' => 0,
                            'reserved_qty_for_production' => 0,
                            '_liked_by' => null,
                            'fcfs_rate' => 0,
                            'reserved_qty' => 0,
                            '_assign' => null,
                            'planned_qty' => 0,
                            'item_code' => $row->item_code,
                            'actual_qty' => $row->transfer_qty,
                            'projected_qty' => $row->transfer_qty,
                            'ma_rate' => 0,
                            'stock_uom' => $row->stock_uom,
                            '_comments' => null,
                            'ordered_qty' => 0,
                            'reserved_qty_for_sub_contract' => 0,
                            'indented_qty' => 0,
                            'warehouse' => $row->s_warehouse,
                            'stock_value' => $row->valuation_rate * $row->transfer_qty,
                            '_user_tags' => null,
                            'valuation_rate' => $row->valuation_rate,
                        ];

                        Bin::query()->insert($bin);
                    } else {
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $binQry->actual_qty - $row->transfer_qty,
                            'stock_value' => $binQry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $binQry->valuation_rate,
                        ];

                        Bin::query()->where('name', $binQry->name)->update($bin);
                    }
                }

                if ($row->t_warehouse) {
                    $binQry = Bin::query()->where('warehouse', $row->t_warehouse)
                        ->where('item_code', $row->item_code)->first();
                    if (!$binQry) {
                        $newId = $newId + 1;
                        $newId = str_pad($newId, 7, '0', STR_PAD_LEFT);
                        $id = 'BINM/' . $newId;

                        $bin = [
                            'name' => $id,
                            'creation' => $now->toDateTimeString(),
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'owner' => Auth::user()->wh_user,
                            'docstatus' => 0,
                            'idx' => 0,
                            'reserved_qty_for_production' => 0,
                            '_liked_by' => null,
                            'fcfs_rate' => 0,
                            'reserved_qty' => 0,
                            '_assign' => null,
                            'planned_qty' => 0,
                            'item_code' => $row->item_code,
                            'actual_qty' => $row->transfer_qty,
                            'projected_qty' => $row->transfer_qty,
                            'ma_rate' => 0,
                            'stock_uom' => $row->stock_uom,
                            '_comments' => null,
                            'ordered_qty' => 0,
                            'reserved_qty_for_sub_contract' => 0,
                            'indented_qty' => 0,
                            'warehouse' => $row->t_warehouse,
                            'stock_value' => $row->valuation_rate * $row->transfer_qty,
                            '_user_tags' => null,
                            'valuation_rate' => $row->valuation_rate,
                        ];

                        Bin::query()->insert($bin);
                    } else {
                        $bin = [
                            'modified' => $now->toDateTimeString(),
                            'modified_by' => Auth::user()->wh_user,
                            'actual_qty' => $binQry->actual_qty + $row->transfer_qty,
                            'stock_value' => $binQry->valuation_rate * $row->transfer_qty,
                            'valuation_rate' => $binQry->valuation_rate,
                        ];

                        Bin::query()->where('name', $binQry->name)->update($bin);
                    }
                }
            }

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'id' => $stockEntryName];
        }
    }

    /**
     * Create GL entries for a stock entry.
     *
     * @param string $stockEntryName Stock entry name
     * @return array{success: bool, message: string}
     */
    public function createGlEntry(string $stockEntryName): array
    {
        try {
            $now = Carbon::now();
            $stockEntryQry = StockEntry::query()->where('name', $stockEntryName)->first();
            $stockEntryDetail = StockEntryDetail::query()
                ->where('parent', $stockEntryName)
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
}
