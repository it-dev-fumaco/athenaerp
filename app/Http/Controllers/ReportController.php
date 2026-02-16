<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\StockEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
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

        $result = [];
        foreach ($itemCodes as $itemCode) {
            $itemInfo = Item::find($itemCode);
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
