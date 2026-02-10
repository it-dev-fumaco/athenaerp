<?php

namespace App\Http\Controllers\Consignment;

use App\Http\Controllers\Controller;
use App\Models\ConsignmentMonthlySalesReport;
use App\Models\ConsignmentSalesReportDeadline;
use App\Services\CutoffDateService;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Exception;

class ConsignmentSalesController extends Controller
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
                    $firstCutoff = Carbon::createFromFormat('m/d/Y', $request->month . '/' . $cutoffDay . '/' . $request->year)
                        ->format('F d, Y');

                    return 'Deadline: ' . $firstCutoff;
                } catch (Exception $e) {
                    return 'Invalid date format.';
                }
            }
        }

        return 'No deadline data available.';
    }

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
            $cutoffDate = app(CutoffDateService::class)->getCutoffPeriod($transactionMonth)[1];

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

            $salesReport = ConsignmentMonthlySalesReport::where('fiscal_year', $request->year)
                ->where('month', $request->month)
                ->where('warehouse', $request->branch)
                ->first();

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
            Log::error('ConsignmentSalesController submitMonthlySaleForm failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', $th->getMessage());
        }
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
}
