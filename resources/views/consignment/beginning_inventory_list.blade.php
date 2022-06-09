@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
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
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                        </div>
                        <div class="card-body p-1">
                            <div class="container-fluid">
                                <form action="/beginning_inv_list" method="get">
                                    <div class="row p-2">
                                        <div class="col-2 offset-3">
                                            <input type="text" class="form-control" name="search" value="{{ request('search') ? request('search') : null }}" placeholder="Search" />
                                        </div>
                                        <div class="col-2">
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
                                        <div class="col-2">
                                            <select name="store" class="form-control">
                                                <option value="" disabled {{ !request('store') ? 'selected' : null }}>Select a store</option>
                                                @foreach ($consignment_stores as $store)
                                                    <option value="{{ $store }}" {{ request('store') == $store ? 'selected' : null }}>{{ $store }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <input type="text" name="date" id="date-filter" class="form-control" value="" />
                                        </div>
                                        <div class="col-1">
                                            <button class="btn btn-primary w-100">Search</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <table class="table table-bordered" style="font-size: 10pt;">
                                <tr>
                                    <th class="font-responsive text-center">ID</th>
                                    <th class="font-responsive text-center">Branch</th>
                                    <th class="font-responsive text-center">Submitted by</th>
                                    <th class="font-responsive text-center">Submitted at</th>
                                    <th class="font-responsive text-center">Status</th>
                                    <th class="font-responsive text-center">Transaction Date</th>
                                    <th class="font-responsive text-center">Action</th>
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
                                        <td class="font-responsive text-center">{{ $inv['name'] }}</td>
                                        <td class="font-responsive text-center">{{ $inv['branch'] }}</td>
                                        <td class="font-responsive text-center">{{ $inv['owner'] }}</td>
                                        <td class="font-responsive text-center">{{ $inv['creation'] }}</td>
                                        <td class="font-responsive text-center">
                                            <span class="badge badge-{{ $status }}">{{ $inv['status'] }}</span>
                                        </td>
                                        <td class="font-responsive text-center">{{ $inv['transaction_date'] }}</td>
                                        <td class="font-responsive text-center">
                                            <a href="#" data-toggle="modal" data-target="#{{ $inv['name'] }}-Modal">
                                                View Items
                                            </a>
                                              
                                            <div class="modal fade" id="{{ $inv['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-xl" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <div class="container-fluid">
                                                                <div class="row">
                                                                    <div class="col-8 text-left font-responsive">
                                                                        <b>Branch:</b> {{ $inv['branch'] }}<br/>
                                                                        <b>Transaction Date:</b> {{ $inv['transaction_date'] }} <br>
                                                                        <b>Promidiser:</b> {{ $inv['owner'] }}
                                                                    </div>
                                                                    @if ($inv['status'] == 'For Approval')
                                                                    <div class="col-4 w-100">
                                                                        @php
                                                                            $status_selection = [
                                                                                ['title' => 'Approve', 'value' => 'Approved'],
                                                                                ['title' => 'Cancel', 'value' => 'Cancelled']
                                                                            ];
                                                                        @endphp
                                                                        <form action="/approve_beginning_inv/{{ $inv['name'] }}" method="post">
                                                                            @csrf
                                                                            <div class="input-group pt-2">
                                                                                <select class="custom-select font-responsive" name="status" id="inputGroupSelect04" required>
                                                                                    <option value="" selected disabled>Select a status</option>
                                                                                    @foreach ($status_selection as $status)
                                                                                        <option value="{{ $status['value'] }}">{{ $status['title'] }}</option>                                                                                
                                                                                    @endforeach
                                                                                </select>
                                                                                <div class="input-group-append">
                                                                                    <button class="btn btn-outline-secondary" type="submit">Submit</button>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                    @endif
                                                                    
                                                                </div>
                                                            </div>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
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
                                                                        <td class="text-left" style="font-size: 10pt">{!! '<b>'.$item['item_code'].'</b> - '.$item['item_description'] !!}</td>
                                                                        <td class="text-center" style="font-size: 10pt">{!! $item['opening_stock'].' '.$item['uom'] !!}</td>
                                                                        <td class="text-center" style="font-size: 10pt">₱ {{ number_format($item['price'], 2) }}</td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td class="text-center" colspan=4>No Item(s)</td>
                                                                    </tr>
                                                                @endforelse
                                                            </table>
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
                </div>
            </div>
        </div>
	</div>
</div>
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
        });
    </script>
@endsection