@extends('layout', [
    'namePage' => 'Stock to Receive User Manual',
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
                                  <li class="breadcrumb-item active" aria-current="page">Stock To Receive</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Stock To Receive</h6>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_16']) ? $images['cs_16'] : $images['no_img'] }}" style="width: 100%; margin-bottom: 30px;">
                            </div>
                            <ol class="mx-auto">
                                <li class="mb-2"><b>Incoming Deliveries List</b> – list of all deliveries on consignment stores.</li>
                                <li class="mb-2"><b>Store Filter</b> – You can filter the list by specific store.</li>
                                <li class="mb-2"><b>Reset Filter</b> – Reset the store filter.</li>
                                <li class="mb-2"><b>View Items</b> – View all items in this delivery.</li>
                            </ol>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_17']) ? $images['cs_17'] : $images['no_img'] }}" style="width: 70%; margin-bottom: 30px;">
                            </div>
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