@extends('layout', [
    'namePage' => 'Delivery Report',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-2">
                            <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">
                                Inquire Delivery
                            </span>
                        </div>
                        <div class="card-body p-1">
                            <form action="/promodiser/inquire_delivery" method="get">
                                <div class="row">
                                    <div class="col-8 col-xl-10">
                                        <input type="text" id="ste-search" name="ste" class="form-control" placeholder="Enter STE Number" value="{{ request('ste') ? request('ste') : null }}">
                                    </div>
                                    <div class="col-4 col-xl-2"><button type="submit" id="submit-search" class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button></div>
                                </div>
                            </form>
                            @if (count($delivery_report) > 0)
                                @php
                                    $ste_details = collect($delivery_report)->first();
                                    $status = "Pending";
                                    if($ste_details->item_status == 'Issued' && Carbon\Carbon::now() > $ste_details->delivery_date){
                                        $status = 'Delivered';
                                    }

                                    $delivery_status = collect($delivery_report)->map(function($q){
                                        return $q->consignment_status ? 1 : 0;
                                    })->min();
                                @endphp
                                <div class="row">
                                    <form action="/promodiser/receive/{{ $ste_details->name }}" method="get">
                                        <div class="container text-center">
                                            <br>
                                            <h5 class="text-center font-responsive font-weight-bold m-0">{{ $ste_details->t_warehouse }}</h5>
                                            <small class="d-block text-center mb-2">{{ $ste_details->name }} | Delivery Date: {{ Carbon\Carbon::parse($ste_details->delivery_date)->format('M d, Y') }}</small>
                                            @if ($ste_details->consignment_status == 'Received')
                                                <small class="d-block"><b>Date Received:</b> {{ Carbon\Carbon::parse($ste_details->consignment_date_received)->format('M d, Y - h:i a') }}</small>
                                            @endif
                                            <div class="callout callout-info text-center">
                                                <small><i class="fas fa-info-circle"></i> Once items are received, stocks will be automatically added to your current inventory.</small>
                                            </div>
                                            <table class="table" style="font-size: 9pt;">
                                                <thead>
                                                    <th class="text-center p-1 align-middle" style="width: 40%">Item Code</th>
                                                    <th class="text-center p-1 align-middle" style="width: 30%">Delivered Qty</th>
                                                    <th class="text-center p-1 align-middle" style="width: 30%">Rate</th>
                                                </thead>
                                                <tbody>
                                                    @foreach ($delivery_report as $item)
                                                    @php
                                                        $orig_exists = 0;
                                                        $webp_exists = 0;

                                                        $img = '/icon/no_img.png';
                                                        $webp = '/icon/no_img.webp';

                                                        if(isset($item_image[$item->item_code])){
                                                            $orig_exists = Storage::disk('public')->exists('/img/'.$item_image[$item->item_code][0]->image_path) ? 1 : 0;
                                                            $webp_exists = Storage::disk('public')->exists('/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp') ? 1 : 0;

                                                            $webp = $webp_exists == 1 ? '/img/'.explode('.', $item_image[$item->item_code][0]->image_path)[0].'.webp' : null;
                                                            $img = $orig_exists == 1 ? '/img/'.$item_image[$item->item_code][0]->image_path : null;

                                                            if($orig_exists == 0 && $webp_exists == 0){
                                                                $img = '/icon/no_img.png';
                                                                $webp = '/icon/no_img.webp';
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="text-left p-1 align-middle" style="border-bottom: 0 !important;">
                                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                                <div class="p-1 text-left">
                                                                    <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $item->item_code }}" data-title="{{ $item->item_code }}">
                                                                        <picture>
                                                                            <source srcset="{{ asset('storage'.$webp) }}" type="image/webp" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                                        </picture>
                                                                    </a>
                                                                </div>
                                                                <div class="p-1 m-0">
                                                                    <span class="font-weight-bold">{{ $item->item_code }}</span>
                                                                </div>
                                                            </div>
        
                                                            <div class="modal fade" id="mobile-{{ $item->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">{{ $item->item_code }}</h5>
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
                                                                                                <source id="mobile-{{ $item->item_code }}-webp-image-src" srcset="{{ asset('storage/').$webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                                <source id="mobile-{{ $item->item_code }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                                <img class="d-block w-100" id="mobile-{{ $item->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                                                                            </picture>
                                                                                        </div>
                                                                                        <span class='d-none' id="mobile-{{ $item->item_code }}-image-data">0</span>
                                                                                    </div>
                                                                                    @if (count($item_image) > 1)
                                                                                    <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $item->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                                        <span class="sr-only">Previous</span>
                                                                                    </a>
                                                                                    <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $item->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
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
                                                        <td class="text-center p-1 align-middle">
                                                            <span class="d-block font-weight-bold">{{ number_format($item->transfer_qty * 1) }}</span>
                                                            <span class="d-none font-weight-bold" id="{{ $item->item_code }}-qty">{{ $item->transfer_qty * 1 }}</span>
                                                            <small>{{ $item->stock_uom }}</small>
                                                        </td>
                                                        <td class="text-center p-1 align-middle">
                                                            <input type="text" name="item_codes[]" class="d-none" value="{{ $item->item_code }}"/>
                                                            <input type="text" value='{{ $item->basic_rate > 0 ? number_format($item->basic_rate, 2) : null }}' class='form-control text-center price' name='price[{{ $item->item_code }}]' data-target='{{ $item->item_code }}' placeholder='0' required>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
                                                            <span class="item-description">{!! strip_tags($item->description) !!}</span> <br>
                                                            Amount: ₱ <span id="{{ $item->item_code }}-amount" min='1' class='font-weight-bold amount'>{{ number_format($item->transfer_qty * $item->basic_rate, 2) }}</span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <div class="row p-2">
                                                @if ($status == 'Delivered' && $delivery_status == 0)
                                                    <input type="checkbox" name="update_price" class="d-none" readonly>
                                                    <input type="checkbox" name="receive_delivery" class="d-none" checked readonly>
                                                    <button type="submit" class="btn btn-primary w-100 submit-once">Receive</button>
                                                @else
                                                    <input type="checkbox" name="update_price" class="d-none" checked readonly>
                                                    <input type="checkbox" name="receive_delivery" class="d-none" readonly>
                                                    <button type="submit" class="btn btn-info w-100 submit-once mb-2">Update Prices</button>
                                                    <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-Modal">
                                                        Cancel
                                                    </button>
                                                        
                                                    <div class="modal fade" id="cancel-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-navy">
                                                                    <h5 class="modal-title" id="exampleModalLabel">Cancel</h5>
                                                                    <button type="button" class="close" onclick="close_modal('#cancel-Modal')">
                                                                    <span aria-hidden="true" style="color: #fff;">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Cancel {{ $ste_details->name }}?
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <a href="/promodiser/cancel/received/{{ $ste_details->name }}" class="btn btn-primary w-100 submit-once">Confirm</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @elseif(session()->has('error'))
                                <div class="row">
                                    <div class="col-12 p-2 text-center">
                                        <p>{{ session()->get('error') }}</p>
                                    </div>
                                </div>
                            @endif
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
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }

        /* Firefox */
        input[type=number] {
        -moz-appearance: textfield;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
    </style>
@endsection

@section('script')
<script>
    $(document).ready(function(){
        var showTotalChar = 150, showChar = "Show more", hideChar = "Show less";

        $('.price').keyup(function(){
            var target = $(this).data('target');
            var price = $(this).val().replace(/,/g, '');
            if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
                var qty = parseInt($('#'+target+'-qty').text());
                var total_amount = price * qty;

                const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
                $('#'+target+'-amount').text(amount);
            }else{
                $('#'+target+'-amount').text('0');
                $(this).val('');
            }
        });

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
    });
</script>
@endsection