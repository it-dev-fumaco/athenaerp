@extends('layout', [
    'namePage' => 'Inventory Audit Item(s)',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-md-10 offset-md-1">
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
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Sales & Inventory Report</h4>
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
                                            <h1 class="m-0 font-details font-weight-bold">{{ '₱ ' . number_format(collect($result)->sum('total_value'), 2) }} 
                                                @if (collect($result)->sum('total_value') > 0)
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
                                    <h6 class="m-0 font-weight-bolder text-uppercase">Sold Item(s)</h6>
                                </div>
                                <div class="p-1 text-center">
                                    <p class="m-0 font-details">Total Qty Sold: <span class="font-weight-bold">{{ collect($result)->sum('sold_qty') }}</span></p>
                                </div>
                            </div>
                            <table class="table table-bordered table-striped" style="font-size: 10pt;">
                                <thead class="border-top text-uppercase">
                                    <th class="text-center font-responsive p-2 align-middle first" style="width: 55%;">Item Code</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Sold Qty</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Rate</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Amount</th>
                                </thead>
                                <tbody>
                                    @forelse ($sales_items as $s)
                                    <tr>
                                        <td class="text-justify p-1 align-middle">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ asset('storage/') }}{{ $s['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $s['item_code'] }}" data-title="{{ $s['item_code'] }}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$s['img_webp']) }}" type="image/webp">
                                                            <source srcset="{{ asset('storage'.$s['img']) }}" type="image/jpeg">
                                                            <img src="{{ asset('storage'.$s['img']) }}" alt="{{ str_slug(explode('.', $s['img'])[0], '-') }}" class="row-img">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="d-block font-weight-bold">{{ $s['item_code'] }}</span>
                                                    <small class="d-block">{!! strip_tags($s['description']) !!}</small>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="mobile-{{ $s['item_code'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ $s['item_code'] }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form></form>
                                                            <div id="image-container" class="container-fluid">
                                                                <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                                    <div class="carousel-inner">
                                                                        <div class="carousel-item active">
                                                                            <picture>
                                                                                <source id="mobile-{{ $s['item_code'] }}-webp-image-src" srcset="{{ asset('storage/').$s['img_webp'] }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                <source id="mobile-{{ $s['item_code'] }}-orig-image-src" srcset="{{ asset('storage/').$s['img'] }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                <img class="d-block w-100" id="mobile-{{ $s['item_code'] }}-image" src="{{ asset('storage/').$s['img'] }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $s['img'])[0], '-') }}">
                                                                            </picture>
                                                                        </div>
                                                                        <span class='d-none5' id="mobile-{{ $s['item_code'] }}-image-data">0</span>
                                                                    </div>
                                                                    @if ($s['img_count'] > 1)
                                                                    <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $s['item_code'] }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                        <span class="sr-only">Previous</span>
                                                                    </a>
                                                                    <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $s['item_code'] }}')" role="button" data-slide="next" style="color: #000 !important">
                                                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                        <span class="sr-only">Next</span>
                                                                    </a>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">
                                            <span class="d-block">{{ number_format($s['qty']) }}</span>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">
                                            <span class="d-block">{{ '₱ ' . number_format($s['price'], 2) }}</span>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">
                                            <span class="d-block">{{ '₱ ' . number_format($s['amount'], 2) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold text-uppercase text-muted" colspan="4">No item(s) found</td>
                                    </tr> 
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="card card-secondary card-outline p-2">
                                <div class="d-flex flex-row align-items-end">
                                    <div class="p-1 col-12 text-left">
                                        <p class="m-1 font-details font-weight-bold text-uppercase">Received Item(s) within this period</p>
                                    </div>
                                </div>
                                <style>
                                    #received-items-table tbody:nth-child(even) {
                                        background-color: #f2f2f2 !important;
                                    }
                                </style>
                                <table class="table mr-2 border" id="received-items-table" style="font-size: 10pt;">
                                    <thead class="border-top text-uppercase">
                                        <th class="text-center font-responsive p-2 align-middle first" style="width: 40%;">Item Code</th>
                                        <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Qty</th>
                                        <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Rate</th>
                                        <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Amount</th>
                                    </thead>
                                    @forelse ($received_items as $r)
                                    <tbody>
                                        <tr>
                                            <td class="text-justify p-1 align-middle">
                                                <div class="d-flex flex-row justify-content-start align-items-center">
                                                    <div class="p-1 text-left">
                                                        <a href="{{ asset('storage/') }}{{ $r['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $r['item_code'] }}" data-title="{{ $r['item_code'] }}">
                                                            <picture>
                                                                <source srcset="{{ asset('storage'.$r['img_webp']) }}" type="image/webp">
                                                                <source srcset="{{ asset('storage'.$r['img']) }}" type="image/jpeg">
                                                                <img src="{{ asset('storage'.$r['img']) }}" alt="{{ str_slug(explode('.', $r['img'])[0], '-') }}" class="row-img">
                                                            </picture>
                                                        </a>
                                                    </div>
                                                    <div class="p-1 m-0">
                                                        <span class="d-block font-weight-bold">{{ $r['item_code'] }}</span>
                                                    </div>
                                                </div>
    
                                                <div class="modal fade" id="mobile-{{ $r['item_code'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ $r['item_code'] }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form></form>
                                                                <div id="image-container" class="container-fluid">
                                                                    <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                                        <div class="carousel-inner">
                                                                            <div class="carousel-item active">
                                                                                <picture>
                                                                                    <source id="mobile-{{ $r['item_code'] }}-webp-image-src" srcset="{{ asset('storage/').$r['img_webp'] }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                    <source id="mobile-{{ $r['item_code'] }}-orig-image-src" srcset="{{ asset('storage/').$r['img'] }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                    <img class="d-block w-100" id="mobile-{{ $r['item_code'] }}-image" src="{{ asset('storage/').$r['img'] }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $r['img'])[0], '-') }}">
                                                                                </picture>
                                                                            </div>
                                                                            <span class='d-none5' id="mobile-{{ $r['item_code'] }}-image-data">0</span>
                                                                        </div>
                                                                        @if ($r['img_count'] > 1)
                                                                        <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $r['item_code'] }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                            <span class="sr-only">Previous</span>
                                                                        </a>
                                                                        <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $r['item_code'] }}')" role="button" data-slide="next" style="color: #000 !important">
                                                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                            <span class="sr-only">Next</span>
                                                                        </a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                <span class="d-block">{{ $r['qty'] }}</span>
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                <span class="d-block">{{ '₱ ' . number_format($r['price'], 2) }}</span>
                                            </td>
                                            <td class="text-center p-1 align-middle font-weight-bold">
                                                <span class="d-block">{{ '₱ ' . number_format($r['amount'], 2) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-2 text-justify border-top-0" colspan=4>
                                                <small class="item-description">{!! strip_tags($r['description']) !!}</small>
                                            </td>
                                        </tr>
                                    </tbody>
                                    @empty
                                    <tbody>
                                        <tr>
                                            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="4">No item(s) found</td>
                                        </tr> 
                                    </tbody>
                                    @endforelse
                                </table>
                                    <div class="m-2">
                                        Total: <b>{{ count($received_items) }}</b>
                                    </div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="card card-secondary card-outline p-2">
                                    <div class="d-flex flex-row align-items-end">
                                        <div class="p-1 col-6 text-left">
                                            <p class="m-1 font-details font-weight-bold text-uppercase">Stock Level</p>
                                        </div>
                                        <div class="p-1 col-6 text-right">
                                            <p class="m-1 font-details">Inventory Value: <span class="font-weight-bold">{{ '₱ ' . number_format($list[0]->grand_total, 2) }}</span></p>
                                        </div>
                                    </div>
                                    <table class="table table-bordered table-striped" style="font-size: 10pt;">
                                        <thead class="border-top text-uppercase">
                                            <th class="text-center font-responsive p-2 align-middle first" style="width: 50%;">Item Code</th>
                                            <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Opening Stock</th>
                                            <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Audit Qty</th>
                                            <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Rate</th>
                                            <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Amount</th>
                                        </thead>
                                        <tbody>
                                            @forelse ($result as $row)
                                            <tr>
                                                <td class="text-justify p-1 align-middle">
                                                    <div class="d-flex flex-row justify-content-start align-items-center">
                                                        <div class="p-1 text-left">
                                                            <a href="{{ asset('storage/') }}{{ $row['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $row['item_code'] }}" data-title="{{ $row['item_code'] }}">
                                                                <picture>
                                                                    <source srcset="{{ asset('storage'.$row['img_webp']) }}" type="image/webp">
                                                                    <source srcset="{{ asset('storage'.$row['img']) }}" type="image/jpeg">
                                                                    <img src="{{ asset('storage'.$row['img']) }}" alt="{{ str_slug(explode('.', $row['img'])[0], '-') }}" class="row-img">
                                                                </picture>
                                                            </a>
                                                        </div>
                                                        <div class="p-1 m-0">
                                                            <span class="d-block font-weight-bold">{{ $row['item_code'] }} - {{ explode(',', strip_tags($row['description']))[0] }}</span>
                                                        </div>
                                                    </div>
        
                                                    <div class="modal fade" id="mobile-{{ $row['item_code'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">{{ $row['item_code'] }}</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form></form>
                                                                    <div id="image-container" class="container-fluid">
                                                                        <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                                            <div class="carousel-inner">
                                                                                <div class="carousel-item active">
                                                                                    <picture>
                                                                                        <source id="mobile-{{ $row['item_code'] }}-webp-image-src" srcset="{{ asset('storage/').$row['img_webp'] }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                        <source id="mobile-{{ $row['item_code'] }}-orig-image-src" srcset="{{ asset('storage/').$row['img'] }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                        <img class="d-block w-100" id="mobile-{{ $row['item_code'] }}-image" src="{{ asset('storage/').$row['img'] }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $row['img'])[0], '-') }}">
                                                                                    </picture>
                                                                                </div>
                                                                                <span class='d-none5' id="mobile-{{ $row['item_code'] }}-image-data">0</span>
                                                                            </div>
                                                                            @if ($row['img_count'] > 1)
                                                                            <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $row['item_code'] }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                                <span class="sr-only">Previous</span>
                                                                            </a>
                                                                            <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $row['item_code'] }}')" role="button" data-slide="next" style="color: #000 !important">
                                                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                                <span class="sr-only">Next</span>
                                                                            </a>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center p-1 align-middle font-weight-bold">
                                                    <span class="d-block">{{ $row['opening_qty'] }}</span>
                                                </td>
                                                <td class="text-center p-1 align-middle font-weight-bold">
                                                    <span class="d-block">{{ $row['audit_qty'] }}</span>
                                                </td>
                                                <td class="text-center p-1 align-middle font-weight-bold">
                                                    <span class="d-block">{{ '₱ ' . number_format($row['price'], 2) }}</span>
                                                </td>
                                                <td class="text-center p-1 align-middle font-weight-bold">
                                                    <span class="d-block">{{ '₱ ' . number_format($row['amount'], 2) }}</span>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td class="text-center font-weight-bold text-uppercase text-muted" colspan="5">No item(s) found</td>
                                            </tr> 
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="m-2">
                                        Total: <b>{{ count($list) }}</b>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
    table {
        table-layout: fixed;
        width: 100%;   
    }
    .morectnt span {
        display: none;
    }
    .row-img{
        width: 50px;
        height: 50px;
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

</style>
@endsection

@section('script')
<script>
    $(function () {
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