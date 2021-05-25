<?php

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

Route::get('/login', 'LoginController@view_login')->name('login');
Route::post('/login_user', 'LoginController@login');

Route::group(['middleware' => 'auth'], function(){
    Route::get('/', 'MainController@index');
    Route::get('/search_results', 'MainController@search_results');
    Route::get('/dashboard_data', 'MainController@dashboard_data');
    
    Route::get('/logout', 'LoginController@logout');
        
    Route::get('/material_issue', 'MainController@view_material_issue');
    Route::get('/material_transfer_for_manufacture', 'MainController@view_material_transfer_for_manufacture');
    Route::get('/material_transfer', 'MainController@view_material_transfer');
    Route::get('/picking_slip', 'MainController@view_picking_slip');
    Route::get('/production_to_receive', 'MainController@view_production_to_receive');

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

    Route::get('/get_ste_details/{id}', 'MainController@get_ste_details');
    Route::get('/get_ps_details/{id}', 'MainController@get_ps_details');
    Route::get('/get_dr_return_details/{id}', 'MainController@get_dr_return_details');

    Route::get('/get_item_details/{item_code}', 'MainController@get_item_details');
    Route::get('/get_athena_transactions/{item_code}', 'MainController@get_athena_transactions');
    Route::get('/get_stock_ledger/{item_code}', 'MainController@get_stock_ledger');

    Route::get('/print_barcode/{item_code}', 'MainController@print_barcode');

    Route::post('/checkout_ste_item', 'MainController@checkout_ste_item');
    Route::post('/checkout_picking_slip_item', 'MainController@checkout_picking_slip_item');
    Route::post('/return_dr_item', 'MainController@return_dr_item');

    Route::get('/submit_stock_entry/{id}', 'MainController@submit_stock_entry');

    Route::post('/upload_item_image', 'MainController@upload_item_image');

    Route::post('/update_stock_entry', 'MainController@update_stock_entry');

    // stock reservation
    // Route::get('/items', 'MainController@get_items');
    Route::get('/warehouses', 'MainController@get_warehouses');
    
    Route::get('/sales_persons', 'MainController@get_sales_persons');
    Route::get('/projects', 'MainController@get_projects');
    // Route::get('/stock_reservation', 'StockReservationController@view_form');
    Route::post('/create_reservation', 'StockReservationController@create_reservation');
    Route::post('/cancel_reservation', 'StockReservationController@cancel_reservation');
    Route::post('/update_reservation', 'StockReservationController@update_reservation');

    Route::get('/get_stock_reservation_details/{id}', 'StockReservationController@get_stock_reservation_details');

    Route::get('/get_stock_reservation/{item_code?}', 'StockReservationController@get_stock_reservation');

    Route::get('/get_item_images/{item_code}', 'MainController@get_item_images');


    
});