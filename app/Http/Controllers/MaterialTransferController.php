<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\SubmitInternalTransferRequest;
use App\Http\Requests\UpdateStockEntryRequest;
use App\Models\AthenaTransaction;
use App\Models\Bin;
use App\Models\Item;
use App\Models\ItemImages;
use App\Models\ItemSupplier;
use App\Models\MaterialRequest;
use App\Models\SalesOrder;
use App\Models\StockEntry;
use App\Models\StockEntryDetail;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Pipelines\ViewMaterialIssuePipeline;
use App\Pipelines\ViewMaterialTransferForManufacturePipeline;
use App\Pipelines\ViewMaterialTransferPipeline;
use App\Services\StockEntryService;
use App\Traits\ERPTrait;
use App\Traits\GeneralTrait;
use Carbon\Carbon;
use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialTransferController extends Controller
{
    use ERPTrait, GeneralTrait;

    public function __construct(
        protected StockEntryService $stockEntryService,
        protected ViewMaterialIssuePipeline $viewMaterialIssuePipeline,
        protected ViewMaterialTransferForManufacturePipeline $viewMaterialTransferForManufacturePipeline,
        protected ViewMaterialTransferPipeline $viewMaterialTransferPipeline
    ) {}

    public function viewMaterialIssue(Request $request)
    {
        if (! $request->arr) {
            return view('material_issue');
        }

        $passable = (object) [
            'allowedWarehouses' => $this->getAllowedWarehouseIds(),
            'getActualQtyBulk' => fn (array $pairs) => $this->getActualQtyBulk($pairs),
            'getAvailableQtyBulk' => fn (array $pairs) => $this->getAvailableQtyBulk($pairs),
            'getWarehouseParentsBulk' => fn (array $warehouses) => $this->getWarehouseParentsBulk($warehouses),
        ];

        return $this->viewMaterialIssuePipeline->run($passable);
    }

    public function viewMaterialTransferForManufacture(Request $request)
    {
        if (! $request->arr) {
            return view('material_transfer_for_manufacture');
        }

        $passable = (object) [
            'allowedWarehouses' => $this->getAllowedWarehouseIds(),
            'getMaterialTransferForManufactureEntries' => fn ($allowedWarehouses) => $this->getMaterialTransferForManufactureEntries($allowedWarehouses),
            'buildMaterialTransferLookupData' => fn ($entries) => $this->buildMaterialTransferLookupData($entries),
            'buildMaterialTransferRecordsList' => fn ($entries, $lookupData) => $this->buildMaterialTransferRecordsList($entries, $lookupData),
        ];

        return $this->viewMaterialTransferForManufacturePipeline->run($passable);
    }

    /**
     * @param  \Illuminate\Support\Collection  $allowedWarehouses
     * @return \Illuminate\Support\Collection
     */
    private function getMaterialTransferForManufactureEntries($allowedWarehouses)
    {
        return StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer for Manufacture')
            ->where('ste.transfer_as', '!=', 'For Return')
            ->whereIn('s_warehouse', $allowedWarehouses)
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'ste.owner', 'ste.material_request', 'ste.work_order', 'ste.creation', 'ste.so_customer_name')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->get();
    }

    private function buildMaterialTransferLookupData(Collection $entries): array
    {
        $entriesArray = $entries->toArray();
        $itemCodesArr = array_values(array_unique(array_column($entriesArray, 'item_code')));
        $sourceWarehousesArr = array_values(array_unique(array_column($entriesArray, 's_warehouse')));
        $workOrdersArr = array_values(array_unique(array_column($entriesArray, 'work_order')));

        return [
            'itemActualQty' => $this->getItemActualQtyLookup($itemCodesArr, $sourceWarehousesArr),
            'stockReservation' => $this->getStockReservationLookup($itemCodesArr, $sourceWarehousesArr),
            'steTotalIssued' => $this->getSteTotalIssuedLookup($itemCodesArr, $sourceWarehousesArr),
            'atTotalIssued' => $this->getAtTotalIssuedLookup($itemCodesArr, $sourceWarehousesArr),
            'partNosQuery' => ItemSupplier::query()
                ->whereIn('parent', $itemCodesArr)
                ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))
                ->groupBy('parent')
                ->pluck('supplier_part_nos', 'parent'),
            'parentWarehouses' => Warehouse::where('disabled', 0)->whereIn('name', $sourceWarehousesArr)->pluck('parent_warehouse', 'name'),
            'workOrderDeliveryDate' => WorkOrder::whereIn('name', $workOrdersArr)->pluck('delivery_date', 'name'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getItemActualQtyLookup(array $itemCodes, array $warehouses): array
    {
        return Bin::query()
            ->join('tabWarehouse', 'tabBin.warehouse', 'tabWarehouse.name')
            ->whereIn('tabBin.item_code', $itemCodes)
            ->whereIn('tabBin.warehouse', $warehouses)
            ->where('tabWarehouse.disabled', 0)
            ->selectRaw('SUM(actual_qty) as actual_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')
            ->get()
            ->groupBy('item')
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getStockReservationLookup(array $itemCodes, array $warehouses): array
    {
        return StockReservation::whereIn('item_code', $itemCodes)
            ->whereIn('warehouse', $warehouses)
            ->whereIn('status', ['Active', 'Partially Issued'])
            ->selectRaw('SUM(reserve_qty) as total_reserved_qty, SUM(consumed_qty) as total_consumed_qty, CONCAT(item_code, "-", warehouse) as item')
            ->groupBy('item_code', 'warehouse')
            ->get()
            ->groupBy('item')
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getSteTotalIssuedLookup(array $itemCodes, array $warehouses): array
    {
        return StockEntryDetail::where('docstatus', 0)
            ->where('status', 'Issued')
            ->whereIn('item_code', $itemCodes)
            ->whereIn('s_warehouse', $warehouses)
            ->selectRaw('SUM(qty) as total_issued, CONCAT(item_code, "-", s_warehouse) as item')
            ->groupBy('item_code', 's_warehouse')
            ->get()
            ->groupBy('item')
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getAtTotalIssuedLookup(array $itemCodes, array $warehouses): array
    {
        return AthenaTransaction::query()
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
            ->whereIn('at.source_warehouse', $warehouses)
            ->selectRaw('SUM(at.issued_qty) as total_issued, CONCAT(at.item_code, "-", at.source_warehouse) as item')
            ->groupBy('at.item_code', 'at.source_warehouse')
            ->get()
            ->groupBy('item')
            ->toArray();
    }

    /**
     * @param  \Illuminate\Support\Collection  $entries
     * @param  array<string, mixed>  $lookupData
     * @return array<int, array<string, mixed>>
     */
    private function buildMaterialTransferRecordsList($entries, array $lookupData)
    {
        $itemActualQty = $lookupData['itemActualQty'];
        $stockReservation = $lookupData['stockReservation'];
        $steTotalIssued = $lookupData['steTotalIssued'];
        $atTotalIssued = $lookupData['atTotalIssued'];
        $partNosQuery = $lookupData['partNosQuery'];
        $parentWarehouses = $lookupData['parentWarehouses'];
        $workOrderDeliveryDate = $lookupData['workOrderDeliveryDate'];

        $list = [];
        foreach ($entries as $d) {
            $reservedQty = Arr::get($stockReservation, "{$d->item_code}-{$d->s_warehouse}.0.total_reserved_qty", 0);
            $consumedQty = Arr::get($stockReservation, "{$d->item_code}-{$d->s_warehouse}.0.total_consumed_qty", 0);
            $reservedQty = $reservedQty - $consumedQty;

            $steItem = data_get($steTotalIssued, "{$d->item_code}-{$d->s_warehouse}.0");
            $issuedQty = is_array($steItem) ? ($steItem['total_issued'] ?? 0) : ($steItem->total_issued ?? 0);
            $atItem = data_get($atTotalIssued, "{$d->item_code}-{$d->s_warehouse}.0");
            $issuedQty += is_array($atItem) ? ($atItem['total_issued'] ?? 0) : ($atItem->total_issued ?? 0);

            $actualItem = data_get($itemActualQty, "{$d->item_code}-{$d->s_warehouse}.0");
            $actualQty = is_array($actualItem) ? ($actualItem['actual_qty'] ?? 0) : ($actualItem->actual_qty ?? 0);

            $refNo = ($d->material_request) ? $d->material_request : $d->sales_order_no;
            $partNos = Arr::get($partNosQuery, $d->item_code, 0);
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));
            $parentWarehouse = Arr::get($parentWarehouses, $d->s_warehouse);
            $deliveryDate = Arr::get($workOrderDeliveryDate, $d->work_order);

            $list[] = [
                'customer' => $d->so_customer_name,
                'order_status' => null,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $partNos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'balance' => $actualQty,
                'ref_no' => $refNo,
                'parent_warehouse' => $parentWarehouse,
                'production_order' => $d->work_order,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'delivery_date' => ($deliveryDate) ? Carbon::parse($deliveryDate)->format('M-d-Y') : null,
                'delivery_status' => ($deliveryDate) ? ((Carbon::parse($deliveryDate) < now()) ? 'late' : null) : null,
            ];
        }

        return $list;
    }

    public function viewMaterialTransfer(Request $request)
    {
        if (! $request->arr) {
            return view('material_transfer');
        }

        $passable = (object) [
            'allowedWarehouses' => $this->getAllowedWarehouseIds(),
            'getMaterialTransferEntries' => fn ($allowedWarehouses) => $this->getMaterialTransferEntries($allowedWarehouses),
            'buildMaterialTransferViewLookupData' => fn ($entries) => $this->buildMaterialTransferViewLookupData($entries),
            'buildMaterialTransferViewRecordsList' => fn ($entries, $lookupData) => $this->buildMaterialTransferViewRecordsList($entries, $lookupData),
        ];

        return $this->viewMaterialTransferPipeline->run($passable);
    }

    /**
     * @param  \Illuminate\Support\Collection|array  $allowedWarehouses
     * @return \Illuminate\Support\Collection
     */
    private function getMaterialTransferEntries($allowedWarehouses)
    {
        $q1 = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('s_warehouse', $allowedWarehouses)
            ->whereNotin('transfer_as', ['Consignment', 'Sample Item', 'For Return'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'ste.work_order')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC");

        return StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('ste.docstatus', 0)
            ->where('purpose', 'Material Transfer')
            ->whereIn('t_warehouse', $allowedWarehouses)
            ->whereIn('transfer_as', ['For Return', 'Internal Transfer', 'Pull Out Item'])
            ->select('sted.status', 'sted.validate_item_code', 'ste.sales_order_no', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.owner', 'ste.material_request', 'ste.creation', 'ste.transfer_as', 'ste.work_order')
            ->orderByRaw("FIELD(sted.status, 'For Checking', 'Issued') ASC")
            ->union($q1)
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection  $entries
     * @return array{stockReservationQty: \Illuminate\Support\Collection, consumedQty: \Illuminate\Support\Collection, itemActualQty: \Illuminate\Support\Collection, totalIssuedSte: \Illuminate\Support\Collection, totalIssuedAt: \Illuminate\Support\Collection, references: \Illuminate\Support\Collection, partNosQuery: \Illuminate\Support\Collection, parentWarehouses: \Illuminate\Support\Collection}
     */
    private function buildMaterialTransferViewLookupData($entries)
    {
        $itemCodes = array_values(array_unique(array_column($entries->toArray(), 'item_code')));
        $sourceWarehouses = array_values(array_unique(array_column($entries->toArray(), 's_warehouse')));

        $stockReservationQty = $consumedQty = $itemActualQty = $totalIssuedSte = $totalIssuedAt = collect();
        $references = $partNosQuery = $parentWarehouses = collect();

        if (count($itemCodes) > 0) {
            $stockReservationQty = StockReservation::whereIn('item_code', $itemCodes)
                ->whereIn('warehouse', $sourceWarehouses)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'reserve_qty')
                ->pluck('reserve_qty', 'id');

            $consumedQty = StockReservation::whereIn('item_code', $itemCodes)
                ->whereIn('warehouse', $sourceWarehouses)
                ->whereIn('type', ['In-house', 'Consignment', 'Website Stocks'])
                ->whereIn('status', ['Active', 'Partially Issued'])
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'consumed_qty')
                ->pluck('consumed_qty', 'id');

            $itemActualQty = Bin::whereIn('item_code', $itemCodes)
                ->whereIn('warehouse', $sourceWarehouses)
                ->select(DB::raw('CONCAT(item_code, REPLACE(warehouse, " ", "")) as id'), 'actual_qty')
                ->pluck('actual_qty', 'id');

            $totalIssuedSte = StockEntryDetail::where('docstatus', 0)
                ->where('status', 'Issued')
                ->whereIn('item_code', $itemCodes)
                ->whereIn('s_warehouse', $sourceWarehouses)
                ->select(DB::raw('CONCAT(item_code, REPLACE(s_warehouse, " ", "")) as id'), DB::raw('sum(qty) as qty'))
                ->groupBy('item_code', 's_warehouse')
                ->pluck('qty', 'id');

            $totalIssuedAt = AthenaTransaction::query()
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
                ->whereIn('at.source_warehouse', $sourceWarehouses)
                ->select(DB::raw('CONCAT(at.item_code, REPLACE(at.source_warehouse, " ", "")) as id'), DB::raw('sum(at.issued_qty) as issued_qty'))
                ->groupBy('at.item_code', 'at.source_warehouse')
                ->pluck('issued_qty', 'id');

            $materialRequests = array_values(array_filter(array_unique(array_column($entries->toArray(), 'material_request'))));
            $salesOrders = array_values(array_filter(array_unique(array_column($entries->toArray(), 'sales_order_no'))));
            $references = collect();
            if (count($materialRequests) > 0) {
                $references = $references->merge(MaterialRequest::whereIn('name', $materialRequests)->pluck('customer', 'name'));
            }
            if (count($salesOrders) > 0) {
                $references = $references->merge(SalesOrder::whereIn('name', $salesOrders)->pluck('customer', 'name'));
            }

            $partNosQuery = ItemSupplier::query()
                ->whereIn('parent', $itemCodes)
                ->select('parent', DB::raw('GROUP_CONCAT(supplier_part_no) as supplier_part_nos'))
                ->groupBy('parent')
                ->pluck('supplier_part_nos', 'parent');

            $parentWarehouses = Warehouse::where('disabled', 0)->whereIn('name', $sourceWarehouses)->pluck('parent_warehouse', 'name');
        }

        return [
            'stockReservationQty' => $stockReservationQty,
            'consumedQty' => $consumedQty,
            'itemActualQty' => $itemActualQty,
            'totalIssuedSte' => $totalIssuedSte,
            'totalIssuedAt' => $totalIssuedAt,
            'references' => $references,
            'partNosQuery' => $partNosQuery,
            'parentWarehouses' => $parentWarehouses,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection  $entries
     * @param  array{stockReservationQty: \Illuminate\Support\Collection, consumedQty: \Illuminate\Support\Collection, itemActualQty: \Illuminate\Support\Collection, totalIssuedSte: \Illuminate\Support\Collection, totalIssuedAt: \Illuminate\Support\Collection, references: \Illuminate\Support\Collection, partNosQuery: \Illuminate\Support\Collection, parentWarehouses: \Illuminate\Support\Collection}  $lookupData
     * @return array<int, array<string, mixed>>
     */
    private function buildMaterialTransferViewRecordsList($entries, array $lookupData)
    {
        $stockReservationQty = $lookupData['stockReservationQty'];
        $consumedQty = $lookupData['consumedQty'];
        $itemActualQty = $lookupData['itemActualQty'];
        $totalIssuedSte = $lookupData['totalIssuedSte'];
        $totalIssuedAt = $lookupData['totalIssuedAt'];
        $references = $lookupData['references'];
        $partNosQuery = $lookupData['partNosQuery'];
        $parentWarehouses = $lookupData['parentWarehouses'];

        $list = [];
        foreach ($entries as $d) {
            $arrKey = $d->item_code.str_replace(' ', '', $d->s_warehouse);

            $reservedQty = Arr::get($stockReservationQty, $arrKey, 0) - Arr::get($consumedQty, $arrKey, 0);
            $issuedQty = Arr::get($totalIssuedSte, $arrKey, 0) + Arr::get($totalIssuedAt, $arrKey, 0);
            $actualQty = Arr::get($itemActualQty, $arrKey, 0);

            $availableQty = ($actualQty - $issuedQty - $reservedQty);
            $availableQty = ($availableQty < 0) ? 0 : $availableQty;

            $refNo = ($d->material_request) ? $d->material_request : $d->sales_order_no;
            $customer = Arr::get($references, $d->material_request) ?: Arr::get($references, $d->sales_order_no);
            $partNos = Arr::get($partNosQuery, $d->item_code, 0);
            $owner = ucwords(str_replace('.', ' ', explode('@', $d->owner)[0]));

            $parentWarehouse = in_array($d->transfer_as, ['For Return', 'Pull Out Item'])
                ? Arr::get($parentWarehouses, $d->t_warehouse)
                : Arr::get($parentWarehouses, $d->s_warehouse);

            $list[] = [
                'customer' => $customer,
                'work_order' => $d->work_order,
                'item_code' => $d->item_code,
                'description' => $d->description,
                's_warehouse' => $d->s_warehouse,
                't_warehouse' => $d->t_warehouse,
                'transfer_as' => $d->transfer_as,
                'available_qty' => $availableQty,
                'uom' => $d->uom,
                'name' => $d->name,
                'owner' => $owner,
                'parent' => $d->parent,
                'part_nos' => $partNos,
                'qty' => $d->qty,
                'validate_item_code' => $d->validate_item_code,
                'status' => $d->status,
                'ref_no' => $refNo,
                'parent_warehouse' => $parentWarehouse,
                'creation' => Carbon::parse($d->creation)->format('M-d-Y h:i:A'),
                'transaction_date' => Carbon::parse($d->creation),
            ];
        }

        return $list;
    }

    public function submitInternalTransfer(SubmitInternalTransferRequest $request)
    {
        try {
            $childId = $request->child_tbl_id;
            $stockEntry = StockEntry::with('items')->whereHas('items', function ($item) use ($childId) {
                return $item->where('name', $childId);
            })->first();

            $now = now();

            if (! $stockEntry) {
                throw new Exception('Record not found');
            }

            $stockEntry->qty = (float) $stockEntry->qty;

            $stockEntryItem = collect($stockEntry->items)->where('name', $childId)->first();
            $itemCode = $stockEntryItem->item_code;
            $itemDetails = Item::find($itemCode);

            if (in_array($stockEntry->item_status, ['Issued', 'Returned'])) {
                throw new Exception("Item already $stockEntry->item_status");
            }

            if ($stockEntry->docstatus == 1) {
                throw new Exception('Item already issued.');
            }

            if (! $itemDetails) {
                throw new Exception("Item <b>$itemCode</b> not found.");
            }

            if ($itemDetails->is_stock_item == 0) {
                throw new Exception("Item <b>$itemCode</b> is not a stock item.");
            }

            if ($request->barcode != $itemCode) {
                throw new Exception("Invalid barcode for <b>$itemDetails->item_code</b>.");
            }

            if ($request->qty <= 0) {
                throw new Exception('Qty cannot be less than or equal to 0.');
            }

            if ($stockEntry->material_request) {
                $mreqIssuedQty = StockEntry::query()
                    ->from('tabStock Entry as ste')
                    ->join('tabStock Entry Detail as sted', 'sted.parent', 'ste.name')
                    ->where('sted.s_warehouse', $stockEntryItem->s_warehouse)
                    ->where('sted.t_warehouse', $stockEntryItem->t_warehouse)
                    ->where('ste.material_request', $stockEntry->material_request)
                    ->where('ste.docstatus', 1)
                    ->where('purpose', 'Material Transfer')
                    ->where('sted.item_code', $itemCode)
                    ->where('sted.status', 'Issued')
                    ->where('ste.docstatus', '<', 2)
                    ->sum('issued_qty');

                $mreqQry = MaterialRequest::with(['items' => function ($item) use ($itemCode) {
                    $item->where('item_code', $itemCode);
                }])->whereHas('items', function ($item) use ($itemCode) {
                    $item->where('item_code', $itemCode);
                })->find($stockEntry->material_request);

                if (! $mreqQry) {
                    throw new Exception("Item $itemCode not found in $stockEntry->material_request<br/>Please contact MREQ owner: $stockEntry->requested_by");
                }

                $mreqItem = collect($mreqQry->items)->first();
                $mreqRequestedQty = $mreqItem->qty;
                if ($mreqIssuedQty >= $mreqRequestedQty) {
                    $mreqIssuedQty = number_format($mreqIssuedQty);
                    $mreqRequestedQty = number_format($mreqRequestedQty);
                    throw new Exception("Issued qty cannot be greater than requested qty<br/>Total Issued Qty: $mreqIssuedQty<br/>Requested Qty: $mreqRequestedQty<br/>Please contact MREQ owner: ".$stockEntry->requested_by);
                }

                if ($request->qty > ($mreqRequestedQty - $mreqIssuedQty)) {
                    $diff = $mreqRequestedQty - $mreqIssuedQty;
                    throw new Exception("Qty cannot be greater than $diff.");
                }
            }

            $unissuedQty = $stockEntryItem->qty - $request->qty;

            if ($unissuedQty > 0) {
                $unissuedStockEntry = [
                    'title' => 'Material Transfer',
                    'naming_series' => 'STE-',
                    'company' => 'FUMACO Inc.',
                    'project' => $stockEntry->project,
                    'work_order' => $stockEntry->work_order,
                    'purpose' => 'Material Transfer',
                    'stock_entry_type' => 'Material Transfer',
                    'material_request' => $stockEntry->material_request,
                    'item_status' => 'For Checking',
                    'sales_order_no' => $stockEntry->sales_order_no,
                    'transfer_as' => 'For Return',
                    'item_classification' => $stockEntry->item_classification,
                    'so_customer_name' => $stockEntry->so_customer_name,
                    'order_type' => $stockEntry->order_type,
                    'items' => [[
                        't_warehouse' => $stockEntryItem->t_warehouse,
                        'transfer_qty' => $unissuedQty,
                        'expense_account' => 'Cost of Goods Sold - FI',
                        'cost_center' => 'Main - FI',
                        's_warehouse' => $stockEntryItem->s_warehouse,
                        'item_code' => $itemCode,
                        'qty' => $unissuedQty,
                    ]],
                ];

                $unissuedResponse = $this->erpPost('Stock Entry', $unissuedStockEntry);
                if (! isset($unissuedResponse['data'])) {
                    $err = data_get($unissuedResponse, 'exception', 'An error occured while submitting Stock entry for unissued items');
                    throw new Exception($err);
                }
            }

            foreach ($stockEntry->items as $item) {
                $item->qty = (float) $item->qty;
                if ($item->name === $childId) {
                    $item->session_user = Auth::user()->wh_user;
                    $item->status = 'Issued';
                    $item->transfer_qty = $request->qty;
                    $item->qty = (float) $request->qty;
                    $item->issued_qty = $request->qty;
                    $item->validate_item_code = $request->barcode;
                    $item->date_modified = $now->toDateTimeString();
                }
            }

            $checker = collect($stockEntry->items)->where('name', '!=', $childId)->where('status', 'For Checking')->count();
            if (! $checker) {
                $stockEntry->item_status = 'Issued';
                $stockEntry->docstatus = 1;
            }

            unset($stockEntry->creation, $stockEntry->owner);

            $response = $this->erpPut('Stock Entry', $stockEntry->name, collect($stockEntry)->toArray());

            if (! Arr::has($response, 'data')) {
                if (Arr::has($response, 'exc_type') && $response['exc_type'] == 'TimestampMismatchError') {
                    $stockEntry = $this->erpGet('Stock Entry', $stockEntry->name);
                    $stockEntry = $stockEntry['data'];

                    $stockEntry['items'] = collect($stockEntry['items'])->map(function ($item) use ($request, $now, $childId) {
                        $item['qty'] = (float) $item['qty'];
                        if ($item['name'] === $childId) {
                            $item['session_user'] = Auth::user()->wh_user;
                            $item['status'] = 'Issued';
                            $item['transfer_qty'] = $request->qty;
                            $item['qty'] = (float) $request->qty;
                            $item['issued_qty'] = $request->qty;
                            $item['validate_item_code'] = $request->barcode;
                            $item['date_modified'] = $now->toDateTimeString();
                        }

                        return $item;
                    });

                    $checker = collect($stockEntry['items'])->where('name', '!=', $childId)->where('status', 'For Checking')->count();
                    if (! $checker) {
                        $stockEntry['item_status'] = 'Issued';
                        $stockEntry['docstatus'] = 1;
                    }

                    $response = $this->erpPut('Stock Entry', $stockEntry['name'], collect($stockEntry)->toArray());

                    if (! Arr::has($response, 'data')) {
                        $err = data_get($response, 'exception', 'An error occured while updating Stock Entry');
                        throw new Exception($err);
                    }
                } else {
                    $err = data_get($response, 'exception', 'An error occured while updating Stock Entry');
                    throw new Exception($err);
                }
            }

            return ApiResponse::success("Item <b>$itemCode</b> has been checked out.");
        } catch (\Throwable $th) {
            Log::error('MaterialTransferController checkoutSteItem failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return ApiResponse::failure('Error creating transaction. Please contact your system administrator.');
        }
    }

    public function getSteDetails($id, Request $request)
    {
        $q = StockEntry::query()
            ->from('tabStock Entry as ste')
            ->join('tabStock Entry Detail as sted', 'ste.name', 'sted.parent')
            ->where('sted.name', $id)
            ->select('ste.work_order', 'ste.transfer_as', 'ste.purpose', 'sted.parent', 'sted.name', 'sted.t_warehouse', 'sted.s_warehouse', 'sted.item_code', 'sted.description', 'sted.uom', 'sted.qty', 'sted.actual_qty', 'sted.validate_item_code', 'sted.owner', 'sted.status', 'sted.remarks', 'sted.stock_uom', 'ste.sales_order_no', 'ste.material_request', 'ste.issue_as', 'ste.docstatus')
            ->first();

        if (! $q) {
            throw new ErrorException('Stock Entry not found.');
        }

        $refNo = ($q->sales_order_no) ? $q->sales_order_no : $q->material_request;

        $owner = ucwords(str_replace('.', ' ', explode('@', $q->owner)[0]));

        $img = ItemImages::query()->where('parent', $q->item_code)->orderBy('idx', 'asc')->pluck('image_path')->first();
        $img = $img ? "/img/$img" : '/icon/no_img.png';
        $img = $this->base64Image($img);

        $sWarehouse = $q->purpose == 'Manufacture' ? 'Goods In Transit - FI' : $q->s_warehouse;

        $availableQty = $this->getAvailableQty($q->item_code, $sWarehouse);

        $stockReservationDetails = [];
        $soDetails = SalesOrder::find($refNo);
        $mrDetails = MaterialRequest::find($refNo);

        $salesPerson = ($soDetails) ? $soDetails->sales_person : null;
        $salesPerson = ($mrDetails) ? $mrDetails->sales_person : $salesPerson;
        $project = ($soDetails) ? $soDetails->project : null;
        $project = ($mrDetails) ? $mrDetails->project : $project;
        $consignmentWarehouse = null;
        if ($q->transfer_as == 'Consignment') {
            $salesPerson = null;
            $project = null;
            $consignmentWarehouse = $q->t_warehouse;
        }

        $stockReservationDetails = $this->getStockReservation($q->item_code, $q->s_warehouse, $salesPerson, $project, $consignmentWarehouse);

        $data = [
            'name' => $q->name,
            'purpose' => $q->purpose,
            's_warehouse' => $q->s_warehouse,
            't_warehouse' => $q->t_warehouse,
            'available_qty' => $availableQty,
            'validate_item_code' => $q->validate_item_code,
            'img' => $img,
            'item_code' => $q->item_code,
            'description' => $q->description,
            'ref_no' => ($refNo) ? $refNo : '-',
            'stock_uom' => $q->stock_uom,
            'qty' => ($q->qty * 1),
            'transfer_as' => $q->transfer_as,
            'owner' => $owner,
            'status' => $q->status,
            'stock_reservation' => $stockReservationDetails,
            'docstatus' => $q->docstatus,
            'parent' => $q->parent,
            'reference' => 'Stock Entry',
        ];

        if ($q->purpose == 'Manufacture') {
            return view('goods_in_transit_modal_content', compact('data'));
        }

        if ($q->purpose == 'Material Transfer for Manufacture') {
            return view('production_withdrawals_modal_content', compact('data'));
        }

        if ($q->purpose == 'Material Issue') {
            if ($q->issue_as == 'Customer Replacement') {
                return view('order_replacement_modal_content', compact('data'));
            } else {
                return view('material_issue_modal_content', compact('data'));
            }
        }

        if (in_array($q->transfer_as, ['Consignment', 'Sample Item'])) {
            $isStockEntry = true;

            return view('deliveries_modal_content', compact('data', 'isStockEntry'));
        }

        if ($q->purpose == 'Material Receipt') {
            $isStockEntry = true;

            return view('return_modal_content', compact('data', 'isStockEntry'));
        }

        if ($q->purpose == 'Material Transfer') {
            return view('internal_transfer_modal_content', compact('data'));
        }

        return response()->json($data);
    }

    public function createStockLedgerEntry($stockEntry)
    {
        return $this->stockEntryService->createStockLedgerEntry($stockEntry);
    }

    public function updateBin($stockEntry)
    {
        $result = $this->stockEntryService->updateBin($stockEntry);
        if (! ($result['success'] ?? true)) {
            return ApiResponse::failureLegacy($result['message'] ?? 'Error', 422, ['error' => $result['message'] ?? 'Error', 'id' => $result['id'] ?? $stockEntry]);
        }
    }

    public function createGlEntry($stockEntry)
    {
        return $this->stockEntryService->createGlEntry($stockEntry);
    }

    public function generateStockEntry($productionOrder)
    {
        try {
            $now = now();

            $stockEntry = StockEntry::with('items')->where('docstatus', 0)->where('work_order', $productionOrder)->first();
            $stockEntryItems = collect($stockEntry->items)->groupBy('item_code');

            $productionOrderDetails = WorkOrder::with('items')->find($productionOrder);
            $productionOrderItems = collect($productionOrderDetails->items)->whereIn('item_code', collect($stockEntry->items)->pluck('item_code'));

            if (! $productionOrderItems) {
                throw new Exception('No item(s) found');
            }

            $itemWarehousePairs = $productionOrderItems->map(fn ($item) => [$item->item_code, $item->source_warehouse])->unique()->values()->toArray();
            $actualQtyMap = $this->getActualQtyBulk($itemWarehousePairs);

            $stockEntryDetail = [];
            $itemStatus = 'For Checking';
            $docstatus = 0;
            foreach ($productionOrderItems as $item) {
                $itemStatus = 'For Checking';
                $docstatus = 0;
                $itemCode = $item->item_code;

                $remainingQty = $item->required_qty - $item->transferred_qty;

                if ($remainingQty < 0) {
                    continue;
                }

                $issuedQty = data_get($stockEntryItems, "{$itemCode}.0.qty", 0);

                $key = "{$item->item_code}-{$item->source_warehouse}";
                $actualQty = $actualQtyMap[$key] ?? 0;

                if (in_array($item->source_warehouse, ['Fabrication - FI', 'Spotwelding Warehouse - FI']) && $actualQty > $item->required_qty) {
                    $itemStatus = 'Issued';
                    $docstatus = 1;
                }

                $stockEntryDetail[] = [
                    't_warehouse' => $productionOrderDetails->wip_warehouse,
                    'transfer_qty' => (float) $remainingQty,
                    'expense_account' => 'Cost of Goods Sold - FI',
                    'cost_center' => 'Main - FI',
                    's_warehouse' => $item->source_warehouse,
                    'item_code' => $item->item_code,
                    'qty' => (float) $remainingQty,
                    'status' => $itemStatus,
                    'date_modified' => ($itemStatus == 'Issued') ? $now->toDateTimeString() : null,
                    'session_user' => ($itemStatus == 'Issued') ? Auth::user()->full_name : null,
                    'remarks' => ($itemStatus == 'Issued') ? 'MES' : null,
                ];
            }

            if (! $stockEntryDetail) {
                return ['success' => 1, 'message' => 'No items found.'];
            }

            $stockEntryData = [
                'docstatus' => $docstatus,
                'naming_series' => 'STE-',
                'posting_time' => $now->format('h:i:a'),
                'to_warehouse' => $productionOrderDetails->wip_warehouse,
                'company' => 'FUMACO Inc.',
                'purpose' => 'Material Transfer for Manufacture',
                'item_status' => $itemStatus,
                'posting_date' => $now->format('M d, Y'),
                'posting_datetime' => $now->format('M d, Y h:i:a'),
                'fg_completed_qty' => (float) $productionOrderDetails->qty,
                'title' => 'Material Transfer for Manufacture',
                'project' => $productionOrderDetails->project,
                'work_order' => $productionOrder,
                'stock_entry_type' => 'Material Transfer for Manufacture',
                'material_request' => $productionOrderDetails->material_request,
                'sales_order_no' => $productionOrderDetails->sales_order_no,
                'transfer_as' => 'Internal Transfer',
                'so_customer_name' => $productionOrderDetails->customer,
                'order_type' => $productionOrderDetails->classification,
                'items' => $stockEntryDetail,
            ];

            $stockEntryResponse = $this->erpPost('Stock Entry', $stockEntryData);
            if (! isset($stockEntryResponse['data'])) {
                $err = $stockEntryResponse['exception'] ?? 'An error occured while generating Stock Entry';
                throw new Exception($err);
            }

            return ['success' => 1, 'message' => 'Stock Entry has been created.'];
        } catch (Exception $e) {
            Log::error('MaterialTransferController generateStockEntry failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => 0, 'message' => $e->getMessage()];
        }
    }

    public function updateStockEntry(UpdateStockEntryRequest $request)
    {
        DB::beginTransaction();
        try {
            $now = now();
            $ste = StockEntry::query()->where('name', $request->ste_no)->first();

            $steItems = StockEntryDetail::query()->where('parent', $request->ste_no)->get();

            foreach ($steItems as $item) {
                $qty = $item->qty / $ste->fg_completed_qty;
                $qty = $qty * $request->qty;
                $rm = [
                    'modified' => $now->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                    'transfer_qty' => $qty,
                    'basic_amount' => $item->basic_rate * $qty,
                    'basic_rate' => $item->basic_rate,
                    'description' => $item->description,
                    'qty' => $qty,
                    'amount' => $item->basic_rate * $qty,
                    'valuation_rate' => $item->basic_rate,
                ];

                StockEntryDetail::query()->where('name', $item->name)->update($rm);
            }

            $basicAmount = StockEntryDetail::query()->where('parent', $request->ste_no)->sum('basic_amount');

            $stockEntryData = [
                'modified' => $now->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
                'fg_completed_qty' => $request->qty,
                'posting_time' => $now->format('H:i:s'),
                'total_outgoing_value' => $basicAmount,
                'total_amount' => $basicAmount,
                'total_incoming_value' => $basicAmount,
                'posting_date' => $now->format('Y-m-d'),
            ];

            StockEntry::query()->where('name', $request->ste_no)->update($stockEntryData);

            $items = StockEntryDetail::query()->where('parent', $request->ste_no)->get();
            foreach ($items as $row) {
                if ($row->s_warehouse) {
                    $actualQty = Bin::query()
                        ->where('item_code', $row->item_code)
                        ->where('warehouse', $row->s_warehouse)
                        ->sum('actual_qty');

                    if ($row->qty > $actualQty) {
                        return ApiResponse::modal(false, 'Insufficient Stock', 'Insufficient stock for '.$row->item_code.' in '.$row->s_warehouse, 422);
                    }
                }
            }

            $this->submitStockEntry($request->ste_no);

            DB::commit();

            return ApiResponse::modal(true, 'Item Received', 'Item has been received.');
        } catch (Exception $e) {
            Log::error('MaterialTransferController submitInternalTransfer failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            DB::rollback();

            return ApiResponse::modal(false, 'Warning', 'There was a problem creating transaction.', 422);
        }
    }
}
