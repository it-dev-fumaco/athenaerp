@extends('layout', [
    'namePage' => 'Stock Transfers List',
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
                                <div class="alert alert-success alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <h6 class="text-center mt-1 font-weight-bold">Stock Transfers List</h6>
                        </div>
                        <div class="card-header text-center ">
                            <span class="font-responsive text-uppercase d-inline-block">{{ \Carbon\Carbon::now()->format('F d, Y') }}</span>
                           
                            <a href="/stock_transfer/form" class="btn btn-xs btn-outline-primary float-right">Create Stock Transfer</a>
                        </div>
                        <div class="container-fluid">
                            <span class="float-right p-2" style="font-size: 10pt;"><b>Total: </b>{{ $stock_transfers->total() }}</span>
                        </div>
                        <div class="card-body p-1">
                            <table class="table table-striped" style="font-size: 10pt">
                                <tr>
                                    <th class="text-center d-none d-lg-table-cell">Name</th>
                                    <th class="text-center mobile-first-row">
                                        <span class="d-none d-lg-inline">From Warehouse</span>
                                        <span class="d-inline d-lg-none">Details</span>
                                    </th>
                                    <th class="text-center d-none d-lg-table-cell">To Warehouse</th>
                                    <th class="text-center d-none d-lg-table-cell">Submitted By</th>
                                    <th class="text-center d-none d-lg-table-cell">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                                @foreach ($ste_arr as $ste)
                                    @php
                                        if($ste['docstatus'] == 1){
                                            $badge = 'success';
                                            $status = 'Approved';
                                        }else{
                                            $badge = 'primary';
                                            $status = 'For Approval';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="text-center d-none d-lg-table-cell">
                                            {{ $ste['name'] }}
                                        </td>
                                        <td>
                                            <div class="d-none d-lg-inline text-center">
                                                {{ $ste['from_warehouse'] }}
                                            </div>
                                            <div class="d-inline d-lg-none text-left">
                                                {{ $ste['name'] }} <br>
                                                <b>From: </b>{{ $ste['from_warehouse'] }} <br>
                                                <b>To: </b>{{ $ste['to_warehouse'] == 'Quarantine Warehouse P2 - FI' ? 'Fumaco - Plant 2' : $ste['to_warehouse'] }} <br>
                                                <b>By: </b>{{ $ste['owner'] }}
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell">{{ $ste['to_warehouse'] == 'Quarantine Warehouse P2 - FI' ? 'Fumaco - Plant 2' : $ste['to_warehouse'] }}</td>
                                        <td class="text-center d-none d-lg-table-cell">{{ $ste['owner'] }}</td>
                                        <td class="text-center d-none d-lg-table-cell">
                                            <span class="badge badge-{{ $badge }}">{{ $status }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="#" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal">
                                                View items
                                            </a>
                                            <span class="badge badge-{{ $badge }} d-xl-none">{{ $status }}</span>
                                            <!-- Modal -->
                                            <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="background-color: #001F3F; color: #fff">
                                                            <div class="row text-left">
                                                                <div class="col-12">
                                                                    <h5 id="exampleModalLabel"><b>{{ $ste['name'] }}</b></h5>
                                                                </div>
                                                                <div class="col-12" style="font-size: 9.5pt;">
                                                                    <span class="font-italic"><b>Source: </b> {{ $ste['from_warehouse'] }}</span>
                                                                </div>
                                                                <div class="col-12" style="font-size: 9.5pt;">
                                                                    <span class="font-italic"><b>Target: </b> {{ $ste['to_warehouse'] }}</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true" style="color: #fff">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <table class="table table-bordered" style="font-size: 10pt;">
                                                                <tr>
                                                                    <th class="text-center" width="50%">Item</th>
                                                                    <th class="text-center">Stock Qty</th>
                                                                    <th class="text-center">Qty to Transfer</th>
                                                                </tr>
                                                                @foreach ($ste['items'] as $item)
                                                                    <tr>
                                                                        <td class="text-center p-0">
                                                                            <div class="row">
                                                                                <div class="col-4">
                                                                                    <picture>
                                                                                        <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                                                        <source srcset="{{ asset('storage/'.$item['image']) }}" type="image/jpeg">
                                                                                        <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" class="w-100">
                                                                                    </picture>
                                                                                </div>
                                                                                <div class="col-5" style="display: flex; justify-content: center; align-items: center;">
                                                                                    <b>{{ $item['item_code'] }}</b>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <b>{{ $item['consigned_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <b>{{ $item['transfer_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                            </td>
                                                                    </tr>
                                                                    <tr class="p-2">
                                                                        <td colspan=3 class="text-justify">
                                                                            <div class="item-description">
                                                                                {{ strip_tags($item['description']) }}
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        </div>
                                                        <div class="modal-footer {{ $ste['docstatus'] == 1 ? 'd-none' : null }}">
                                                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancel-{{ $ste['name'] }}-Modal">
                                                                Cancel
                                                            </button>
                                                              
                                                              <!-- Modal -->
                                                            <div class="modal fade" id="cancel-{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header" style="background-color: #001F3F; color: #fff">
                                                                            <span class="modal-title" id="exampleModalLabel">{{ $ste['name'] }}</span>
                                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                <span aria-hidden="true" style="color: #fff;">&times;</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <h5>Cancel {{ $ste['name'] }}?</h5>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <a href="/stock_transfer/cancel/{{ $ste['name'] }}" class="btn btn-danger">Cancel</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Modal -->
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            <div class="mt-3 ml-3 clearfix pagination" style="display: block;">
                                <div class="offset-8 col-md-4">
                                    {{ $stock_transfers->links() }}
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
        .morectnt span {
            display: none;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
        @media (max-width: 575.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media (max-width: 767.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .mobile-first-row{
                width: 70%;
            }
        }

    </style>
@endsection

@section('script')
    <script>
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
    </script>    
@endsection