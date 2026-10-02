<?php

namespace App\Http\Controllers;

use App\Models\AthenaTransaction;
use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    use GeneralTrait;

    public function pendingSubmitReport(Request $request)
    {
        $itemCode = trim((string) $request->query('item_code', ''));
        $documentType = $request->query('document_type', 'all');
        if (! in_array($documentType, ['all', 'ste', 'dr'], true)) {
            $documentType = 'all';
        }
        $warehouses = Auth::user()->allowedWarehouseIds()->filter()->values();
        $lines = collect();

        if ($warehouses->isNotEmpty()) {
            $steLines = collect();
            if ($documentType !== 'dr') {
                $steLines = StockEntryDetail::query()
                    ->issuedOnDraftStockEntry()
                    ->when($itemCode !== '', function ($query) use ($itemCode) {
                        $query->where('tabStock Entry Detail.item_code', 'like', '%'.$itemCode.'%');
                    })
                    ->whereIn('tabStock Entry Detail.s_warehouse', $warehouses)
                    ->select([
                        'ste.name as document_no',
                        'ste.creation as date_created',
                        'ste.purpose as transaction_type',
                        'tabStock Entry Detail.status as item_status',
                        'tabStock Entry Detail.date_modified as date_issued',
                        'tabStock Entry Detail.session_user as issued_by',
                        'ste.item_status as main_status',
                        DB::raw("'Draft' as doc_status"),
                        'tabStock Entry Detail.qty as qty',
                        'tabStock Entry Detail.item_code',
                        'tabStock Entry Detail.s_warehouse as warehouse',
                    ])
                    ->get();
            }

            $drLines = collect();
            if ($documentType !== 'ste') {
                $drLines = AthenaTransaction::query()
                    ->joinPackingSlipDeliveryNote()
                    ->when($itemCode !== '', function ($query) use ($itemCode) {
                        $query->where('at.item_code', 'like', '%'.$itemCode.'%');
                    })
                    ->whereIn('at.source_warehouse', $warehouses)
                    ->groupBy('ps.name', 'ps.item_status', 'at.reference_type', 'at.item_code', 'at.source_warehouse')
                    ->select([
                        'ps.name as document_no',
                        DB::raw('MIN(ps.creation) as date_created'),
                        'at.reference_type as transaction_type',
                        DB::raw("'Issued' as item_status"),
                        DB::raw('MAX(at.transaction_date) as date_issued'),
                        DB::raw("GROUP_CONCAT(DISTINCT at.warehouse_user SEPARATOR ', ') as issued_by"),
                        'ps.item_status as main_status',
                        DB::raw("'DR Draft' as doc_status"),
                        DB::raw('SUM(at.issued_qty) as qty'),
                        'at.item_code',
                        'at.source_warehouse as warehouse',
                    ])
                    ->get();

                $slipLines = DB::table('tabPacking Slip as ps')
                    ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                    ->join('tabDelivery Note as dr', 'dr.name', 'ps.delivery_note')
                    ->join('tabDelivery Note Item as dri', function ($join) {
                        $join->on('dri.parent', '=', 'dr.name')
                            ->on('dri.item_code', '=', 'psi.item_code');
                    })
                    ->where('psi.status', 'Issued')
                    ->where('dr.docstatus', 0)
                    ->where('ps.docstatus', '<', 2)
                    ->whereIn('dri.warehouse', $warehouses)
                    ->when($itemCode !== '', function ($query) use ($itemCode) {
                        $query->where('psi.item_code', 'like', '%'.$itemCode.'%');
                    })
                    ->groupBy('ps.name', 'ps.item_status', 'psi.item_code', 'dri.warehouse')
                    ->select([
                        'ps.name as document_no',
                        DB::raw('MIN(ps.creation) as date_created'),
                        DB::raw("'Picking Slip' as transaction_type"),
                        DB::raw("'Issued' as item_status"),
                        DB::raw('MAX(psi.date_modified) as date_issued'),
                        DB::raw("GROUP_CONCAT(DISTINCT psi.session_user SEPARATOR ', ') as issued_by"),
                        'ps.item_status as main_status',
                        DB::raw("'DR Draft' as doc_status"),
                        DB::raw('SUM(psi.qty) as qty'),
                        'psi.item_code',
                        'dri.warehouse as warehouse',
                    ])
                    ->get();

                $loggedSlips = $drLines->mapWithKeys(function ($line) {
                    return [$line->document_no.'|'.$line->item_code.'|'.$line->warehouse => true];
                });
                $slipLines = $slipLines->reject(function ($line) use ($loggedSlips) {
                    return $loggedSlips->has($line->document_no.'|'.$line->item_code.'|'.$line->warehouse);
                })->map(function ($line) {
                    $line->transaction_type = 'Picking Slip';

                    return $line;
                });

                $drLines = $drLines->concat($slipLines)->map(function ($line) {
                    if ($line->transaction_type === 'Packing Slip') {
                        $line->transaction_type = 'Delivery';
                    }

                    return $line;
                });
            }

            $lines = $steLines->concat($drLines);
        }

        $rows = $this->pendingSubmitRows($lines);

        if ($request->boolean('export')) {
            return $this->downloadPendingSubmitReport($rows);
        }

        return view('reports.pending_submit', [
            'rows' => $this->paginatePendingSubmitRows($rows, $request),
            'itemCode' => $itemCode,
            'documentType' => $documentType,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $lines
     * @return list<array<string, mixed>>
     */
    private function pendingSubmitRows($lines): array
    {
        $sorted = $lines->sortBy([
            ['item_code', 'asc'],
            ['warehouse', 'asc'],
            ['document_no', 'asc'],
        ])->values();

        $pairs = [];
        foreach ($sorted as $line) {
            if (! $line->item_code || ! $line->warehouse) {
                continue;
            }
            $pairs[$line->item_code.'-'.$line->warehouse] = [$line->item_code, $line->warehouse];
        }

        $pairList = array_values($pairs);
        $actualMap = $this->getActualQtyBulk($pairList);
        $availableMap = $this->getAvailableQtyBulk($pairList);

        $rows = [];
        foreach ($sorted->groupBy('item_code') as $itemCode => $itemLines) {
            $firstItem = true;
            foreach ($itemLines->groupBy('warehouse') as $warehouse => $warehouseLines) {
                $stockKey = $itemCode.'-'.$warehouse;
                $firstWarehouse = true;
                foreach ($warehouseLines as $line) {
                    $rows[] = [
                        'show_item' => $firstItem,
                        'item_rowspan' => $itemLines->count(),
                        'item_code' => $itemCode,
                        'document_no' => $line->document_no,
                        'date_created' => $this->formatPendingSubmitDate($line->date_created ?? null),
                        'transaction_type' => $line->transaction_type,
                        'item_status' => $line->item_status,
                        'date_issued' => $this->formatPendingSubmitDate($line->date_issued ?? null),
                        'issued_by' => $line->issued_by ?: '',
                        'main_status' => $line->main_status,
                        'doc_status' => $line->doc_status,
                        'qty' => (float) $line->qty,
                        'show_warehouse' => $firstWarehouse,
                        'warehouse_rowspan' => $warehouseLines->count(),
                        'warehouse' => $warehouse,
                        'actual' => $actualMap[$stockKey] ?? 0,
                        'available' => $availableMap[$stockKey] ?? 0,
                    ];
                    $firstItem = false;
                    $firstWarehouse = false;
                }
            }
        }

        return $rows;
    }

    private function formatPendingSubmitDate($value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('M d, Y h:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function paginatePendingSubmitRows(array $rows, Request $request): LengthAwarePaginator
    {
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));
        $slice = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $this->recalculatePendingSubmitRowspans($slice),
            count($rows),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Row spans are limited to the current page so a group is not cut across the pager.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function recalculatePendingSubmitRowspans(array $rows): array
    {
        $count = count($rows);
        $index = 0;

        while ($index < $count) {
            $itemEnd = $index;
            while ($itemEnd < $count && $rows[$itemEnd]['item_code'] === $rows[$index]['item_code']) {
                $itemEnd++;
            }

            $warehouseIndex = $index;
            while ($warehouseIndex < $itemEnd) {
                $warehouseEnd = $warehouseIndex;
                while ($warehouseEnd < $itemEnd && $rows[$warehouseEnd]['warehouse'] === $rows[$warehouseIndex]['warehouse']) {
                    $warehouseEnd++;
                }

                for ($i = $warehouseIndex; $i < $warehouseEnd; $i++) {
                    $rows[$i]['show_item'] = $i === $index;
                    $rows[$i]['item_rowspan'] = $itemEnd - $index;
                    $rows[$i]['show_warehouse'] = $i === $warehouseIndex;
                    $rows[$i]['warehouse_rowspan'] = $warehouseEnd - $warehouseIndex;
                }

                $warehouseIndex = $warehouseEnd;
            }

            $index = $itemEnd;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function downloadPendingSubmitReport(array $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pending DR and STE');

        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'Pending DR and STE that are still in Draft Status but items are already issued');
        $sheet->mergeCells('A2:A3');
        $sheet->setCellValue('A2', 'Item Code');
        $sheet->mergeCells('B2:J2');
        $sheet->setCellValue('B2', 'Pending Transactions');
        $sheet->mergeCells('K2:M2');
        $sheet->setCellValue('K2', 'Current Available Stock Per Source');

        $subHeaders = ['No.', 'Date Created', 'Transaction Type', 'Item Status', 'Date Issued', 'Issued By', 'Main Status', 'Doc Status', 'Qty', 'Warehouse', 'Actual', 'Available'];
        $column = 'B';
        foreach ($subHeaders as $header) {
            $sheet->setCellValue($column.'3', $header);
            $column++;
        }

        $excelRow = 4;
        foreach ($rows as $row) {
            if ($row['show_item']) {
                $sheet->setCellValue('A'.$excelRow, $row['item_code']);
                if ($row['item_rowspan'] > 1) {
                    $sheet->mergeCells('A'.$excelRow.':A'.($excelRow + $row['item_rowspan'] - 1));
                }
            }

            $sheet->setCellValue('B'.$excelRow, $row['document_no']);
            $sheet->setCellValue('C'.$excelRow, $row['date_created']);
            $sheet->setCellValue('D'.$excelRow, $row['transaction_type']);
            $sheet->setCellValue('E'.$excelRow, $row['item_status']);
            $sheet->setCellValue('F'.$excelRow, $row['date_issued']);
            $sheet->setCellValue('G'.$excelRow, $row['issued_by']);
            $sheet->setCellValue('H'.$excelRow, $row['main_status'] ?: '');
            $sheet->setCellValue('I'.$excelRow, $row['doc_status']);
            $sheet->setCellValue('J'.$excelRow, $row['qty']);

            if ($row['show_warehouse']) {
                $sheet->setCellValue('K'.$excelRow, $row['warehouse']);
                $sheet->setCellValue('L'.$excelRow, $row['actual']);
                $sheet->setCellValue('M'.$excelRow, $row['available']);
                if ($row['warehouse_rowspan'] > 1) {
                    $end = $excelRow + $row['warehouse_rowspan'] - 1;
                    $sheet->mergeCells('K'.$excelRow.':K'.$end);
                    $sheet->mergeCells('L'.$excelRow.':L'.$end);
                    $sheet->mergeCells('M'.$excelRow.':M'.$end);
                }
            }

            $excelRow++;
        }

        $lastRow = max(3, $excelRow - 1);
        $sheet->getStyle('A1:M'.$lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('A1:M3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE9EEF5'],
            ],
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'pending-dr-ste-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function salesReport(Request $request)
    {
        if (! $request->report_type) {
            return view('external_reports.sales_report');
        }

        $exportExcel = $request->export;

        $start = new Carbon('first day of January '.$request->year);
        $end = new Carbon('last day of December '.$request->year);

        $reportType = $request->report_type;

        $itemCodes = ['LR00440', 'DO00433', 'DO00435', 'BT00673', 'BT00674', 'BT00675', 'BT00677', 'BT00678', 'BT00679', 'BT00686', 'BT00681', 'BT00683', 'BT00684'];

        if ($reportType == 'lazada_orders') {
            $query = StockEntry::query()
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'tabStock Entry.name')
                ->where('tabStock Entry.purpose', 'Material Issue')
                ->where('tabStock Entry.docstatus', 1)
                ->where('tabStock Entry.remarks', 'like', '%lazada%')
                ->whereBetween('tabStock Entry.posting_date', [$start, $end])
                ->select('tabStock Entry.name', 'tabStock Entry.posting_date', 'tabStock Entry.purpose', 'tabStock Entry.remarks', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom', 'sted.date_modified', 'sted.status', 'sted.session_user')
                ->orderBy('tabStock Entry.posting_date', 'asc')
                ->orderBy('sted.item_code', 'asc')
                ->get();
        }

        if ($reportType == 'withdrawals') {
            $query = StockEntry::query()
                ->join('tabStock Entry Detail as sted', 'sted.parent', 'tabStock Entry.name')
                ->where('tabStock Entry.purpose', 'Manufacture')
                ->where('tabStock Entry.docstatus', 1)
                ->whereIn('sted.item_code', $itemCodes)
                ->whereBetween('tabStock Entry.posting_date', [$start, $end])
                ->select('tabStock Entry.work_order', 'tabStock Entry.posting_date', 'tabStock Entry.sales_order_no', 'tabStock Entry.so_customer_name', 'tabStock Entry.project', 'tabStock Entry.name', 'sted.item_code', 'sted.description', 'sted.transfer_qty', 'sted.stock_uom')
                ->orderBy('tabStock Entry.posting_date', 'asc')
                ->orderBy('sted.item_code', 'asc')
                ->get();
        }

        if ($reportType == 'sales_orders') {
            $query = DeliveryNote::query()
                ->join('tabDelivery Note Item as dri', 'dri.parent', 'tabDelivery Note.name')
                ->whereIn('tabDelivery Note.status', ['Completed', 'To Bill'])
                ->where('tabDelivery Note.docstatus', 1)
                ->whereIn('dri.item_code', $itemCodes)
                ->whereBetween('tabDelivery Note.posting_date', [$start, $end])
                ->select('tabDelivery Note.posting_date', 'tabDelivery Note.sales_order', 'tabDelivery Note.customer', 'tabDelivery Note.project', 'tabDelivery Note.name', 'dri.item_code', 'dri.description', 'dri.qty', 'dri.stock_uom', 'tabDelivery Note.status')
                ->orderBy('tabDelivery Note.posting_date', 'asc')
                ->orderBy('dri.item_code', 'asc')
                ->get();
        }

        return view('external_reports.sales_report_table', compact('query', 'reportType', 'exportExcel'));
    }

    public function salesReportSummary(Request $request, $year)
    {
        $itemCodes = ['LR00440', 'DO00433', 'DO00435', 'BT00673', 'BT00674', 'BT00675', 'BT00677', 'BT00678', 'BT00679', 'BT00686', 'BT00681', 'BT00683', 'BT00684'];

        $lazadaOrders = StockEntry::query()
            ->join('tabStock Entry Detail as sted', 'sted.parent', 'tabStock Entry.name')
            ->where('tabStock Entry.purpose', 'Material Issue')
            ->where('tabStock Entry.docstatus', 1)
            ->where('tabStock Entry.remarks', 'like', '%lazada%')
            ->where(DB::raw('YEAR(tabStock Entry.posting_date)'), $year)
            ->select('sted.item_code', 'sted.transfer_qty', DB::raw('MONTH(tabStock Entry.posting_date) as month'), DB::raw('YEAR(tabStock Entry.posting_date) as year'))
            ->get();

        $withdrawals = StockEntry::query()
            ->join('tabStock Entry Detail as sted', 'sted.parent', 'tabStock Entry.name')
            ->where('tabStock Entry.purpose', 'Manufacture')
            ->where('tabStock Entry.docstatus', 1)
            ->whereIn('sted.item_code', $itemCodes)
            ->where(DB::raw('YEAR(tabStock Entry.posting_date)'), $year)
            ->select('sted.item_code', 'sted.transfer_qty', DB::raw('MONTH(tabStock Entry.posting_date) as month'), DB::raw('YEAR(tabStock Entry.posting_date) as year'))
            ->get();

        $salesOrders = DeliveryNote::query()
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'tabDelivery Note.name')
            ->whereIn('tabDelivery Note.status', ['Completed', 'To Bill'])
            ->where('tabDelivery Note.docstatus', 1)
            ->whereIn('dri.item_code', $itemCodes)
            ->where(DB::raw('YEAR(tabDelivery Note.posting_date)'), $year)
            ->select('dri.item_code', 'dri.qty', DB::raw('MONTH(tabDelivery Note.posting_date) as month'), DB::raw('YEAR(tabDelivery Note.posting_date) as year'))
            ->get();

        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $itemsMap = Item::whereIn('name', $itemCodes)->get()->keyBy('name');
        $result = [];
        foreach ($itemCodes as $itemCode) {
            $itemInfo = $itemsMap->get($itemCode);
            $itemDescription = $itemInfo ? $itemInfo->description : null;
            $perMonth = [];
            foreach ($months as $monthIndex => $month) {
                $monthNumber = $monthIndex + 1;
                $lazadaOrdersQty = collect($lazadaOrders)->where('item_code', $itemCode)->where('month', $monthNumber)->sum('transfer_qty');
                $withdrawalsQty = collect($withdrawals)->where('item_code', $itemCode)->where('month', $monthNumber)->sum('transfer_qty');
                $salesOrdersQty = collect($salesOrders)->where('item_code', $itemCode)->where('month', $monthNumber)->sum('qty');

                $perMonth[] = [
                    'month' => $month,
                    'lazada' => $lazadaOrdersQty,
                    'sales' => $salesOrdersQty,
                    'withdrawals' => $withdrawalsQty,
                ];
            }

            $totalSalesOrderQty = collect($perMonth)->sum('sales');
            $totalLazadaQty = collect($perMonth)->sum('lazada');
            $totalStockEntryQty = collect($perMonth)->sum('withdrawals');
            $overallTotalQty = $totalStockEntryQty + $totalLazadaQty + $totalSalesOrderQty;

            $result[] = [
                'item_code' => $itemCode,
                'description' => $itemDescription,
                'per_month' => $perMonth,
                'total_so_qty' => $totalSalesOrderQty,
                'total_laz_qty' => $totalLazadaQty,
                'total_ste_qty' => $totalStockEntryQty,
                'overall_total' => $overallTotalQty,
            ];
        }

        $reportType = 'summary';
        $exportExcel = $request->export;

        return view('external_reports.sales_report_table', compact('result', 'reportType', 'months', 'exportExcel'));
    }
}
