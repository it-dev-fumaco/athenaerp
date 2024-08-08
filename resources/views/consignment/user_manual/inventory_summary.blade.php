@extends('layout', [
    'namePage' => 'Inventory Summary User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Inventory Summary</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Inventory Report</h6>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_15']) ? $images['cs_15'] : $images['no_img'] }}" style="width: 100%; margin-bottom: 30px;">
                            </div>
                            <ol class="mx-auto">
                                <li class="mb-2"><b>Report Summary</b> – Shows quantity sold and total sales amount per item. Also shows total sales for the current cutoff.</li>
                                <li class="mb-2"><b>Received Item(s)</b> – Shows list of items received within the cutoff</li>
                                <li class="mb-2"><b>Stock Levels</b> – Quantity of stocks left for the cutoff</li>
                                <li class="mb-2"><b>Previous / Next Button</b> – go to the previous/next record</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection
