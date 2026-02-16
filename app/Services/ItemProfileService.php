<?php

namespace App\Services;

use App\Models\DepartmentWithPriceAccess;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\LandedCostVoucher;
use App\Models\PurchaseOrder;
use App\Models\Singles;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemProfileService
{
    /**
     * Get price-related data for an item (last purchase, landed cost, website price, etc.).
     *
     * @return array{itemRate: float, minimumSellingPrice: float, defaultPrice: float, lastPurchaseRate: float, manualRate: int, lastPurchaseDate: string|null, websitePrice: array|object|null, avgPurchaseRate: string, isTaxIncludedInRate: float, minimumPriceComputation: float, standardPriceComputation: float}
     */
    public function getItemPrices(string $itemCode, ?Item $itemDetails = null): array
    {
        $itemDetails = $itemDetails ?? Item::query()->where('name', $itemCode)->first();

        $userDepartment = Auth::user()->department;
        $userGroup = Auth::user()->user_group;
        $allowedDepartment = DepartmentWithPriceAccess::query()->pluck('department')->toArray();

        $itemRate = $minimumSellingPrice = $defaultPrice = $lastPurchaseRate = $manualRate = $isTaxIncludedInRate = 0;
        $lastPurchaseDate = null;
        $websitePrice = [];
        $avgPurchaseRate = '₱ 0.00';

        if (! in_array($userDepartment, $allowedDepartment) && ! in_array($userGroup, ['Manager', 'Director'])) {
            return [
                'itemRate' => $itemRate,
                'minimumSellingPrice' => $minimumSellingPrice,
                'defaultPrice' => $defaultPrice,
                'lastPurchaseRate' => $lastPurchaseRate,
                'manualRate' => $manualRate,
                'lastPurchaseDate' => $lastPurchaseDate,
                'websitePrice' => $websitePrice,
                'avgPurchaseRate' => $avgPurchaseRate,
                'isTaxIncludedInRate' => 0,
                'minimumPriceComputation' => 0,
                'standardPriceComputation' => 0,
            ];
        }

        $avgPurchaseRate = $this->avgPurchaseRate($itemCode);

        $lastPurchaseOrder = PurchaseOrder::query()->from('tabPurchase Order as po')->join('tabPurchase Order Item as poi', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)->where('poi.item_code', $itemCode)->select('poi.base_rate', 'po.supplier_group', 'po.creation')->orderBy('po.creation', 'desc')->first();

        if ($lastPurchaseOrder) {
            $lastPurchaseDate = Carbon::parse($lastPurchaseOrder->creation)->format('M. d, Y h:i:A');
            if ($lastPurchaseOrder->supplier_group == 'Imported') {
                $lastLandedCostVoucher = LandedCostVoucher::query()->from('tabLanded Cost Voucher as a')
                    ->join('tabLanded Cost Item as b', 'a.name', 'b.parent')
                    ->where('a.docstatus', 1)->where('b.item_code', $itemCode)
                    ->select('a.creation', 'a.name as purchase_order', 'b.item_code', 'b.valuation_rate', DB::raw('ifnull(a.posting_date, a.creation) as transaction_date'), 'a.posting_date')
                    ->orderBy('transaction_date', 'desc')
                    ->first();

                if ($lastLandedCostVoucher) {
                    $itemRate = $lastLandedCostVoucher->valuation_rate;
                }
            } else {
                $itemRate = $lastPurchaseOrder->base_rate;
            }
        }

        $priceSettings = Singles::query()->where('doctype', 'Price Settings')
            ->whereIn('field', ['minimum_price_computation', 'standard_price_computation', 'is_tax_included_in_rate'])->pluck('value', 'field')->toArray();

        $minimumPriceComputation = Arr::get($priceSettings, 'minimum_price_computation', 0);
        $standardPriceComputation = Arr::get($priceSettings, 'standard_price_computation', 0);
        $isTaxIncludedInRate = Arr::get($priceSettings, 'is_tax_included_in_rate', 0);

        $lastPurchaseRate = $itemRate;

        if ($itemRate <= 0 && $itemDetails) {
            $manualRate = 1;
            $itemRate = $itemDetails->custom_item_cost ?? 0;
        }

        $minimumSellingPrice = $itemRate * $minimumPriceComputation;
        $defaultPrice = $itemRate * $standardPriceComputation;

        if ($isTaxIncludedInRate) {
            $defaultPrice = ($itemRate * $standardPriceComputation) * 1.12;
        }

        $websitePrice = ItemPrice::query()
            ->where('price_list', 'Website Price List')->where('selling', 1)
            ->where('item_code', $itemCode)->orderBy('modified', 'desc')
            ->select('price_list_rate', 'price_list')->first();

        $defaultPrice = ($websitePrice) ? $websitePrice->price_list_rate : $defaultPrice;

        return [
            'itemRate' => $itemRate,
            'minimumSellingPrice' => $minimumSellingPrice,
            'defaultPrice' => $defaultPrice,
            'lastPurchaseRate' => $lastPurchaseRate,
            'manualRate' => $manualRate,
            'lastPurchaseDate' => $lastPurchaseDate,
            'websitePrice' => $websitePrice,
            'avgPurchaseRate' => $avgPurchaseRate,
            'isTaxIncludedInRate' => $isTaxIncludedInRate,
            'minimumPriceComputation' => $minimumPriceComputation,
            'standardPriceComputation' => $standardPriceComputation,
        ];
    }

    /**
     * Get average purchase rate for an item.
     */
    public function avgPurchaseRate(string $itemCode): string
    {
        $result = DB::table('tabPurchase Order Item as poi')
            ->join('tabPurchase Order as po', 'po.name', 'poi.parent')
            ->where('po.docstatus', 1)->where('poi.item_code', $itemCode)
            ->selectRaw('AVG(poi.base_rate) as avg_rate')->first();

        $avgRate = $result->avg_rate ?? 0;

        return '₱ '.number_format($avgRate, 2);
    }
}
