<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Mail;
use DB;

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
        $sales_report_deadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $cutoff_1 = $sales_report_deadline ? $sales_report_deadline->{'1st_cutoff_date'} : 0;
        $cutoff_2 = $sales_report_deadline ? $sales_report_deadline->{'2nd_cutoff_date'} : 0;

        if(in_array(Carbon::now()->format('d'), [$cutoff_1, $cutoff_2])){
            $transaction_date = Carbon::now()->startOfMonth();
            $start_date = Carbon::parse($transaction_date)->subMonth();
            $end_date = Carbon::parse($transaction_date)->addMonths(2);

            $period = CarbonPeriod::create($start_date, '28 days' , $end_date);

            $transaction_date = $transaction_date->format('Y-m-d');
            $cutoff_period = [];
            foreach ($period as $i => $date) {
                $date1 = $date->day($cutoff_1);
                if ($date1 >= $start_date && $date1 <= $end_date) {
                    $cutoff_period[] = $date->format('Y-m-d');
                }

                if($i == 0){
                    $feb_cutoff = $cutoff_1 <= 28 ? $cutoff_1 : 28;
                    $cutoff_period[] = $feb_cutoff.'-02-'.Carbon::now()->format('Y');
                }
            }

            $cutoff_period[] = $transaction_date;
            // sort array with given user-defined function
            usort($cutoff_period, function ($time1, $time2) {
                return strtotime($time1) - strtotime($time2);
            });

            $transaction_date_index = array_search($transaction_date, $cutoff_period);
            // set cutoff date
            $period_from = Carbon::parse($cutoff_period[$transaction_date_index - 1])->startOfDay();
            $period_to = Carbon::parse($cutoff_period[$transaction_date_index + 1])->endOfDay();

            $active_promodisers = DB::table('tabWarehouse Users as wu')
                ->join('tabAssigned Consignment Warehouse as acw', 'acw.parent', 'wu.frappe_userid')
                ->join('tabWarehouse as w', 'w.warehouse_name', 'acw.warehouse_name')
                ->where('wu.enabled', 1)->where('wu.user_group', 'Promodiser')->where('w.disabled', 0)
                ->select('wu.full_name', 'wu.wh_user', 'acw.warehouse')
                ->get();

            $report_details = DB::table('tabConsignment Inventory Audit Report')
                ->select('owner', 'promodiser', 'branch_warehouse', DB::raw('max(transaction_date) as last_audit'))
                ->groupBy('owner', 'promodiser', 'branch_warehouse')
                ->get();

            $submitted_report = collect($report_details)->filter(function ($q){
                return Carbon::parse($q->last_audit) >= Carbon::now()->startOfMonth();
            })->groupBy(['owner', 'branch_warehouse']);
            $report_details = collect($report_details)->groupBy(['owner', 'branch_warehouse']);

            $report = [];
            foreach ($active_promodisers as $value) {
                if(!isset($submitted_report[$value->wh_user][$value->warehouse]))
                $report[] = [
                    'full_name' => $value->full_name,
                    'email' => $value->wh_user,
                    'warehouse' => $value->warehouse,
                    'last_audit' => isset($report_details[$value->wh_user][$value->warehouse]) ? $report_details[$value->wh_user][$value->warehouse][0]->last_audit : null
                ];
            }

            $email_data = [
                'users' => collect($report)->groupBy('full_name'),
                'cutoff_dates' => Carbon::parse($period_from)->format('F d, Y').' - '.Carbon::parse($period_to)->format('F d, Y')
            ];

            $receivers = ['hr@fumaco.local', 'consignment@fumaco.local'];
            foreach ($receivers as $receiver) {
                try {
                    Mail::mailer('local_mail')->send('mail_template.hr_promodiser_report', $email_data, function($message) use ($receiver){
                        $message->to($receiver);
                        $message->subject('AthenaERP - Promodisers Monthly Report');
                    });
                } catch (\Throwable $th) {}
            }
        }
    }
}
