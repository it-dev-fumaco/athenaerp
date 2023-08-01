@extends('layout', [
    'namePage' => 'Stock Transfers Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-3">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-7 col-lg-6 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Transfers</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-0">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <div class="row p-2">
                                <div class="col-12">
                                    <ul class="nav nav-pills m-0" role="tablist">
                                        <li class="nav-item mr-1 border rounded">
                                            <a class="nav-link active font-responsive" data-purpose='Store Transfer' data-toggle="tab" href="#stock_transfers">Stock-to-Store Transfer <span class="badge badge-warning" id="store-transfer-count">0</span></a>
                                        </li>
                                        <li class="nav-item mr-1 border rounded">
                                            <a class="nav-link font-responsive" data-purpose='Pull Out' data-toggle="tab" href="#pull-out">Item Pull Out <span class="badge badge-warning" id="pullout-count">0</span></a>
                                        </li>
                                        <li class="nav-item mr-1 border rounded">
                                            <a class="nav-link font-responsive" data-purpose='Item Return' data-toggle="tab" href="#item-return">Item Return <span class="badge badge-warning" id="return-count">0</span></a>
                                        </li>
                                        <li class="nav-item mr-1 border rounded">
                                            <a class="nav-link font-responsive" data-purpose='Damaged Items' data-toggle="tab" href="#damaged_items">Damaged Item List</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="tab-content">
                                @php
                                    $status = [
                                        ['title' => 'Select Status', 'value' => ''],
                                        ['title' => 'Pending', 'value' => 'Pending'],
                                        ['title' => 'Completed', 'value' => 'Completed'],
                                        ['title' => 'Cancelled', 'value' => 'Cancelled'],
                                    ];
                                @endphp 
                                <!-- Stock Transfers -->
                                <div id="stock_transfers" class="tab-pane active">
                                    <!-- Stock Transfers -->
                                    <form id="stock-transfer-filter">
                                        <div id="accordion" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 12px;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="row p-2">
                                                    <div class="col-3">
                                                        <input type="text" name="q" class="form-control" placeholder='Search' style='font-size: 12px;' />
                                                    </div>
                                                    <div class="col-3">
                                                        <select name="source_warehouse" id="source-warehouse" class="form-control"></select>
                                                    </div>
                                                    <div class="col-3">
                                                        <select name="target_warehouse" id="target-warehouse" class="form-control"></select>
                                                    </div>
                                                    <div class="col-2">
                                                        <select name="status" class="form-control" style="font-size: 12px;">
                                                            @foreach ($status as $s)
                                                            @php
                                                                $selected = null;
                                                                if(request('tab1_status') == 'All'){
                                                                    $selected = $loop->first ? 'selected' : null;
                                                                }else{
                                                                    $selected = $s['value'] == request('tab1_status') ? 'selected' : null;
                                                                }
                                                            @endphp
                                                            <option value="{{ $s['value'] }}" {{ $selected }}>{{ $s['title'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="stock-transfer-div" class="p-1"></div>
                                </div>
                                <!-- Stock Transfers -->

                                <!-- Pull Out -->
                                <div id="pull-out" class="tab-pane">
                                    <!-- Stock Transfers -->
                                    <form id="pull-out-filter">
                                        <div id="accordion" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree" style="font-size: 12px;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                            <div id="collapseThree" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="row p-2">
                                                    <div class="col-3">
                                                        <input type="text" name="q" class="form-control" placeholder='Search' style='font-size: 12px;'/>
                                                    </div>
                                                    <div class="col-3">
                                                        <select name="source_warehouse" id="source-warehouse-pull-out" class="form-control"></select>
                                                    </div>
                                                    <div class="col-2">
                                                        <select name="status" class="form-control" style="font-size: 12px;">
                                                            @foreach ($status as $s)
                                                            @php
                                                                $selected = null;
                                                                if(request('tab1_status') == 'All'){
                                                                    $selected = $loop->first ? 'selected' : null;
                                                                }else{
                                                                    $selected = $s['value'] == request('tab1_status') ? 'selected' : null;
                                                                }
                                                            @endphp
                                                            <option value="{{ $s['value'] }}" {{ $selected }}>{{ $s['title'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="pull-out-div" class="p-1"></div>
                                </div>
                                <!-- Pull Out -->

                                <!-- Item Return -->
                                <div id="item-return" class="tab-pane">
                                    <form id="item-return-filter">
                                        <div id="accordion2" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseFour" aria-expanded="true" aria-controls="collapseThree" style="font-size: 12px;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                            <div id="collapseFour" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="row p-2">
                                                    <div class="col-3">
                                                        <input type="text" name="q" class="form-control" placeholder='Search' style='font-size: 12px;'/>
                                                    </div>
                                                    <div class="col-3">
                                                        <select name="target_warehouse" id="target-warehouse-item-return" class="form-control"></select>
                                                    </div>
                                                    <div class="col-2">
                                                        <select name="status" class="form-control" style="font-size: 12px;">
                                                            @foreach ($status as $s)
                                                            @php
                                                                $selected = null;
                                                                if(request('tab1_status') == 'All'){
                                                                    $selected = $loop->first ? 'selected' : null;
                                                                }else{
                                                                    $selected = $s['value'] == request('tab1_status') ? 'selected' : null;
                                                                }
                                                            @endphp
                                                            <option value="{{ $s['value'] }}" {{ $selected }}>{{ $s['title'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="item-return-div" class="p-1"></div>
                                </div>
                                <!-- Item Return -->

                                <!-- Damaged Items -->
                                <div id="damaged_items" class="tab-pane">
                                    <form id="damaged-items-filter">
                                        <div id="accordion2" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" style="font-size: 10pt;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                        </div>
                                        
                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingOne" data-parent="#accordion2">
                                            <div class="row p-2">
                                                <div class="col-3 mt-2 mt-lg-0">
                                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search Item" style='font-size: 10pt'/>
                                                </div>
                                                <div class="col-3">
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
                                                <div class="col-1">
                                                    <button class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="damaged-item-div" class="p-1"></div>
                                </div>
                                <!-- Damaged Items -->
                            </div>
                        </div>
                        <!-- Nav tabs -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="success-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body p-3" style="font-size: 11px;">
                <form class="d-none"></form>
                <div class="d-flex flex-row align-items-center">
                    <div class="col-12 text-center">
                        <center>
                            <p class="text-success text-center mb-0" style="font-size: 4rem;">
                                <i class="fas fa-check-circle"></i>
                            </p>
                        </center>
                        <h6 class="d-block">Stock Entry has been created.</h6>
                        <span class="d-block">Reference Stock Entry: <a class="text-dark font-weight-bold" href="#" id="reference-stock-entry-text"></a></span>
                        <button class="btn btn-secondary btn-sm mt-3" type="button" id="success-modal-btn">&times; Close</button>
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
    
    $('#stock-transfer-filter').submit(function (e) {
        e.preventDefault();
        load_stock_transfer('Store Transfer', 1);
    });

    $('#pull-out-filter').submit(function (e) {
        e.preventDefault()
        load_stock_transfer('Pull Out', 1);
    });

    $('#item-return-filter').submit(function (e) {
        e.preventDefault()
        load_stock_transfer('Item Return', 1);
    });

    $('#damaged-items-filter').submit(function (e) {
        e.preventDefault();
        load_damaged_items();
    });

    load_stock_transfer('Store Transfer', 1);
    function load_stock_transfer(purpose, page){
        switch (purpose) {
            case 'Pull Out':
                var div = '#pull-out-div';
                var filter = '#pull-out-filter';
                break;
            case 'Item Return':
                var div = '#item-return-div';
                var filter = '#item-return-filter';
                break;
            default:
                var div = '#stock-transfer-div';
                var filter = '#stock-transfer-filter';
                break;
        }
        $.ajax({
            type: 'GET',
            url: '/stocks_report/list?purpose=' + purpose + '&page=' + page,
            data: $(filter).serialize(),
            success: function(response){
                $(div).html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification("danger", 'An error occured. Please contact your system administrator.', "fa fa-info");
            }
        });
    }

    load_damaged_items();
    function load_damaged_items(page){
        $.ajax({
            type: 'GET',
            url: '/damaged_items_list?page=' + page,
            data: $('#damaged-items-filter').serialize(),
            success: function(response){
                $('#damaged-item-div').html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification("danger", 'An error occured. Please contact your system administrator.', "fa fa-info");
            }
        });
    }

    $(document).on('click', '#consignment-stock-entry-pagination a', function(event){
        event.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        var purpose = $(this).data('consignment-purpose');
        load_stock_transfer(purpose, page);
    });

    $(document).on('click', '#damaged-items-pagination a', function(event){
        event.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        load_damaged_items(page);
    });

    $(document).on('click', '.nav-link', function (e){
        var purpose = $(this).data('purpose');
        load_stock_transfer(purpose, 1);
    });

    countStockTransfer('Store Transfer', '#store-transfer-count');
    countStockTransfer('Pull Out', '#pullout-count');
    countStockTransfer('Item Return', '#return-count');
    function countStockTransfer(purpose, el){
        $.ajax({
            type: 'GET',
            url: '/countStockTransfer/' + purpose,
            success: function(response){
                $(el).html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification("danger", 'An error occured. Please contact your system administrator.', "fa fa-info");
            }
        });
    }

    function showNotification(color, message, icon){
        $.notify({
            icon: icon,
            message: message
        },{
            type: color,
            timer: 500,
            z_index: 1060,
            placement: {
                from: 'top',
                align: 'center'
            }
        });
    }

    $(document).on('submit', '.generate-stock-entry-form', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '/generate_stock_transfer_entry',
            data: $(this).serialize(),
            success: function(response){
                $('#success-modal').modal('show');
                $('#reference-stock-entry-text').attr('href', response.data.link).text(response.data.stock_entry_name);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showNotification("danger", 'An error occured. Please contact your system administrator.', "fa fa-info");
            }
        });
    });

    $('#success-modal-btn').click(function (e) {
        e.preventDefault();
        $('.modal').modal('hide');
    });

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
        $('#collapseThree').removeClass('show');
    }else{ // desktop
        $('#collapseOne').addClass('show');
        $('#collapseTwo').addClass('show');
        $('#collapseThree').addClass('show');
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

    $('#target-warehouse-item-return').select2({
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


    $('#source-warehouse-pull-out').select2({
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

    $('#target-warehouse-pull-out').select2({
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