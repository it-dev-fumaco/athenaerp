@extends('layout', [
    'namePage' => 'Stock Adjustments',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Adjustments</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2">
                            @if(session()->has('success'))
                            <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{{ session()->get('success') }}</div>
                            @endif
                            @if(session()->has('error'))
                            <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{{ session()->get('error') }}</div>
                            @endif
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active font-responsive" id="beginning-inventory-tab" data-toggle="pill" href="#beginning-inventory-content" role="tab" href="#">Beginning Inventory</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link font-responsive" id="stock-adjustment-tab" data-toggle="pill" href="#stock-adjustment-content" role="tab" href="#">Stock Adjustments</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link font-responsive" id="inventory-audit-history-tab" data-toggle="pill" href="#inventory-audit-history-content" role="tab" href="#">Activity Logs</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="beginning-inventory-content" role="tabpanel" aria-labelledby="pending-tab">
                                    <form action="/beginning_inv_list" method="get">
                                        <div id="accordion" class="mt-2">
                                            <button type="button" class="btn btn-link border-bottom btn-block text-left d-xl-none d-lg-none" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 10pt;">
                                                <i class="fa fa-filter"></i> Filters
                                            </button>
                                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                                <div class="row p-2">
                                                    <div class="col-12 col-lg-3 col-xl-3">
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
                                                                    if(in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director'])){
                                                                        $selected = $status == 'For Approval' ? 'selected' : null;
                                                                    }
                                                                }
                                                            @endphp
                                                            <option value="{{ $status }}" {{ $selected }}>{{ $status }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                        <select name="store" class="form-control filters-font consignment-store-select">
                                                            <option value="" disabled {{ !request('store') ? 'selected' : null }}>Select a store</option>
                                                            @foreach ($consignmentStores as $store)
                                                            <option value="{{ $store }}" {{ request('store') == $store ? 'selected' : null }}>{{ $store }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-lg-3 col-xl-3 mt-2 mt-lg-0">
                                                        <input type="text" name="date" id="date-filter" class="form-control filters-font" value="" />
                                                    </div>
                                                    <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
                                                        <button type="submit" class="btn btn-primary filters-font btn-block"><i class="fas fa-search"></i> Search</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    <table class="table table-striped" style="font-size: 9pt;">
                                        <thead>
                                            <th class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">Date</th>
                                            <th class="font-responsive align-middle p-2 text-center">Store</th>
                                            <th class="font-responsive align-middle p-2 text-center">Total Qty</th>
                                            <th class="font-responsive align-middle p-2 text-center">Total Value</th>
                                            <th class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">Submitted by</th>
                                            <th class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">Status</th>
                                            <th class="font-responsive align-middle p-2 text-center last-ro1w">Action</th>
                                        </thead>
                                        @forelse ($invArr as $inv)
                                            @php
                                                switch ($inv->status) {
                                                    case 'Approved':
                                                        $badge = 'success';
                                                        break;
                                                    case 'Cancelled':
                                                        $badge = 'secondary';
                                                        break;
                                                    default:
                                                        $badge = 'primary';
                                                        break;
                                                }

                                                $modalForm = in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv->status == 'For Approval' ? '/approve_beginning_inv/'.$inv->name : '/stock_adjust/submit/'.$inv->name;
                                            @endphp
                                            <tr>
                                                <td class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">
                                                    <span style="white-space: nowrap">{{ $inv->transaction_date }}</span>
                                                </td>
                                                <td class="font-responsive align-middle p-2 text-left text-xl-center">
                                                    <span class="d-block">{{ $inv->branch_warehouse }}</span>
                                                    <small class="d-block text-left d-lg-none">
                                                        <span class="d-block"><b>By:</b>&nbsp;{{ $inv->owner }}</span>
                                                        <span class="d-block"><b>Date:</b>&nbsp;{{ $inv->transaction_date }}</span>
                                                    </small>
                                                </td>
                                                <td class="font-responsive align-middle p-2 text-center">{{ number_format($inv->qty) }}</td>
                                                <td class="font-responsive align-middle p-2 text-center">{{ '₱ ' . number_format($inv->amount, 2) }}</td>
                                                <td class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">{{ $inv->owner }}</td>
                                                <td class="font-responsive align-middle p-2 text-center d-none d-lg-table-cell">
                                                    <span class="badge badge-{{ $badge }}">{{ $inv->status }}</span>
                                                </td>
                                                <td class="font-responsive align-middle p-2 text-center">
                                                    <a href="#" class="d-block modal-trigger" data-branch='{{ $inv["branch"] }}' data-toggle="modal" data-target="#{{ $inv->name }}-Modal">View Items</a>
                                                    <span class="badge badge-{{ $badge }} d-xl-none d-lg-none">{{ $inv->status }}</span>
                                                        
                                                    <div class="modal fade" id="{{ $inv->name }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl" role="document">
                                                            <div class="modal-content">
                                                                <form action="{{ $modalForm }}" method="post">
                                                                    @csrf
                                                                    <div class="modal-header bg-navy">
                                                                        <h6 class="modal-title">{{ $inv->branch_warehouse }} <span class="badge badge-{{ $badge }} d-inline-block ml-2">{{ $inv->status }}</span></h6>
                                                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row">
                                                                            <div class="pt-0 pr-2 pl-2 pb-2 col-6 col-lg-4 col-xl-4 text-left">
                                                                                <dl class="row">
                                                                                    <dt class="col-12 col-xl-4 col-lg-6">Inventory Date:</dt>
                                                                                    <dd class="col-12 col-xl-8 col-lg-6">{{ $inv->transaction_date }}</dd>
                                                                                  
                                                                                    <dt class="col-12 col-xl-4 col-lg-6">Submitted By:</dt>
                                                                                    <dd class="col-12 col-xl-8 col-lg-6">{{ $inv->owner }}</dd>
                                                                                </dl>
                                                                            </div>
                                                                            <div class="pt-0 pr-2 pl-2 pb-2 col-6 col-lg-4 col-xl-4 text-left">
                                                                                <dl class="row">
                                                                                    <dt class="col-12 col-xl-4 col-lg-6">Total Qty:</dt>
                                                                                    <dd class="col-12 col-xl-8 col-lg-6">{{ number_format($inv->qty) }}</dd>
                                                                                  
                                                                                    <dt class="col-12 col-xl-4 col-lg-6">Total Value:</dt>
                                                                                    <dd class="col-12 col-xl-8 col-lg-6">{{ '₱ ' . number_format($inv->amount, 2) }}</dd>
                                                                                </dl>
                                                                            </div>
                                                                            <div class="pt-0 pr-2 pl-2 pb-2 col-12 col-lg-4 col-xl-4 text-left">
                                                                                @if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv->status == 'For Approval')
                                                                                @php
                                                                                    $statusSelection = [
                                                                                        ['title' => 'Approve', 'value' => 'Approved'],
                                                                                        ['title' => 'Cancel', 'value' => 'Cancelled']
                                                                                    ];
                                                                                @endphp
                                                                                        
                                                                                <div class="input-group">
                                                                                    <select class="custom-select font-responsive" name="status">
                                                                                        <option value="" selected disabled>Select a status</option>
                                                                                        @foreach ($statusSelection as $status)
                                                                                        <option value="{{ $status['value'] }}">{{ $status['title'] }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                    <div class="input-group-append">
                                                                                        <button class="btn btn-primary" type="submit" id="{{ $inv->name }}-submit">Submit</button>
                                                                                    </div>
                                                                                </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        @if ($inv->status == 'For Approval')
                                                                            <div class="callout callout-info font-responsive text-center pr-2 pl-2 pb-3 pt-3" style="font-size: 10pt;">
                                                                                <span class="d-block"><i class="fas fa-info-circle"></i> "For your Approval - you can modify/change item code and prices" - Once Approved stocks will be automatically added to this consignment Store</span>
                                                                            </div>
                                                                            <!-- Add item modal -->
                                                                            <button type='button' class="btn btn-primary float-right mb-2" style='font-size: 10pt;' data-toggle="modal" data-target="#addItems{{ $inv->name }}Modal"><i class="fa fa-plus"></i> Add Items</button>
                                                                            
                                                                            <div class="modal fade" id="addItems{{ $inv->name }}Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                <div class="modal-dialog" role="document">
                                                                                    <div class="modal-content">
                                                                                        <div class="modal-header bg-navy">
                                                                                            <h5 class="modal-title" id="exampleModalLabel">Add Items</h5>
                                                                                            <button type="button" class="close" onclick="close_modal('#addItems{{ $inv->name }}Modal')">
                                                                                                <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                            </button>
                                                                                        </div>
                                                                                        <div class="modal-body add-item-modal">
                                                                                            <select class="replacement-item form-control" data-name="{{ $inv->name }}"></select>
                                                                                            <div id="{{ $inv->name }}-new-item-container" class="pt-2 d-none">
                                                                                                <table class="table table-striped new-item-table">
                                                                                                    <thead>
                                                                                                        <th class="font-responsive text-center p-1 align-middle" style="width: 35%">Item Code</th>
                                                                                                        <th class="font-responsive text-center p-1 align-middle" style="width: 35%">Opening Stock</th>
                                                                                                        <th class="font-responsive text-center p-1 align-middle" style="width: 30%">Price</th>
                                                                                                    </thead>
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td class="text-justify p-1 align-middle" colspan="3">
                                                                                                                <div class="d-flex flex-row justify-content-center align-items-center">
                                                                                                                    <div class="p-1 col-2 text-center">
                                                                                                                        <div class="d-none">
                                                                                                                            <span class="text-placeholder" id="{{ $inv->name }}-webp-display"></span> <br>
                                                                                                                            <span class="text-placeholder" id="{{ $inv->name }}-img-display"></span> <br>
                                                                                                                            <span class="text-placeholder" id="{{ $inv->name }}-alt-display"></span> <br>
                                                                                                                            <span class="text-placeholder" id="{{ $inv->name }}-uom-display"></span>
                                                                                                                        </div>
                                                                                                                        <img src="" class="src-placeholder" alt="" id="{{ $inv->name }}-new-img" class="img-thumbna1il" alt="User Image" width="40" height="40">

                                                                                                                        <picture>
                                                                                                                            <source srcset="" class="src-placeholder" id="{{ $inv->name }}-new-src-img-webp" type="image/webp">
                                                                                                                            <source srcset="" class="src-placeholder" id="{{ $inv->name }}-new-src-img" type="image/jpeg">
                                                                                                                            <img src="" class="src-placeholder" alt="" id="{{ $inv->name }}-new-img" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                                                                                        </picture>
                                                                                                                    </div>
                                                                                                                    <div class="p-1 col m-0">
                                                                                                                        <span class="font-weight-bold font-responsive"><span class="text-placeholder" id="{{ $inv->name }}-item-code-display"></span></span>
                                                                                                                    </div>
                                                                                                                    <div class="p-0 col-4">
                                                                                                                        <div class="input-group p-1">
                                                                                                                            <div class="input-group-prepend p-0">
                                                                                                                                <button class="btn btn-outline-danger btn-xs new-item-qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                                                                                            </div>
                                                                                                                            <div class="custom-a p-0">
                                                                                                                                <input type="text" class="form-control form-control-sm qty new-item-validate new-item-stock value-placeholder" id="{{ $inv->name }}-new-item-stock" value="0" style="text-align: center; width: 47px">
                                                                                                                            </div>
                                                                                                                            <div class="input-group-append p-0">
                                                                                                                                <button class="btn btn-outline-success btn-xs new-item-qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="col-3 text-center p-1 align-middle">
                                                                                                                        <input type="text" id="{{ $inv->name }}-new-item-price" value="" class="form-control value-placeholder text-center"/>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="p-1 text-placeholder" id="{{ $inv->name }}-description-display" style="font-size: 9.5pt !important;"></div>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type='button' data-id='{{ $inv->name }}' id="{{ $inv->name }}-add-item" class="add-item btn btn-primary w-100" disabled>Add item</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <!-- Add item modal -->
                                                                        @endif
                                                                        
                                                                        <span id="item-count-{{ $inv->name }}" class="d-none">{{ count($inv->items) }}</span>
                                                                        <table class="table table-striped items-table" id="{{ $inv->name }}-items-table" style="font-size: 10pt;">
                                                                            <thead>
                                                                                <th class="text-center p-2 align-middle col-lg-4 col-3" style="width: 2%"></th>
                                                                                <th class="text-center p-2 align-middle col-lg-4 col-3" style="width: 36%">Item Code</th>
                                                                                <th class="text-center p-2 align-middle col-lg-2 col-3" style='width: 16%'>Opening Stock</th>
                                                                                <th class="text-center p-2 align-middle col-lg-2 col-3" style='width: 16%'>Price</th>
                                                                                @if ($inv->status == 'Approved')
                                                                                    <th class="text-center p-2 align-middle col-lg-2 col-3"style='width: 15%'>-</th>
                                                                                @else
                                                                                    <th class="text-center p-2 align-middle col-lg-2 col-3"style='width: 15%'>Action</th>
                                                                                @endif
                                                                            </thead>
                                                                            <tbody>
                                                                                @forelse ($inv->items as $i => $item)
                                                                                    @php
                                                                                        $target = $inv->name.'-'.$item->item_code;
                                                                                        $img = asset("storage/$item->image");
                                                                                    @endphp
                                                                                    <tr id="row-{{ $target }}" class="{{ $item->item_code }}">
                                                                                        <td class="text-center p-1 align-middle">
                                                                                            {{ $item->idx.$i }}
                                                                                        </td>
                                                                                        <td class="text-center p-1 align-middle">
                                                                                            <div class="d-flex flex-row justify-content-start align-items-center" id="{{ $target }}-container">
                                                                                                <div class="p-2 text-left">
                                                                                                    <a href="{{ $img }}" class="view-images" data-item-code="{{ $item->item_code }}">
                                                                                                        <img src="{{ $img }}" alt="{{ Illuminate\Support\Str::slug($item->item_description, '-') }}" width="60" height="60">
                                                                                                    </a>
                                                                                                </div>
                                                                                                <div class="p-2 text-left">
                                                                                                    <b>{!! ''.$item->item_code !!}</b>
                                                                                                    <span class="d-none d-xl-inline"> - {!! $item->item_description !!}</span>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="d-none flex-row justify-content-start align-items-center" id="{{ $target }}-replacement-container">
                                                                                                <div class="p-2 text-left">
                                                                                                    <a href="" data-toggle="mobile-lightbox" data-gallery="" data-title="">
                                                                                                        <picture>
                                                                                                            <source id="{{ $target }}-webp-replacement" srcset="" type="image/webp">
                                                                                                            <source id="{{ $target }}-img-src-replacement" srcset="" type="image/jpeg">
                                                                                                            <img src="" id="{{ $target }}-img-replacement" alt="" width="60" height="60">
                                                                                                        </picture>
                                                                                                    </a>
                                                                                                </div>
                                                                                                <div class="p-2 text-left">
                                                                                                    <b><span id="{{ $target }}-item-code-replacement"></span></b>
                                                                                                    <span class="d-none d-xl-inline"> - <span id="{{ $target }}-description-replacement"></span></span>
                                                                                                </div>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td class="text-center p-1 align-middle text-nowrap">
                                                                                            <b id="{{ $inv->name.'-'.$item->item_code }}-qty">{!! $item->opening_stock !!}<br></b>
                                                                                            @if ($inv->status == 'Approved')
                                                                                                <input id="{{ $inv->name.'-'.$item->item_code }}-new-qty" type="text" class="form-control text-center d-none" name="item[{{ $item->item_code }}][qty]" value={{ $item->opening_stock }} style="font-size: 10pt;"/>
                                                                                            @endif
                                                                                            <small id="{{ $target }}-uom">{{ $item->uom }}</small>
                                                                                        </td>
                                                                                        <td class="text-center p-1 align-middle">
                                                                                            @if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv->status == 'For Approval')
                                                                                                ₱ <input type="text" name="price[{{ $item->item_code }}]" id="item-price-{{ $target }}" value="{{ number_format($item->price, 2) }}" style="text-align: center; width: 60px" required/>
                                                                                                <input id="item-qty-{{ $target }}" type="text" class="d-none" name="qty[{{ $item->item_code }}]" value={{ $item->opening_stock }} style="font-size: 10pt;"/>
                                                                                            @elseif ($inv->status == 'Approved')
                                                                                                <input id="{{ $inv->name.'-'.$item->item_code }}-new-price" type="text" class="form-control text-center d-none" name="item[{{ $item->item_code }}][price]" value={{ $item->price }} style="font-size: 10pt;"/>
                                                                                                <span id="{{ $inv->name.'-'.$item->item_code }}-price">₱ {{ number_format($item->price, 2) }}</span>
                                                                                            @else
                                                                                                ₱ {{ number_format($item->price, 2) }}
                                                                                            @endif
                                                                                        </td>
                                                                                        @if ($inv->status == 'Approved')
                                                                                            <td class="text-center p-1 align-middle">
                                                                                                <span class="btn btn-primary btn-xs edit-stock_qty" data-reference="{{ $inv->name.'-'.$item->item_code }}" data-name="{{ $inv->name }}"><i class="fa fa-edit"></i></span>
                                                                                            </td>
                                                                                        @else
                                                                                            <td class="text-center p-1 align-middle">
                                                                                                <div class="btn-group" role="group" aria-label="Basic example">
                                                                                                    <!-- Change Button -->
                                                                                                    <button type="button" class="btn btn-xs btn-outline-primary p-1" id="{{ $target }}-replacement-button" style="font-size: 9pt;" data-toggle="modal" data-target="#{{ $target }}-replacement-Modal" data-original-code="{{ $item->item_code }}">Change</button>
                                                                                                    <!-- Change Button -->

                                                                                                    <!-- Undo Button -->
                                                                                                    <button type="button" class="btn btn-xs btn-outline-primary p-1 undo-change d-none" id="{{ $target }}-undo-button" style="font-size: 9pt;" data-target="{{ $target }}" data-original-code="{{ $item->item_code }}" data-orignal-uom="{{ $item->uom }}" data-replacement=''><i class="fa fa-undo"></i> Reset</button>
                                                                                                    <!-- Undo Button -->

                                                                                                    <!-- Remove Button -->
                                                                                                    <button type="button" class="btn btn-xs btn-outline-secondary p-1" style="font-size: 9pt;" data-toggle="modal" data-target="#{{ $target }}-remove-confirmation-Modal">Remove</button>
                                                                                                    <!-- Remove Button -->

                                                                                                    <!-- Replace Item Modal -->
                                                                                                    <div class="modal fade" id="{{ $target }}-replacement-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                                        <div class="modal-dialog" role="document">
                                                                                                            <div class="modal-content">
                                                                                                                <div class="modal-header bg-navy">
                                                                                                                    <h5 class="modal-title" id="exampleModalLabel">Change Code for {{ $item->item_code }}</h5>
                                                                                                                    <button type="button" class="close" onclick="close_modal('#{{ $target }}-replacement-Modal')">
                                                                                                                        <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                                <div class="modal-body replace-item-modal">
                                                                                                                    <select class="form-control replacement-item" id="{{ $target }}-replacement" data-original-code='{{ $item->item_code }}' data-id='{{ $target }}' style="width: 200px !important;"></select>
                                                                                                                    <br>
                                                                                                                    <div class="row d-none" id="{{ $target }}-replacement-info">
                                                                                                                        <div class="p-2 col-2 vertically-align-element">
                                                                                                                            <picture>
                                                                                                                                <source class="src-placeholder" id="{{ $target }}-replacement-webp" srcset="" type="image/webp">
                                                                                                                                <source class="src-placeholder" id="{{ $target }}-replacement-img-src" srcset="" type="image/jpeg">
                                                                                                                                <img class="d-block w-100 src-placeholder" id="{{ $target }}-replacement-img" src="{{ asset('storage/').$img }}" alt="">
                                                                                                                            </picture>
                                                                                                                        </div>
                                                                                                                        <div class="p-2 col-10 vertically-align-element">
                                                                                                                            <div class="p-2 text-left">
                                                                                                                                <b><span id="{{ $target }}-replacement-item-code"></span></b>
                                                                                                                                <span class="d-none d-xl-inline"> - <span id="{{ $target }}-replacement-description"></span></span>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="row d-none">
                                                                                                                        <span class="text-placeholder" id="{{ $target }}-replacement-display-webp"></span> <br>
                                                                                                                        <span class="text-placeholder" id="{{ $target }}-replacement-display-image"></span> <br>
                                                                                                                        <span class="text-placeholder" id="{{ $target }}-replacement-display-alt"></span> <br>
                                                                                                                        <span class="text-placeholder" id="{{ $target }}-replacement-display-uom"></span>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                <button type="button" class="btn btn-primary w-100 {{ $target }} change-item" data-name="{{ $inv->name }}" data-target="{{ $target }}" data-original-code="{{ $item->item_code }}" disabled>Change Item</button>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <!-- Replace Item Modal -->

                                                                                                    <!-- Remove Confirmation Modal -->
                                                                                                    <div class="modal fade" id="{{ $target }}-remove-confirmation-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                                        <div class="modal-dialog" role="document">
                                                                                                            <div class="modal-content">
                                                                                                                <div class="modal-header bg-navy">
                                                                                                                    <h5 class="modal-title" id="exampleModalLabel">Remove Item</h5>
                                                                                                                    <button type="button" class="close" onclick="close_modal('#{{ $target }}-remove-confirmation-Modal')">
                                                                                                                        <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    Remove {{ $item->item_code }}?
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                <button type="button" class="btn btn-primary w-100 remove-item" data-name="{{ $inv->name }}" data-target="{{ $target }}">Confirm</button>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <!-- Remove Confirmation Modal -->
                                                                                                </div>
                                                                                            </td>
                                                                                        @endif
                                                                                    </tr>
                                                                                    <tr class="d-xl-none">
                                                                                        <td colspan="4" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
                                                                                            <div class="w-100 item-description">{!! $item->item_description !!}</div>
                                                                                        </td>
                                                                                    </tr>
                                                                                @empty
                                                                                <tr>
                                                                                    <td class="text-center text-uppercase text-muted" colspan="4">No Item(s)</td>
                                                                                </tr>
                                                                                @endforelse
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                    <div class="container text-left p-2">
                                                                        <label style='font-size: 10pt;'>Remarks</label>
                                                                        <textarea name="remarks" id="remarks" cols="30" rows="5" class="form-control" placeholder='Remarks' data-name="{{ $inv->name }}" style="font-size: 10pt;">{{ $inv->remarks }}</textarea>
                                                                    </div>
                                                                    {{-- Update button for approved records --}}
                                                                    @if ($inv->status == 'Approved')
                                                                    <div class="modal-footer">
                                                                        <div class="container-fluid" id="{{ $inv->name }}-stock-adjust-update-btn" style="display: none">
                                                                            <button type="submit" class="btn btn-info w-100">Update</button>
                                                                        </div>
                                                                        <div class="container-fluid">
                                                                            <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $inv->name }}-Modal">
                                                                                Cancel
                                                                            </button>
                                                                            
                                                                            <!-- Modal -->
                                                                            <div class="modal fade" id="cancel-{{ $inv->name }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                <div class="modal-dialog" role="document">
                                                                                    <div class="modal-content">
                                                                                        <div class="modal-header bg-navy">
                                                                                            <h6 id="exampleModalLabel">Cancel Beginning Inventory?</h6>
                                                                                            <button type="button" class="close">
                                                                                            <span aria-hidden="true" style="color: #fff" onclick="close_modal('#cancel-{{ $inv->name }}-Modal')">&times;</span>
                                                                                            </button>
                                                                                        </div>
                                                                                        <div class="modal-body">
                                                                                            <div class="callout callout-danger text-justify">
                                                                                                <i class="fas fa-info-circle"></i> Are you sure you want to cancel {{ $inv->name }}?
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <a href="/cancel/approved_beginning_inv/{{ $inv->name }}" class="btn btn-primary w-100">Confirm</a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
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
                                                <td class="font-responsive text-center text-muted text-uppercase p-2" colspan="7">
                                                    No submitted beginning inventory
                                                </td>
                                            </tr>
                                        @endforelse
                                    </table>
                                    <div class="float-right mt-4">
                                        {{ $beginningInventory->appends(request()->input())->links('pagination::bootstrap-4') }}
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="stock-adjustment-content" role="tabpanel" aria-labelledby="stock-adjustment-tab" style="font-size: 9pt;">
                                    <div class="row p-2">
                                        <div class="col-4">
                                            <div class="row">
                                                <div class="col-11">
                                                    <select id="tab2-warehouse" class="form-control consignment-store-select"></select>
                                                </div>
                                                <div class="col-1 d-flex justify-content-center align-items-center">
                                                    <i class="fa fa-undo clear-filters"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4 offset-4">
                                            <a href="/stock_adjustment_form" class="btn btn-primary w-100" style="font-size: 9pt;"><i class="fa fa-edit"></i> Edit Stocks</a>
                                        </div>
                                    </div>
                                    <div id="stock-adjustments-container" class="p-2"></div>
                                </div>
                                <div class="tab-pane fade" id="inventory-audit-history-content" role="tabpanel" aria-labelledby="inventory-audit-history-tab">
                                    <div class="row pt-2" style="font-size: 9pt;">
                                        <div class="col-3">
                                            <select id="activity-logs-warehouse" class="form-control"></select>
                                        </div>
                                        <div class="col-3">
                                            <input type="text" class="form-control" id="activity-logs-daterange" style="font-size: 9pt;" placeholder="Select Date Range"/>
                                        </div>
                                        <div class="col-3">
                                            <select id="activity-logs-user" class="form-control" style="font-size: 9pt;">
                                                <option value="" selected disabled>Select a User</option>
                                                @foreach ($activityLogsUsers as $user)
                                                    <option value="{{ $user }}">{{ $user }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-3">
                                            <button type="button" class="btn btn-primary" id="refresh-activity-logs"><i class="fa fa-undo"></i></button>
                                        </div>
                                    </div>
                                    <div id="activity-logs-el" class="p-2"></div>
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
        table .items-table { width: 100%; }
        .morectnt span {
            display: none;
        }
        .last-row{
            width: 20% !important;
        }
        .filters-font{
            font-size: 13px !important;
        }
        .item-code-container{
            text-align: justify;
            padding: 10px;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
        .undo-replacement{
            cursor: pointer;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .empty-border{
            border: 1px solid red;
        }

        @media (max-width: 575.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media (max-width: 767.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            var warehouse = $('#activity-logs-warehouse').val();
            var date = $("#activity-logs-daterange").val();
            var user = $("#activity-logs-user").val();
            $(document).on('click', '#inventory-audit-history-tab', () => {
                loadActivityLogs(1, warehouse, date, user);
            })

            function loadActivityLogs(page, warehouse, date, user) {
                $.ajax({
                    type: "GET",
                    url: "/get_activity_logs?page=" + page ,
                    data: {
                        warehouse: warehouse,
                        date: date,
                        user: user
                    },
                    success: function (response) {
                        $('#activity-logs-el').html(response);
                    }
                });
            }

            $('#activity-logs-warehouse').select2({
                placeholder: 'Select Warehouse',

                ajax: {
                    url: '/consignment_stores',
                    method: 'GET',
                    dataType: 'json',
                    data: function (data) {
                        return {
                            q: data.term, // search term
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

            $(document).on('select2:select', '#activity-logs-warehouse', function(e){
                var warehouse = e.params.data.id;
                var user = $("#activity-logs-user").val();
                var date = $("#activity-logs-daterange").val();

                loadActivityLogs(1, warehouse, date, user);
            });

            $(document).on('click', '#activity-logs-pagination a', function(event){
                event.preventDefault();
                var user = $("#activity-logs-user").val();
                var date = $("#activity-logs-daterange").val();
                var page = $(this).attr('href').split('page=')[1];
                var warehouse = $('#activity-logs-warehouse').val();

                loadActivityLogs(page, warehouse, date, user);
            });

            $('#activity-logs-daterange').daterangepicker({
                autoUpdateInput: false,
                opens: 'left',
                locale: {
                    format: 'YYYY-MMM-DD',
                    separator: " to "
                }
            });

            $("#activity-logs-daterange").on('apply.daterangepicker', function (ev, picker) {
                var user = $('#activity-logs-user').val();
                var warehouse = $('#activity-logs-warehouse').val();
                var date = picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD');
                $(this).val(date);

                loadActivityLogs(1, warehouse, date, user);
            });

            $("#activity-logs-daterange").on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
                var user = $('#activity-logs-user').val();

                loadActivityLogs(1, warehouse, '', user);
            });

            $(document).on('change', '#activity-logs-user', function(){
                var user = $(this).val();
                var date = $("#activity-logs-daterange").val();
                var warehouse = $('#activity-logs-warehouse').val();

                loadActivityLogs(1, warehouse, date, user);
            })

            $(document).on('click', '#refresh-activity-logs', function (){
                $("#activity-logs-daterange").val('');
                $('#activity-logs-warehouse').empty().trigger('change');
                $('#activity-logs-user').val($("#activity-logs-user option:first").val());

                loadActivityLogs(1);
            });

            $(document).on('click', '#stock-adjustment-history-pagination a', function(e){
                e.preventDefault();
                var page = $(this).attr('href').split('page=')[1];

                load_stock_adjustment_history(page);
            });
            
            function load_stock_adjustment_history(page){
                $.ajax({
                    type: "GET",
                    url: "/stock_adjustment_history",
                    data: {
                        page: page,
                        branch_warehouse: $('#tab2-warehouse').val()
                    },
                    success: function (response) {
                        $('#stock-adjustments-container').html(response);
                    }
                });
            }

            $(document).on('click', '#stock-adjustment-tab', function (){
                load_stock_adjustment_history(1);
            });

            $('table.items-table').on('click', '.remove-item', function (){
                var name = $(this).data('name');
                var target = $(this).data('target');

                remove_row(name, target);
                validate_submit(name);
            });

            function remove_row(name, target){
                $('#row-' + target).remove();
                $('#item-count-' + name).text(parseInt($('#item-count-' + name).text()) - 1);
            }

            function validate_submit(name){
                var count = parseInt($('#item-count-' + name).text());
                if(count <= 0){
                    $('#' + name + '-submit').prop('disabled', true);
                }else{
                    $('#' + name + '-submit').prop('disabled', false);
                }
            }

            var excluded_items_arr = new Array();
            $('.modal-trigger').click(function(){
                excluded_items_arr = [];
                var active_branch = $(this).data('branch');
                $('.replacement-item').select2({
                    templateResult: formatState,
                    placeholder: 'Select an Item',

                    ajax: {
                        url: '/get_items/'+active_branch,
                        method: 'GET',
                        dataType: 'json',
                        data: function (data) {
                            return {
                                q: data.term, // search term
                                excluded_items: excluded_items_arr,
                                all_items: 1
                            };
                        },
                        processResults: function (response) {
                            return {
                                results: response.items
                            };
                        },
                        cache: true
                    }
                });
            });

            function formatState (opt) {
                if (!opt.id) {
                    return opt.text;
                }

                const optimage = opt.image;

                if(!optimage){
                    return opt.text;
                } else {
                    var $opt = $(
                    '<span><img src="' + optimage + '" width="40px" /> ' + opt.text + '</span>'
                    );
                    return $opt;
                }
            };

            // Change Item Controls

            $(document).on('select2:select', '.replace-item-modal .replacement-item', function(e){
                var name = $(this).data('id');

                // Display
                $('#' + name + '-replacement-item-code').text(e.params.data.id); // item code
                $('#' + name + '-replacement-description').html(e.params.data.description); // description
                $('#' + name + '-replacement-img-src').attr('src', e.params.data.image); // image

                $('#' + name + '-replacement-webp').attr('src', e.params.data.image_webp); // webp
                $('#' + name + '-replacement-img').attr('src', e.params.data.image); // image

                // hidden values
                $('#' + name + '-replacement-display-webp').text(e.params.data.image_webp);
                $('#' + name + '-replacement-display-image').text(e.params.data.image);
                $('#' + name + '-replacement-display-alt').text(e.params.data.alt);
                $('#' + name + '-replacement-display-uom').text(e.params.data.uom);

                $('#' + name + '-replacement-info').removeClass('d-none');
                $('.' + name + '.change-item').prop('disabled', false);
            });

            $('.change-item').click(function (){
                var target = $(this).data('target');
                var original_item_code = $(this).data('original-code');

                var item_code = $('#' + target + '-replacement-item-code').text(); // item code
                var description = $('#' + target + '-replacement-description').html(); // description
                var webp = $('#' + target + '-replacement-display-webp').text();
                var image = $('#' + target + '-replacement-display-image').text();
                var alt = $('#' + target + '-replacement-display-alt').text();
                var uom = $('#' + target + '-replacement-display-uom').text();

                $('#row-' + target).removeClass(original_item_code).addClass(item_code);
                $('#' + target + '-item-code-replacement').text(item_code);
                $('#' + target + '-webp-replacement').attr('src', webp);
                $('#' + target + '-img-src-replacement').attr('src', image);
                $('#' + target + '-img-replacement').attr('src', image);
                $('#' + target + '-description-replacement').html(description);
                $('#' + target + '-uom').text(uom);

                $('#' + target + '-container').removeClass('d-flex').addClass('d-none');
                $('#' + target + '-replacement-container').addClass('d-flex').removeClass('d-none');

                $('#item-price-' + target).attr('name', 'price[' + item_code + '][]');
                $('#item-qty-' + target).attr('name', 'qty[' + item_code + '][]');

                $('#' + target + '-replacement-info').addClass('d-none');
                $(".replacement-item").empty().trigger('change');
                $('.' + target + '.change-item').prop('disabled', true);
                $('#' + target + '-replacement-button').addClass('d-none');
                $('#' + target + '-undo-button').removeClass('d-none');
                $('#' + target + '-undo-button').data('replacement', item_code);

                reset_placeholders();
                close_modal('#' + target + '-replacement-Modal');
            });

            $('.undo-change').click(function (){
                var target = $(this).data('target');
                var original_item_code = $(this).data('original-code');
                var item_code = $(this).data('replacement');

                $('#item-price-' + target).attr('name', 'price[' + original_item_code + '][]');
                $('#item-qty-' + target).attr('name', 'qty[' + original_item_code + '][]');

                $('#row-' + target).removeClass(item_code).addClass(original_item_code);
                $('#' + target + '-item-code-replacement').text('');
                $('#' + target + '-webp-replacement').attr('src', '');
                $('#' + target + '-img-src-replacement').attr('src', '');
                $('#' + target + '-img-replacement').attr('src', '');
                $('#' + target + '-description-replacement').text('');
                $('#' + target + '-uom').text($(this).data('original-uom'));

                $('#' + target + '-container').addClass('d-flex').removeClass('d-none');
                $('#' + target + '-replacement-container').removeClass('d-flex').addClass('d-none');
                $('#' + target + '-undo-button').data('replacement', '');

                $('#' + target + '-replacement-button').removeClass('d-none');
                $('#' + target + '-undo-button').addClass('d-none');
            });

            // Change Item Controls

            // Add Modal Controls
            $(document).on('select2:select', '.add-item-modal .replacement-item', function(e){
                var name = $(this).data('name');

                $('#' + name + '-new-item-container').removeClass('d-none');

                // Display
                $('#' + name + '-item-code-display').text(e.params.data.id); // item code
                $('#' + name + '-description-display').html(e.params.data.description); // description
                $('#' + name + '-new-img').attr('src', e.params.data.image); // image

                $('#' + name + '-new-src-img-webp').attr('src', e.params.data.image_webp); // webp
                $('#' + name + '-new-src-img').attr('src', e.params.data.image); // image

                // hidden values
                $('#' + name + '-webp-display').text(e.params.data.image_webp);
                $('#' + name + '-img-display').text(e.params.data.image);
                $('#' + name + '-alt-display').text(e.params.data.alt);
                $('#' + name + '-max-display').text(e.params.data.max);
                $('#' + name + '-uom-display').text(e.params.data.uom);

                $('#' + name + '-new-item-stock').val(0);

                $('#' + name + '-item-selection-table').removeClass('d-none');
                $('#' + name + '-add-item').prop('disabled', false);
            });

            $('table.new-item-table').on('click', '.new-item-qtyplus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());

                // // If is not undefined
                if (!isNaN(currentVal)) {
                    // Increment
                    fieldName.val(currentVal + 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
            });

            // This button will decrement the value till 0
            $('table.new-item-table').on('click', '.new-item-qtyminus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If it isn't undefined or its greater than 0
                if (!isNaN(currentVal) && currentVal > 0) {
                    // Decrement one
                    fieldName.val(currentVal - 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
            });

            var items_array = new Array();
            $('.add-item').click(function (){
                var id = $(this).data('id');
                var img = $('#' + id + '-new-src-img').text();
                var alt = $('#' + id + '-alt-display').text();
                var qty = $('#' + id + '-new-item-stock').val();
                var uom = $('#' + id + '-uom-display').text();
                var webp = $('#' + id + '-webp-display').text();
                var price = $('#' + id + '-new-item-price').val();
                var stocks = $('#' + id + '-new-item-stock').val();
                var item_code = $('#' + id + '-item-code-display').text();
                var description = $('#' + id + '-description-display').text();

                var existing = $('#' + id + '-items-table').find('.' + item_code).eq(0).length;
                if (existing) {
                    showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
					return false;
                }

                var target = id + '-' + item_code;

                var row = '<tr id="row-' + target + '" class="' + item_code + '">' +
                    '<td class="text-center p-1 align-middle">-</td>' +
                    '<td class="text-center p-1 align-middle">' +
                        '<div class="d-flex flex-row justify-content-start align-items-center" id="' + target + '-container">' +
                            '<div class="p-2 text-left">' +
                                '<a href="' + img + '" class="view-images" data-item-code="' + item_code + '">' +
                                    '<picture>' +
                                        '<source srcset="' + webp + '" type="image/webp" width="60" height="60">' +
                                        '<source srcset="' + img + '" type="image/jpeg" width="60" height="60">' +
                                        '<img src="' + img + '" alt="' + alt + '" width="60" height="60">' +
                                    '</picture>' +
                                '</a>' +
                            '</div>' +
                            '<div class="p-2 text-left">' +
                                '<b>' + item_code + '</b>' +
                                '<span class="d-none d-xl-inline"> - ' + description + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-2 w-100 text-left d-none" id="' + target + '-selection">' + 
                            '<div class="row">' + 
                                '<div class="col-9">' + 
                                    '<select class="form-control replacement-item" id="' + target + '-replacement" data-original-code=' + item_code + ' style="width: 200px !important;"></select>' + 
                                '</div>' + 
                                '<div class="col-3" style="display: flex; justify-content: center; align-items: center;">' + 
                                    '<span class="undo-replacement" data-item-code="' + item_code + '" data-target="' + target + '" data-name="' + id + '"><i class="fa fa-undo"></i> Reset</span>' + 
                                '</div>' + 
                            '</div>' + 
                        '</div>' + 
                    '</td>' +
                    '<td class="text-center p-1 align-middle text-nowrap">' + 
                        '<div class="input-group p-1 col-6 mx-auto">' +
                            '<div class="input-group-prepend p-0">' +
                                '<button class="btn btn-outline-danger btn-xs new-item-qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>' +
                            '</div>' +
                            '<div class="custom-a p-0">' +
                                '<input type="text" name="qty[' + item_code + ']" class="form-control form-control-sm qty new-item-validate new-item-stock" value="' + qty + '" style="text-align: center; width: 47px" required>' +
                            '</div>' +
                            '<div class="input-group-append p-0">' +
                                '<button class="btn btn-outline-success btn-xs new-item-qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>' +
                            '</div>' +
                        '</div>' +
                        '<small>' + uom + '</small>' + 
                    '</td>' + 
                    '<td class="text-center p-1 align-middle">' + 
                        '₱ <input type="text" name="price[' + item_code + ']" id="item-price-' + item_code + '" value="' + price + '" style="text-align: center; width: 60px" required/>' + 
                    '</td>' + 
                    '<td class="text-center p-1 align-middle">' + 
                        '<div class="btn-group" role="group" aria-label="Basic example">' + 
                            '<button type="button" class="btn btn-xs btn-outline-secondary p-1 remove-item" data-name="' + id + '" data-target="' + target + '" style="font-size: 9pt;">Remove</button>' + 
                        '</div>' + 
                    '</td>' + 
                '</tr>' + 
                '<tr class="d-xl-none">' +
                    '<td colspan="4" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">' +
                        '<div class="w-100 item-description">' + description + '</div>' +
                    '</td>' +
                '</tr>';

                $('#item-count-' + id).text(parseInt($('#item-count-' + id).text()) + 1);
                $('#' + id + '-new-item-container').addClass('d-none');
                $(".replacement-item").empty().trigger('change');
                $('#' + id + '-add-item').prop('disabled', true);
                $('#' + id + '-items-table tbody').prepend(row);

                close_modal('#addItems' + id + 'Modal');
                reset_placeholders();
                validate_submit(id);
            });

            // Add Modal Controls

            function reset_placeholders(){
                $('.text-placeholder').text('');
                $('.src-placeholder').attr('src', '');
                $('.value-placeholder').val('');
            };

            $('table.items-table').on('click', '.new-item-qtyplus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());

                // // If is not undefined
                if (!isNaN(currentVal)) {
                    // Increment
                    fieldName.val(currentVal + 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
            });

            // This button will decrement the value till 0
            $('table.items-table').on('click', '.new-item-qtyminus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If it isn't undefined or its greater than 0
                if (!isNaN(currentVal) && currentVal > 0) {
                    // Decrement one
                    fieldName.val(currentVal - 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }
            });
            
            var from_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[0])->format("Y-M-d") : now()->subDays(7)->format("Y-M-d")  }}';
            var to_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[1])->format("Y-M-d") : now()->format("Y-M-d")  }}';
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
                // $('#'+reference+'-qty').addClass('d-none');
                // $('#'+reference+'-new-qty').removeClass('d-none');
                $('#'+reference+'-price').addClass('d-none');
                $('#'+reference+'-new-price').removeClass('d-none');
                $('#'+$(this).data('name')+'-stock-adjust-update-btn').slideDown();
            });

            $(document).on('keyup', '#remarks', function(){
                $('#'+$(this).data('name')+'-stock-adjust-update-btn').slideDown();
            });

            var showTotalChar = 200, showChar = "Show more", hideChar = "Show less";
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

            $('.consignment-store-select').select2({
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

            $(document).on('click', '.clear-filters', function (e){
                $("#tab2-warehouse").empty().trigger('change')
            });

            $(document).on('change', '#tab2-warehouse', function (e){
                e.preventDefault();
                load_stock_adjustment_history(1);
            });

            function showNotification(color, message, icon){
                $.notify({
                    icon: icon,
                    message: message
                },{
                    type: color,
                    timer: 500,
                    z_index: 1060,
                    placement: {
                        from: 'top',
                        align: 'center'
                    }
                });
            }
        });
    </script>
@endsection