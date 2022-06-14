@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content" style="min-height: 90vh">
	<div class="content-header pt-0">
        <div class="container-fluid">
            <div class="row pt-3">
                <div class="col-md-12 p-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center font-weight-bold">
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
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Stock Adjustments</h6>
                        </div>
                        <div class="card-body p-1">
                            <div class="container-fluid">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#beginning_inventory" style="font-size: 10pt">Beginning Inventory</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#stock_adjustments" style="font-size: 10pt">Stock Adjustments</a>
                                    </li>
                                </ul>
                              
                                <!-- Tab panes -->
                                <div class="tab-content"> 
                                    <div id="beginning_inventory" class="container-fluid tab-pane active" style="padding: 8px 0 0 0;">
                                        <div class="container-fluid">
                                            <form action="/beginning_inv_list" method="get">
                                                <div class="row p-2">
                                                    <div class="col-12 col-lg-2 col-xl-2 offset-xl-3">
                                                        <input type="text" class="form-control" name="search" value="{{ request('search') ? request('search') : null }}" placeholder="Search" />
                                                    </div>
                                                    <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                        @php
                                                            $statuses = ['For Approval', 'Approved', 'Cancelled'];
                                                        @endphp
                                                        <select name="status" class="form-control">
                                                            <option value="" disabled>Select a status</option>
                                                            @foreach ($statuses as $status)
                                                                <option value="{{ $status }}" {{ request('status') ? ( request('status') == $status ? 'selected' : null) : ($status == 'For Approval' ? 'selected' : null) }}>{{ $status }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                        <select name="store" class="form-control">
                                                            <option value="" disabled {{ !request('store') ? 'selected' : null }}>Select a store</option>
                                                            @foreach ($consignment_stores as $store)
                                                                <option value="{{ $store }}" {{ request('store') == $store ? 'selected' : null }}>{{ $store }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-4 col-xl-2 mt-2 mt-lg-0">
                                                        <input type="text" name="date" id="date-filter" class="form-control" value="" />
                                                    </div>
                                                    <div class="col-12 col-lg-2 col-xl-1 mt-2 mt-lg-0">
                                                        <button type="submit" class="btn btn-primary w-100">Search</button>
                                                    </div>
                                                </div>
                                            </form>

                                            <table class="table table-bordered" style="font-size: 10pt;">
                                                <tr>
                                                    <th class="font-responsive text-center">ID</th>
                                                    <th class="font-responsive text-center">Branch</th>
                                                    <th class="font-responsive text-center d-none d-lg-table-cell">Submitted by</th>
                                                    <th class="font-responsive text-center d-none d-lg-table-cell">Submitted at</th>
                                                    <th class="font-responsive text-center d-none d-lg-table-cell">Status</th>
                                                    <th class="font-responsive text-center d-none d-lg-table-cell">Transaction Date</th>
                                                    <th class="font-responsive text-center d-none d-lg-table-cell">Action</th>
                                                </tr>
                                                @forelse ($inv_arr as $inv)
                                                    @php
                                                        $status = 'secondary';
                                                        if($inv['status'] == 'For Approval'){
                                                            $status = 'primary';
                                                        }else if($inv['status'] == 'Approved'){
                                                            $status = 'success';
                                                        }else if($inv['status'] == 'Cancelled'){
                                                            $status = 'danger';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="font-responsive text-center">
                                                            {{ $inv['name'] }}
                                                            <div class="d-block d-lg-none">
                                                                <span class="badge badge-{{ $status }}">{{ $inv['status'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="font-responsive text-center">
                                                            {{ $inv['branch'] }}
                                                        </td>
                                                        <td class="font-responsive text-center d-none d-lg-table-cell">{{ $inv['owner'] }}</td>
                                                        <td class="font-responsive text-center d-none d-lg-table-cell">{{ $inv['creation'] }}</td>
                                                        <td class="font-responsive text-center d-none d-lg-table-cell">
                                                            <span class="badge badge-{{ $status }}">{{ $inv['status'] }}</span>
                                                        </td>
                                                        <td class="font-responsive text-center d-none d-lg-table-cell">{{ $inv['transaction_date'] }}</td>
                                                        <td class="font-responsive text-center d-none d-lg-table-cell">
                                                            <a href="#" data-toggle="modal" data-target="#{{ $inv['name'] }}-Modal">
                                                                View Items
                                                            </a>
                                                              
                                                            <div class="modal fade" id="{{ $inv['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-xl" role="document">
                                                                    <div class="modal-content">
                                                                        <form action="/approve_beginning_inv/{{ $inv['name'] }}" method="post">
                                                                            @csrf
                                                                            <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                                                                                <div class="container-fluid">
                                                                                    <div class="row">
                                                                                        <div class="col-8 text-left font-responsive">
                                                                                        <h4>{{ $inv['branch'] }}</h4>
                                                                                        Inventory Date:<b>{{ $inv['transaction_date'] }} </b><br>
                                                                                        Submitted By:<b>{{ $inv['owner'] }}</b>
                                                                                        </div>
                                                                                        @if ($inv['status'] == 'For Approval')
                                                                                            <div class="col-4 w-100">
                                                                                                @php
                                                                                                    $status_selection = [
                                                                                                        ['title' => 'Approve', 'value' => 'Approved'],
                                                                                                        ['title' => 'Cancel', 'value' => 'Cancelled']
                                                                                                    ];
                                                                                                @endphp
                                                                                                
                                                                                                <div class="input-group pt-2">
                                                                                                    <select class="custom-select font-responsive" name="status" id="inputGroupSelect04" required>
                                                                                                        <option value="" selected disabled>Select a status</option>
                                                                                                        @foreach ($status_selection as $status)
                                                                                                            <option value="{{ $status['value'] }}">{{ $status['title'] }}</option>                                                                                
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                    <div class="input-group-append">
                                                                                                        <button class="btn btn-outline-secondary" type="submit" style="color: #fff">Submit</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                    <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <table class="table table-bordered">
                                                                                    <tr>
                                                                                        <th class="text-center" style="width: 60%; font-size: 10pt" colspan=2>Item</th>
                                                                                        <th class="text-center" style="width: 20%; font-size: 10pt">Opening Stock</th>
                                                                                        <th class="text-center" style="width: 20%; font-size: 10pt">Price</th>
                                                                                    </tr>
                                                                                    @forelse ($inv['items'] as $item)
                                                                                        @php
                                                                                            $img = $item['image'] ? "/img/" . $item['image'] : "/icon/no_img.png";
                                                                                            $img_webp = $item['image'] ? "/img/" . explode('.', $item['image'])[0].'.webp' : "/icon/no_img.webp";
                                                                                        @endphp
                                                                                        <tr>
                                                                                            <td class="text-center col-1">
                                                                                                <picture>
                                                                                                    <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                                                                                                    <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                                                                                                    <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" width="100%">
                                                                                                </picture>
                                                                                            </td>
                                                                                            <td class="text-justify" style="font-size: 10pt">
                                                                                                    {!! '<b>'.$item['item_code'].'</b> - '.strip_tags($item['item_description'] )!!}
                                                                                            </td>
                                                                                            <td class="text-center" style="font-size: 10pt">{!! '<b>'.$item['opening_stock'].'</b> '.$item['uom'] !!}</td>
                                                                                            <td class="text-center" style="font-size: 10pt">
                                                                                                @if ($inv['status'] == 'For Approval')
                                                                                                    ₱ <input type="text" name="price[{{ $item['item_code'] }}][]" value="{{ number_format($item['price'], 2) }}" style="text-align: center; width: 80px;" required/>
                                                                                                @else
                                                                                                    ₱ {{ number_format($item['price'], 2) }}
                                                                                                @endif
                                                                                            </td>
                                                                                        </tr>
                                                                                    @empty
                                                                                        <tr>
                                                                                            <td class="text-center" colspan=4>No Item(s)</td>
                                                                                        </tr>
                                                                                    @endforelse
                                                                                </table>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="d-lg-none">
                                                        <td colspan=2 class="text-center">
                                                            <div class="d-block d-lg-none">
                                                                {{-- <table class="table text-left">
                                                                    <tr>
                                                                        <th class="p-0" style="width: 40%">Transaction Date: </th>
                                                                        <td class="p-0">{{ $inv['transaction_date'] }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="p-0" style="width: 40%">Submitted at: </th>
                                                                        <td class="p-0">{{ $inv['creation'] }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th class="p-0" style="width: 40%">Submitted by: </th>
                                                                        <td class="p-0">{{ $inv['owner'] }}</td>
                                                                    </tr>
                                                                </table> --}}
                                                                <a href="#" data-toggle="modal" data-target="#{{ $inv['name'] }}-Modal2">
                                                                    View Items
                                                                </a>
                    
                                                                <!-- Modal(mobile) -->
                                                                <div class="modal fade" id="{{ $inv['name'] }}-Modal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                    <div class="modal-dialog" role="document">
                                                                        <div class="modal-content">
                                                                            <form action="/approve_beginning_inv/{{ $inv['name'] }}" method="post">
                                                                                @csrf
                                                                                <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                                                                                    <div class="container-fluid">
                                                                                        <div class="row">
                                                                                            <div class="col-12 col-lg-8 text-left font-responsive">
                                                                                            <h4>{{ $inv['branch'] }}</h4>
                                                                                            Inventory Date:<b>{{ $inv['transaction_date'] }} </b><br>
                                                                                            Submitted By:<b>{{ $inv['owner'] }}</b>
                                                                                            </div>
                                                                                            @if ($inv['status'] == 'For Approval')
                                                                                                <div class="col-12 col-lg-4 w-100">
                                                                                                    @php
                                                                                                        $status_selection = [
                                                                                                            ['title' => 'Approve', 'value' => 'Approved'],
                                                                                                            ['title' => 'Cancel', 'value' => 'Cancelled']
                                                                                                        ];
                                                                                                    @endphp
                                                                                                    <div class="input-group pt-2">
                                                                                                        <select class="custom-select font-responsive" name="status" id="inputGroupSelect04" required>
                                                                                                            <option value="" selected disabled>Select a status</option>
                                                                                                            @foreach ($status_selection as $status)
                                                                                                                <option value="{{ $status['value'] }}">{{ $status['title'] }}</option>                                                                                
                                                                                                            @endforeach
                                                                                                        </select>
                                                                                                        <div class="input-group-append">
                                                                                                            <button class="btn btn-outline-secondary" type="submit" style="color: #fff">Submit</button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                        <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <table class="table table-bordered" id='mob-table'>
                                                                                        <tr>
                                                                                            <th class="text-center" style="width: 60%; font-size: 10pt" colspan=2>Item</th>
                                                                                            <th class="text-center" style="width: 20%; font-size: 10pt">Opening Stock</th>
                                                                                            <th class="text-center" style="width: 20%; font-size: 10pt">Price</th>
                                                                                        </tr>
                                                                                        @forelse ($inv['items'] as $item)
                                                                                            @php
                                                                                                $img = $item['image'] ? "/img/" . $item['image'] : "/icon/no_img.png";
                                                                                                $img_webp = $item['image'] ? "/img/" . explode('.', $item['image'])[0].'.webp' : "/icon/no_img.webp";
                                                                                            @endphp
                                                                                            <tr>
                                                                                                <td colspan=2 class="text-center col-3" style="font-size: 10pt;">
                                                                                                    <div class="row">
                                                                                                        <div class="col-4">
                                                                                                            <picture>
                                                                                                                <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                                                                                                                <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                                                                                                                <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" width="100%">
                                                                                                            </picture>
                                                                                                        </div>
                                                                                                        <div class="col-5" style="display: flex; justify-content: center; align-items: center;">
                                                                                                            <b>{!! $item['item_code'] !!}</b>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </td>
                                                                                                <td class="text-center" style="font-size: 10pt">
                                                                                                    <b>{!! $item['opening_stock'] !!}</b> <br>
                                                                                                    <small>{{ $item['uom'] }}</small>
                                                                                                </td>
                                                                                                <td class="text-center" style="font-size: 10pt; white-space: nowrap">
                                                                                                    @if ($inv['status'] == 'For Approval')
                                                                                                        ₱ <input type="text" name="price[{{ $item['item_code'] }}][]" value="{{ number_format($item['price'], 2) }}" style="text-align: center; width: 80px;" required/>
                                                                                                    @else
                                                                                                        ₱ {{ number_format($item['price'], 2) }}
                                                                                                    @endif
                                                                                                </td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td class='text-justify' colspan=4>
                                                                                                    <div class="item-description-modal">
                                                                                                        {{ strip_tags($item['item_description']) }}
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        @empty
                                                                                            <tr>
                                                                                                <td class="text-center" colspan=4>No Item(s)</td>
                                                                                            </tr>
                                                                                        @endforelse
                                                                                    </table>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="font-responsive text-center" colspan=7>
                                                            No submitted beginning inventory
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </table>
                                            <div class="float-right mt-4">
                                                {{ $beginning_inventory->links('pagination::bootstrap-4') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="container-fluid tab-pane" id="stock_adjustments" style="padding: 8px 0 0 0;">
                                        Stock Adjustments
                                    </div>
                                </form>
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
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            var from_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[0])->format("Y-M-d") : Carbon\Carbon::now()->subDays(7)->format("Y-M-d")  }}'
            var to_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[1])->format("Y-M-d") : Carbon\Carbon::now()->format("Y-M-d")  }}'
            $('#date-filter').daterangepicker({
                opens: 'left',
                startDate: from_date,
                endDate: to_date,
                locale: {
                    format: 'YYYY-MMM-DD',
                    separator: " to "
                },
            });

            $(document).on('click', '.show-more', function(e) {
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
        });
    </script>
@endsection