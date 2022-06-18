@extends('layout', [
    'namePage' => 'Stock Transfers Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid p-0">
            <div class="row mt-1">
                <div class="col-md-9 mx-auto">
                   
                    <div style="margin-bottom: -43px;">
                        <a href="/" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i> </a>
                    </div>

                    <h3 class="text-center font-weight-bold m-2 text-uppercase">Stock Transfers</h3>
                    <div class="card card-info card-outline">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs m-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#stock_transfers" style="font-size: 10pt">Stock Transfers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#damaged_items" style="font-size: 10pt">Damaged Items</a>
                            </li>
                        </ul>
                      
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- Stock Transfers -->
                            <div id="stock_transfers" class="container-fluid tab-pane active" style="padding: 8px 0 0 0;">
                                <div id="accordion" class="p-0 m-0" style="border: none !important">
                                    <div class="card p-0 m-0" style="border: none !important">
                                        <div class="card-header p-2 d-block d-xl-none" id="headingOne">
                                            <button class="btn btn-link p-0" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 10pt">
                                                <i class="fa fa-filter"></i>&nbsp;Filters
                                            </button>
                                        </div>
                                    
                                        <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                            <div class="card-body">
                                                <form action="/stocks_report/list" method="get">
                                                    <div class="row">
                                                        <div class="col-12 col-xl-2">
                                                            <input type="text" name="tab1_q" class="form-control" placeholder='Search' style='font-size: 10pt'/>
                                                        </div>
                                                        <div class="col-12 mt-2 mt-xl-0 col-xl-2">
                                                            <select name="source_warehouse" id="source-warehouse" class="form-control" style="font-size: 10pt"></select>
                                                        </div>
                                                        <div class="col-12 mt-2 mt-xl-0 col-xl-2">
                                                            <select name="target_warehouse" id="target-warehouse" class="form-control" style="font-size: 10pt"></select>
                                                        </div>
                                                        <div class="col-12 mt-2 mt-xl-0 col-xl-2">
                                                            <select name="tab1_status" id='status' class="form-control" style="font-size: 10pt">
                                                                @php
                                                                    $status = [
                                                                        ['title' => 'Select All', 'value' => 'All'],
                                                                        ['title' => 'For Approval', 'value' => 0],
                                                                        ['title' => 'Approved', 'value' => 1]
                                                                    ];
                                                                @endphp 
                                                                <option value="" disabled selected>Select a status</option>
                                                                @foreach ($status as $s)
                                                                    <option value="{{ $s['value'] }}">{{ $s['title'] }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12 col-xl-1 mt-2 mt-xl-0">
                                                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <table class="table table-striped" style="font-size: 10pt;">
                                    <tr>
                                        <th class="text-center d-none d-xl-table-cell" id='first-row'>Date</th>
                                        <th class="text-center" id='second-row'>
                                            <span class="d-block d-xl-none">Details</span>
                                            <span class="d-none d-xl-block">Name</span>
                                        </th>
                                        <th class="text-center d-none d-xl-table-cell" style="width: 10%">Purpose</th>
                                        <th class="text-center d-none d-xl-table-cell" style="width: 20%">From</th>
                                        <th class="text-center d-none d-xl-table-cell" style="width: 20%">To</th>
                                        <th class="text-center d-none d-xl-table-cell" style="width: 10%">Submitted by</th>
                                        <th class="text-center d-none d-xl-table-cell" style="width: 10%">Status</th>
                                        <th class="text-center" style="width: 10%">Action</th>
                                    </tr>
                                    @foreach ($ste_arr as $ste)
                                        @php
                                            if($ste['status'] == 'Approved'){
                                                $badge = 'success';
                                            }else{
                                                $badge = 'primary';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-center d-none d-xl-table-cell">
                                                {{ $ste['creation'] }}
                                            </td>
                                            <td class="text-center">
                                                {{ $ste['name'] }}

                                                <div class="d-block d-xl-none text-left">
                                                    <b>From: </b> {{ $ste['source_warehouse'] }} <br>
                                                    <b>To: </b> {{ $ste['target_warehouse'] }} <br>
                                                    <b>Purpose: </b> {{ $ste['transfer_as'] }} <br>
                                                    <b>Date: </b> {{ $ste['creation'] }}
                                                </div>
                                            </td>
                                            <td class="text-center d-none d-xl-table-cell">{{ $ste['transfer_as'] }}</td>
                                            <td class="text-center d-none d-xl-table-cell">{{ $ste['source_warehouse'] }}</td>
                                            <td class="text-center d-none d-xl-table-cell">{{ $ste['target_warehouse'] }}</td>
                                            <td class="text-center d-none d-xl-table-cell">{{ $ste['submitted_by'] }}</td>
                                            <td class="text-center d-none d-xl-table-cell">
                                                <span class="badge badge-{{ $badge }}">{{ $ste['status'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="#" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal" style="font-size: 10pt;">
                                                    View Items
                                                </a>
                                                <span class="badge badge-{{ $badge }} d-xl-none">{{ $ste['status'] }}</span>
                                                  <!-- Modal -->
                                                <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="background-color: #001F3F; color: #fff">
                                                                <div class="row text-left">
                                                                    <div class="col-12">
                                                                        <h5 id="exampleModalLabel"><b>{{ $ste['name'] }}</b></h5>
                                                                    </div>
                                                                    <div class="col-12" style="font-size: 8pt;">
                                                                        <span class="font-italic"><b>Source: </b> {{ $ste['source_warehouse'] }}</span>
                                                                    </div>
                                                                    <div class="col-12" style="font-size: 8pt;">
                                                                        <span class="font-italic"><b>Target: </b> {{ $ste['target_warehouse'] }}</span>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <table class="table table-bordered">
                                                                    <tr>
                                                                        <th class="text-center" width="50%">Item</th>
                                                                        <th class="text-center">Stock Qty</th>
                                                                        <th class="text-center">Qty to Transfer</th>
                                                                    </tr>
                                                                    @foreach ($ste['items'] as $item)
                                                                        <tr>
                                                                            <td class="text-center p-0">
                                                                                <div class="row">
                                                                                    <div class="col-4">
                                                                                        <picture>
                                                                                            <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                                                            <source srcset="{{ asset('storage/'.$item['image']) }}" type="image/jpeg">
                                                                                            <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" class="w-100">
                                                                                        </picture>
                                                                                    </div>
                                                                                    <div class="col-5" style="display: flex; justify-content: center; align-items: center;">
                                                                                        <b>{{ $item['item_code'] }}</b>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <b>{{ $item['consigned_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <b>{{ $item['transfer_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                                </td>
                                                                        </tr>
                                                                        <tr class="p-2">
                                                                            <td colspan=3 class="text-justify">
                                                                                <div class="item-description">
                                                                                    {{ strip_tags($item['description']) }}
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                                <div class="float-left m-2">Total: <b>{{ $stock_entry->total() }}</b></div>
                                <div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $stock_entry->links('pagination::bootstrap-4') }}</div>
                            </div>
                            <!-- Stock Transfers -->

                            <!-- Damaged Items -->
                            <div id="damaged_items" class="container-fluid tab-pane" style="padding: 8px 0 0 0;">
                                <div id="accordion2" class="p-0 m-0" style="border: none !important">
                                    <div class="card p-0 m-0" style="border: none !important">
                                        <div class="card-header p-2 d-block d-xl-none" id="headingOne">
                                            <button class="btn btn-link p-0" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" style="font-size: 10pt">
                                                <i class="fa fa-filter"></i>&nbsp;Filters
                                            </button>
                                        </div>
                                    
                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingOne" data-parent="#accordion2">
                                            <div class="card-body">
                                                <form action="/stocks_report/list" method="GET">
                                                    <div class="row p-1 mt-1 mb-1">
                                                        <div class="col-12 col-xl-3">
                                                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search Item" style='font-size: 10pt'/>
                                                        </div>
                                                        <div class="col-12 mt-2 mt-xl-0 col-xl-3">
                                                            @php
                                                                $statuses = ['For Approval', 'Approved', 'Cancelled'];
                                                            @endphp
                                                            <select class="form-control" name="store" id="consignment-store-select">
                                                                <option value="">Select Store</option>
                                                                @foreach ($statuses as $status)
                                                                <option value="{{ $status }}">{{ $status }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-12 mt-2 mt-xl-0 col-xl-1">
                                                            <button class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <table class="table table-striped" style="font-size: 10pt;">
                                        <thead>
                                            <th class="text-center d-none d-xl-table-cell" style="width: 10%;">Date</th>
                                            <th class="text-center" style="width: 35%;">Item Description</th>
                                            <th class="text-center d-none d-xl-table-cell" style="width: 10%;">Qty</th>
                                            <th class="text-center d-none d-xl-table-cell" style="width: 20%;">Store</th>
                                            <th class="text-center d-none d-xl-table-cell" style="width: 20%;">Damage Description</th>
                                            <th class="text-center d-none d-xl-table-cell" style="width: 5%;">-</th>
                                        </thead>
                                        @forelse ($items_arr as $i => $item)
                                            <tr>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['creation'] }}</td>
                                                <td class="p-1 text-justify align-middle">
                                                    <div class="d-flex flex-row align-items-center">
                                                        <div class="p-1">
                                                            <picture>
                                                                <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                                <source srcset="{{ asset('storage'.$item['image']) }}" type="image/jpeg">
                                                                <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" width="70">
                                                            </picture>
                                                        </div>
                                                        <div class="p-1">
                                                            <span class="d-block font-weight-bold">{{ $item['item_code'] }}</span>
                                                            <small class="d-block item-description">{!! strip_tags($item['description']) !!}</small>
        
                                                            <small class="d-block mt-2">Created by: <b>{{ $item['promodiser'] }}</b></small>
                                                        </div>
                                                    </div>
                                                    <div class="d-block d-xl-none" style="font-size: 9pt;">
                                                        <b>Damaged Qty: </b>{{ $item['damaged_qty'] }}&nbsp;<small>{{ $item['uom'] }}</small> <br>
                                                        <b>Store: </b> {{ $item['store'] }} <br>
                                                        <b>Damage Description: </b> {{ $item['damage_description'] }} <br>
                                                        <b>Date: </b> {{ $item['creation'] }}
                                                    </div>
                                                </td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                                                    <span class="d-block font-weight-bold">{{ $item['damaged_qty'] }}</span>
                                                    <small>{{ $item['uom'] }}</small>
                                                </td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['store'] }}</td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['damage_description'] }}</td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                                                    <a href="#" class="btn btn-primary btn-sm"><i class="fas fa-retweet"></i></a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No record(s) found.</td>
                                            </tr>
                                        @endforelse
                                    </table>
                                    <div class="float-left m-2">Total: <b>{{ $damaged_items->total() }}</b></div>
                                    <div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $damaged_items->links('pagination::bootstrap-4') }}</div>
                                </div>
                            </div>
                            <!-- Damaged Items -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .morectnt span {
        display: none;
    }
    .modal{
        background-color: rgba(0,0,0,0.4);
    }
    table {
        table-layout: fixed;
        width: 100%;   
    }
    #first-row, #second-row{
        width: 10%;
    }
    @media (max-width: 575.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
  	@media (max-width: 767.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
	@media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
	}
</style>
@endsection

@section('script')
<script>
    var showTotalChar = 110, showChar = "Show more", hideChar = "Show less";
    $('.item-description').each(function() {
        var content = $(this).text();
        if (content.length > showTotalChar) {
            var con = content.substr(0, showTotalChar);
            var hcon = content.substr(showTotalChar, content.length - showTotalChar);
            var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="showmoretxt">' + showChar + '</a></span>';
            $(this).html(txt);
        }
    });

    $(".showmoretxt").click(function(e) {
        e.preventDefault();
        if ($(this).hasClass("sample")) {
            $(this).removeClass("sample");
            $(this).text(showChar);
        } else {
            $(this).addClass("sample");
            $(this).text(hideChar);
        }

        $(this).parent().prev().toggle();
        $(this).prev().toggle();
        return false;
    });

    $('#consignment-store-select').select2({
        placeholder: "Select Store",
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });

    function show_modal(modal){
        $(modal).modal('show');
    }

    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
        $('#collapseOne').removeClass('show');
        $('#collapseTwo').removeClass('show');
    }else{ // desktop
        $('#collapseOne').addClass('show');
        $('#collapseTwo').addClass('show');
    }

    $('#source-warehouse').select2({
        placeholder: 'Source Warehouse',
        allowClear: true,
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });

    $('#target-warehouse').select2({
        placeholder: 'Target Warehouse',
        allowClear: true,
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
</script>
@endsection