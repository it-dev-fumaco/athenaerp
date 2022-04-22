@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
    <div class="container-fluid p-3">
        <div class="row">
            <div class="col-md-12">
                <div style="position: absolute; right: 8px; top: -10px;">
                    <img src="{{ asset('storage/icon/back.png') }}" style="width: 45px; cursor: pointer;" id="back-btn">
                </div>
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#item-info">
                            <span class="d-none d-md-block">Item Info</span>
                            <i class="fas fa-info d-block d-md-none"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="get-athena-transactions" data-toggle="tab" href="#athena-logs">
                            <span class="d-none d-md-block">Athena Transactions</span>
                            <i class="fas fa-boxes d-block d-md-none"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#history">
                            <span class="d-none d-md-block">ERP Submitted Transaction Histories</span>
                            <i class="fas fa-history d-block d-md-none"></i>
                        </a>
                    </li>
                    @if (in_array($user_group, ['Manager', 'Director']))
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#purchase-history">Purchase Rate History</a>
                    </li>
                    @endif
                </ul>
                <div class="tab-content">
                    <div id="item-info" class="container-fluid tab-pane active bg-white">
                        <div class="row">
                            <div class="col-12 col-lg-8">
                                <div class="box box-solid mt-2">
                                    <div class="row">
                                        @php
                                            $img_1 = (array_key_exists(0, $item_images)) ? '/img/' . $item_images[0] : '/icon/no_img.png';
                                            $img_1_webp = (array_key_exists(0, $item_images)) ? '/img/' . explode('.', $item_images[0])[0].'.webp' : '/icon/no_img.webp';
                                            $img_1_alt = (array_key_exists(0, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_1)[0], '-') : null;
                                            
                                            $img_2 = (array_key_exists(1, $item_images)) ? '/img/' . $item_images[1] : '/icon/no_img.png';
                                            $img_2_webp = (array_key_exists(1, $item_images)) ? '/img/' . explode('.', $item_images[1])[0].'.webp' : '/icon/no_img.webp';
                                            $img_2_alt = (array_key_exists(1, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_2)[0], '-') : null;
                                            
                                            $img_3 = (array_key_exists(2, $item_images)) ? '/img/' . $item_images[2] : '/icon/no_img.png';
                                            $img_3_webp = (array_key_exists(2, $item_images)) ? '/img/' . explode('.', $item_images[2])[0].'.webp' : '/icon/no_img.webp';
                                            $img_3_alt = (array_key_exists(2, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_3)[0], '-') : null;
                                            
                                            $img_4 = (array_key_exists(3, $item_images)) ? '/img/' . $item_images[3] : '/icon/no_img.png';
                                            $img_4_webp = (array_key_exists(3, $item_images)) ? '/img/' . explode('.', $item_images[3])[0].'.webp' : '/icon/no_img.webp';
                                            $img_4_alt = (array_key_exists(3, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_4)[0], '-') : null;
                                        @endphp
                                        <div class="col-md-3">
                                            <div class="row">
                                                <div class="col-12">
                                                    <a href="{{ asset('storage/') . $img_1 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                                        <img src="{{ asset('storage/') .''. $img_1 }}" alt="{{ $img_1_alt }}" class="img-responsive {{ array_key_exists(0, $item_images) ? null : '' }}" style="width: 100% !important; {{ array_key_exists(0, $item_images) ? null : 'min-height: 200px' }}">
                                                    </a>
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <a href="{{ asset('storage/'.$img_2) }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                                        {{-- <img src="{{ asset('storage/') .''. $img_2 }}" alt="{{ $img_2_alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_2_webp) }}" type="image/webp" alt="{{ $img_2_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                            <source srcset="{{ asset('storage'.$img_2) }}" type="image/jpeg" alt="{{ $img_2_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                            <img src="{{ asset('storage'.$img_2) }}" alt="{{ $img_2_alt }}" alt="{{ $img_2_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="col-4 mt-2"> 
                                                    <a href="{{ asset('storage/'.$img_3) }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                                        {{-- <img src="{{ asset('storage/') .''. $img_3 }}" alt="{{ $img_3_alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_3_webp) }}" type="image/webp" alt="{{ $img_3_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                            <source srcset="{{ asset('storage'.$img_3) }}" type="image/jpeg" alt="{{ $img_3_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                            <img src="{{ asset('storage'.$img_3) }}" alt="{{ $img_3_alt }}" alt="{{ $img_3_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="col-4 mt-2">
                                                    <a href="{{ asset('storage'.$img_4) }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                                        <div class="text-white">
                                                            {{-- <img src="{{ asset('storage/') .''. $img_4 }}" alt="{{ $img_4_alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                                            <picture>
                                                                <source srcset="{{ asset('storage'.$img_4_webp) }}" type="image/webp" alt="{{ $img_4_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                                <source srcset="{{ asset('storage'.$img_4) }}" type="image/jpeg" alt="{{ $img_4_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                                <img src="{{ asset('storage'.$img_4) }}" alt="{{ $img_4_alt }}" alt="{{ $img_4_alt }}" class="img-responsive hover" style="width: 100% !important;">
                                                            </picture>
                                                            @if(count($item_images) > 4)
                                                                <div class="card-img-overlay text-center">
                                                                    <h5 class="card-title m-1 font-weight-bold">MORE</h5>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="col-md-12 text-center pt-3">
                                                    <button class="btn btn-primary btn-sm upload-item-image" data-item-code="{{ $item_details->name }}">Upload Image(s)</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-9">
                                            <br class="d-block d-md-none"/>
                                            <dl>
                                                <dt class="responsive-item-code" style="font-size: 14pt;"><span id="selected-item-code">{{ $item_details->name }}</span> {{ $item_details->brand }}</dt>
                                                <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $item_details->description !!}</dd>
                                            </dl>
                                            <div class="d-block d-lg-none">
                                                <p class="mt-2 mb-2 text-center">
                                                    @if (!in_array($user_department, $not_allowed_department)) 
                                                    @if(!in_array($user_group, ['Warehouse Personnel']) && $default_price > 0)
                                                        <span class="d-block font-weight-bold mt-3" style="font-size: 17pt;">{{ '₱ ' . number_format($default_price, 2, '.', ',') }}</span>
                                                        <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                                                    @endif
                                                    @if (in_array($user_group, ['Manager', 'Director']) && $minimum_selling_price > 0)
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
                                            <div class="card-header border-bottom-0 p-1">
                                                <h3 class="card-title m-0 font-responsive"><i class="fa fa-box-open"></i> Stock Level</h3>
                                                @if(in_array($user_group, ['Warehouse Personnel', 'Inventory Manager']))
                                                    <button class="btn btn-primary p-1 float-right" id="warehouse-location-btn" data-item-code="{{ $item_details->name }}" style="font-size: 12px;">Update Warehouse Location</button>
                                                @endif
                                            </div>
                                            <div class="box box-solid p-0">
                                                <div class="box-header with-border">
                                                    <div class="box-body table-responsive">
                                                        <table class="table table-striped table-bordered table-hover" style="font-size: 11pt;">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col" rowspan=2 class="font-responsive text-center p-1 align-middle">Warehouse</th>
                                                                    <th scope="col" colspan=3 class="font-responsive text-center p-1">Quantity</th>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="col" class="font-responsive text-center p-1 text-muted">Reserved</th>
                                                                    <th scope="col" class="font-responsive text-center p-1">Actual</th>
                                                                    <th scope="col" class="font-responsive text-center p-1">Available</th>
                                                                </tr>
                                                            </thead>
                                                            @forelse ($site_warehouses as $stock)
                                                            <tr>
                                                                <td class="p-1 font-responsive">
                                                                    {{ $stock['warehouse'] }}
                                                                    @if ($stock['location'])
                                                                        <small class="text-muted font-italic"> - {{ $stock['location'] }}</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center p-1 font-responsive">
                                                                    <small class="text-muted">{{ number_format((float)$stock['reserved_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</small>
                                                                </td>
                                                                <td class="text-center p-1 font-responsive">{{ number_format((float)$stock['actual_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</td>
                                                                <td class="text-center p-1">
                                                                    <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'secondary' }} font-responsive">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
                                                                </td>
                                                            </tr>
                                                            @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center font-responsive">No Stock(s)</td>
                                                            </tr>
                                                            @endforelse
                                                        </table>
                                                        @if(count($consignment_warehouses) > 0)
                                                            <div class="text-center">
                                                                <a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#vcww{{ $item_details->name }}" style="font-size: 12px;">View Consignment Warehouse</a>
                                                            </div>
                        
                                                            <div class="modal fade" id="vcww{{ $item_details->name }}" tabindex="-1" role="dialog">
                                                                <div class="modal-dialog" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h4 class="modal-title">{{ $item_details->name }} - Consignment Warehouse(s) </h4>
                                                                            <button type="button" class="close" onclick="close_modal('#vcww{{ $item_details->name }}')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                        </div>
                                                                        <form></form>
                                                                        <div class="modal-body">
                                                                            <table class="table table-hover m-0">
                                                                                <col style="width: 70%;">
                                                                                <col style="width: 30%;">
                                                                                <tr>
                                                                                    <th class="text-center">Warehouse</th>
                                                                                    <th class="text-center">Available Qty</th>
                                                                                </tr>
                                                                                @forelse($consignment_warehouses as $con)
                                                                                <tr>
                                                                                    <td>
                                                                                        {{ $con['warehouse'] }}
                                                                                        @if ($con['location'])
                                                                                            <small class="text-muted font-italic"> - {{ $con['location'] }}</small>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="text-center"><span class="badge badge-{{ ($con['available_qty'] > 0) ? 'success' : 'secondary' }}" style="font-size: 15px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span></td>
                                                                                </tr>
                                                                                @empty
                                                                                <tr>
                                                                                    <td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
                                                                                </tr>
                                                                                @endforelse
                                                                            </table>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-default" onclick="close_modal('#vcww{{ $item_details->name }}')">Close</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-none d-lg-block col-lg-4">
                                <div class="box box-solid h-100">
                                    <div class="box-body table-responsive no-padding h-100" style="display: flex; justify-content: center; align-items: center;">
                                        <p class="mt-2 mb-2 text-center">
                                            @if (!in_array($user_department, $not_allowed_department)) 
                                            @if(!in_array($user_group, ['Warehouse Personnel']) && $default_price > 0)
                                                <span class="d-block font-weight-bold" style="font-size: 17pt;">{{ '₱ ' . number_format($default_price, 2, '.', ',') }}</span>
                                                <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                                            @endif
                                            @if (in_array($user_group, ['Manager', 'Director']))
                                                @if ($minimum_selling_price > 0)
                                                    <span class="d-block font-weight-bold mt-3" style="font-size: 11pt;">{{ '₱ ' . number_format($minimum_selling_price, 2, '.', ',') }}</span>
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
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card-header border-bottom-0">
                                    <h3 class="card-title font-responsive"><i class="fas fa-project-diagram"></i> Variants</h3>
                                </div>
                            </div>
                            {{-- <div class="d-block d-lg-none col-12">
                                <div class="box box-solid">
                                    @php
                                        $variants = collect($co_variants)->chunk(5);
                                    @endphp
                                    <div class="divs tab-content">
                                        @for($i = 0; $i < count($variants); $i++)
                                        <div id="mob-variant-page-{{ $i + 1 }}" class="mob-tab tab-pane {{ $i == 0 ? 'active' : null }}">
                                            @php
                                                $first_item_name = \Illuminate\Support\Str::limit($item_details->item_name, 30, $end='...')
                                            @endphp
                                            <button class="btn w-100 text-left mb-3" type="button" data-toggle="collapse" data-target="#variant-data-{{ $item_details->name }}" aria-expanded="false" aria-controls="multiCollapseExample2" style="font-size: 9pt;border-bottom: 2px solid #28A745; color: #28A745;"><b>{{ $item_details->name }}</b> - {{ $first_item_name }} <i class="fa fa-chevron-down float-right"></i></button>
                                                    
                                            <div class="collapse multi-collapse show" id="variant-data-{{ $item_details->name }}">
                                                <table class="table" style="font-size: 9pt;">
                                                    @foreach ($attribute_names as $attribute_name)
                                                    <tr>
                                                        @php
                                                            $attribute_value = collect($attributes)->where('parent', $item_details->name)->where('attribute', $attribute_name)->pluck('attribute_value')->first();
                                                        @endphp
                                                        <td>{{ $attribute_name }}</td>
                                                        <td>{{ $attribute_value ? $attribute_value : 'n/a' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                            @foreach ($variants[$i] as $variant)
                                            @if ($item_details->name == $variant->name)
                                                @continue
                                            @endif
                                            @php
                                                $item_name = \Illuminate\Support\Str::limit($variant->item_name, 30, $end='...')
                                            @endphp
                                            <button class="btn w-100 text-left mb-3" type="button" data-toggle="collapse" data-target="#variant-data-{{ $variant->name }}" aria-expanded="false" aria-controls="multiCollapseExample2" style="font-size: 9pt; border-bottom: 1px solid #C4C4C4"><b>{{ $variant->name }}</b> - {{ $item_name }} <i class="fa fa-chevron-down float-right"></i></button>
                                            
                                            <div class="collapse multi-collapse" id="variant-data-{{ $variant->name }}">
                                                <table class="table" style="font-size: 9pt;">
                                                    @foreach ($attribute_names as $attribute_name)
                                                        <tr>
                                                            @php
                                                                $attribute_value = collect($attributes)->where('parent', $variant->name)->where('attribute', $attribute_name)->pluck('attribute_value')->first();
                                                            @endphp
                                                            <td>{{ $attribute_name }}</td>
                                                            <td>{{ $attribute_value ? $attribute_value : 'n/a' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                            @endforeach
                                        </div>
                                        
                                        @endfor
                                        @if (count($variants) > 1)
                                        <button class="btn float-left" id="btn-prev" style="font-size: 9pt; color: #007BFF"><i class="fa fa-chevron-left"></i> Previous</button>
                                        <button class="btn float-right" id="btn-next" style="font-size: 9pt; color: #007BFF">Next <i class="fa fa-chevron-right"></i></button>
                                        @endif
                                    </div>
                                </div>
                            </div> --}}
                            <div class="container d-none d-lg-block col-12 mt-2">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive" id="example">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" class="text-center" style="background-color: #CCD1D1;">Item Code</th>
                                                        @foreach ($attribute_names as $attribute_name)
                                                        <th scope="col" class="text-center text-nowrap">{{ $attribute_name }}</th>
                                                        @endforeach
                                                        @if (!in_array($user_department, $not_allowed_department)) 
                                                        @if (in_array($user_group, ['Manager', 'Director']))
                                                        <th scope="col" class="text-center text-nowrap">Price</th>
                                                        @endif
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr class="highlight-row">
                                                        <th scope="row" class="text-center align-middle" style="background-color: #001F3F !important;">{{ $item_details->name }}</th>
                                                        @foreach ($attribute_names as $attribute_name)
                                                        <td class="text-center align-middle">{{ array_key_exists($attribute_name, $item_attributes) ? $item_attributes[$attribute_name] : null }}</td>
                                                        @endforeach
                                                        @if (!in_array($user_department, $not_allowed_department)) 
                                                        @if (in_array($user_group, ['Manager', 'Director']))
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($default_price > 0)
                                                            {{ '₱ ' . number_format($default_price, 2, '.', ',') }}
                                                            @else
                                                            <span class="entered-price d-none">0.00</span>
                                                            <form action="/update_item_price/{{ $item_details->name }}" method="POST" autocomplete="off" class="update-price-form">
                                                                @csrf
                                                                <div class="input-group" style="width: 120px;">
                                                                    <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" required>
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                            @endif
                                                        </td>
                                                        @endif
                                                        @endif
                                                    </tr>
                                                    @foreach ($co_variants as $variant)
                                                    <tr style="font-size: 9pt;">
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
                                                            $price = 0;
                                                            if(array_key_exists($variant->name, $variants_price_arr)){
                                                                $price = $variants_price_arr[$variant->name];
                                                            }
                                                        @endphp
                                                        @if (!in_array($user_department, $not_allowed_department)) 
                                                         @if (in_array($user_group, ['Manager', 'Director']))
                                                        <td class="text-center align-middle text-nowrap">
                                                            @if ($price > 0)
                                                            {{ '₱ ' . number_format($price, 2, '.', ',') }}
                                                            @else
                                                            <span class="entered-price d-none">0.00</span>
                                                            <form action="/update_item_price/{{ $item_details->name }}" method="POST" autocomplete="off" class="update-price-form">
                                                                @csrf
                                                                <div class="input-group" style="width: 120px;">
                                                                    <input type="text" class="form-control form-control-sm" name="price" placeholder="0.00" required>
                                                                    <div class="input-group-append">
                                                                        <button class="btn btn-secondary btn-sm" type="submit"><i class="fas fa-check"></i></button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                            @endif
                                                        </td>
                                                        @endif
                                                        @endif
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="m-2">
                                    {{ $co_variants->links() }}
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card-header border-bottom-0">
                                    <h3 class="card-title font-responsive"><i class="fas fa-filter"></i> Item Alternatives</h3>
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
                                                        <a href="#" class="view-item-details text-dark" data-item-code="{{ $a['item_code'] }}" data-item-classification="{{ $item_details->item_classification }}">
                                                            <div class="p-1 text-justify">
                                                                <span class="font-weight-bold font-responsive">{{ $a['item_code'] }}</span>
                                                                <small class="font-italic font-responsive" style="font-size: 9pt;">{{ str_limit($a['description'], $limit = 78, $end = '...') }}</small>
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
                            <button class="btn btn-secondary font-responsive" id="athReset">Reset Filters</button>
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
                                    <button class="btn btn-secondary font-responsive" id="erpReset">Reset Filters</button>
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
                </div>
            </div>
        </div>
    </div>
    <style>
        #example tr > *:first-child {
            position: -webkit-sticky;
            position: sticky;
            left: 0;
            min-width: 10rem;
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
            font-weight: bold !important;
            font-size: 11pt;
            box-shadow: 2px 2px 8px #000000;
        }
        .variant-tabs{
            border-top: 1px solid #DEE2E6 !important;
        }

        .variant-tabs .nav-item .active{
            border-top: none !important;
            border-bottom: 1px solid #DEE2E6 !important;
        }
    </style>
@endsection
@section('script')
    <script>

        $(document).on('submit', '.update-price-form', function(e){
            e.preventDefault();

            var entered_price = $(this).parent().find('.entered-price');
            var frm = $(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    // $('#athena-transactions').html(response);
                    console.log(response)
                    entered_price.removeClass('d-none').text(response);
                    frm.remove();
                }
            });
        });
        // var divs = $('.divs>div');
        // var now = 0; // currently shown div
        // divs.hide().first().show();
        // $("#btn-next").click(function (e) {
        //     divs.eq(now).hide();
        //     now = (now + 1 < divs.length) ? now + 1 : 0;
        //     divs.eq(now).show(); // show next
        // });
        // $("#btn-prev").click(function (e) {
        //     divs.eq(now).hide();
        //     now = (now > 0) ? now - 1 : divs.length - 1;
        //     divs.eq(now).show(); // or .css('display','block');
        // });
        // $('#get-athena-transactions').click(function (){
        //     get_athena_transactions('{{ $item_details->name }}');
        // });

        // $('#get-stock-ledger').click(function (){
        //     get_stock_ledger('{{ $item_details->name }}');
        // });

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

        get_stock_ledger();
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
                // format: 'YYYY-MM-DD',
                format: 'YYYY-MMM-DD',
                separator: " to "
            },
            startDate: moment().subtract(30, 'days'), endDate: moment(),
            // startDate: '2018-06-01', endDate: moment(),
        });
        $("#ath_dates").val('');
        $("#ath_dates").attr("placeholder","Select Date Range");

        $("#erp_dates").daterangepicker({
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

        $("#erp_dates").val('');
		$("#erp_dates").attr("placeholder","Select Date Range");

        $('#erpReset').click(function(){
            $('#erp-warehouse-filter').empty();
            $('#erp-warehouse-user-filter').empty();
            $(function() {
                $("#erp_dates").daterangepicker({
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
                    // startDate: moment().subtract(30, 'days'), endDate: moment(),
                    startDate: '2018-01-01', endDate: moment(),

                });
            });
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
            $(function() {
                $("#ath_dates").daterangepicker({
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
            });
            $(function() {
                $("#erp_dates").daterangepicker({
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
            });
            $("#erp_dates").val('');
            $("#erp_dates").attr("placeholder","Select Date Range");
            $("#ath_dates").val('');
            $("#ath_dates").attr("placeholder","Select Date Range");
        });
    </script>
@endsection