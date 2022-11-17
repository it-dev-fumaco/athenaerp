@extends('layout', [
    'namePage' => 'Stock Transfers Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-3">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-7 col-lg-6 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Transfers</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <div class="row p-2">
                                <div class="col-10">
                                    <ul class="nav nav-pills m-0" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active font-responsive" data-toggle="tab" href="#stock_transfers">Stock Transfer History</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link font-responsive" data-toggle="tab" href="#damaged_items">Damaged Item List</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-2 p-2">
                                    <a href="/stock_return_form" class="btn btn-sm btn-primary w-100"><i class="fas fa-plus"></i> Create Return</a>
                                </div>
                            </div>
                            
                            <div class="tab-content">
                                <!-- Stock Transfers -->
                                <div id="stock_transfers" class="tab-pane active">
                                    <!-- Stock Transfers -->
                                    <form action="/stocks_report/list" method="get">
                                        <div id="accordion" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 10pt;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="row p-2">
                                                    <div class="col-12 col-xl-2 col-lg-2">
                                                        <input type="text" name="tab1_q" class="form-control" placeholder='Search' style='font-size: 10pt;' value="{{ request('tab1_q') ? request('tab1_q') : null }}"/>
                                                    </div>
                                                    <div class="col-12 mt-2 mt-lg-0 col-xl-2 col-lg-2">
                                                        <select name="tab1_purpose" id='status' class="form-control" style="font-size: 10pt;">
                                                            @php
                                                                $purposes = ['Store Transfer', 'Consignment', 'For Return', 'Sales Return'];
                                                            @endphp
                                                            <option value="" {{ !request('tab1_purpose') ? 'selected' : null }}>Select Purpose</option>
                                                            @foreach ($purposes as $p)
                                                            <option value="{{ $p }}" {{ request('tab1_purpose') == $p ? 'selected' : null }}>{{ $p }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 mt-2 mt-lg-0 col-xl-2 col-lg-2">
                                                        <select name="source_warehouse" id="source-warehouse" class="form-control" style="font-size: 10pt;"></select>
                                                    </div>
                                                    <div class="col-12 mt-2 mt-lg-0 col-xl-2 col-lg-2">
                                                        <select name="target_warehouse" id="target-warehouse" class="form-control" style="font-size: 10pt;"></select>
                                                    </div>
                                                    <div class="col-12 mt-2 mt-lg-0 col-xl-2 col-lg-2">
                                                        <select name="tab1_status" id='status' class="form-control" style="font-size: 10pt;">
                                                            @php
                                                                $status = [
                                                                    ['title' => 'Select All', 'value' => 'All'],
                                                                    ['title' => 'For Approval', 'value' => 0],
                                                                    ['title' => 'Approved', 'value' => 1]
                                                                ];
                                                            @endphp 
                                                            <option value="" disabled {{ !request('tab1_status') ? 'selected' : null }}>Select a status</option>
                                                            @foreach ($status as $s)
                                                            @php
                                                                $selected = null;
                                                                if(request('tab1_status') == 'All'){
                                                                    $selected = $loop->first ? 'selected' : null;
                                                                }else{
                                                                    $selected = $s['value'] == request('tab1_status') ? 'selected' : null;
                                                                }
                                                            @endphp
                                                            <option value="{{ $s['value'] }}" {{ $selected }}>{{ $s['title'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-xl-2 col-lg-2 mt-2 mt-lg-0">
                                                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Search</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <table class="table table-striped" style="font-size: 9pt;">
                                        <thead>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" id='first-row'>Reference</th>
                                            <th class="text-center p-2 align-middle" id='second-row'>
                                                <span class="d-block d-xl-none">Details</span>
                                                <span class="d-none d-xl-block">Purpose</span>
                                            </th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%">From</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%">To</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%">Created by</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%">Status</th>
                                            <th class="text-center p-2 align-middle" style="width: 10%">Action</th>
                                        </thead>
                                        <tbody>
                                            @forelse ($ste_arr as $ste)
                                            @php
                                                if($ste['status'] != 'Cancelled'){
                                                    if($ste['status'] == 'Approved'){
                                                        $badge = 'success';
                                                    }else{
                                                        $badge = 'primary';
                                                    }
                                                }else{
                                                    $badge = 'secondary';
                                                }
                                                $purpose = $ste['transfer_as'] ? $ste['transfer_as'] : $ste['receive_as'];
                                                $badge = 'secondary';

                                                if($ste['docstatus'] < 2){
                                                    $badge = $ste['consignment_status'] == 'Received' ? 'success' : 'primary';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="text-center p-2 align-middle d-none d-xl-table-cell">
                                                    <b>{{ $ste['name'] }}</b> <br>
                                                    {{ $ste['creation'] }}
                                                </td>
                                                <td class="text-center p-2 align-middle">
                                                    <span class="d-block text-left text-lg-center text-xl-center font-weight-bold">
                                                        {{ $purpose }}
                                                        <span class="d-inline d-xl-none text-left"> - <b>{{ $ste['name'] }}</b></span>
                                                    </span>
                                                    <div class="d-block d-xl-none text-left">
                                                        <b>From: </b> {{ $ste['source_warehouse'] ? $ste['source_warehouse'] : '-' }} <br>
                                                        <b>To: </b> {{ $ste['target_warehouse'] }} <br>
                                                        <b>Purpose: </b> {{ $purpose }} <br>
                                                        {{ $ste['submitted_by'] }} - {{ $ste['creation'] }}
                                                    </div>
                                                </td>
                                                <td class="text-center p-2 align-middle d-none d-xl-table-cell">{{ $ste['source_warehouse'] ? $ste['source_warehouse'] : '-' }}</td>
                                                <td class="text-center p-2 align-middle d-none d-xl-table-cell">{{ $ste['target_warehouse'] }}</td>
                                                <td class="text-center p-2 align-middle d-none d-xl-table-cell">{{ $ste['submitted_by'] }}</td>
                                                <td class="text-center p-2 align-middle d-none d-xl-table-cell">
                                                    <span class="badge badge-{{ $badge }}">{{ $ste['status'] }}</span>
                                                </td>
                                                <td class="text-center p-2 align-middle">
                                                    <a href="#" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal" style="font-size: 10pt;" class="d-block">View Items</a>
                                                    <span class="badge badge-{{ $badge }} d-xl-none">{{ $ste['status'] }}</span>
                                                    <!-- Modal -->
                                                    <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-navy">
                                                                    <h6 class="modal-title">{{ $purpose .' - '. $ste['name'] }} <span class="badge badge-{{ $badge }} d-inline-block ml-2">{{ $ste['status'] }}</span></h6>
                                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    @if ($purpose != 'Sales Return')
                                                                        <div class="callout callout-info text-center mt-2">
                                                                            <small><i class="fas fa-info-circle"></i> Consignment Supervisors can approve stock transfers in ERP.</small>
                                                                        </div>
                                                                    @endif
                                                                    <div class="row pb-0 mb-3">
                                                                        <div class="pt-0 pr-2 pl-2 pb-0 col-6 text-left m-0">
                                                                            <dl class="row p-0 m-0">
                                                                                <dt class="col-12 col-xl-3 col-lg-2 p-1 m-0">Source:</dt>
                                                                                <dd class="col-12 col-xl-9 col-lg-10 p-1 m-0">{{ $ste['source_warehouse'] ? $ste['source_warehouse'] : '-' }}</dd>
                                                                                <dt class="col-12 col-xl-3 col-lg-2 p-1 m-0">Target:</dt>
                                                                                <dd class="col-12 col-xl-9 col-lg-10 p-1 m-0">
                                                                                    @if ($purpose == 'For Return' && $ste['consignment_status'] != 'Received')
                                                                                        <select class="form-control form-control-sm target-warehouse-selection" data-reference="{{ $ste['name'] }}">
                                                                                            @foreach ($warehouses as $warehouse)
                                                                                                <option value="{{ $warehouse }}" {{ $warehouse == $ste['target_warehouse'] ? 'selected' : null }}>{{ $warehouse }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    @else
                                                                                        {{ $ste['target_warehouse'] }}
                                                                                    @endif
                                                                                </dd>
                                                                            </dl>
                                                                        </div>
                                                                        <div class="pt-0 pr-2 pl-2 pb-0 col-6 text-left m-0">
                                                                            <dl class="row p-0 m-0">
                                                                                <dt class="col-12 col-xl-4 col-lg-6 p-1 m-0">Transaction Date:</dt>
                                                                                <dd class="col-12 col-xl-8 col-lg-6 p-1 m-0">{{ $ste['creation'] }}</dd>
                                                                                <dt class="col-12 col-xl-4 col-lg-6 p-1 m-0">Submitted by:</dt>
                                                                                <dd class="col-12 col-xl-8 col-lg-6 p-1 m-0">{{ $ste['submitted_by'] }}</dd>
                                                                            </dl>   
                                                                        </div>
                                                                    </div>
                                                                    <form action="/promodiser/receive/{{ $ste['name'] }}" method="get">
                                                                        <input type="text" class="d-none" name="target_warehouse" id="{{ $ste['name'] }}-target-warehouse" value="{{ $ste['target_warehouse'] }}">
                                                                        <table class="table table-striped" style="font-size: 10pt;">
                                                                            <thead>
                                                                                <th class="text-center p-2 align-middle" width="50%">Item Code</th>
                                                                                <th class="text-center p-2 align-middle">Stock Qty</th>
                                                                                <th class="text-center p-2 align-middle">Qty to Transfer</th>
                                                                            </thead>
                                                                            @foreach ($ste['items'] as $item)
                                                                                <tr>
                                                                                    <td class="text-center p-1 align-middle">
                                                                                        <div class="d-none">
                                                                                            <input type="checkbox" name="receive_delivery" checked>
                                                                                            <input type="text" name="item_codes[]" value="{{ $item['item_code'] }}">
                                                                                            <input type="text" name="price[{{ $item['item_code'] }}][]" value="{{ $item['price'] }}">
                                                                                        </div>
                                                                                        <div class="d-flex flex-row justify-content-start align-items-center">
                                                                                            <div class="p-2 text-left">
                                                                                                <a href="{{ asset('storage/') }}{{ $item['image'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $item['item_code'] }}" data-title="{{ $item['item_code'] }}">
                                                                                                    <picture>
                                                                                                        <source srcset="{{ asset('storage'.$item['webp']) }}" type="image/webp">
                                                                                                        <source srcset="{{ asset('storage'.$item['image']) }}" type="image/jpeg">
                                                                                                        <img src="{{ asset('storage'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" width="60" height="60">
                                                                                                    </picture>
                                                                                                </a>
                                                                                            </div>
                                                                                            <div class="p-2 text-left">
                                                                                                <b>{!! ''.$item['item_code'] !!}</b>
                                                                                                <span class="d-none d-xl-inline"> - {!! strip_tags($item['description']) !!}</span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal fade" id="mobile-{{ $item['item_code'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                                                                <div class="modal-content">
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title">{{ $item['item_code'] }}</h5>
                                                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                                            <span aria-hidden="true">&times;</span>
                                                                                                        </button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        <form></form>
                                                                                                        <div class="container-fluid">
                                                                                                            <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                                                                                <div class="carousel-inner">
                                                                                                                    <div class="carousel-item active">
                                                                                                                        <picture>
                                                                                                                            <source id="mobile-{{ $item['item_code'] }}-webp-image-src" srcset="{{ asset('storage/').$item['webp'] }}" type="image/webp">
                                                                                                                            <source id="mobile-{{ $item['item_code'] }}-orig-image-src" srcset="{{ asset('storage/').$item['image'] }}" type="image/jpeg">
                                                                                                                            <img class="d-block w-100" id="mobile-{{ $item['item_code'] }}-image" src="{{ asset('storage/').$item['image'] }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $item['image'])[0], '-') }}">
                                                                                                                        </picture>
                                                                                                                    </div>
                                                                                                                    <span class='d-none' id="mobile-{{ $item['item_code'] }}-image-data">0</span>
                                                                                                                </div>
                                                                                                                <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $item['item_code'] }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                                                                    <span class="sr-only">Previous</span>
                                                                                                                </a>
                                                                                                                <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $item['item_code'] }}')" role="button" data-slide="next" style="color: #000 !important">
                                                                                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                                                                    <span class="sr-only">Next</span>
                                                                                                                </a>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td class="text-center p-1 align-middle">
                                                                                        <b>{{ $item['consigned_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                                    </td>
                                                                                    <td class="text-center p-1 align-middle">
                                                                                        <b>{{ $item['transfer_qty'] * 1 }}</b><br/><small>{{ $item['uom'] }}</small>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr class="d-xl-none">
                                                                                    <td colspan="3" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
                                                                                        <div class="w-100 item-description">{!! strip_tags($item['description']) !!}</div>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </table>
                                                                        @if ($ste['transfer_as'] == 'For Return' && $ste['docstatus'] == 0)
                                                                        <div class="col-12 col-xl-4 mx-auto">
                                                                            <button type="submit" class="btn btn-primary w-100">Receive</button>
                                                                        </div>
                                                                        @endif
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="text-center font-responsive text-uppercase text-muted p-2">No record(s) found</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div class="float-left m-2">Total: <b>{{ $stock_entry->total() }}</b></div>
                                    <div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $stock_entry->links('pagination::bootstrap-4') }}</div>
                                </div>
                                <!-- Stock Transfers -->

                                <!-- Damaged Items -->
                                <div id="damaged_items" class="tab-pane">
                                    <form action="/stocks_report/list" method="GET">
                                        <div id="accordion2" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" style="font-size: 10pt;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                        </div>
                                        
                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingOne" data-parent="#accordion2">
                                            <div class="row p-2">
                                                <div class="col-12 col-xl-4 col-lg-4 mt-2 mt-lg-0">
                                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search Item" style='font-size: 10pt'/>
                                                </div>
                                                <div class="col-12 col-xl-4 col-lg-4 mt-2 mt-lg-0">
                                                    @php
                                                        $statuses = ['For Approval', 'Approved', 'Cancelled'];
                                                    @endphp
                                                    <select class="form-control" name="store" id="consignment-store-select">
                                                        <option value="">Select Store</option>
                                                        @foreach ($statuses as $status)
                                                        <option value="{{ $status }}">{{ $status }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12 col-xl-2 col-lg-2 mt-2 mt-lg-0">
                                                    <button class="btn btn-primary btn-block"><i class="fas fa-search"></i> Search</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <table class="table table-striped" style="font-size: 9pt;">
                                        <thead>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%;">Date</th>
                                            <th class="text-center p-2 align-middle" style="width: 35%;">Item Description</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%;">Qty</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%;">Store</th>
                                            <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%;">Damage Description</th>
                                        </thead>
                                        @forelse ($items_arr as $i => $item)
                                            <tr>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                                                    {{ $item['creation'] }}<br/>
                                                    <span class="badge badge-success {{ !$item['item_status'] ? 'd-none' : null }}">{{ $item['item_status'] }}</span>
                                                </td>
                                                <td class="p-1 text-justify align-middle">
                                                    <div class="d-flex flex-row align-items-center">
                                                        <div class="p-1">
                                                            <picture>
                                                                <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                                <source srcset="{{ asset('storage'.$item['image']) }}" type="image/jpeg">
                                                                <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" width="70">
                                                            </picture>
                                                        </div>
                                                        <div class="p-1">
                                                            <span class="d-block font-weight-bold">{{ $item['item_code'] }}</span>
                                                            <small class="d-block item-description">{!! strip_tags($item['description']) !!}</small>
        
                                                            <small class="d-block mt-2">Created by: <b>{{ $item['promodiser'] }}</b></small>
                                                        </div>
                                                    </div>
                                                    <div class="d-block d-xl-none" style="font-size: 9pt;">
                                                        <b>Damaged Qty: </b>{{ $item['damaged_qty'] }}&nbsp;<small>{{ $item['uom'] }}</small> <br>
                                                        <b>Store: </b> {{ $item['store'] }} <br>
                                                        <b>Damage Description: </b> {{ $item['damage_description'] }} <br>
                                                        <b>Date: </b> {{ $item['creation'] }}
                                                    </div>
                                                </td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                                                    <span class="d-block font-weight-bold">{{ $item['damaged_qty'] }}</span>
                                                    <small>{{ $item['uom'] }}</small>
                                                </td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['store'] }}</td>
                                                <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['damage_description'] }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No record(s) found.</td>
                                            </tr>
                                        @endforelse
                                    </table>
                                    <div class="float-left m-2">Total: <b>{{ $damaged_items->total() }}</b></div>
                                    <div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $damaged_items->links('pagination::bootstrap-4') }}</div>
                                
                                </div>
                                <!-- Damaged Items -->
                            </div>
                        </div>
                        <!-- Nav tabs -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if (session()->has('success'))
    @php
        $received = session()->get('success');
    @endphp
    <div class="modal fade" id="receivedDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h5 class="modal-title" id="exampleModalLabel">Item(s) Received</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: #fff">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 10pt;">
                <div class="row">
                    <div class="col-2">
                    <center>
                        <p class="text-success text-center mb-0" style="font-size: 4rem;">
                        <i class="fas fa-check-circle"></i>
                        </p>
                    </center>
                    </div>
                    <div class="col-10">
                    <span>{{ $received['message'] }}</span> <br>
                    <span>Branch: <b>{{ $received['branch'] }}</b></span> <br>
                    <span>Total Amount: <b>₱ {{ number_format(collect($received)->sum('amount'), 2) }}</b></span>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function(){
            $('#receivedDeliveryModal').modal('show');
        });
    </script>
@endif
<style>
    .morectnt span {
        display: none;
    }
    .modal{
        background-color: rgba(0,0,0,0.4);
    }
    table {
        table-layout: fixed;
        width: 100%;   
    }
    #first-row, #second-row{
        width: 10%;
    }
    @media (max-width: 575.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
  	@media (max-width: 767.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
	@media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
	}
</style>
@endsection

@section('script')
<script>
    var showTotalChar = 110, showChar = "Show more", hideChar = "Show less";
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

    $('#consignment-store-select').select2({
        placeholder: "Select Store",
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });

    function show_modal(modal){
        $(modal).modal('show');
    }

    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
        $('#collapseOne').removeClass('show');
        $('#collapseTwo').removeClass('show');
    }else{ // desktop
        $('#collapseOne').addClass('show');
        $('#collapseTwo').addClass('show');
    }

    $('#source-warehouse').select2({
        placeholder: 'Source Warehouse',
        allowClear: true,
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });

    $('#target-warehouse').select2({
        placeholder: 'Target Warehouse',
        allowClear: true,
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });

    $('.target-warehouse-selection').select2();

    $(document).on('select2:select', '.target-warehouse-selection', function(e){
        var reference = $(this).data('reference');
        $('#' + reference + '-target-warehouse').val($(this).val());
    });
</script>
@endsection