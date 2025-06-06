@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        @if (Auth::user()->user_group == 'Director')
        <ul class="nav nav-pills mb-2 mt-2">
            <li class="nav-item p-0">
                <a class="nav-link font-responsive text-center" href="/">In House Warehouse Transaction</a>
            </li>
            <li class="nav-item p-0">
                <a class="nav-link active font-responsive text-center" href="/consignment_dashboard">Consignment Dashboard</a>
            </li>
        </ul>
        @endif
        <div class="container-fluid">
            <div class="row p-0 mr-0 ml-0 mb-0 mt-2">
                <div class="col-12 m-0 p-0">
                    <div class="row p-0 m-0">
                        <div class="col-6 col-md-3 p-1">
                            <a href="/inventory_audit">
                                <div class="info-box bg-gradient-info m-0">
                                    <div class="info-box-content p-0">
                                        <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                                            <div class="p-1 text-center col-4" style="font-size: 30px !important;">{{ number_format($total_pending_inventory_audit) }} <small class="d-block" style="font-size: 8pt; margin-top: -5px;">Pending</small></div>
                                            <div class="p-1 text-left col-8">Inventory Report</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 p-1">
                            <a href="/view_consignment_deliveries">
                                <div class="info-box bg-gradient-primary m-0">
                                    <div class="info-box-content p-0">
                                        <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                                            <div class="p-1 text-center col-4" style="font-size: 30px !important;">{{ number_format($pending_to_receive) }} <small class="d-block" style="font-size: 8pt; margin-top: -5px;">Pending Item(s)</small></div>
                                            <div class="p-1 text-left col-8">To Receive</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    
                        <div class="col-6 col-md-3 p-1">
                            <a href="/stocks_report/list">
                                <div class="info-box bg-gradient-warning m-0">
                                    <div class="info-box-content p-0">
                                        <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                                            <div class="p-1 text-center col-4" style="font-size: 30px !important;">{{ number_format($total_stock_transfers) }} <small class="d-block" style="font-size: 8pt; margin-top: -5px;">For Approval</small></div>
                                            <div class="p-1 text-left col-8">Stock Transfers</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 p-1">
                            <a href="/consignment/replenish" style="color: inherit">
                                <div class="info-box bg-gradient-secondary m-0">
                                    <div class="info-box-content p-0">
                                        <div class="d-flex flex-row p-0 m-0 align-items-center justify-content-around">
                                            <div class="p-1 text-center col-4" style="font-size: 30px !important;">{{ number_format($total_consignment_orders) }} <small class="d-block" style="font-size: 8pt; margin-top: -5px;">Pending</small></div>
                                            <div class="p-1 text-left col-8">Consignment Orders</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="card card-secondary card-outline">
                                <div class="card-header p-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="d-flex flex-row align-items-center">
                                                <div class="p-0 col-5">
                                                    <ul class="nav nav-pills custom-navpill">
                                                        <li class="nav-item col-6 p-0">
                                                            <a class="nav-link active font-responsive text-center rounded-0" style="height: 60px; padding-top: 15px;" data-toggle="pill" href="#pending-content" role="tab" href="#">Sales Report</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="p-0 col-2">
                                                    <div class="text-center">
                                                        <a href="/consignment/branches" style="text-transform: none; text-decoration: none; color: #212545">
                                                            <p class="text-center m-0 font-responsive">
                                                                <span class="d-inline-block font-weight-bolder" style="font-size: 1.2rem;">{{ count($active_consignment_branches) }}</span>
                                                                <span class="d-inline-block text-muted" style="font-size: .8rem;">/ {{ count($consignment_branches) }}</span>
                                                            </p>
                                                            <span class="d-block" style="font-size: 9pt;">Active Store</span>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="p-0 col-2">
                                                    <a href="/view_promodisers" style="color: inherit;">
                                                        <div class="text-center">
                                                            <p class="text-center font-weight-bolder m-0 font-responsive" style="font-size: 1.2rem;">{{ ($promodisers) }}</p>
                                                            <span class="d-block" style="font-size: 9pt;">Promodiser(s)</span>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="p-0 col-3 m-0">
                                                    <a href="/beginning_inv_list" style="color: inherit;">
                                                        <div class="d-flex flex-row align-items-center m-0 p-0">
                                                            <div class="p-0 m-0">
                                                                <div class="skills_section text-right m-0 p-0">
                                                                    <div class="skills-area m-0 p-0">
                                                                        <div class="single-skill w-100 mb-1">
                                                                            <div class="circlechart" data-percentage="{{ $beginning_inv_percentage }}">
                                                                                <svg class="circle-chart" viewBox="0 0 33.83098862 33.83098862"><circle class="circle-chart__background" cx="16.9" cy="16.9" r="15.9"></circle><circle class="circle-chart__circle success-stroke" stroke-dasharray="92,100" cx="16.9" cy="16.9" r="15.9"></circle></svg>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="p-0 m-0">
                                                                <div class="text-center">
                                                                    <span class="d-block text-muted" style="font-size: 1.2rem;">{{ $consignment_branches_with_beginning_inventory }} / {{ count($consignment_branches) }}</span>
                                                                    <span class="d-block" style="font-size: 8pt;">Beginning Inventory Completion</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
        
                                            <div class="tab-content custom-tabcontent">
                                                <div class="tab-pane fade show active" id="pending-content" role="tabpanel" aria-labelledby="pending-tab">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="p-1">
                                                                <div class="col-9 text-white p-2">
                                                                    <div class="row">
                                                                        <div class="col-4 d-flex justify-content-center align-items-center">
                                                                            <label>Select Date Range</label>
                                                                        </div>
                                                                        <div class="col-8">
                                                                            <input type="text" class="form-control date-range">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-2 offset-4 pr-3 d-flex justify-content-end align-items-center">
                                                            <a href="/consignment_import_tool" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-external-link-alt"></i> Import Sales Report</a>
                                                        </div>
                                                    </div>
                                                    <div id="beginning-inventory-list-el" class="p-0"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card card-primary card-outline">
                                <div class="card-header p-2">
                                    <h5>Deliveries Pending to Receive</h5>
                                </div>
                                <div class="card-body p-2">
                                    <div class="d-flex flex-row align-items-center mt-2">
                                        <div class="p-1 col-4">
                                            <select class="form-control" name="store" id="consignment-store-select">
                                                <option value="">Select Store</option>
                                            </select>
                                        </div>
                                        <div class="p-1">
                                            <button class="btn btn-primary" type="button" id="to-receive-search"><i class="fas fa-search"></i> Search</button>
                                        </div>
                                        <div class="p-1">
                                            <button type="button" class="btn btn-secondary reload-to-receive-table"><i class="fas fa-undo"></i></button>
                                        </div>
                                    </div>
                                    <div id="pending-to-receive-table" class="overflow-auto"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if (session()->has('error'))
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h5 class="modal-title" id="exampleModalLabel"><i class="fa fa-info-circle"></i> Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ session()->get('error') }}
                </div>
            </div>
        </div>
    </div>
@endif
@if (session()->has('success'))
    @php
        $received = session()->get('success');
    @endphp
    <div class="modal fade" id="receivedDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h5 class="modal-title" id="exampleModalLabel">
                        @switch($received['action'])
                            @case('received')
                                Item(s) Received
                                @break
                            @case('canceled')
                                Stock Transfer Cancelled
                                @break
                            @default
                                Delivered Item(s)
                        @endswitch
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: #fff">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 10pt;">
                    <div class="row">
                        <div class="col-2">
                            <center>
                                <p class="text-success text-center mb-0" style="font-size: 4rem;">
                                    <i class="fas fa-check-circle"></i>
                                </p>
                            </center>
                        </div>
                        <div class="col-10">
                            <span>{{ $received['message'] }}</span> <br>
                            <span>Branch: <b>{{ $received['branch'] }}</b></span> <br>
                            <span>Total Amount: <b>₱ {{ number_format(collect($received)->sum('amount'), 2) }}</b></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
<style>
    .custom-navpill .nav-item .active{
        background-color:rgba(58, 112, 170, 0.905);
    }
    .custom-tabcontent .tab-pane.active{
        background-color:rgba(58, 112, 170, 0.905);
    }
    .circle-chart {
        width: 80px;
        height: 50px;
    }
    .circle-chart__circle {
        stroke: #00acc1;
        stroke-width: 2;
        stroke-linecap: square;
        fill: none;
        animation: circle-chart-fill 2s reverse; /* 1 */ 
        transform: rotate(-90deg); /* 2, 3 */
        transform-origin: center; /* 4 */
    }
    .circle-chart__circle--negative {
        transform: rotate(-90deg) scale(1,-1); /* 1, 2, 3 */
    }
    .circle-chart__background {
        stroke: #efefef;
        stroke-width: 2;
        fill: none; 
    }
    .circle-chart__info {
        animation: circle-chart-appear 2s forwards;
        opacity: 0;
        transform: translateY(0.3em);
    } 
    .circle-chart__percent {
        alignment-baseline: central;
        text-anchor: middle;
        font-size: 7px;
    }
    .circle-chart__subline {
        alignment-baseline: central;
        text-anchor: middle;
        font-size: 3px;
    }
    .success-stroke {
        stroke: #00C851;
    }
    .warning-stroke {
        stroke: #ffbb33;
    }
    .danger-stroke {
        stroke: #ff4444;
    }
    @keyframes circle-chart-fill {
        to { stroke-dasharray: 0 100; }
    }
    @keyframes circle-chart-appear {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .skills_section{
        width: 100%;
        margin: 0 auto;
        margin-bottom: 80px;
    }
    .skills-area {
        margin-top: 5%;
        display: flex;
        flex-wrap: wrap;
    }
    .single-skill {
        width: 25%;
        margin-bottom: 80px;
    }
    .success-stroke {
        stroke: rgb(129, 86, 252);
    }
    .circle-chart__background {
        stroke: #ede4e4;
        stroke-width: 2;
    }
    /* Extra small devices (portrait phones, less than 576px) */
    @media (max-width: 575.98px) {
        .skill-icon {
            width: 50%;
        }
        .skill-icon i {
            font-size: 70px;
        }
        .single-skill {
            width: 50%;
        }
    }
</style>
@endsection

@section('script')
<script>
    $('#errorModal').modal('show');
    $('#receivedDeliveryModal').modal('show');

    function makesvg(percentage, inner_text=""){
        var abs_percentage = Math.abs(percentage).toString();
        var percentage_str = percentage.toString();
        var classes = "";
        if(percentage < 0){
            classes = "danger-stroke circle-chart__circle--negative";
        } else if(percentage > 0 && percentage <= 30){
            classes = "warning-stroke";
        } else{
            classes = "success-stroke";
        }

        var svg = '<svg class="circle-chart" viewbox="0 0 33.83098862 33.83098862" xmlns="http://www.w3.org/2000/svg">'
            + '<circle class="circle-chart__background" cx="16.9" cy="16.9" r="15.9" />'
            + '<circle class="circle-chart__circle '+classes+'"'
            + 'stroke-dasharray="'+ abs_percentage+',100"    cx="16.9" cy="16.9" r="15.9" />'
            + '<g class="circle-chart__info">'
            + '   <text class="circle-chart__percent" x="17.9" y="19.5">'+percentage_str+'%</text>';

        if(inner_text){
            svg += '<text class="circle-chart__subline" x="16.91549431" y="22">'+inner_text+'</text>'
        }

        svg += ' </g></svg>';

        return svg
    }

    (function( $ ) {
        $.fn.circlechart = function() {
            this.each(function() {
                var percentage = $(this).data("percentage");
                var inner_text = $(this).text();
                $(this).html(makesvg(percentage, inner_text));
            });
            return this;
        };
    }( jQuery ));

    $(function () {
        $('.circlechart').circlechart();

        $(document).on('change', '#hide-zero-check', function() {
            loadSalesReport();
        });

        $('#year-filter').change(function(){
            loadSalesReport();
        });

        $(".date-range").daterangepicker({
            placeholder: 'Select Duration',
            startDate: moment().startOf('month').format('YYYY-MMM-DD'),
            endDate: moment().format('YYYY-MMM-DD'),
            locale: {
                format: 'YYYY-MMM-DD',
                separator: " to ",
            }
        });

        console.log(moment().startOf('month').format('YYYY-MMM-DD'));

        $(".date-range").on('apply.daterangepicker', function (ev, picker) {
            var duration = picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD');
            $(this).val(duration);

            loadSalesReport();
        });

        loadSalesReport();
        function loadSalesReport() {
            $.ajax({
                type: "GET",
                url: "/consignment_sales_report",
                data: {
                    daterange: $('.date-range').val()
                },
                success: function (data) {
                    $('#beginning-inventory-list-el').html(data);
                }
            });
        }

        $('#consignment-audit-select').select2({
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

        $(document).on('submit', '#search-audit-form', function(e) {
            e.preventDefault();

            loadData();
        });

        $(document).on('click', '.reload-to-receive-table', function(e) {
            e.preventDefault();
            $('#consignment-store-select').empty().trigger('change');
            loadPendingToReceive('', 1);
        });

        $(document).on('click', '#to-receive-search', function (){
            var warehouse = $('#consignment-store-select').val();
            warehouse = warehouse ? warehouse : '';
            loadPendingToReceive(warehouse, 1);
        });

        $(document).on('click', '#to-receive-pagination a', function(event){
            event.preventDefault();
            var warehouse = $('#consignment-store-select').val();
            warehouse = warehouse ? warehouse : '';

            var page = $(this).attr('href').split('page=')[1];
            loadPendingToReceive(warehouse, page);
        });

        $(document).on('click', '.submit-btn', function (){
            validate_submit($(this).data('reference'));
        });

        function validate_submit(reference){
            var err = 0;
            
            $('.' + reference + '-price').each(function (){
                if($(this).val() == '' || $(this).val() <= 0){
                    $(this).css('border', '1px solid red');
                    err = 1;
                }else{
                    $(this).css('border', '1px solid #DEE2E6');
                }
            });
            
            if(err == 1){
                showNotification("danger", 'Item price cannot be less than or equal to 0.', "fa fa-info");
            }else{
                $('#' + reference + '-form').submit();
            }
        }

        $(document).on('submit', '.deliveries-form', function (e){
            e.preventDefault();
            var modal = $(this).data('modal-container');
            $.ajax({
                type: 'GET',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if(response.success){
                        loadPendingToReceive('', '{{ request()->has("page") ? request()->get("page") : 1 }}');
                        $(modal).modal('hide');
                        showNotification("success", response.message, "fa fa-check");
                    }else{
                        showNotification("danger", response.message, "fa fa-info");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
                }
            });
        });

        loadPendingToReceive('', 1);
        function loadPendingToReceive(warehouse, page){
            $.ajax({
				type: "GET",
				url: "/view_consignment_deliveries?page=" + page,
                data: {
                    store: warehouse,
                    status: 'To Receive'
                },
				success: function (response) {
					$('#pending-to-receive-table').html(response);
				}
			});
        }

        function loadData() {
            loadDeliveries();
            loadReturns();
            // loadSales();
        }

        function loadDeliveries() {
			$.ajax({
				type: "GET",
				url: "/get_audit_deliveries",
				data: $('#search-audit-form').serialize(),
				success: function (response) {
					$('#deliveries-content').html(response);
				}
			});
		}

        function loadReturns() {
			$.ajax({
				type: "GET",
				url: "/get_audit_returns",
				data: $('#search-audit-form').serialize(),
				success: function (response) {
					$('#returns-content').html(response);
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
    });
</script>
@endsection