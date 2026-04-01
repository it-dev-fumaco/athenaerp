<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhaseOutReportRequest;
use App\Http\Requests\PhaseOutTaggedItemsRequest;
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
