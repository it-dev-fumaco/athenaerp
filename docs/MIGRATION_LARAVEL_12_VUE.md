# AthenaERP Migration Plan: Laravel 12 + Vue.js

This document outlines the phased migration from **Laravel 10** to **Laravel 12** and the adoption of **Vue.js** as the frontend framework.

> **Note:** Phase 1 complete. Laravel 12, PHP 8.3, and Vite are in place. Phase 2 (Vue.js setup) is next.

---

## 1. Current State Summary

| Component | Current | Target |
|-----------|---------|--------|
| **Laravel** | 12.x ✓ | 12.x |
| **PHP** | 8.2–8.3 ✓ | 8.3 recommended |
| **Build** | Vite ✓ | Vite |
| **Frontend** | Blade + jQuery | Vue 3 + Blade (incremental) or SPA |
| **Blade views** | ~152 | Migrate gradually |
| **Controllers** | ~20+ | Add API layer where needed |

---

## 2. Phase 1: Laravel 10 → 12 Upgrade (2–4 weeks)

Starting from Laravel 10 + PHP 8.3. Next steps: 10 → 11 → 12.

### 2.1 Prerequisites

- [x] Laravel 10 + PHP 8.3 (already in place)
- [x] Composer 2.x (used with Laravel 12)
- [ ] Backup database and codebase (do before deploy)
- [ ] Create migration branch: `upgrade/laravel-12` (optional, if not already on branch)

### 2.2 Step 1: Laravel 10 → 11 (1–1.5 weeks)

> **Done:** Laravel 11 upgrade completed. `composer.json` updated; `bootstrap/app.php` and `public/index.php` migrated to L11 style; custom middleware (`sanitation`, `checkConnection`) registered in bootstrap; routes loaded via `withRouting()`; rate limiting kept in `RouteServiceProvider`; tests passing.

**Tasks:**

1. Update `composer.json`:
   - `laravel/framework: ^11.0`
   - `laravel/sanctum: ^4.0` (check)
   - `phpunit/phpunit: ^11.0` if using

2. Run `composer update` and fix dependency conflicts.

3. Apply Laravel 11 structural changes:
   - Install `laravel/installer` and run `laravel new temp --no-install` to compare structure
   - Migrate to slim config: reduce `config/` files (remove `config/auth.php`, `config/cors.php` if using defaults)
   - Update `bootstrap/app.php` (Laravel 11 uses a new bootstrap structure)
   - Move middleware registration to `bootstrap/app.php` if using custom middleware

4. Update `app/Http/Kernel.php`:
   - Laravel 11 combines `Kernel.php` logic into `bootstrap/app.php`; migrate middleware there

5. Update third-party packages:
   - `barryvdh/laravel-dompdf`: ensure ^3.0 for Laravel 11
   - `phpoffice/phpspreadsheet`: ^1.29+
   - `buglinjo/laravel-webp`: check compatibility

6. Run tests and fix any breaking changes.

### 2.3 Step 2: Laravel 11 → 12 (0.5–1 week)

> **Done:** Laravel 12 in place. `composer.json` has `laravel/framework: ^12.0`, `phpunit/phpunit: ^11.0`; Carbon 3 and strict types addressed as needed.

**Tasks:**

1. Update `composer.json`:
   - `laravel/framework: ^12.0`
   - `phpunit/phpunit: ^11.0` (Laravel 12 requirement)
   - `nesbot/carbon: ^3.0` (Carbon 2.x removed)

2. Resolve Carbon 3.x breaking changes:
   - Search for `Carbon::` usage; replace deprecated methods
   - Ensure `now()` is used where appropriate per project rules

3. Fix strict type issues:
   - Child classes must match parent method signatures
   - Review custom services, policies, and models for return type mismatches

4. `DatabaseTokenRepository` (if used):
   - Constructor now expects `$expires` in **seconds**, not minutes

5. Run full test suite and manual smoke tests.

### 2.4 Step 3: Laravel Mix → Vite (0.5–1 week)

> **Done:** Vite configured. `vite.config.js` added; `package.json` scripts use `vite` / `vite build`; layout uses `@vite(['resources/css/app.css', 'resources/js/app.js'])`; `resources/js/bootstrap.js` and `app.js` converted to ESM; `webpack.mix.js` and `laravel-mix` removed.

**Tasks:**

1. Install Vite and Laravel plugin:
   ```bash
   npm install --save-dev vite laravel-vite-plugin
   ```

2. Create `vite.config.js`:
   ```js
   import { defineConfig } from 'vite';
   import laravel from 'laravel-vite-plugin';

   export default defineConfig({
       plugins: [
           laravel({
               input: ['resources/css/app.css', 'resources/js/app.js'],
               refresh: true,
           }),
       ],
   });
   ```

3. Update `package.json`:
   - Replace `laravel-mix` scripts with Vite:
   ```json
   "scripts": {
       "dev": "vite",
       "build": "vite build",
       "preview": "vite preview"
   }
   ```

4. Update Blade layouts:
   - Replace `mix()` / `@mix` with `@vite(['resources/css/app.css', 'resources/js/app.js'])`

5. Update `resources/js/app.js`:
   - Remove Webpack-specific `require()`; use ES modules
   - Temporarily keep jQuery if needed for existing pages

6. Remove `webpack.mix.js`, `laravel-mix` from `package.json`.

7. Run `npm run build` and verify assets load.

### 2.5 Phase 1 Checklist

- [x] Laravel 11 upgrade complete
- [x] Laravel 12 upgrade complete
- [x] Vite configured and working
- [x] All tests passing (`php artisan test`) – 5 passed, 1 skipped (ItemSearchServiceTest needs DB/auth)
- [x] npm build verified (`npm install` and `npm run build` completed with no errors)
- [ ] Manual smoke test of critical flows
- [ ] Staging deploy successful

**Phase 1 (Laravel 12 + Vite) is complete.** Run the validation steps below, then proceed to Phase 2 (Vue.js setup) when ready.

### 2.6 Phase 1 Validation (run before Phase 2)

Do this on your machine to confirm Phase 1 is solid:

1. **Frontend build**
   ```bash
   npm install
   npm run build
   ```
   (Or `npm run dev` and leave it running.) Confirm no errors.

2. **Tests**
   ```bash
   php artisan test
   ```
   Expect: 5 passed, 1 skipped (ItemSearchServiceTest).

3. **Manual checks**
   - Open the app in the browser; confirm the page loads (no 404s for Vite assets in Network tab).
   - Login.
   - One consignment flow (e.g. list stores or view inventory).
   - One brochure action (e.g. open brochure form or history).

4. **Vite assets**
   - With `npm run build`: load the app and check that `/build/assets/*.js` and `*.css` return 200 (not 404).

---

## 3. Phase 2: Vue.js Setup (1–2 weeks)

### 3.1 Install Vue 3 + Tooling

```bash
npm install vue@^3 @vitejs/plugin-vue
npm install vue-router@^4 pinia  # if building SPA or using state
```

### 3.2 Vite + Vue Config

Update `vite.config.js`:

```js
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

### 3.3 Entry Point

Create `resources/js/app.js`:

```js
import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';

// Mount only where #app exists (Blade pages)
const app = document.getElementById('app');
if (app) {
    createApp(App).mount('#app');
}
```

### 3.4 Strategy: Incremental vs SPA

| Approach | When to Use | Effort |
|----------|--------------|--------|
| **Incremental (Vue in Blade)** | Migrate page by page, keep Blade layouts | 3–4 months |
| **Inertia.js** | Want Vue with Laravel routing, no full API | 3–4 months |
| **Full SPA** | Need complete client-side app | 5–6 months |

**Recommended:** Start with **Incremental**. Add Vue components only on pages being migrated; keep Blade for others.

### 3.5 Phase 2 Checklist

- [x] Vue 3 + @vitejs/plugin-vue installed
- [x] vue-router@^4 and pinia installed (for SPA/state when needed)
- [x] Vite config: vue plugin + `@` alias to `resources/js`
- [x] `resources/js/App.vue` root component created
- [x] `resources/js/app.js` mounts Vue only when `#app` exists
- [x] Run `npm install` then `npm run build` to verify (no errors)
- [x] Add a Blade page with `<div id="app"></div>` to test Vue mount (done: dashboard `resources/views/index.blade.php`)

**Phase 2 (Vue.js setup) is complete.** Vue mounts on the dashboard; you should see the placeholder message "Vue 3 mounted. Add page-specific components when migrating a Blade page." Next: Phase 3 (incremental Vue migration).

---

## 4. Phase 3: Incremental Vue Migration (3–4 months)

### 4.0 Phase 3 First Page (Dashboard stats) – Done

- [x] **Dashboard (index):** Stats cards (Check In / Check Out counts) migrated to Vue
  - `resources/js/components/DashboardStats.vue` – fetches `/dashboard_data`, `/count_ste_for_issue/*`, `/count_production_to_receive`, `/count_ps_for_issue` via axios and renders the 8 count cards
  - `App.vue` renders `DashboardStats` when `data-page="dashboard"` on `#app`
  - `resources/views/index.blade.php` – stats row moved into `#app`; jQuery count calls removed (tables/tabs still use jQuery)
- [x] **Dashboard: Inventory Accuracy card** migrated to Vue
  - `resources/js/components/DashboardInventoryAccuracy.vue` – fetches `/inv_accuracy/{year}` via axios; month/year selects and table with Classification, Warehouse, Accuracy, Target; mounted on `#dashboard-inv-accuracy`
  - `app.js` mounts `DashboardInventoryAccuracy` when `#dashboard-inv-accuracy` exists; jQuery `monthlyInvAccuracyTbl` and `.filter-inv-accuracy` handler removed
- [x] **Dashboard: remaining sections** – all data-driven areas now Vue
  - **Stock Level Alert** – `DashboardLowStock.vue` on `#dashboard-low-stock`; fetches `/get_low_stock_level_items?page=` (HTML), pagination link clicks intercepted and refetched
  - **Stock Movement(s) / Athena logs** – `DashboardAthenaLogs.vue` on `#dashboard-athena-logs`; month list + table from `/get_athena_logs?month=` (HTML)
  - **Recently Received Item(s)** – `DashboardRecentlyReceived.vue` on `#dashboard-recently-received`; fetches `/recently_received_items` (HTML)
  - **Reserved Items** – `DashboardReservedItems.vue` on `#dashboard-reserved-items`; fetches `/get_reserved_items?page=` (HTML), pagination intercepted
  - All dashboard jQuery for these sections removed; only `showNotification` kept for use elsewhere if needed
- [x] **Dashboard migration complete.** Next: migrate other pages (Consignment, Brochure, Item management) per §4.1
- [x] **Consignment: Replenishment list (Promodiser)** – Consignment Orders page migrated to Vue
  - `resources/js/components/ConsignmentReplenishTable.vue` – search, branch/status filters, “Advanced Filters” toggle; fetches `/consignment/replenish?page=&branch=&status=&search=` (HTML), pagination link clicks intercepted
  - `resources/views/consignment/replenish_index.blade.php` – filters + table replaced with `<div id="consignment-replenish" data-stores="..." data-statuses="...">`; jQuery removed
  - `app.js` mounts `ConsignmentReplenishTable` when `#consignment-replenish` exists
- [x] **Consignment: Supervisor Consignment Order list** – same component, different mount
  - `ConsignmentReplenishTable.vue` reads from `#consignment-replenish` or `#consignment-orders-supervisor`; backend returns supervisor table HTML for non-Promodiser users
  - `resources/views/consignment/supervisor/consignment_order_index.blade.php` – filters + list replaced with `<div id="consignment-orders-supervisor" data-stores="..." data-statuses="...">`; jQuery load/pagination/filters removed; only `showNotification` and session flash kept
  - `app.js` mounts `ConsignmentReplenishTable` when `#consignment-orders-supervisor` exists
- [x] **Consignment: Stock Transfers list (Promodiser)** – tabs + table migrated to Vue
  - `resources/js/components/ConsignmentStockTransferList.vue` – three tabs (Store-to-Store Transfer, Item Pull Out, Item Return); fetches `/stock_transfer/list?page=&purpose=` (HTML), pagination link clicks intercepted
  - `resources/views/consignment/stock_transfers_list.blade.php` – nav pills + tab panes replaced with `<div id="consignment-stock-transfer-list">`; all jQuery (loadTable, nav-trigger, pagination, show-more) removed; Create dropdown and session flash kept
  - `app.js` mounts `ConsignmentStockTransferList` when `#consignment-stock-transfer-list` exists

**Phase 3 complete.** Dashboard and core Consignment list pages (replenish, supervisor orders, stock transfers) are migrated to Vue. Remaining work is documented below as Phase 4.

### 4.0.1 Phase 4 – Remaining / Deferred

Migrate when ready; follow the same pattern (mount point, Vue component, axios fetch HTML or JSON, remove jQuery).

- [x] **Consignment: Beginning inventory list** (`/beginning_inv_list`, Promodiser) – migrated to Vue
  - Controller: when `$request->ajax()`, return `consignment.partials.beginning_inventory_table` (table + pagination); partial uses `$inv['branch_warehouse'] ?? $inv['branch']` for store label; modal item search uses classes `.item-search-input` and `.items-table-in-modal` to avoid duplicate IDs
  - `resources/js/components/ConsignmentBeginningInventoryList.vue` – filters (search, store, date range text), fetches `/beginning_inv_list?search=&store=&date=&page=` (HTML), intercepts pagination links
  - `resources/views/consignment/beginning_inventory_list.blade.php` – accordion + form + table replaced with `<div id="consignment-beginning-inventory-list" data-stores="..." data-earliest-date="...">`; minimal Blade script keeps delegated handlers for `.allow-edit`, `.update-btn`, `.item-search-input` (filter rows in same modal), `.show-more`
  - `app.js` mounts `ConsignmentBeginningInventoryList` when `#consignment-beginning-inventory-list` exists
- [x] **Consignment: Supervisor stock transfers report** (`view_stock_transfers`) – migrated to Vue
  - 4 tabs: Store Transfer, Pull Out, Item Return, Damaged Items; each tab has filter form (search, source/target warehouse where applicable, status); warehouse options from `/consignment_stores` (JSON); table HTML from `/stocks_report/list?purpose=&page=&...` (first 3 tabs) or `/damaged_items_list?page=&search=&store=` (Damaged Items); badge counts from `/countStockTransfer/{purpose}` (Store Transfer, Pull Out, Item Return)
  - `resources/js/components/SupervisorStockTransferReport.vue` – tabs, filter forms with native selects (store options fetched on mount), load report HTML, intercept pagination (#consignment-stock-entry-pagination, #damaged-items-pagination)
  - `resources/views/consignment/supervisor/view_stock_transfers.blade.php` – tabs + filter forms + content divs replaced with `<div id="supervisor-stock-transfer-report"></div>`; success modal kept; minimal Blade script keeps delegated `.generate-stock-entry-form` submit and `#success-modal-btn` close
  - `app.js` mounts `SupervisorStockTransferReport` when `#supervisor-stock-transfer-report` exists
- [x] **Brochure module (form page – history sidebar)** – partially migrated
  - Form page (`/brochure`) is standalone (no layout). Vite added: `@vite(['resources/js/brochure.js'])`; CSRF meta added for axios; `vite.config.js` input includes `resources/js/brochure.js`
  - **Recently Uploads sidebar** migrated to Vue: `resources/js/components/BrochureHistory.vue` – search input (debounced), fetches GET `/brochure?search=` (HTML), injects into sidebar via `v-html`; `resources/js/brochure.js` mounts `BrochureHistory` when `#brochure-sidebar` exists
  - `resources/views/brochure/form.blade.php` – sidebar content replaced with `<div id="brochure-sidebar"></div>`; jQuery `load_history` and `#history-search` keyup removed; upload form, modal, and form submit remain jQuery
- [x] **Consignment: Supervisor inventory audit list** (`/inventory_audit`, view_inventory_audit) – migrated to Vue
  - Two panels: left (stats + submitted audit list with filters store/year/promodiser, pagination); right (pending for submission with store filter). Endpoints: `/submitted_inventory_audit?page=&store=&year=&promodiser=`, `/pending_submission_inventory_audit?page=&store=`; store options from `/consignment_stores`
  - `resources/js/components/SupervisorInventoryAuditList.vue` – reads `data-displayed-data`, `data-select-year`, `data-promodisers` from mount el; stats, filter forms with native selects, load submitted/pending HTML, intercept #inventory-audit-history-pagination
  - `resources/views/consignment/supervisor/view_inventory_audit.blade.php` – card body content replaced with `<div id="supervisor-inventory-audit-list" data-displayed-data="..." data-select-year="..." data-promodisers="...">`; all jQuery (Select2, load functions, pagination) removed
  - `app.js` mounts `SupervisorInventoryAuditList` when `#supervisor-inventory-audit-list` exists
- [x] **Consignment: Sales report list** (`/sales_report_list/{branch}`, Promodiser) – migrated to Vue
  - Branch name, year select; table HTML from GET `/sales_report_list/{branch}?year=` (returns `consignment.tbl_sales_report` when `$request->ajax()`)
  - `resources/js/components/SalesReportList.vue` – reads `data-branch`, `data-years`, `data-current-year` from mount el; year select, load report HTML on change and mount
  - `resources/views/consignment/view_sales_report_list.blade.php` – card body content replaced with `<div id="sales-report-list" data-branch="..." data-years="..." data-current-year="...">`; session flash kept; jQuery removed
  - `app.js` mounts `SalesReportList` when `#sales-report-list` exists

**Phase 4 complete.** Phase 5 (Brochure form + modal) complete. Remaining items deferred.

- [x] **Brochure module (form + modal)** – Form submit and modal product list migrated to Vue (Phase 5).
  - `resources/js/components/BrochureForm.vue` – file input, Upload (POST `/read_file` with `is_readonly`, show modal with HTML), Generate Brochure (POST `/read_file`, redirect to preview URL); Bootstrap modal; axios; CSRF/template URL from mount el
  - `resources/views/brochure/form.blade.php` – form/modal replaced with `<div id="brochure-form-app" data-csrf="..." data-template-url="...">`; jQuery form/modal script removed; Bootstrap bundle kept for modal
  - `resources/js/brochure.js` – mounts `BrochureForm` on `#brochure-form-app` and `BrochureHistory` on `#brochure-sidebar`
  - Preview/print pages remain Blade for now.
**Phase 6 – Backlog** (below). Dashboard “Reserved Items” widget is already Vue: `DashboardReservedItems.vue` on `#dashboard-reserved-items` (GET `/get_reserved_items`, pagination via delegated click). Layout still has jQuery for stock reservation create/edit/cancel modals and `get_reserved_items` targeting `#reserved-items-div` (legacy if only dashboard uses reserved items).

### 4.1 Priority Order (Phase 4 & 5 – completed)

1. **Consignment module** ✓ (Phase 4 completed)
   - Beginning inventory list, Supervisor stock transfers report, Supervisor inventory audit list, Sales report list

2. **Brochure module** ✓ (Phase 5 completed – form, modal, history sidebar; preview/print remain Blade)

3. **Item management** → Phase 6 backlog (see 4.1.2)

4. **Stock reservation & reports** → Phase 6 backlog (see 4.1.2)

### 4.1.1 Phase 5 – Completed

Brochure form submit + modal migrated: Vue `BrochureForm.vue`, axios POST `/read_file`, Bootstrap modal, jQuery removed from form page.

### 4.1.2 Phase 6 – Backlog (finish deferred)

Tackle in this order. Same pattern: mount point, Vue component, axios for HTML/JSON, remove jQuery from that page.

**1. Item management**

| Area | Routes / endpoints | Main views | Notes |
|------|--------------------|------------|--------|
| **Search results** | GET `/search_results`, `/search_results_images` | `search_results.blade.php` | ✓ **JSON API + Vue:** `SearchController::searchResults` returns JSON when `expectsJson()` (data, meta, bundled_items, show_price). `SearchResultsList.vue` mounts inside `#search-results-app`; list wrapped in app; fetches with `Accept: application/json`, renders list and pagination from `apiData`; initial server-rendered list captured in `window.__SEARCH_RESULTS_INITIAL_HTML__` for first paint. |
| **Item profile** | GET `/get_item_details/{item_code}`, `/get_stock_reservation/{item_code?}`; GET `/get_athena_transactions/{item_code}`; etc. | `item_profile.blade.php` | **All data tabs migrated to Vue.** Stock Reservations: JSON API + Vue (see below). Create/Edit/Cancel reservation: fully Vue modals with native selects and `input type="date"` (Select2/datepicker removed). |
| **Item attributes (update app)** | GET `/search` (itemAttributeSearch), `/update_form`, `/add_form/{item_code}`; POST `/update_attribute`, `/insert_attribute` | `item_attributes_updating/*` (standalone with update_login) | **Vue:** `ItemAttributeSearch.vue` (mount `#item-attribute-search-app`) intercepts search form, fetches `/search?item_code=`, replaces `#item-attribute-search-results`. `ItemAttributeUpdateForm.vue` (mount `#item-attribute-update-form-app`) intercepts `#updateForm`, POST `/update_attribute` via axios (Accept: application/json); backend returns JSON when expectsJson(). Add form (chunked submit) remains jQuery. Layout uses @vite. |

**2. Stock reservation**

| Area | Routes / endpoints | Where used | Notes |
|------|--------------------|------------|--------|
| **Reserved items list** | GET `/get_reserved_items` | Dashboard: already Vue (`DashboardReservedItems.vue`). Layout script still references `#reserved-items-div` (legacy). | Remove or align layout `get_reserved_items` / `#reserved-items-div` if unused. |
| **Stock reservation list (per item)** | GET `/get_stock_reservation/{item_code?}` | `item_profile.blade.php` (tabs: Web, Consignment, In-house, Pending) | ✓ **JSON API + Vue:** `StockReservationController::getStockReservation` returns JSON when `expectsJson()` (web, consignment, inhouse, pending with data + meta per section). `ItemProfileStockReservation.vue` fetches with `Accept: application/json`, renders four sections with tables and per-section pagination; Edit/Cancel buttons still open Vue modals. |
| **Create / Edit / Cancel reservation** | POST `/create_reservation`, `/update_reservation`, `/cancel_reservation`; GET `/get_item_details/{code}`, `/get_stock_reservation_details/{id}`, `/get_available_qty/` | Rendered by Vue | **Fully Vue:** `StockReservationModals.vue` **owns** the three modals (add, edit, cancel). Native `<select>` with options from `/warehouses_with_stocks`, `/sales_persons`, `/projects`, `/consignment_warehouses`; `<input type="date">` for valid_until. Layout: modal HTML and all Select2/datepicker inits for those modals **removed**. |

**3. Other reports / pages**

- External reports: `external_reports/sales_report.blade.php`, `sales_report_table.blade.php`
- **Low-level stocks:** Layout’s `get_low_stock_level_items()` and `#low-level-stocks-pagination` click handler removed. Create-MR success now dispatches `low-level-stocks-refresh`; `DashboardLowStock.vue` listens and reloads current page. Table load and pagination remain in Vue (dashboard).
- Other tables/partials: material transfer, deliveries, etc. Migrate when touching those pages.

**Phase 6 – Completed (item profile tabs + layout cleanup)**

1. **Quick win:** ✓ Removed dead `get_reserved_items` and `#reserved-items-div` from `layout.blade.php`; removed delegated `#reserved-items-pagination` handler. Removed layout’s four stock-reservation pagination handlers (`#stock-reservations-pagination-1/2/3`, `#pending-arr-pagination`) so item-profile Vue owns that content.
2. **Stock reservation list (item profile):** ✓ `ItemProfileStockReservation.vue` – mount `#item-profile-stock-reservation`, GET `/get_stock_reservation/{item_code}`, delegated pagination, `item-profile-stock-reservation-refresh` after create/edit/cancel. Update/Cancel still open layout modals.
3. **Athena Transactions tab (item profile):** ✓ `ItemProfileAthenaTransactions.vue` – mount `#item-profile-athena-transactions`, GET `/get_athena_transactions/{item_code}` with filters; delegated pagination; `item-profile-athena-transactions-refresh`. Layout: removed `get_athena_transactions()` and `#athena-transactions-pagination`; filter/date handlers dispatch refresh.
4. **Stock Ledger tab (item profile):** ✓ `ItemProfileStockLedger.vue` – mount `#item-profile-stock-ledger`, GET `/get_stock_ledger/{item_code}` with wh_user, erp_wh, erp_d from DOM; delegated pagination; `item-profile-stock-ledger-refresh`. item_profile: removed `get_stock_ledger()`, filter/date/erpReset dispatch refresh.
5. **Purchase History tab (item profile):** ✓ `ItemProfilePurchaseHistory.vue` – mount `#item-profile-purchase-history` (Manager/Director only), GET `/purchase_rate_history/{item_code}`; delegated pagination; `item-profile-purchase-history-refresh`.
6. **Consignment Stock Movement tab (item profile):** ✓ `ItemProfileConsignmentStockMovement.vue` – mount `#item-profile-consignment-stock-movement`, GET `/consignment_stock_movement/{item_code}` with branch_warehouse, date_range, user from DOM; delegated pagination; `item-profile-consignment-stock-movement-refresh`. item_profile: removed `load()`, filter/date/user/reset dispatch refresh.

**Item search – JSON API + Vue**

- **SearchController::searchResults** – When `$request->expectsJson()`, returns JSON: `data` (itemList), `meta` (current_page, last_page, total, path), `bundled_items`, `show_price`.
- **SearchResultsList.vue** – Mounts on `#search-results-app`; `#search-results-list` is **inside** the app. On load, initial server-rendered list HTML is captured in `window.__SEARCH_RESULTS_INITIAL_HTML__`. On form submit or pagination/filter link click: fetch URL with `Accept: application/json`, set `apiData`, render list and pagination from JSON; else show `initialHtml`. Search results page is now a **full page driven by Vue** when user searches or paginates.

**Phase 6 – Completed (all four items) + JSON API refactor**

- **Item attributes app** – Vue: search (form intercept + replace results), update form (axios POST with JSON response). Add form remains jQuery (chunked submit). Layout uses @vite.
- **Reservation modals** – Fully Vue: modals **rendered inside** `StockReservationModals.vue`; native selects (options from APIs) and `input type="date"`; layout modal HTML and Select2/datepicker inits **removed**.
- **Stock reservation list (item profile)** – JSON API: `getStockReservation` returns JSON; `ItemProfileStockReservation.vue` renders four sections from API data with pagination.
- **Search results** – JSON API + Vue; full page list and pagination from JSON; counts as **one more full page migrated**.
- **Shared components** – `DataTable`, `Modal`, `SelectFilter` in `resources/js/components/shared/`; `ExampleComponent.vue` uses Modal and DataTable.

### 4.2 Per-Page Migration Steps
1. Identify Blade view and its AJAX endpoints
2. Add a Vue root `#app` (or page-specific mount point) in the Blade template
3. Create Vue component(s) for the interactive parts
4. Replace jQuery AJAX with `axios` or `fetch` calls
5. Remove jQuery from that page
6. Test and commit

### 4.3 Shared Components (Built)

- **DataTable** (`resources/js/components/shared/DataTable.vue`) – reusable table with columns, data, loading, pagination; emits `page-change`, `sort`; optional `#cell` slot per column.
- **Modal** (`resources/js/components/shared/Modal.vue`) – modal wrapper with `v-model`, `title`, `#default` and `#footer` slots; uses Teleport to body.
- **SelectFilter** (`resources/js/components/shared/SelectFilter.vue`) – dropdown with optional search; supports static `options` or `searchUrl` for AJAX; `v-model` and `placeholder`.
- `FormInput` – text, number, date inputs with validation (optional future).
- `Toast` / `Alert` – notifications (optional future).

Example usage: `ExampleComponent.vue` uses `SharedModal` and `SharedDataTable`.

### 4.4 API Layer

- Controllers that return Blade partials → add JSON alternatives or refactor to return JSON
- Use `ApiResponse` helper for consistent responses
- Consider `routes/api.php` for Vue-only endpoints

---

## 5. Best Next Plan (Post–Laravel 12 + Vue migration)

Now that Laravel 12 is in place and the main Vue migration (stock reservation, search results, reservation modals, shared components) is done, this is a **prioritized next plan** for the whole codebase.

### 5.1 Tier 1 – Stabilization & quality (do first)

| Priority | Action | Why |
|----------|--------|-----|
| **1** | **Manual smoke test** of critical flows (login, search, item profile + stock reservation add/edit/cancel, consignment lists, brochure upload). | Catch regressions before they reach production. |
| **2** | **Staging deploy** and sign-off. | Validate Laravel 12 + Vue stack in a real environment. |
| **3** | **Add/expand tests** for key flows: | Lock in behavior and speed up future refactors. |
| | - Feature test for search results (JSON response when `Accept: application/json`). | |
| | - Feature test for stock reservation list JSON (optional). | |
| | - Fix or remove skipped `ItemSearchServiceTest` (DB/auth) if possible. | |

### 5.2 Tier 2 – Consistency & reuse

| Priority | Action | Why |
|----------|--------|-----|
| **4** | **Use shared components** where they fit: use `DataTable.vue` in `ItemProfileStockReservation`, `SearchResultsList`, or dashboard list components for consistent tables and pagination. | Less duplicate table/pagination code; easier maintenance. |
| **5** | **JSON API for 1–2 more item-profile tabs** (e.g. Athena Transactions or Stock Ledger): add `expectsJson()` in controller, return `data` + `meta`, refactor the existing Vue component to render from JSON instead of HTML. | Aligns with stock reservation and search; enables DataTable and better loading states. |
| **6** | **Centralize notifications**: replace `window.showNotification` with a small Vue toast/alert component (or use shared `Modal` for confirmations) so all Vue and legacy code can trigger the same UX. | Cleaner than global jQuery-style notifications. |

### 5.3 Tier 3 – Further jQuery reduction

| Priority | Action | Why |
|----------|--------|-----|
| **7** | **Audit `layout.blade.php`**: remove dead jQuery (e.g. any remaining `#reserved-items-div` / `get_reserved_items` references, unused Select2/datepicker inits). Layout still has many `$()` usages; trim what’s no longer needed. | Smaller payload and fewer surprises. |
| **8** | **Migrate Item attributes add form** (chunked submit) to Vue when you next touch that flow. | Completes the item-attributes app in Vue. |
| **9** | **Migrate on touch**: external reports, material transfer, deliveries, etc. – apply the same pattern (mount point, Vue component, axios, remove jQuery for that page) when you work on those features. | Incremental progress without a big-bang rewrite. |

### 5.4 Tier 4 – Optional longer-term

| Option | Description |
|--------|-------------|
| **Inertia.js** | If you later want “full Vue pages” with Laravel routing and no separate API layer, consider Inertia (see §6). Best adopted for new sections or a dedicated area of the app. |
| **API versioning** | If you add mobile or external API consumers, introduce `routes/api.php` and versioned JSON endpoints; keep web and API responses consistent via `ApiResponse` (or similar). |
| **E2E tests** | Playwright or Cypress for critical user journeys (e.g. search → item profile → add reservation) to protect against layout and JS regressions. |

### 5.5 Suggested order for the next 2–4 weeks

1. Run **manual smoke tests** and fix any issues.
2. **Deploy to staging** and get sign-off.
3. Add **1–2 Feature tests** for search and/or stock reservation JSON.
4. **Refactor one list** (e.g. ItemProfileStockReservation or a dashboard list) to use **shared DataTable**.
5. **Remove dead jQuery** from layout and document what’s left for future sprints.

After that, pick from Tier 2–3 based on which flows you’ll be changing next (e.g. more JSON APIs if you’re improving item profile; more Vue pages if you’re improving reports).

---

## 6. Alternative: Inertia.js Path

If you prefer **Inertia.js** (Laravel routes + Vue, no full API):

1. Install Inertia:
   ```bash
   composer require inertiajs/inertia-laravel
   npm install @inertiajs/vue3
   ```

2. Replace Blade views with Inertia pages (Vue components)
3. Controllers return `Inertia::render('PageName', $props)` instead of `view()`
4. No need for a separate API layer; Inertia handles data passing

**Pros:** Faster than full SPA, keeps Laravel routing  
**Cons:** All migrated pages must be Inertia; mixed Blade + Inertia can be tricky

---

## 7. Milestones & Timeline

| Milestone | Target | Duration |
|-----------|--------|----------|
| **M1** | Laravel 12 + Vite working | 2–4 weeks |
| **M2** | Vue 3 setup, first component | 1 week |
| **M3** | Consignment replenishment in Vue | 3–4 weeks |
| **M4** | Consignment module complete | 6–8 weeks |
| **M5** | Brochure + Item modules | 4–6 weeks |
| **M6** | Remaining pages, jQuery removal | 4–6 weeks |

**Total (incremental):** ~4–5 months with 1 developer  
**With 2 devs:** ~3–4 months

---

## 8. Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Package incompatibility | Pin versions, test in staging first |
| Regression in critical flows | Automated tests, manual checklist |
| Mixed Blade + Vue bugs | Clear mount boundaries, minimal shared state |
| Timeline slip | Prioritize high-value pages; defer low-traffic views |

---

## 9. References

- [Laravel 11 Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [Laravel 12 Upgrade Guide](https://laravel.com/docs/12.x/upgrade)
- [Vite Laravel Integration](https://laravel.com/docs/12.x/vite)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js](https://inertiajs.com/) (optional path)
