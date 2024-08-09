@extends('layout', [
    'namePage' => 'Stock Transfer User Manual',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-2 pl-0 pr-0">
                <div class="col-md-12 m-0 p-0">
                    <div class="card card-info card-outline" style="font-size: 9pt;">
                        <div class="card-header p-1">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb ml-2">
                                  <li class="breadcrumb-item"><a href="/">Home</a></li>
                                  <li class="breadcrumb-item"><a href="/user_manual">User Manuals</a></li>
                                  <li class="breadcrumb-item active" aria-current="page">Stock Transfer</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Stock Transfers</h6>
                            <p class="mt-2 mb-2 text-justify">There are three (3) types of stock transfers for promodisers. They are the following:</p>
                            <ul>
                                <li class="mb-2"><b>Store Transfer</b> – Transfer an item from your store to another.</li>
                                <li class="mb-2"><b>For Return</b> – Return an item to FUMACO.</li>
                                <li class="mb-2"><b>Sales Return</b> – If a customer returns a sold item.</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">To create a stock transfer request follow this steps:</p>
                            <p class="mt-2 mb-2 text-justify">1. Click "<b>Stock Transfer</b>" tab in the dashboard.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_1']) ? $images['stock_transfer_1'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. You will see a list of Approved and For approval stock transfer requests. Click "<b>Create</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_2']) ? $images['stock_transfer_2'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. Stock Transfer Request Form. Select "<b>Purpose</b>" of your transfer.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_3']) ? $images['stock_transfer_3'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">a. "<b>Store Transfer</b>" – if you selected this, "<b>To</b>" field will show up.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_4']) ? $images['stock_transfer_4'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">i. "<b>From</b>" – select from the list of stores assigned to you.</p>
                            <p class="mt-2 mb-2 text-justify">ii. "<b>To</b>" – select from the list of ALL available stores you wish to transfer your item.</p>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can select your target(to) store after selecting the source(from) store.
                            </div>
                            <p class="mt-2 mb-2 text-justify">b. "<b>For Return</b>" – selecting this will automatically fill up the "<b>To</b>" field with "<b>Fumaco – Plant 2</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_5']) ? $images['stock_transfer_5'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">i. "<b>From</b>" – select from the list of stores assigned to you.</p>
                            <p class="mt-2 mb-2 text-justify">c. "<b>Sales Return</b>" – selecting this option will remove the "<b>From</b>" field.</p>
                            <p class="mt-2 mb-2 text-justify">i. "<b>To</b>" – select from the list of stores assigned to you.</p>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You have to complete ANY of these steps to proceed.
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. After completing ANY of the steps from step 3. Click "<b>Add Item</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_6']) ? $images['stock_transfer_6'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. Use the dropdown to search and select for the item/s you wish to transfer or return.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_7']) ? $images['stock_transfer_7'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. After selecting an item, you will see how many stocks are left for that item. You can then enter the quantity you want to transfer or return. Click on "<b>Add item</b>" to add the item to the list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_8']) ? $images['stock_transfer_8'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                                <img src="{{ isset($images['stock_transfer_9']) ? $images['stock_transfer_9'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> If you selected "<b>Sales Return</b>", you will see the quantity sold per item instead.
                            </div>
                            <p class="mt-2 mb-2 text-justify">Warnings:</p>
                            <ul>
                                <li class="mb-2">"<b>Item [item code] already exists in the list</b>" – Item is already added on the list. You <u>DO NOT</u> need to add the item.</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">7. You can also edit the quantity you wish to transfer on the list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_10']) ? $images['stock_transfer_10'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">8. After reviewing your entry and making sure everything is in order, you can then click submit. After submitting the form, a new "<b>For Approval</b>" stock transfer request will be added to the list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_11']) ? $images['stock_transfer_11'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">9. You can see the status of your request by clicking "<b>Store Transfer</b>" from the dashboard.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_12']) ? $images['stock_transfer_12'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> List of Sales Returns are in the Sales Returns Report. Go to Report > Sales Returns Report
                            </div>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_13']) ? $images['stock_transfer_13'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">Then you will see the list of Approved and For Approval Sales Returns. Click "<b>View Items</b>" to verify if the request is correct.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_transfer_14']) ? $images['stock_transfer_14'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection