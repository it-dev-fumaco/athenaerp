<?php

declare(strict_types=1);

namespace App\Services\Main;

use App\Constants\DocStatus;
use App\Constants\StockEntryConstants;
use App\Models\DeliveryNote;
use App\Models\PackingSlip;
use App\Models\StockEntry;
use Illuminate\Support\Collection;

class StockEntryCountService
{
    /**
     * Count stock entries for issue by purpose (filtered by allowed warehouses).
     * Uses loose comparison (==) for $purpose to match original controller behavior exactly.
     *
     * @param string|int $purpose Route parameter; passed through without cast to preserve original coercion behavior
     */
    public function countSteForIssue(string|int $purpose, Collection $allowedWarehouses): int
    {
        $allowedWarehouseIds = $allowedWarehouses->all();

        $count = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', DocStatus::DRAFT)
            ->where('purpose', $purpose)
            ->whereNotIn('sted.status', [StockEntryConstants::STATUS_ISSUED, StockEntryConstants::STATUS_RETURNED])
            ->when($purpose == StockEntryConstants::PURPOSE_MATERIAL_ISSUE, function ($query) use ($allowedWarehouseIds) {
                return $query
                    ->whereNotIn('ste.issue_as', ['Customer Replacement', StockEntryConstants::ISSUE_AS_SAMPLE])
                    ->whereIn('sted.s_warehouse', $allowedWarehouseIds);
            })
            ->when($purpose == StockEntryConstants::PURPOSE_MATERIAL_TRANSFER, function ($query) use ($allowedWarehouseIds) {
                return $query
                    ->whereNotIn('ste.transfer_as', [
                        StockEntryConstants::TRANSFER_AS_CONSIGNMENT,
                        StockEntryConstants::TRANSFER_AS_SAMPLE_ITEM,
                        StockEntryConstants::TRANSFER_AS_FOR_RETURN,
                    ])
                    ->whereIn('sted.s_warehouse', $allowedWarehouseIds);
            })
            ->when($purpose == StockEntryConstants::PURPOSE_MATERIAL_TRANSFER_FOR_MANUFACTURE, function ($query) use ($allowedWarehouseIds) {
                return $query->whereIn('sted.s_warehouse', $allowedWarehouseIds);
            })
            ->when($purpose == StockEntryConstants::PURPOSE_MATERIAL_RECEIPT, function ($query) use ($allowedWarehouseIds) {
                return $query
                    ->where('ste.receive_as', StockEntryConstants::RECEIVE_AS_SALES_RETURN)
                    ->whereIn('sted.t_warehouse', $allowedWarehouseIds);
            })
            ->count();

        if ($purpose == StockEntryConstants::PURPOSE_MATERIAL_RECEIPT) {
            $count += DeliveryNote::query()
                ->from('tabDelivery Note as dn')
                ->join('tabDelivery Note Item as dni', 'dn.name', 'dni.parent')
                ->where('dn.is_return', 1)
                ->where('dn.docstatus', DocStatus::DRAFT)
                ->whereIn('dni.warehouse', $allowedWarehouseIds)
                ->count();

            $count += StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', DocStatus::DRAFT)
                ->where('ste.purpose', StockEntryConstants::PURPOSE_MATERIAL_TRANSFER)
                ->where('ste.transfer_as', StockEntryConstants::TRANSFER_AS_FOR_RETURN)
                ->whereIn('sted.t_warehouse', $allowedWarehouseIds)
                ->where('ste.naming_series', StockEntryConstants::NAMING_SERIES_STEC)
                ->count();
        }

        if ($purpose == StockEntryConstants::PURPOSE_MATERIAL_TRANSFER) {
            $count += StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.docstatus', DocStatus::DRAFT)
                ->where('purpose', StockEntryConstants::PURPOSE_MATERIAL_TRANSFER)
                ->whereNotIn('sted.status', [StockEntryConstants::STATUS_ISSUED, StockEntryConstants::STATUS_RETURNED])
                ->whereIn('t_warehouse', $allowedWarehouseIds)
                ->whereIn('transfer_as', [StockEntryConstants::TRANSFER_AS_FOR_RETURN, StockEntryConstants::TRANSFER_AS_INTERNAL_TRANSFER])
                ->count();
        }

        return $count;
    }

    /**
     * Count packing slips for issue (filtered by allowed warehouses).
     */
    public function countPsForIssue(Collection $allowedWarehouses): int
    {
        $allowedWarehouseIds = $allowedWarehouses->all();

        $countPackingSlip = PackingSlip::query()
            ->from('tabPacking Slip as ps')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note Item as dri', 'dri.parent', 'ps.delivery_note')
            ->join('tabDelivery Note as dr', 'dri.parent', 'dr.name')
            ->where('psi.status', StockEntryConstants::STATUS_FOR_CHECKING)
            ->whereRaw('dri.item_code = psi.item_code')
            ->where('ps.docstatus', DocStatus::DRAFT)
            ->where('dri.docstatus', DocStatus::DRAFT)
            ->whereIn('dri.warehouse', $allowedWarehouseIds)
            ->count();

        $countStockEntry = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', DocStatus::DRAFT)
            ->where('purpose', StockEntryConstants::PURPOSE_MATERIAL_TRANSFER)
            ->where('sted.status', StockEntryConstants::STATUS_FOR_CHECKING)
            ->whereIn('s_warehouse', $allowedWarehouseIds)
            ->whereIn('transfer_as', [StockEntryConstants::TRANSFER_AS_CONSIGNMENT, StockEntryConstants::TRANSFER_AS_SAMPLE_ITEM])
            ->count();

        return $countPackingSlip + $countStockEntry;
    }
}
