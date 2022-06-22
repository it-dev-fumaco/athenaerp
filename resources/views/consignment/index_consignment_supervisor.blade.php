@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container p-0">
            <div class="row p-0 m-0">
                <div class="col-6 col-md-3 p-1">
                    <a href="/view_sales_report">
                        <div class="info-box bg-gradient-primary m-0">
                            <div class="info-box-content p-1">
                                <span class="info-box-text font-responsive m-0">Sales Report</span>
                                <span class="info-box-number font-responsive m-0">{{ number_format($total_item_sold) }}</span>
                                <span class="progress-description font-responsive" style="font-size: 7pt;">{{ $duration }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <a href="/inventory_audit">
                        <div class="info-box bg-gradient-info m-0">
                            <div class="info-box-content p-1">
                                <span class="info-box-text font-responsive m-0">Inventory Audit</span>
                                <span class="info-box-number font-responsive m-0">{{ number_format($total_pending_inventory_audit) }}</span>
                                <span class="progress-description font-responsive" style="font-size: 7pt;">{{ $duration }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <a href="/stocks_report/list">
                        <div class="info-box bg-gradient-warning m-0">
                            <div class="info-box-content p-1">
                                <span class="info-box-text font-responsive">Stock Transfers</span>
                                <span class="info-box-number font-responsive">{{ number_format($total_stock_transfers) }}</span>
                                <div class="progress">
                                    <div class="progress-bar"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <a href="/beginning_inv_list" style="color: inherit">
                        <div class="info-box bg-gradient-secondary m-0">
                            <div class="info-box-content p-1">
                                <span class="info-box-text font-responsive">Stock Adjustments</span>
                                <span class="info-box-number font-responsive">{{ number_format($total_stock_adjustments) }}</span>
                                <div class="progress">
                                    <div class="progress-bar"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="d-flex flex-row  align-items-center">
                <div class="p-2 col-4">
                    <div class="text-center">
                        <p class="text-center m-0 font-responsive">
                            <span class="d-inline-block font-weight-bolder" style="font-size: 3rem;">{{ count($active_consignment_branches) }}</span>
                            <span class="d-inline-block text-muted" style="font-size: 1rem;">/ {{ count($consignment_branches) }}</span>
                        </p>
                        <span class="d-block">Active Store</span>
                    </div>
                </div>
                <div class="p-2 col-4">
                    <div class="text-center">
                        <p class="text-center font-weight-bolder m-0 font-responsive" style="font-size: 3rem;">{{ ($promodisers) }}</p>
                        <span class="d-block">Promodiser(s)</span>
                    </div>
                </div>
                <div class="p-2 col-4">
                    <a href="/beginning_inv_list" style="color: inherit">
                        <div class="skills_section text-center mb-1 p-0">
                            <div class="skills-area m-0">
                                <div class="single-skill w-100 m-2">
                                    <div class="circlechart" data-percentage="{{ $beginning_inv_percentage }}">
                                        <svg class="circle-chart" viewBox="0 0 33.83098862 33.83098862"><circle class="circle-chart__background" cx="16.9" cy="16.9" r="15.9"></circle><circle class="circle-chart__circle success-stroke" stroke-dasharray="92,100" cx="16.9" cy="16.9" r="15.9"></circle></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="d-block text-muted">{{ $consignment_branches_with_beginning_inventory }} / {{ count($consignment_branches) }}</small>
                            <span class="d-block">Beginning Inventory Completion</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-md-12">
                    <div class="card card-warning card-outline">
                        <div class="card-header p-2">
                            <h6 class="font-weight-bold text-center text-uppercase m-0">Pending for Submission of Inventory Audit</h6>
                        </div>
                        <div class="card-body p-2">
                            <form action="#" id="pending-inventory-audit-filter-form">
                                <div class="row p-1 mt-1 mb-1">
                                    <div class="col-10 col-xl-6">
                                        <select class="form-control pending-inventory-audit-filter" name="store" id="consignment-store-select">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                    <div class="col-2 p-0">
                                        <a href="/" class="btn btn-secondary d-inline-block float-left ml-1"><i class="fas fa-undo"></i></a>
                                    </div>
                                </div>
                            </form>
                            <div id="beginning-inventory-list-el"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="beginning-inventory-detail-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div id="beginning-inventory-detail-el"></div>
        </div>
    </div>
</div>

<style>
    .circle-chart {
        width: 150px;
        height: 110px;
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
        .circle-chart {
            width: 110px;
            height: 110px;
        }
    }
</style>
@endsection

@section('script')
<script>
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

        $(document).on('change', '.pending-inventory-audit-filter', function(e) {
            e.preventDefault();
            get_pending_inventory_audit();
        });

        get_pending_inventory_audit();
        function get_pending_inventory_audit(page) {
            $.ajax({
                type: "GET",
                url: "/pending_submission_inventory_audit?page=" + page,
                data: $('#pending-inventory-audit-filter-form').serialize(),
                success: function (data) {
                    $('#beginning-inventory-list-el').html(data);
                }
            });
        }

        $(document).on('click', '#beginning-inventory-list-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            get_pending_inventory_audit(page);
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
    });
</script>
@endsection