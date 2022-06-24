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
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            @if(session()->has('success'))
                                <div class="alert alert-success fade show font-responsive" role="alert">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">
                                @if ($type == 'all')
                                    Delivery Report
                                @else
                                    Incoming Deliveries                                    
                                @endif
                            </span>
                        </div>
                        <div class="card-body p-1">
                            <span class="float-right mr-3" style="font-size: 10pt">Total items: {{ $delivery_report->total() }}</span>
                            <table class="table table-striped" style='font-size: 10pt;'>
                                <tr>
                                    <th class="text-center" style='width: 30%'>Name</th>
                                    <th class="text-center">Store</th>
                                </tr>
                                @forelse ($ste_arr as $ste)
                                    <tr>
                                        <td class="text-center">
                                            {{ $ste['name'] }}
                                            <span class="badge badge-{{ $ste['status'] == 'Pending' ? 'warning' : 'success' }}">{{ $ste['status'] }}</span>
                                        </td>
                                        <td>
                                            <a href="#" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal">{{ $ste['to_consignment'] }}</a>
                                            @if ($ste['status'] == 'Delivered')
                                                <span class="badge badge-{{ $ste['delivery_status'] == 0 ? 'warning' : 'success' }}">{{ $ste['delivery_status'] == 0 ? 'To Receive' : 'Received' }}</span>
                                            @endif
                                            <br>
                                            <span><b>Delivery Date:</b> {{ Carbon\Carbon::parse($ste['delivery_date'])->format('F d, Y') }}</span>

                                            <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="background-color: #001F3F; color: #fff">
                                                            <div class="row w-100">
                                                                <div class="col-12">
                                                                    <span>Delivered Items</span> <br>
                                                                    <span>{{ $ste['to_consignment'] }}</span><br>
                                                                    <span>{{ Carbon\Carbon::parse($ste['delivery_date'])->format('F d, Y') }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true" style="color: #fff">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="callout callout-info text-center">
                                                                <small><i class="fas fa-info-circle"></i> Once items are received, stocks will be automatically added to your current inventory.</small>
                                                            </div>
                                                            <table class="table table-bordered">
                                                                <tr>
                                                                    <th class="text-center" style="width: 50%">Item</th>
                                                                    <th class="text-center"><span style="white-space: nowrap">Delivered</span> Qty</th>
                                                                    <th class="text-center">Price</th>
                                                                </tr>
                                                                @foreach ($ste['items'] as $item)
                                                                    @php
                                                                        $id = $ste['name'].'-'.$item['item_code'];

                                                                        $img = $item['image'] ? "/img/" . $item['image'] : "/icon/no_img.png";
                                                                        $img_webp = $item['image'] ? "/img/" . explode('.', $item['image'])[0].'.webp' : "/icon/no_img.webp";
                                                                    @endphp
                                                                    <tr>
                                                                        <td colspan=3>
                                                                            <div class="row">
                                                                                <div class="col-6">
                                                                                    <div class="row">
                                                                                        <div class="col-6">
                                                                                            <picture>
                                                                                                <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                                                                                                <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                                                                                                <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" width="100%">
                                                                                            </picture>
                                                                                        </div>
                                                                                        <div class="col-6" style="display: flex; justify-content: center; align-items: center;">
                                                                                            <b>{{ $item['item_code'] }}</b>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-3 text-center" style="display: flex; justify-content: center; align-items: center;">
                                                                                    <b>{{ $item['delivered_qty'] * 1 }}</b>&nbsp;<small>{{ $item['stock_uom'] }}</small>
                                                                                </div>
                                                                                <div class="col-3 text-center" style="display: flex; justify-content: center; align-items: center;">
                                                                                    <span style="white-space: nowrap">₱ {{ number_format($item['price'] * 1, 2) }}</span>
                                                                                </div>
                                                                                <div class="col-12 item-description ste-description pt-2 text-justify">
                                                                                    {{ strip_tags($item['description']) }}
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        </div>
                                                        @if ($ste['status'] == 'Delivered' && $ste['delivery_status'] == 0)
                                                            <div class="modal-footer">
                                                                <a href="/promodiser/receive/{{ $ste['name'] }}" class="btn btn-primary w-100">Receive</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan=2 class="text-center">
                                            @if ($type == 'all')
                                                No delivery record(s)
                                            @else
                                                No incoming deliveries
                                            @endif
                                        </td>
                                    </tr> 
                                @endforelse
                            </table>
                            <div class="mt-3 ml-3 clearfix pagination" style="display: block;">
                                <div class="col-md-4 float-right">
                                    {{ $delivery_report->links() }}
                                </div>
                            </div>
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
        $(document).ready(function(){
            var showTotalChar = 150, showChar = "Show more", hideChar = "Show less";
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