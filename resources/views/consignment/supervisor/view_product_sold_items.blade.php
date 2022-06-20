@extends('layout', [
    'namePage' => 'Inventory Audit Item(s)',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div style="margin-bottom: -43px;">
                        <a href="/view_sales_report" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i> </a>
                    </div>
                    <h3 class="text-center font-weight-bold m-2 text-uppercase">Sales Report Item(s)</h3>
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <span class="font-weight-bolder d-block font-responsive">{{ $store }}</span>
                        </div>
                        <div class="card-body p-1">
                            <h5 class="text-center mt-2 font-weight-bolder font-responsive">{{ $duration }}</h5>
                            <p class="m-1">Promodiser(s): <span class="font-weight-bold">{{ $promodisers }}</span></p>
                            <p class="m-1">Total Qty Sold: <span class="font-weight-bold">{{ collect($result)->sum('qty') }}</span></p>
                            <p class="m-1">Total Amount: <span class="font-weight-bold">{{ '₱ ' . number_format(collect($result)->sum('amount'), 2) }}</span></p>
                            <table class="table table-bordered table-striped" id="items-table">
                                <thead class="border-top">
                                    <th class="text-center font-responsive p-2 align-middle">Item Code</th>
                                    <th class="text-center font-responsive p-2 align-middle">Qty Sold</th>
                                    <th class="text-center font-responsive p-2 align-middle">Amount</th>
                                </thead>
                                <tbody>
                                    @forelse ($result as $row)
                                    <tr>
                                        <td class="text-justify p-1 align-middle">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ asset('storage/') }}{{ $row['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $row['item_code'] }}" data-title="{{ $row['item_code'] }}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$row['img_webp']) }}" type="image/webp" alt="{{ str_slug(explode('.', $row['img'])[0], '-') }}" width="70" height="70">
                                                            <source srcset="{{ asset('storage'.$row['img']) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $row['img'])[0], '-') }}" width="70" height="70">
                                                            <img src="{{ asset('storage'.$row['img']) }}" alt="{{ str_slug(explode('.', $row['img'])[0], '-') }}" width="70" height="70">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="d-block font-weight-bold">{{ $row['item_code'] }}</span>
                                                    <small class="item-description">{!! strip_tags($row['description']) !!}</small>
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
                                            <span class="d-block">{{ number_format($row['qty']) }}</span>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">
                                            <span class="d-block">{{ '₱ ' . number_format($row['qty'], 2) }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold text-uppercase text-muted" colspan="2">No item(s) found</td>
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

<style>
    .morectnt span {
        display: none;
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