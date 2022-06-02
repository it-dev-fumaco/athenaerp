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
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-left">
                            <span class="d-block" style="font-size: 11pt;">{{ Auth::user()->full_name }}</span>
                            <span id="branch-name" class="font-weight-bold d-block">{{ $branch }}</span>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                            <div class="alert alert-success fade show text-center" role="alert">
                                {{ session()->get('success') }}
                            </div>
                            @endif
                            @if(session()->has('error'))
                            <div class="alert alert-danger fade show text-center" role="alert">
                                {{ session()->get('error') }}
                            </div>
                            @endif
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Product Sold Entry</h6>
                            <h5 class="text-center mt-1">{{ \Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</h5>
                            <form action="/submit_product_sold_form" method="POST" autocomplete="off">
                                @csrf
                                <input type="hidden" name="transaction_date" value="{{ $transaction_date }}">
                                <input type="hidden" name="branch_warehouse" value="{{ $branch }}">
                                <div class="form-group m-2">
                                    <input type="text" class="form-control" placeholder="Search Items" id="search-filter">
                                </div>
                                <table class="table table-bordered" style="font-size: 8pt;" id="items-table">
                                    <thead>
                                        <th class="text-center p-1" style="width: 55%;">ITEM DESCRIPTION</th>
                                        <th class="text-center p-1" style="width: 45%;">QTY SOLD</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $row)
                                        @php
                                            $img = array_key_exists($row->item_code, $item_images) ? "/img/" . $item_images[$row->item_code][0]->image_path : "/icon/no_img.png";
                                            $img_webp = array_key_exists($row->item_code, $item_images) ? "/img/" . explode('.',$item_images[$row->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
                                            $qty = array_key_exists($row->item_code, $existing_record) ? ($existing_record[$row->item_code] * 1) : 0;
                                        @endphp
                                        <tr>
                                            <td class="text-justify p-1 align-middle" colspan="2">
                                                <div class="d-flex flex-row justify-content-center align-items-center">
                                                    <div class="p-1 col-2 text-center">
                                                        <input type="hidden" name="item[{{ $row->item_code }}][description]" value="{!! strip_tags($row->description) !!}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                        </picture>
                                                    </div>
                                                    <div class="p-1 col-5 m-0">
                                                        <span class="font-weight-bold">{{ $row->item_code }}</span>
                                                    </div>
                                                    <div class="p-1 col-5">
                                                        <div class="input-group p-1">
                                                            <div class="input-group-prepend p-0">
                                                                <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                            </div>
                                                            <div class="custom-a p-0">
                                                                <input type="text" class="form-control form-control-sm qty" value="{{ $qty }}" name="item[{{ $row->item_code }}][qty]" style="text-align: center; width: 80px">
                                                            </div>
                                                            <div class="input-group-append p-0">
                                                                <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-row">
                                                    <div class="p-1">{!! strip_tags($row->description) !!}</div>
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
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-check"></i> SUBMIT</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('script')
<script>
    $(function () {
        $('.qtyplus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                fieldName.val(currentVal + 1);
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
    });
</script>
@endsection