<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailHR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:hr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email alert to hr';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $salesReportDeadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff1 = $salesReportDeadline ? $salesReportDeadline->{'1st_cutoff_date'} : 0;
        $cutoff2 = $salesReportDeadline ? $salesReportDeadline->{'2nd_cutoff_date'} : 0;

        if (in_array(now()->format('d'), [$cutoff1, $cutoff2])) {
            $transactionDate = now()->startOfMonth();
            $startDate = Carbon::parse($transactionDate)->subMonth();
            $endDate = Carbon::parse($transactionDate)->addMonths(2);

            $period = CarbonPeriod::create($startDate, '28 days', $endDate);

            $transactionDate = $transactionDate->format('Y-m-d');
            $cutoffPeriod = [];
            foreach ($period as $i => $date) {
                $date1 = $date->day($cutoff1);
                if ($date1 >= $startDate && $date1 <= $endDate) {
                    $cutoffPeriod[] = $date->format('Y-m-d');
                }

                if ($i == 0) {
                    $febCutoff = $cutoff1 <= 28 ? $cutoff1 : 28;
                    $cutoffPeriod[] = $febCutoff . '-02-' . now()->format('Y');
                }
            }

            $cutoffPeriod[] = $transactionDate;
            // sort array with given user-defined function
            usort($cutoffPeriod, function ($time1, $time2) {
                return strtotime($time1) - strtotime($time2);
            });

            $transactionDateIndex = array_search($transactionDate, $cutoffPeriod);
            // set cutoff date
            $periodFrom = Carbon::parse($cutoffPeriod[$transactionDateIndex - 1])->startOfDay();
            $periodTo = Carbon::parse($cutoffPeriod[$transactionDateIndex + 1])->endOfDay();

            $activePromodisers = DB::table('tabWarehouse Users as wu')
                ->join('tabAssigned Consignment Warehouse as acw', 'acw.parent', 'wu.frappe_userid')
                ->join('tabWarehouse as w', 'w.warehouse_name', 'acw.warehouse_name')
                ->where('wu.enabled', 1)
                ->where('wu.user_group', 'Promodiser')
                ->where('w.disabled', 0)
                ->select('wu.full_name', 'wu.wh_user', 'acw.warehouse')
                ->get();

            $reportDetails = DB::table('tabConsignment Inventory Audit Report')
                ->select('owner', 'promodiser', 'branch_warehouse', DB::raw('max(transaction_date) as last_audit'))
                ->groupBy('owner', 'promodiser', 'branch_warehouse')
                ->get();

            $submittedReport = collect($reportDetails)->filter(function (object $q) {
                return Carbon::parse($q->last_audit) >= now()->startOfMonth();
            })->groupBy(['owner', 'branch_warehouse']);
            $reportDetails = collect($reportDetails)->groupBy(['owner', 'branch_warehouse']);

            $report = [];
            foreach ($activePromodisers as $value) {
                if (!isset($submittedReport[$value->wh_user][$value->warehouse]))
                    $report[] = [
                        'full_name' => $value->full_name,
                        'email' => $value->wh_user,
                        'warehouse' => $value->warehouse,
                        'last_audit' => isset($reportDetails[$value->wh_user][$value->warehouse]) ? $reportDetails[$value->wh_user][$value->warehouse][0]->last_audit : null
                    ];
            }

            $emailData = [
                'users' => collect($report)->groupBy('full_name'),
                'cutoff_dates' => Carbon::parse($periodFrom)->format('F d, Y') . ' - ' . Carbon::parse($periodTo)->format('F d, Y')
            ];

            $receivers = ['hr@fumaco.local', 'consignment@fumaco.local'];
            foreach ($receivers as $receiver) {
                try {
                    Mail::mailer('local_mail')->send('mail_template.hr_promodiser_report', $emailData, function ($message) use ($receiver) {
                        $message->to($receiver);
                        $message->subject('AthenaERP - Promodisers Monthly Report');
                    });
                } catch (\Throwable $th) {
                }
            }
        }

        return self::SUCCESS;
    }
}
