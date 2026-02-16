<?php

namespace App\Pipelines\Pipes;

use App\Contracts\Pipeline\Pipe;
use App\Models\AssignedWarehouses;
use App\Models\BeginningInventory;
use App\Models\Bin;
use App\Models\ConsignmentInventoryAuditReport;
use App\Models\ConsignmentStockEntry;
use App\Models\MaterialRequest;
use App\Services\CutoffDateService;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResolveIndexResponsePipe implements Pipe
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        $user = Auth::user()->frappe_userid;
        $userGroup = Auth::user()->user_group;

        if ($userGroup == 'User') {
            $passable->response = redirect('/search_results');

            return $next($passable);
        }

        if ($userGroup == 'Promodiser') {
            $assignedConsignmentStore = AssignedWarehouses::where('parent', $user)->orderBy('warehouse', 'asc')->pluck('warehouse');

            if (count($assignedConsignmentStore) > 0) {
                $cutoffDisplayInfo = app(CutoffDateService::class)->getCutoffDisplayInfo();
                $due = $cutoffDisplayInfo['due'];

                $invSummary = Bin::query()
                    ->from('tabBin as b')
                    ->join('tabItem as i', 'i.name', 'b.item_code')
                    ->where('i.disabled', 0)
                    ->where('i.is_stock_item', 1)
                    ->whereIn('b.warehouse', $assignedConsignmentStore)
                    ->where('b.consigned_qty', '>', 0)
                    ->select('b.warehouse', 'b.consigned_qty')
                    ->get()
                    ->toArray();

                $invSummary = collect($invSummary)->groupBy('warehouse');

                $inventorySummary = [];
                foreach ($invSummary as $warehouse => $row) {
                    $inventorySummary[$warehouse] = [
                        'items_on_hand' => collect($row)->count(),
                        'total_qty' => collect($row)->sum('consigned_qty'),
                    ];
                }

                $storesWithBeginningInventory = BeginningInventory::query()
                    ->where('status', 'Approved')
                    ->whereIn('branch_warehouse', $assignedConsignmentStore)
                    ->orderBy('branch_warehouse', 'asc')
                    ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                    ->groupBy('branch_warehouse')
                    ->pluck('transaction_date', 'branch_warehouse')
                    ->toArray();

                ConsignmentInventoryAuditReport::query()
                    ->whereIn('branch_warehouse', array_keys($storesWithBeginningInventory))
                    ->select(DB::raw('MAX(transaction_date) as transaction_date'), 'branch_warehouse')
                    ->groupBy('branch_warehouse')
                    ->pluck('transaction_date', 'branch_warehouse')
                    ->toArray();

                $totalStockTransfer = ConsignmentStockEntry::query()
                    ->whereIn('source_warehouse', $assignedConsignmentStore)
                    ->where('status', 'Pending')
                    ->count();

                $totalConsignmentOrders = MaterialRequest::where('custom_purpose', 'Consignment Order')->where('transfer_as', 'Consignment')->whereIn('branch_warehouse', $assignedConsignmentStore)->where('consignment_status', 'For Approval')->count();

                $branchesWithBeginningInventory = BeginningInventory::query()
                    ->whereIn('branch_warehouse', $assignedConsignmentStore)
                    ->where('status', '!=', 'Cancelled')
                    ->distinct()
                    ->pluck('branch_warehouse')
                    ->toArray();

                $branchesWithPendingBeginningInventory = [];
                foreach ($assignedConsignmentStore as $store) {
                    if (! in_array($store, $branchesWithBeginningInventory)) {
                        $branchesWithPendingBeginningInventory[] = $store;
                    }
                }

                $passable->response = view('consignment.index_promodiser', compact('assignedConsignmentStore', 'inventorySummary', 'totalStockTransfer', 'totalConsignmentOrders', 'branchesWithPendingBeginningInventory', 'due'));

                return $next($passable);
            }

            $passable->response = redirect('/search_results');

            return $next($passable);
        }

        if ($userGroup == 'Consignment Supervisor') {
            $passable->response = isset($passable->getConsignmentDashboardView) && is_callable($passable->getConsignmentDashboardView)
                ? ($passable->getConsignmentDashboardView)()
                : redirect('/');

            return $next($passable);
        }

        $passable->response = view('index');

        return $next($passable);
    }
}
