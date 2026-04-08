<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemLifecycleStatusChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemLifecycleStatusController extends Controller
{
    public function __invoke(Request $request, string $item_code): JsonResponse
    {
        $validated = $request->validate([
            'newStatus' => ['required', 'string', 'in:'.implode(',', Item::LIFECYCLE_STATUSES)],
            'reason' => ['required', 'string', 'min:1'],
        ]);

        $newStatus = trim((string) $validated['newStatus']);
        $reason = trim((string) $validated['reason']);

        $col = Item::lifecycleStatusColumn();

        /** @var Item|null $item */
        $item = Item::query()->where('name', $item_code)->first();
        if (! $item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        $oldStatus = (string) ($item->getAttribute($col) ?? '');
        $oldStatus = $oldStatus !== '' ? $oldStatus : null;

        Item::query()
            ->where('name', $item_code)
            ->update([$col => $newStatus]);

        $user = Auth::user();
        ItemLifecycleStatusChange::create([
            'item_code' => $item_code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $user?->wh_user ?? $user?->name,
            'changed_by_name' => $user?->full_name ?? null,
        ]);

        return response()->json([
            'message' => 'Lifecycle status updated.',
            'item_code' => $item_code,
            'status' => $newStatus,
        ]);
    }
}

