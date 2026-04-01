<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkTagItemsRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;

class BulkTagItemsController extends Controller
{
    public function __invoke(BulkTagItemsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tag = $data['tag'];
        $itemIds = $data['itemIds'];

        Item::whereIn('name', $itemIds)->update([
            Item::lifecycleStatusColumn() => $tag,
        ]);

        return response()->json([
            'message' => 'Items tagged successfully.',
            'taggedItems' => $itemIds,
            'tag' => $tag,
        ]);
    }
}
