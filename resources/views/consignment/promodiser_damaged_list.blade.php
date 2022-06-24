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
                            @if(session()->has('success'))
                                <div class="p-2">
                                    <div class="alert alert-success fade show font-responsive text-center" role="alert">
                                        {{ session()->get('success') }}
                                    </div>
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="p-2">
                                    <div class="alert alert-danger fade show font-responsive text-center" role="alert">
                                        {{ session()->get('error') }}
                                    </div>
                                </div>
                            @endif
                            <div class="card-header text-center">
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Damaged Items List</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="col-12">
                                    <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
                                </div>
                               <table class="table" id="items-table" style="font-size: 9.5pt">
                                    <tr>
                                        <th class="text-center" style="width: 75%">Item</th>
                                        <th class="text-center" style="width: 25%">Action</th>
                                    </tr>
                                    @forelse ($damaged_arr as $i => $item)
                                        <tr>
                                            <td class="text-center p-2" style="width: 75%">
                                                <div class="row">
                                                    <div class="col-3">
                                                        <picture>
                                                            <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                            <source srcset="{{ asset('storage/'.$item['image']) }}" type="image/jpeg">
                                                            <img src="{{ asset('storage'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" class="img-thumbnail" width="100%">
                                                        </picture>
                                                    </div>
                                                    <div class="col-9 text-left p-1">
                                                        <b>{{ $item['item_code'] }}</b> <span class="badge badge-{{ $item['status'] == 'Returned' ? 'success' : 'primary' }}">{{ $item['status'] == 'Returned' ? $item['status'] : 'For Return' }}</span>
                                                        <div class="col-12 text-justify p-0">
                                                            {{ $item['store'] }} <br>
                                                            <b>Reason: </b> {{ $item['damage_description'] }} <br>
                                                            <b>Qty: </b> {{ number_format($item['damaged_qty']) }} <small style="white-space: nowrap">{{ $item['uom'] }}</small>
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
                                            <td class="text-center" style="width: 25%">
                                                <a href='#' data-toggle="modal" data-target="#view-item-details-{{ $i }}-Modal">
                                                    View
                                                </a>
                                                  
                                                <!-- Modal -->
                                                <div class="modal fade" id="view-item-details-{{ $i }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                                                                <div class="row text-left">
                                                                    <div class="col-12">
                                                                        <h5>Damaged Items</h5>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <h6 id="exampleModalLabel">{{ $item['store'] }} <span class="badge badge-{{ $item['status'] == 'Returned' ? 'success' : 'primary' }}">{{ $item['status'] == 'Returned' ? $item['status'] : 'For Return' }}</span></h6>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <span class="font-italic">{{ $item['promodiser'].' - '.Carbon\Carbon::parse($item['creation'])->format('F d, Y') }}</span>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true" style="color: #fff;">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="callout callout-info text-center">
                                                                    <small><i class="fas fa-info-circle"></i> Consignment Supervisor will notify that there are damaged/defective item in your store.</small>
                                                                </div>
                                                                <table class="table">
                                                                    <tr>
                                                                        <th style="width: 65% !important">Item</th>
                                                                        <th>Qty</th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="p-0">
                                                                            <div class="row">
                                                                                <div class="col-4">
                                                                                    <picture>
                                                                                        <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                                                        <source srcset="{{ asset('storage/'.$item['image']) }}" type="image/jpeg">
                                                                                        <img src="{{ asset('storage'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" class="img-thumbnail" width="100%">
                                                                                    </picture>
                                                                                </div>
                                                                                <div class="col-4" style="display: flex; justify-content: center; align-items: center;">
                                                                                    <b>{{ $item['item_code'] }}</b>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="container" style="display: flex; justify-content: center; align-items: center;">
                                                                                <div>
                                                                                    <b>{{ number_format($item['damaged_qty']) }}</b> <br>
                                                                                    <small>{{ $item['uom'] }}</small>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan=2 class="text-justify p-0">
                                                                            <div class="p-2 item-description container-fluid text-justify">
                                                                                {{ strip_tags($item['item_description']) }} <br>
                                                                            </div>
                                                                            <div class='p-2'>
                                                                                <b>Reason: </b> {{ $item['damage_description'] }} <br>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            @if ($item['status'] != 'Returned')
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#confirm-{{ $i }}-Modal">Return to Plant</button>

                                                                    <!-- Modal -->
                                                                    <div class="modal fade confirm" id="confirm-{{ $i }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                        <div class="modal-dialog" role="document">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header" style='background-color: #001F3F; color: #fff'>
                                                                                    <h5 class="modal-title" id="exampleModalLabel">Return to Plant</h5>
                                                                                    <button type="button" onclick="close_modal('#confirm-{{ $i }}-Modal')" style="background-color: rgba(0,0,0,0); border: none;">
                                                                                        <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    Return {{ $item['item_code'] }} to Plant?
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <a href="/damaged/return/{{ $item['name'] }}" class="btn btn-primary w-100">Confirm</a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan=2 class="p-0">
                                                <div class="d-none"><!-- For Search -->
                                                    {{ $item['item_code'] }}
                                                </div>
                                                <div class="p-2 item-description container-fluid text-justify">
                                                    {{ strip_tags($item['item_description']) }} <br>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan=2 class='text-center'>
                                                No damaged item(s) reported.
                                            </td>
                                        </tr>
                                    @endforelse
                               </table>
                               <div class="mt-3 ml-3 clearfix pagination" style="display: block;">
                                    <div class="col-md-4 float-right">
                                        {{ $damaged_items->links() }}
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
        .modal .confirm{
            background-color: rgba(0,0,0,0.4);
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