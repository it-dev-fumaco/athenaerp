<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassUpdateItemsRequest;
use App\Http\Requests\PhaseOutReportRequest;
use App\Http\Requests\PhaseOutTaggedItemsRequest;
use App\Models\Item;
use App\Services\PhaseOutReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PhaseOutController extends Controller
{
    public function dashboard()
    {
        return view('phase_out.dashboard');
    }

    public function items()
    {
        return view('phase_out.items');
    }

    public function updateLifecycleStatus()
    {
        return view('phase_out.update_lifecycle_status');
    }

    public function massUpdateItems(MassUpdateItemsRequest $request, PhaseOutReportService $phaseOutReportService): JsonResponse
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 15);
        $page = (int) ($data['page'] ?? 1);

        $filters = [];
        if (! empty($data['brand'])) {
            $filters['brand'] = $data['brand'];
        }
        if (! empty($data['item_classification'])) {
            $filters['item_classification'] = $data['item_classification'];
        }
        if (array_key_exists('last_movement_days_min', $data) && $data['last_movement_days_min'] !== null) {
            $filters['last_movement_days_min'] = (int) $data['last_movement_days_min'];
        }
        if (array_key_exists('last_movement_days_max', $data) && $data['last_movement_days_max'] !== null) {
            $filters['last_movement_days_max'] = (int) $data['last_movement_days_max'];
        }

        $paginator = $phaseOutReportService->paginateMassUpdateItems($perPage, $page, $filters);

        $rows = [];
        foreach ($paginator->items() as $item) {
            /** @var Item $item */
            $rows[] = [
                'item_code' => $item->name,
                'name' => $item->item_name,
                'item_classification' => $item->item_classification ?? null,
                'brand' => $item->brand ?? null,
                'global_stock' => (float) ($item->total_actual_qty ?? 0),
                'last_movement_days' => isset($item->days_since_last_movement)
                    ? (int) $item->days_since_last_movement
                    : null,
                'last_movement_date' => $item->last_stock_ledger_posting ?? null,
                'last_purchase' => null,
            ];
        }

        return response()->json([
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    public function summary(PhaseOutReportService $phaseOutReportService): JsonResponse
    {
        try {
            return response()->json($phaseOutReportService->getPhaseOutSummary());
        } catch (\Throwable $e) {
            Log::warning('phase_out.summary_endpoint_failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'tagged_count' => 0,
                'total_units' => 0.0,
                'total_stock_value' => 0.0,
                'by_brand' => [],
            ]);
        }
    }

    public function taggedItems(PhaseOutTaggedItemsRequest $request, PhaseOutReportService $phaseOutReportService): JsonResponse
    {
        $perPage = (int) ($request->input('per_page') ?? config('phase_out.tagged_per_page'));
        $page = (int) ($request->input('page') ?? 1);

        return response()->json(
            $phaseOutReportService->paginateTaggedEnriched($perPage, $page)
        );
    }

    public function report(PhaseOutReportRequest $request, PhaseOutReportService $phaseOutReportService): JsonResponse
    {
        $taggedPerPage = (int) ($request->input('tagged_per_page') ?? config('phase_out.tagged_per_page'));
        $candidatesPerPage = (int) ($request->input('candidates_per_page') ?? config('phase_out.candidates_per_page'));
        $taggedPage = (int) ($request->input('tagged_page') ?? 1);
        $candidatesPage = (int) ($request->input('candidates_page') ?? 1);
        $months = (int) ($request->input('months') ?? config('phase_out.months_without_activity'));

        $filters = [];
        if ($request->filled('brand')) {
            $filters['brand'] = $request->input('brand');
        }
        if ($request->filled('created_before')) {
            $filters['created_before'] = $request->input('created_before');
        }
        if ($request->filled('no_movement_days')) {
            $filters['no_movement_days'] = (int) $request->input('no_movement_days');
        }
        if ($request->boolean('excess_stock_only')) {
            $filters['excess_stock_only'] = true;
        }

        return response()->json([
            'tagged' => $phaseOutReportService->paginateTagged($taggedPerPage, $taggedPage),
            'candidates' => $phaseOutReportService->paginateCandidates($candidatesPerPage, $candidatesPage, $months, $filters),
            'meta' => [
                'months_without_activity' => $months,
                'candidate_filters' => $filters,
            ],
        ]);
    }
}
