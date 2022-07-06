@extends('layout', [
    'namePage' => 'Products Sold Form',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/view_calendar_menu/{{ $branch }}" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Product Sold Entry</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                            <div class="alert alert-success fade show text-center" role="alert" style="font-size: 10pt;">
                                {!! session()->get('success') !!}
                            </div>
                            @endif
                            @if(session()->has('error'))
                            <div class="alert alert-danger fade show text-center" role="alert" style="font-size: 10pt;">
                                {!! session()->get('error') !!}
                            </div>
                            @endif
                            <span id="branch-name" class="font-weight-bolder d-block text-center" style="font-size: 11pt;">{{ $branch }}</span>
                            <h5 class="text-center mt-1 font-weight-bolder">{{ \Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</h5>
                            <form action="/submit_product_sold_form" method="POST" autocomplete="off">
                                @csrf
                                <input type="hidden" name="transaction_date" value="{{ $transaction_date }}">
                                <input type="hidden" name="branch_warehouse" value="{{ $branch }}">
                                <div class="form-group m-2">
                                    <input type="text" class="form-control form-control-sm" placeholder="Search Items" id="search-filter">
                                </div>
                                <table class="table table-bordered" style="font-size: 8pt;" id="items-table">
                                    <thead>
                                        <th class="text-center p-1" style="width: 55%;">ITEM DESCRIPTION</th>
                                        <th class="text-center p-1" style="width: 45%;">QTY SOLD</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $row)
                                        @php
                                            $id = $row->item_code;
                                            $img = array_key_exists($row->item_code, $item_images) ? "/img/" . $item_images[$row->item_code][0]->image_path : "/icon/no_img.png";
                                            $img_webp = array_key_exists($row->item_code, $item_images) ? "/img/" . explode('.',$item_images[$row->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
                                            $qty = array_key_exists($row->item_code, $existing_record) ? ($existing_record[$row->item_code] * 1) : 0;
                                            $consigned_qty = array_key_exists($row->item_code, $consigned_stocks) ? ($consigned_stocks[$row->item_code] * 1) : 0;

                                            $img_count = array_key_exists($row->item_code, $item_images) ? count($item_images[$row->item_code]) : 0;

                                            if(session()->has('error')) {
                                                $data = session()->get('old_data');
                                                $qty = $data['item'][$row->item_code]['qty'];
                                            }
                                        @endphp
                                        <tr>
                                            <td class="text-justify p-1 align-middle" colspan="2">
                                                <div class="d-flex flex-row justify-content-center align-items-center">
                                                    <div class="p-1 col-2 text-center">
                                                        <input type="hidden" name="item[{{ $row->item_code }}][description]" value="{!! strip_tags($row->description) !!}">
                                                        <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $row->item_code }}" data-title="{{ $row->item_code }}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                        </picture>
                                                    </a>
                                                    </div>
                                                    <div class="p-1 col-5 m-0">
                                                        <span class="font-weight-bold">{{ $row->item_code }}</span>
                                                    </div>
                                                    <div class="p-1 col-5">
                                                        <div class="input-group p-1 justify-content-center">
                                                            <div class="input-group-prepend p-0">
                                                                <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                            </div>
                                                            <div class="custom-a p-0">
                                                                <input type="number" class="form-control form-control-sm qty" value="{{ $qty }}" name="item[{{ $row->item_code }}][qty]" style="text-align: center; width: 80px;" data-max="{{ $consigned_qty }}">
                                                            </div>
                                                            <div class="input-group-append p-0">
                                                                <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <small>Available: {{ $consigned_qty }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-row">
                                                    <div class="p-1 text-justify">
                                                        <div class="item-description">{!! strip_tags($row->description) !!}</div>
                                                    </div>
                                                </div>

                                                <div class="modal fade" id="mobile-{{ $row->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ $row->item_code }}</h5>
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
                                                                                    <source id="mobile-{{ $row->item_code }}-webp-image-src" srcset="{{ asset('storage/').$img_webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                    <source id="mobile-{{ $row->item_code }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                    <img class="d-block w-100" id="mobile-{{ $row->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                                                                </picture>
                                                                            </div>
                                                                            <span class='d-none' id="mobile-{{ $row->item_code }}-image-data">0</span>
                                                                        </div>
                                                                        @if ($img_count > 1)
                                                                        <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $row->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                            <span class="sr-only">Previous</span>
                                                                        </a>
                                                                        <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $row->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
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
                                        </tr> 
                                        @empty
                                        <tr>
                                            <td class="text-center font-weight-bold" colspan="2">No item(s) found.</td>
                                        </tr> 
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="m-3">
                                    <button type="submit" class="btn btn-primary btn-block" {{ count($items) <= 0 ? 'disabled' : ''  }}><i class="fas fa-check"></i> SUBMIT</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<div class="modal fade" id="success-modal" tabindex="-1" role="dialog" aria-labelledby="success-modalTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <form></form>
                <div class="d-flex flex-row justify-content-end">
                    <div class="p-1">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                @if(session()->has('success'))
                <p class="text-success text-center mb-0" style="font-size: 5rem; margin-top: -40px;">
                    <i class="fas fa-check-circle"></i>
                </p>
                <p class="text-center text-uppercase mt-0 font-weight-bold">Product Sold is Saved</p>
                <div class="text-center mb-2" style="font-size: 9pt;">
                    <span class="d-block font-weight-bold mt-3">{{ session()->get('no_of_items_updated') }}</span>
                    <small class="d-block">No. of updated Items</small>
                    <span class="d-block font-weight-bold mt-3">{{ \Carbon\Carbon::parse(session()->get('transaction_date'))->format('F d, Y') }}</span>
                    <small class="d-block">Transaction Date</small>
                    <span class="d-block font-weight-bold mt-3">{{ session()->get('branch') }}</span>
                    <small class="d-block">Branch / Store</small>
                </div>
                <div class="d-flex flex-row justify-content-center">
                    <div class="p-2">
                        <a href="/view_calendar_menu/{{ $branch }}" class="btn btn-secondary font-responsive"><i class="far fa-calendar-alt"></i> Return to Calendar</a>
                    </div>
                </div>
                <div class="d-flex flex-row justify-content-between">
                    <div class="p-2">
                        <a href="/view_product_sold_form/{{ $branch }}/{{ \Carbon\Carbon::parse($transaction_date)->subDay()->format('Y-m-d') }}" class="btn btn-primary btn-sm font-responsive">
                            <i class="fas fa-arrow-left"></i> {{ \Carbon\Carbon::parse($transaction_date)->subDay()->format('F d, Y') }}
                        </a>
                    </div>
                    <div class="p-2">
                        <a href="/view_product_sold_form/{{ $branch }}/{{ \Carbon\Carbon::parse($transaction_date)->addDay()->format('Y-m-d') }}" class="btn btn-primary btn-sm font-responsive">
                            {{ \Carbon\Carbon::parse($transaction_date)->addDay()->format('F d, Y') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
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
    .morectnt span {
        display: none;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        @if (session()->has('success'))
        $('#success-modal').modal('show');
        @endif
        $('.qtyplus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            // get max value
            var max = fieldName.data('max');
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                if (currentVal < max) {
                    fieldName.val(currentVal + 1);
                }
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }
        });
        // This button will decrement the value till 0
        $(".qtyminus").click(function(e) {
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If it isn't undefined or its greater than 0
            if (!isNaN(currentVal) && currentVal > 0) {
                // Decrement one
                fieldName.val(currentVal - 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }
        });

        $("#search-filter").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
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