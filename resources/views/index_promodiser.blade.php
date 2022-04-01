@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-3">
				<div class="col-sm-12">
                    <div class="card card-secondary card-outline">
                        <div id="first-chart" class="d-none" data-el="{{ str_slug($assigned_consignment_store[0], '-') }}" data-store="{{ $assigned_consignment_store[0] }}"></div>
                        <div class="card-header d-none d-xl-block"> <!-- Desktop Chart Control -->
                            <ul class="nav nav-pills p-2">
                                @foreach ($assigned_consignment_store as $n => $store)
                                <li class="nav-item">
                                    <a class="c-store font-responsive nav-link {{ $loop->first ? 'active' : '' }}" href="#tab{{ $n }}" data-toggle="tab" data-el="{{ str_slug($store, '-') }}">{{ $store }}</a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="card-header d-block d-xl-none"> <!-- Mobile Chart Control -->
                            <select class="form-control custom-select" id="chart-select">
                                @foreach ($assigned_consignment_store as $n => $store)
                                    <option data-el="{{ str_slug($store, '-') }}" data-store="{{ $store }}" {{ $loop->first ? 'selected' : null }} data-tab="#tab{{ $n }}">{{ $store }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="card-body p-0">
                            <div class="tab-content p-0">
                                @foreach ($assigned_consignment_store as $m => $store)
                                <div class="tab-pane p-0 {{ $loop->first ? 'active' : '' }}" id="tab{{ $m }}">
                                    <div class="row m-0 p-0">
                                        <div class="col-md-12">
                                            <div class="position-relative m-4">
                                                <canvas id="sales-chart-{{ str_slug($store, '-') }}" class="canvas"></canvas>
                                            </div>
                                            <div class="container-fluid text-center">
                                                <span style="font-size: 20px;"><b>Inventory Stocks</b></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mt-2">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Search..." id="s{{ str_slug($store, '-') }}" autocomplete="off">
                                                <span class="input-group-append">
                                                    <button type="button" class="btn btn-secondary c-store-search" data-el="{{ str_slug($store, '-') }}" data-warehouse="{{ $store }}">Search</button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-12 p-2" id="{{ str_slug($store, '-') }}"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
    .canvas{
        height: 400px;
    }
    @media (max-width: 575.98px) {
        .canvas{
            height: 200px;
        }
    }
    @media (max-width: 767.98px) {
        .canvas{
            height: 200px;
        }
    }
</style>
@endsection

@section('script')

<script>
$(document).ready(function() {
    load_first_chart();
    function load_first_chart(){
        var el = $('#first-chart').data('el');
        var warehouse = $('#first-chart').data('store');

        get_item_stock(el, warehouse);
        consignment_chart('sales-chart-' + el, warehouse);
    }

    $('#chart-select').change(function(){ // Mobile chart control
        var el = $(this).find(':selected').data('el');
        var warehouse = $(this).find(':selected').data('store');
        var tab = $(this).find(':selected').data('tab');

        change_tab(tab);
        get_item_stock(el, warehouse);
        consignment_chart('sales-chart-' + el, warehouse);
    });

    $('.c-store').click(function(){ // Desktop chart control
        var warehouse = $(this).text();
        var el = $(this).data('el');

        get_item_stock(el, warehouse);
        consignment_chart('sales-chart-' + el, warehouse);
    });

    function change_tab(tab){
        $('.tab-pane').removeClass('active');
        $(tab).addClass('active');
    }

    function get_item_stock(el, warehouse, q = null, page = null) {
        $.ajax({
            type: "GET",
            url: "/consignment_stock/" + warehouse + "?page=" + page,
            data: {q},
            success: function (data) {
                $('#' + el).html(data);
            }
        });
	}

    $(document).on('click', '.c-store-pagination a', function(event){
        event.preventDefault();
        var page = $(this).attr('href').split('page=')[1];
        var c_div = $(this).closest('.c-store-pagination').eq(0);
        var el = c_div.data('el');
        var warehouse = c_div.data('warehouse');
        var query = $('#s' + el).val();
      
        get_item_stock(el, warehouse, query, page);
    });

    $(document).on('click', '.c-store-search', function() {
        var el = $(this).data('el');
        var warehouse = $(this).data('warehouse');
        var query = $('#s' + el).val();
      
        get_item_stock(el, warehouse, query);
    });

    var ticksStyle = {
        fontColor: '#495057',
        fontStyle: 'bold'
    }
    
    var mode = 'index';
    var intersect = true;

    function consignment_chart(el, warehouse) {
        $.ajax({
            type: "GET",
            url: "/consignment_sales/" + warehouse,
            success: function (data) {
                // eslint-disable-next-line no-unused-vars
                new Chart($('#' + el), {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            backgroundColor: '#0774C0',
                            borderColor: '#0774C0',
                            data: data.data
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            mode: mode,
                            intersect: intersect
                        },
                        hover: {
                            mode: mode,
                            intersect: intersect
                        },
                        legend: {
                            display: false
                        },
                        scales: {
                            yAxes: [{
                                ticks: $.extend({
                                    beginAtZero: true,
                                    // Include a dollar sign in the ticks
                                    callback: function (value) {
                                        if (value >= 1000) {
                                            value /= 1000
                                            value += 'k'
                                        }

                                        return '₱' + value;
                                    }
                                }, ticksStyle)
                            }],
                            xAxes: [{
                                display: true,
                                ticks: ticksStyle
                            }]
                        },
                        tooltips: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return "₱ " + tooltipItem.yLabel.toLocaleString();
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Sales Report',
                            fontSize: 22,
                            fontColor: '#000000'
                        },
                    }
                });
            }
        });
    }
});
</script>

@endsection