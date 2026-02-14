<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\ItemVariantAttribute;
use App\Models\MESOperation;
use App\Models\MESProductionOrder;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    use GeneralTrait;

    public function countProductionToReceive()
    {
        try {
            $allowedWarehouses = $this->getAllowedWarehouseIds();

            return DB::connection('mysql_mes')
                ->table('production_order AS po')
                ->whereNotIn('po.status', ['Cancelled', 'Stopped'])
                ->whereIn('po.fg_warehouse', $allowedWarehouses)
                ->where('po.fg_warehouse', 'P2 - Housing Temporary - FI')
                ->where('po.produced_qty', '>', 0)
                ->whereRaw('po.produced_qty > feedback_qty')
                ->count();
        } catch (QueryException $e) {
            return 0;
        }
    }

    public function viewProductionToReceive(Request $request)
    {
        if (! $request->arr) {
            return view('production_to_receive');
        }

        $list = [];

        try {
            $allowedWarehouses = $this->getAllowedWarehouseIds();

            $q = MESProductionOrder::whereNotIn('status', ['Cancelled'])
                ->whereIn('fg_warehouse', $allowedWarehouses)
                ->where('fg_warehouse', 'P2 - Housing Temporary - FI')
                ->where('produced_qty', '>', 0)
                ->whereRaw('produced_qty > feedback_qty')
                ->get();

            foreach ($q as $row) {
                $parentWarehouse = $this->getWarehouseParent($row->fg_warehouse);

                $owner = ucwords(str_replace('.', ' ', explode('@', $row->created_by)[0]));

                $operationId = ($row->operation_id) ? $row->operation_id : 0;
                $operationName = MESOperation::find($operationId);
                $operationName = ($operationName) ? $operationName->operation_name : '--';

                $list[] = [
                    'production_order' => $row->production_order,
                    'fg_warehouse' => $row->fg_warehouse,
                    'sales_order_no' => $row->sales_order,
                    'material_request' => $row->material_request,
                    'customer' => $row->customer,
                    'item_code' => $row->item_code,
                    'description' => $row->description,
                    'qty_to_receive' => number_format($row->produced_qty - $row->feedback_qty),
                    'qty_to_manufacture' => number_format($row->qty_to_manufacture),
                    'stock_uom' => $row->stock_uom,
                    'parent_warehouse' => $parentWarehouse,
                    'owner' => $owner,
                    'created_at' => Carbon::parse($row->created_at)->format('M-d-Y h:i A'),
                    'operation_name' => $operationName,
                    'delivery_date' => ($row->delivery_date) ? Carbon::parse($row->delivery_date)->format('M-d-Y') : null,
                    'delivery_status' => ($row->delivery_date) ? ((Carbon::parse($row->delivery_date) < now()) ? 'late' : null) : null,
                ];
            }
        } catch (QueryException $e) {
            $list = [];
        }

        return response()->json(['records' => $list]);
    }

    public function feedbackProductionOrderItems($productionOrder, $qtyToManufacture, $fgCompletedQty)
    {
        $productionOrderItemsQry = DB::table('tabWork Order Item')
            ->where('parent', $productionOrder)
            ->where(function ($query) {
                $query
                    ->where('item_alternative_for', 'new_item')
                    ->orWhereNull('item_alternative_for');
            })
            ->orderBy('idx', 'asc')
            ->get();

        $arr = [];
        foreach ($productionOrderItemsQry as $row) {
            $itemRequiredQty = $row->required_qty;
            $itemRequiredQty += DB::table('tabWork Order Item')
                ->where('parent', $productionOrder)
                ->where('item_alternative_for', $row->item_code)
                ->whereNotNull('item_alternative_for')
                ->sum('required_qty');

            $consumedQty = \App\Models\StockEntry::query()
                ->from('tabStock Entry as ste')
                ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                ->where('ste.work_order', $productionOrder)
                ->whereNull('sted.t_warehouse')
                ->where('sted.item_code', $row->item_code)
                ->where('purpose', 'Manufacture')
                ->where('ste.docstatus', 1)
                ->sum('qty');

            $balanceQty = ($row->transferred_qty - $consumedQty);

            $remainingRequiredQty = ($fgCompletedQty - $balanceQty);

            if ($balanceQty <= 0 || $fgCompletedQty > $balanceQty) {
                $alternativeItemsQry = $this->getAlternativeItems($productionOrder, $row->item_code, $remainingRequiredQty);
            } else {
                $alternativeItemsQry = [];
            }

            $qtyPerItem = $itemRequiredQty / $qtyToManufacture;
            $perItem = $qtyPerItem * $fgCompletedQty;

            $requiredQty = ($balanceQty > $perItem) ? $perItem : $balanceQty;

            foreach ($alternativeItemsQry as $aiRow) {
                if ($aiRow['required_qty'] > 0) {
                    $arr[] = [
                        'item_code' => $aiRow['item_code'],
                        'item_name' => $aiRow['item_name'],
                        'description' => $aiRow['description'],
                        'stock_uom' => $aiRow['stock_uom'],
                        'required_qty' => $aiRow['required_qty'],
                        'transferred_qty' => $aiRow['transferred_qty'],
                        'consumed_qty' => $aiRow['consumed_qty'],
                        'balance_qty' => $aiRow['balance_qty'],
                    ];
                }
            }

            if ($balanceQty > 0) {
                $arr[] = [
                    'item_code' => $row->item_code,
                    'item_name' => $row->item_name,
                    'description' => $row->description,
                    'stock_uom' => $row->stock_uom,
                    'required_qty' => $requiredQty,
                    'transferred_qty' => $row->transferred_qty,
                    'consumed_qty' => $consumedQty,
                    'balance_qty' => $balanceQty,
                ];
            }
        }

        return $arr;
    }

    public function getAlternativeItems($productionOrder, $itemCode, $remainingRequiredQty)
    {
        $q = DB::table('tabWork Order Item')
            ->where('parent', $productionOrder)
            ->where('item_alternative_for', $itemCode)
            ->orderBy('required_qty', 'asc')
            ->get();

        $remaining = $remainingRequiredQty;
        $arr = [];
        foreach ($q as $row) {
            if ($remaining > 0) {
                $consumedQty = \App\Models\StockEntry::query()
                    ->from('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
                    ->where('ste.work_order', $productionOrder)
                    ->whereNull('sted.t_warehouse')
                    ->where('sted.item_code', $row->item_code)
                    ->where('purpose', 'Manufacture')
                    ->where('ste.docstatus', 1)
                    ->sum('qty');

                $balanceQty = ($row->transferred_qty - $consumedQty);
                $requiredQty = ($balanceQty > $remaining) ? $remaining : $balanceQty;
                $arr[] = [
                    'item_code' => $row->item_code,
                    'required_qty' => $requiredQty,
                    'item_name' => $row->item_name,
                    'description' => $row->description,
                    'stock_uom' => $row->stock_uom,
                    'transferred_qty' => $row->transferred_qty,
                    'consumed_qty' => $consumedQty,
                    'balance_qty' => $balanceQty,
                ];

                $remaining = $remaining - $balanceQty;
            }
        }

        return $arr;
    }

    public function insertProductionScrap($productionOrder, $qty)
    {
        $productionOrderDetails = DB::connection('mysql_mes')
            ->table('production_order')
            ->where('production_order', $productionOrder)
            ->first();

        if (! $productionOrderDetails) {
            return ApiResponse::failureLegacy('Production Order '.$productionOrder.' not found.');
        }

        $bomScrapDetails = DB::table('tabBOM Scrap Item')
            ->where('parent', $productionOrderDetails->bom_no)
            ->first();

        if (! $bomScrapDetails) {
            return ApiResponse::failureLegacy('BOM '.$productionOrderDetails->bom_no.' not found.');
        }

        $uomDetails = DB::connection('mysql_mes')
            ->table('uom')
            ->where('uom_name', 'Kilogram')
            ->first();

        if (! $uomDetails) {
            return ApiResponse::failureLegacy('UoM Kilogram not found.');
        }

        $thickness = ItemVariantAttribute::query()
            ->where('parent', $bomScrapDetails->item_code)
            ->where('attribute', 'like', '%thickness%')
            ->first();

        if ($thickness) {
            $thickness = $thickness->attribute_value;
            $thickness = str_replace(' ', '', preg_replace('/[^0-9,.]/', '', $thickness));
            $material = strtok($bomScrapDetails->item_name, ' ');
            $scrapQty = $qty * $bomScrapDetails->stock_qty;

            if ($material == 'CRS') {
                $uomArr1 = DB::connection('mysql_mes')
                    ->table('uom_conversion')
                    ->join('uom', 'uom.uom_id', 'uom_conversion.uom_id')
                    ->where('uom.uom_name', $bomScrapDetails->stock_uom)
                    ->pluck('uom_conversion_id')
                    ->toArray();

                $uomArr2 = DB::connection('mysql_mes')
                    ->table('uom_conversion')
                    ->where('uom_id', $uomDetails->uom_id)
                    ->pluck('uom_conversion_id')
                    ->toArray();

                $uomConversionId = array_intersect($uomArr1, $uomArr2);

                $uom1ConversionFactor = DB::connection('mysql_mes')
                    ->table('uom_conversion')
                    ->where('uom_conversion_id', $uomConversionId[0])
                    ->where('uom_id', '!=', $uomDetails->uom_id)
                    ->sum('conversion_factor');

                $uom2ConversionFactor = DB::connection('mysql_mes')
                    ->table('uom_conversion')
                    ->where('uom_conversion_id', $uomConversionId[0])
                    ->where('uom_id', $uomDetails->uom_id)
                    ->sum('conversion_factor');

                $conversionFactor = $uom2ConversionFactor / $uom1ConversionFactor;
                $scrapQty = $scrapQty * $conversionFactor;

                $existingScrap = DB::connection('mysql_mes')
                    ->table('scrap')
                    ->where('material', $material)
                    ->where('uom_id', $uomDetails->uom_id)
                    ->where('thickness', $thickness)
                    ->first();

                if ($existingScrap) {
                    $scrapQty = $scrapQty + $existingScrap->scrap_qty;
                    $values = [
                        'scrap_qty' => $scrapQty,
                        'last_modified_by' => Auth::user()->full_name,
                    ];
                    DB::connection('mysql_mes')
                        ->table('scrap')
                        ->where('scrap_id', $existingScrap->scrap_id)
                        ->update($values);
                    $scrapId = $existingScrap->scrap_id;
                } else {
                    $values = [
                        'uom_conversion_id' => $uomConversionId[0],
                        'uom_id' => $uomDetails->uom_id,
                        'material' => $material,
                        'thickness' => $thickness,
                        'scrap_qty' => $scrapQty,
                        'created_by' => Auth::user()->full_name,
                    ];
                    $scrapId = DB::connection('mysql_mes')->table('scrap')->insertGetId($values);
                }

                $existingScrapReference = DB::connection('mysql_mes')
                    ->table('scrap_reference')
                    ->where('reference_type', 'Production Order')
                    ->where('reference_id', $productionOrder)
                    ->where('scrap_id', $scrapId)
                    ->first();

                if ($existingScrapReference) {
                    $scrapQty = $scrapQty + ($existingScrap?->scrap_qty ?? 0);
                    $values = [
                        'scrap_qty' => $scrapQty,
                        'last_modified_by' => Auth::user()->full_name,
                    ];
                    DB::connection('mysql_mes')
                        ->table('scrap_reference')
                        ->where('scrap_id', $existingScrapReference->scrap_reference_id)
                        ->update($values);
                } else {
                    $values = [
                        'reference_type' => 'Production Order',
                        'reference_id' => $productionOrder,
                        'uom_id' => $uomDetails->uom_id,
                        'scrap_id' => $scrapId,
                        'scrap_qty' => $scrapQty,
                        'created_by' => Auth::user()->full_name,
                    ];
                    DB::connection('mysql_mes')->table('scrap_reference')->insert($values);
                }
            }
        }
    }
}
