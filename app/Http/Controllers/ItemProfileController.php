<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Resources\ItemResource;
use App\Models\AssignedWarehouses;
use App\Models\AthenaTransaction;
use App\Models\Bin;
use App\Models\DepartmentWithPriceAccess;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\ItemPrice;
use App\Models\ItemVariantAttribute;
use App\Models\LandedCostVoucher;
use App\Models\ProductBundle;
use App\Models\PurchaseOrder;
use App\Models\StockEntryDetail;
use App\Models\StockReservation;
use App\Models\UOM;
use App\Models\WarehouseAccess;
use App\Services\ItemProfileService;
use App\Traits\GeneralTrait;
use Buglinjo\LaravelWebp\Facades\Webp;
use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ItemProfileController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected ItemProfileService $itemProfileService
    ) {}

    public function formWarehouseLocation($itemCode)
    {
        try {
            $allowedWarehouses = $this->getAllowedWarehouseIds();

            $warehouses = Bin::query()->whereIn('warehouse', $allowedWarehouses)->where('item_code', $itemCode)->select('warehouse', 'location')->get();

            if (count($warehouses) <= 0) {
                throw new ErrorException('Item <b>'.$itemCode.'</b> is not available on any warehouse.');
            }

            return view('form_warehouse_location', compact('warehouses', 'itemCode'));
        } catch (ErrorException $th) {
            return ApiResponse::failure($th->getMessage(), 400);
        } catch (Exception $th) {
            return ApiResponse::failure('Something went wrong. Please try again.', 400);
        }
    }

    public function editWarehouseLocation(Request $request)
    {
        DB::beginTransaction();
        try {
            $locations = $request->location;

            if ($locations) {
                foreach ($locations as $warehouse => $location) {
                    Bin::query()
                        ->where('warehouse', $warehouse)
                        ->where('item_code', $request->item_code)
                        ->update(['location' => strtoupper($location)]);
                }
            }

            DB::commit();

            return ApiResponse::success('Warehouse location updated.', ['item_code' => $request->item_code]);
        } catch (Exception $e) {
            DB::rollback();

            return ApiResponse::failure('Something went wrong. Please contact your system administrator.');
        }
    }

    public function getItemDetails(Request $request, $itemCode)
    {
        $itemDetails = Item::query()->where('name', $itemCode)->first();

        if (! $itemDetails) {
            abort(404);
        }

        if ($request->json) {
            return ApiResponse::data((new ItemResource($itemDetails))->resolve());
        }

        if ($request->ajax()) {
            $uoms = UOM::query()->pluck('name');

            return view('item_information', compact('itemDetails', 'uoms'));
        }

        $bundled = ProductBundle::query()->where('name', $itemCode)->exists();

        $userDepartment = Auth::user()->department;
        $userGroup = Auth::user()->user_group;
        $allowedDepartment = DepartmentWithPriceAccess::query()->pluck('department')->toArray();

        $priceData = $this->itemProfileService->getItemPrices($itemCode, $itemDetails);
        $itemRate = $priceData['itemRate'];
        $minimumSellingPrice = $priceData['minimumSellingPrice'];
        $defaultPrice = $priceData['defaultPrice'];
        $lastPurchaseRate = $priceData['lastPurchaseRate'];
        $manualRate = $priceData['manualRate'];
        $lastPurchaseDate = $priceData['lastPurchaseDate'];
        $websitePrice = $priceData['websitePrice'];
        $avgPurchaseRate = $priceData['avgPurchaseRate'];
        $isTaxIncludedInRate = $priceData['isTaxIncludedInRate'];
        $minimumPriceComputation = $priceData['minimumPriceComputation'];
        $standardPriceComputation = $priceData['standardPriceComputation'];

        $itemStockLevels = $this->getItemStockLevels($itemCode, $request);

        $consignmentWarehouses = $itemStockLevels['consignment_warehouses'];
        $siteWarehouses = $itemStockLevels['site_warehouses'];

        $itemStockAvailable = collect($consignmentWarehouses)->sum('available_qty');
        if ($itemStockAvailable <= 0) {
            $itemStockAvailable = collect($siteWarehouses)->sum('available_qty');
        }

        $itemImages = ItemImages::query()->where('parent', $itemCode)->orderBy('idx', 'asc')->pluck('image_path');

        $itemImages = collect($itemImages)->map(function ($image) {
            return $this->base64Image("/img/$image");
        });

        $noImg = $this->base64Image('/icon/no_img.png');

        $itemAlternatives = [];
        $productionItemAlternatives = DB::table('tabWork Order Item as p')
            ->join('tabItem as i', 'p.item_alternative_for', 'i.name')
            ->where('p.item_code', $itemDetails->name)
            ->where('p.item_alternative_for', '!=', $itemDetails->name)
            ->where('i.stock_uom', $itemDetails->stock_uom)
            ->select('i.item_code', 'i.description')
            ->orderBy('p.modified', 'desc')
            ->get()
            ->toArray();

        $productionItemAlternativeItemCodes = array_column($productionItemAlternatives, 'item_code');
        $itemAlternativeImages = ItemImages::query()->whereIn('parent', $productionItemAlternativeItemCodes)->orderBy('idx', 'asc')->pluck('image_path', 'parent')->toArray();
        $productionItemAltActualStock = Bin::query()
            ->whereIn('item_code', $productionItemAlternativeItemCodes)
            ->selectRaw('SUM(actual_qty) as actual_qty, item_code')
            ->groupBy('item_code')
            ->pluck('actual_qty', 'item_code')
            ->toArray();
        foreach ($productionItemAlternatives as $a) {
            $a = (object) $a;
            $itemAlternativeImage = Arr::exists($itemAlternativeImages, $a->item_code) ? '/img/'.$itemAlternativeImages[$a->item_code] : '/icon/no_img.png';
            $itemAlternativeImage = $this->base64Image($itemAlternativeImage);

            $actualStocks = Arr::get($productionItemAltActualStock, $a->item_code, 0);

            if (count($itemAlternatives) < 7) {
                $itemAlternatives[] = [
                    'item_code' => $a->item_code,
                    'description' => $a->description,
                    'item_alternative_image' => $itemAlternativeImage,
                    'actual_stocks' => $actualStocks,
                ];
            }
        }

        $variantsPriceArr = $variantsCostArr = $variantsMinPriceArr = $actualVariantStocks = $manualPriceInput = $attributeNames = $attributes = $coVariants = $itemAttributes = [];

        if (! $bundled) {
            $itemAttributes = ItemVariantAttribute::query()->where('parent', $itemCode)->orderBy('idx', 'asc')->pluck('attribute_value', 'attribute')->toArray();
            $q = Item::query()->where('variant_of', $itemDetails->variant_of)->where('name', '!=', $itemDetails->name)->orderBy('modified', 'desc')->get();
            $alternativeItemCodes = collect($q)->pluck('name');

            $actualStocksQuery = Bin::query()->whereIn('item_code', $alternativeItemCodes)->selectRaw('item_code, SUM(actual_qty) as actual_qty')->groupBy('item_code')->get();
            $actualStocks = collect($actualStocksQuery)->groupBy('item_code');

            $stockReservesQuery = StockReservation::whereIn('item_code', $alternativeItemCodes)->whereIn('status', ['Active', 'Partially Issued'])->selectRaw('SUM(reserve_qty) as reserved_qty, SUM(consumed_qty) as consumed_qty, item_code')->groupBy('item_code')->get();
            $alternativeReserves = collect($stockReservesQuery)->groupBy('item_code');

            $steIssuedQuery = StockEntryDetail::query()->where('docstatus', 0)->whereIn('item_code', $alternativeItemCodes)->where('status', 'Issued')->selectRaw('SUM(qty) as qty, item_code')->groupBy('item_code')->get();
            $alternativesIssuedSte = collect($steIssuedQuery)->groupBy('item_code');

            $atIssuedQuery = AthenaTransaction::query()
                ->from('tabAthena Transactions as at')
                ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
                ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
                ->where('dr.docstatus', 0)
                ->where('ps.docstatus', '<', 2)
                ->where('psi.status', 'Issued')
                ->where('at.status', 'Issued')
                ->whereIn('at.item_code', $alternativeItemCodes)
                ->whereRaw('psi.item_code = at.item_code')
                ->selectRaw('SUM(at.issued_qty) as qty, at.item_code')
                ->groupBy('at.item_code')
                ->get();
            $alternativesIssuedAt = collect($atIssuedQuery)->groupBy('item_code');

            $itemAlternativeImages = ItemImages::query()->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent')->toArray();

            foreach ($q as $a) {
                $itemAlternativeImage = Arr::exists($itemAlternativeImages, $a->item_code) ? '/img/'.$itemAlternativeImages[$a->item_code] : '/icon/no_img.png';
                $itemAlternativeImage = $this->base64Image($itemAlternativeImage);

                $totalReserved = $totalConsumed = 0;
                if (Arr::exists($alternativeReserves, $a->item_code)) {
                    $totalReserved = $alternativeReserves[$a->item_code]->sum('reserved_qty');
                    $totalConsumed = $alternativeReserves[$a->item_code]->sum('consumed_qty');
                }

                $totalIssuedSte = Arr::exists($alternativesIssuedSte, $a->item_code) ? $alternativesIssuedSte[$a->item_code]->sum('qty') : 0;
                $totalIssetAt = Arr::exists($alternativesIssuedAt, $a->item_code) ? $alternativesIssuedAt[$a->item_code]->sum('qty') : 0;

                $totalIssued = $totalIssuedSte + $totalIssetAt;
                $remainingReserved = $totalReserved - $totalConsumed;

                $actualStock = $actualStocks[$a->item_code][0] ?? null;
                if (is_array($actualStock)) {
                    $actualStock = (object) $actualStock;
                }
                $actualQty = $actualStock->actual_qty ?? 0;
                $availableQty = $actualQty - ($totalIssued + $remainingReserved);
                $availableQty = $availableQty > 0 ? $availableQty : 0;

                if (count($itemAlternatives) < 7) {
                    $itemAlternatives[] = [
                        'item_code' => $a->item_code,
                        'description' => $a->description,
                        'item_alternative_image' => $itemAlternativeImage,
                        'actual_stocks' => $availableQty,
                    ];
                }
            }

            if (count($itemAlternatives) <= 0) {
                $q = Item::query()
                    ->where('item_classification', $itemDetails->item_classification)
                    ->where('name', '!=', $itemDetails->name)
                    ->limit(100)
                    ->orderBy('modified', 'desc')
                    ->get();
                $itemAlternativeImages = ItemImages::query()->whereIn('parent', collect($q)->pluck('item_code'))->orderBy('idx', 'asc')->pluck('image_path', 'parent')->toArray();

                foreach ($q as $a) {
                    $itemAlternativeImage = Arr::exists($itemAlternativeImages, $a->item_code) ? '/img/'.$itemAlternativeImages[$a->item_code] : '/icon/no_img.png';
                    $itemAlternativeImage = $this->base64Image($itemAlternativeImage);

                    $totalReserved = $totalConsumed = 0;
                    if (Arr::exists($alternativeReserves, $a->item_code)) {
                        $totalReserved = $alternativeReserves[$a->item_code]->sum('reserved_qty');
                        $totalConsumed = $alternativeReserves[$a->item_code]->sum('consumed_qty');
                    }

                    $totalIssuedSte = Arr::exists($alternativesIssuedSte, $a->item_code) ? $alternativesIssuedSte[$a->item_code]->sum('qty') : 0;
                    $totalIssetAt = Arr::exists($alternativesIssuedAt, $a->item_code) ? $alternativesIssuedAt[$a->item_code]->sum('qty') : 0;

                    $totalIssued = $totalIssuedSte + $totalIssetAt;
                    $remainingReserved = $totalReserved - $totalConsumed;

                    $actualStock = $actualStocks[$a->item_code][0] ?? null;
                    if (is_array($actualStock)) {
                        $actualStock = (object) $actualStock;
                    }
                    $actualQty = $actualStock->actual_qty ?? 0;
                    $availableQty = $actualQty - ($totalIssued + $remainingReserved);
                    $availableQty = $availableQty > 0 ? $availableQty : 0;

                    if (count($itemAlternatives) < 7) {
                        $itemAlternatives[] = [
                            'item_code' => $a->item_code,
                            'description' => $a->description,
                            'item_alternative_image' => $itemAlternativeImage,
                            'actual_stocks' => $availableQty,
                        ];
                    }
                }
            }

            $itemAlternatives = collect($itemAlternatives)->sortByDesc('actual_stocks')->toArray();

            $coVariants = Item::query()->variantSiblings($itemDetails->variant_of, $itemDetails->name)->enabled()->select('name', 'item_name', 'custom_item_cost')->paginate(10);
            $variantItemCodes = array_column($coVariants->items(), 'name');

            if (in_array($userDepartment, $allowedDepartment) || in_array(Auth::user()->user_group, ['Manager', 'Director'])) {
                $itemCustomCost = [];
                foreach ($coVariants->items() as $row) {
                    $itemCustomCost[$row->name] = $row->custom_item_cost;
                }

                $variantsLastPurchaseOrder = PurchaseOrder::query()
                    ->from('tabPurchase Order as po')
                    ->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
                    ->where('po.docstatus', 1)
                    ->whereIn('poi.item_code', $variantItemCodes)
                    ->select('poi.base_rate', 'poi.item_code', 'po.supplier_group')
                    ->orderBy('po.creation', 'desc')
                    ->get();

                $variantsLastLandedCostVoucher = LandedCostVoucher::query()
                    ->from('tabLanded Cost Voucher as a')
                    ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                    ->where('a.docstatus', 1)
                    ->whereIn('b.item_code', $variantItemCodes)
                    ->select('a.creation', 'b.item_code', 'b.rate', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')
                    ->orderBy('transaction_date', 'desc')
                    ->get();

                $variantsLastPurchaseOrderRates = collect($variantsLastPurchaseOrder)->groupBy('item_code')->toArray();
                $variantsLastLandedCostVoucherRates = collect($variantsLastLandedCostVoucher)->groupBy('item_code')->toArray();

                $variantsWebsitePrices = ItemPrice::query()
                    ->where('price_list', 'Website Price List')
                    ->where('selling', 1)
                    ->whereIn('item_code', $variantItemCodes)
                    ->orderBy('modified', 'desc')
                    ->pluck('price_list_rate', 'item_code')
                    ->toArray();

                foreach ($variantItemCodes as $variant) {
                    $variantsDefaultPrice = 0;
                    $variantRate = 0;
                    if (Arr::exists($variantsLastPurchaseOrderRates, $variant)) {
                        if ($variantsLastPurchaseOrderRates[$variant][0]->supplier_group == 'Imported') {
                            $variantRate = data_get($variantsLastLandedCostVoucherRates, "{$variant}.0.valuation_rate", 0);
                        } else {
                            $variantRate = $variantsLastPurchaseOrderRates[$variant][0]->base_rate;
                        }
                    }

                    $isManualRate = 0;
                    if ($variantRate <= 0) {
                        if (Arr::exists($itemCustomCost, $variant)) {
                            $variantRate = $itemCustomCost[$variant];
                            $isManualRate = 1;
                        } else {
                            $variantRate = 0;
                        }
                    }

                    if ($isTaxIncludedInRate) {
                        $variantsDefaultPrice = ($variantRate * $standardPriceComputation) * 1.12;
                    }

                    $variantsDefaultPrice = Arr::get($variantsWebsitePrices, $variant, $variantsDefaultPrice);
                    $variantsPriceArr[$variant] = $variantsDefaultPrice;
                    $variantsCostArr[$variant] = $variantRate;
                    $variantsMinPriceArr[$variant] = $variantRate * $minimumPriceComputation;
                    $manualPriceInput[$variant] = $isManualRate;
                }
            }

            $actualVariantStocks = Bin::query()->whereIn('item_code', $variantItemCodes)->selectRaw('SUM(actual_qty) as actual_qty, item_code')->groupBy('item_code')->pluck('actual_qty', 'item_code')->toArray();

            array_push($variantItemCodes, $itemDetails->name);

            $attributesQuery = ItemVariantAttribute::query()->whereIn('parent', $variantItemCodes)->select('parent', 'attribute', 'attribute_value')->orderBy('idx', 'asc')->get();

            $attributeNames = collect($attributesQuery)->pluck('attribute')->unique();

            $attributes = [];
            foreach ($attributesQuery as $row) {
                $attributes[$row->parent][$row->attribute] = $row->attribute_value;
            }
        }

        $consignmentBranches = [];
        if (Auth::user()->user_group == 'Promodiser') {
            $consignmentBranches = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        }

        return view('item_profile', compact('isTaxIncludedInRate', 'itemDetails', 'itemAttributes', 'siteWarehouses', 'itemImages', 'itemAlternatives', 'consignmentWarehouses', 'userGroup', 'minimumSellingPrice', 'defaultPrice', 'attributeNames', 'coVariants', 'attributes', 'variantsPriceArr', 'itemRate', 'lastPurchaseDate', 'allowedDepartment', 'userDepartment', 'avgPurchaseRate', 'lastPurchaseRate', 'variantsCostArr', 'variantsMinPriceArr', 'actualVariantStocks', 'itemStockAvailable', 'manualRate', 'manualPriceInput', 'consignmentBranches', 'bundled', 'noImg'));
    }

    public function saveItemInformation(Request $request, $itemCode)
    {
        DB::beginTransaction();
        try {
            foreach ($request->except('_token') as $dimension => $value) {
                if (! in_array($dimension, ['package_dimension_uom'])) {
                    if (! is_numeric($value)) {
                        return ApiResponse::failureLegacy(str_replace('_', ' ', $dimension).' must be a number.');
                    }

                    if ((float) $value <= 0) {
                        return ApiResponse::failureLegacy(str_replace('_', ' ', $dimension).' cannot be less than 0.');
                    }
                }
            }

            $updateArr = $request->except('_token');
            $updateArr['modified'] = now()->toDateTimeString();
            $updateArr['modified_by'] = Auth::user()->wh_user;

            Item::query()->where('name', $itemCode)->update($updateArr);

            DB::commit();

            return ApiResponse::successLegacy('Package dimension saved.');
        } catch (\Throwable $th) {
            DB::rollback();

            return ApiResponse::failureLegacy('An error occured. Please try again later.');
        }
    }

    public function printBarcode($itemCode)
    {
        $itemDetails = Item::query()->where('name', $itemCode)->first();

        return view('print_barcode', compact('itemDetails'));
    }

    public function uploadItemImage(Request $request)
    {
        $existingImages = $request->existing_images ? $request->existing_images : [];
        $removedImages = ItemImages::query()
            ->where('parent', $request->item_code)
            ->when($existingImages, function ($query) use ($existingImages) {
                $query->whereNotIn('name', $existingImages);
            })
            ->pluck('image_path');

        foreach ($removedImages as $img) {
            Storage::disk('upcloud')->delete(Storage::disk('upcloud')->path($img));
        }

        ItemImages::query()
            ->where('parent', $request->item_code)
            ->when($existingImages, function ($query) use ($existingImages) {
                $query->whereNotIn('name', $existingImages);
            })
            ->delete();

        $now = now();
        if ($request->hasFile('item_image')) {
            $files = $request->file('item_image');

            $itemImagesArr = [];
            foreach ($files as $i => $file) {
                $microTime = round(microtime(true));
                $fileIndex = 0;
                $filename = "{$microTime}{$fileIndex}-{$request->item_code}";
                $originalExtension = $file->getClientOriginalExtension();
                $storagePath = Storage::disk('upcloud')->path($img);
                $jpegFilename = Storage::disk('upcloud')->path("$filename.$originalExtension");
                $webpFilename = Storage::disk('upcloud')->path("$filename.webp");

                $webp = Webp::make($file);

                if (! File::exists(public_path('temp'))) {
                    File::makeDirectory(public_path('temp'), 0755, true);
                }

                $webpPath = public_path("temp/$webpFilename");
                $webp->save($webpPath);

                $webContents = file_get_contents($webpPath);
                Storage::disk('upcloud')->put(Storage::disk('upcloud')->path($webpFilename), $webContents);

                unlink($webpPath);

                $itemImagesArr[] = [
                    'name' => uniqid(),
                    'creation' => $now->toDateTimeString(),
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'idx' => $i + 1,
                    'parent' => $request->item_code,
                    'parentfield' => 'item_images',
                    'parenttype' => 'Item',
                    'image_path' => Storage::disk('upcloud')->path($webpFilename),
                ];
            }

            ItemImages::query()->insert($itemImagesArr);

            return response()->json(['message' => 'Item image for '.$request->item_code.' has been uploaded.']);
        } else {
            return response()->json(['message' => 'Item image for '.$request->item_code.' has been updated.']);
        }
    }

    public function loadItemImages($itemCode, Request $request)
    {
        $images = ItemImages::where('parent', $itemCode)->select('image_path', 'owner', 'modified_by', 'creation', 'modified')->orderBy('idx', 'asc')->get();

        if (count($images) <= 0) {
            return response()->json([], 404);
        }

        $selected = $request->idx ? $request->idx : 0;

        $images = collect($images)->map(function ($image) {
            $image->image = $image->image_path;
            $image->image_path = $image->image_path ? Storage::disk('upcloud')->path($image->image_path) : Storage::disk('upcloud')->path('icon/no_img.png');

            $image->original = 1;
            if (Storage::disk('upcloud')->exists(Storage::disk('upcloud')->path(explode('.', $image->image_path)[0]).'.webp')) {
                $image->original = 0;
                $image->image = Storage::disk('upcloud')->path(explode('.', $image->image_path)[0]).'.webp';
            }

            return $image;
        });

        return view('images_container', compact('images', 'selected'));
    }

    public function getItemImages($itemCode)
    {
        $images = ItemImages::query()->where('parent', $itemCode)->orderBy('idx', 'asc')->pluck('image_path', 'name');

        return collect($images)->map(function ($image) {
            return Storage::disk('upcloud')->path("img/$image");
        });
    }

    public function getItemStockLevels($itemCode, Request $request)
    {
        $itemDetails = Item::query()->where('name', $itemCode)->first();
        $allowWarehouse = [];
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

            $allowWarehouse = array_merge($allowedParentWarehouseForPromodiser, $allowedWarehouseForPromodiser);
            $allowWarehouse = array_merge($allowWarehouse, $consignmentStores);
        }

        $itemInventory = Bin::query()
            ->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')
            ->where('item_code', $itemCode)
            ->when($isPromodiser, function ($query) use ($allowWarehouse) {
                return $query->whereIn('warehouse', $allowWarehouse);
            })
            ->where('stock_warehouse', 1)
            ->where('tabWarehouse.disabled', 0)
            ->select('item_code', 'warehouse', 'location', 'actual_qty', 'consigned_qty', 'tabBin.stock_uom', 'parent_warehouse')
            ->get();

        $stockWarehouses = array_column($itemInventory->toArray(), 'warehouse');

        $stockReserves = [];
        $steIssued = [];
        $atIssued = [];
        if (count($stockWarehouses) > 0) {
            $stockReserves = StockReservation::where('item_code', $itemCode)
                ->whereIn('warehouse', $stockWarehouses)
                ->whereIn('status', ['Active', 'Partially Issued'])
                ->selectRaw('SUM(reserve_qty) as reserved_qty, SUM(consumed_qty) as consumed_qty, warehouse')
                ->groupBy('warehouse')
                ->get();

            $stockReserves = collect($stockReserves)->groupBy('warehouse')->toArray();

            $steIssued = StockEntryDetail::query()
                ->where('docstatus', 0)
                ->where('status', 'Issued')
                ->where('item_code', $itemCode)
                ->whereIn('s_warehouse', $stockWarehouses)
                ->selectRaw('SUM(qty) as qty, s_warehouse')
                ->groupBy('s_warehouse')
                ->pluck('qty', 's_warehouse')
                ->toArray();

            $atIssued = AthenaTransaction::query()
                ->from('tabAthena Transactions as at')
                ->join('tabPacking Slip as ps', 'ps.name', 'at.reference_parent')
                ->join('tabPacking Slip Item as psi', 'ps.name', 'psi.parent')
                ->join('tabDelivery Note as dr', 'ps.delivery_note', 'dr.name')
                ->whereIn('at.reference_type', ['Packing Slip', 'Picking Slip'])
                ->where('dr.docstatus', 0)
                ->where('ps.docstatus', '<', 2)
                ->where('at.status', 'Issued')
                ->where('psi.status', 'Issued')
                ->where('at.item_code', $itemCode)
                ->where('psi.item_code', $itemCode)
                ->whereIn('at.source_warehouse', $stockWarehouses)
                ->selectRaw('SUM(at.issued_qty) as qty, at.source_warehouse')
                ->groupBy('at.source_warehouse')
                ->pluck('qty', 'source_warehouse')
                ->toArray();
        }

        $siteWarehouses = [];
        $consignmentWarehouses = [];
        foreach ($itemInventory as $value) {
            $reservedQty = Arr::get($stockReserves, "{$value->warehouse}.0.reserved_qty", 0);
            $consumedQty = Arr::get($stockReserves, "{$value->warehouse}.0.consumed_qty", 0);
            $steIssuedQty = Arr::get($steIssued, $value->warehouse, 0);
            $atIssuedQty = Arr::get($atIssued, $value->warehouse, 0);

            $issuedQty = $atIssuedQty + $steIssuedQty;
            $reservedQty = $reservedQty - $consumedQty;
            $reservedQty = $reservedQty > 0 ? $reservedQty : 0;

            $issuedReservedQty = ($reservedQty + $issuedQty) - $consumedQty;

            $actualQty = $value->actual_qty;
            $availableQty = ($actualQty > $issuedReservedQty) ? $actualQty - $issuedReservedQty : 0;
            if ($value->parent_warehouse == 'P2 Consignment Warehouse - FI' && ! $isPromodiser) {
                $consignmentWarehouses[] = [
                    'warehouse' => $value->warehouse,
                    'location' => $value->location,
                    'reserved_qty' => $reservedQty,
                    'actual_qty' => $value->actual_qty,
                    'issued_qty' => $issuedQty,
                    'available_qty' => $availableQty,
                    'stock_uom' => $value->stock_uom,
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
                    'issued_qty' => $issuedQty,
                    'available_qty' => $availableQty,
                    'stock_uom' => $value->stock_uom,
                ];
            }
        }

        if ($request->ajax()) {
            return view('item_stock_level', compact('consignmentWarehouses', 'siteWarehouses', 'itemDetails'));
        }

        return [
            'consignment_warehouses' => $consignmentWarehouses,
            'site_warehouses' => $siteWarehouses,
        ];
    }

    public function getBundledItemStockLevels(Request $request, $itemCode)
    {
        $productBundle = DB::table('tabProduct Bundle as p')
            ->join('tabProduct Bundle Item as c', 'c.parent', 'p.name')
            ->where('p.name', $itemCode)
            ->select('p.name as bundle_id', 'p.*', 'c.*')
            ->get();

        $items = collect($productBundle)->pluck('item_code');
        $grouped = collect($productBundle)->groupBy('item_code');

        $stocks = [];
        foreach ($items as $item) {
            $bundleItem = $grouped[$item][0] ?? null;
            if (is_array($bundleItem)) {
                $bundleItem = (object) $bundleItem;
            }
            $description = $bundleItem->description ?? null;
            $details = $this->getItemStockLevels($item, $request);

            unset($details['consignment_warehouses']);

            $details['site_warehouses'] = collect($details['site_warehouses'])->where('actual_qty', '>', 0);
            $details['description'] = $description;

            $stocks[$item] = $details;
        }

        return view('item_stock_level_bundled', compact('stocks'));
    }
}
