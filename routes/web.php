<?php
use App\Http\Middleware\CheckConnectionMiddleware;

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
    Route::get('/login', 'LoginController@view_login')->name('login');
    Route::post('/login_user', 'LoginController@login');

    Route::get('/update', 'ItemAttributeController@update_login')->name('update_login');
    Route::post('/U_login_user', 'ItemAttributeController@login');

    Route::group(['middleware' => 'auth'], function(){
        Route::get('/checkConnection', 'MainController@checkConnection');

        Route::post('/generate_sales_order', 'ConsignmentController@createSalesOrder');

        // standard product brochure printing
        Route::get('/generate_brochure', 'BrochureController@generateBrochure');
        Route::post('/upload_image_for_standard_brochure', 'BrochureController@uploadImageForStandard');
        Route::get('/get_item_attributes/{item_code}', 'BrochureController@getItemAttributes');
        Route::post('/update_brochure_attributes', 'BrochureController@updateBrochureAttributes');
        Route::post('/add_to_brochure_list', 'BrochureController@addToBrochureList');
        Route::get('/remove_from_brochure_list/{key}', 'BrochureController@removeFromBrochureList');
        Route::get('/count_brochures', 'BrochureController@countBrochures');
        Route::get('/generate_multiple_brochures', 'BrochureController@generateMultipleBrochures');
        
        // routes for item attribute updating
        Route::post('/update_attribute', 'ItemAttributeController@item_attribute_update');
        Route::get('/search', 'ItemAttributeController@item_attribute_search');
        Route::get('/update_form', 'ItemAttributeController@update_attrib_form');
        Route::get('/add_form/{item_code}', 'ItemAttributeController@add_attrib_form');
        Route::get('/attribute_dropdown', 'ItemAttributeController@item_attribute_dropdown');
        Route::post('/insert_attribute', 'ItemAttributeController@item_attribute_insert');
        Route::get('/signout', 'ItemAttributeController@signout');
        Route::get('/getAttributes', 'ItemAttributeController@getAttributes');
        Route::get('/viewParentItemDetails', 'ItemAttributeController@viewParentItemDetails');
        Route::post('/deleteItemAttribute/{parentItemCode}', 'ItemAttributeController@deleteItemAttribute');
        Route::post('/updateParentItem/{item_code}', 'ItemAttributeController@updateParentItem');
        
        
        Route::get('/', 'MainController@index');
        Route::get('/search_results', 'MainController@search_results');
        Route::get('/search_results_images', 'MainController@search_results_images');
        Route::get('/dashboard_data', 'MainController@dashboard_data');
        Route::get('/import_from_ecommerce', 'MainController@import_from_ecommerce');
        Route::post('/import_images', 'MainController@import_images');
        
        Route::get('/logout', 'LoginController@logout');
            
        Route::get('/material_issue', 'MainController@view_material_issue');
        Route::get('/material_transfer_for_manufacture', 'MainController@view_material_transfer_for_manufacture');
        Route::get('/material_transfer', 'MainController@view_material_transfer');
        Route::get('/picking_slip', 'MainController@view_picking_slip');
        Route::get('/production_to_receive', 'MainController@view_production_to_receive');
        Route::get('/feedbacked_in_transit', 'MainController@feedbacked_in_transit');
        Route::get('/recently_received_items', 'MainController@recently_received_items');
        Route::post('/receive/{id}', 'MainController@receive_stocks');

        // Route::get('/cancel_transaction_modal', 'MainController@cancel_transaction_modal');
        Route::post('/cancel_transaction', 'MainController@cancel_athena_transaction');
        Route::get('/cancel_issued_item', 'MainController@cancel_issued_item');

        // JQUERY
        Route::get('/count_ste_for_issue/{purpose}', 'MainController@count_ste_for_issue');
        Route::get('/count_ps_for_issue', 'MainController@count_ps_for_issue');
        Route::get('/count_production_to_receive', 'MainController@count_production_to_receive');

        Route::get('/load_suggestion_box', 'MainController@load_suggestion_box');
        Route::get('/sales_report', 'ReportController@salesReport');
        Route::get('/sales_summary_report/{year}', 'ReportController@salesReportSummary');

        Route::get('/get_select_filters', 'MainController@get_select_filters');

        Route::get('/get_parent_warehouses', 'MainController@get_parent_warehouses');
        Route::get('/get_pending_item_request_for_issue', 'MainController@get_pending_item_request_for_issue');
        Route::get('/get_items_for_return', 'MainController@get_items_for_return');
        Route::get('/get_dr_return', 'MainController@get_dr_return');
        Route::get('/get_mr_sales_return', 'MainController@get_mr_sales_return');

        Route::get('/feedback_details/{id}', 'MainController@feedback_details');
        Route::post('/feedback_submit', 'MainController@feedback_submit');
        Route::get('/get_ste_details/{id}', 'MainController@get_ste_details');
        Route::get('/get_ps_details/{id}', 'MainController@get_ps_details');
        Route::get('/get_dr_return_details/{id}', 'MainController@get_dr_return_details');

        Route::get('/get_item_details/{item_code}', 'MainController@get_item_details');
        Route::get('/get_athena_transactions/{item_code}', 'MainController@get_athena_transactions');
        Route::get('/get_stock_ledger/{item_code}', 'MainController@get_stock_ledger');
        Route::get('/form_warehouse_location/{item_code}', 'MainController@form_warehouse_location');
        Route::get('/get_item_stock_levels/{item_code}', 'MainController@get_item_stock_levels');
        Route::post('/edit_warehouse_location', 'MainController@edit_warehouse_location');
        Route::post('/save_item_information/{item_code}', 'MainController@save_item_information');

        Route::get('/print_barcode/{item_code}', 'MainController@print_barcode');

        Route::post('/checkout_ste_item', 'MainController@checkout_ste_item');
        Route::post('/checkout_picking_slip_item', 'MainController@checkout_picking_slip_item');
        Route::post('/submit_dr_sales_return', 'MainController@submit_dr_sales_return');

        Route::get('/submit_stock_entry/{id}', 'MainController@submit_stock_entry');

        Route::post('/upload_item_image', 'MainController@upload_item_image');
        Route::get('/load_item_images/{item_code}', 'MainController@load_item_images');

        Route::post('/update_stock_entry', 'MainController@update_stock_entry');

        Route::get('/returns', 'MainController@returns');
        Route::get('/replacements', 'MainController@replacements');
        Route::get('/receipts', 'MainController@receipts');

        // stock reservation
        Route::get('/warehouses', 'MainController@get_warehouses');
        Route::get('/warehouses_with_stocks', 'StockReservationController@get_warehouse_with_stocks');
        Route::get('/sales_persons', 'MainController@get_sales_persons');
        Route::get('/projects', 'MainController@get_projects');
        Route::post('/create_reservation', 'StockReservationController@create_reservation');
        Route::post('/cancel_reservation', 'StockReservationController@cancel_reservation');
        Route::post('/update_reservation', 'StockReservationController@update_reservation');
        Route::get('/get_stock_reservation_details/{id}', 'StockReservationController@get_stock_reservation_details');
        Route::get('/get_stock_reservation/{item_code?}', 'StockReservationController@get_stock_reservation');
        Route::get('/get_item_images/{item_code}', 'MainController@get_item_images');
        Route::get('/get_low_stock_level_items', 'MainController@get_low_stock_level_items');
        Route::get('/allowed_parent_warehouses', 'MainController@allowed_parent_warehouses');
        Route::get('/get_purchase_receipt_details/{id}', 'MainController@get_purchase_receipt_details');
        Route::post('/update_received_item', 'MainController@update_received_item');
        Route::get('/inv_accuracy/{year}', 'MainController@invAccuracyChart');
        // Route::get('/get_recently_added_items', 'MainController@get_recently_added_items');
        Route::get('/get_reserved_items', 'MainController@get_reserved_items');
        Route::get('/get_available_qty/{item_code}/{warehouse}', 'MainController@get_available_qty');
        Route::get('/validate_if_reservation_exists', 'MainController@validate_if_reservation_exists');
        Route::post('/submit_sales_return', 'MainController@submit_sales_return');
        Route::get('/view_deliveries', 'MainController@view_deliveries');
        Route::get('/get_athena_logs', 'MainController@get_athena_logs');
        Route::post('/submit_transaction', 'MainController@submit_transaction');
        Route::get('/create_material_request/{id}', 'MainController@create_material_request');
        Route::get('/consignment_warehouses', 'MainController@consignment_warehouses');
        Route::post('/create_feedback', 'MainController@create_feedback');
        Route::get('/consignment_sales/{warehouse}', 'MainController@consignmentSalesReport');
        Route::get('/purchase_rate_history/{item_code}', 'MainController@purchaseRateHistory');
        Route::post('/update_item_price/{item_code}', 'MainController@updateItemCost');
        Route::get('/search_item_cost', 'MainController@itemCostList');
        Route::get('/item_group_per_parent/{parent}', 'MainController@itemGroupPerParent');
        Route::get('/get_parent_item', 'MainController@getParentItems');
        Route::get('/view_variants/{parent}', 'MainController@itemVariants');
        Route::post('/update_rate', 'MainController@updateRate');

        // Consignment Forms
        Route::group(['middleware' => 'checkConnection'], function(){
            Route::post('/approve_beginning_inv/{id}', 'ConsignmentController@approveBeginningInventory');
            Route::post('/adjust_stocks', 'ConsignmentController@adjustStocks');
            Route::post('/add_promodiser_submit', 'ConsignmentController@addPromodiser');
            Route::post('/edit_promodiser_submit/{id}', 'ConsignmentController@editPromodiser');
            Route::post('/submit_monthly_sales_form', 'ConsignmentController@submitMonthlySaleForm');
            Route::post('/submit_inventory_audit_form', 'ConsignmentController@submitInventoryAuditForm');
            Route::post('/stock_transfer/submit', 'ConsignmentController@stockTransferSubmit');
            Route::post('/stock_adjust/submit/{id}', 'ConsignmentController@submitStockAdjustment');
            Route::get('/cancel_stock_adjustment/{id}', 'ConsignmentController@cancelStockAdjustment');
            Route::post('/item_return/submit', 'ConsignmentController@itemReturnSubmit');
            Route::post('/save_beginning_inventory', 'ConsignmentController@saveBeginningInventory');
            Route::get('/promodiser/receive/{id}', 'ConsignmentController@promodiserReceiveDelivery');
            Route::get('/promodiser/cancel/received/{id}', 'ConsignmentController@promodiserCancelReceivedDelivery');
            Route::post('/promodiser/damage_report/submit', 'ConsignmentController@submitDamagedItem');
            Route::post('/generate_stock_transfer_entry', 'ConsignmentController@generateStockTransferEntry');
            Route::post('/consignment_read_file', 'ConsignmentController@readFile');
            Route::post('/assign_barcodes', 'ConsignmentController@assign_barcodes');
        });

        // Consignment Supervisor
        Route::get('/beginning_inv_list', 'ConsignmentController@beginningInventoryApproval');
        Route::get('/consignment_sales_report', 'ConsignmentController@salesReport');
        Route::get('/get_consignment_warehouses', 'ConsignmentController@getConsignmentWarehouses');
        Route::get('/stock_adjustment_history', 'ConsignmentController@viewStockAdjustmentHistory');
        Route::get('/stock_adjustment_form', 'ConsignmentController@viewStockAdjustmentForm');
        Route::get('/add_promodiser', 'ConsignmentController@addPromodiserForm');
        Route::get('/edit_promodiser/{id}', 'ConsignmentController@editPromodiserForm');

        // Promodisers
        Route::get('/view_monthly_sales_form/{branch}/{date}', 'ConsignmentController@viewMonthlySalesForm');
        Route::get('/view_inventory_audit_form/{branch}/{transaction_date}', 'ConsignmentController@viewInventoryAuditForm');
        Route::get('/stock_transfer/form', 'ConsignmentController@stockTransferForm');
        Route::get('/stock_transfer/list', 'ConsignmentController@stockTransferList')->name('stock_transfers');
        Route::get('/stock_transfer/cancel/{id}', 'ConsignmentController@stockTransferCancel');

        Route::get('/item_return/form', 'ConsignmentController@itemReturnForm');
        
        Route::get('/beginning_inventory_list', 'ConsignmentController@beginningInventoryList');
        Route::get('/beginning_inventory/{inv?}', 'ConsignmentController@beginningInventory');
        Route::get('/beginning_inv_items/{action}/{branch}/{id?}', 'ConsignmentController@beginningInvItems');
        Route::get('/get_items/{branch}', 'ConsignmentController@getItems');
        Route::get('/cancel/approved_beginning_inv/{id}', 'ConsignmentController@cancelApprovedBeginningInventory');
        Route::get('/promodiser/delivery_report/{type}', 'ConsignmentController@promodiserDeliveryReport');
        Route::get('/promodiser/inquire_delivery', 'ConsignmentController@promodiserInquireDelivery');
        Route::get('/consignment/pending_to_receive', 'ConsignmentController@pendingToReceive');
        Route::get('/sales_report_deadline', 'ConsignmentController@salesReportDeadline');
        Route::get('/validate_beginning_inventory', 'ConsignmentController@checkBeginningInventory'); 
        Route::get('/promodiser/damage_report/form', 'ConsignmentController@promodiserDamageForm'); 
        Route::get('/damage_report/list', 'ConsignmentController@damagedItems'); 
        Route::get('/damaged/return/{id}', 'ConsignmentController@returnDamagedItem');
        Route::get('/beginning_inv/get_received_items/{branch}', 'ConsignmentController@getReceivedItems'); 
        Route::get('/stocks_report/list', 'ConsignmentController@stockTransferReport')->name('stock_report_list');
        Route::get('/damaged_items_list', 'ConsignmentController@viewDamagedItemsList');
        Route::get('/countStockTransfer/{purpose}', 'ConsignmentController@countStockTransfer');

        Route::get('/inventory_items/{branch}', 'ConsignmentController@inventoryItems'); 

        Route::get('/inventory_audit', 'ConsignmentController@viewInventoryAuditList');
        Route::get('/consignment_stores', 'ConsignmentController@consignmentStores');
        Route::get('/submitted_inventory_audit', 'ConsignmentController@getSubmittedInvAudit');
        Route::get('/view_inventory_audit_items/{branch}/{from}/{to}', 'ConsignmentController@viewInventoryAuditItems');
        Route::get('/pending_submission_inventory_audit', 'ConsignmentController@getPendingSubmissionInventoryAudit');
        Route::get('/sales_report_list/{branch}', 'ConsignmentController@viewSalesReportList');        

        Route::get('/view_sales_report', 'ConsignmentController@viewSalesReport');

        Route::get('/get_activity_logs', 'ConsignmentController@activityLogs');
        Route::get('/view_promodisers', 'ConsignmentController@viewPromodisersList');
        Route::get('/get_audit_deliveries', 'ConsignmentController@getAuditDeliveries');
        Route::get('/get_audit_returns', 'ConsignmentController@getAuditReturns');

        Route::get('/consignment_dashboard', 'MainController@viewConsignmentDashboard');

        Route::get('/view_consignment_deliveries', 'ConsignmentController@viewDeliveries');

        Route::get('/consignment_import_tool', 'ConsignmentController@import_tool');
        Route::get('/consignment_select_values', 'ConsignmentController@select_values');
        Route::get('/consignment/branches', 'ConsignmentController@consignment_branches');
        Route::get('/consignment/export/{branch}', 'ConsignmentController@export_to_excel');

        Route::get('/user_manual', function () {
            return view('consignment.user_manual.index');
        });
        Route::get('/user_manual/beginning_inventory', function () {
            return view('consignment.user_manual.beginning_inventory');
        });
        Route::get('/user_manual/sales_report_entry', function () {
            return view('consignment.user_manual.sales_report');
        });
        Route::get('/user_manual/stock_transfer', function () {
            return view('consignment.user_manual.stock_transfer');
        });
        Route::get('/user_manual/damaged_items', function () {
            return view('consignment.user_manual.damaged_items');
        });
        Route::get('/user_manual/stock_receiving', function () {
            return view('consignment.user_manual.stock_receiving');
        });
        Route::get('/user_manual/inventory_audit', function () {
            return view('consignment.user_manual.inventory_audit');
        });
        Route::get('/user_manual/consignment_dashboard', function () {
            return view('consignment.user_manual.consignment_dashboard');
        });
        Route::get('/user_manual/beginning_entries', function () {
            return view('consignment.user_manual.beginning_entries');
        });
        Route::get('/user_manual/inventory_report', function () {
            return view('consignment.user_manual.inventory_report');
        });
        Route::get('/user_manual/inventory_summary', function () {
            return view('consignment.user_manual.inventory_summary');
        });
        Route::get('/user_manual/stock_to_receive', function () {
            return view('consignment.user_manual.stock_to_receive');
        });
        Route::get('/user_manual/consignment_stock_transfer', function () {
            return view('consignment.user_manual.consignment_stock_transfer');
        });
        Route::get('/consignment_ledger', 'ConsignmentController@consignmentLedger');
        Route::get('/get_item_list', 'ConsignmentController@getErpItems');
        Route::get('/consignment_stock_movement/{item_code}', 'ConsignmentController@consignmentStockMovement');
    });

    Route::get('/brochure', 'BrochureController@viewForm')->name('brochure');
    Route::post('/read_file', 'BrochureController@readExcelFile');
    Route::post('/upload_image', 'BrochureController@uploadImage');
    Route::get('/preview/{project}/{filename}', 'BrochureController@previewBrochure');
    Route::get('/preview_standard', 'BrochureController@previewStandardBrochure');
    Route::get('/download/{project}/{filename}', 'BrochureController@downloadBrochure');
    Route::post('/remove_image', 'BrochureController@removeImage');
});