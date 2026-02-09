<?php

namespace App\Services;

use App\Models\AssignedWarehouses;
use App\Models\Bin;
use App\Models\Item;
use App\Models\WarehouseAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemSearchService
{
    /**
     * Build and execute item search query with filters.
     *
     * @param Request $request Search request with searchString, classification, brand, wh, group, check_qty, assigned_to_me, assigned_items
     * @return LengthAwarePaginator
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $selectColumns = [
            'tabItem.name as item_code',
            'tabItem.description',
            'tabItem.item_group',
            'tabItem.stock_uom',
            'tabItem.custom_item_cost',
            'tabItem.item_classification',
            'tabItem.item_group_level_1 as lvl1',
            'tabItem.item_group_level_2 as lvl2',
            'tabItem.item_group_level_3 as lvl3',
            'tabItem.item_group_level_4 as lvl4',
            'tabItem.item_group_level_5 as lvl5',
            'tabItem.weight_per_unit',
            'tabItem.package_length',
            'tabItem.package_width',
            'tabItem.package_height',
            'tabItem.weight_uom',
            'tabItem.package_dimension_uom',
            $request->wh ? 'd.warehouse' : null
        ];
        $selectColumns = array_filter($selectColumns);

        $checkQty = 1;
        if ($request->has('check_qty')) {
            $checkQty = $request->check_qty == 'on' ? 1 : 0;
        }

        $allowedWarehouses = $consignmentStores = [];
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
        if ($isPromodiser) {
            $allowedParentWarehouseForPromodiser = WarehouseAccess::query()->from('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.parent_warehouse')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $allowedWarehouseForPromodiser = WarehouseAccess::query()->from('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.name')
                ->where('wa.parent', Auth::user()->name)->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')->toArray();

            $consignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse')->toArray();

            $allowedWarehouses = array_merge($allowedParentWarehouseForPromodiser, $allowedWarehouseForPromodiser);
            $allowedWarehouses = array_merge($allowedWarehouses, $consignmentStores);
        }

        $itemCodesBasedOnWarehouseAssigned = [];
        if ($request->assigned_to_me) {
            $user = Auth::user()->frappe_userid;
            $assignedConsignmentStores = AssignedWarehouses::where('parent', $user)->pluck('warehouse');
            $itemCodesBasedOnWarehouseAssigned = Bin::whereIn('warehouse', $assignedConsignmentStores)->select('item_code', 'warehouse')->get();
            $itemCodesBasedOnWarehouseAssigned = array_keys(collect($itemCodesBasedOnWarehouseAssigned)->groupBy('item_code')->toArray());
        }

        return Item::query()->where('tabItem.disabled', 0)
            ->where('tabItem.has_variants', 0)
            ->when($request->searchString, fn ($query) => $query->search($request->searchString))
            ->when($request->assigned_items, function ($query) use ($consignmentStores) {
                return $query->join('tabBin as bin', 'bin.item_code', 'tabItem.name')
                    ->whereIn('bin.warehouse', $consignmentStores);
            })
            ->when($request->classification, fn ($query) => $query->where('tabItem.item_classification', $request->classification))
            ->when($request->brand, fn ($query) => $query->where('tabItem.brand', $request->brand))
            ->when($checkQty && !$isPromodiser, function ($query) {
                return $query->where(DB::raw('(SELECT SUM(`tabBin`.actual_qty) FROM `tabBin` JOIN tabWarehouse ON tabWarehouse.name = `tabBin`.warehouse WHERE `tabBin`.item_code = `tabItem`.name and `tabWarehouse`.stock_warehouse = 1)'), '>', 0);
            })
            ->when($request->assigned_to_me, function ($query) use ($itemCodesBasedOnWarehouseAssigned) {
                return $query->whereIn('tabItem.name', $itemCodesBasedOnWarehouseAssigned);
            })
            ->when($request->wh, function ($query) use ($request) {
                return $query->join('tabBin as d', 'd.item_code', 'tabItem.name')
                    ->where('d.warehouse', $request->wh);
            })
            ->when($request->group, function ($query) use ($request) {
                return $query->where(function ($subQuery) use ($request) {
                    return $subQuery->where('tabItem.item_group', $request->group)
                        ->orWhere('tabItem.item_group_level_1', $request->group)
                        ->orWhere('tabItem.item_group_level_2', $request->group)
                        ->orWhere('tabItem.item_group_level_3', $request->group)
                        ->orWhere('tabItem.item_group_level_4', $request->group)
                        ->orWhere('tabItem.item_group_level_5', $request->group);
                });
            })
            ->select($selectColumns)
            ->orderBy('tabItem.modified', 'desc')
            ->paginate(20);
    }
}
