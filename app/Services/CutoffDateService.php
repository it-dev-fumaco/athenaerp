<?php

namespace App\Services;

use App\Models\ConsignmentSalesReportDeadline;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CutoffDateService
{
    /**
     * Get cutoff period [from, to] for a given transaction date.
     *
     * @param Carbon|string $transactionDate
     * @return array{Carbon, Carbon} [periodFrom, periodTo]
     */
    public function getCutoffPeriod($transactionDate): array
    {
        $transactionDate = Carbon::parse($transactionDate);
        $startDate = Carbon::parse($transactionDate)->subMonth();
        $endDate = Carbon::parse($transactionDate)->addMonths(2);

        $period = CarbonPeriod::create($startDate, '28 days', $endDate);

        $cutoffDay = $this->getCutoffDay();

        $transactionDateStr = $transactionDate->format('Y-m-d');
        $cutoffPeriod = $this->buildCutoffPeriod($period, $startDate, $endDate, $cutoffDay);
        $cutoffPeriod[] = $transactionDateStr;

        usort($cutoffPeriod, fn ($a, $b) => strtotime($a) - strtotime($b));

        $transactionDateIndex = array_search($transactionDateStr, $cutoffPeriod);
        $periodFrom = Carbon::parse($cutoffPeriod[$transactionDateIndex - 1])->startOfDay();
        $periodTo = Carbon::parse($cutoffPeriod[$transactionDateIndex + 1])->endOfDay();

        return [$periodFrom, $periodTo];
    }

    public function getCutoffDay(): int
    {
        $salesReportDeadline = ConsignmentSalesReportDeadline::first();

        return $salesReportDeadline ? (int) $salesReportDeadline->{'1st_cutoff_date'} : 0;
    }

    /**
     * Get cutoff display info for dashboard (durationFrom, durationTo, due).
     *
     * @param Carbon|string|null $transactionDate Defaults to now()
     * @return array{durationFrom: string, durationTo: string, due: string, periodFrom: Carbon, periodTo: Carbon}
     */
    public function getCutoffDisplayInfo($transactionDate = null): array
    {
        $transactionDate = $transactionDate ? Carbon::parse($transactionDate) : now();
        [$periodFrom, $periodTo] = $this->getCutoffPeriod($transactionDate);

        return [
            'durationFrom' => $periodFrom->copy()->addDay()->format('d-m-Y'),
            'durationTo' => $periodTo->format('d-m-Y'),
            'due' => 'Due: ' . $periodTo->format('M d, Y'),
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
        ];
    }

    /**
     * @return array<string>
     */
    private function buildCutoffPeriod(CarbonPeriod $period, Carbon $startDate, Carbon $endDate, int $cutoffDay): array
    {
        $cutoffPeriod = [];

        foreach ($period as $monthIndex => $date) {
            $dateWithCutoff = $date->day($cutoffDay);
            if ($dateWithCutoff >= $startDate && $dateWithCutoff <= $endDate) {
                $cutoffPeriod[] = $date->format('Y-m-d');
            }
            if ($monthIndex == 0) {
                $febCutoff = $cutoffDay <= 28 ? $cutoffDay : 28;
                $cutoffPeriod[] = $febCutoff . '-02-' . now()->format('Y');
            }
        }

        return $cutoffPeriod;
    }
}
