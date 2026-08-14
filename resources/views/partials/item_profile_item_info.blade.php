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

    $stockUom = $itemDetails->stock_uom ?? '';
    $sellableSiteWarehouses = collect($siteWarehouses ?? [])->reject(function ($row) {
        return \App\Constants\WarehouseConstants::isExcludedFromItemProfileStockSummary(
            $row['parent_warehouse'] ?? null,
            $row['warehouse'] ?? null
        );
    })->values();
    $stockAvailable = $sellableSiteWarehouses->sum('available_qty');
    $stockReserved = $sellableSiteWarehouses->sum('reserved_qty');
    $stockOnHand = $sellableSiteWarehouses->sum('actual_qty');
    $warehouseCount = $sellableSiteWarehouses->count();

    $lifecycleStatus = $lifecycleCurrentStatus ?? \App\Models\Item::LIFECYCLE_STATUS_ACTIVE;
    if ($lifecycleStatus === \App\Models\Item::LIFECYCLE_STATUS_ACTIVE) {
        $statusBadgeClass = 'success';
    } elseif ($lifecycleStatus === \App\Models\Item::LIFECYCLE_STATUS_PHASE_OUT) {
        $statusBadgeClass = 'warning';
    } else {
        $statusBadgeClass = 'secondary';
    }

    $stockTitle = $bundled ? 'Bundled Items' : 'Stock Information';
    $showDeptPricing = in_array($userDepartment, $allowedDepartment) && !in_array($userGroup, ['Manager', 'Director']);
    $showManagerPricing = in_array($userGroup, ['Manager', 'Director']);
@endphp
<div class="row">
    <div class="col-12 col-lg-10">
        <div class="d-md-none mb-2 col-12 px-0">
            <div class="dropdown show">
                <a class="btn btn-sm p-1 btn-secondary dropdown-toggle float-right" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 9pt;">
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
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#item-information-modal">
                            <i class="fa fa-edit pb-1"></i> Package Details
                        </a>
                    @endif
                    @if (in_array(Auth::user()->user_group, ['Director']))
                        <a class="dropdown-item" href="/item_form/{{ $itemDetails->name }}">
                            <i class="fa fa-info pb-1"></i> Update Attribute
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row align-items-start">
            <div class="col-md-3 col-lg-3 pl-2 pr-2 pb-2 pt-0">
                <div class="ip-images-card ip-card p-2">
                    <div class="row pb-1">
                        @if (count($itemImages) > 0)
                            @for($i = 0; $i <= 3; $i++)
                                @isset($itemImages[$i])
                                    @php
                                        $imageData = $itemImages[$i];
                                        $thumb = data_get($imageData, 'thumb', data_get($imageData, 'full'));
                                        $full = data_get($imageData, 'full');
                                        $alt = Illuminate\Support\Str::slug($itemBrochureDescription, '-');
                                    @endphp
                                    <div class="{{ $i == 0 ? 'col-12' : 'col-4 mt-2 p-2 border' }} {{ $i == 0 ? 'ip-main-image-wrapper' : null }}">
                                        <a href="{{ $full }}" class="view-images" data-item-code="{{ $itemDetails->name }}" data-idx="{{ $i }}">
                                            <picture>
                                                @if ($i === 0)
                                                    <img
                                                        src="{{ $thumb }}"
                                                        srcset="{{ $thumb }} 640w, {{ $full }} 1024w"
                                                        sizes="(min-width: 992px) 320px, 100vw"
                                                        alt="{{ $alt }}"
                                                        class="img-responsive hover ip-main-image blur-up"
                                                        width="640"
                                                        height="480"
                                                        decoding="async"
                                                        fetchpriority="high"
                                                        onload="this.classList.remove('blur-up')"
                                                    >
                                                @else
                                                    <img
                                                        src="{{ $thumb }}"
                                                        alt="{{ $alt }}"
                                                        class="img-responsive hover ip-thumb-image"
                                                        width="120"
                                                        height="75"
                                                        loading="lazy"
                                                        decoding="async"
                                                    >
                                                @endif
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
                            <div class="col-12 ip-main-image-wrapper">
                                <img
                                    src="{{ $noImg }}"
                                    alt="no-image"
                                    class="img-responsive hover ip-main-image"
                                    width="640"
                                    height="480"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-9 col-lg-9">
                <div class="row">
                    <div class="col-12 {{ ($showDeptPricing || $showManagerPricing) ? 'col-xl-5' : 'col-xl-8' }} mb-3">
                        <div class="ip-card h-100" id="item-summary-card">
                            <div class="ip-card-header d-flex align-items-center justify-content-between flex-wrap">
                                <h3 class="m-0 font-responsive">Item Summary</h3>
                                <span class="badge badge-{{ $statusBadgeClass }}">{{ $lifecycleStatus }}</span>
                            </div>
                            <div class="ip-card-body">
                                <span id="selected-item-code" class="d-none">{{ $itemDetails->name }}</span>
                                <div class="mb-2">
                                    <div class="responsive-item-code font-weight-bold" style="font-size: 14pt;">
                                        {{ $itemDetails->name.' '.$itemDetails->brand }}
                                        @if ($bundled)
                                            &nbsp;<span class="badge badge-info font-italic" style="font-size: 8pt;">Product Bundle</span>
                                        @endif
                                    </div>
                                    <div class="responsive-description text-justify mb-2" style="font-size: 11pt;">{!! $itemDetails->description !!}</div>
                                </div>
                                <div id="item-information-container"></div>
                                <div class="ip-meta-grid">
                                    <div class="ip-meta-item">
                                        <span class="ip-meta-label">Category</span>
                                        <span class="ip-meta-value">{{ $itemDetails->item_classification ?: '—' }}</span>
                                    </div>
                                    <div class="ip-meta-item">
                                        <span class="ip-meta-label">Brand</span>
                                        <span class="ip-meta-value">{{ $itemDetails->brand ?: '—' }}</span>
                                    </div>
                                    <div class="ip-meta-item">
                                        <span class="ip-meta-label">UOM</span>
                                        <span class="ip-meta-value">{{ $stockUom ?: '—' }}</span>
                                    </div>
                                    <div class="ip-meta-item">
                                        <span class="ip-meta-label">Item Group</span>
                                        <span class="ip-meta-value">{{ $itemDetails->item_group ?: '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ip-card-footer text-muted small">
                                <i class="fas fa-clock mr-1"></i>
                                {{ $lifecycleLastUpdatedLabel ?? 'Last updated' }}:
                                {{ $lifecycleLastUpdatedDetail ?? '—' }}
                            </div>
                        </div>
                    </div>

                    @if ($showDeptPricing || $showManagerPricing)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <div class="ip-card h-100">
                            <div class="ip-card-header">
                                <h3 class="m-0 font-responsive">Pricing &amp; Sales Information</h3>
                            </div>
                            <div class="ip-card-body">
                                @if ($showDeptPricing && $defaultPrice > 0)
                                    <div class="ip-price-section">
                                        <div class="ip-price-row">
                                            <span class="ip-price-label">Standard Selling Price ({{ $isTaxIncludedInRate ? 'Vat Inclusive' : 'Vat Exclusive' }})</span>
                                            <span class="ip-price-value">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                        </div>
                                    </div>
                                @endif

                                @if ($showManagerPricing)
                                    <div class="ip-price-section">
                                        @if ($defaultPrice > 0)
                                            <div class="ip-price-row">
                                                <span class="ip-price-label">Standard Selling Price (Vat Inclusive)</span>
                                                <span class="ip-price-value">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                            </div>
                                        @endif
                                        @if ($minimumSellingPrice > 0)
                                            <div class="ip-price-row">
                                                <span class="ip-price-label">Minimum Selling Price (Vat Inclusive)</span>
                                                <span class="ip-price-value">{{ '₱ ' . number_format($minimumSellingPrice, 2, '.', ',') }}</span>
                                            </div>
                                        @endif
                                        <div class="ip-price-row">
                                            <span class="ip-price-group-label">Average Selling Price (Vat Exclusive)</span>
                                        </div>
                                        <div class="ip-price-row ip-price-nested">
                                            <span class="ip-price-label">Last 6 Months</span>
                                            <span class="ip-price-value">
                                                @if (! empty($avgSellingPrice6m) && $avgSellingPrice6m > 0)
                                                    {{ '₱ ' . number_format($avgSellingPrice6m, 2, '.', ',') }}
                                                @else
                                                    —
                                                @endif
                                            </span>
                                        </div>
                                        <div class="ip-price-row ip-price-nested">
                                            <span class="ip-price-label">Year to Date (YTD)</span>
                                            <span class="ip-price-value">
                                                @if (! empty($avgSellingPriceYtd) && $avgSellingPriceYtd > 0)
                                                    {{ '₱ ' . number_format($avgSellingPriceYtd, 2, '.', ',') }}
                                                @else
                                                    —
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                @endif

                                <div class="ip-price-section ip-sold-chart">
                                    <div class="ip-price-row ip-sold-chart-header">
                                        <span class="ip-price-label">Average Sold Per Month (Last 6 Months)</span>
                                        <span class="ip-price-value">
                                            @if (! empty($itemDetails->stock_uom))
                                                <small class="text-muted">{{ $itemDetails->stock_uom }}</small>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="ip-sold-chart-wrap">
                                        <canvas id="ip-avg-sold-chart" height="140"></canvas>
                                    </div>
                                </div>

                                @if ($showManagerPricing)
                                    <div class="ip-price-section ip-price-section-last">
                                        @if ($lastPurchaseRate > 0)
                                            <div class="ip-price-row">
                                                <span class="ip-price-label">Last Purchase Rate</span>
                                                <span class="ip-price-value">
                                                    {{ '₱ ' . number_format($lastPurchaseRate, 2, '.', ',') }}
                                                    <small class="text-muted font-italic d-block">{{ $lastPurchaseDate }}</small>
                                                </span>
                                            </div>
                                        @endif
                                        @if ($avgPurchaseRate > 0)
                                            <div class="ip-price-row avg-purchase-rate-div">
                                                <span class="ip-price-label">Average Purchase Rate (Vat Exclusive)</span>
                                                <span class="ip-price-value">{{ $avgPurchaseRate }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="col-12 col-md-6 {{ ($showDeptPricing || $showManagerPricing) ? 'col-xl-3' : 'col-xl-4' }} mb-3">
                        <div class="ip-card h-100">
                            <div class="ip-card-header">
                                <h3 class="m-0 font-responsive">Key Dates</h3>
                            </div>
                            <div class="ip-card-body">
                                <div class="ip-date-row">
                                    <span class="ip-date-icon"><i class="fas fa-sync"></i></span>
                                    <div>
                                        <div class="ip-meta-label">Last Movement</div>
                                        <div class="ip-meta-value">{{ $lifecycleLastMovementLabel ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="ip-date-row">
                                    <span class="ip-date-icon"><i class="fas fa-shopping-cart"></i></span>
                                    <div>
                                        <div class="ip-meta-label">Last Order Date</div>
                                        <div class="ip-meta-value">
                                            {{ $lifecycleLastOrderLabel ?? '—' }}
                                            @if (! empty($lastOrderDate))
                                                <small class="text-muted d-block">{{ $lastOrderDate }}</small>
                                            @endif
                                            @if (! empty($lastOrderCustomer))
                                                <small class="text-muted d-block">from {{ $lastOrderCustomer }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="ip-date-row">
                                    <span class="ip-date-icon"><i class="fas fa-warehouse"></i></span>
                                    <div>
                                        <div class="ip-meta-label">Last Purchase</div>
                                        <div class="ip-meta-value">
                                            {{ $lifecycleLastPurchaseLabel ?? '—' }}
                                            @if (! empty($lastPurchaseSupplier))
                                                <small class="text-muted d-block">from {{ $lastPurchaseSupplier }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-none d-md-block d-lg-none col-12 mb-3 item-profile-actions-col">
                        <div class="item-profile-actions-buttons d-flex flex-wrap">
                            <div class="dropdown show flex-fill m-1">
                                <a class="ip-action-btn dropdown-toggle generate-brochure-dropdown w-100" href="#" role="button" id="dropdownMenuLinkTablet" data-toggle="{{ !$bundled ? 'dropdown' : null }}" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-print"></i>
                                    <span>Generate Brochure</span>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuLinkTablet" style="font-size: 9pt;">
                                    <a class="dropdown-item print-brochure-btn" href="#" data-item-code="{{ $itemDetails->name }}" data-item-name="{{ $itemBrochureName }}" data-item-description="{{ $itemBrochureDescription }}">Print Now</a>
                                    <a class="dropdown-item generate-multiple-brochure" href="#" data-item-code="{{ $itemDetails->name }}">Generate Multiple</a>
                                </div>
                            </div>
                            <a class="ip-action-btn flex-fill m-1 upload-item-image" data-item-code="{{ $itemDetails->name }}">
                                <i class="fas fa-camera"></i>
                                <span>Upload Image</span>
                            </a>
                            <a class="ip-action-btn flex-fill m-1 edit-warehouse-location-btn" data-item-code="{{ $itemDetails->name }}">
                                <i class="fas fa-warehouse"></i>
                                <span>Location</span>
                            </a>
                            @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                                <a class="ip-action-btn flex-fill m-1" data-toggle="modal" data-target="#item-information-modal">
                                    <i class="fa fa-edit"></i>
                                    <span>Package Details</span>
                                </a>
                            @endif
                            @if (in_array(Auth::user()->user_group, ['Director']))
                                <a class="ip-action-btn flex-fill m-1" href="/item_form/{{ $itemDetails->name }}">
                                    <i class="fa fa-info"></i>
                                    <span>Update Attribute</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ip-card mb-3">
            <div class="ip-card-header">
                <h3 class="m-0 font-responsive">
                    <i class="fa fa-box-open"></i> {!! $stockTitle !!}
                </h3>
            </div>
            <div class="ip-card-body">
                @if (!$bundled)
                    <div class="row mb-3">
                        <div class="col-6 col-md-3 mb-2">
                            <div class="ip-stock-widget ip-stock-available">
                                <div class="ip-stock-widget-label"><i class="fas fa-check-circle mr-1"></i> Available Qty</div>
                                <div class="ip-stock-widget-value">{{ number_format((float) $stockAvailable, 2, '.', ',') }}</div>
                                <div class="ip-stock-widget-uom">{{ $stockUom }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="ip-stock-widget ip-stock-reserved">
                                <div class="ip-stock-widget-label"><i class="fas fa-lock mr-1"></i> Reserved</div>
                                <div class="ip-stock-widget-value">{{ number_format((float) $stockReserved, 2, '.', ',') }}</div>
                                <div class="ip-stock-widget-uom">{{ $stockUom }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="ip-stock-widget ip-stock-onhand">
                                <div class="ip-stock-widget-label"><i class="fas fa-cubes mr-1"></i> Actual On Hand Qty</div>
                                <div class="ip-stock-widget-value">{{ number_format((float) $stockOnHand, 2, '.', ',') }}</div>
                                <div class="ip-stock-widget-uom">{{ $stockUom }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="ip-stock-widget ip-stock-warehouses">
                                <div class="ip-stock-widget-label"><i class="fas fa-warehouse mr-1"></i> Warehouses</div>
                                <div class="ip-stock-widget-value">{{ $warehouseCount }}</div>
                                <div class="ip-stock-widget-uom">locations</div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="item-stock-level-div overflow-auto">
                    @if ($bundled)
                        @include('item_stock_level_bundled', ['stocks' => $bundledStocks])
                    @else
                        @include('item_stock_level', ['consignmentWarehouses' => $consignmentWarehouses, 'siteWarehouses' => $sellableSiteWarehouses, 'itemDetails' => $itemDetails])
                    @endif
                </div>
            </div>
        </div>

        @if (!$bundled && count($coVariants) > 0)
            <div class="ip-card mb-3">
                <div class="ip-card-header">
                    <a class="d-flex align-items-center justify-content-between text-dark text-decoration-none collapsed" data-toggle="collapse" href="#ip-variants-collapse" role="button" aria-expanded="false" aria-controls="ip-variants-collapse">
                        <h3 class="m-0 font-responsive"><i class="fas fa-project-diagram"></i> Variants</h3>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse" id="ip-variants-collapse">
                    <div class="ip-card-body">
                        <div id="example" class="responsive-table-wrap overflow-auto">
                            <table class="table table-sm table-bordered table-striped variants-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="text-center align-middle" style="background-color: #CCD1D1;">Item Code</th>
                                        @foreach ($attributeNames as $attributeName)
                                            <th scope="col" class="text-center align-middle variants-th-attr">{{ $attributeName }}</th>
                                        @endforeach
                                        <th scope="col" class="text-center align-middle">Stock Availability</th>
                                        @if ($showDeptPricing)
                                            <th scope="col" class="text-center text-nowrap align-middle variants-th-price">Standard Price</th>
                                        @endif
                                        @if ($showManagerPricing)
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
                                            <span class="badge badge-{{ ($stockAvailable > 0) ? 'success' : 'secondary' }} font-responsive">{{ ($stockAvailable > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                        </td>
                                        @if ($showDeptPricing)
                                            <td class="text-center align-middle text-nowrap">
                                                @if ($defaultPrice > 0)
                                                    {{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}
                                                @else
                                                    --
                                                @endif
                                            </td>
                                        @endif
                                        @if ($showManagerPricing)
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
                                                if (Arr::exists($variantsPriceArr ?? [], $variant->name)) {
                                                    $price = $variantsPriceArr[$variant->name];
                                                }
                                            @endphp
                                            @if ($showDeptPricing)
                                                <td class="text-center align-middle text-nowrap">
                                                    @if ($price > 0)
                                                        {{ '₱ ' . number_format($price, 2, '.', ',') }}
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            @endif
                                            @if ($showManagerPricing)
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
                                                        if (Arr::exists($variantsMinPriceArr ?? [], $variant->name)) {
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
                        <div class="m-2">
                            {{ $coVariants->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="d-none d-lg-block col-lg-2 pr-2 pl-0 item-profile-actions-col">
        <div class="box box-solid h-100 item-profile-actions-box border-0 bg-transparent shadow-none">
            <div class="item-profile-actions-buttons px-2">
                @if (Auth::check() && in_array(Auth::user()->user_group, \App\Http\Middleware\EnsureInventoryLifecycleSettingsAccess::ALLOWED_USER_GROUPS, true))
                    <div
                        id="item-profile-lifecycle-status-app"
                        data-item-code="{{ $itemDetails->name }}"
                        data-item-name="{{ $itemDetails->item_name ?? $itemDetails->name }}"
                        data-item-tag="{{ $itemDetails->name }}"
                        data-current-stock="{{ (float) $stockAvailable }}"
                        data-last-movement="{{ $lifecycleLastMovementLabel ?? '—' }}"
                        data-last-purchase="{{ $lifecycleLastPurchaseLabel ?? '—' }}"
                        data-current-status="{{ $lifecycleCurrentStatus ?? \App\Models\Item::LIFECYCLE_STATUS_ACTIVE }}"
                        data-last-updated-label="{{ $lifecycleLastUpdatedLabel ?? 'Last updated' }}"
                        data-last-updated-detail="{{ $lifecycleLastUpdatedDetail ?? '—' }}"
                        data-status-options='@json(\App\Models\Item::LIFECYCLE_STATUSES)'
                    ></div>
                @endif
                <div class="dropdown show">
                    <a class="ip-action-btn dropdown-toggle generate-brochure-dropdown w-100" href="#" role="button" id="dropdownMenuLink" data-toggle="{{ !$bundled ? 'dropdown' : null }}" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-print"></i>
                        <span>Generate Brochure</span>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink" style="font-size: 9pt;">
                        <a class="dropdown-item print-brochure-btn" href="#" data-item-code="{{ $itemDetails->name }}" data-item-name="{{ $itemBrochureName }}" data-item-description="{{ $itemBrochureDescription }}">Print Now</a>
                        <a class="dropdown-item generate-multiple-brochure" href="#" data-item-code="{{ $itemDetails->name }}">Generate Multiple</a>
                    </div>
                </div>
                <a class="ip-action-btn upload-item-image" data-item-code="{{ $itemDetails->name }}">
                    <i class="fas fa-camera"></i>
                    <span>Upload Image</span>
                </a>
                <a class="ip-action-btn edit-warehouse-location-btn" data-item-code="{{ $itemDetails->name }}">
                    <i class="fas fa-warehouse"></i>
                    <span>Location</span>
                </a>
                @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
                    <a class="ip-action-btn" data-toggle="modal" data-target="#item-information-modal">
                        <i class="fa fa-edit"></i>
                        <span>Package Details</span>
                    </a>
                @endif
                @if (in_array(Auth::user()->user_group, ['Director']))
                    <a class="ip-action-btn" href="/item_form/{{ $itemDetails->name }}">
                        <i class="fa fa-info"></i>
                        <span>Update Attribute</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
