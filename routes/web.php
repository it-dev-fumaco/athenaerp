<?php
use App\Http\Controllers\BrochureController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\ItemAttributeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockReservationController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\CheckConnectionMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['sanitation', 'throttle:global']], function(){
    Route::get('/login', [LoginController::class, 'view_login'])->name('login');
    Route::get('/login_user', function () {
        return redirect('/login');
    });
    Route::post('/login_user', [LoginController::class, 'login']);

    Route::get('/update', [ItemAttributeController::class, 'update_login'])->name('update_login');
    Route::post('/U_login_user', [ItemAttributeController::class, 'login']);

    Route::group(['middleware' => 'auth'], function(){
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
        Route::get('/customers', [MainController::class, 'get_customers']);
        Route::get('/erp_projects', [MainController::class, 'get_erp_projects']);
        Route::get('/customer_address', [MainController::class, 'get_customer_address']);
        Route::get('/branch_warehouses', [MainController::class, 'get_branch_warehouses']);

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
        Route::post('/update_attribute', [ItemAttributeController::class, 'item_attribute_update']);
        Route::get('/search', [ItemAttributeController::class, 'item_attribute_search']);
        Route::get('/update_form', [ItemAttributeController::class, 'update_attrib_form']);
        Route::get('/add_form/{item_code}', [ItemAttributeController::class, 'add_attrib_form']);
        Route::get('/attribute_dropdown', [ItemAttributeController::class, 'item_attribute_dropdown']);
        Route::post('/insert_attribute', [ItemAttributeController::class, 'item_attribute_insert']);
        Route::get('/signout', [ItemAttributeController::class, 'signout']);
        Route::get('/getAttributes', [ItemAttributeController::class, 'getAttributes']);
        Route::get('/viewParentItemDetails', [ItemAttributeController::class, 'viewParentItemDetails']);
        Route::post('/deleteItemAttribute/{parentItemCode}', [ItemAttributeController::class, 'deleteItemAttribute']);
        Route::post('/updateParentItem/{item_code}', [ItemAttributeController::class, 'updateParentItem']);
        
        
        Route::get('/', [MainController::class, 'index']);
        Route::get('/search_results', [MainController::class, 'search_results']);
        Route::get('/search_results_images', [MainController::class, 'search_results_images']);
        Route::get('/dashboard_data', [MainController::class, 'dashboard_data']);
        Route::get('/import_from_ecommerce', [MainController::class, 'import_from_ecommerce']);
        Route::post('/import_images', [MainController::class, 'import_images']);
        
        Route::get('/logout', [LoginController::class, 'logout']);
            
        Route::get('/material_issue', [MainController::class, 'view_material_issue']);
        Route::get('/material_transfer_for_manufacture', [MainController::class, 'view_material_transfer_for_manufacture']);
        Route::get('/material_transfer', [MainController::class, 'view_material_transfer']);
        Route::post('/submit_internal_transfer', [MainController::class, 'submit_internal_transfer']);
        Route::get('/picking_slip', [MainController::class, 'view_picking_slip']);
        Route::post('/checkout_picking_slip', [TransactionController::class, 'checkout_picking_slip']);
        Route::get('/production_to_receive', [MainController::class, 'view_production_to_receive']);
        Route::get('/recently_received_items', [MainController::class, 'recently_received_items']);

        Route::prefix('/in_transit')->group(function(){
            Route::get('/', [MainController::class, 'feedbacked_in_transit']);
            Route::post('/receive/{id}', [MainController::class, 'receive_transit_stocks']);
            Route::post('/transfer/{id}', [MainController::class, 'transfer_transit_stocks']);
        });

        // Route::get('/cancel_transaction_modal', 'MainController@cancel_transaction_modal');
        Route::post('/cancel_transaction', [MainController::class, 'cancel_athena_transaction']);
        Route::get('/cancel_issued_item', [MainController::class, 'cancel_issued_item']);

        // JQUERY
        Route::get('/count_ste_for_issue/{purpose}', [MainController::class, 'count_ste_for_issue']);
        Route::get('/count_ps_for_issue', [MainController::class, 'count_ps_for_issue']);
        Route::get('/count_production_to_receive', [MainController::class, 'count_production_to_receive']);

        Route::get('/load_suggestion_box', [MainController::class, 'load_suggestion_box']);
        Route::get('/sales_report', [ReportController::class, 'salesReport']);
        Route::get('/sales_summary_report/{year}', [ReportController::class, 'salesReportSummary']);

        Route::get('/get_select_filters', [MainController::class, 'get_select_filters']);

        Route::get('/get_parent_warehouses', [MainController::class, 'get_parent_warehouses']);
        Route::get('/get_pending_item_request_for_issue', [MainController::class, 'get_pending_item_request_for_issue']);
        Route::get('/get_items_for_return', [MainController::class, 'get_items_for_return']);
        Route::get('/get_dr_return', [MainController::class, 'get_dr_return']);
        Route::get('/get_mr_sales_return', [MainController::class, 'get_mr_sales_return']);

        Route::get('/feedback_details/{id}', [MainController::class, 'feedback_details']);
        Route::post('/feedback_submit', [MainController::class, 'feedback_submit']);
        Route::get('/get_ste_details/{id}', [MainController::class, 'get_ste_details']);
        Route::get('/get_ps_details/{id}', [MainController::class, 'get_ps_details']);
        Route::get('/get_dr_return_details/{id}', [MainController::class, 'get_dr_return_details']);

        Route::get('/get_item_details/{item_code}', [MainController::class, 'get_item_details']);
        Route::get('/get_athena_transactions/{item_code}', [MainController::class, 'get_athena_transactions']);
        Route::get('/get_stock_ledger/{item_code}', [MainController::class, 'get_stock_ledger']);
        Route::get('/form_warehouse_location/{item_code}', [MainController::class, 'form_warehouse_location']);
        Route::get('/get_item_stock_levels/{item_code}', [MainController::class, 'get_item_stock_levels']);
        Route::get('/get_item_stock_levels/bundled/{item_code}', [MainController::class, 'get_bundled_item_stock_levels']);
        Route::post('/edit_warehouse_location', [MainController::class, 'edit_warehouse_location']);
        Route::post('/save_item_information/{item_code}', [MainController::class, 'save_item_information']);

        Route::get('/print_barcode/{item_code}', [MainController::class, 'print_barcode']);

        Route::post('/checkout_ste_item', [MainController::class, 'checkout_ste_item']);
        Route::post('/checkout_picking_slip_item', [MainController::class, 'checkout_picking_slip_item']);
        Route::post('/submit_dr_sales_return', [MainController::class, 'submit_dr_sales_return']);

        Route::get('/submit_stock_entry/{id}', [MainController::class, 'submit_stock_entry']);

        Route::post('/upload_item_image', [MainController::class, 'upload_item_image']);
        Route::get('/load_item_images/{item_code}', [MainController::class, 'load_item_images']);

        Route::post('/update_stock_entry', [MainController::class, 'update_stock_entry']);

        Route::get('/returns', [MainController::class, 'returns']);
        Route::post('/submit_sales_return', [MainController::class, 'submit_sales_return']);
        Route::post('/submit_dr_sales_return_api', [MainController::class, 'submit_dr_sales_return_api']);
        Route::get('/replacements', [MainController::class, 'replacements']);
        Route::get('/receipts', [MainController::class, 'receipts']);

        // stock reservation
        Route::get('/warehouses', [MainController::class, 'get_warehouses']);
        Route::get('/warehouses_with_stocks', [StockReservationController::class, 'get_warehouse_with_stocks']);
        Route::get('/sales_persons', [MainController::class, 'get_sales_persons']);
        Route::get('/projects', [MainController::class, 'get_projects']);
        Route::post('/create_reservation', [StockReservationController::class, 'create_reservation']);
        Route::post('/cancel_reservation', [StockReservationController::class, 'cancel_reservation']);
        Route::post('/update_reservation', [StockReservationController::class, 'update_reservation']);
        Route::get('/get_stock_reservation_details/{id}', [StockReservationController::class, 'get_stock_reservation_details']);
        Route::get('/get_stock_reservation/{item_code?}', [StockReservationController::class, 'get_stock_reservation']);
        Route::get('/get_item_images/{item_code}', [MainController::class, 'get_item_images']);
        Route::get('/get_low_stock_level_items', [MainController::class, 'get_low_stock_level_items']);
        Route::get('/allowed_parent_warehouses', [MainController::class, 'allowed_parent_warehouses']);
        Route::get('/get_purchase_receipt_details/{id}', [MainController::class, 'get_purchase_receipt_details']);
        Route::post('/update_received_item', [MainController::class, 'update_received_item']);
        Route::get('/inv_accuracy/{year}', [MainController::class, 'invAccuracyChart']);
        // Route::get('/get_recently_added_items', 'MainController@get_recently_added_items');
        Route::get('/get_reserved_items', [MainController::class, 'get_reserved_items']);
        Route::get('/get_available_qty/{item_code}/{warehouse}', [MainController::class, 'get_available_qty']);
        Route::get('/validate_if_reservation_exists', [MainController::class, 'validate_if_reservation_exists']);
        Route::post('/submit_sales_return', [MainController::class, 'submit_sales_return']);
        Route::get('/view_deliveries', [MainController::class, 'view_deliveries']);
        Route::get('/get_athena_logs', [MainController::class, 'get_athena_logs']);
        // Route::post('/submit_transaction', 'MainController@submit_transaction');
        Route::post('/submit_transaction', [TransactionController::class, 'submit_transaction']);
        Route::get('/create_material_request/{id}', [MainController::class, 'create_material_request']);
        Route::get('/consignment_warehouses', [MainController::class, 'consignment_warehouses']);
        Route::post('/create_feedback', [MainController::class, 'create_feedback']);
        Route::get('/consignment_sales/{warehouse}', [MainController::class, 'consignmentSalesReport']);
        Route::get('/purchase_rate_history/{item_code}', [MainController::class, 'purchaseRateHistory']);
        Route::post('/update_item_price/{item_code}', [MainController::class, 'updateItemCost']);
        Route::get('/search_item_cost', [MainController::class, 'itemCostList']);
        Route::get('/item_group_per_parent/{parent}', [MainController::class, 'itemGroupPerParent']);
        Route::get('/get_parent_item', [MainController::class, 'getParentItems']);
        Route::get('/view_variants/{parent}', [MainController::class, 'itemVariants']);
        Route::post('/update_rate', [MainController::class, 'updateRate']);

        // Consignment Forms
        Route::group(['middleware' => 'checkConnection'], function(){
            Route::post('/approve_beginning_inv/{id}', [ConsignmentController::class, 'approveBeginningInventory']);
            Route::post('/adjust_stocks', [ConsignmentController::class, 'adjustStocks']);
            Route::post('/add_promodiser_submit', [ConsignmentController::class, 'addPromodiser']);
            Route::post('/edit_promodiser_submit/{id}', [ConsignmentController::class, 'editPromodiser']);
            Route::post('/submit_monthly_sales_form', [ConsignmentController::class, 'submitMonthlySaleForm']);
            Route::post('/submit_inventory_audit_form', [ConsignmentController::class, 'submitInventoryAuditForm']);
            Route::post('/stock_transfer/submit', [ConsignmentController::class, 'stockTransferSubmit']);
            Route::post('/stock_adjust/submit/{id}', [ConsignmentController::class, 'submitStockAdjustment']);
            Route::get('/cancel_stock_adjustment/{id}', [ConsignmentController::class, 'cancelStockAdjustment']);
            Route::post('/item_return/submit', [ConsignmentController::class, 'itemReturnSubmit']);
            Route::post('/save_beginning_inventory', [ConsignmentController::class, 'saveBeginningInventory']);
            Route::get('/cancel_beginning_inventory/{id}', [ConsignmentController::class, 'cancelDraftBeginningInventory']);
            Route::post('/update_beginning_inventory/{id}', [ConsignmentController::class, 'updateDraftBeginningInventory']);
            Route::get('/promodiser/receive/{id}', [ConsignmentController::class, 'promodiserReceiveDelivery']);
            Route::get('/promodiser/cancel/received/{id}', [ConsignmentController::class, 'promodiserCancelReceivedDelivery']);
            Route::post('/promodiser/damage_report/submit', [ConsignmentController::class, 'submitDamagedItem']);
            Route::post('/generate_stock_transfer_entry', [ConsignmentController::class, 'generateStockTransferEntry']);
            Route::post('/consignment_read_file', [ConsignmentController::class, 'readFile']);
            Route::post('/assign_barcodes', [ConsignmentController::class, 'assign_barcodes']);

            Route::prefix('/consignment')->group(function(){
                Route::prefix('/replenish')->group(function(){
                    Route::get('/', [ConsignmentController::class, 'replenish_index']);
                    Route::post('/', [ConsignmentController::class, 'replenish_submit']);
                    Route::get('/form/{id?}', [ConsignmentController::class, 'replenish_form']);
                    Route::post('/{id}', [ConsignmentController::class, 'replenish_update']);
                    Route::get('/modal/{id}', [ConsignmentController::class, 'replenish_modal_contents']);
                    Route::post('/{id}/approve', [ConsignmentController::class, 'replenish_approve']);
                    Route::get('/{id}/delete', [ConsignmentController::class, 'replenish_delete']);
                });
            });

            Route::get('/consignment_order/{id}/edit', [ConsignmentController::class, 'editConsignmentOrder']);
            Route::post('/consignment_order/{id}/update', [ConsignmentController::class, 'updateConsignmentOrder']);
        });

        // Consignment Supervisor
        Route::get('/beginning_inv_list', [ConsignmentController::class, 'beginningInventoryApproval']);
        Route::get('/consignment_sales_report', [ConsignmentController::class, 'salesReport']);
        Route::get('/get_consignment_warehouses', [ConsignmentController::class, 'getConsignmentWarehouses']);
        Route::get('/stock_adjustment_history', [ConsignmentController::class, 'viewStockAdjustmentHistory']);
        Route::get('/stock_adjustment_form', [ConsignmentController::class, 'viewStockAdjustmentForm']);
        Route::get('/add_promodiser', [ConsignmentController::class, 'addPromodiserForm']);
        Route::get('/edit_promodiser/{id}', [ConsignmentController::class, 'editPromodiserForm']);

        // Promodisers
        Route::get('/view_monthly_sales_form/{branch}/{date}', [ConsignmentController::class, 'viewMonthlySalesForm']);
        Route::get('/view_inventory_audit_form/{branch}/{transaction_date}', [ConsignmentController::class, 'viewInventoryAuditForm']);
        Route::get('/stock_transfer/form', [ConsignmentController::class, 'stockTransferForm']);
        Route::get('/stock_transfer/list', [ConsignmentController::class, 'stockTransferList'])->name('stock_transfers');
        Route::get('/stock_transfer/cancel/{id}', [ConsignmentController::class, 'stockTransferCancel']);

        Route::get('/item_return/form', [ConsignmentController::class, 'itemReturnForm']);
        
        Route::get('/beginning_inventory_list', [ConsignmentController::class, 'beginningInventoryList']);
        Route::get('/beginning_inventory/{inv?}', [ConsignmentController::class, 'beginningInventory']);
        Route::get('/beginning_inv_items/{action}/{branch}/{id?}', [ConsignmentController::class, 'beginningInvItems']);
        Route::get('/get_items/{branch}', [ConsignmentController::class, 'getItems']);
        Route::get('/cancel/approved_beginning_inv/{id}', [ConsignmentController::class, 'cancelApprovedBeginningInventory']);
        Route::get('/promodiser/delivery_report/{type}', [ConsignmentController::class, 'promodiserDeliveryReport']);
        Route::get('/promodiser/inquire_delivery', [ConsignmentController::class, 'promodiserInquireDelivery']);
        Route::get('/consignment/pending_to_receive', [ConsignmentController::class, 'pendingToReceive']);
        Route::get('/sales_report_deadline', [ConsignmentController::class, 'salesReportDeadline']);
        Route::get('/validate_beginning_inventory', [ConsignmentController::class, 'checkBeginningInventory']); 
        Route::get('/promodiser/damage_report/form', [ConsignmentController::class, 'promodiserDamageForm']); 
        Route::get('/damage_report/list', [ConsignmentController::class, 'damagedItems']); 
        Route::get('/damaged/return/{id}', [ConsignmentController::class, 'returnDamagedItem']);
        Route::get('/beginning_inv/get_received_items/{branch}', [ConsignmentController::class, 'getReceivedItems']); 
        Route::get('/stocks_report/list', [ConsignmentController::class, 'stockTransferReport'])->name('stock_report_list');
        Route::get('/damaged_items_list', [ConsignmentController::class, 'viewDamagedItemsList']);
        Route::get('/countStockTransfer/{purpose}', [ConsignmentController::class, 'countStockTransfer']);

        Route::get('/inventory_items/{branch}', [ConsignmentController::class, 'inventoryItems']); 

        Route::get('/inventory_audit', [ConsignmentController::class, 'viewInventoryAuditList']);
        Route::get('/consignment_stores', [ConsignmentController::class, 'consignmentStores']);
        Route::get('/submitted_inventory_audit', [ConsignmentController::class, 'getSubmittedInvAudit']);
        Route::get('/view_inventory_audit_items/{branch}/{from}/{to}', [ConsignmentController::class, 'viewInventoryAuditItems']);
        Route::get('/pending_submission_inventory_audit', [ConsignmentController::class, 'getPendingSubmissionInventoryAudit']);
        Route::get('/sales_report_list/{branch}', [ConsignmentController::class, 'viewSalesReportList']);        

        Route::get('/view_sales_report', [ConsignmentController::class, 'viewSalesReport']);

        Route::get('/get_activity_logs', [ConsignmentController::class, 'activityLogs']);
        Route::get('/view_promodisers', [ConsignmentController::class, 'viewPromodisersList']);
        Route::get('/get_audit_deliveries', [ConsignmentController::class, 'getAuditDeliveries']);
        Route::get('/get_audit_returns', [ConsignmentController::class, 'getAuditReturns']);

        Route::get('/consignment_dashboard', [MainController::class, 'viewConsignmentDashboard']);

        Route::get('/view_consignment_deliveries', [ConsignmentController::class, 'viewDeliveries']);

        Route::get('/consignment_import_tool', [ConsignmentController::class, 'import_tool']);
        Route::get('/consignment_select_values', [ConsignmentController::class, 'select_values']);
        Route::get('/consignment/branches', [ConsignmentController::class, 'consignment_branches']);
        Route::get('/consignment/export/{branch}', [ConsignmentController::class, 'export_to_excel']);

        Route::get('/user_manual', [MainController::class, 'get_manuals']);

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

    Route::get('/download_image/{file}', [MainController::class, 'download_image']);
});