<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Closure;

class ComputeCutoffPeriodPipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (! in_array(now()->format('d'), [$passable->cutoff1, $passable->cutoff2])) {
            $passable->shouldSendEmail = false;

            return $next($passable);
        }

        $passable->shouldSendEmail = true;
        $transactionDate = now()->startOfMonth();
        $startDate = Carbon::parse($transactionDate)->subMonth();
        $endDate = Carbon::parse($transactionDate)->addMonths(2);

        $period = CarbonPeriod::create($startDate, '28 days', $endDate);
        $transactionDateStr = $transactionDate->format('Y-m-d');
        $cutoffPeriod = [];

        foreach ($period as $i => $date) {
            $date1 = $date->day($passable->cutoff1);
            if ($date1 >= $startDate && $date1 <= $endDate) {
                $cutoffPeriod[] = $date->format('Y-m-d');
            }
            if ($i === 0) {
                $febCutoff = $passable->cutoff1 <= 28 ? $passable->cutoff1 : 28;
                $cutoffPeriod[] = $febCutoff.'-02-'.now()->format('Y');
            }
        }

        $cutoffPeriod[] = $transactionDateStr;
        usort($cutoffPeriod, fn ($a, $b) => strtotime($a) - strtotime($b));

        $transactionDateIndex = array_search($transactionDateStr, $cutoffPeriod);
        $passable->periodFrom = Carbon::parse($cutoffPeriod[$transactionDateIndex - 1])->startOfDay();
        $passable->periodTo = Carbon::parse($cutoffPeriod[$transactionDateIndex + 1])->endOfDay();

        return $next($passable);
    }
}
