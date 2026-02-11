<?php

use App\Http\Controllers\BrochureController;
use App\Http\Controllers\Consignment\ConsignmentBeginningInventoryController;
use App\Http\Controllers\Consignment\ConsignmentInventoryAuditController;
use App\Http\Controllers\Consignment\ConsignmentPromodiserController;
use App\Http\Controllers\Consignment\ConsignmentReplenishController;
use App\Http\Controllers\Consignment\ConsignmentSalesController;
use App\Http\Controllers\Consignment\ConsignmentStockAdjustmentController;
use App\Http\Controllers\Consignment\ConsignmentStockTransferController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\ItemAttributeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemProfileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\MaterialTransferController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SelectFilterController;
use App\Http\Controllers\StockReservationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |
 */

Route::group(['middleware' => ['sanitation', 'throttle:global']], function () {
    Route::get('/login', [LoginController::class, 'viewLogin'])->name('login');
    Route::get('/login_user', function () {
        return redirect('/login');
    });
    Route::post('/login_user', [LoginController::class, 'login']);

    Route::get('/update', [ItemAttributeController::class, 'updateLogin'])->name('update_login');
    Route::post('/U_login_user', [ItemAttributeController::class, 'login']);

    Route::group(['middleware' => 'auth'], function () {
        Route::get('/item_form/{item_code}', [ItemController::class, 'index']);
        Route::get('/item_attribute_values/{attributeName}', [ItemController::class, 'getAttributeValues']);
        Route::get('/item_attribute/{item_code}', [ItemController::class, 'getItemAttributes']);
        Route::post('/save_item_attribute', [ItemController::class, 'saveItemAttribute']);
        Route::post('/delete_item_attribute', [ItemController::class, 'deleteItemAttribute']);
        Route::post('/save_item_info', [ItemController::class, 'saveItem']);
        Route::post('/update_item_variant', [ItemController::class, 'updateItemVariant']);

        Route::get('/checkConnection', [MainController::class, 'checkConnection']);

        Route::post('/generate_sales_order', [ConsignmentController::class, 'createSalesOrder']);

        // ERP
        Route::get('/customers', [MainController::class, 'getCustomers']);
        Route::get('/erp_projects', [MainController::class, 'getErpProjects']);
        Route::get('/customer_address', [MainController::class, 'getCustomerAddress']);
        Route::get('/branch_warehouses', [MainController::class, 'getBranchWarehouses']);

        // standard product brochure printing
        Route::get('/generate_brochure', [BrochureController::class, 'generateBrochure']);
        Route::post('/upload_image_for_standard_brochure', [BrochureController::class, 'uploadImageForStandard']);
        Route::get('/get_item_attributes/{item_code}', [BrochureController::class, 'getItemAttributes']);
        Route::post('/update_brochure_attributes', [BrochureController::class, 'updateBrochureAttributes']);
        Route::post('/add_to_brochure_list', [BrochureController::class, 'addToBrochureList']);
        Route::get('/remove_from_brochure_list/{key}', [BrochureController::class, 'removeFromBrochureList']);
        Route::get('/count_brochures', [BrochureController::class, 'countBrochures']);
        Route::get('/generate_multiple_brochures', [BrochureController::class, 'generateMultipleBrochures']);

        // routes for item attribute updating
        Route::post('/update_attribute', [ItemAttributeController::class, 'itemAttributeUpdate']);
        Route::get('/search', [ItemAttributeController::class, 'itemAttributeSearch']);
        Route::get('/update_form', [ItemAttributeController::class, 'updateAttribForm']);
        Route::get('/add_form/{item_code}', [ItemAttributeController::class, 'addAttribForm']);
        Route::get('/attribute_dropdown', [ItemAttributeController::class, 'itemAttributeDropdown']);
        Route::post('/insert_attribute', [ItemAttributeController::class, 'itemAttributeInsert']);
        Route::get('/signout', [ItemAttributeController::class, 'signout']);
        Route::get('/getAttributes', [ItemAttributeController::class, 'getAttributes']);
        Route::get('/viewParentItemDetails', [ItemAttributeController::class, 'viewParentItemDetails']);
        Route::post('/deleteItemAttribute/{parentItemCode}', [ItemAttributeController::class, 'deleteItemAttribute']);
        Route::post('/updateParentItem/{item_code}', [ItemAttributeController::class, 'updateParentItem']);

        Route::get('/', [MainController::class, 'index']);
        Route::get('/search_results', [SearchController::class, 'searchResults']);
        Route::get('/search_results_images', [SearchController::class, 'searchResultsImages']);
        Route::get('/dashboard_data', [MainController::class, 'dashboardData']);
        Route::get('/import_from_ecommerce', [MainController::class, 'importFromEcommerce']);
        Route::post('/import_images', [MainController::class, 'importImages']);

        Route::get('/logout', [LoginController::class, 'logout']);

        Route::get('/material_issue', [MaterialTransferController::class, 'viewMaterialIssue']);
        Route::get('/material_transfer_for_manufacture', [MaterialTransferController::class, 'viewMaterialTransferForManufacture']);
        Route::get('/material_transfer', [MaterialTransferController::class, 'viewMaterialTransfer']);
        Route::post('/submit_internal_transfer', [MaterialTransferController::class, 'submitInternalTransfer']);
        Route::get('/picking_slip', [DeliveryController::class, 'viewPickingSlip']);
        Route::post('/checkout_picking_slip', [TransactionController::class, 'checkoutPickingSlip']);
        Route::get('/production_to_receive', [ProductionController::class, 'viewProductionToReceive']);
        Route::get('/recently_received_items', [MainController::class, 'recentlyReceivedItems']);

        Route::prefix('/in_transit')->group(function () {
            Route::get('/', [MainController::class, 'feedbackedInTransit']);
            Route::post('/receive/{id}', [MainController::class, 'receiveTransitStocks']);
            Route::post('/transfer/{id}', [MainController::class, 'transferTransitStocks']);
        });

        // Route::get('/cancel_transaction_modal', 'MainController@cancel_transaction_modal');
        Route::post('/cancel_transaction', [MainController::class, 'cancelAthenaTransaction']);
        Route::get('/cancel_issued_item', [MainController::class, 'cancelIssuedItem']);

        // JQUERY
        Route::get('/count_ste_for_issue/{purpose}', [MainController::class, 'countSteForIssue']);
        Route::get('/count_ps_for_issue', [MainController::class, 'countPsForIssue']);
        Route::get('/count_production_to_receive', [ProductionController::class, 'countProductionToReceive']);

        Route::get('/load_suggestion_box', [SearchController::class, 'loadSuggestionBox']);
        Route::get('/sales_report', [ReportController::class, 'salesReport']);
        Route::get('/sales_summary_report/{year}', [ReportController::class, 'salesReportSummary']);

        Route::get('/get_select_filters', [SelectFilterController::class, 'getSelectFilters']);

        Route::get('/get_parent_warehouses', [SelectFilterController::class, 'getParentWarehouses']);
        Route::get('/get_pending_item_request_for_issue', [MainController::class, 'getPendingItemRequestForIssue']);
        Route::get('/get_items_for_return', [MainController::class, 'getItemsForReturn']);
        Route::get('/get_dr_return', [MainController::class, 'getDrReturn']);
        Route::get('/get_mr_sales_return', [MainController::class, 'getMrSalesReturn']);

        Route::get('/feedback_details/{id}', [MainController::class, 'feedbackDetails']);
        Route::post('/feedback_submit', [MainController::class, 'feedbackSubmit']);
        Route::get('/get_ste_details/{id}', [MaterialTransferController::class, 'getSteDetails']);
        Route::get('/get_ps_details/{id}', [MainController::class, 'getPsDetails']);
        Route::get('/get_dr_return_details/{id}', [DeliveryController::class, 'getDrReturnDetails']);

        Route::get('/get_item_details/{item_code}', [ItemProfileController::class, 'getItemDetails']);
        Route::get('/get_athena_transactions/{item_code}', [MainController::class, 'getAthenaTransactions']);
        Route::get('/get_stock_ledger/{item_code}', [MainController::class, 'getStockLedger']);
        Route::get('/form_warehouse_location/{item_code}', [ItemProfileController::class, 'formWarehouseLocation']);
        Route::get('/get_item_stock_levels/{item_code}', [ItemProfileController::class, 'getItemStockLevels']);
        Route::get('/get_item_stock_levels/bundled/{item_code}', [ItemProfileController::class, 'getBundledItemStockLevels']);
        Route::post('/edit_warehouse_location', [ItemProfileController::class, 'editWarehouseLocation']);
        Route::post('/save_item_information/{item_code}', [ItemProfileController::class, 'saveItemInformation']);

        Route::get('/print_barcode/{item_code}', [ItemProfileController::class, 'printBarcode']);

        Route::post('/checkout_ste_item', [MainController::class, 'checkoutSteItem']);
        Route::post('/checkout_picking_slip_item', [MainController::class, 'checkoutPickingSlipItem']);
        Route::post('/submit_dr_sales_return', [DeliveryController::class, 'submitDrSalesReturn']);

        Route::get('/submit_stock_entry/{id}', [MainController::class, 'submitStockEntry']);

        Route::post('/upload_item_image', [ItemProfileController::class, 'uploadItemImage']);
        Route::get('/load_item_images/{item_code}', [ItemProfileController::class, 'loadItemImages']);

        Route::post('/update_stock_entry', [MaterialTransferController::class, 'updateStockEntry']);

        Route::get('/returns', [MainController::class, 'returns']);
        Route::post('/submit_sales_return', [MainController::class, 'submitSalesReturn']);
        Route::post('/submit_dr_sales_return_api', [DeliveryController::class, 'submitDrSalesReturnApi']);
        Route::get('/replacements', [MainController::class, 'replacements']);
        Route::get('/receipts', [MainController::class, 'receipts']);

        // stock reservation
        Route::get('/warehouses', [SelectFilterController::class, 'getWarehouses']);
        Route::get('/warehouses_with_stocks', [StockReservationController::class, 'getWarehouseWithStocks']);
        Route::get('/sales_persons', [SelectFilterController::class, 'getSalesPersons']);
        Route::get('/projects', [SelectFilterController::class, 'getProjects']);
        Route::post('/create_reservation', [StockReservationController::class, 'createReservation']);
        Route::post('/cancel_reservation', [StockReservationController::class, 'cancelReservation']);
        Route::post('/update_reservation', [StockReservationController::class, 'updateReservation']);
        Route::get('/get_stock_reservation_details/{id}', [StockReservationController::class, 'getStockReservationDetails']);
        Route::get('/get_stock_reservation/{item_code?}', [StockReservationController::class, 'getStockReservation']);
        Route::get('/get_item_images/{item_code}', [ItemProfileController::class, 'getItemImages']);
        Route::get('/get_low_stock_level_items', [MainController::class, 'getLowStockLevelItems']);
        Route::get('/allowed_parent_warehouses', [MainController::class, 'allowedParentWarehouses']);
        Route::get('/get_purchase_receipt_details/{id}', [MainController::class, 'getPurchaseReceiptDetails']);
        Route::post('/update_received_item', [MainController::class, 'updateReceivedItem']);
        Route::get('/inv_accuracy/{year}', [MainController::class, 'invAccuracyChart']);
        // Route::get('/get_recently_added_items', 'MainController@get_recently_added_items');
        Route::get('/get_reserved_items', [MainController::class, 'getReservedItems']);
        Route::get('/get_available_qty/{item_code}/{warehouse}', [MainController::class, 'getAvailableQty']);
        Route::get('/validate_if_reservation_exists', [MainController::class, 'validateIfReservationExists']);
        Route::post('/submit_sales_return', [MainController::class, 'submitSalesReturn']);
        Route::get('/view_deliveries', [DeliveryController::class, 'viewDeliveries']);
        Route::get('/get_athena_logs', [DeliveryController::class, 'getAthenaLogs']);
        // Route::post('/submit_transaction', 'MainController@submit_transaction');
        Route::post('/submit_transaction', [TransactionController::class, 'submitTransaction']);
        Route::get('/create_material_request/{id}', [MainController::class, 'createMaterialRequest']);
        Route::get('/consignment_warehouses', [MainController::class, 'consignmentWarehouses']);
        Route::post('/create_feedback', [MainController::class, 'createFeedback']);
        Route::get('/consignment_sales/{warehouse}', [MainController::class, 'consignmentSalesReport']);
        Route::get('/purchase_rate_history/{item_code}', [MainController::class, 'purchaseRateHistory']);
        Route::post('/update_item_price/{item_code}', [MainController::class, 'updateItemCost']);
        Route::get('/search_item_cost', [MainController::class, 'itemCostList']);
        Route::get('/item_group_per_parent/{parent}', [MainController::class, 'itemGroupPerParent']);
        Route::get('/get_parent_item', [MainController::class, 'getParentItems']);
        Route::get('/view_variants/{parent}', [MainController::class, 'itemVariants']);
        Route::post('/update_rate', [MainController::class, 'updateRate']);

        // Consignment Forms
        Route::group(['middleware' => 'checkConnection'], function () {
            Route::post('/approve_beginning_inv/{id}', [ConsignmentBeginningInventoryController::class, 'approveBeginningInventory']);
            Route::post('/adjust_stocks', [ConsignmentStockAdjustmentController::class, 'adjustStocks']);
            Route::post('/add_promodiser_submit', [ConsignmentPromodiserController::class, 'addPromodiser']);
            Route::post('/edit_promodiser_submit/{id}', [ConsignmentPromodiserController::class, 'editPromodiser']);
            Route::post('/submit_monthly_sales_form', [ConsignmentSalesController::class, 'submitMonthlySaleForm']);
            Route::post('/submit_inventory_audit_form', [ConsignmentInventoryAuditController::class, 'submitInventoryAuditForm']);
            Route::post('/stock_transfer/submit', [ConsignmentStockTransferController::class, 'submit']);
            Route::post('/stock_adjust/submit/{id}', [ConsignmentStockAdjustmentController::class, 'submitStockAdjustment']);
            Route::get('/cancel_stock_adjustment/{id}', [ConsignmentStockAdjustmentController::class, 'cancelStockAdjustment']);
            Route::post('/item_return/submit', [ConsignmentStockTransferController::class, 'itemReturnSubmit']);
            Route::post('/save_beginning_inventory', [ConsignmentBeginningInventoryController::class, 'saveBeginningInventory']);
            Route::get('/cancel_beginning_inventory/{id}', [ConsignmentBeginningInventoryController::class, 'cancelDraftBeginningInventory']);
            Route::post('/update_beginning_inventory/{id}', [ConsignmentBeginningInventoryController::class, 'updateDraftBeginningInventory']);
            Route::get('/promodiser/receive/{id}', [ConsignmentPromodiserController::class, 'promodiserReceiveDelivery']);
            Route::get('/promodiser/cancel/received/{id}', [ConsignmentPromodiserController::class, 'promodiserCancelReceivedDelivery']);
            Route::post('/promodiser/damage_report/submit', [ConsignmentPromodiserController::class, 'submitDamagedItem']);
            Route::post('/generate_stock_transfer_entry', [ConsignmentStockTransferController::class, 'generateStockTransferEntry']);
            Route::post('/consignment_read_file', [ConsignmentController::class, 'readFile']);
            Route::post('/assign_barcodes', [ConsignmentController::class, 'assignBarcodes']);

            Route::prefix('/consignment')->group(function () {
                Route::prefix('/replenish')->group(function () {
                    Route::get('/', [ConsignmentReplenishController::class, 'index']);
                    Route::post('/', [ConsignmentReplenishController::class, 'submit']);
                    Route::get('/form/{id?}', [ConsignmentReplenishController::class, 'form']);
                    Route::post('/{id}', [ConsignmentReplenishController::class, 'update']);
                    Route::get('/modal/{id}', [ConsignmentReplenishController::class, 'modalContents']);
                    Route::post('/{id}/approve', [ConsignmentReplenishController::class, 'approve']);
                    Route::get('/{id}/delete', [ConsignmentReplenishController::class, 'delete']);
                });
            });

            Route::get('/consignment_order/{id}/edit', [ConsignmentReplenishController::class, 'editConsignmentOrder']);
            Route::post('/consignment_order/{id}/update', [ConsignmentReplenishController::class, 'updateConsignmentOrder']);
        });

        // Consignment Supervisor
        Route::get('/beginning_inv_list', [ConsignmentBeginningInventoryController::class, 'beginningInventoryApproval']);
        Route::get('/consignment_sales_report', [ConsignmentSalesController::class, 'salesReport']);
        Route::get('/get_consignment_warehouses', [ConsignmentPromodiserController::class, 'getConsignmentWarehouses']);
        Route::get('/stock_adjustment_history', [ConsignmentStockAdjustmentController::class, 'viewStockAdjustmentHistory']);
        Route::get('/stock_adjustment_form', [ConsignmentStockAdjustmentController::class, 'viewStockAdjustmentForm']);
        Route::get('/add_promodiser', [ConsignmentPromodiserController::class, 'addPromodiserForm']);
        Route::get('/edit_promodiser/{id}', [ConsignmentPromodiserController::class, 'editPromodiserForm']);

        // Promodisers
        Route::get('/view_monthly_sales_form/{branch}/{date}', [ConsignmentSalesController::class, 'viewMonthlySalesForm']);
        Route::get('/view_inventory_audit_form/{branch}/{transaction_date}', [ConsignmentInventoryAuditController::class, 'viewInventoryAuditForm']);
        Route::get('/stock_transfer/form', [ConsignmentStockTransferController::class, 'form']);
        Route::get('/stock_transfer/list', [ConsignmentStockTransferController::class, 'list'])->name('stock_transfers');
        Route::get('/stock_transfer/cancel/{id}', [ConsignmentStockTransferController::class, 'cancel']);

        Route::get('/item_return/form', [ConsignmentStockTransferController::class, 'itemReturnForm']);

        Route::get('/beginning_inventory_list', [ConsignmentBeginningInventoryController::class, 'beginningInventoryList']);
        Route::get('/beginning_inventory/{inv?}', [ConsignmentBeginningInventoryController::class, 'beginningInventory']);
        Route::get('/beginning_inv_items/{action}/{branch}/{id?}', [ConsignmentBeginningInventoryController::class, 'beginningInvItems']);
        Route::get('/get_items/{branch}', [ConsignmentBeginningInventoryController::class, 'getItems']);
        Route::get('/cancel/approved_beginning_inv/{id}', [ConsignmentBeginningInventoryController::class, 'cancelApprovedBeginningInventory']);
        Route::get('/promodiser/delivery_report/{type}', [ConsignmentPromodiserController::class, 'promodiserDeliveryReport']);
        Route::get('/promodiser/inquire_delivery', [ConsignmentPromodiserController::class, 'promodiserInquireDelivery']);
        Route::get('/consignment/pending_to_receive', [ConsignmentPromodiserController::class, 'pendingToReceive']);
        Route::get('/sales_report_deadline', [ConsignmentSalesController::class, 'salesReportDeadline']);
        Route::get('/validate_beginning_inventory', [ConsignmentBeginningInventoryController::class, 'checkBeginningInventory']);
        Route::get('/promodiser/damage_report/form', [ConsignmentPromodiserController::class, 'promodiserDamageForm']);
        Route::get('/damage_report/list', [ConsignmentPromodiserController::class, 'damagedItems']);
        Route::get('/damaged/return/{id}', [ConsignmentPromodiserController::class, 'returnDamagedItem']);
        Route::get('/beginning_inv/get_received_items/{branch}', [ConsignmentBeginningInventoryController::class, 'getReceivedItems']);
        Route::get('/stocks_report/list', [ConsignmentStockTransferController::class, 'report'])->name('stock_report_list');
        Route::get('/damaged_items_list', [ConsignmentPromodiserController::class, 'viewDamagedItemsList']);
        Route::get('/countStockTransfer/{purpose}', [ConsignmentStockTransferController::class, 'count']);

        Route::get('/inventory_items/{branch}', [ConsignmentController::class, 'inventoryItems']);

        Route::get('/inventory_audit', [ConsignmentInventoryAuditController::class, 'viewInventoryAuditList']);
        Route::get('/consignment_stores', [ConsignmentController::class, 'consignmentStores']);
        Route::get('/submitted_inventory_audit', [ConsignmentInventoryAuditController::class, 'getSubmittedInvAudit']);
        Route::get('/view_inventory_audit_items/{branch}/{from}/{to}', [ConsignmentInventoryAuditController::class, 'viewInventoryAuditItems']);
        Route::get('/pending_submission_inventory_audit', [ConsignmentInventoryAuditController::class, 'getPendingSubmissionInventoryAudit']);
        Route::get('/sales_report_list/{branch}', [ConsignmentSalesController::class, 'viewSalesReportList']);

        Route::get('/view_sales_report', [ConsignmentPromodiserController::class, 'viewSalesReport']);

        Route::get('/get_activity_logs', [ConsignmentPromodiserController::class, 'activityLogs']);
        Route::get('/view_promodisers', [ConsignmentPromodiserController::class, 'viewPromodisersList']);
        Route::get('/get_audit_deliveries', [ConsignmentPromodiserController::class, 'getAuditDeliveries']);
        Route::get('/get_audit_returns', [ConsignmentPromodiserController::class, 'getAuditReturns']);

        Route::get('/consignment_dashboard', [MainController::class, 'viewConsignmentDashboard']);

        Route::get('/view_consignment_deliveries', [ConsignmentPromodiserController::class, 'viewDeliveries']);

        Route::get('/consignment_import_tool', [ConsignmentController::class, 'importTool']);
        Route::get('/consignment_select_values', [ConsignmentController::class, 'selectValues']);
        Route::get('/consignment/branches', [ConsignmentController::class, 'consignmentBranches']);
        Route::get('/consignment/export/{branch}', [ConsignmentController::class, 'exportToExcel']);

        Route::get('/user_manual', [MainController::class, 'getManuals']);
        Route::get('/user_manual/beginning_inventory', [GuideController::class, 'beginningInventory']);
        Route::get('/user_manual/sales_report_entry', [GuideController::class, 'salesReportEntry']);
        Route::get('/user_manual/stock_transfer', [GuideController::class, 'stockTransfer']);
        Route::get('/user_manual/damaged_items', [GuideController::class, 'damagedItems']);
        Route::get('/user_manual/stock_receiving', [GuideController::class, 'stockReceiving']);
        Route::get('/user_manual/inventory_audit', [GuideController::class, 'inventoryAudit']);
        Route::get('/user_manual/consignment_dashboard', [GuideController::class, 'consignmentDashboard']);
        Route::get('/user_manual/beginning_entries', [GuideController::class, 'beginningEntries']);
        Route::get('/user_manual/inventory_report', [GuideController::class, 'inventoryReport']);
        Route::get('/user_manual/inventory_summary', [GuideController::class, 'inventorySummary']);
        Route::get('/user_manual/stock_to_receive', [GuideController::class, 'stockToReceive']);
        Route::get('/user_manual/consignment_stock_transfer', [GuideController::class, 'consignmentStockTransfer']);

        Route::get('/consignment_ledger', [ConsignmentController::class, 'consignmentLedger']);
        Route::get('/get_item_list', [ConsignmentController::class, 'getErpItems']);
        Route::get('/consignment_stock_movement/{item_code}', [ConsignmentController::class, 'consignmentStockMovement']);
    });

    Route::get('/brochure', [BrochureController::class, 'viewForm'])->name('brochure');
    Route::post('/read_file', [BrochureController::class, 'readExcelFile']);
    Route::post('/upload_image', [BrochureController::class, 'uploadImage']);
    Route::get('/preview/{project}/{filename}', [BrochureController::class, 'previewBrochure']);
    Route::get('/preview_standard', [BrochureController::class, 'previewStandardBrochure']);
    Route::get('/download/{project}/{filename}', [BrochureController::class, 'downloadBrochure']);
    Route::post('/remove_image', [BrochureController::class, 'removeImage']);

    Route::get('/download_image/{file}', [MainController::class, 'downloadImage']);
});
