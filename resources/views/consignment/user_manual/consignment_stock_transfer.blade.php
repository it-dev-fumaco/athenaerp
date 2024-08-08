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
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_18']) ? $images['cs_18'] : $images['no_img'] }}" style="width: 100%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">1. <b>Stock Transfers</b> – List of all stock transfers.</p>
                            <p class="mt-2 mb-2 text-justify">2. <b>Damaged Items List</b> – Tab of returned damaged items.</p>
                            <p class="mt-2 mb-2 text-justify">3. <b>Filters</b> – You can filter this list by the following:</p>
                            <ul class="mx-auto">
                                <li class="mb-2">Searching</li>
                                <li class="mb-2">Purpose of transfer</li>
                                <li the="mb-2">Source warehouse</li>
                                <li class="mb-2">Target warehouse</li>
                                <li class="mb-2">Status</li>
                            </ul>
                            <p class="mt-2 mb-2 text-justify">4. <b>View Items</b> – List of items transferred and their quantity.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_19']) ? $images['cs_19'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify"><b>For 'For Return' STE</b></p>
                            <p class="mt-2 mb-2 text-justify">5. <b>Select Target Warehouse</b> - By default, target warehouse is 'Quarantine Warehouse - FI'.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_21']) ? $images['cs_21'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <p class="mt-2 mb-2 text-justify">6. <b>Receive Items</b> – Once verified, click 'Receive' to transfer stocks from the consignment store to the selected warehouse.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_20']) ? $images['cs_20'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
                            <h6 class="font-weight-bold text-info text-uppercase">Process Flow</h6>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['receiving-of-stocks']) ? $images['receiving-of-stocks'] : $images['no_img'] }}" style="margin-bottom: 30px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection
