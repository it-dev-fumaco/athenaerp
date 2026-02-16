# ConsignmentController Refactoring

This document tracks the refactoring of the 5,830-line `ConsignmentController` into smaller, domain-focused controllers.

## Completed Extractions

### 1. ConsignmentReplenishController
**Location:** `app/Http/Controllers/Consignment/ConsignmentReplenishController.php`

**Methods:** ~10 methods, ~350 lines

| Method | Route | Notes |
|--------|-------|-------|
| `index` | `GET /consignment/replenish` | Main entry, delegates to Promodiser or Supervisor view |
| `indexPromodiser` | (internal) | Promodiser-specific list |
| `form` | `GET /consignment/replenish/form/{id?}` | Create/edit form |
| `submit` | `POST /consignment/replenish` | Create new replenishment |
| `update` | `POST /consignment/replenish/{id}` | Update existing |
| `delete` | `GET /consignment/replenish/{id}/delete` | Delete |
| `approve` | `POST /consignment/replenish/{id}/approve` | Approve replenishment |
| `modalContents` | `GET /consignment/replenish/modal/{id}` | Modal content |
| `editConsignmentOrder` | `GET /consignment_order/{id}/edit` | Edit consignment order |
| `updateConsignmentOrder` | `POST /consignment_order/{id}/update` | Update consignment order |

### 2. ConsignmentStockTransferController
**Location:** `app/Http/Controllers/Consignment/ConsignmentStockTransferController.php`

**Methods:** 9 methods, ~450 lines

| Method | Route | Notes |
|--------|-------|-------|
| `count` | `GET /countStockTransfer/{purpose}` | Pending count by purpose |
| `generateStockTransferEntry` | `POST /generate_stock_transfer_entry` | Create Stock Entry from CSE |
| `report` | `GET /stocks_report/list` | Supervisor stock transfer report |
| `submit` | `POST /stock_transfer/submit` | Submit stock transfer |
| `form` | `GET /stock_transfer/form` | Stock transfer form |
| `itemReturnForm` | `GET /item_return/form` | Item return form |
| `itemReturnSubmit` | `POST /item_return/submit` | Submit item return |
| `cancel` | `GET /stock_transfer/cancel/{id}` | Cancel stock transfer |
| `list` | `GET /stock_transfer/list` | Stock transfer list |

### 3. ConsignmentSalesController
**Location:** `app/Http/Controllers/Consignment/ConsignmentSalesController.php`

**Methods:** 5 methods, ~180 lines

| Method | Route | Notes |
|--------|-------|-------|
| `viewSalesReportList` | `GET /sales_report_list/{branch}` | Sales report list by branch |
| `salesReportDeadline` | `GET /sales_report_deadline` | Cutoff deadline info |
| `viewMonthlySalesForm` | `GET /view_monthly_sales_form/{branch}/{date}` | Monthly sales form |
| `submitMonthlySaleForm` | `POST /submit_monthly_sales_form` | Submit sales report |
| `salesReport` | `GET /consignment_sales_report` | Supervisor sales report |

### 4. ConsignmentInventoryAuditController
**Location:** `app/Http/Controllers/Consignment/ConsignmentInventoryAuditController.php`

**Methods:** 6 methods, ~640 lines

| Method | Route | Notes |
|--------|-------|-------|
| `viewInventoryAuditForm` | `GET /view_inventory_audit_form/{branch}/{transaction_date}` | Form for promodiser |
| `submitInventoryAuditForm` | `POST /submit_inventory_audit_form` | Submit audit report |
| `viewInventoryAuditList` | `GET /inventory_audit` | List (promodiser or supervisor) |
| `getSubmittedInvAudit` | `GET /submitted_inventory_audit` | Submitted reports table |
| `viewInventoryAuditItems` | `GET /view_inventory_audit_items/{branch}/{from}/{to}` | Audit items detail |
| `getPendingSubmissionInventoryAudit` | `GET /pending_submission_inventory_audit` | Pending table (supervisor) |

### 5. ConsignmentStockAdjustmentController
**Location:** `app/Http/Controllers/Consignment/ConsignmentStockAdjustmentController.php`

**Methods:** 5 methods, ~350 lines

| Method | Route | Notes |
|--------|-------|-------|
| `cancelStockAdjustment` | `GET /cancel_stock_adjustment/{id}` | Cancel CSA record |
| `viewStockAdjustmentHistory` | `GET /stock_adjustment_history` | History list |
| `viewStockAdjustmentForm` | `GET /stock_adjustment_form` | Adjust stocks form |
| `adjustStocks` | `POST /adjust_stocks` | Create Consignment Stock Adjustment |
| `submitStockAdjustment` | `POST /stock_adjust/submit/{id}` | Adjust Beginning Inventory stocks |

### 6. ConsignmentBeginningInventoryController
**Location:** `app/Http/Controllers/Consignment/ConsignmentBeginningInventoryController.php`

**Methods:** 12 methods, ~570 lines

| Method | Route | Notes |
|--------|-------|-------|
| `checkBeginningInventory` | `GET /validate_beginning_inventory` | Validation helper |
| `beginningInventoryApproval` | `GET /beginning_inv_list` | Supervisor approval / Promodiser list |
| `approveBeginningInventory` | `POST /approve_beginning_inv/{id}` | Approve/edit BI |
| `cancelApprovedBeginningInventory` | `GET /cancel/approved_beginning_inv/{id}` | Cancel approved BI |
| `beginningInventoryList` | `GET /beginning_inventory_list` | Promodiser list |
| `beginningInventory` | `GET /beginning_inventory/{inv?}` | Create/edit form |
| `getItems` | `GET /get_items/{branch}` | Item search (shared: stock adjustments, import tool) |
| `beginningInvItems` | `GET /beginning_inv_items/{action}/{branch}/{id?}` | Items for create/update |
| `saveBeginningInventory` | `POST /save_beginning_inventory` | Create new BI |
| `cancelDraftBeginningInventory` | `GET /cancel_beginning_inventory/{id}` | Cancel draft |
| `updateDraftBeginningInventory` | `POST /update_beginning_inventory/{id}` | Update draft |
| `getReceivedItems` | `GET /beginning_inv/get_received_items/{branch}` | Received items for select |

### 7. ConsignmentPromodiserController
**Location:** `app/Http/Controllers/Consignment/ConsignmentPromodiserController.php`

**Methods:** 20 methods, ~1050 lines

| Method | Route | Notes |
|--------|-------|-------|
| `promodiserDeliveryReport` | `GET /promodiser/delivery_report/{type}` | Delivery report (promodiser) |
| `promodiserInquireDelivery` | `GET /promodiser/inquire_delivery` | Inquire delivery |
| `promodiserReceiveDelivery` | `GET /promodiser/receive/{id}` | Receive delivery |
| `promodiserCancelReceivedDelivery` | `GET /promodiser/cancel/received/{id}` | Cancel received |
| `pendingToReceive` | `GET /consignment/pending_to_receive` | Redirect to view deliveries |
| `viewDeliveries` | `GET /view_consignment_deliveries` | Supervisor deliveries list |
| `promodiserDamageForm` | `GET /promodiser/damage_report/form` | Damage report form |
| `submitDamagedItem` | `POST /promodiser/damage_report/submit` | Submit damage |
| `damagedItems` | `GET /damage_report/list` | Promodiser damaged list |
| `viewDamagedItemsList` | `GET /damaged_items_list` | Supervisor damaged list |
| `returnDamagedItem` | `GET /damaged/return/{id}` | Return damaged to quarantine |
| `getConsignmentWarehouses` | `GET /get_consignment_warehouses` | Warehouse select |
| `viewSalesReport` | `GET /view_sales_report` | Product sold list |
| `activityLogs` | `GET /get_activity_logs` | Activity logs table |
| `viewPromodisersList` | `GET /view_promodisers` | Promodiser management |
| `addPromodiserForm` | `GET /add_promodiser` | Add promodiser form |
| `addPromodiser` | `POST /add_promodiser_submit` | Add promodiser |
| `editPromodiserForm` | `GET /edit_promodiser/{id}` | Edit form |
| `editPromodiser` | `POST /edit_promodiser_submit/{id}` | Edit promodiser |
| `getAuditDeliveries` | `GET /get_audit_deliveries` | Audit deliveries table |
| `getAuditReturns` | `GET /get_audit_returns` | Audit returns table |

## Remaining in ConsignmentController

The following domains are still in `ConsignmentController`:

| Domain | Methods | Est. Lines |
|--------|---------|------------|
| **Misc / Shared** | ~14 methods | ~400 |

## Suggested Next Steps

1. Remaining misc methods (import tool, consignment ledger, etc.)

## Shared Dependencies

- **CutoffDateService** – Used by `getCutoffDate()` in ConsignmentController; inject via `app(CutoffDateService::class)`
- **GeneralTrait** – `getAvailableQtyBulk()`, `sendMail()`, `revertChanges()`
- **ERPTrait** – `erpCall()`, `erpPut()`, `erpPost()`, `erpDelete()`

## Route Migration Pattern

When extracting a controller:

1. Create new controller in `app/Http/Controllers/Consignment/`
2. Add `use App\Http\Controllers\Consignment\NewController;` to `routes/web.php`
3. Update routes from `[ConsignmentController::class, 'method']` to `[NewController::class, 'method']`
4. Remove extracted methods from ConsignmentController

## Testing

Run after each extraction:

```bash
php artisan test
php artisan route:list --path=consignment
```
