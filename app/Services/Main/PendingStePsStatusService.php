<?php

declare(strict_types=1);

namespace App\Services\Main;

use App\Constants\DocStatus;
use App\Constants\StockEntryConstants;
use App\Models\PackingSlipItem;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use Exception;
use Illuminate\Support\Facades\DB;

class PendingStePsStatusService
{
    /**
     * Update pending stock entry item status (For Checking -> Issued/Returned).
     *
     * @return string|null Last set item_status or null
     */
    public function updatePendingSteItemStatus(): ?string
    {
        DB::beginTransaction();
        try {
            $forCheckingSte = StockEntry::query()
                ->where('item_status', StockEntryConstants::STATUS_FOR_CHECKING)
                ->where('docstatus', DocStatus::DRAFT)
                ->select('name', 'transfer_as', 'receive_as')
                ->get();

            $itemStatus = null;
            foreach ($forCheckingSte as $ste) {
                $itemsForChecking = StockEntryDetail::query()
                    ->where('parent', $ste->name)
                    ->where('status', StockEntryConstants::STATUS_FOR_CHECKING)
                    ->exists();

                if (! $itemsForChecking) {
                    if ($ste->receive_as === StockEntryConstants::RECEIVE_AS_SALES_RETURN) {
                        StockEntry::query()
                            ->where('name', $ste->name)
                            ->where('docstatus', DocStatus::DRAFT)
                            ->update(['item_status' => StockEntryConstants::STATUS_RETURNED]);
                    } else {
                        $itemStatus = $ste->transfer_as === StockEntryConstants::TRANSFER_AS_FOR_RETURN
                            ? StockEntryConstants::STATUS_RETURNED
                            : StockEntryConstants::STATUS_ISSUED;
                        StockEntry::query()
                            ->where('name', $ste->name)
                            ->where('docstatus', DocStatus::DRAFT)
                            ->update(['item_status' => $itemStatus]);
                    }
                }
            }

            DB::commit();

            return $itemStatus;
        } catch (Exception $e) {
            DB::rollBack();

            return null;
        }
    }

    /**
     * Update pending packing slip item status. If no items left For Checking, submit via ERP.
     * Exceptions from $erpPut are not caught so they propagate (original controller returned 500 on throw).
     *
     * @param string $id Packing slip name
     * @param callable $erpPut (doctype, name, data) => array
     * @return array{success: int, message: string}
     */
    public function updatePendingPsItemStatus(string $id, callable $erpPut): array
    {
        $itemsForChecking = PackingSlipItem::query()
            ->where('parent', $id)
            ->where('status', StockEntryConstants::STATUS_FOR_CHECKING)
            ->exists();

        if (! $itemsForChecking) {
            $erpPut(StockEntryConstants::REFERENCE_PACKING_SLIP, $id, [
                'item_status' => StockEntryConstants::STATUS_ISSUED,
                'docstatus' => DocStatus::SUBMITTED,
            ]);
        }

        return ['success' => 1, 'message' => 'Packing Slips updated!'];
    }
}
