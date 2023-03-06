@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
    <div class="container-fluid p-3">
        <div class="row">
            <div class="col-md-12">
                <div class="back-btn">
                    <img src="{{ asset('storage/icon/back.png') }}" style="width: 45px; cursor: pointer;" id="back-btn">
                </div>
                <ul class="nav nav-tabs" id="ip-navs" role="tablist" style="font-size: 10pt;">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#item-info">
                            <span class="d-none d-lg-block">Item Info</span>
                            <i class="fas fa-info d-block d-lg-none"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="get-athena-transactions" data-toggle="tab" href="#athena-logs">
                            <span class="d-none d-lg-block">Athena Transactions</span>
                            <i class="fas fa-boxes d-block d-lg-none"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#history">
                            <span class="d-none d-lg-block">ERP Submitted Transaction Histories</span>
                            <i class="fas fa-history d-block d-lg-none"></i>
                        </a>
                    </li>
                    @if(Auth::check() and in_array(Auth::user()->user_group, ['Inventory Manager']))
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab_4">
                            <span class="d-none d-lg-block">Stock Reservations</span>
                            <i class="fas fa-warehouse d-block d-lg-none"></i>
                        </a>
                    </li>
                    @endif
                    @if (in_array($user_group, ['Manager', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link d-none d-lg-block" data-toggle="tab" href="#purchase-history">Purchase Rate History</a>
                        <a class="nav-link d-block d-lg-none" data-toggle="tab" href="#purchase-history"><i class="fa fa-shopping-cart"></i></a>
                    </li>
                    @endif
                    @if(Auth::check() and in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Promodiser', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#consignment-stock-movement">
                            <span class="d-none d-lg-block">Consignment Stock Movement</span>
                            <i class="fas fa-warehouse d-block d-lg-none"></i>
                        </a>
                    </li>
                    @endif
                </ul>
                <div class="tab-content">
                    <div class="container-fluid tab-pane bg-white" id="consignment-stock-movement">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-3 p-2">
                                        @if(Auth::check() and in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
                                        <select class="form-control csm-filter" name="store" id="consignment-store-select"></select>
                                        @else
                                        @if (count($consignment_branches) > 1)
                                        <select class="form-control csm-filter" name="store">
                                            @foreach ($consignment_branches as $store)
                                            <option value="{{ $store }}">{{ $store }}</option>
                                            @endforeach
                                        </select>
                                        @endif
                                        @if ((count($consignment_branches) == 1))
                                        <input type="hidden" class="csm-filter" name="store" value="{{ $consignment_branches[0] }}">
                                        @endif
                                        @endif
                                    </div>
                                    <div class="col-3 p-2">
                                        <input type="text" class="form-control date-range" id="consignment-date-range" name="date_range" style="height: 30px;"> 
                                    </div>
                                    <div class="col-3 p-2">
                                        <select name="user" id="consignment-user-select" class="form-control select2"></select>
                                    </div>
                                    <div class="col-3 p-2">
                                        <button class="btn btn-sm btn-secondary" id="consignment-reset">Reset Filters</button>
                                    </div>
                                    <div class="col-12">
                                        <div id="consignment-ledger-content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="item-info" class="container-fluid tab-pane active bg-white">
                        <div class="row">
                            <div class="col-12 col-lg-9">
                                <div class="box box-solid mt-2">
                                    <div class="row">
                                        @php
                                            if (!$item_details->item_brochure_description) {
                                                $item_brochure_description = $item_details->description;
                                                $exploded_description = explode(",", strip_tags($item_brochure_description));
                                                $item_brochure_description = (isset($exploded_description[0]) ? $exploded_description[0] : '') . (isset($exploded_description[1]) ? ", " . $exploded_description[1] : '');
                                            } else {
                                                $item_brochure_description = strip_tags($item_details->item_brochure_description);
                                            }

                                            if (!$item_details->item_brochure_name) {
                                                $item_brochure_name = $item_details->item_name;
                                                $exploded_item_name = explode("-", strip_tags($item_brochure_name));
                                                $exploded_item_name1 = (isset($exploded_item_name[0]) ? $exploded_item_name[0] : '');
                                                $exploded_item_name2 = (isset($exploded_item_name[1]) ? '-' . $exploded_item_name[1] : '');
                                                $exploded_item_name3 = (isset($exploded_item_name[2]) ? '-' . $exploded_item_name[2] : '');
                                                $exploded_item_name4 = (isset($exploded_item_name[3]) ? '-' . $exploded_item_name[3] : '');
                                                $item_brochure_name = $exploded_item_name1 . $exploded_item_name2 . $exploded_item_name3 . $exploded_item_name4;
                                            } else {
                                                $item_brochure_name = strip_tags($item_details->item_brochure_name);
                                            }
                                        @endphp
                                        <div class="d-md-none mb-2 col-12">
                                            <div class="dropdown show">
                                                <a class="btn btn-sm p-1 btn-secondary dropdown-toggle float-right" href="#" role="button"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 9pt;">
                                                    Menu
                                                </a>
                                              
                                                <div class="dropdown-menu" style="font-size: 9pt;">
                                                    <a class="print-brochure-btn dropdown-item" href="#" data-item-code="{{ $item_details->name }}" data-item-name="{{ $item_brochure_name }}" data-item-description="{{ $item_brochure_description }}">
                                                        <i class="fas fa-print pb-1"></i> Print Brochure
                                                    </a>
                                                    <a class="dropdown-item upload-item-image" href="#" data-item-code="{{ $item_details->name }}">
                                                        <i class="fas fa-camera pb-1"></i> Upload Image
                                                    </a>
                                                    <a class="dropdown-item edit-warehouse-location-btn" href="#" data-item-code="{{ $item_details->name }}">
                                                        <i class="fas fa-warehouse pb-1"></i> Location
                                                    </a>
                                                    @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target='#item-information-modal'>
                                                            <i class="fa fa-edit pb-1"></i> Package Details
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-lg-3 pl-2 pr-2 pb-2 pt-0">
                                            <div class="row pb-2" style="border-bottom: solid 3px #2E86C1">
                                                @for($i = 0; $i <= 3; $i++)
                                                    @isset($item_images[$i])
                                                        @php
                                                            $image = '/img/' . $item_images[$i];
                                                            $webp = explode('.', $image)[0].'.webp';
                                                            $alt = Illuminate\Support\Str::slug(explode('.', $image)[0], '-');
                                                        @endphp
                                                        <div class="{{ $i == 0 ? 'col-12' : 'col-4 mt-2 p-2 border' }}" style="{{ $i > 0 ? 'height: 75px;' : null }}">
                                                            <a href="{{ asset('storage/'.$image) }}" class="view-images" data-item-code="{{ $item_details->name }}" data-idx="{{ $i }}">
                                                                <picture>
                                                                    <source srcset="{{ asset('storage'.$webp) }}" type="image/webp">
                                                                    <source srcset="{{ asset('storage'.$image) }}" type="image/jpeg">
                                                                    <img src="{{ asset('storage'.$image) }}" alt="{{ $alt }}" class="img-responsive hover" style="width: 100%; height: 100%;">
                                                                </picture>
                                                                @if($i == 3 && count($item_images) > 4)
                                                                    <div class="card-img-overlay text-center">
                                                                        <h5 class="card-title m-1 font-weight-bold" style="color: #fff; text-shadow: 2px 2px 8px #000;">MORE</h5>
                                                                    </div>
                                                                @endif
                                                            </a>
                                                        </div>
                                                    @endisset
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="col-md-9 col-lg-9">
                                            <div class="row">
                                                <div class="col-12 col-md-8 col-lg-12">
                                                    <span id="selected-item-code" class="d-none">{{ $item_details->name }}</span>
                                                    <dl class="ml-3">
                                                        <dt class="responsive-item-code" style="font-size: 14pt;">{{ $item_details->name.' '.$item_details->brand }}</dt>
                                                        <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $item_details->description !!}</dd>
                                                    </dl>
                                                    <div id="item-information-container"></div>
                                                </div>
                                                <div class="d-none d-md-block d-lg-none col-4">
                                                    <a class="btn btn-app m-2 d-block print-brochure-btn pb-5" data-item-code="{{ $item_details->name }}" data-item-name="{{ $item_brochure_name }}" data-item-description="{{ $item_brochure_description }}" style="font-size: 8pt !important">
                                                        <i class="fas fa-print pb-1"></i> Print Brochure
                                                    </a>
                                                    <a class="btn btn-app m-2 d-block upload-item-image pb-5" data-item-code="{{ $item_details->name }}" style="font-size: 8pt !important">
                                                        <i class="fas fa-camera pb-1"></i> Upload Image
                                                    </a>
                                                    <a class="btn btn-app m-2 d-block edit-warehouse-location-btn pb-5" data-item-code="{{ $item_details->name }}" style="font-size: 8pt !important">
                                                        <i class="fas fa-warehouse pb-1"></i> Location
                                                    </a>
                                                    @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                                    <a class="btn btn-app m-2 d-block pb-5" data-toggle="modal" data-target='#item-information-modal' style="font-size: 8pt !important">
                                                        <i class="fa fa-edit pb-1"></i> Package Details
                                                    </a> 
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-block ml-3">
                                                <p class="mt-2 mb-2 text-1center">
                                                    @if (in_array($user_department, $allowed_department) && !in_array($user_group, ['Manager', 'Director']) && $default_price > 0) 
                                                    <span class="d-block font-weight-bold mt-3" style="font-size: 17pt;">{{ '₱ ' . number_format($default_price, 2, '.', ',') }}</span>
                                                    <span class="d-block responsive-description" style="font-size: 11pt;">Standard Selling Price</span>
                                                    @if ($is_tax_included_in_rate)
                                                    <small class="text-muted font-italic" style="font-size: 7.5pt;">* VAT inclusive</small>
                                                    @endif
                                                    @endif

                                                    @if (in_array($user_group, ['Manager', 'Director']))
                                                        @if ($default_price > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 17pt;">{{ '₱ ' . number_format($default_price, 2, '.', ',') }}</span>
                                                        <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                                                        @if ($is_tax_included_in_rate)
                                                        <small class="text-muted font-italic" style="font-size: 7.5pt;">* VAT inclusive</small>
                                                        @endif
                                                        @endif
                                                        @if ($minimum_selling_price > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 15pt;">{{ '₱ ' . number_format($minimum_selling_price, 2, '.', ',') }}</span>
                                                        <span class="d-block" style="font-size: 9pt;">Minimum Selling Price</span>
                                                        @endif
                                                        @if ($last_purchase_rate > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 11pt;">{{ '₱ ' . number_format($last_purchase_rate, 2, '.', ',') }}</span>
                                                        <span class="d-inline-block" style="font-size: 9pt;">Last Purchase Rate</span>
                                                        <span class="d-inline-block font-weight-bold font-italic" style="font-size: 9pt;">- {{ $last_purchase_date }}</span>
                                                        @endif
                                                        @if ($avgPurchaseRate > 0)
                                                        <span class="d-block font-weight-bold avg-purchase-rate-div mt-3" style="font-size: 11pt;">{{ $avgPurchaseRate }}</span>
                                                        <span class="d-inline-block" style="font-size: 9pt;">Average Purchase Rate</span>
                                                        @endif
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="d-none d-lg-block">
                                                <div class="card-header border-bottom-0 p-1 ml-3">
                                                    <h3 class="card-title m-0 font-responsive"><i class="fa fa-box-open"></i> Stock Level</h3>
                                                </div>
                                                <div class="box box-solid p-0 ml-3">
                                                    <div class="box-header with-border">
                                                        <div class="box-body item-stock-level-div"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 d-block d-lg-none">
                                            <div class="card-header border-bottom-0 p-1 ml-3">
                                                <h3 class="card-title m-0 font-responsive"><i class="fa fa-box-open"></i> Stock Level</h3>
                                            </div>
                                            <div class="box box-solid p-0 ml-3">
                                                <div class="box-header with-border">
                                                    <div class="box-body item-stock-level-div"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-none d-lg-block col-lg-3 pr-2">
                                <div class="box box-solid h-100 pr-0s">
                                    <div class="col-sm-12 col-md-12 col-lg-10 offset-lg-2 col-xl-6 offset-xl-6">
                                        <a class="btn btn-app m-2 d-block print-brochure-btn pb-5" data-item-code="{{ $item_details->name }}" data-item-name="{{ $item_brochure_name }}" data-item-description="{{ $item_brochure_description }}">
                                            <i class="fas fa-print pb-1"></i> Print Brochure
                                        </a>
                                        <a class="btn btn-app m-2 d-block upload-item-image pb-5" data-item-code="{{ $item_details->name }}">
                                            <i class="fas fa-camera pb-1"></i> Upload Image
                                        </a>
                                        <a class="btn btn-app m-2 d-block edit-warehouse-location-btn pb-5" data-item-code="{{ $item_details->name }}">
                                            <i class="fas fa-warehouse pb-1"></i> Location
                                        </a>
                                        @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                        <a class="btn btn-app m-2 d-block pb-5" data-toggle="modal" data-target='#item-information-modal'>
                                            <i class="fa fa-edit pb-1"></i> Package Details
                                        </a> 
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if (count($co_variants) > 0)
                            <div class="col-12">
                                <div class="card-header border-bottom-0">
                                    <h3 class="card-title font-responsive mt-5"><i class="fas fa-project-diagram"></i> Variants</h3>
                                </div>
                            </div>
                            @endif
                            <div class="container col-12 mt-2">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="example" class="overflow-auto">
                                            <table class="table table-sm table-bordered table-striped variants-table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" class="text-center align-middle" style="background-color: #CCD1D1;">Item Code</th>
                                                        @foreach ($attribute_names as $attribute_name)
                                                        <th scope="col" class="text-center align-middle" style="width: 350px;">{{ $attribute_name }}</th>
                                                        @endforeach
                                                        <th scope="col" class="text-center align-middle">Stock Availability</th>
                                                        @if (in_array($user_department, $allowed_department) && !in_array($user_group, ['Manager', 'Director'])) 
                                                        <th scope="col" class="text-center text-nowrap align-middle" style="width: 300px;">Standard Price</th>
                                                        @endif
                                                        @if (in_array($user_group, ['Manager', 'Director']))
                                                        <th scope="col" class="text-center text-nowrap align-middle" style="width: 300px;">Cost</th>
                                                        <th scope="col" class="text-center text-nowrap align-middle" style="width: 300px;">Min. Selling Price</th>
                                                        <th scope="col" class="text-center text-nowrap align-middle" style="width: 300px;">Standard Price</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="highlight-row">
                                                        <th scope="row" class="text-center align-middle" style="background-color: #001F3F !important;">{{ $item_details->name }}</th>
                                                        @foreach ($attribute_names as $attribute_name)
                                                        <td class="text-center align-middle">{{ array_key_exists($attribute_name, $item_attributes) ? $item_attributes[$attribute_name] : null }}</td>
                                                        @endforeach
                                                        <td class="text-center align-middle text-nowrap variants-table">
                                                            <span class="badge badge-{{ ($item_stock_available > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($item_stock_available > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                                        </td>
                                                        @if (in_array($user_department, $allowed_department) && !in_array($user_group, ['Manager', 'Director'])) 
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($default_price > 0)
                                                            {{ '₱ ' . number_format($default_price, 2, '.', ',') }}
                                                            @else
                                                            --
                                                            @endif
                                                        </td>
                                                        @endif
                                                        @if (in_array($user_group, ['Manager', 'Director']))
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($manual_rate)
                                                            <center>
                                                                <span class="entered-price d-none">0.00</span>
                                                                <form action="/update_item_price/{{ $item_details->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $item_details->name }}-computed-price">
                                                                    @csrf
                                                                    <div class="input-group" style="width: 120px;">
                                                                        <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" value="{{ $item_rate }}" required>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </center>
                                                            @else
                                                            @if ($item_rate > 0)
                                                                {{ '₱ ' . number_format($item_rate, 2, '.', ',') }}
                                                            @else
                                                            <center>
                                                                <span class="entered-price d-none">0.00</span>
                                                                <form action="/update_item_price/{{ $item_details->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $item_details->name }}-computed-price">
                                                                    @csrf
                                                                    <div class="input-group" style="width: 120px;">
                                                                        <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" required>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </center>
                                                            @endif
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($minimum_selling_price > 0)
                                                            <span id="{{ $item_details->name }}-computed-price-min">{{ '₱ ' . number_format($minimum_selling_price, 2, '.', ',') }}</span>
                                                            @else
                                                            <span id="{{ $item_details->name }}-computed-price-min">--</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($default_price > 0)
                                                            <span id="{{ $item_details->name }}-computed-price">{{ '₱ ' . number_format($default_price, 2, '.', ',') }}</span>
                                                            @else
                                                            <span id="{{ $item_details->name }}-computed-price">--</span>
                                                            @endif
                                                        </td>
                                                        @endif
                                                    </tr>
                                                    @foreach ($co_variants as $variant)
                                                    <tr class="variants-table">
                                                        <td class="text-center align-middle font-weight-bold text-dark" style="background-color: #CCD1D1;">
                                                            <a href="/get_item_details/{{ $variant->name }}">{{ $variant->name }}</a>
                                                        </td>
                                                        @foreach ($attribute_names as $attribute_name)
                                                        @php
                                                            $attr_val = null;
                                                            if (array_key_exists($variant->name, $attributes)) {
                                                                $attr_val = array_key_exists($attribute_name, $attributes[$variant->name]) ? $attributes[$variant->name][$attribute_name] : null;
                                                            }
                                                        @endphp
                                                        <td class="text-center align-middle p-2">{{ $attr_val }}</td>
                                                        @endforeach
                                                        @php
                                                            $avail_stock = array_key_exists($variant->name, $actual_variant_stocks) ? $actual_variant_stocks[$variant->name] : 0;
                                                        @endphp
                                                        <td class="text-center align-middle text-nowrap variants-table">
                                                            <span class="badge badge-{{ ($avail_stock > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($avail_stock > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                                        </td>
                                                        @php
                                                            $price = 0;
                                                            if(array_key_exists($variant->name, $variants_price_arr)){
                                                                $price = $variants_price_arr[$variant->name];
                                                            }
                                                        @endphp
                                                        @if (in_array($user_department, $allowed_department) && !in_array($user_group, ['Manager', 'Director'])) 
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($price > 0)
                                                            {{ '₱ ' . number_format($price, 2, '.', ',') }}
                                                            @else
                                                            --
                                                            @endif
                                                        </td>
                                                        @endif
                                                        @if (in_array($user_group, ['Manager', 'Director']))
                                                        <td class="text-center align-middle text-nowrap">
                                                            @php
                                                                $cost = 0;
                                                                if(array_key_exists($variant->name, $variants_cost_arr)){
                                                                    $cost = $variants_cost_arr[$variant->name];
                                                                }
                                                                $is_manual = 0;
                                                                if(array_key_exists($variant->name, $manual_price_input)){
                                                                    $is_manual = $manual_price_input[$variant->name];
                                                                }
                                                            @endphp
                                                             @if ($is_manual)
                                                             <center>
                                                                 <span class="entered-price d-none">0.00</span>
                                                                 <form action="/update_item_price/{{ $variant->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $variant->name }}-computed-price">
                                                                     @csrf
                                                                     <div class="input-group" style="width: 120px;">
                                                                         <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" value="{{ $cost }}" required>
                                                                         <div class="input-group-append">
                                                                             <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                         </div>
                                                                     </div>
                                                                 </form>
                                                            </center>
                                                            @else
                                                            @if ($cost > 0)
                                                               {{ '₱ ' . number_format($cost, 2, '.', ',') }}
                                                            @else
                                                            <center>
                                                                <span class="entered-price d-none">0.00</span>
                                                                <form action="/update_item_price/{{ $variant->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $variant->name }}-computed-price">
                                                                    @csrf
                                                                    <div class="input-group" style="width: 120px;">
                                                                        <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" required>
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </center>
                                                            @endif
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle text-nowrap">
                                                            @php
                                                                $minprice = 0;
                                                                if(array_key_exists($variant->name, $variants_min_price_arr)){
                                                                    $minprice = $variants_min_price_arr[$variant->name];
                                                                }
                                                            @endphp
                                                            @if ($minprice > 0)
                                                            <span id="{{ $variant->name }}-computed-price-min">{{ '₱ ' . number_format($minprice, 2, '.', ',') }}</span>
                                                            @else
                                                            <span id="{{ $variant->name }}-computed-price-min">--</span>
                                                            @endif
                                                        </td>
                                                         <td class="text-center align-middle text-nowrap">
                                                            @if ($price > 0)
                                                            <span id="{{ $variant->name }}-computed-price">{{ '₱ ' . number_format($price, 2, '.', ',') }}</span>
                                                            @else
                                                            <span id="{{ $variant->name }}-computed-price">--</span>
                                                            @endif
                                                        </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="m-2">
                                    {{ $co_variants->links('pagination::bootstrap-4') }}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card-header border-bottom-0">
                                    <h3 class="card-title font-responsive mb-3 mt-5"><i class="fas fa-filter"></i> Item Alternatives</h3>
                                </div>
                                <div class="d-flex flex-row flex-nowrap overflow-auto">
                                    @forelse($item_alternatives as $a)
                                    <div class="custom-body m-1">
                                        <div class="card card-default">
                                            <div class="card-body p-0">
                                                <div class="col-12">
                                                    <div class="d-flex flex-row">
                                                        <div class="pt-2 pb-2 pr-1 pl-1">
                                                            @php
                                                                $img = ($a['item_alternative_image']) ? '/img/' . explode('.', $a['item_alternative_image'])[0].'.jpg' : '/icon/no_img.jpg';
                                                                $img_webp = ($a['item_alternative_image']) ? '/img/' . explode('.', $a['item_alternative_image'])[0].'.webp' : '/icon/no_img.webp';
                                                            @endphp
                                                            <a href="{{ asset('storage' . $img) }}" data-toggle="lightbox" data-gallery="{{ $a['item_code'] }}" data-title="{{ $a['item_code'] }}">
                                                                <picture>
                                                                    <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" class="rounded" width="80" height="80">
                                                                    <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="rounded" width="80" height="80">
                                                                    <img src="{{ asset('storage'.$img) }}" class="rounded" width="80" height="80">
                                                                </picture>
                                                            </a>
                                                        </div>
                                                        <a href="/get_item_details/{{ $a['item_code'] }}" class="text-dark" style="font-size: 9pt;">
                                                            <div class="p-1 text-justify">
                                                                <span class="font-weight-bold font-responsive">{{ $a['item_code'] }}</span>
                                                                <small class="font-italic font-responsive" style="font-size: 9pt;">{{ \Illuminate\Support\Str::limit($a['description'], $limit = 78, $end = '...') }}</small>
                                                                <br>
                                                                <span class="badge badge-{{ ($a['actual_stocks'] > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($a['actual_stocks'] > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-md-12">
                                        <h5 class="text-center font-responsive">No Item Alternative(s)</h5>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <div id="athena-logs" class="container-fluid tab-pane bg-white p-2">
                        <div class="col-md-2 p-2" style="display: inline-block">
                            <div class="form-group m-0 font-responsive" id="ath-src-warehouse-filter-parent" style="z-index: 1050">
                                <select name="ath-src-warehouse" id="ath-src-warehouse-filter" class="form-control"></select>
                            </div>
                        </div>
                        <div class="col-md-2 p-2" style="display: inline-block">
                            <div class="form-group m-0 font-responsive" id="ath-to-warehouse-filter-parent" style="z-index: 1050">
                                <select name="ath-to-warehouse" id="ath-to-warehouse-filter" class="form-control"></select>
                            </div>
                        </div>
                        <div class="col-md-2 p-2" style="display: inline-block">
                            <div class="form-group m-0 font-responsive" id="warehouse-user-filter-parent" style="z-index: 1050">
                                <select name="warehouse_user" id="warehouse-user-filter" class="form-control"></select>
                            </div>
                        </div>
                        <div class="col-md-2" style="display: inline-block">
                            <button class="btn btn-secondary font-responsive btn-sm" id="athReset">Reset Filters</button>
                        </div>
                        <div id="athena-transactions" class="col-12"></div>
                    </div>
        
                    <div id="history" class="container-fluid tab-pane bg-white p-2">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="col-md-3 p-0" style="display: inline-block;">
                                    <div class="form-group m-1">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>
                                            <input type="text" name="erpdates" class="form-control float-right font-responsive" id="erp_dates">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 p-2" style="display: inline-block">
                                    <div class="form-group m-0 font-responsive" id="erp-warehouse-filter-parent" style="z-index: 1050">
                                        <select name="erp-warehouse" id="erp-warehouse-filter" class="form-control"></select>
                                    </div>
                                </div>
                                <div class="col-md-3 p-2" style="display: inline-block">
                                    <div class="form-group m-0 font-responsive" id="erp-warehouse-user-filter-parent" style="z-index: 1050">
                                        <select name="erp-warehouse-user" id="erp-warehouse-user-filter" class="form-control"></select>
                                    </div>
                                </div>
                                <div class="col-md-2" style="display: inline-block">
                                    <button class="btn btn-secondary font-responsive btn-sm" id="erpReset">Reset Filters</button>
                                </div>
                                <div class="box-body table-responsive no-padding font-responsive" id="stock-ledger-table"></div>
                            </div>
                        </div>
                        <div id="stock-ledger" class="col-12"></div>
                    </div>
                    @if (in_array($user_group, ['Manager', 'Director']))
                    <div id="purchase-history" class="container-fluid tab-pane bg-white">
                        <div id="purchase-history-div" class="p-3 col-12"></div>
                    </div>
                    @endif
                    <div class="container-fluid tab-pane bg-white" id="tab_4">
                        <div class="row">
                            <div class="col-md-12">
                                @php
                                    $attr = null;
                                    if(Auth::check()){
                                        $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                                    }
                                @endphp
                                <div class="float-right m-2">
                                    <button class="btn btn-primary font-responsive btn-sm" id="add-stock-reservation-btn" {{ $attr }}>New Stock Reservation</button>
                                </div>
                                <div class="box-body table-responsive no-padding font-responsive" id="stock-reservation-table"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
          #ip-navs .nav-link {
            padding: 10px 20px;
            color: #2c3e50;
            text-decoration: none;
        }
        #ip-navs .nav-item .active {
            color: #2e86c1 !important;
            font-weight: bolder !important;
            border-bottom: 3px solid #2e86c1 !important;
        }
        #example tr > *:first-child {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            min-width: 7rem;
            z-index: 1;
        }
        #example tr > *:first-child::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: -1;
        }
        .custom-body {
            min-width: 406px;
            max-width: 406px;
        }

        .table-highlight{
            border: 2px solid rgba(0, 31, 63, 0.3) !important;
        }

        .highlight-row{
            background-color: #001F3F !important;
            color: #fff;
            box-shadow: 2px 2px 8px #000000;
        }
        .variant-tabs{
            border-top: 1px solid #DEE2E6 !important;
        }

        .variant-tabs .nav-item .active{
            border-top: none !important;
            border-bottom: 1px solid #DEE2E6 !important;
        }
        .back-btn{
            position: absolute;
            right: 70px;
            top: -10px;
        }
        .responsive-item-code{
            font-size: 14pt;
        }
        .responsive-description{
            font-size: 11pt;
        }
        .variants-table{
            font-size: 9pt;
        }
        @media (max-width: 575.98px) {
            #example tr > *:first-child {
                min-width: 5rem;
            }
            .pagination{
                font-size: 10pt !important;
            }
            .responsive-item-code{
                font-size: 12pt !important;
            }
            .responsive-description{
                font-size: 9pt !important;
            }
            .variants-table{
                font-size: 8pt !important;
            }
        }
        @media (max-width: 767.98px) {
            #example tr > *:first-child {
                min-width: 5rem;
            }
            .pagination{
                font-size: 10pt !important;
            }
            .responsive-item-code{
                font-size: 12pt !important;
            }
            .responsive-description{
                font-size: 9pt !important;
            }
            .variants-table{
                font-size: 8pt !important;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .pagination{
                font-size: 10pt !important;
            }
            .back-btn{
                right: 0;
            }
            .responsive-item-code{
                font-size: 12pt !important;
            }
            .responsive-description{
                font-size: 9pt !important;
            }
            .variants-table{
                font-size: 8pt !important;
            }
        }
        @media only screen and (min-device-width : 768px) and (orientation : landscape) {
            .pagination{
                font-size: 10pt !important;
            }
            .back-btn{
                right: 0;
            }
            .responsive-item-code{
                font-size: 12pt !important;
            }
            .responsive-description{
                font-size: 9pt !important;
            }
            .variants-table{
                font-size: 8pt !important;
            }
        }

        .select2{
			width: 100% !important;
			outline: none !important;
            font-size: 9pt;
		}
		.select2-selection__rendered {
			line-height: 18px !important;
			outline: none !important;
		}
		.select2-container .select2-selection--single {
			height: 29px !important;
			padding-top: 1.5%;
			outline: none !important;
		}
		.select2-selection__arrow {
			height: 28px !important;
		}
        .date-range, .myFont{
            font-size:9pt;
        }
    </style>
@endsection
@section('script')
    <script>
        get_item_stock_levels('{{ $item_details->name }}');
        $(document).on('submit', '#edit-warehouse-location-form', function (e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.status) {
                        $('#warehouseLocationModal').modal('hide');

                        get_item_stock_levels(response.item_code);
                        showNotification("success", response.message, "fa fa-check");
                    } else {
                        showNotification("danger", response.message, "fa fa-info");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                }
            });
        });

        function get_item_stock_levels(item_code) {
            $.ajax({
                type: 'GET',
                url: '/get_item_stock_levels/' + item_code,
                success: function(response){
                    $('.item-stock-level-div').html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                }
            });
        }
        
        $(document).on('submit', '.update-price-form', function(e){
            e.preventDefault();

            var entered_price_computed = $(this).data('id');

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    $('#' + entered_price_computed).text(response.standard_price);
                    $('#' + entered_price_computed + '-min').text(response.min_price);
                    showNotification("success", 'Item price updated.', "fa fa-check");
                }
            });
        });
        
        load_item_information();
        function load_item_information(){
            $.ajax({
                type: 'GET',
                url: '/get_item_details/{{ $item_details->name }}',
                success: function(response){
                    $('#item-information-container').html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Error in getting product information.', "fa fa-info");
                }
            });
        }

        $('#back-btn').on('click', function(e){
            e.preventDefault();
            window.history.back();
        });

        get_athena_transactions();
        function get_athena_transactions(page){
            var item_code = '{{ $item_details->name }}';
            var ath_src = $('#ath-src-warehouse-filter').val();
            var ath_trg = $('#ath-to-warehouse-filter').val();
            var ath_user = $('#warehouse-user-filter').val();
            var ath_drange = $('#ath_dates').val();
            $.ajax({
                type: 'GET',
                url: '/get_athena_transactions/' + item_code + '?page=' + page + '&wh_user=' + ath_user + '&src_wh=' + ath_src + '&trg_wh=' + ath_trg + '&ath_dates=' + ath_drange,
                success: function(response){
                    $('#athena-transactions').html(response);
                }
            });
        }

        get_stock_reservation();
        function get_stock_reservation(tbl, page){
            var item_code = '{{ $item_details->name }}';
            $.ajax({
                type: 'GET',
                url: '/get_stock_reservation/' + item_code + '?' + tbl + '=' + page,
                success: function(response){
                    $('#stock-reservation-table').html(response);
                }
            });
        }

        $(document).on('click', '#stock-reservations-pagination-1 a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            get_stock_reservation(page);
        });

        $('#stock-reservation-form').submit(function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.error) {
                        showNotification("danger", response.modal_message, "fa fa-info");
                    }else{
                        get_stock_reservation();
                        showNotification("success", response.modal_message, "fa fa-check");
                        $('#add-stock-reservation-modal').modal('hide');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                }
            });
        });

        $('#edit-reservation-form').submit(function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.error) {
                        showNotification("danger", response.modal_message, "fa fa-info");
                    }else{
                        get_stock_reservation();
                        showNotification("success", response.modal_message, "fa fa-check");
                        $('#edit-stock-reservation-modal').modal('hide');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                }
            });
        });

        $('#cancel-reservation-form').submit(function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.error) {
                        showNotification("danger", response.modal_message, "fa fa-info");
                    }else{
                        get_stock_reservation();
                        showNotification("success", response.modal_message, "fa fa-check");
                        $('#cancel-stock-reservation-modal').modal('hide');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                }
            });
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

        get_stock_ledger(1);
        function get_stock_ledger(page){
            var item_code = '{{ $item_details->name }}';
            var erp_user = $('#erp-warehouse-user-filter').val();
            var erp_wh = $('#erp-warehouse-filter').val();
            var erp_d = $('#erp_dates').val();
            $.ajax({
                type: 'GET',
                url: '/get_stock_ledger/' + item_code + '?page=' + page + '&wh_user=' + erp_user + '&erp_wh=' + erp_wh + '&erp_d=' + erp_d,
                success: function(response){
                    $('#stock-ledger').html(response);
                }
            });
        }

        $('#erp_dates').on('change', function(e){ 
            get_stock_ledger();
        });

        $(document).on('select2:select', '#erp-warehouse-user-filter', function(e){
            get_stock_ledger();
        });

        $(document).on('select2:select', '#erp-warehouse-filter', function(e){
        	get_stock_ledger();
        });

        $(document).on('click', '#stock-ledger-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            get_stock_ledger(page);
        });

        @if (in_array($user_group, ['Manager', 'Director']))
        get_purchase_history();
        @endif

        function get_purchase_history(page){
            var item_code = '{{ $item_details->name }}';
            $.ajax({
                type: 'GET',
                url: '/purchase_rate_history/' + item_code + '?page=' + page,
                success: function(response){
                    $('#purchase-history-div').html(response);
                }
            });
        }

        $(document).on('click', '#purchase-history-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            get_purchase_history(page);
        });

        $("#ath_dates").daterangepicker({
            autoUpdateInput: false,
            placeholder: 'Select Date Range',
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MMM-DD',
                separator: " to "
            },
            startDate: moment().subtract(30, 'days'), endDate: moment(),
        });

        $("#ath_dates").on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD'));
            get_athena_transactions();
        });

        $("#ath_dates").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            get_athena_transactions();
        });

        $("#ath_dates").val('');
        $("#ath_dates").attr("placeholder","Select Date Range");

        $("#erp_dates").daterangepicker({
            autoUpdateInput: false,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MMM-DD',
                separator: " to "
            },
            startDate: moment().subtract(30, 'days'), endDate: moment(),
        });

        $("#erp_dates").on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD'));
            get_stock_ledger();
        });

        $("#erp_dates").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            get_stock_ledger();
        });

        $("#erp_dates").val('');
		$("#erp_dates").attr("placeholder","Select Date Range");

        $(document).on('click', '#athReset', function(){
            $('#ath-src-warehouse-filter').empty();
            $('#ath-to-warehouse-filter').empty();
            $('#warehouse-user-filter').empty();
            $('#ath_dates').val('');
            $("#ath_dates").attr("placeholder","Select Date Range");
            get_athena_transactions();
        });

        $('#erpReset').click(function(){
            $('#erp-warehouse-filter').empty();
            $('#erp-warehouse-user-filter').empty();
            $("#erp_dates").val('');
            $("#erp_dates").attr("placeholder","Select Date Range");
            get_stock_ledger();
        })

        $('#resetAll').click(function(){
            $('#ath-to-warehouse-filter').empty();
            $('#ath-src-warehouse-filter').empty();
            $('#warehouse-user-filter').empty();
            $('#erp-warehouse-filter').empty();
            $('#erp-warehouse-user-filter').empty();
            $("#erp_dates").val('');
            $("#erp_dates").attr("placeholder","Select Date Range");
            $("#ath_dates").val('');
            $("#ath_dates").attr("placeholder","Select Date Range");
        });

        $('#consignment-store-select').select2({
            dropdownCssClass: "myFont",
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

        $(document).on('change', '.csm-filter', function(e){
            load();
        });

        load();
        function load(page) {
            var item_code = '{{ $item_details->name }}';
            var branch_warehouse = $('.csm-filter').eq(0).val();
            var date_range = $('#consignment-date-range').val();
            var user = $('#consignment-user-select').val();

            $.ajax({
                type: "GET",
                url: "/consignment_stock_movement/" + item_code + "?page=" + page,
                data: {
                    branch_warehouse,
                    date_range: date_range,
                    user: user
                },
                success: function (response) {
                    $('#consignment-ledger-content').html(response);
                }
            });
        }

        $(document).on('click', '#consignment-stock-movement-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            load(page);
        });

        $(".date-range").daterangepicker({
            autoUpdateInput: false,
            placeholder: 'Select Date Range',
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MMM-DD',
                separator: " to "
            },
            startDate: moment().subtract(30, 'days'), endDate: moment(),
        });

        $(".date-range").on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MMM-DD') + ' to ' + picker.endDate.format('YYYY-MMM-DD'));
            load();
        });

        $(".date-range").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            load();
        });

        $(".date-range").val('');
        $(".date-range").attr("placeholder","Select Date Range");

        $('#consignment-user-select').select2({
            placeholder: "Select a user",
            ajax: {
                url: "/consignment_stock_movement/{{ $item_details->name }}?get_users=1",
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

        $(document).on('select2:select', '#consignment-user-select', function(e){
            load();
        });

        $(document).on('click', '#consignment-reset', function (){
            $('#consignment-user-select').empty().trigger('change');
            $('#consignment-date-range').val('');
            @if (Auth::user()->user_group == 'Consignment Supervisor')
                $(".csm-filter").empty().trigger('change');
            @endif
            @if (count($consignment_branches) > 1 && Auth::user()->user_group == 'Promodiser')
                $(".csm-filter").val($(".csm-filter option:first").val());
            @endif
            load();
        });
    </script>
@endsection