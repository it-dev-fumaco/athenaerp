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
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Incoming Deliveries</span>
                        </div>
                        <div class="card-body p-1">
                            <span class="float-right mr-3" style="font-size: 10pt">Total items: {{ count($ste_arr) }}</span>
                            <table class="table table-bordered" style='font-size: 10pt;'>
                                <tr>
                                    <th class="text-center" style='width: 30%'>Name</th>
                                    <th class="text-center">Warehouse</th>
                                </tr>
                                @foreach ($ste_arr as $ste)
                                    <tr>
                                        <td class="text-center">
                                            {{ $ste['name'] }}
                                            <span class="badge badge-{{ $ste['status'] == 'For Checking' ? 'warning' : 'success' }}">{{ $ste['status'] }}</span>
                                        </td>
                                        <td>
                                            <a href="#" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal">{{ $ste['to_consignment'] }}</a> <br><br>
                                            <span><b>Delivery Date:</b> {{ Carbon\Carbon::parse($ste['delivery_date'])->format('F d, Y') }}</span>

                                            <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Delivered Items</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
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
                                                                                        <div class="col-6 text-center">
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
                                                                                    ₱ {{ $item['price'] * 1 }}
                                                                                </div>
                                                                                <div id="{{ $id }}-description" class="col-12 ste-description pt-2 text-justify" style="height: 50px; overflow: hidden;">
                                                                                    {{ strip_tags($item['description']) }}
                                                                                </div>
                                                                                <div class="col-12 text-center pt-2">
                                                                                    <button class="btn btn-xs btn-outline-primary show-more" id="{{ $id }}-show-more" data-id="{{ $id }}">Show More</button>
                                                                                    <button class="btn btn-xs btn-outline-primary d-none show-less" id="{{ $id }}-show-less" data-id="{{ $id }}">Show Less</button>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        </div>
                                                        @if ($ste['delivery_status'] == 0)
                                                            <div class="modal-footer">
                                                                <a href="/promodiser/receive/{{ $ste['name'] }}" class="btn btn-success">Receive</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
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
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $('.show-more').click(function(){
                $('#'+ $(this).data('id')+ '-description').css('height', 'auto');
                $(this).addClass('d-none');
                $('#'+$(this).data('id')+ '-show-less').removeClass('d-none');
            });

            $('.show-less').click(function(){
                $('#'+ $(this).data('id')+ '-description').css('height', '50px');
                $(this).addClass('d-none');
                $('#'+$(this).data('id')+ '-show-more').removeClass('d-none');
            });
        });
    </script>
@endsection