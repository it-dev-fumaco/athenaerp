<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Closure;
use Illuminate\Support\Facades\DB;

class LoadCutoffSettingsPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $salesReportDeadline = DB::table('tabConsignment Sales Report Deadline')->first();

        $passable->cutoff1 = $salesReportDeadline ? $salesReportDeadline->{'1st_cutoff_date'} : 0;
        $passable->cutoff2 = $salesReportDeadline ? $salesReportDeadline->{'2nd_cutoff_date'} : 0;

        return $next($passable);
    }
}
