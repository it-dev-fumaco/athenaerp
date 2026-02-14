# Codebase Optimization Report

This document lists optimization opportunities found across the Laravel app, ordered by impact and effort.

---

## 1. N+1 queries (high impact)

### 1.1 `MainController::get_athena_logs` (lines ~1032–1037)

**Issue:** Inside a `foreach ($logs as $row)` a query runs per row:

```php
$existingReferenceNo = DB::table('tab' . $referenceType)->where('name', $row->reference_parent)->first();
```

**Fix:** Batch load references by type before the loop.

- Group logs by computed `referenceType` (e.g. `Packing Slip` vs `Stock Entry`).
- For each type, run one query: `DB::table('tab' . $type)->whereIn('name', $ids)->get()` and key by `name`.
- In the loop, use the preloaded map: `data_get($referencesByType, "{$referenceType}.{$row->reference_parent}")`.

**Impact:** 1 query per log → 1 query per distinct reference type (typically 1–3).

---

### 1.2 `MainController::update_production_order_items` (lines 1377–1406)

**Issue:** For each work order item, three queries run:

1. `StockEntry::query()->...->where('item_code', $row->item_code)->sum('qty')`
2. `DB::connection('mysql')->table(...)->...->where('sted.item_code', $row->item_code)->sum('sted.qty')`
3. `DB::table('tabWork Order Item')->...->update(...)`

**Fix:**

- Use `WorkOrderItem` model instead of `DB::table('tabWork Order Item')`.
- Precompute transferred and returned qty per item in one or two aggregate queries (e.g. `StockEntry` + `StockEntryDetail` grouped by `item_code`), then loop and update from the maps.

**Impact:** 3N queries → 2–3 queries total (N = number of work order items).

---

### 1.3 `TransactionController::submitPackingSlipIssue` (lines 384–402)

**Issue:** Inside `foreach ($packedItems as $packed)`, `getStockReservation()` is called per item, each doing a DB query.

**Fix:** Add a bulk helper (e.g. `getStockReservationBulk(array $itemWarehousePairs, $salesPerson, $project, ...)`) that loads all matching reservations in one query keyed by item+warehouse, then use the map in the loop.

**Impact:** N queries → 1 query for reservation lookup.

---

### 1.4 `MainController::feedback_production_order_items` (line ~1905)

**Issue:** Inside a loop over `$list`:

```php
$partNos = ItemSupplier::query()->where('parent', $d->item_code)->pluck('supplier_part_no');
```

**Fix:** Collect all `item_code` values, then `ItemSupplier::whereIn('parent', $itemCodes)->get()->groupBy('parent')`. In the loop, use `data_get($partNosByItem, $d->item_code, collect())->pluck('supplier_part_no')`.

**Impact:** N queries → 1 query.

---

## 2. Laravel / project conventions (medium impact, quick wins)

### 2.1 `isset()` / array access → `data_get()` / `Arr::get()`

**Rule:** Prefer `Arr::get()` or `data_get()` for safe array/object access with defaults (see `.cursor/rules/laravel-helpers.mdc`).

**Locations (examples):**

- `MainController`: 1548, 1604, 1814, 1816, 1888, 1908, 1931, 2331–2332, 2355–2357, 2731–2732, 2756–2757, 2766.
- `ConsignmentBeginningInventoryController`: 133, 209, 214, 220, 222, 244, 246, 252–253, 264, 278–279, 301, 305, 605, 607, 630, 661, 702, 704, 724, 787, 804.
- `ConsignmentController`, `ConsignmentPromodiserController`, `ConsignmentInventoryAuditController`, `ConsignmentStockAdjustmentController`, `ConsignmentStockTransferController`, `ConsignmentReplenishController`: multiple `isset($arr[$key])` and similar.
- `BrochureController`: 70, 363, 473, 480–481, 487, 493, 497, 526, 528–529, 573, 581–583, 707, 712.
- `EmailHR.php`, `UpdateStocks.php`: 100, 105, 67, 69.

**Example:**

```php
// Before
$itemImage = isset($itemImages[$a->item_code]) ? '/img/' . $itemImages[$a->item_code] : '/icon/no_img.webp';

// After
$itemImage = '/img/' . Arr::get($itemImages, $a->item_code, 'icon/no_img.webp');
// or ensure path: $itemImage = Arr::get($itemImages, $a->item_code) ? '/img/' . $itemImages[$a->item_code] : '/icon/no_img.webp';
```

---

### 2.2 Raw `DB::table()` where a model exists

**Locations:**

- `MainController::update_production_order_items`: `DB::table('tabWork Order Item')` → use `WorkOrderItem` model.
- `ProductionController`: `DB::table('tabWork Order Item')` in several places → use `WorkOrderItem`.
- `ItemProfileController`: `DB::table('tabWork Order Item as p')` → use `WorkOrderItem` with joins.
- `MainController::get_athena_logs`: `DB::table('tab' . $referenceType)` – table name is dynamic; batching (above) still applies; consider model per doctype if this grows.

Using models improves consistency, IDE support, and reuse of scopes/relationships.

---

### 2.3 `env()` in application code

**Rule:** Use `config()` instead of `env()` in app code (config is cached).  
**Status:** Only LDAP code uses `putenv()`; no direct `env()` in app code. Keep using config for any new settings.

---

## 3. Controller / architecture (medium impact, larger effort)

### 3.1 MainController size and responsibility

- **Current:** Very large controller with many domains (search, items, stock, deliveries, production, reports, guides).
- **Strategy:** See `.cursor/rules/main-controller-refactor.mdc`. Continue splitting by domain (GuideController, SearchController, ItemProfileController, MaterialTransferController, DeliveryController, ProductionController, etc.) and moving business logic to models/services.

### 3.2 Logic in controllers that belongs in models/services

- **Cutoff period / Promodiser dashboard:** Prefer `CutoffDateService` and model scopes over inline logic in `MainController::index`.
- **Stock reservation / available qty:** Already have `getAvailableQtyBulk`; add `getStockReservationBulk` and use in loops (see 1.3).
- **Production order item updates:** Consider a small service or `WorkOrder` model method that encapsulates “recompute transferred/returned and update work order items”.

---

## 4. Other improvements

### 4.1 Duplicate SalesOrder / MaterialRequest fetch

- **MainController (e.g. delivery modal):** `SalesOrder::query()->where('name', $q->sales_order)->first()` is called in multiple branches; fetch once and reuse.
- **MaterialTransferController (e.g. getSteDetails):** `SalesOrder::find($refNo)` and `MaterialRequest::find($refNo)` – ensure ref is not fetched twice when both are used.

### 4.2 Pagination in memory

- **MainController::getLowLevelStocks, getReservedItems:** Results are fetched, then paginated with `LengthAwarePaginator` on a collection. For large datasets, use the query builder’s `paginate()` (or cursor pagination) so the database does the paging.

### 4.3 Repeated base64 image conversion

- **getLowLevelStocks / getReservedItems:** `$this->base64Image($itemImage)` is called per row. If the same image appears many times, consider caching (e.g. by path or item_code) to avoid repeated file read and encoding.

---

## Recommended order of work

1. **Quick:** Replace `isset`/array access with `Arr::get`/`data_get` in a few high-traffic paths (e.g. MainController get_athena_logs, getReservedItems, getLowLevelStocks).
2. **High impact:** Fix N+1 in `get_athena_logs` (batch reference lookups).
3. **High impact:** Optimize `update_production_order_items` (batch queries + WorkOrderItem model).
4. **Medium:** Add `getStockReservationBulk` and use it in TransactionController and any similar loops.
5. **Medium:** Batch `ItemSupplier` in `feedback_production_order_items`.
6. **Ongoing:** Use WorkOrderItem model everywhere instead of `DB::table('tabWork Order Item')`.
7. **Larger:** Continue MainController split and move logic to models/services as in the refactor rule.

---

*Generated from a full-codebase review. Re-run checks after major changes.*
