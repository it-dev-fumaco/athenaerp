<?php

namespace App\Http\Controllers;

use App\Models\AssignedWarehouses;
use App\Models\AthenaInventorySearchHistory;
use App\Models\AthenaTransaction;
use App\Models\Bin;
use App\Models\DepartmentWithPriceAccess;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\ItemImages;
use App\Models\ItemPrice;
use App\Models\ItemReorder;
use App\Models\ItemSupplier;
use App\Models\LandedCostVoucher;
use App\Models\ProductBundle;
use App\Models\PurchaseOrder;
use App\Models\Singles;
use App\Models\StockEntryDetail;
use App\Models\StockReservation;
use App\Models\WarehouseAccess;
use App\Services\ItemSearchService;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected ItemSearchService $itemSearchService
    ) {}

    public function searchResults(Request $request): \Illuminate\View\View|string
    {
        $items = $this->itemSearchService->search($request);

        $allowedWarehouses = [];
        $isPromodiser = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
        if ($isPromodiser) {
            $allowedParentWarehouseForPromodiser = WarehouseAccess::query()
                ->from('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.parent_warehouse')
                ->where('wa.parent', Auth::user()->name)
                ->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')
                ->toArray();

            $allowedWarehouseForPromodiser = WarehouseAccess::query()
                ->from('tabWarehouse Access as wa')
                ->join('tabWarehouse as w', 'wa.warehouse', 'w.name')
                ->where('wa.parent', Auth::user()->name)
                ->where('w.is_group', 0)
                ->where('w.stock_warehouse', 1)
                ->pluck('w.name')
                ->toArray();

            $consignmentStores = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse')->toArray();

            $allowedWarehouses = array_merge($allowedParentWarehouseForPromodiser, $allowedWarehouseForPromodiser);
            $allowedWarehouses = array_merge($allowedWarehouses, $consignmentStores);
        }

        $itemGroups = collect($items->items())->map(function ($item) {
            return [
                'item_group' => $item->item_group,
                'item_group_level_1' => $item->lvl1,
                'item_group_level_2' => $item->lvl2,
                'item_group_level_3' => $item->lvl3,
                'item_group_level_4' => $item->lvl4,
                'item_group_level_5' => $item->lvl5,
            ];
        })->unique()->values()->all();

        $a = array_column($itemGroups, 'item_group');
        $a1 = array_column($itemGroups, 'item_group_level_1');
        $a2 = array_column($itemGroups, 'item_group_level_2');
        $a3 = array_column($itemGroups, 'item_group_level_3');
        $a4 = array_column($itemGroups, 'item_group_level_4');
        $a5 = array_column($itemGroups, 'item_group_level_5');

        $igs = array_unique(array_merge($a, $a1, $a2, $a3, $a4, $a5));

        $breadcrumbs = [];
        if ($request->group) {
            $selectedGroup = ItemGroup::where('item_group_name', $request->group)->first();
            if ($selectedGroup) {
                session()->forget('breadcrumbs');
                if (! session()->has('breadcrumbs')) {
                    session()->put('breadcrumbs', []);
                }

                session()->push('breadcrumbs', $request->group);
                $this->breadcrumbs($selectedGroup->parent_item_group);

                $breadcrumbs = array_reverse(session()->get('breadcrumbs'));
            }
        }

        $totalItems = $items->total();

        if ($request->searchString) {
            AthenaInventorySearchHistory::create([
                'name' => uniqid(),
                'creation' => now(),
                'modified' => now(),
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'search_string' => $request->searchString,
                'total_result' => $totalItems,
            ]);
        }

        if ($request->get_total) {
            return number_format($totalItems);
        }

        $itemCodes = array_column($items->items(), 'item_code');

        $bundledItems = ProductBundle::whereIn('name', $itemCodes)->pluck('name')->toArray();

        $itemInventory = Bin::query()
            ->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')
            ->whereIn('tabBin.item_code', $itemCodes)
            ->when($isPromodiser, function ($query) use ($allowedWarehouses) {
                return $query->whereIn('warehouse', $allowedWarehouses);
            })
            ->where('stock_warehouse', 1)
            ->where('tabWarehouse.disabled', 0)
            ->select('item_code', 'warehouse', 'location', 'actual_qty', 'tabBin.consigned_qty', 'stock_uom', 'parent_warehouse')
            ->get();

        $itemWarehouses = array_column($itemInventory->toArray(), 'warehouse');

        $itemInventory = collect($itemInventory)->groupBy('item_code')->toArray();

        $stockReservation = StockReservation::whereIn('item_code', $itemCodes)
            ->whereIn('warehouse', $itemWarehouses)
            ->whereIn('status', ['Active', 'Partially Issued'])
            ->selectRaw('SUM(reserve_qty) as total_reserved_qty, SUM(consumed_qty) as total_consumed_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')
            ->get();
        $stockReservation = collect($stockReservation)->groupBy('item')->toArray();

        $steTotalIssued = StockEntryDetail::where('docstatus', 0)
            ->where('status', 'Issued')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('s_warehouse', $itemWarehouses)
            ->selectRaw('SUM(qty) as total_issued, CONCAT(item_code, "-", s_warehouse) as item')
            ->groupBy('item_code', 's_warehouse')
            ->get();
        $steTotalIssued = collect($steTotalIssued)->groupBy('item')->toArray();

        $atTotalIssued = AthenaTransaction::query()
            ->from('tabAthena Transactions as at')
            ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
            ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
            ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
            ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
            ->where('dr.docstatus', 0)
            ->where('ps.docstatus', '<', 2)
            ->where('at.status', 'Issued')
            ->where('psi.status', 'Issued')
            ->whereIn('at.item_code', $itemCodes)
            ->whereIn('psi.item_code', $itemCodes)
            ->whereIn('at.source_warehouse', $itemWarehouses)
            ->selectRaw('SUM(at.issued_qty) as total_issued, CONCAT(at.item_code, "-", at.source_warehouse) as item')
            ->groupBy('at.item_code', 'at.source_warehouse')
            ->get();

        $atTotalIssued = collect($atTotalIssued)->groupBy('item')->toArray();

        $lowLevelStock = ItemReorder::query()
            ->whereIn('parent', $itemCodes)
            ->whereIn('warehouse', $itemWarehouses)
            ->selectRaw('SUM(warehouse_reorder_level) as total_warehouse_reorder_level, CONCAT(parent, "-", warehouse) as item')
            ->groupBy('parent', 'warehouse')
            ->get();
        $lowLevelStock = collect($lowLevelStock)->groupBy('item')->toArray();

        $itemImages = ItemImages::whereIn('parent', $itemCodes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $itemImages = collect($itemImages)->map(function ($image) {
            return $this->base64Image("/img/$image");
        });
        $noImgPlaceholder = $this->base64Image('/icon/no_img.png');

        $partNosQuery = ItemSupplier::whereIn('parent', $itemCodes)
            ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))
            ->groupBy('parent')
            ->pluck('supplier_part_nos', 'parent');

        $userDepartment = Auth::user()->department;
        $allowedDepartment = DepartmentWithPriceAccess::pluck('department')->toArray();

        $lastPurchaseOrder = [];
        $lastLandedCostVoucher = [];
        $priceSettings = [];
        $websitePrices = [];
        if (in_array($userDepartment, $allowedDepartment) || in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
            $lastPurchaseOrder = PurchaseOrder::query()
                ->from('tabPurchase Order as po')
                ->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
                ->where('po.docstatus', 1)
                ->whereIn('poi.item_code', $itemCodes)
                ->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')
                ->orderBy('po.creation', 'desc')
                ->get();

            $lastLandedCostVoucher = LandedCostVoucher::query()
                ->from('tabLanded Cost Voucher as a')
                ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                ->where('a.docstatus', 1)
                ->whereIn('b.item_code', $itemCodes)
                ->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')
                ->orderBy('transaction_date', 'desc')
                ->get();

            $lastPurchaseOrderRates = collect($lastPurchaseOrder)->groupBy('item_code')->toArray();
            $lastLandedCostVoucherRates = collect($lastLandedCostVoucher)->groupBy('item_code')->toArray();

            $websitePrices = ItemPrice::where('price_list', 'Website Price List')
                ->where('selling', 1)
                ->whereIn('item_code', $itemCodes)
                ->orderBy('modified', 'desc')
                ->pluck('price_list_rate', 'item_code')
                ->toArray();

            $priceSettings = Singles::where('doctype', 'Price Settings')
                ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])
                ->pluck('value', 'field')
                ->toArray();
        }

        $minimumPriceComputation = Arr::get($priceSettings, 'minimum_price_computation', 0);
        $standardPriceComputation = Arr::get($priceSettings, 'standard_price_computation', 0);
        $isTaxIncludedInRate = Arr::get($priceSettings, 'is_tax_included_in_rate', 0);

        $itemList = [];
        foreach ($items as $row) {
            $image = Arr::get($itemImages, $row->item_code, $noImgPlaceholder);

            $partNos = Arr::get($partNosQuery, $row->item_code);

            $siteWarehouses = [];
            $consignmentWarehouses = [];
            $itemInventoryArr = Arr::get($itemInventory, $row->item_code, []);
            foreach ($itemInventoryArr as $value) {
                $binKey = $value->item_code.'-'.$value->warehouse;
                $reservedQty = Arr::get($stockReservation, "{$binKey}.0.total_reserved_qty", 0);

                $consumedQty = Arr::get($stockReservation, "{$binKey}.0.total_consumed_qty", 0);

                $issuedQty = data_get($steTotalIssued, "{$binKey}.0.total_issued", 0);
                $issuedQty += data_get($atTotalIssued, "{$binKey}.0.total_issued", 0);

                $reservedQty = $reservedQty - $consumedQty;
                $reservedQty = $reservedQty > 0 ? $reservedQty : 0;

                $actualQty = $value->actual_qty;

                $warehouseReorderLevel = data_get($lowLevelStock, "{$binKey}.0.total_warehouse_reorder_level", 0);

                $issuedReservedQty = ($reservedQty + $issuedQty) - $consumedQty;

                $availableQty = ($actualQty > $issuedReservedQty) ? $actualQty - $issuedReservedQty : 0;
                if ($value->parent_warehouse == 'P2 Consignment Warehouse - FI' && ! $isPromodiser) {
                    $consignmentWarehouses[] = [
                        'warehouse' => $value->warehouse,
                        'location' => $value->location,
                        'reserved_qty' => $reservedQty,
                        'actual_qty' => $value->actual_qty,
                        'available_qty' => $availableQty,
                        'consigned_qty' => $value->consigned_qty > 0 ? $value->consigned_qty : 0,
                        'stock_uom' => $value->stock_uom ? $value->stock_uom : $row->stock_uom,
                        'warehouse_reorder_level' => $warehouseReorderLevel,
                        'parent_warehouse' => $value->parent_warehouse,
                    ];
                } else {
                    if (Auth::user()->user_group == 'Promodiser' && $value->parent_warehouse == 'P2 Consignment Warehouse - FI') {
                        $availableQty = $value->consigned_qty > 0 ? $value->consigned_qty : 0;
                    }

                    $siteWarehouses[] = [
                        'warehouse' => $value->warehouse,
                        'location' => $value->location,
                        'reserved_qty' => $reservedQty,
                        'actual_qty' => $value->actual_qty,
                        'available_qty' => $availableQty,
                        'consigned_qty' => $value->consigned_qty > 0 ? $value->consigned_qty : 0,
                        'stock_uom' => $value->stock_uom ? $value->stock_uom : $row->stock_uom,
                        'warehouse_reorder_level' => $warehouseReorderLevel,
                        'parent_warehouse' => $value->parent_warehouse,
                    ];
                }
            }

            $lastPurchaseOrderRates = collect($lastPurchaseOrder)->groupBy('item_code')->toArray();
            $lastLandedCostVoucherRates = collect($lastLandedCostVoucher)->groupBy('item_code')->toArray();

            $itemRate = 0;
            $lastPurchaseOrderArr = data_get($lastPurchaseOrderRates, "{$row->item_code}.0", []);
            if ($lastPurchaseOrderArr) {
                if (data_get($lastPurchaseOrderArr, 'supplier_group') == 'Imported') {
                    $lastLandedCostVoucherItem = data_get($lastLandedCostVoucherRates, "{$row->item_code}.0", []);

                    if ($lastLandedCostVoucherItem) {
                        $itemRate = data_get($lastLandedCostVoucherItem, 'valuation_rate', 0);
                    }
                } else {
                    $itemRate = data_get($lastPurchaseOrderArr, 'base_rate', 0);
                }
            }

            if ($itemRate <= 0) {
                $itemRate = $row->custom_item_cost;
            }

            $minimumSellingPrice = $itemRate * $minimumPriceComputation;
            $defaultPrice = $itemRate * $standardPriceComputation;
            if ($isTaxIncludedInRate) {
                $defaultPrice = ($itemRate * $standardPriceComputation) * 1.12;
            }

            $websitePrice = Arr::get($websitePrices, $row->item_code, 0);

            $defaultPrice = ($websitePrice > 0) ? $websitePrice : $defaultPrice;

            $packageDimension = null;
            if (
                $row->weight_per_unit > 0 ||
                $row->package_length > 0 ||
                $row->package_width > 0 ||
                $row->package_height > 0
            ) {
                $packageDimension = '<span class="text-muted">Net Weight:</span> <b>'.($row->weight_per_unit > 0 ? (float) $row->weight_per_unit.' '.$row->weight_uom : '-').'</b>, ';
                $packageDimension .= '<span class="text-muted">Length:</span> <b>'.($row->package_length > 0 ? (float) $row->package_length.' '.$row->package_dimension_uom : '-').'</b>, ';
                $packageDimension .= '<span class="text-muted">Width:</span>  <b>'.($row->package_width > 0 ? (float) $row->package_width.' '.$row->package_dimension_uom : '-').'</b>, ';
                $packageDimension .= '<span class="text-muted">Height:</span> <b>'.($row->package_height > 0 ? (float) $row->package_height.' '.$row->package_dimension_uom : '-').'</b>';
            }

            $itemList[] = [
                'name' => $row->item_code,
                'description' => $row->description,
                'image' => $image,
                'part_nos' => $partNos,
                'item_group' => $row->item_group,
                'stock_uom' => $row->stock_uom,
                'item_classification' => $row->item_classification,
                'item_inventory' => $siteWarehouses,
                'consignment_warehouses' => $consignmentWarehouses,
                'default_price' => $defaultPrice,
                'package_dimension' => $packageDimension,
            ];
        }

        $root = ItemGroup::query()->where('parent_item_group', '')->pluck('name')->first();

        $itemGroup = ItemGroup::query()
            ->when(array_filter($request->all()), function ($query) use ($igs, $request) {
                $query
                    ->whereIn('item_group_name', $igs)
                    ->orWhere('item_group_name', 'LIKE', '%'.$request->searchString.'%');
            })
            ->select('name', 'item_group_name', 'parent_item_group', 'is_group', 'old_parent', 'order_no')
            ->orderByRaw('LENGTH(order_no) ASC')
            ->orderBy('order_no', 'ASC')
            ->get();

        $all = collect($itemGroup)->groupBy('parent_item_group');

        $itemGroups = collect($itemGroup)->where('parent_item_group', $root)->where('is_group', 1)->groupBy('name')->toArray();
        $subItems = array_filter($request->all()) ? collect($itemGroup)->where('parent_item_group', '!=', $root) : [];

        $arr = [];
        if ($subItems) {
            $igsCollection = collect($itemGroup)->groupBy('item_group_name');
            session()->forget('igs_array');
            if (! session()->has('igs_array')) {
                session()->put('igs_array', []);
            }

            foreach ($subItems as $a) {
                if (! in_array($a->item_group_name, session()->get('igs_array'))) {
                    session()->push('igs_array', $a->item_group_name);
                }

                $this->checkItemGroupTree($a->parent_item_group, $igsCollection);
            }

            $igsArray = session()->get('igs_array');

            $arr = array_filter($igsArray);
        }

        $itemGroupArray = $this->itemGroupTree(1, $itemGroups, $all, $arr);

        if ($request->expectsJson()) {
            $showPrice = in_array($userDepartment, $allowedDepartment) || in_array(Auth::user()->user_group, ['Manager', 'Director']);

            return response()->json([
                'data' => $itemList,
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'total' => $items->total(),
                    'path' => $items->path(),
                ],
                'bundled_items' => $bundledItems,
                'show_price' => $showPrice,
            ]);
        }

        return view('search_results', compact('itemList', 'items', 'all', 'itemGroups', 'itemGroupArray', 'breadcrumbs', 'totalItems', 'root', 'allowedDepartment', 'userDepartment', 'bundledItems', 'noImgPlaceholder'));
    }

    private function breadcrumbs($parent)
    {
        session()->push('breadcrumbs', $parent);

        $rootParent = ItemGroup::query()->where('item_group_name', $parent)->pluck('parent_item_group')->first();
        if ($rootParent) {
            $this->breadcrumbs($rootParent);
        }

        return 1;
    }

    private function checkItemGroupTree($parent, $igsCollection)
    {
        $itemGroup = Arr::get($igsCollection, "{$parent}.0", []);
        $itemGroups = session()->get('igs_array');
        if ($itemGroup) {
            if (! in_array($itemGroup->item_group_name, $itemGroups)) {
                session()->push('igs_array', $itemGroup->item_group_name);
            }

            $this->checkItemGroupTree($itemGroup->parent_item_group, $igsCollection);

            return 1;
        }
    }

    private function itemGroupTree($currentLvl, $group, $all, $igsArray = [])
    {
        $currentLvl = $currentLvl + 1;

        $lvlArr = [];
        if ($igsArray) {
            foreach ($group as $lvl) {
                $nextLevel = Arr::exists($all, $lvl[0]->name) ? collect($all[$lvl[0]->name])->groupBy('name') : [];
                if (in_array($lvl[0]->name, $igsArray)) {
                    if ($nextLevel) {
                        $nxt = $this->itemGroupTree($currentLvl, $nextLevel, $all, $igsArray);
                        $lvlArr[$lvl[0]->name] = [
                            'lvl'.$currentLvl => $nxt,
                            'is_group' => $lvl[0]->is_group,
                        ];
                    } else {
                        $lvlArr[$lvl[0]->name] = [
                            'lvl'.$currentLvl => $nextLevel,
                            'is_group' => $lvl[0]->is_group,
                        ];
                    }
                }
            }
        } else {
            foreach ($group as $lvl) {
                $nextLevel = Arr::exists($all, $lvl[0]->name) ? collect($all[$lvl[0]->name])->groupBy('name') : [];
                if ($nextLevel) {
                    $nxt = $this->itemGroupTree($currentLvl, $nextLevel, $all);
                    $lvlArr[$lvl[0]->name] = [
                        'lvl'.$currentLvl => $nxt,
                        'is_group' => $lvl[0]->is_group,
                    ];
                } else {
                    $lvlArr[$lvl[0]->name] = [
                        'lvl'.$currentLvl => $nextLevel,
                        'is_group' => $lvl[0]->is_group,
                    ];
                }
            }
        }

        return $lvlArr;
    }

    public function searchResultsImages(Request $request)
    {
        if ($request->ajax()) {
            $itemImages = ItemImages::where('parent', $request->item_code)->orderBy('idx', 'asc')->get();

            $dir = $request->dir == 'next' ? 0 : count($itemImages) - 1;

            $img = Arr::exists($itemImages, $request->img_key) ? $itemImages[$request->img_key]->image_path : $itemImages[$dir]->image_path;
            $currentKey = Arr::exists($itemImages, $request->img_key) ? $request->img_key : $dir;

            $imgArr = [
                'item_code' => $request->item_code,
                'alt' => Str::slug(explode('.', $itemImages[$currentKey]->image_path)[0]),
                'orig_image_path' => asset('storage/').'/img/'.$img,
                'orig_path' => Storage::disk('public')->exists('/img/'.$img) ? 1 : 0,
                'webp_image_path' => asset('storage/').'/img/'.explode('.', $img)[0].'.webp',
                'webp_path' => Storage::disk('public')->exists('/img/'.explode('.', $img)[0]) ? 1 : 0,
                'current_img_key' => $currentKey,
            ];

            return $imgArr;
        }
    }

    public function loadSuggestionBox(Request $request)
    {
        $q = Item::query()
            ->where('disabled', 0)
            ->where('has_variants', 0)
            ->search($request->search_string)
            ->select('tabItem.name', 'description', 'item_image_path')
            ->orderBy('tabItem.modified', 'desc')
            ->paginate(8);

        $itemCodes = collect($q->items())->pluck('name');

        $bundledItems = ProductBundle::query()->whereIn('name', $itemCodes)->pluck('name')->toArray();

        $imageCollection = ItemImages::whereIn('parent', $itemCodes)->orderBy('idx', 'asc')->pluck('image_path', 'parent');
        $imageCollection = collect($imageCollection)->map(function ($image) {
            return $this->base64Image("/img/$image");
        });

        $noImg = $this->base64Image('/icon/no_img.png');

        return view('suggestion_box', compact('q', 'imageCollection', 'bundledItems', 'noImg'));
    }
}
