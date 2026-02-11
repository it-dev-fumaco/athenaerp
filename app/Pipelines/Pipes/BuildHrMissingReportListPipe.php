<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;

class BuildHrMissingReportListPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! ($passable->shouldSendEmail ?? true)) {
            return $next($passable);
        }

        $reportDetails = DB::table('tabConsignment Inventory Audit Report')
            ->select('owner', 'promodiser', 'branch_warehouse', DB::raw('max(transaction_date) as last_audit'))
            ->groupBy('owner', 'promodiser', 'branch_warehouse')
            ->get();

        $submittedReport = collect($reportDetails)
            ->filter(fn (object $row) => Carbon::parse($row->last_audit) >= now()->startOfMonth())
            ->groupBy(['owner', 'branch_warehouse']);

        $reportDetailsGrouped = collect($reportDetails)->groupBy(['owner', 'branch_warehouse']);
        $report = [];

        foreach ($passable->activePromodisers as $value) {
            if (! isset($submittedReport[$value->wh_user][$value->warehouse])) {
                $report[] = [
                    'full_name' => $value->full_name,
                    'email' => $value->wh_user,
                    'warehouse' => $value->warehouse,
                    'last_audit' => isset($reportDetailsGrouped[$value->wh_user][$value->warehouse])
                        ? $reportDetailsGrouped[$value->wh_user][$value->warehouse][0]->last_audit
                        : null,
                ];
            }
        }

        $passable->emailData = [
            'users' => collect($report)->groupBy('full_name'),
            'cutoff_dates' => Carbon::parse($passable->periodFrom)->format('F d, Y').' - '.Carbon::parse($passable->periodTo)->format('F d, Y'),
        ];

        return $next($passable);
    }
}
