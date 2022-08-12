@extends('layout', [
    'namePage' => 'Product Sold Entry User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Product Sold Entry</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Product Sold Entry</h6>
                            <p class="text-justify">Once your beginning inventory entry has been approved by your consignment supervisor. You have to make product sold entry for products sold each day.</p>
                            <p class="mt-2 mb-2 text-justify">1. From the dashboard, Click "<b>Product Sold</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_1.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">If there are more than one (1) store assigned to you, this will pop up the list of stores assigned to you. Click on the store with sold products for that day. If there is only one (1) store assigned to you, you will skip this step.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_2.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. A calendar page will open, select the date with products sold.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_3.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <ul>
                                <li class="mb-2 text-justify">Green dates indicates dates with on time submission of product sold entry</li>
                                <li class="mb-2 text-justify">Red dates indicates late submission of product sold entry</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">3. Enter the quantity sold per item.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_4.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> Only items with APPROVED Beginning inventory entry and RECEIVED items will be shown in the list. If you cannot find an item, you can search for it using the search bar or go to Inventory > Beginning Inventory and look through all the "<b>For Approval</b>" Beginning Inventory entry for the missing items.
                            </div>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_5.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. After entering the quantity sold per item. Click "<b>Submit</b>" at the bottom of the page.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_6.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. You will see a pop up with a summary of your entry. This includes the total sales amount and total quantity sold for that day. After verifying that this data is correct, click "<b>Confirm</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_7.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. After confirmation, your record will be saved.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ asset('storage/user_manual_img/product_sold_8.png') }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">7. You can follow this process again if you wish to edit your entry.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection

@section('script')

@endsection