<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterItemsRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;

class RetrieveItemsByLifecycleStatusController extends Controller
{
    public function __invoke(FilterItemsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $statuses = $data['status'];
        $perPage = $data['per_page'] ?? 15;

        $col = Item::lifecycleStatusColumn();

        $items = Item::whereIn($col, $statuses)
            ->orderBy('creation', 'desc')
            ->paginate($perPage);

        return response()->json($items);
    }
}
