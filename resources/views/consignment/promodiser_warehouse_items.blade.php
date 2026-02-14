@extends('layout', [
    'namePage' => 'Inventory Summary',
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
                            @if (count($assignedConsignmentStores) > 1)
                                <select id="warehouse" class="form-control">
                                    @foreach ($assignedConsignmentStores as $store)
                                        <option value="{{ $store }}" {{ $store == $branch ? 'selected' : null }}>{{ $store }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">
                                    {{ $branch }}
                                </span>
                            @endif
                        </div>
                        <div class="card-body p-1 border">
                            <div class="col-12 p-0">
                                <input type="text" class="form-control mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
                            </div>
                            <table class="table" id='items-table' style="font-size: 13px;">
                                <col style="width: 18%;">
                                <col style="width: 25%;">
                                <col style="width: 30%;">
                                <col style="width: 27%;">
                                <thead class="border-top text-uppercase" style="font-size: 12px;">
                                    <tr>
                                        <th class="text-center align-middle p-1" colspan="2">Item Code</th>
                                        <th class="text-center align-middle p-1">Available Qty</th>
                                        <th class="text-center align-middle p-1">Price</th>
                                    </tr>
                                </thead>
                                @forelse ($invSummary as $item)
                                @php
                                    $img = isset($item->defaultImage->image_path) ? '/img/'.$item->defaultImage->image_path : '/icon/no_img.png';
                                    if(Storage::disk('public')->exists(explode('.', $item->image)[0].'.webp')){
                                        $img = explode('.', $item->image)[0].'.webp';
                                    }

                                    $img = Storage::disk(upcloud)->url($img");
                                @endphp
                                <tbody>
                                    <tr>
                                        <td class="p-1 align-middle" rowspan="2">
                                            <div>
                                                <a href="{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $item->item_code }}" data-title="{{ $item->item_code }}">
                                                    <img src="{{ $img }}" alt="{{ Illuminate\Support\Str::slug(strip_tags($item->description), '-') }}" class="w-100">
                                                </a>
                                            </div>
                                        </td>
                                        <td class="p-1 align-middle">
                                            <b>{{ $item->item_code }}</b>
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <p>
                                                <span class="font-weight-bold d-block">{{ number_format($item->bin[0]->consigned_qty) }}</span>
                                                <small class="text-muted">{{ $item->stock_uom }}</small>
                                            </p>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">
                                            {{ '₱ ' . number_format($item->bin[0]->consignment_price, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="p-1">
                                            <div class="text-justify">
                                                <div class="item-description" style="font-size: 12px; letter-spacing: 0;">{!! $item->description !!}</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                @empty
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-uppercase text-muted">
                                        No item(s) found. 
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
        table { width: 100%; }
        .morectnt span {
            display: none;
        }

        tbody:nth-child(odd) {
            background-color: #E5E7E9;
            border: 1px solid #dee2e6;
        }

        tbody:nth-child(even) {
            background-color:#F8F9F9;
            border: 1px solid #dee2e6;
        }
    </style>
@endsection

@section('script')
    <script>
        var showTotalChar = 85, showChar = "Show more", hideChar = "Show less";
        $('.item-description').each(function() {
            var content = $(this).text();
            if (content.length > showTotalChar) {
                var con = content.substr(0, showTotalChar);
                var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="showmoretxt">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $('#warehouse').change(function(){
            window.location.href = "/inventory_items/" + $(this).val();
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

        $("#item-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    </script>
@endsection