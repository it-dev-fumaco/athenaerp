# Mobile responsiveness audit hit-list

Prioritized by user flows. Patterns: fixed widths, `overflow-x: hidden`, `table-layout: fixed`, inline `min-width`/`width` on modals and tables.

## Priority 1 – Global (affects all pages)

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/layout.blade.php` | Loader 150px; `#brochure-list-floater` 280px; `#suggesstion-box` 95%; `.col-md-13` 19%; modals `min-width: 90%`, `min-width: 30%` | Lines 52, 229–237, 507–514, 177–179, 712, 872, 962 |

## Priority 2 – Search (high-traffic)

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/search_results.blade.php` | `html, body { overflow-x: hidden !important }`; `.search-thumbnail` 200px; `.modal.left` 320px/240px | Lines 733–741, 793, 836, 1054 |

## Priority 3 – Item profile / details

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/item_profile.blade.php` | Th 350px/300px; input-group 120px; item alternatives min/max 406px | Lines 350–359, 388–490, 674–688, 726–774 |
| `resources/views/item_information.blade.php` | Modal `min-width: 30%` | Line 17 |
| `resources/views/tbl_item_details.blade.php` | Item alternatives 406px | Lines 364–365 |
| `resources/views/view_item_variants.blade.php` | Th 350px; form-group 100px; min-width 7rem | Lines 43–47, 64, 112 |
| `resources/views/update_item_form.blade.php` | Container `min-width: 80%` | Line 7 |

## Priority 4 – Consignment (table-heavy)

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/consignment/supervisor/view_stock_adjustments.blade.php` | `table-layout: fixed`; select 200px | 559, 380, 1020 |
| `resources/views/consignment/supervisor/view_stock_transfers.blade.php` | `table-layout: fixed` | 71 |
| `resources/views/consignment/supervisor/view_inventory_audit_items.blade.php` | Th 500px/100px; `table-layout: fixed` | 94–101, 355 |
| `resources/views/consignment/supervisor/adjust_stocks.blade.php` | `table-layout: fixed` | 149 |
| `resources/views/consignment/replenish_form.blade.php` | `table-layout: fixed` | 353 |
| `resources/views/consignment/replenish_index.blade.php` | `table-layout: fixed` | 46 |
| `resources/views/consignment/stock_transfer_form.blade.php` | `table-layout: fixed` | 214 |
| `resources/views/consignment/promodiser_warehouse_items.blade.php` | `table-layout: fixed` | 101 |
| `resources/views/consignment/promodiser_damage_report_form.blade.php` | `table-layout: fixed` | 158 |
| `resources/views/consignment/promodiser_damaged_list.blade.php` | `table-layout: fixed` | 179 |
| `resources/views/consignment/item_returns_form.blade.php` | `table-layout: fixed` | 181 |
| `resources/views/consignment/beginning_inventory.blade.php` | `table-layout: fixed` | 57 |
| `resources/views/consignment/supervisor/Import_tool/*` | td 400px/300px; modal min-width 70%; width 800px | tbl.blade.php 39–47; index 49, 250 |

## Priority 5 – Brochure

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/brochure/print_preview.blade.php` | Sidebar 280px; overflow-x hidden; 230px/430px/420px blocks | 34, 41, 96–144, 466–482 |
| `resources/views/brochure/form.blade.php` | width 500px/400px; overflow-x hidden | 38, 86, 116, 123 |
| `resources/views/brochure/preview_standard_brochure.blade.php` | 430px/420px; 230px | 169–185, 380–428 |
| `resources/views/brochure/preview_loop.blade.php` | 230px | 238–286 |
| `resources/views/brochure/pdf.blade.php` | 230px/240px | 92, 137 |

## Priority 6 – External reports

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/external_reports/sales_report_table.blade.php` | Table `width: 2790px`; col widths 100px/350px/60px | 166–186 (guard for `$exportExcel`) |

## Priority 7 – Other modals / forms

| File | Pattern | Location / notes |
|------|---------|------------------|
| `resources/views/picking_slip.blade.php` | modal-dialog min-width 35% | 126, 133 |
| `resources/views/returns.blade.php` | modal-dialog min-width 35% | 130, 136 |
| `resources/views/replacement.blade.php` | modal-dialog min-width 35% | 154 |
| `resources/views/production_to_receive.blade.php` | modal-dialog min-width 35% | 152 |
| `resources/views/material_transfer.blade.php` | modal-dialog min-width 35% | 144 |
| `resources/views/material_transfer_for_manufacture.blade.php` | modal-dialog min-width 35% | 159 |
| `resources/views/material_issue.blade.php` | modal-dialog min-width 35% | 141 |
| `resources/views/goods_in_transit_modal_content.blade.php` | modal-dialog min-width 35% | 8 |
| `resources/views/search_item_cost.blade.php` | div 250px | 16–51 |

## Vue components

| File | Pattern | Notes |
|------|---------|--------|
| `resources/js/components/StockReservationModals.vue` | Hard min/max-width | Audit inline styles |
| `resources/js/components/BrochureForm.vue` | Hard min/max-width | Audit inline styles |
