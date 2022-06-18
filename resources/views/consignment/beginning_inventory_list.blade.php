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
                        <div class="card-body p-0">
                            <div class="container-fluid p-0">
                                <div id="beginning_inventory" class="container-fluid">
                                    <form action="/beginning_inv_list" method="get">
                                        <div id="accordion">
                                            <div class="card" style='border: none !important; box-shadow: none !important'>
                                                <div class="card-header p-0" id="headingOne">
                                                    <h5 class="mb-0">
                                                    <button type="button" class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 10pt;">
                                                        <i class="fa fa-filter"></i> Filters
                                                    </button>
                                                    </h5>
                                                </div>
                                            
                                                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                                    <div class="card-body p-0">
                                                        <div class="row p-2">
                                                            <div class="col-12 col-lg-2 col-xl-2 offset-xl-3">
                                                                <input type="text" class="form-control filters-font" name="search" value="{{ request('search') ? request('search') : null }}" placeholder="Search"/>
                                                            </div>
                                                            <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                                @php
                                                                    $statuses = ['For Approval', 'Approved', 'Cancelled'];
                                                                @endphp
                                                                <select name="status" class="form-control filters-font">
                                                                    <option value="" disabled {{ Auth::user()->user_group == 'Promodiser' && !request('status') ? 'selected' : null }}>Select a status</option>
                                                                    <option value="All" {{ request('status') ? ( request('status') == 'All' ? 'selected' : null) : null }}>Select All</option>
                                                                    @foreach ($statuses as $status)
                                                                        @php
                                                                            $selected = null;
                                                                            if(request('status')){
                                                                                if(request('status') == $status){
                                                                                    $selected = 'selected';
                                                                                }
                                                                            }else{
                                                                                if(Auth::user()->user_group == 'Consignment Supervisor'){
                                                                                    $selected = $status == 'For Approval' ? 'selected' : null;
                                                                                }
                                                                            }
                                                                        @endphp
                                                                        <option value="{{ $status }}" {{ $selected }}>{{ $status }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                                <select name="store" class="form-control filters-font">
                                                                    <option value="" disabled {{ !request('store') ? 'selected' : null }}>Select a store</option>
                                                                    @foreach ($consignment_stores as $store)
                                                                        <option value="{{ $store }}" {{ request('store') == $store ? 'selected' : null }}>{{ $store }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-12 col-lg-4 col-xl-2 mt-2 mt-lg-0">
                                                                <input type="text" name="date" id="date-filter" class="form-control filters-font" value="" />
                                                            </div>
                                                            <div class="col-12 col-lg-2 col-xl-1 mt-2 mt-lg-0">
                                                                <button type="submit" class="btn btn-primary filters-font w-100" >Search</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <table class="table table-striped" style="font-size: 10pt;">
                                        <tr>
                                            <th class="font-responsive text-center d-none d-lg-table-cell">Date</th>
                                            <th class="font-responsive text-center">Branch</th>
                                            <th class="font-responsive text-center d-none d-lg-table-cell">Submitted by</th>
                                            <th class="font-responsive text-center d-none d-lg-table-cell">Status</th>
                                            <th class="font-responsive text-center last-row">Action</th>
                                        </tr>
                                        @forelse ($inv_arr as $inv)
                                            @php
                                                $badge = 'secondary';
                                                if($inv['status'] == 'For Approval'){
                                                    $badge = 'primary';
                                                }else if($inv['status'] == 'Approved'){
                                                    $badge = 'success';
                                                }else if($inv['status'] == 'Cancelled'){
                                                    $badge = 'danger';
                                                }

                                                $modal_form = Auth::user()->user_group == 'Consignment Supervisor' && $inv['status'] == 'For Approval' ? '/approve_beginning_inv/'.$inv['name'] : '/stock_adjust/submit/'.$inv['name'];
                                            @endphp
                                            <tr>
                                                <td class="font-responsive text-center d-none d-lg-table-cell">
                                                    <span style="white-space: nowrap">{{ $inv['transaction_date'] }}</span>
                                                </td>
                                                <td class="font-responsive text-left text-xl-center">
                                                    {{ $inv['branch'] }}
                                                    <div class="row pl-2 d-block text-left d-lg-none">
                                                        <b>By:</b>&nbsp;{{ $inv['owner'] }} <br>
                                                        <b>Date:</b>&nbsp;<span style="white-space: nowrap">{{ $inv['transaction_date'] }}</span>
                                                    </div>
                                                </td>
                                                <td class="font-responsive text-center d-none d-lg-table-cell">{{ $inv['owner'] }}</td>
                                                <td class="font-responsive text-center d-none d-lg-table-cell">
                                                    <span class="badge badge-{{ $badge }}">{{ $inv['status'] }}</span>
                                                </td>
                                                <td class="font-responsive text-center p-0 pt-3">
                                                    <a href="#" data-toggle="modal" data-target="#{{ $inv['name'] }}-Modal">
                                                        View Items
                                                    </a>
                                                    <span class="badge badge-{{ $badge }} d-xl-none">{{ $inv['status'] }}</span>
                                                        
                                                    <div class="modal fade" id="{{ $inv['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl" role="document">
                                                            <div class="modal-content">
                                                                <form action="{{ $modal_form }}" method="post">
                                                                    @csrf
                                                                    <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                                                                        <div class="container-fluid">
                                                                            <div class="row">
                                                                                <div class="col-8 text-left font-responsive">
                                                                                <h4>{{ $inv['branch'] }}</h4>
                                                                                Inventory Date:<b>{{ $inv['transaction_date'] }} </b><br>
                                                                                Submitted By:<b>{{ $inv['owner'] }}</b>
                                                                                </div>
                                                                                @if (Auth::user()->user_group == 'Consignment Supervisor' && $inv['status'] == 'For Approval')
                                                                                    <div class="col-12 col-xl-4 w-100">
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
                                                                        <table class="table table-striped">
                                                                            <tr>
                                                                                <th class="text-center" style="width: 60%; font-size: 10pt" colspan=2>Item</th>
                                                                                <th class="text-center" style="width: 15%; font-size: 10pt">Opening Stock</th>
                                                                                <th class="text-center" style="width: 15%; font-size: 10pt">Price</th>
                                                                                @if ($inv['status'] == 'Approved')
                                                                                    <th class="text-center">-</th>
                                                                                @endif
                                                                            </tr>
                                                                            @forelse ($inv['items'] as $item)
                                                                                @php
                                                                                    $img = $item['image'] ? "/img/" . $item['image'] : "/icon/no_img.png";
                                                                                    $img_webp = $item['image'] ? "/img/" . explode('.', $item['image'])[0].'.webp' : "/icon/no_img.webp";
                                                                                @endphp
                                                                                <tr>
                                                                                    <td class="text-center col-1 p-0">
                                                                                        <picture>
                                                                                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                                                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                                                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" width="100%">
                                                                                        </picture>
                                                                                    </td>
                                                                                    <td class="text-justify" style="font-size: 10pt">
                                                                                        <b>{!! ''.$item['item_code'] !!}</b><span class="d-none d-xl-inline"> - {{ strip_tags($item['item_description']) }}</span>
                                                                                    </td>
                                                                                    <td class="text-center" style="font-size: 10pt;">
                                                                                        <b id="{{ $inv['name'].'-'.$item['item_code'] }}-qty">{!! $item['opening_stock'] !!}<br></b>
                                                                                        @if ($inv['status'] == 'Approved')
                                                                                            <input id="{{ $inv['name'].'-'.$item['item_code'] }}-new-qty" type="text" class="form-control text-center d-none" name="item[{{ $item['item_code'] }}][qty]" value={{ $item['opening_stock'] }} style="font-size: 10pt;"/>
                                                                                        @endif
                                                                                        <small>{{ $item['uom'] }}</small>
                                                                                    </td>
                                                                                    <td class="text-center" style="font-size: 10pt; white-space: nowrap">
                                                                                        @if (Auth::user()->user_group == 'Consignment Supervisor' && $inv['status'] == 'For Approval')
                                                                                            ₱ <input type="text" name="price[{{ $item['item_code'] }}][]" value="{{ number_format($item['price'], 2) }}" style="text-align: center; width: 60px" required/>
                                                                                        @else
                                                                                            ₱ {{ number_format($item['price'], 2) }}
                                                                                        @endif
                                                                                    </td>
                                                                                    @if ($inv['status'] == 'Approved')
                                                                                        <td class="text-center">
                                                                                            <span class="btn btn-primary btn-xs"><i class="fa fa-edit edit-stock_qty" data-reference="{{ $inv['name'].'-'.$item['item_code'] }}" data-name="{{ $inv['name'] }}"></i></span>
                                                                                        </td>
                                                                                    @endif
                                                                                </tr>
                                                                                <tr class="d-xl-none">
                                                                                    <td colspan=5 class="text-justify p-2">
                                                                                        {{ strip_tags($item['item_description']) }}
                                                                                    </td>
                                                                                </tr>
                                                                            @empty
                                                                                <tr>
                                                                                    <td class="text-center" colspan=4>No Item(s)</td>
                                                                                </tr>
                                                                            @endforelse
                                                                        </table>
                                                                    </div>
                                                                    {{-- Update button for approved records --}}
                                                                    @if ($inv['status'] == 'Approved')
                                                                        <div class="modal-footer" id="{{ $inv['name'] }}-stock-adjust-update-btn" style="display: none">
                                                                            <button type="submit" class="btn btn-info w-100">Update</button>
                                                                        </div>
                                                                    @endif
                                                                </form>
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
        .last-row{
            width: 20% !important;
        }
        .filters-font{
            font-size: 13px !important;
        }
        @media (max-width: 575.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
        }
        @media (max-width: 767.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
        }
        @media only screen and (min-device-width : 768px) and (orientation : landscape) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            var from_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[0])->format("Y-M-d") : Carbon\Carbon::now()->subDays(7)->format("Y-M-d")  }}';
            var to_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[1])->format("Y-M-d") : Carbon\Carbon::now()->format("Y-M-d")  }}';
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

            $(document).on('click', '.edit-stock_qty', function(){
                var reference = $(this).data('reference');
                $('#'+reference+'-qty').addClass('d-none');
                $('#'+reference+'-new-qty').removeClass('d-none');
                $('#'+$(this).data('name')+'-stock-adjust-update-btn').slideDown();
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

            // always show filters on pc, allow collapse of filters on mobile
            if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
				$('#headingOne').removeClass('d-none');
                $('#collapseOne').removeClass('show');
			}else{ // desktop
                $('#headingOne').addClass('d-none');
                $('#collapseOne').addClass('show');
			}
        });
    </script>
@endsection