<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BeginningInventory;
use App\Models\ConsignmentInventoryAuditReport;
use App\Models\ConsignmentSalesReportDeadline;
use App\Models\ConsignmentStockEntry;
use App\Models\MaterialRequest;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ConsignmentDashboardService
{
    private const CONSIGNMENT_START_DATE = '2022-06-25';

    public function __construct(
        private readonly CutoffDateService $cutoffDateService
    ) {}

    /**
     * Build view data for the consignment supervisor dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardViewData(): array
    {
        $cutoffDisplayInfo = $this->cutoffDateService->getCutoffDisplayInfo();
        $duration = Carbon::parse($cutoffDisplayInfo['durationFrom'])->format('M d, Y')
            .' - '
            .Carbon::parse($cutoffDisplayInfo['durationTo'])->format('M d, Y');

        $consignmentBranches = $this->getConsignmentBranches();
        $activeConsignmentBranches = collect($consignmentBranches)->where('is_group', 0)->where('disabled', 0);

        $promodisers = User::where('user_group', 'Promodiser')->where('enabled', 1)->count();

        $consignmentBranchesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->whereIn('branch_warehouse', array_column($consignmentBranches, 'name'))
            ->distinct()
            ->pluck('branch_warehouse')
            ->count();

        // Match original: integer 0 when no branches, string from number_format when > 0 (preserves view/JS contract)
        $beginningInvPercentage = count($consignmentBranches) > 0
            ? number_format(($consignmentBranchesWithBeginningInventory / count($consignmentBranches)) * 100, 2)
            : 0;

        $totalStockTransfers = ConsignmentStockEntry::where('purpose', '!=', 'Item Return')
            ->where('status', 'Pending')
            ->count();

        $pendingToReceive = $this->getPendingToReceiveCount();

        $totalConsignmentOrders = MaterialRequest::query()
            ->where('custom_purpose', 'Consignment Order')
            ->where('transfer_as', 'Consignment')
            ->where('consignment_status', 'For Approval')
            ->count();

        $totalPendingInventoryAudit = $this->computePendingInventoryAuditCount($consignmentBranches);

        $cutoffFilters = $this->buildCutoffFilters();
        $salesReportIncludedYears = range(2022, (int) date('Y'));

        return [
            'duration' => $duration,
            'pendingToReceive' => $pendingToReceive,
            'beginningInvPercentage' => $beginningInvPercentage,
            'promodisers' => $promodisers,
            'activeConsignmentBranches' => $activeConsignmentBranches,
            'consignmentBranches' => $consignmentBranches,
            'consignmentBranchesWithBeginningInventory' => $consignmentBranchesWithBeginningInventory,
            'totalStockTransfers' => $totalStockTransfers,
            'totalPendingInventoryAudit' => $totalPendingInventoryAudit,
            'totalConsignmentOrders' => $totalConsignmentOrders,
            'cutoffFilters' => $cutoffFilters,
            'salesReportIncludedYears' => $salesReportIncludedYears,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getConsignmentBranches(): array
    {
        return User::query()
            ->from('tabWarehouse Users as wu')
            ->join('tabAssigned Consignment Warehouse as acw', 'wu.name', 'acw.parent')
            ->join('tabWarehouse as w', 'w.name', 'acw.warehouse')
            ->where('wu.user_group', 'Promodiser')
            ->where('w.disabled', 0)
            ->where('w.is_group', 0)
            ->select('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->groupBy('w.warehouse_name', 'w.name', 'w.is_group', 'w.disabled')
            ->orderBy('w.warehouse_name', 'asc')
            ->get()
            ->toArray();
    }

    private function getPendingToReceiveCount(): int
    {
        return StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->whereDate('ste.delivery_date', '>=', self::CONSIGNMENT_START_DATE)
            ->whereIn('ste.transfer_as', ['Consignment', 'For Return', 'Store Transfer'])
            ->where('ste.purpose', 'Material Transfer')
            ->where('ste.docstatus', 1)
            ->where(function ($query): void {
                $query->whereNull('sted.consignment_status')
                    ->orWhere('sted.consignment_status', '!=', 'Received');
            })
            ->count();
    }

    /**
     * @param array<int, array<string, mixed>> $consignmentBranches
     */
    private function computePendingInventoryAuditCount(array $consignmentBranches): int
    {
        $storesWithBeginningInventory = BeginningInventory::query()
            ->where('status', 'Approved')
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->orderBy('branch_warehouse', 'asc')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $inventoryAuditPerWarehouse = ConsignmentInventoryAuditReport::query()
            ->whereIn('branch_warehouse', array_keys($storesWithBeginningInventory))
            ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
            ->groupBy('branch_warehouse')
            ->pluck('transaction_date', 'branch_warehouse')
            ->toArray();

        $end = now()->endOfDay();
        $cutoffDay = $this->cutoffDateService->getCutoffDay();
        $firstCutoff = Carbon::createFromFormat(
            'm/d/Y',
            $end->format('m') . '/' . $cutoffDay . '/' . $end->format('Y')
        )->endOfDay();

        if ($firstCutoff->gt($end)) {
            $end = $firstCutoff;
        }

        [$periodFrom, $periodTo] = $this->cutoffDateService->getCutoffPeriod(now()->endOfDay());
        $periodFrom = $periodFrom->copy()->addDay();

        $totalPendingInventoryAudit = 0;
        foreach (array_keys($storesWithBeginningInventory) as $store) {
            $beginningInventoryTransactionDate = Arr::get($storesWithBeginningInventory, $store);
            $lastInventoryAuditDate = Arr::get($inventoryAuditPerWarehouse, $store);

            $start = null;
            if ($beginningInventoryTransactionDate) {
                $start = Carbon::parse($beginningInventoryTransactionDate);
            }
            if ($lastInventoryAuditDate) {
                $start = Carbon::parse($lastInventoryAuditDate);
            }

            if ($start === null) {
                continue;
            }

            $lastAuditDate = $start;
            $start = $start->copy()->startOfDay();

            $check = Carbon::parse($start)->between($periodFrom, $periodTo);
            if (Carbon::parse($start)->addDay()->startOfDay()->lt(Carbon::parse($periodTo)->startOfDay())) {
                if ($lastAuditDate->endOfDay()->lt($end) && $beginningInventoryTransactionDate && ! $check) {
                    $totalPendingInventoryAudit++;
                }
            }
        }

        return $totalPendingInventoryAudit;
    }

    /**
     * @return array<int, array{id: string, cutoff_start: string, cutoff_end: string}>
     */
    private function buildCutoffFilters(): array
    {
        $startDate = Carbon::parse(self::CONSIGNMENT_START_DATE)->startOfDay()->format('Y-m-d');
        $endDate = now();
        $period = CarbonPeriod::create($startDate, '28 days', $endDate);

        $salesReportDeadline = ConsignmentSalesReportDeadline::first();
        $cutoffFilters = [];

        if ($salesReportDeadline) {
            $cutoffDay = $salesReportDeadline->{'1st_cutoff_date'};
            $cutoffPeriod = [];

            foreach ($period as $monthIndex => $date) {
                $dateWithCutoff = $date->day($cutoffDay);
                if ($dateWithCutoff >= $startDate && $dateWithCutoff <= $endDate) {
                    $cutoffPeriod[] = $date->format('d-m-Y');
                }
                if ($monthIndex === 0) {
                    $febCutoff = $cutoffDay <= 28 ? $cutoffDay : 28;
                    $cutoffPeriod[] = $febCutoff . '-02-' . now()->format('Y');
                }
            }

            $cutoffPeriod[] = $endDate->format('d-m-Y');
            usort($cutoffPeriod, fn ($time1, $time2) => strtotime($time1) - strtotime($time2));

            foreach ($cutoffPeriod as $index => $cutoffDateItem) {
                if (Arr::exists($cutoffPeriod, $index + 1)) {
                    $cutoffFilters[] = [
                        'id' => $cutoffPeriod[$index] . '/' . $cutoffPeriod[$index + 1],
                        'cutoff_start' => Carbon::parse($cutoffPeriod[$index])->format('M. d, Y'),
                        'cutoff_end' => Carbon::parse($cutoffPeriod[$index + 1])->format('M. d, Y'),
                    ];
                }
            }
        }

        return $cutoffFilters;
    }
}
