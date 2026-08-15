@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
    <style>
        /* Reserve space for images to reduce CLS and improve LCP */
        .ip-back-btn-img {
            max-width: 40px;
            height: auto;
        }

        .ip-main-image-wrapper {
            aspect-ratio: 4 / 3;
            max-height: 320px;
        }

        .ip-main-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .ip-thumb-image {
            width: 100%;
            height: 75px;
            object-fit: cover;
        }

        .ip-image-cell {
            position: relative;
        }

        .ip-default-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 2;
            background: #2563eb;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            pointer-events: none;
        }

        .ip-set-default-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            z-index: 2;
            border: 0;
            background: rgba(17, 24, 39, 0.75);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.45rem;
            border-radius: 4px;
            line-height: 1.2;
        }

        .ip-set-default-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        /* Simple blur-up effect for main image */
        .blur-up {
            filter: blur(12px);
            transform: scale(1.03);
            transition: filter 0.4s ease-out, transform 0.4s ease-out;
        }

        .ip-tab-chrome {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
            padding: 0 0.25rem;
            margin-bottom: 0.75rem;
        }

        .ip-tab-chrome .back-btn {
            position: relative;
            right: auto;
            top: auto;
            width: 40px;
            margin-left: 0.5rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ip-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .ip-card-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .ip-card-header h3 {
            font-size: 11pt;
            font-weight: 700;
            color: #1f2937;
        }

        .ip-card-body {
            padding: 0.85rem 1rem;
        }

        .ip-card-footer {
            padding: 0.6rem 1rem;
            border-top: 1px solid #eef2f7;
            background: #fafbfc;
            border-radius: 0 0 8px 8px;
        }

        .ip-images-card {
            border-color: #e8edf2;
        }

        .ip-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem 1rem;
            margin-top: 0.75rem;
        }

        .ip-meta-label {
            display: block;
            font-size: 8.5pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .ip-meta-value {
            display: block;
            font-size: 10pt;
            font-weight: 600;
            color: #111827;
        }

        .ip-price-row,
        .ip-date-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.45rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .ip-price-row:last-child,
        .ip-date-row:last-child {
            border-bottom: 0;
        }

        .ip-price-section {
            padding-bottom: 0.35rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #eef2f7;
        }

        .ip-price-section:last-child,
        .ip-price-section-last {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }

        .ip-price-section .ip-price-row {
            border-bottom: 0;
        }

        .ip-price-group-label {
            font-size: 9pt;
            font-weight: 600;
            color: #4b5563;
        }

        .ip-price-row.ip-price-nested {
            padding-left: 1rem;
        }

        .ip-price-label {
            font-size: 9pt;
            color: #4b5563;
        }

        .ip-price-value {
            font-size: 11pt;
            font-weight: 700;
            color: #111827;
            text-align: right;
        }

        .ip-date-row {
            justify-content: flex-start;
        }

        .ip-date-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #f3f4f6;
            color: #374151;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ip-sold-chart {
            padding-top: 0.15rem;
        }

        .ip-sold-chart-header {
            border-bottom: 0;
            padding-bottom: 0.2rem;
        }

        .ip-sold-chart-wrap {
            position: relative;
            height: 150px;
            margin-top: 0.25rem;
        }

        .ip-stock-widget {
            border-radius: 8px;
            padding: 0.75rem 0.85rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            min-height: 88px;
        }

        .ip-stock-widget-label {
            font-size: 8.5pt;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .ip-stock-widget-value {
            font-size: 14pt;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .ip-stock-widget-uom {
            font-size: 8pt;
            color: #9ca3af;
        }

        .ip-stock-available {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .ip-stock-reserved {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .ip-stock-onhand {
            background: #f3f4f6;
            border-color: #e5e7eb;
        }

        .ip-stock-warehouses {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .ip-stock-table thead th {
            background: #f8fafc;
        }

        .ip-stock-table .badge-success,
        .ip-available-badge.badge-success {
            background-color: #10b981 !important;
        }

        #ip-variants-collapse.show + .ip-card-header .fa-chevron-down,
        a[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        a[data-toggle="collapse"] .fa-chevron-down {
            transition: transform 0.2s ease;
        }
    </style>
    <div class="container-fluid p-1 p-md-3">
        <div class="row">
            <div class="col-md-12">
                <div class="ip-tab-chrome d-flex align-items-center flex-wrap">
                    <ul class="nav nav-tabs flex-grow-1 mb-0" id="ip-navs" role="tablist">
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
                            <a class="nav-link" id="get-order-history" data-toggle="tab" href="#order-history">
                                <span class="d-none d-lg-block">Order History</span>
                                <i class="fas fa-file-invoice d-block d-lg-none"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="getProductFiles" data-toggle="tab" href="#tabProductFiles">
                                <span class="d-none d-lg-block">Product Files</span>
                                <i class="fas fa-folder-open d-block d-lg-none"></i>
                            </a>
                        </li>
                    </ul>
                    <div class="back-btn ml-auto">
                        <img
                            src="{{ Storage::disk('upcloud')->url('/icon/arrow.png') }}"
                            id="back-btn"
                            class="ip-back-btn-img"
                            width="40"
                            height="40"
                            loading="lazy"
                            decoding="async"
                            alt="Back"
                        >
                    </div>
                </div>
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
                        @include('partials.item_profile_item_info')
                        @if (!$bundled)
                        <div class="row">
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
                                                                        <img
                                                                            src="{{ $a['item_alternative_image'] }}"
                                                                            class="rounded"
                                                                            width="80"
                                                                            height="80"
                                                                            loading="lazy"
                                                                            decoding="async"
                                                                        >
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
                        </div>
                        @endif
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
                    <div id="order-history" class="container-fluid tab-pane bg-white overflow-auto">
                        <div id="item-profile-order-history" data-item-code="{{ $itemDetails->name }}"></div>
                    </div>
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
                        @if (in_array($userGroup, ['Inventory Manager', 'Director']) || in_array(Auth::user()->department, ['Information Technology', 'Engineering']))
                            <div class="col-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title font-weight-bolder">Supplier Brochure
                                            <small class="d-block text-muted text-xs mt-1">Upload supplier brochure documents (PDF, DOCX, or images)</small>
                                        </h5>

                                        <div class="card-tools">
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $itemDetails->name }}" data-file-type="Supplier Brochure"><i class="fas fa-upload"></i></button>
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
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $itemDetails->name }}" data-file-type="Photometric Data"><i class="fas fa-upload"></i></button>
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
                                            <button class="btn btn-sm btn-primary upload-files-btn" data-item-code="{{ $itemDetails->name }}" data-file-type="IES Files"><i class="fas fa-upload"></i></button>
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
        .ip-tab-chrome #ip-navs {
            border-bottom: 0;
        }
        #ip-navs .nav-link {
            padding: 12px 16px;
            color: #6b7280;
            text-decoration: none;
            border: 0;
            border-bottom: 3px solid transparent;
            background: transparent;
            font-size: 10pt;
        }
        #ip-navs .nav-link:hover {
            color: #1f2937;
            border-color: transparent;
        }
        #ip-navs .nav-item .active {
            color: #0f2744 !important;
            font-weight: 700 !important;
            border-bottom: 3px solid #0f2744 !important;
            background: transparent !important;
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
            position: relative;
            right: auto;
            top: auto;
            width: 40px;
            cursor: pointer;
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
                position: relative;
                margin-right: 0;
                top: auto;
                width: 28px;
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
                position: relative;
                top: auto;
                width: 28px;
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
                position: relative;
                right: auto;
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
                position: relative;
                right: auto;
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
                position: relative;
                right: auto;
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

        // Defer initial data fetches slightly so first paint is not competing with AJAX work.
        document.addEventListener('DOMContentLoaded', function () {
            var startDataFetch = function () {
                load_item_information();
            };
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(startDataFetch, { timeout: 1000 });
            } else {
                setTimeout(startDataFetch, 0);
            }

            var soldChartEl = document.getElementById('ip-avg-sold-chart');
            var soldChartData = @json($soldPerMonthChart ?? ['labels' => [], 'values' => []]);
            if (soldChartEl && typeof Chart !== 'undefined' && soldChartData.labels && soldChartData.labels.length) {
                var formatSoldQty = function (value) {
                    var n = Number(value);
                    if (!isFinite(n)) {
                        return '0';
                    }
                    return Math.round(n * 100) / 100 === Math.round(n) ? String(Math.round(n)) : n.toFixed(2);
                };
                new Chart(soldChartEl.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: soldChartData.labels,
                        datasets: [{
                            data: soldChartData.values,
                            backgroundColor: '#3b82f6',
                            hoverBackgroundColor: '#2563eb',
                            barPercentage: 0.65,
                            categoryPercentage: 0.8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: { display: false },
                        tooltips: {
                            callbacks: {
                                label: function (tooltipItem) {
                                    return formatSoldQty(tooltipItem.yLabel);
                                }
                            }
                        },
                        layout: { padding: { top: 18 } },
                        scales: {
                            xAxes: [{
                                gridLines: { display: false },
                                ticks: { fontSize: 10, fontColor: '#6b7280', maxRotation: 0, minRotation: 0 }
                            }],
                            yAxes: [{
                                display: false,
                                ticks: { beginAtZero: true }
                            }]
                        }
                    },
                    plugins: [{
                        afterDatasetsDraw: function (chart) {
                            var ctx = chart.ctx;
                            chart.data.datasets.forEach(function (dataset, i) {
                                var meta = chart.getDatasetMeta(i);
                                if (meta.hidden) {
                                    return;
                                }
                                meta.data.forEach(function (bar, index) {
                                    var value = dataset.data[index];
                                    ctx.fillStyle = '#374151';
                                    ctx.font = '600 10px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(formatSoldQty(value), bar._model.x, bar._model.y - 4);
                                });
                            });
                        }
                    }]
                });
            }
        });
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
            get_item_files('{{ isset($itemDetails) ? $itemDetails->name : "" }}', 'Supplier Brochure', 'supplier-brochure-files-div');
            get_item_files('{{ isset($itemDetails) ? $itemDetails->name : "" }}', 'Photometric Data', 'photometric-data-files-div');
            get_item_files('{{ isset($itemDetails) ? $itemDetails->name : "" }}', 'IES Files', 'ies-files-div');
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
            var path = '/get_item_stock_levels/{{ $bundled ? "bundled/" : null }}' + encodeURIComponent(item_code);
            fetch(path, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.text();
            }).then(function (html) {
                document.querySelectorAll('.item-stock-level-div').forEach(function (el) {
                    el.innerHTML = html;
                });
            }).catch(function () {
                showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
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
        
        function load_item_information(){
            fetch('/get_item_details/' + encodeURIComponent('{{ $itemDetails->name }}'), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.text();
            }).then(function (html) {
                var el = document.getElementById('item-information-container');
                if (el) el.innerHTML = html;
            }).catch(function () {
                showNotification("danger", 'Error in getting product information.', "fa fa-info");
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
            var data = e.params.data;
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh', { detail: { wh_user: data && data.id ? data.id : '' } }));
        });

        $(document).on('select2:select', '#erp-warehouse-filter', function(e){
            var data = e.params.data;
            document.dispatchEvent(new CustomEvent('item-profile-stock-ledger-refresh', { detail: { erp_wh: data && data.id ? data.id : '' } }));
        });

        @if (in_array($userGroup, ['Manager', 'Director']))
            $(document).on('click', '#get-purchase-history', function (e){
                document.dispatchEvent(new CustomEvent('item-profile-purchase-history-refresh'));
            })
        @endif

        $(document).on('click', '#get-order-history', function () {
            document.dispatchEvent(new CustomEvent('item-profile-order-history-refresh'));
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