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
                        <div class="card-header text-center">
                            <span class="font-weight-bold d-block font-responsive">{{ $branch }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold p-1">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-1" style="font-size: 10pt">
                                <span><b>Transaction Date:</b> {{ Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</span> <br>
                                <span><b>Total items:</b> {{ count($inventory) }}</span>
                            </div>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="font-responsive text-center p-2" style="width: 50%">Item Description</th>
                                    <th class="font-responsive text-center">Opening Stock</th>
                                    <th class="font-responsive text-center">Price</th>
                                </tr>
                                @forelse ($inventory as $inv)
                                    @php
                                        $img = isset($item_image[$inv->item_code]) ? "/img/" . $item_image[$inv->item_code][0]->image_path : "/icon/no_img.png";
                                        $img_webp = isset($item_image[$inv->item_code]) ? "/img/" . explode('.',$item_image[$inv->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
                                    @endphp 
                                    <tr>
                                        <td class="text-center font-responsive p-1">
                                            <div class="row">
                                                <div class="col-4">
                                                    <picture>
                                                        <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                        <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                        <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                    </picture>
                                                </div>
                                                <div class="col-6 font-weight-bold" style="display: flex; justify-content: center; align-items: center;">
                                                    {{ $inv->item_code }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-responsive">
                                            {{ $inv->opening_stock * 1 }}
                                        </td>
                                        <td class="text-center font-responsive">
                                            ₱ {{ number_format($inv->price * 1, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan=3 class="text-justify p-2" style="font-size: 9.5pt;">
                                            <span class="item-description">
                                                {{ strip_tags($inv->item_description) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="font-responsive text-center" colspan=3>
                                            No available item(s) / All items for this branch are approved.
                                        </td>
                                    </tr>
                                @endforelse
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
        table {
            table-layout: fixed;
            width: 100%;
        }
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
    </script>
@endsection