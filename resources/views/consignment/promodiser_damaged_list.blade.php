@extends('layout', [
    'namePage' => 'Damaged Items List',
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
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Damaged Items List</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="col-12">
                                    <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
                                </div>
                               <table class="table" id="items-table" style="font-size: 10pt">
                                    <tr>
                                        <th class="text-center" style="width: 80%">Item</th>
                                        <th class="text-center" style="width: 20%">Qty</th>
                                    </tr>
                                    @foreach ($damaged_arr as $item)
                                        <tr>
                                            <td class="text-center p-2" style="width: 80%">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <picture>
                                                            <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                            <source srcset="{{ asset('storage/'.$item['image']) }}" type="image/jpeg">
                                                            <img src="{{ asset('storage'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" class="img-thumbnail" width="100%">
                                                        </picture>
                                                    </div>
                                                    <div class="col-9 text-left">
                                                        <b>{{ $item['item_code'] }}</b>
                                                        <div class="p-0 item-description container-fluid text-justify">
                                                            {{ strip_tags($item['item_description']) }} <br>
                                                        </div>
                                                    </div>
                                                    <div class="d-none"><!-- For Search -->
                                                        {{ $item['store'] }} <br>
                                                        {{ $item['damage_description'] }} <br>
                                                        {{ $item['promodiser'] }} <br>
                                                        {{ Carbon\Carbon::parse($item['creation'])->format('F d, Y') }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center" style="width: 20%">
                                                <div class="pt-2">
                                                    <b>{{ number_format($item['damaged_qty']) }}</b> <br>
                                                    <span style="white-space: nowrap">{{ $item['uom'] }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan=2 class="pl-2 pt-2">
                                                <span class="d-none">{{ $item['item_code'].' '.strip_tags($item['item_description']) }}</span><!-- For Search -->
                                                <b>Damage Description: </b> {{ $item['damage_description'] }} <br>
                                                <b>Store: </b> {{ $item['store'] }} <br>
                                                <b>By:</b> {{ $item['promodiser'] }} <br>
                                                <b>Date: </b> {{ Carbon\Carbon::parse($item['creation'])->format('F d, Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
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
        @media (max-width: 575.98px) {
        }
        @media (max-width: 767.98px) {
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
        }

    </style>
@endsection

@section('script')
    <script>
        var showTotalChar = 120, showChar = "Show more", hideChar = "Show less";
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

        $("#item-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    </script>
@endsection