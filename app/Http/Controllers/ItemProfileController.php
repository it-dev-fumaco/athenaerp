<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Helpers\SafePath;
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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
                Bin::updateLocationsForItem($request->item_code, $locations);
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
        $debugQueries = $request->boolean('debug_queries', false);
        if ($debugQueries) {
            DB::enableQueryLog();
        }

        $cacheTtl = (int) config('item_profile.cache_ttl', 300);

        // Fetch only fields needed by this controller + `resources/views/item_profile.blade.php`.
        // Also eager-load images to avoid an extra `ItemImages` query.
        $itemDetails = Item::query()
            ->where('name', $itemCode)
            ->select([
                'name',
                'brand',
                'description',
                'item_name',
                'item_brochure_name',
                'item_brochure_description',
                'stock_uom',
                'custom_item_cost',
                'item_classification',
                'variant_of',
                'has_variants',
                'disabled',
            ])
            ->with([
                'images' => fn ($q) => $q
                    ->select('parent', 'image_path', 'idx')
                    ->orderBy('idx', 'asc'),
            ])
            ->first();

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
        $allowedDepartment = Cache::remember('item_profile.allowed_departments', $cacheTtl, function () {
            return DepartmentWithPriceAccess::query()->pluck('department')->toArray();
        });

        // Price queries can be expensive (purchase orders, landed costs, website prices).
        // Cache per item + permission context so unauthorized users still get zeros.
        $priceCacheKey = sprintf('item_profile.item_prices:%s:%s:%s', $itemCode, $userDepartment ?? 'null', $userGroup ?? 'null');
        $priceData = Cache::remember($priceCacheKey, $cacheTtl, function () use ($itemCode, $itemDetails, $allowedDepartment) {
            return $this->itemProfileService->getItemPrices($itemCode, $itemDetails, $allowedDepartment);
        });
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

        $itemStockLevels = $this->getItemStockLevels($itemCode, $request, $itemDetails);

        $consignmentWarehouses = $itemStockLevels['consignment_warehouses'];
        $siteWarehouses = $itemStockLevels['site_warehouses'];

        $itemStockAvailable = collect($consignmentWarehouses)->sum('available_qty');
        if ($itemStockAvailable <= 0) {
            $itemStockAvailable = collect($siteWarehouses)->sum('available_qty');
        }

        // images are eager-loaded on the main item query above.
        $itemImagesRaw = $itemDetails->images?->pluck('image_path') ?? collect();

        $disk = Storage::disk('upcloud');
        $itemImages = collect($itemImagesRaw)->map(function ($imagePath) use ($disk) {
            $path = $imagePath ? trim((string) $imagePath) : null;
            $url = $this->itemImageUrlFast($path);

            $thumbUrl = $url;
            if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
                $baseName = pathinfo($path, PATHINFO_FILENAME);
                $thumbKey = 'items/thumbs/'.$baseName.'.webp';
                if ($disk->exists($thumbKey)) {
                    $thumbUrl = $disk->url($thumbKey);
                }
            }

            return [
                'full' => $url,
                'thumb' => $thumbUrl,
            ];
        });

        $noImg = $this->itemImageUrlWebpOrNoImg(null);

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
        foreach ($productionItemAlternatives as $productionAltRow) {
            $productionAltRow = (object) $productionAltRow;
            $path = Arr::exists($itemAlternativeImages, $productionAltRow->item_code) ? $itemAlternativeImages[$productionAltRow->item_code] : null;
            $itemAlternativeImage = $this->itemImageUrlFast($path);

            $actualStocks = Arr::get($productionItemAltActualStock, $productionAltRow->item_code, 0);

            if (count($itemAlternatives) < 7) {
                $itemAlternatives[] = [
                    'item_code' => $productionAltRow->item_code,
                    'description' => $productionAltRow->description,
                    'item_alternative_image' => $itemAlternativeImage,
                    'actual_stocks' => $actualStocks,
                ];
            }
        }

        $variantsPriceArr = $variantsCostArr = $variantsMinPriceArr = $actualVariantStocks = $manualPriceInput = $attributeNames = $attributes = $coVariants = $itemAttributes = [];

        if (! $bundled) {
            $itemAttributes = ItemVariantAttribute::query()->where('parent', $itemCode)->orderBy('idx', 'asc')->pluck('attribute_value', 'attribute')->toArray();
            $variantItems = Item::query()
                ->variantSiblings($itemDetails->variant_of, $itemDetails->name)
                ->orderBy('modified', 'desc')
                ->select(['name', 'description', 'disabled', 'custom_item_cost', 'variant_of'])
                ->get();
            $alternativeItemCodes = $variantItems->pluck('name');

            $actualStocksQuery = Bin::query()->whereIn('item_code', $alternativeItemCodes)->selectRaw('item_code, SUM(actual_qty) as actual_qty')->groupBy('item_code')->get();
            $actualStocks = collect($actualStocksQuery)->groupBy('item_code');

            $stockReservesQuery = StockReservation::whereIn('item_code', $alternativeItemCodes)->whereIn('status', ['Active', 'Partially Issued'])->selectRaw('SUM(reserve_qty) as reserved_qty, SUM(consumed_qty) as consumed_qty, item_code')->groupBy('item_code')->get();
            $alternativeReserves = collect($stockReservesQuery)->groupBy('item_code');

            $steIssuedQuery = StockEntryDetail::query()->where('docstatus', 0)->whereIn('item_code', $alternativeItemCodes)->where('status', 'Issued')->selectRaw('SUM(qty) as qty, item_code')->groupBy('item_code')->get();
            $alternativesIssuedSte = collect($steIssuedQuery)->groupBy('item_code');

            $atIssuedQuery = AthenaTransaction::query()
                ->joinPackingSlipDeliveryNote()
                ->whereIn('at.item_code', $alternativeItemCodes)
                ->selectRaw('SUM(at.issued_qty) as qty, at.item_code')
                ->groupBy('at.item_code')
                ->get();
            $alternativesIssuedAt = collect($atIssuedQuery)->groupBy('item_code');

            $itemAlternativeImages = ItemImages::query()->whereIn('parent', $variantItems->pluck('name'))->orderBy('idx', 'asc')->pluck('image_path', 'parent')->toArray();

            foreach ($variantItems as $variantItem) {
                $path = Arr::exists($itemAlternativeImages, $variantItem->name) ? $itemAlternativeImages[$variantItem->name] : null;
                $itemAlternativeImage = $this->itemImageUrlFast($path);

                $totalReserved = $totalConsumed = 0;
                if (Arr::exists($alternativeReserves, $variantItem->name)) {
                    $totalReserved = $alternativeReserves[$variantItem->name]->sum('reserved_qty');
                    $totalConsumed = $alternativeReserves[$variantItem->name]->sum('consumed_qty');
                }

                $totalIssuedSte = Arr::exists($alternativesIssuedSte, $variantItem->name) ? $alternativesIssuedSte[$variantItem->name]->sum('qty') : 0;
                $totalIssetAt = Arr::exists($alternativesIssuedAt, $variantItem->name) ? $alternativesIssuedAt[$variantItem->name]->sum('qty') : 0;

                $totalIssued = $totalIssuedSte + $totalIssetAt;
                $remainingReserved = $totalReserved - $totalConsumed;

                $actualStock = $actualStocks[$variantItem->name][0] ?? null;
                if (is_array($actualStock)) {
                    $actualStock = (object) $actualStock;
                }
                $actualQty = $actualStock->actual_qty ?? 0;
                $availableQty = $actualQty - ($totalIssued + $remainingReserved);
                $availableQty = $availableQty > 0 ? $availableQty : 0;

                if (count($itemAlternatives) < 7) {
                    $itemAlternatives[] = [
                        'item_code' => $variantItem->name,
                        'description' => $variantItem->description,
                        'item_alternative_image' => $itemAlternativeImage,
                        'actual_stocks' => $availableQty,
                    ];
                }
            }

            if (count($itemAlternatives) <= 0) {
                $classificationItems = Item::query()
                    ->where('item_classification', $itemDetails->item_classification)
                    ->where('name', '!=', $itemDetails->name)
                    ->limit(15)
                    ->orderBy('modified', 'desc')
                    ->select(['name', 'description'])
                    ->get();
                $itemAlternativeImages = ItemImages::query()->whereIn('parent', $classificationItems->pluck('name'))->orderBy('idx', 'asc')->pluck('image_path', 'parent')->toArray();

                foreach ($classificationItems as $altItem) {
                    $path = Arr::exists($itemAlternativeImages, $altItem->name) ? $itemAlternativeImages[$altItem->name] : null;
                    $itemAlternativeImage = $this->itemImageUrlFast($path);

                    $totalReserved = $totalConsumed = 0;
                    if (Arr::exists($alternativeReserves, $altItem->name)) {
                        $totalReserved = $alternativeReserves[$altItem->name]->sum('reserved_qty');
                        $totalConsumed = $alternativeReserves[$altItem->name]->sum('consumed_qty');
                    }

                    $totalIssuedSte = Arr::exists($alternativesIssuedSte, $altItem->name) ? $alternativesIssuedSte[$altItem->name]->sum('qty') : 0;
                    $totalIssetAt = Arr::exists($alternativesIssuedAt, $altItem->name) ? $alternativesIssuedAt[$altItem->name]->sum('qty') : 0;

                    $totalIssued = $totalIssuedSte + $totalIssetAt;
                    $remainingReserved = $totalReserved - $totalConsumed;

                    $actualStock = $actualStocks[$altItem->name][0] ?? null;
                    if (is_array($actualStock)) {
                        $actualStock = (object) $actualStock;
                    }
                    $actualQty = $actualStock->actual_qty ?? 0;
                    $availableQty = $actualQty - ($totalIssued + $remainingReserved);
                    $availableQty = $availableQty > 0 ? $availableQty : 0;

                    if (count($itemAlternatives) < 7) {
                        $itemAlternatives[] = [
                            'item_code' => $altItem->name,
                            'description' => $altItem->description,
                            'item_alternative_image' => $itemAlternativeImage,
                            'actual_stocks' => $availableQty,
                        ];
                    }
                }
            }

            $itemAlternatives = collect($itemAlternatives)->sortByDesc('actual_stocks')->toArray();

            $enabledVariantItems = $variantItems->where('disabled', 0)->values();
            $coVariantsPage = LengthAwarePaginator::resolveCurrentPage();
            $coVariantsPerPage = 10;
            $coVariantsTotal = $enabledVariantItems->count();
            $coVariantsSlice = $enabledVariantItems->slice(($coVariantsPage - 1) * $coVariantsPerPage, $coVariantsPerPage)->values();
            $coVariants = new LengthAwarePaginator($coVariantsSlice, $coVariantsTotal, $coVariantsPerPage, $coVariantsPage, ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]);
            $variantItemCodes = $coVariantsSlice->pluck('name')->toArray();

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
                        if (data_get($variantsLastPurchaseOrderRates[$variant][0], 'supplier_group') == 'Imported') {
                            $variantRate = data_get($variantsLastLandedCostVoucherRates, "{$variant}.0.valuation_rate", 0);
                        } else {
                            $variantRate = data_get($variantsLastPurchaseOrderRates[$variant][0], 'base_rate', 0);
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
            // Eager-load all variant attributes for the current page slice (plus the main item)
            // so these attributes are fetched in a single query (no relation N+1 risk).
            $itemsForAttributes = $coVariantsSlice->concat(collect([$itemDetails]));
            $itemsForAttributes->load([
                'variantAttributes' => fn ($q) => $q
                    ->select('parent', 'attribute', 'attribute_value', 'idx')
                    ->orderBy('idx', 'asc'),
            ]);

            $allAttrRows = $itemsForAttributes
                ->flatMap(fn ($item) => $item->variantAttributes)
                ->sortBy('idx')
                ->values();

            $attributeNames = $allAttrRows->pluck('attribute')->unique()->values();

            $attributes = [];
            foreach ($allAttrRows as $row) {
                $attributes[$row->parent][$row->attribute] = $row->attribute_value;
            }
        }

        $consignmentBranches = [];
        if (Auth::user()->user_group == 'Promodiser') {
            $consignmentBranches = AssignedWarehouses::where('parent', Auth::user()->frappe_userid)->pluck('warehouse');
        }

        if ($debugQueries) {
            $queries = DB::getQueryLog();
            Log::info('item_profile.getItemDetails query count', [
                'item_code' => $itemCode,
                'count' => is_array($queries) ? count($queries) : 0,
            ]);
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
        $existingImages = $request->existing_images ?? [];

        $query = ItemImages::query()
            ->where('parent', $request->item_code);

        if (! empty($existingImages)) {
            $query->whereNotIn('name', $existingImages);
        }

        $removedImages = $query->pluck('image_path')->toArray();

        // Delete from cloud
        if (! empty($removedImages)) {
            Storage::disk('upcloud')->delete($removedImages);
        }

        // Delete DB records
        $query->delete();

        $now = now();

        if ($request->hasFile('item_image')) {

            $files = $request->file('item_image');
            $itemImagesArr = [];

            foreach ($files as $i => $file) {

                // Unique filename (unpredictable, no path in item_code)
                $safeItemCode = SafePath::sanitizeSegment($request->item_code);
                $randomBase = (string) Str::random(24).'-'.($safeItemCode ?: 'item');

                $storageKey = null;
                $tempPath = null;
                $thumbTempPath = null;

                // Prefer WebP when supported; otherwise fall back to original file bytes.
                try {
                    if (function_exists('imagewebp')) {
                        $filename = $randomBase.'.webp';
                        $storageKey = "img/{$filename}";

                        $webp = Webp::make($file);
                        $tempPath = storage_path("app/temp/{$filename}");
                        if (! File::exists(dirname($tempPath))) {
                            File::makeDirectory(dirname($tempPath), 0755, true);
                        }
                        $webp->save($tempPath);
                        Storage::disk('upcloud')->put($storageKey, file_get_contents($tempPath));

                        // Generate a smaller thumbnail (~600px wide) under img/thumbs/ for LCP.
                        $thumbFilename = $filename;
                        $thumbKey = "img/thumbs/{$thumbFilename}";
                        $thumbTempPath = storage_path("app/temp/thumb-{$filename}");
                        $thumbImage = Webp::make($file);
                        if (method_exists($thumbImage, 'resize')) {
                            $thumbImage->resize(600, null, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            });
                        }
                        if (! File::exists(dirname($thumbTempPath))) {
                            File::makeDirectory(dirname($thumbTempPath), 0755, true);
                        }
                        $thumbImage->save($thumbTempPath);
                        Storage::disk('upcloud')->put($thumbKey, file_get_contents($thumbTempPath));
                    } else {
                        $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
                        $filename = $randomBase.'.'.$ext;
                        $storageKey = "img/{$filename}";
                        Storage::disk('upcloud')->put($storageKey, file_get_contents($file->getRealPath()));
                    }
                } catch (\Throwable $e) {
                    // Last-resort fallback: store original bytes so upload never 500s.
                    $ext = strtolower((string) $file->getClientOriginalExtension()) ?: 'jpg';
                    $filename = $randomBase.'.'.$ext;
                    $storageKey = "img/{$filename}";
                    Storage::disk('upcloud')->put($storageKey, file_get_contents($file->getRealPath()));
                } finally {
                    if ($tempPath && is_file($tempPath)) {
                        @unlink($tempPath);
                    }
                    if ($thumbTempPath && is_file($thumbTempPath)) {
                        @unlink($thumbTempPath);
                    }
                }

                $itemImagesArr[] = [
                    'name' => uniqid('', true),
                    'creation' => $now,
                    'modified' => $now,
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'idx' => $i + 1,
                    'parent' => $request->item_code,
                    'parentfield' => 'item_images',
                    'parenttype' => 'Item',
                    'image_path' => $storageKey, // store only object key
                ];
            }

            ItemImages::insert($itemImagesArr);

            return response()->json([
                'message' => 'Item image for '.$request->item_code.' has been uploaded.',
            ]);
        }

        return response()->json([
            'message' => 'Item image for '.$request->item_code.' has been updated.',
        ]);
    }

    /**
     * Item profile image rule: show .webp if it exists, otherwise no-img.png.
     * Used for main item images and item alternatives. Does not fall back to jpeg/png.
     */
    private function itemImageUrlWebpOrNoImg(?string $imagePath): string
    {
        $noImgUrl = Storage::disk('upcloud')->url('icon/no-img.png');
        if (! $imagePath || ! trim((string) $imagePath)) {
            return $noImgUrl;
        }
        $imagePath = trim((string) $imagePath);
        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }
        $disk = Storage::disk('upcloud');
        $baseName = pathinfo($imagePath, PATHINFO_FILENAME);

        $webpCandidates = [
            "items/{$baseName}.webp",
            "item-images/{$baseName}.webp",
            "img/{$baseName}.webp",
        ];
        if (str_contains($imagePath, '/')) {
            $key = ltrim($imagePath, '/');
            $dir = dirname($key);
            $webpCandidates[] = $dir.'/'.$baseName.'.webp';
        }

        foreach ($webpCandidates as $key) {
            if ($disk->exists($key)) {
                return $disk->url($key);
            }
        }

        return $noImgUrl;
    }

    /**
     * Return image URL for item profile. Minimizes exists() calls: path with "/" is used as storage key (no exists).
     * Filename-only: try img/ first, then legacy items/ and item-images/ so existing paths still work.
     */
    private function itemImageUrlFast(?string $imagePath): string
    {
        if (! $imagePath || ! trim((string) $imagePath)) {
            return Storage::disk('upcloud')->url('icon/no-img.png');
        }
        $imagePath = trim((string) $imagePath);
        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }
        $disk = Storage::disk('upcloud');
        if (str_contains($imagePath, '/')) {
            $key = ltrim($imagePath, '/');
            return $disk->url($key);
        }
        $candidates = [
            "img/{$imagePath}",
            "items/{$imagePath}",
            "item-images/{$imagePath}",
        ];
        foreach ($candidates as $key) {
            if ($disk->exists($key)) {
                return $disk->url($key);
            }
        }
        return $disk->url('icon/no-img.png');
    }

    /**
     * Build public URL for an item image. If path is already a full URL (or contains one, e.g. malformed "img/https://..."), return that URL.
     * Otherwise build URL using the upcloud disk. Prefers webp when it exists, otherwise returns original (jpg, png, etc.).
     */
    private function buildItemImageUrl(?string $path): string
    {
        if (! $path) {
            return Storage::disk('upcloud')->url('icon/no-img.png');
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        // Malformed path can be stored with URL — use the URL part for display/download.
        if (Str::contains($path, '://')) {
            $scheme = Str::contains($path, 'https://') ? 'https://' : 'http://';
            $after = Str::after($path, $scheme);

            return $scheme.$after;
        }
        $disk = Storage::disk('upcloud');
        $storageKey = str_contains($path, '/') ? ltrim($path, '/') : 'img/'.$path;

        // Prefer webp when it exists, otherwise use original (jpg, png, etc.)
        if (str_starts_with($storageKey, 'img/')) {
            $baseName = pathinfo($storageKey, PATHINFO_FILENAME);
            $dir = dirname($storageKey);
            $webpKey = ($dir === '.' || $dir === 'img') ? 'img/'.$baseName.'.webp' : $dir.'/'.$baseName.'.webp';
            if ($disk->exists($webpKey)) {
                return $disk->url($webpKey);
            }
        }

        return $disk->url($storageKey);
    }

    /**
     * Resolve storage key for the webp version of an image (same directory as original).
     * Paths that contain a URL are not valid storage keys; use filename-only key under img/.
     */
    private function itemImageStorageKey(string $originalPath, string $webpFilename): string
    {
        if (Str::contains($originalPath, '://')) {
            return 'img/'.basename($webpFilename);
        }
        if (str_contains($originalPath, '/')) {
            return dirname($originalPath).'/'.basename($webpFilename);
        }

        return 'img/'.$webpFilename;
    }

    public function loadItemImages($itemCode, Request $request)
    {
        $images = ItemImages::where('parent', $itemCode)->select('image_path', 'owner', 'modified_by', 'creation', 'modified')->orderBy('idx', 'asc')->get();

        if (count($images) <= 0) {
            return response()->json([], 404);
        }

        $selected = $request->idx ? $request->idx : 0;

        $images = collect($images)->map(function ($image) {
            $originalPath = $image->image_path;
            $image->image = $originalPath;

            $image->image_url = $this->buildItemImageUrl($originalPath);

            $image->original = 1;
            $isFullUrl = $originalPath && (Str::startsWith($originalPath, ['http://', 'https://']) || Str::contains($originalPath, '://'));
            $webpKey = null;
            $webpStorageKey = null;
            if (! $isFullUrl && $originalPath) {
                $webpKey = explode('.', basename($originalPath))[0].'.webp';
                $webpStorageKey = $this->itemImageStorageKey($originalPath, $webpKey);
            }
            if ($webpStorageKey && Storage::disk('upcloud')->exists($webpStorageKey)) {
                $image->original = 0;
                $image->image = $webpStorageKey;
                $image->image_url = Storage::disk('upcloud')->url($webpStorageKey);
            }

            return $image;
        });

        return view('images_container', compact('images', 'selected'));
    }

    public function getItemImages($itemCode)
    {
        $images = ItemImages::query()->where('parent', $itemCode)->orderBy('idx', 'asc')->pluck('image_path', 'name');

        return collect($images)->map(function ($image) {
            $imagePath = $image ? trim((string) $image) : null;

            if (! $imagePath) {
                return Storage::disk('upcloud')->url('icon/no-img.png');
            }

            // If a full URL is already stored, just use it.
            if (Str::startsWith($imagePath, ['http://', 'https://'])) {
                return $imagePath;
            }

            $disk = Storage::disk('upcloud');

            // If the path already includes a directory, treat it as the object key.
            if (str_contains($imagePath, '/')) {
                $storageKey = ltrim($imagePath, '/');
                $url = $this->preferWebpUrlOrOriginal($disk, $storageKey);

                return $url ?? $disk->url($storageKey);
            }

            // Filename-only rows: try img/ first, then legacy items/ and item-images/.
            $candidates = [
                "img/{$imagePath}",
                "items/{$imagePath}",
                "item-images/{$imagePath}",
            ];

            foreach ($candidates as $candidate) {
                if ($disk->exists($candidate)) {
                    $url = $this->preferWebpUrlOrOriginal($disk, $candidate);

                    return $url ?? $disk->url($candidate);
                }
            }

            return $disk->url('icon/no-img.png');
        });
    }

    /**
     * Return webp URL if webp exists for this key, otherwise null (caller uses original).
     */
    private function preferWebpUrlOrOriginal(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $storageKey): ?string
    {
        $baseName = pathinfo($storageKey, PATHINFO_FILENAME);
        $dir = dirname($storageKey);
        $webpKey = ($dir === '.' ? 'img/' : $dir.'/').$baseName.'.webp';
        if ($disk->exists($webpKey)) {
            return $disk->url($webpKey);
        }

        return null;
    }

    /**
     * Available qty for one item/warehouse. Uses getItemStockLevels as single source of truth
     * so the Stock Reservation modal always matches the Item Profile → Stock Level table.
     * Formula: Available = Actual - Reserved - Issued (same as Stock Level).
     */
    public function getItemWarehouseAvailableQty(string $itemCode, string $warehouse)
    {
        $request = new Request;
        $result = $this->getItemStockLevels($itemCode, $request);

        $siteWarehouses = $result['site_warehouses'] ?? [];
        $consignmentWarehouses = $result['consignment_warehouses'] ?? [];
        $allRows = array_merge($siteWarehouses, $consignmentWarehouses);

        foreach ($allRows as $row) {
            if (($row['warehouse'] ?? '') === $warehouse) {
                $availableQty = (float) ($row['available_qty'] ?? 0);

                return response()->json(round($availableQty, 2));
            }
        }

        return response()->json(0);
    }

    public function getItemStockLevels($itemCode, Request $request, ?Item $itemDetails = null)
    {
        $itemDetails = $itemDetails ?? Item::query()->where('name', $itemCode)->first();
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
                ->joinPackingSlipDeliveryNote()
                ->where('at.item_code', $itemCode)
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

            $issuedReservedQty = $reservedQty + $issuedQty;

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

        $itemCodes = collect($productBundle)->pluck('item_code')->unique()->values();
        $grouped = collect($productBundle)->groupBy('item_code');

        $itemsById = Item::query()->whereIn('name', $itemCodes)->get()->keyBy('name');

        $stocks = [];
        foreach ($itemCodes as $itemCode) {
            $bundleItem = $grouped[$itemCode][0] ?? null;
            if (is_array($bundleItem)) {
                $bundleItem = (object) $bundleItem;
            }
            $description = $bundleItem->description ?? null;
            $details = $this->getItemStockLevels($itemCode, $request, $itemsById->get($itemCode));

            unset($details['consignment_warehouses']);

            $details['site_warehouses'] = collect($details['site_warehouses'])->where('actual_qty', '>', 0);
            $details['description'] = $description;

            $stocks[$itemCode] = $details;
        }

        return view('item_stock_level_bundled', compact('stocks'));
    }
}
