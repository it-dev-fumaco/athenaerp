@extends('layout', [
    'namePage' => 'Dashboard User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Dashboard</h6>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_1']) ? $images['cs_1'] : $images['no_img'] }}" style="width: 100%; margin-bottom: 30px;">
                            </div>
                            <ol class="mx-auto">
                                <li class="mb-2"><b>Inventory Report</b> – contains inventory audit entry per cutoff</li>
                                <li class="mb-2"><b>To Receive</b> – contains list of incoming deliveries per consignment store</li>
                                <li class="mb-2"><b>Stock Transfers</b> – contains list of all stock transfer requests submitted by the promodisers</li>
                                <li class="mb-2"><b>Beginning Entries</b> – contains list of all beginning inventory entries</li>
                                <li class="mb-2"><b>Sales Report</b> – Summary of sales per cutoff per store</li>
                                <li class="mb-2"><b>Active Store</b> – Number of stores with submitted beginning inventory entries</li>
                                <li class="mb-2"><b>Promodiser</b>s – Number of Active/Enabled Promodisers</li>
                                <li class="mb-2"><b>Beginning Inventory Completion</b> – Percentage of submitted beginning inventory entries</li>
                            </ol>
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