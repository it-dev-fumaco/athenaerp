@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
    <div class="container-fluid p-1 p-md-3">
        <div class="row">
            <div class="col-md-12">
                <div class="back-btn">
                    <img src="{{ Storage::disk('upcloud')->url('icon/back.png') }}" id="back-btn" class="w-100">
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
                        <a class="nav-link" id="get-stock-ledger" data-toggle="tab" href="#history">
                            <span class="d-none d-lg-block">ERP Submitted Transaction Histories</span>
                            <i class="fas fa-history d-block d-lg-none"></i>
                        </a>
                    </li>
                    @if(Auth::check() and in_array(Auth::user()->user_group, ['Inventory Manager', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link" id="get-stock-reservations" data-toggle="tab" href="#tab_4">
                            <span class="d-none d-lg-block">Stock Reservations</span>
                            <i class="fas fa-warehouse d-block d-lg-none"></i>
                        </a>
                    </li>
                    @endif
                    @if (in_array($userGroup, ['Manager', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link" id="get-purchase-history" data-toggle="tab" href="#purchase-history">
                            <span class="d-none d-lg-block">Purchase Rate History</span>
                            <i class="fa fa-shopping-cart d-block d-lg-none"></i>
                        </a>
                    </li>
                    @endif
                    @if(Auth::check() and in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Promodiser', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link" id="get-consignment-stock-movement" data-toggle="tab" href="#consignment-stock-movement">
                            <span class="d-none d-lg-block">Consignment Stock Movement</span>
                            <i class="fas fa-warehouse d-block d-lg-none" style="font-size: 8pt"></i>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" id="getProductFiles" data-toggle="tab" href="#tabProductFiles">
                            <span class="d-none d-lg-block">Product Files</span>
                            <i class="fas fa-folder-open d-block d-lg-none"></i>
                        </a>
                    </li>
                </ul>
                <div class="d-none">
                    <form action="/add_to_brochure_list" id="add-to-brochure-form" method="post">
                        @csrf
                        <input type="text" name="item_codes[]" value="{{ $itemDetails->name }}">
                    </form>
                </div>
                <div class="tab-content">
                    <div class="container-fluid tab-pane bg-white" id="consignment-stock-movement">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-12 col-md-3 p-2">
                                        @if(Auth::check() and in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
                                            <select class="form-control csm-filter" name="store" id="consignment-store-select"></select>
                                        @else
                                            @if (count($consignmentBranches) > 1)
                                            <select class="form-control csm-filter" name="store">
                                                @foreach ($consignmentBranches as $store)
                                                <option value="{{ $store }}">{{ $store }}</option>
                                                @endforeach
                                            </select>
                                            @endif
                                            @if ((count($consignmentBranches) == 1))
                                                <input type="hidden" class="csm-filter" name="store" value="{{ $consignmentBranches[0] }}">
                                            @endif
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-3 p-2">
                                        <input type="text" class="form-control date-range" id="consignment-date-range" name="date_range" style="height: 30px;"> 
                                    </div>
                                    <div class="col-12 col-md-3 p-2">
                                        <select name="user" id="consignment-user-select" class="form-control select2"></select>
                                    </div>
                                    <div class="col-12 col-md-3 p-2">
                                        <button class="btn btn-sm btn-secondary" id="consignment-reset">Reset Filters</button>
                                    </div>
                                    <div class="col-12 overflow-auto">
                                        <div id="item-profile-consignment-stock-movement" data-item-code="{{ $itemDetails->name }}"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="item-info" class="container-fluid tab-pane active bg-white">
                        <div class="row">
                            <div class="col-12 col-lg-10">
                                <div class="box box-solid mt-2">
                                    <div class="row">
                                        @php
                                            if (!$itemDetails->item_brochure_description) {
                                                $itemBrochureDescription = $itemDetails->description;
                                                $explodedDescription = explode(",", strip_tags($itemBrochureDescription));
                                                $itemBrochureDescription = (isset($explodedDescription[0]) ? $explodedDescription[0] : '') . (isset($explodedDescription[1]) ? ", " . $explodedDescription[1] : '');
                                            } else {
                                                $itemBrochureDescription = strip_tags($itemDetails->item_brochure_description);
                                            }

                                            if (!$itemDetails->item_brochure_name) {
                                                $itemBrochureName = $itemDetails->item_name;
                                                $explodedItemName = explode("-", strip_tags($itemBrochureName));
                                                $explodedItemName1 = (isset($explodedItemName[0]) ? $explodedItemName[0] : '');
                                                $explodedItemName2 = (isset($explodedItemName[1]) ? '-' . $explodedItemName[1] : '');
                                                $explodedItemName3 = (isset($explodedItemName[2]) ? '-' . $explodedItemName[2] : '');
                                                $explodedItemName4 = (isset($explodedItemName[3]) ? '-' . $explodedItemName[3] : '');
                                                $itemBrochureName = $explodedItemName1 . $explodedItemName2 . $explodedItemName3 . $explodedItemName4;
                                            } else {
                                                $itemBrochureName = strip_tags($itemDetails->item_brochure_name);
                                            }
                                        @endphp
                                        <div class="d-md-none mb-2 col-12">
                                            <div class="dropdown show">
                                                <a class="btn btn-sm p-1 btn-secondary dropdown-toggle float-right" href="#" role="button"  data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 9pt;">
                                                    Menu
                                                </a>
                                              
                                                <div class="dropdown-menu" style="font-size: 9pt;">
                                                    <a class="dropdown-item {{ !$bundled ? 'print-brochure-btn' : null }} generate-brochure-dropdown" href="#" data-item-code="{{ $itemDetails->name }}" data-item-name="{{ $itemBrochureName }}" data-item-description="{{ $itemBrochureDescription }}">
                                                        <i class="fas fa-print pb-1"></i> Print Brochure Now
                                                    </a>
                                                    <a class="dropdown-item {{ !$bundled ? 'generate-multiple-brochure' : null }} generate-brochure-dropdown" href="#" data-item-code="{{ $itemDetails->name }}">
                                                        <i class="fas fa-file-pdf pb-1"></i> Generate Multiple
                                                    </a>
                                                    <a class="dropdown-item upload-item-image" href="#" data-item-code="{{ $itemDetails->name }}">
                                                        <i class="fas fa-camera pb-1"></i> Upload Image
                                                    </a>
                                                    <a class="dropdown-item edit-warehouse-location-btn" href="#" data-item-code="{{ $itemDetails->name }}">
                                                        <i class="fas fa-warehouse pb-1"></i> Location
                                                    </a>
                                                    @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target='#item-information-modal'>
                                                            <i class="fa fa-edit pb-1"></i> Package Details
                                                        </a>
                                                    @endif
                                                    @if (in_array(Auth::user()->user_group, ['Director']))
                                                    <a class="dropdown-item" href="#" href="/item_form/{{ $itemDetails->name }}">
                                                        <i class="fa fa-info pb-1"></i> Update Attribute
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-lg-3 pl-2 pr-2 pb-2 pt-0">
                                            <div class="row pb-2" style="border-bottom: solid 3px #2E86C1">
                                                @if (count($itemImages) > 0)
                                                    @for($i = 0; $i <= 3; $i++)
                                                        @isset($itemImages[$i])
                                                            @php
                                                                $image = $itemImages[$i];
                                                                $alt = Illuminate\Support\Str::slug($itemBrochureDescription, '-');
                                                            @endphp
                                                            <div class="{{ $i == 0 ? 'col-12' : 'col-4 mt-2 p-2 border' }}" style="{{ $i > 0 ? 'height: 75px;' : null }}">
                                                                <a href="{{ $image }}" class="view-images" data-item-code="{{ $itemDetails->name }}" data-idx="{{ $i }}">
                                                                    <picture>
                                                                        <img src="{{ $image }}" alt="{{ $alt }}" class="img-responsive hover" style="width: 100%; height: 100%;">
                                                                    </picture>
                                                                    @if($i == 3 && count($itemImages) > 4)
                                                                        <div class="card-img-overlay text-center">
                                                                            <h5 class="card-title m-1 font-weight-bold" style="color: #fff; text-shadow: 2px 2px 8px #000;">MORE</h5>
                                                                        </div>
                                                                    @endif
                                                                </a>
                                                            </div>
                                                        @endisset
                                                    @endfor
                                                @else
                                                    <div class="col-12">
                                                        <img src="{{ $noImg }}" class="img-responsive hover" style="width: 100%; height: 100%;">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-9 col-lg-9">
                                            <div class="row">
                                                <div class="col-12 col-md-8 col-lg-12">
                                                    <span id="selected-item-code" class="d-none">{{ $itemDetails->name }}</span>
                                                    <dl class="ml-3">
                                                        <dt class="responsive-item-code" style="font-size: 14pt;">
                                                            {{ $itemDetails->name.' '.$itemDetails->brand }}
                                                            @if ($bundled)
                                                                &nbsp;<span class="badge badge-info font-italic" style="font-size: 8pt;">Product Bundle&nbsp;</span>
                                                            @endif
                                                        </dt>
                                                        <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $itemDetails->description !!}</dd>
                                                    </dl>
                                                    <div id="item-information-container"></div>
                                                </div>
                                                <div class="d-none d-md-block d-lg-none col-4 item-profile-actions-col px-2">
                                                    <div class="dropdown show">
                                                        <a class="btn btn-app m-2 d-block pb-5 dropdown-toggle generate-brochure-dropdown" href="#" role="button" id="dropdownMenuLink" data-toggle="{{ !$bundled ? 'dropdown' : null }}" aria-haspopup="true" aria-expanded="false" disabled="disabled">
                                                            <i class="fas fa-print pb-1"></i> Generate Brochure
                                                        </a>
                                                      
                                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink" style="font-size: 9pt;">
                                                            <a class="dropdown-item print-brochure-btn" href="#" data-item-code="{{ $itemDetails->name }}" data-item-name="{{ $itemBrochureName }}" data-item-description="{{ $itemBrochureDescription }}">Print Now</a>
                                                            <a class="dropdown-item generate-multiple-brochure" href="#" data-item-code="{{ $itemDetails->name }}">Generate Multiple</a>
                                                        </div>
                                                    </div>
                                                    <a class="btn btn-app m-2 d-block upload-item-image pb-5" data-item-code="{{ $itemDetails->name }}" style="font-size: 8pt !important">
                                                        <i class="fas fa-camera pb-1"></i> Upload Image
                                                    </a>
                                                    <a class="btn btn-app m-2 d-block edit-warehouse-location-btn pb-5" data-item-code="{{ $itemDetails->name }}" style="font-size: 8pt !important">
                                                        <i class="fas fa-warehouse pb-1"></i> Location
                                                    </a>
                                                    @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                                    <a class="btn btn-app m-2 d-block pb-5" data-toggle="modal" data-target='#item-information-modal' style="font-size: 8pt !important">
                                                        <i class="fa fa-edit pb-1"></i> Package Details
                                                    </a> 
                                                    @endif
                                                    @if (in_array(Auth::user()->user_group, ['Director']))
                                                    <a class="btn btn-app m-2 d-block pb-5"  href="/item_form/{{ $itemDetails->name }}" style="font-size: 8pt !important">
                                                        <i class="fa fa-info pb-1"></i> Update Attribute
                                                    </a> 
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-block ml-3">
                                                <p class="mt-2 mb-2 text-1center">
                                                    @if (in_array($userDepartment, $allowedDepartment) && !in_array($userGroup, ['Manager', 'Director']) && $defaultPrice > 0) 
                                                    <span class="d-block font-weight-bold mt-3" style="font-size: 17pt;">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                                    <span class="d-block responsive-description" style="font-size: 11pt;">Standard Selling Price</span>
                                                    @if ($isTaxIncludedInRate)
                                                    <small class="text-muted font-italic" style="font-size: 7.5pt;">* VAT inclusive</small>
                                                    @endif
                                                    @endif

                                                    @if (in_array($userGroup, ['Manager', 'Director']))
                                                        @if ($defaultPrice > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 17pt;">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                                        <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                                                        @if ($isTaxIncludedInRate)
                                                        <small class="text-muted font-italic" style="font-size: 7.5pt;">* VAT inclusive</small>
                                                        @endif
                                                        @endif
                                                        @if ($minimumSellingPrice > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 15pt;">{{ '₱ ' . number_format($minimumSellingPrice, 2, '.', ',') }}</span>
                                                        <span class="d-block" style="font-size: 9pt;">Minimum Selling Price</span>
                                                        @endif
                                                        @if ($lastPurchaseRate > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 11pt;">{{ '₱ ' . number_format($lastPurchaseRate, 2, '.', ',') }}</span>
                                                        <span class="d-inline-block" style="font-size: 9pt;">Last Purchase Rate</span>
                                                        <span class="d-inline-block font-weight-bold font-italic" style="font-size: 9pt;">- {{ $lastPurchaseDate }}</span>
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
                                                    <h3 class="card-title m-0 font-responsive">
                                                        @php
                                                            $stockTitle = $bundled ? 'Bundled Items' : 'Stock Level'
                                                        @endphp
                                                        <i class="fa fa-box-open"></i> {!! $stockTitle !!}
                                                    </h3>
                                                </div>
                                                <div class="box box-solid p-0 ml-3">
                                                    <div class="box-header with-border">
                                                        <div class="box-body item-stock-level-div">
                                                            <div class="container border p-4 d-flex justify-content-center align-items-center">
                                                                <div class="spinner-border" role="status">
                                                                    <span class="sr-only">Loading...</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 d-block d-lg-none">
                                            <div class="card-header border-bottom-0 p-1 ml-3">
                                                <h3 class="card-title m-0 font-responsive"><i class="fa fa-box-open"></i> {!! $stockTitle !!}</h3>
                                            </div>
                                            <div class="box box-solid p-0 ml-3 overflow-auto">
                                                <div class="box-header with-border">
                                                    <div class="box-body item-stock-level-div"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-none d-lg-block col-lg-2 pr-2 pl-0 item-profile-actions-col">
                                <div class="box box-solid h-100 item-profile-actions-box">
                                    <div class="item-profile-actions-buttons px-2">
                                        <div class="dropdown show">
                                            <a class="btn btn-app m-2 d-block pb-5 dropdown-toggle generate-brochure-dropdown" href="#" role="button" id="dropdownMenuLink" data-toggle="{{ !$bundled ? 'dropdown' : null }}" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-print pb-1"></i> Generate Brochure
                                            </a>
                                          
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink" style="font-size: 9pt;">
                                                <a class="dropdown-item print-brochure-btn" href="#" data-item-code="{{ $itemDetails->name }}" data-item-name="{{ $itemBrochureName }}" data-item-description="{{ $itemBrochureDescription }}">Print Now</a>
                                                <a class="dropdown-item generate-multiple-brochure" href="#" data-item-code="{{ $itemDetails->name }}">Generate Multiple</a>
                                            </div>
                                        </div>
                                        <a class="btn btn-app m-2 d-block upload-item-image pb-5" data-item-code="{{ $itemDetails->name }}">
                                            <i class="fas fa-camera pb-1"></i> Upload Image
                                        </a>
                                        <a class="btn btn-app m-2 d-block edit-warehouse-location-btn pb-5" data-item-code="{{ $itemDetails->name }}">
                                            <i class="fas fa-warehouse pb-1"></i> Location
                                        </a>
                                        @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                        <a class="btn btn-app m-2 d-block pb-5" data-toggle="modal" data-target='#item-information-modal'>
                                            <i class="fa fa-edit pb-1"></i> Package Details
                                        </a> 
                                        @endif
                                        @if (in_array(Auth::user()->user_group, ['Director']))
                                        <a class="btn btn-app m-2 d-block pb-5" href="/item_form/{{ $itemDetails->name }}">
                                            <i class="fa fa-info pb-1"></i> Update Attribute
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            @if (!$bundled)
                                @if (count($coVariants) > 0)
                                    <div class="col-12">
                                        <div class="card-header border-bottom-0">
                                            <h3 class="card-title font-responsive mt-5"><i class="fas fa-project-diagram"></i> Variants</h3>
                                        </div>
                                    </div>
                                    <div class="container col-12 mt-2">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="example" class="responsive-table-wrap overflow-auto">
                                                    <table class="table table-sm table-bordered table-striped variants-table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col" class="text-center align-middle" style="background-color: #CCD1D1;">Item Code</th>
                                                                @foreach ($attributeNames as $attributeName)
                                                                <th scope="col" class="text-center align-middle variants-th-attr">{{ $attributeName }}</th>
                                                                @endforeach
                                                                <th scope="col" class="text-center align-middle">Stock Availability</th>
                                                                @if (in_array($userDepartment, $allowedDepartment) && !in_array($userGroup, ['Manager', 'Director'])) 
                                                                <th scope="col" class="text-center text-nowrap align-middle variants-th-price">Standard Price</th>
                                                                @endif
                                                                @if (in_array($userGroup, ['Manager', 'Director']))
                                                                <th scope="col" class="text-center text-nowrap align-middle variants-th-price">Cost</th>
                                                                <th scope="col" class="text-center text-nowrap align-middle variants-th-price">Min. Selling Price</th>
                                                                <th scope="col" class="text-center text-nowrap align-middle variants-th-price">Standard Price</th>
                                                                @endif
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="highlight-row">
                                                                <th scope="row" class="text-center align-middle" style="background-color: #001F3F !important;">{{ $itemDetails->name }}</th>
                                                                @foreach ($attributeNames as $attributeName)
                                                                <td class="text-center align-middle">{{ data_get($itemAttributes, $attributeName) }}</td>
                                                                @endforeach
                                                                <td class="text-center align-middle text-nowrap variants-table">
                                                                    <span class="badge badge-{{ ($itemStockAvailable > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($itemStockAvailable > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                                                </td>
                                                                @if (in_array($userDepartment, $allowedDepartment) && !in_array($userGroup, ['Manager', 'Director'])) 
                                                                <td class="text-center align-middle text-nowrap">
                                                                    @if ($defaultPrice > 0)
                                                                    {{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}
                                                                    @else
                                                                    --
                                                                    @endif
                                                                </td>
                                                                @endif
                                                                @if (in_array($userGroup, ['Manager', 'Director']))
                                                                <td class="text-center align-middle text-nowrap">
                                                                    @if ($manualRate)
                                                                    <center>
                                                                        <span class="entered-price d-none">0.00</span>
                                                                        <form action="/update_item_price/{{ $itemDetails->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $itemDetails->name }}-computed-price">
                                                                            @csrf
                                                                            <div class="input-group input-group-price">
                                                                                <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" value="{{ $itemRate }}" required>
                                                                                <div class="input-group-append">
                                                                                    <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                                </div>
                                                                            </div>
                                                                        </form>
                                                                    </center>
                                                                    @else
                                                                    @if ($itemRate > 0)
                                                                        {{ '₱ ' . number_format($itemRate, 2, '.', ',') }}
                                                                    @else
                                                                    <center>
                                                                        <span class="entered-price d-none">0.00</span>
                                                                        <form action="/update_item_price/{{ $itemDetails->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $itemDetails->name }}-computed-price">
                                                                            @csrf
                                                                            <div class="input-group input-group-price">
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
                                                                    @if ($minimumSellingPrice > 0)
                                                                    <span id="{{ $itemDetails->name }}-computed-price-min">{{ '₱ ' . number_format($minimumSellingPrice, 2, '.', ',') }}</span>
                                                                    @else
                                                                    <span id="{{ $itemDetails->name }}-computed-price-min">--</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center align-middle text-nowrap">
                                                                    @if ($defaultPrice > 0)
                                                                    <span id="{{ $itemDetails->name }}-computed-price">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                                                    @else
                                                                    <span id="{{ $itemDetails->name }}-computed-price">--</span>
                                                                    @endif
                                                                </td>
                                                                @endif
                                                            </tr>
                                                            @foreach ($coVariants as $variant)
                                                            <tr class="variants-table">
                                                                <td class="text-center align-middle font-weight-bold text-dark" style="background-color: #CCD1D1;">
                                                                    <a href="/get_item_details/{{ $variant->name }}">{{ $variant->name }}</a>
                                                                </td>
                                                                @foreach ($attributeNames as $attributeName)
                                                                @php
                                                                    $attrVal = data_get($attributes, "{$variant->name}.{$attributeName}");
                                                                @endphp
                                                                <td class="text-center align-middle p-2">{{ $attrVal }}</td>
                                                                @endforeach
                                                                @php
                                                                    $availStock = data_get($actualVariantStocks, $variant->name, 0);
                                                                @endphp
                                                                <td class="text-center align-middle text-nowrap variants-table">
                                                                    <span class="badge badge-{{ ($availStock > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($availStock > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                                                </td>
                                                                @php
                                                                    $price = 0;
                                                                    if(Arr::exists($variantsPriceArr ?? [], $variant->name)){
                                                                        $price = $variantsPriceArr[$variant->name];
                                                                    }
                                                                @endphp
                                                                @if (in_array($userDepartment, $allowedDepartment) && !in_array($userGroup, ['Manager', 'Director'])) 
                                                                <td class="text-center align-middle text-nowrap">
                                                                    @if ($price > 0)
                                                                    {{ '₱ ' . number_format($price, 2, '.', ',') }}
                                                                    @else
                                                                    --
                                                                    @endif
                                                                </td>
                                                                @endif
                                                                @if (in_array($userGroup, ['Manager', 'Director']))
                                                                <td class="text-center align-middle text-nowrap">
                                                                    @php
                                                                        $cost = data_get($variantsCostArr, $variant->name, 0);
                                                                        $isManual = data_get($manualPriceInput, $variant->name, 0);
                                                                    @endphp
                                                                    @if ($isManual)
                                                                    <center>
                                                                        <span class="entered-price d-none">0.00</span>
                                                                        <form action="/update_item_price/{{ $variant->name }}" method="POST" autocomplete="off" class="update-price-form" data-id="{{ $variant->name }}-computed-price">
                                                                            @csrf
                                                                            <div class="input-group input-group-price">
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
                                                                            <div class="input-group input-group-price">
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
                                                                        if(Arr::exists($variantsMinPriceArr ?? [], $variant->name)){
                                                                            $minprice = $variantsMinPriceArr[$variant->name];
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
                                            {{ $coVariants->links('pagination::bootstrap-4') }}
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-12 item-alternatives-section min-width-0">
                                    <div class="card-header border-bottom-0">
                                        <h3 class="card-title font-responsive mb-3 mt-5"><i class="fas fa-filter"></i> Item Alternatives</h3>
                                    </div>
                                    <div class="item-alternatives-scroll">
                                        <div class="d-flex flex-row flex-nowrap">
                                        @forelse($itemAlternatives as $a)
                                            <div class="custom-body m-1">
                                                <div class="card card-default">
                                                    <div class="card-body p-0">
                                                        <div class="col-12">
                                                            <div class="d-flex flex-row">
                                                                <div class="pt-2 pb-2 pr-1 pl-1">
                                                                    <a href="{{ $a['item_alternative_image'] }}" data-toggle="lightbox" data-gallery="{{ $a['item_code'] }}" data-title="{{ $a['item_code'] }}">
                                                                        <img src="{{ $a['item_alternative_image'] }}" class="rounded" width="80" height="80">
                                                                    </a>
                                                                </div>
                                                                <a href="/get_item_details/{{ $a['item_code'] }}" class="text-dark" style="font-size: 9pt;">
                                                                    <div class="p-1 text-justify">
                                                                        <span class="font-weight-bold font-responsive">{{ $a['item_code'] }}</span>
                                                                        <small class="font-italic font-responsive item-alternative-description" style="font-size: 9pt;">{!! $a['description'] !!}</small>
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
                            @endif
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
						<div id="item-profile-athena-transactions" class="col-12 overflow-auto" data-item-code="{{ $itemDetails->name }}"></div>
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
                        <div id="item-profile-stock-ledger" class="col-12 overflow-auto" data-item-code="{{ $itemDetails->name }}"></div>
                    </div>
                    @if (in_array($userGroup, ['Manager', 'Director']))
                    <div id="purchase-history" class="container-fluid tab-pane bg-white overflow-auto">
                        <div id="item-profile-purchase-history" class="p-3 col-12" data-item-code="{{ $itemDetails->name }}"></div>
                    </div>
                    @endif
                    <div class="container-fluid tab-pane bg-white" id="tab_4">
                        <div class="row">
                            <div class="col-md-12">
                                @php
                                    $attr = null;
                                    if(Auth::check()){
                                        $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager', 'Director'])) ? 'disabled' : '';
                                    }
                                @endphp
                                <div class="float-right m-2">
                                    <button class="btn btn-primary font-responsive btn-sm" id="add-stock-reservation-btn" {{ $attr }}>New Stock Reservation</button>
                                </div>
                                <div
                                    id="item-profile-stock-reservation"
                                    class="box-body table-responsive no-padding font-responsive"
                                    data-item-code="{{ $itemDetails->name }}"
                                ></div>
                            </div>
                        </div>
                    </div>


                       <div class="container-fluid tab-pane bg-white" id="tabProductFiles">

                       <div class="row border p-2">
                        <div class="col-12 p-3">
                            <h5 class="font-weight-bolder">Product / Item Files
                                <span class="text-muted font-weight-normal">| Manage item files across different categories</span>
                            </h5>
                        </div>
                        @if (in_array($user_group, ['Inventory Manager', 'Director']) || in_array(Auth::user()->department, ['Information Technology', 'Engineering']))
                            <div class="col-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title font-weight-bolder">Supplier Brochure
                                            <small class="d-block text-muted text-xs mt-1">Upload supplier brochure documents (PDF, DOCX, or images)</small>
                                        </h5>

                                        <div class="card-tools">
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $item_details->name }}" data-file-type="Supplier Brochure"><i class="fas fa-upload"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0" id="supplier-brochure-files-div">
                                    </div>
                                </div>
                            </div>
                            @endif
                            

                                 <div class="col-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title font-weight-bolder">Photometric Data
                                         <small class="d-block text-muted text-xs mt-1">Upload hotometric data and technical specifications</small>

                                        </h5>
                                        <div class="card-tools">
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $item_details->name }}" data-file-type="Photometric Data"><i class="fas fa-upload"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0" id="photometric-data-files-div">
                                    </div>
                                </div>
                            </div>

                                 <div class="col-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title font-weight-bolder">IES Files
                                        <small class="d-block text-muted text-xs mt-1">Upload IES (Illuminating Engineering Society) lighting files</small>
                                        </h5>
                                        <div class="card-tools">
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $item_details->name }}" data-file-type="IES Files"><i class="fas fa-upload"></i></button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0" id="ies-files-div">
                                    </div>
                                </div>
                            </div>
                       </div>
                    </div>



                </div>
            </div>
        </div>
    </div>

     <div class="modal fade" id="deleteFileModal" tabindex="-1" role="dialog" aria-labelledby="deleteFileModalLabel" aria-hidden="true">
        <form action="/delete_item_file" method="POST" id="delete-file-form">
            @csrf
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h6 class="modal-title" id="deleteFileModalLabel">Delete File</h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="file_name" id="deleteFileNameInput">
                        <input type="hidden" name="file_id" id="fileIdInput">
                        <p class="text-center">Delete file <span id="delete-file-name" class="font-weight-bold"></span> of this item?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    	@include('modals.fileListModal')


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
            min-width: 0;
            max-width: 406px;
        }
        .item-alternative-description {
            max-height: 5.5em;
            overflow-y: auto;
            display: block;
        }
        .item-alternative-description p { margin-bottom: 0.25em; }
        .variants-th-attr { min-width: 100px; }
        .variants-th-price { min-width: 90px; }
        .input-group-price { width: 120px; max-width: 100%; }

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
            width: 45px;
            cursor: pointer
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
        @media (max-width: 479.98px) {
            #example tr > *:first-child {
                min-width: 5rem;
            }
            .back-btn{
                position: absolute;
                margin-right: 8px;
                top: 0;
                width: 25px;
            }
            i{
                font-size: 9pt;
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
        @media (max-width: 575.98px) {
            #example tr > *:first-child {
                min-width: 5rem;
            }
            .back-btn{
                position: absolute;
                top: 3px;
                width: 25px;
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
            .custom-body {
                max-width: 100%;
            }
            .back-btn{
                right: 0;
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

        .margin-top-250px{
            margin-top: 250px
        }
    </style>
@endsection
@section('script')
    <script>
        const bundled = parseInt('{{ $bundled ? 1 : 0 }}')
        get_item_stock_levels('{{ $itemDetails->name }}');
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

        function loadFiles(){
            get_item_files('{{ isset($item_details) ? $item_details->name : "" }}', 'Supplier Brochure', 'supplier-brochure-files-div');
            get_item_files('{{ isset($item_details) ? $item_details->name : "" }}', 'Photometric Data', 'photometric-data-files-div');
            get_item_files('{{ isset($item_details) ? $item_details->name : "" }}', 'IES Files', 'ies-files-div');
        }

        loadFiles()

        function get_item_files(item_code, file_type, container_id){ 
            $.ajax({
                type: 'GET',
                url: '/get_item_files/' + item_code + '/' + file_type,
                success: function (response) {
                    $('#' + container_id).html(response);
                }
            });
        }

        $(document).on('click', '.delete-file-btn', function(e){
            e.preventDefault();

            var file_name = $(this).data('file-name');
            var file_id = $(this).data('id');

            $('#delete-file-name').text(file_name);

            $('#deleteFileNameInput').val(file_name);
            $('#fileIdInput').val(file_id);

            $('#deleteFileModal').modal('show');
        });

        $(document).on('submit', '#delete-file-form', function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.status) {
                        showNotification("success", response.message, "fa fa-check");
                        $('#deleteFileModal').modal('hide');
                        loadFiles();
                    } else {
                        showNotification("danger", response.message, "fa fa-info");
                    }
                }
            });
        });

             $(document).on('submit', '#fileListModal form', function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: new FormData(this),
                cache: false,
                contentType: false,
                processData: false,
                success: function(response){
                    if (response.status) {
                        showNotification("success", response.message, "fa fa-check");
                        $('#fileListModal').modal('hide');
                        loadFiles();
                    } else {
                        showNotification("danger", response.message, "fa fa-info");
                    }
                }
            });
        });

        $(document).on('click', '.generate-brochure-dropdown', function(e){
            if(bundled){
                e.preventDefault()

                showNotification('danger', 'Generating a brochure is not allowed for product bundles.')
            }
        }) 

        function get_item_stock_levels(item_code) {
            $.ajax({
                type: 'GET',
                url: '/get_item_stock_levels/{{ $bundled ? "bundled/" : null }}' + item_code,
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
                url: '/get_item_details/{{ $itemDetails->name }}',
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

        $(document).on('click', '#get-athena-transactions', function (e){
            document.dispatchEvent(new CustomEvent('item-profile-athena-transactions-refresh'));
        })

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

        $(document).on('click', '#get-stock-ledger', function (e){
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        })

        $('#erp_dates').on('change', function(e){ 
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        });

        $(document).on('select2:select', '#erp-warehouse-user-filter', function(e){
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        });

        $(document).on('select2:select', '#erp-warehouse-filter', function(e){
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        });

        @if (in_array($userGroup, ['Manager', 'Director']))
            $(document).on('click', '#get-purchase-history', function (e){
                document.dispatchEvent(new CustomEvent('item-profile-purchase-history-refresh'));
            })
        @endif

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
            document.dispatchEvent(new CustomEvent('item-profile-athena-transactions-refresh'));
        });

        $("#ath_dates").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            document.dispatchEvent(new CustomEvent('item-profile-athena-transactions-refresh'));
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
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        });

        $("#erp_dates").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
        });

        $("#erp_dates").val('');
		$("#erp_dates").attr("placeholder","Select Date Range");

        $(document).on('click', '#athReset', function(){
            $('#ath-src-warehouse-filter').empty();
            $('#ath-to-warehouse-filter').empty();
            $('#warehouse-user-filter').empty();
            $('#ath_dates').val('');
            $("#ath_dates").attr("placeholder","Select Date Range");
            document.dispatchEvent(new CustomEvent('item-profile-athena-transactions-refresh'));
        });

        $('#erpReset').click(function(){
            $('#erp-warehouse-filter').empty();
            $('#erp-warehouse-user-filter').empty();
            $("#erp_dates").val('');
            $("#erp_dates").attr("placeholder","Select Date Range");
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh'));
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
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
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
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
        });

        $(".date-range").on('cancel.daterangepicker', function (ev, picker) {
            $(this).val('');
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
        });

        $(".date-range").val('');
        $(".date-range").attr("placeholder","Select Date Range");

        $('#consignment-user-select').select2({
            placeholder: "Select a user",
            ajax: {
                url: "/consignment_stock_movement/{{ $itemDetails->name }}?get_users=1",
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
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
        });

        $(document).on('click', '#get-consignment-stock-movement', function (e){
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
        })

        $(document).on('click', '#consignment-reset', function (){
            $('#consignment-user-select').empty().trigger('change');
            $('#consignment-date-range').val('');
            @if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
                $(".csm-filter").empty().trigger('change');
            @endif
            @if (count($consignmentBranches) > 1 && Auth::user()->user_group == 'Promodiser')
                $(".csm-filter").val($(".csm-filter option:first").val());
            @endif
            document.dispatchEvent(new CustomEvent('item-profile-consignment-stock-movement-refresh'));
        });
    </script>
@endsection