@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <span class="font-weight-bold d-block font-responsive text-center">{{ $branch }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-3">
                                    <a href="/beginning_inventory_list" class="btn btn-secondary m-0" style="width: 70px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-6">
                                    <h6 class="font-responsive font-weight-bold text-center m-0 text-uppercase d-block">Beginning Inventory</h6>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-1" style="font-size: 10pt">
                                <span class="d-block">Transaction Date: <b>{{ Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</b></span>
                                <span class="d-block">Total item(s): <b>{{ count($inventory) }}</b></span>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
                            </div>
                            <table class="table" id="items-table" style="font-size: 8pt;">
                                <thead class="border-top">
                                    <th class="text-uppercase text-center p-1 align-middle">Item Description</th>
                                    <th class="text-uppercase text-center p-1 align-middle">Opening Stock</th>
                                    <th class="text-uppercase text-center p-1 align-middle">Price</th>
                                </thead>
                                <tbody>
                                    @forelse ($inventory as $inv)
                                    @php
                                        $img = isset($item_image[$inv->item_code]) ? "/img/" . $item_image[$inv->item_code][0]->image_path : "/icon/no_img.png";
                                        $img_webp = isset($item_image[$inv->item_code]) ? "/img/" . explode('.',$item_image[$inv->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";

                                        $img_count = array_key_exists($inv->item_code, $item_image) ? count($item_image[$inv->item_code]) : 0;
                                    @endphp 
                                    <tr style="border-bottom: 0 !important;">
                                        <td class="text-center p-1">
                                            <span class="d-none">{{ strip_tags($inv->item_description) }}</span>
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $inv->item_code }}" data-title="{{ $inv->item_code }}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="font-weight-bold">{{ $inv->item_code }}</span>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="mobile-{{ $inv->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ $inv->item_code }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div id="image-container" class="container-fluid">
                                                                <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                                    <div class="carousel-inner">
                                                                        <div class="carousel-item active">
                                                                            <picture>
                                                                                <source id="mobile-{{ $inv->item_code }}-webp-image-src" srcset="{{ asset('storage/').$img_webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                <source id="mobile-{{ $inv->item_code }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                <img class="d-block w-100" id="mobile-{{ $inv->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                                                            </picture>
                                                                        </div>
                                                                        <span class='d-none5' id="mobile-{{ $inv->item_code }}-image-data">0</span>
                                                                    </div>
                                                                    @if ($img_count > 1)
                                                                    <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $inv->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                        <span class="sr-only">Previous</span>
                                                                    </a>
                                                                    <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $inv->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
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
                                        <td class="text-center p-1 align-middle font-weight-bold">{{ number_format($inv->opening_stock) }}</td>
                                        <td class="text-center p-1 align-middle font-weight-bold">₱ {{ number_format($inv->price * 1, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-justify pt-0 pb-1 pl-2 pr-1 align-middle" style="border-top: 0 !important;">
                                            <span class="d-none">{{ $inv->item_code }}</span><!-- for search -->
                                            <span class="item-description">{{ strip_tags($inv->item_description) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="font-responsive text-center" colspan=3>
                                            No available item(s) / All items for this branch are approved.
                                        </td>
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
@endsection

@section('style')
    <style>
        .morectnt span {
            display: none;
        }
    </style>
@endsection

@section('script')
    <script>
        var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
        $('.item-description').each(function() {
            var content = $(this).text();
            if (content.length > showTotalChar) {
                var con = content.substr(0, showTotalChar);
                var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $(".show-more").click(function(e) {
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

        $("#item-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    </script>
@endsection