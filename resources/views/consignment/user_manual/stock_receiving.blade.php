@extends('layout', [
    'namePage' => 'Stock Receiving User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Stock Receiving</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Receive Incoming Deliveries</h6>
                            <p class="mt-2 mb-2 text-justify">You can receive incoming deliveries to your store at the dashboard or at the delivery reports page.</p>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> Once you receive an item. You <u>DO NOT</u> have to create a beginning inventory entry for that item.
                            </div>
                            <p class="mt-2 mb-2 text-justify">1. To go to the delivery reports page, go to Reports > Delivery Report.</p>                           
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_1']) ? $images['stock_receiving_1'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">2. Incoming deliveries for your store/s will be tagged "<b>To Receive</b>". ALL deliveries to your store will be shown in this list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_2']) ? $images['stock_receiving_2'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">You can skip Step 1 by heading to "<b>To Receive Item(s)</b>" section of the dashboard. Only deliveries tagged "<b>To Receive</b>" will be shown in this list.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_3']) ? $images['stock_receiving_3'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">3. Click on the name of the store to review and confirm the items to be received.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_4']) ? $images['stock_receiving_4'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">4. After confirming the quantity received per item. Click "<b>Receive</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_5']) ? $images['stock_receiving_5'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can also enter/update the price of an item by editing the rate.
                            </div>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_6']) ? $images['stock_receiving_6'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> ALL items to be received MUST have a price.
                            </div>
                            <p class="mt-2 mb-2 text-justify">5. After receiving an item. The quantity received per item will be added to your actual stocks. A pop up with the summary of the transaction will be shown.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_7']) ? $images['stock_receiving_7'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="m-2 font-weight-bold">This includes the following:</p>
                            <ul>
                                <li class="mb-2 text-justify">Total quantity of received items.</li>
                                <li class="mb-2 text-justify">Total amount of received items.</li>
                                <li class="mb-2 text-justify">Branch in which the items are delivered to.</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">6. In case there is a mistake in the price of a received item. You can edit the price at the delivery report, see Step 1. Edit the rate and click "<b>Update Prices</b>".</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['stock_receiving_8']) ? $images['stock_receiving_8'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">7. You can also cancel the transaction by clicking "<b>Cancel</b>". After cancelling the transaction the quantity received will be deducted to your actual stocks.</p>
                            <div class="alert alert-info p-2 text-justify" style="font-size: 9pt;">
                                <i class="fas fa-info-circle"></i> <b>Note:</b> You can only cancel a delivery if the actual stocks of ALL the items of that delivery are equal or more than the delivered quantity.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection