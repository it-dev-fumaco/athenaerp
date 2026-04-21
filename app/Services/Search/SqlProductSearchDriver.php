<?php

namespace App\Services\Search;

use App\Models\AssignedWarehouses;
use App\Models\Bin;
use App\Models\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SqlProductSearchDriver
{
    /**
     * Build and execute item search query with filters.
     *
     * @param  Request  $request  Search request with searchString, classification, brand, wh, group, check_qty, assigned_to_me, assigned_items
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = $this->baseQuery($request);

        $query->when($request->searchString, fn ($q) => $q->search($request->searchString));

        return $query
            ->select($this->selectColumns($request))
            ->orderBy('tabItem.modified', 'desc')
            ->paginate(20);
    }

    public function suggest(Request $request, int $perPage = 8): LengthAwarePaginator
    {
        $searchString = $request->input('search_string') ?? $request->input('searchString');

        return Item::query()
            ->where('tabItem.disabled', 0)
            ->where('tabItem.has_variants', 0)
            ->when($searchString, fn ($q) => $q->search($searchString))
            ->select('tabItem.name', 'tabItem.description', 'tabItem.item_image_path')
            ->orderBy('tabItem.modified', 'desc')
            ->paginate($perPage);
    }

    /**
     * Hydrate the current page of results preserving Typesense order.
     *
     * @param  array<int, string>  $orderedItemCodes
     */
    public function paginateFromOrderedItemCodes(
        Request $request,
        array $orderedItemCodes,
        int $total,
        int $perPage,
        int $page
    ): LengthAwarePaginator {
        if ($orderedItemCodes === []) {
            return new ConcreteLengthAwarePaginator(
                collect(),
                $total,
                $perPage,
                $page,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                    'query' => $request->query(),
                ]
            );
        }

        $query = $this->baseQuery($request);
        $query->whereIn('tabItem.name', $orderedItemCodes);

        $rows = $query
            ->select($this->selectColumns($request))
            ->get()
            ->keyBy('item_code');

        $sorted = collect($orderedItemCodes)
            ->map(fn (string $code) => $rows->get($code))
            ->filter()
            ->values();

        return new ConcreteLengthAwarePaginator(
            $sorted,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return array<int, string|null>
     */
    public function selectColumns(Request $request): array
    {
        $lifecycleCol = Item::lifecycleStatusColumn();

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
            'tabItem.'.$lifecycleCol.' as lifecycle_status',
            $request->wh ? 'd.warehouse' : null,
        ];

        return array_values(array_filter($selectColumns));
    }

    /**
     * @return Builder<\App\Models\Item>
     */
    public function baseQuery(Request $request): Builder
    {
        $checkQty = 1;
        if ($request->has('check_qty')) {
            $checkQty = $request->check_qty == 'on' ? 1 : 0;
        }

        $consignmentStores = $this->consignmentStoresForUser();

        $itemCodesBasedOnWarehouseAssigned = [];
        if ($request->assigned_to_me) {
            $user = Auth::user()->frappe_userid;
            $assignedConsignmentStores = AssignedWarehouses::where('parent', $user)->pluck('warehouse');
            $itemCodesBasedOnWarehouseAssigned = Bin::whereIn('warehouse', $assignedConsignmentStores)->select('item_code', 'warehouse')->get();
            $itemCodesBasedOnWarehouseAssigned = array_keys(collect($itemCodesBasedOnWarehouseAssigned)->groupBy('item_code')->toArray());
        }

        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;

        return Item::query()->where('tabItem.disabled', 0)
            ->where('tabItem.has_variants', 0)
            ->when($request->assigned_items, function ($query) use ($consignmentStores) {
                return $query->join('tabBin as bin', 'bin.item_code', 'tabItem.name')
                    ->whereIn('bin.warehouse', $consignmentStores);
            })
            ->when($request->classification, fn ($query) => $query->where('tabItem.item_classification', $request->classification))
            ->when($request->brand, fn ($query) => $query->where('tabItem.brand', $request->brand))
            ->when($checkQty && ! $isPromodiser, function ($query) {
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
            });
    }

    /**
     * @return array<int, string>
     */
    public function consignmentStoresForUser(): array
    {
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
        if (! $isPromodiser) {
            return [];
        }

        $consignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse')->toArray();

        return $consignmentStores;
    }

    /**
     * @return array<int, string>
     */
    public function assignedToMeItemCodes(Request $request): array
    {
        if (! $request->assigned_to_me) {
            return [];
        }

        $user = Auth::user()->frappe_userid;
        $assignedConsignmentStores = AssignedWarehouses::where('parent', $user)->pluck('warehouse');
        $rows = Bin::whereIn('warehouse', $assignedConsignmentStores)->select('item_code', 'warehouse')->get();

        return array_keys(collect($rows)->groupBy('item_code')->toArray());
    }
}
