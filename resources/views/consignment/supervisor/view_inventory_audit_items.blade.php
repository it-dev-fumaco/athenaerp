@extends('layout', [
    'namePage' => 'Inventory Audit Item(s)',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                <a href="/inventory_audit" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-10 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">{{ $store }}</h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 p-0">
                            <h5 class="text-center font-weight-bold m-2 text-uppercase">Sales & Inventory Report</h5>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-2">
                                    <div class="d-flex flex-row align-items-center">
                                        <div class="pt-3 col-12 text-center">
                                            <a href="{{ $previous_record_link ? $previous_record_link : '#' }}" class="text-dark">
                                                <h1 class="m-0 font-details font-weight-bold" style="font-size: 30pt;"><i class="fas fa-angle-double-left"></i></h1>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="d-flex flex-row align-items-end">
                                        <div class="p-0 col-12 text-left">
                                            <p class="m-1 font-details">Period: <span class="font-weight-bold">{{ $duration }}</span></p>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-row align-items-end">
                                        <div class="p-0 col-12 text-left">
                                            <small class="m-1 font-details">Promodiser(s): <span class="font-weight-bold">{{ $promodisers }}</span></small>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-row align-items-end">
                                        <div class="p-0 col-12 text-left">
                                            <small class="m-1 font-details">Date Submitted: <span class="font-weight-bold">{{ \Carbon\Carbon::parse($list[0]->transaction_date)->format('F d, Y') }}</span></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="d-flex flex-row align-items-center">
                                        <div class="pt-3 col-12 text-center">
                                            <h1 class="m-0 font-details font-weight-bold">{{ '₱ ' . number_format($total_sales, 2) }} 
                                                @if ($total_sales > 0)
                                                @if ($sales_increase)
                                                <i class="fas fa-long-arrow-alt-up text-success"></i>
                                                @else
                                                <i class="fas fa-long-arrow-alt-down text-danger"></i>
                                                @endif
                                                @endif
                                            </h1>
                                            <small class="text-muted">Sales</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <div class="d-flex flex-row align-items-center">
                                        <div class="pt-3 col-12 text-center">
                                            <a href="{{ $next_record_link ? $next_record_link : '#' }}" class="text-dark">
                                                <h1 class="m-0 font-details font-weight-bold" style="font-size: 30pt;"><i class="fas fa-angle-double-right"></i></h1>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-row justify-content-between mt-3">
                                <div class="p-1 text-left">
                                    <h6 class="m-0 font-weight-bolder text-uppercase">Item List</h6>
                                </div>
                                <div class="p-1 text-center">
                                    <p class="m-0 font-details">Total Item(s): <span class="font-weight-bold">{{ count($result) }}</span></p>
                                </div>
                            </div>
                            <div class="tableFixHead table-responsive">
                                <table id="customers">
                                    <thead class="border-top" style="font-size: 12px;">
                                        <tr>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 500px;">Item Code</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Opening</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Audit Qty</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Sold</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Received</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Returned</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Transferred</th>
                                            <th class="text-center p-2 align-middle text-uppercase" style="width: 100px;">Damaged</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 13px;">
                                        @forelse ($result as $row)
                                        <tr>
                                            <td class="text-justify p-2 align-middle">
                                                <div class="d-flex flex-row justify-content-start align-items-center">
                                                    <div class="p-0 text-left">
                                                        <a href="{{ asset('storage/') }}{{ $row['img'] }}" class="view-images" data-item-code="{{ $row['item_code'] }}">
                                                            <picture>
                                                                <source srcset="{{ asset('storage'.$row['img_webp']) }}" type="image/webp">
                                                                <source srcset="{{ asset('storage'.$row['img']) }}" type="image/jpeg">
                                                                <img src="{{ asset('storage'.$row['img']) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $row['img'])[0], '-') }}" class="row-img">
                                                            </picture>
                                                        </a>
                                                    </div>
                                                    <div class="pl-2 m-0">
                                                        <span class="d-block"><b>{{ $row['item_code'] }}</b> - {{ $row['description'] }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                <span class="d-block">{{ $row['opening_qty'] }}</span>
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                <span class="d-block">{{ $row['audit_qty'] }}</span>
                                            </td>
                                            @php
                                                $total_received = isset($received_items[$row['item_code']]) ? collect($received_items[$row['item_code']])->sum('qty') : '-';
                                                $total_returned = isset($returned_items[$row['item_code']]) ? collect($returned_items[$row['item_code']])->sum('qty') : '-';
                                                $total_transferred = isset($transferred_items[$row['item_code']]) ? collect($transferred_items[$row['item_code']])->sum('qty') : '-';
                                                $total_damaged = isset($damaged_item_list[$row['item_code']]) ? collect($damaged_item_list[$row['item_code']])->sum('qty') : '-';
                                            @endphp
                                            <td class="text-center p-1 align-middle">
                                                <b>{{ number_format($row['sold_qty']) }}</b>
                                            </td>
                                            <td class="text-center p-1 align-middle">
                                                @if ($total_received != '-')
                                                <a href="#" data-toggle="modal" data-target="#received-{{ $row['item_code'] }}-modal">
                                                    <span class="d-block">{{ number_format($total_received) }}</span>
                                                </a>
                                                @endif
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                @if ($total_returned != '-')
                                                <a href="#" data-toggle="modal" data-target="#returned-{{ $row['item_code'] }}-modal">
                                                    <span class="d-block">{{ number_format($total_returned) }}</span>
                                                </a>
                                                @endif
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                @if ($total_transferred != '-')
                                                <a href="#" data-toggle="modal" data-target="#transferred-{{ $row['item_code'] }}-modal">
                                                    <span class="d-block">{{ number_format($total_transferred) }}</span>
                                                </a>
                                                @endif
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                @if ($total_damaged != '-')
                                                <a href="#" data-toggle="modal" data-target="#damaged-{{ $row['item_code'] }}-modal">
                                                    <span class="d-block">{{ number_format($total_damaged) }}</span>
                                                </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="5">No item(s) found</td>
                                        </tr> 
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@foreach ($result as $r)
@php
    $receiving_txn = isset($received_items[$r['item_code']]) ? $received_items[$r['item_code']] : [];
    $returned_txn = isset($returned_items[$r['item_code']]) ? $returned_items[$r['item_code']] : [];
    $transfer_txn = isset($transferred_items[$r['item_code']]) ? $transferred_items[$r['item_code']] : [];
    $damaged_txn = isset($damaged_item_list[$r['item_code']]) ? $damaged_item_list[$r['item_code']] : [];
@endphp
<div class="modal fade" id="received-{{ $r['item_code'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="font-size: 9pt;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $r['item_code'] }} - Stocks Received</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th class="p-2 align-middle text-center">Reference No.</th>
                        <th class="p-2 align-middle text-center">Delivery Date</th>
                        <th class="p-2 align-middle text-center">Quantity</th>
                        <th class="p-2 align-middle text-center">Rate</th>
                        <th class="p-2 align-middle text-center">Amount</th>
                        <th class="p-2 align-middle text-center">Date Received</th>
                        <th class="p-2 align-middle text-center">Received By</th>
                    </thead>
                    <tbody>
                        @forelse ($receiving_txn as $rtxn)
                        <tr>
                            <td class="p-1 align-middle text-center">{{ $rtxn['reference'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $rtxn['delivery_date'] }}</td>
                            <td class="p-1 align-middle text-center">{{ number_format($rtxn['qty']) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($rtxn['price'], 2) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($rtxn['amount'], 2) }}</td>
                            <td class="p-1 align-middle text-center">{{ $rtxn['date_received'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $rtxn['received_by'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="7">No item(s) found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="damaged-{{ $r['item_code'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="font-size: 9pt;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $r['item_code'] }} - Stocks Damaged</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th class="p-2 align-middle text-center">Transaction Date</th>
                        <th class="p-2 align-middle text-center">Quantity</th>
                        <th class="p-2 align-middle text-center">Stock UOM</th>
                        <th class="p-2 align-middle text-center">Damage Description</th>
                    </thead>
                    <tbody>
                        @forelse ($damaged_txn as $dtxn)
                        <tr>
                            <td class="p-1 align-middle text-center">{{ $dtxn['transaction_date'] }}</td>
                            <td class="p-1 align-middle text-center">{{ number_format($dtxn['qty']) }}</td>
                            <td class="p-1 align-middle text-center">{{ $dtxn['stock_uom'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $dtxn['damage_description'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="8">No item(s) found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="transferred-{{ $r['item_code'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="font-size: 9pt;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $r['item_code'] }} - Stocks Transferred</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th class="p-2 align-middle text-center">Transaction Date</th>
                        <th class="p-2 align-middle text-center">Reference No.</th>
                        <th class="p-2 align-middle text-center">Target</th>
                        <th class="p-2 align-middle text-center">Quantity</th>
                        <th class="p-2 align-middle text-center">Rate</th>
                        <th class="p-2 align-middle text-center">Amount</th>
                        <th class="p-2 align-middle text-center">Date Received</th>
                        <th class="p-2 align-middle text-center">Received By</th>
                    </thead>
                    <tbody>
                        @forelse ($transfer_txn as $ttxn)
                        <tr>
                            <td class="p-1 align-middle text-center">{{ $ttxn['transaction_date'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $ttxn['reference'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $ttxn['t_warehouse'] }}</td>
                            <td class="p-1 align-middle text-center">{{ number_format($ttxn['qty']) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($ttxn['price'], 2) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($ttxn['amount'], 2) }}</td>
                            <td class="p-1 align-middle text-center">{{ $ttxn['date_received'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $ttxn['received_by'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="8">No item(s) found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="returned-{{ $r['item_code'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="font-size: 9pt;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $r['item_code'] }} - Stocks Returned</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form></form>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th class="p-2 align-middle text-center">Transaction Date</th>
                        <th class="p-2 align-middle text-center">Reference No.</th>
                        <th class="p-2 align-middle text-center">Target</th>
                        <th class="p-2 align-middle text-center">Quantity</th>
                        <th class="p-2 align-middle text-center">Rate</th>
                        <th class="p-2 align-middle text-center">Amount</th>
                    </thead>
                    <tbody>
                        @forelse ($returned_txn as $rrtxn)
                        <tr>
                            <td class="p-1 align-middle text-center">{{ $rrtxn['transaction_date'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $rrtxn['reference'] }}</td>
                            <td class="p-1 align-middle text-center">{{ $rrtxn['t_warehouse'] }}</td>
                            <td class="p-1 align-middle text-center">{{ number_format($rrtxn['qty']) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($rrtxn['price'], 2) }}</td>
                            <td class="p-1 align-middle text-center">{{ '₱ ' . number_format($rrtxn['amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="6">No item(s) found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endforeach
<style>
    table {
        table-layout: fixed;
        width: 100%;   
    }
    .morectnt span {
        display: none;
    }
    .row-img{
        width: 30px;
        height: 30px;
    }
    .first{
        width: 70%;
    }
    @media (max-width: 575.98px) {
        #items-table{
            font-size: 10pt;
        }
        .first{
            width: 35%;
        }
        .row-img{
            width: 50px;
            height: 50px;
        }
    }
    @media (max-width: 767.98px) {
        #items-table{
            font-size: 10pt;
        }
        .first{
            width: 35%;
        }
        .row-img{
            width: 50px;
            height: 50px;
        }
    }
    @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
        #items-table{
            font-size: 10pt;
        }
        .first{
            width: 35%;
        }
        .row-img{
            width: 50px;
            height: 50px;
        }
    }

    .tableFixHead { overflow-y: auto; height: 600px; }
   
   table #customers {
      display: table;
      border-collapse: collapse;
      width: 100%;
    }

    #customers td, #customers th {
      border: 1px solid #ddd;
    }

    #customers tr:nth-child(even){background-color: #f2f2f2;}

    #customers tr:hover {background-color: #ddd;}

    #customers th {
      padding-top: 12px;
      padding-bottom: 12px;
      text-align: left;
      background-color: #2C3B49;
      color: white;
    }

</style>
@endsection

@section('script')
<script>
    $(function () {
        var $th = $('.tableFixHead').find('thead th')
        $('.tableFixHead').on('scroll', function() {
            $th.css('transform', 'translateY('+ this.scrollTop +'px)');
        });

        var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
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
    });
</script>
@endsection