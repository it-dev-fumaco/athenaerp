<div class="row p-1 bg-white">
    <div class="col-12 col-lg-8">
        <div class="box box-solid mt-2">
            <div class="row">
                @php
                    $img1 = data_get($itemImages, 0) ? '/img/' . $itemImages[0] : '/icon/no_img.png';
                    $img1Name = data_get($itemImages, 0);
                    $img1Webp = data_get($itemImages, 0) ? '/img/' . explode('.', $itemImages[0])[0].'.webp' : '/icon/no_img.webp';
                    $img1Alt = data_get($itemImages, 0) ? Illuminate\Support\Str::slug(explode('.', $img1)[0], '-') : null;

                    $img2 = data_get($itemImages, 1) ? '/img/' . $itemImages[1] : '/icon/no_img.png';
                    $img2Name = data_get($itemImages, 1);
                    $img2Webp = data_get($itemImages, 1) ? '/img/' . explode('.', $itemImages[1])[0].'.webp' : '/icon/no_img.webp';
                    $img2Alt = data_get($itemImages, 1) ? Illuminate\Support\Str::slug(explode('.', $img2)[0], '-') : null;

                    $img3 = data_get($itemImages, 2) ? '/img/' . $itemImages[2] : '/icon/no_img.png';
                    $img3Name = data_get($itemImages, 2);
                    $img3Webp = data_get($itemImages, 2) ? '/img/' . explode('.', $itemImages[2])[0].'.webp' : '/icon/no_img.webp';
                    $img3Alt = data_get($itemImages, 2) ? Illuminate\Support\Str::slug(explode('.', $img3)[0], '-') : null;

                    $img4 = data_get($itemImages, 3) ? '/img/' . $itemImages[3] : '/icon/no_img.png';
                    $img4Name = data_get($itemImages, 3);
                    $img4Webp = data_get($itemImages, 3) ? '/img/' . explode('.', $itemImages[3])[0].'.webp' : '/icon/no_img.webp';
                    $img4Alt = data_get($itemImages, 3) ? Illuminate\Support\Str::slug(explode('.', $img4)[0], '-') : null;
                @endphp
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-12">
                            <a href="{{ asset('storage/') . $img1 }}" data-toggle="lightbox" data-gallery="{{ $itemDetails->name }}" data-title="{{ $itemDetails->name }}">
                                {{-- <img src="{{ asset('storage/') .''. $img1 }}" alt="{{ $img1Alt }}" class="img-responsive {{ data_get($itemImages, 0) ? null : '' }}" style="width: 100% !important; {{ data_get($itemImages, 0) ? null : 'min-height: 200px' }}"> --}}
                                @if(!Storage::disk('public')->exists('/img/'.explode('.', $img1Name)[0].'.webp'))
                                    <img src="{{ asset('storage/') .''. $img1 }}" alt="{{ $img1Alt }}" class="img-responsive {{ data_get($itemImages, 0) ? null : '' }}" style="width: 100% !important; {{ data_get($itemImages, 0) ? null : 'min-height: 200px' }}">
                                @elseif(!Storage::disk('public')->exists('/img/'.$img1Name))
                                    <img src="{{ asset('storage/') .''. $img1Webp }}" alt="{{ $img1Alt }}" class="img-responsive {{ data_get($itemImages, 0) ? null : '' }}" style="width: 100% !important; {{ data_get($itemImages, 0) ? null : 'min-height: 200px' }}">
                                @else
                                    <picture>
                                        <source srcset="{{ asset('storage'.$img1Webp) }}" type="image/webp" class="img-responsive">
                                        <source srcset="{{ asset('storage'.$img1) }}" type="image/jpeg" class="img-responsive">
                                            <img src="{{ asset('storage/') .''. $img1 }}" alt="{{ $img1Alt }}" class="img-responsive {{ data_get($itemImages, 0) ? null : '' }}" style="width: 100% !important; {{ data_get($itemImages, 0) ? null : 'min-height: 200px' }}">
                                    </picture>
                                @endif
                            </a>
                        </div>
                        <div class="col-4 mt-2">
                            <a href="{{ asset('storage/'.$img2) }}" data-toggle="lightbox" data-gallery="{{ $itemDetails->name }}" data-title="{{ $itemDetails->name }}">
                                {{-- <img src="{{ asset('storage/') .''. $img2 }}" alt="{{ $img2Alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                @if(!Storage::disk('public')->exists('/img/'.explode('.', $img2Name)[0].'.webp'))
                                    <img src="{{ asset('storage/') .''. $img2 }}" alt="{{ $img2Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                @elseif(!Storage::disk('public')->exists('/img/'.$img2Name))
                                    <img src="{{ asset('storage/') .''. $img2Webp }}" alt="{{ $img2Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                @else
                                    <picture>
                                        <source srcset="{{ asset('storage'.$img2Webp) }}" type="image/webp" class="img-responsive hover" style="width: 100% !important;">
                                        <source srcset="{{ asset('storage'.$img2) }}" type="image/jpeg" class="img-responsive hover" style="width: 100% !important;">
                                        <img src="{{ asset('storage/') .''. $img2 }}" alt="{{ $img2Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                    </picture>
                                @endif
                            </a>
                        </div>
                        <div class="col-4 mt-2"> 
                            <a href="{{ asset('storage/'.$img3) }}" data-toggle="lightbox" data-gallery="{{ $itemDetails->name }}" data-title="{{ $itemDetails->name }}">
                                {{-- <img src="{{ asset('storage/') .''. $img3 }}" alt="{{ $img3Alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                @if(!Storage::disk('public')->exists('/img/'.explode('.', $img3Name)[0].'.webp'))
                                    <img src="{{ asset('storage/') .''. $img3 }}" alt="{{ $img3Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                @elseif(!Storage::disk('public')->exists('/img/'.$img3Name))
                                    <img src="{{ asset('storage/') .''. $img3Webp }}" alt="{{ $img3Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                @else
                                    <picture>
                                        <source srcset="{{ asset('storage'.$img3Webp) }}" type="image/webp" class="img-responsive hover" style="width: 100% !important;">
                                        <source srcset="{{ asset('storage'.$img3) }}" type="image/jpeg" class="img-responsive hover" style="width: 100% !important;">
                                        <img src="{{ asset('storage/') .''. $img3 }}" alt="{{ $img3Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                    </picture>
                                @endif
                            </a>
                        </div>
                        <div class="col-4 mt-2">
                            <a href="{{ asset('storage'.$img4) }}" data-toggle="lightbox" data-gallery="{{ $itemDetails->name }}" data-title="{{ $itemDetails->name }}">
                                <div class="text-white">
                                    {{-- <img src="{{ asset('storage/') .''. $img4 }}" alt="{{ $img4Alt }}" class="img-responsive hover" style="width: 100% !important;"> --}}
                                    @if(!Storage::disk('public')->exists('/img/'.explode('.', $img4Name)[0].'.webp'))
                                        <img src="{{ asset('storage/') .''. $img4 }}" alt="{{ $img4Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                    @elseif(!Storage::disk('public')->exists('/img/'.$img3Name))
                                        <img src="{{ asset('storage/') .''. $img4Webp }}" alt="{{ $img4Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                    @else
                                        <picture>
                                            <source srcset="{{ asset('storage'.$img4Webp) }}" type="image/webp" class="img-responsive hover" style="width: 100% !important;">
                                            <source srcset="{{ asset('storage'.$img4) }}" type="image/jpeg" class="img-responsive hover" style="width: 100% !important;">
                                            <img src="{{ asset('storage/') .''. $img4 }}" alt="{{ $img4Alt }}" class="img-responsive hover" style="width: 100% !important;">
                                        </picture>
                                    @endif
                                    
                                    @if(count($itemImages) > 4)
                                        <div class="card-img-overlay text-center">
                                            <h5 class="card-title m-1 font-weight-bold">MORE</h5>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>
                        <div class="col-md-12 text-center pt-3">
                            <button class="btn btn-primary btn-sm upload-item-image" data-item-code="{{ $itemDetails->name }}">Upload Image(s)</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <br class="d-block d-md-none"/>
                    <dl>
                        <dt class="responsive-item-code" style="font-size: 14pt;"><span id="selected-item-code">{{ $itemDetails->name }}</span> {{ $itemDetails->brand }}</dt>
                        <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $itemDetails->description !!}</dd>
                    </dl>
                    <div class="d-block d-lg-none">
                        <p class="mt-2 mb-2 text-center">
                            @if(!in_array($userGroup, ['Warehouse Personnel']) && $defaultPrice > 0)
                                <span class="d-block font-weight-bold" style="font-size: 17pt;">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                                <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                            @endif
                            @if (in_array($userGroup, ['Manager', 'Director']) && $minimumSellingPrice > 0)
                                <span class="d-block font-weight-bold" style="font-size: 15pt;">{{ '₱ ' . number_format($minimumSellingPrice, 2, '.', ',') }}</span>
                                <span class="d-block" style="font-size: 9pt;">Minimum Selling Price</span>
                            @endif
                        </p>
                    </div>
                    <div class="card-header border-bottom-0 p-1">
                        <h3 class="card-title m-0 font-responsive"><i class="fa fa-box-open"></i> Stock Level</h3>
                        @if(in_array($userGroup, ['Warehouse Personnel', 'Inventory Manager']))
                            <button class="btn btn-primary p-1 float-right" id="warehouse-location-btn" data-item-code="{{ $itemDetails->name }}" style="font-size: 12px;">Update Warehouse Location</button>
                        @endif
                    </div>
                    <div class="box box-solid p-0">
                        <div class="box-header with-border">
                            <div class="box-body table-responsive">
                                <table class="table table-striped table-bordered table-hover" style="font-size: 11pt;">
                                    <thead>
                                        <tr>
                                            <th scope="col" rowspan=2 class="font-responsive text-center p-1">Warehouse</th>
                                            <th scope="col" colspan=3 class="font-responsive text-center p-1">Quantity</th>
                                        </tr>
                                        <tr>
                                            <th scope="col" class="font-responsive text-center p-1 text-muted">Reserved</th>
                                            <th scope="col" class="font-responsive text-center p-1">Actual</th>
                                            <th scope="col" class="font-responsive text-center p-1">Available</th>
                                        </tr>
                                    </thead>
                                    @forelse ($siteWarehouses as $stock)
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
                                            <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'secondary' }} font-responsive" style="font-size: 11pt;">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center font-responsive">No Stock(s)</td>
                                    </tr>
                                    @endforelse
                                </table>
                                @if(count($consignmentWarehouses) > 0)
                                    <div class="text-center">
                                        <a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#vcww{{ $itemDetails->name }}" style="font-size: 12px;">View Consignment Warehouse</a>
                                    </div>

                                    <div class="modal fade" id="vcww{{ $itemDetails->name }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title">{{ $itemDetails->name }} - Consignment Warehouse(s) </h4>
                                                    <button type="button" class="close" onclick="close_modal('#vcww{{ $itemDetails->name }}')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                                                        @forelse($consignmentWarehouses as $con)
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
                                                    <button type="button" class="btn btn-default" onclick="close_modal('#vcww{{ $itemDetails->name }}')">Close</button>
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
        <div class="box box-solid">
            <div class="box-body table-responsive no-padding">
                <p class="mt-2 mb-2 text-center">
                    @if(!in_array($userGroup, ['Warehouse Personnel']) && $defaultPrice > 0)
                        <span class="d-block font-weight-bold" style="font-size: 17pt;">{{ '₱ ' . number_format($defaultPrice, 2, '.', ',') }}</span>
                        <span class="d-block" style="font-size: 11pt;">Standard Selling Price</span>
                    @endif
                    @if (in_array($userGroup, ['Manager', 'Director']) && $minimumSellingPrice > 0)
                        <span class="d-block font-weight-bold" style="font-size: 15pt;">{{ '₱ ' . number_format($minimumSellingPrice, 2, '.', ',') }}</span>
                        <span class="d-block" style="font-size: 9pt;">Minimum Selling Price</span>
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
    <div class="d-block d-lg-none col-12">
        <div class="box box-solid">
            @php
                $variants = collect($coVariants)->chunk(5);
            @endphp
            <div class="divs tab-content">
                @for($i = 0; $i < count($variants); $i++)
                    <div id="mob-variant-page-{{ $i + 1 }}" class="mob-tab tab-pane {{ $i == 0 ? 'active' : null }}">
                        @php
                            $firstItemName = \Illuminate\Support\Str::limit($itemDetails->item_name, 30, $end='...')
                        @endphp
                        <button class="btn w-100 text-left mb-3" type="button" data-toggle="collapse" data-target="#variant-data-{{ $itemDetails->name }}" aria-expanded="false" aria-controls="multiCollapseExample2" style="font-size: 9pt;border-bottom: 2px solid #28A745; color: #28A745;"><b>{{ $itemDetails->name }}</b> - {{ $firstItemName }} <i class="fa fa-chevron-down float-right"></i></button>
                            
                        <div class="collapse multi-collapse show" id="variant-data-{{ $itemDetails->name }}">
                            <table class="table" style="font-size: 9pt;">
                                @foreach ($attributeNames as $attributeName)
                                    <tr>
                                        @php
                                            $attributeValue = collect($attributes)->where('parent', $itemDetails->name)->where('attribute', $attributeName)->pluck('attribute_value')->first();
                                        @endphp
                                        <td>{{ $attributeName }}</td>
                                        <td>{{ $attributeValue ? $attributeValue : 'n/a' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        @foreach ($variants[$i] as $variant)
                            @if ($itemDetails->name == $variant->name)
                                @continue
                            @endif
                            @php
                                $itemName = \Illuminate\Support\Str::limit($variant->item_name, 30, $end='...')
                            @endphp
                            <button class="btn w-100 text-left mb-3" type="button" data-toggle="collapse" data-target="#variant-data-{{ $variant->name }}" aria-expanded="false" aria-controls="multiCollapseExample2" style="font-size: 9pt; border-bottom: 1px solid #C4C4C4"><b>{{ $variant->name }}</b> - {{ $itemName }} <i class="fa fa-chevron-down float-right"></i></button>
                            
                            <div class="collapse multi-collapse" id="variant-data-{{ $variant->name }}">
                                <table class="table" style="font-size: 9pt;">
                                    @foreach ($attributeNames as $attributeName)
                                        <tr>
                                            @php
                                                $attributeValue = collect($attributes)->where('parent', $variant->name)->where('attribute', $attributeName)->pluck('attribute_value')->first();
                                            @endphp
                                            <td>{{ $attributeName }}</td>
                                            <td>{{ $attributeValue ? $attributeValue : 'n/a' }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endfor
                <button class="btn float-left" id="btn-prev" style="font-size: 9pt; color: #007BFF"><i class="fa fa-chevron-left"></i> Previous</button>
                <button class="btn float-right" id="btn-next" style="font-size: 9pt; color: #007BFF">Next <i class="fa fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
    <div class="d-none d-lg-block col-12 mt-2">
        <div class="box box-solid">
            @php
                $variants = collect($coVariants)->chunk(5);
            @endphp
            <div class="tab-content" style="overflow-x: auto; white-space: nowrap;">
                @for($i = 0; $i < count($variants); $i++)
                    <div id="variant-page-{{ $i + 1 }}" class="tab-pane {{ $i == 0 ? 'active' : null }}">
                        <table id="variants-table" class="table table-bordered" style="font-size: 10pt;">
                            <tr>
                                <th class="text-center">Item Code</th>
                                @foreach ($attributeNames as $attributeName)
                                    <th class="text-center">{{ $attributeName }}</th>
                                @endforeach
                                <th class="text-center">Price</th>
                            </tr>
                            <tr class="highlight-row">
                                <td class="text-center table-highlight pb-3 pt-3">{{ $itemDetails->name }}</td>
                                @foreach($attributeNames as $attributeName)
                                    @php
                                        $attributeValue = collect($attributes)->where('parent', $itemDetails->name)->where('attribute', $attributeName)->pluck('attribute_value')->first();
                                    @endphp
                                    <td class="text-center table-highlight pb-3 pt-3">{{ $attributeValue ? $attributeValue : 'n/a' }}</td>
                                @endforeach
                                <td class="text-center table-highlight pb-3 pt-3">{{ $defaultPrice > 0 ? '₱ ' . number_format($defaultPrice, 2, '.', ',') : 'n/a' }}</td>
                            </tr>
                            @foreach ($variants[$i] as $variant)
                                @if ($itemDetails->name == $variant->name)
                                    @continue
                                @endif
                                <tr style="font-size: 9pt;">
                                    <td class="text-center">{{ $variant->name }}</td>
                                    @foreach ($attributeNames as $attributeName)
                                        @php
                                            $attributeValue = collect($attributes)->where('parent', $variant->name)->where('attribute', $attributeName)->pluck('attribute_value')->first();
                                        @endphp
                                        <td class="text-center">{{ $attributeValue ? $attributeValue : 'n/a' }}</td>
                                    @endforeach
                                    @php
                                        $price = 0;
                                        if(isset($variantsPriceArr[$variant->name])){
                                            $price = $variantsPriceArr[$variant->name][0];
                                        }
                                    @endphp
                                    <td class="text-center">{{ $price > 0 ? '₱ ' . number_format($price, 2, '.', ',') : 'n/a' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endfor
            </div>
            @if (count($variants) > 1)
                <ul class="nav nav-tabs variant-tabs mb-3" role="tablist">
                    @foreach ($variants as $i => $item)
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : null }}" data-toggle="tab" href="#variant-page-{{ $i + 1 }}">{{ $i + 1 }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="col-md-12 item-alternatives-section min-width-0">
        <div class="card-header border-bottom-0">
            <h3 class="card-title font-responsive"><i class="fas fa-filter"></i> Item Alternatives</h3>
        </div>

        <style>
        .custom-body {
            min-width: 280px;
            max-width: 406px;
            flex-shrink: 0;
        }
        .item-alternative-description {
            max-height: 5.5em;
            overflow-y: auto;
            display: block;
        }
        .item-alternative-description p { margin-bottom: 0.25em; }
        @media (max-width: 767.98px) {
            .custom-body { min-width: 240px; max-width: 100%; }
        }
        </style>
        <div class="item-alternatives-scroll">
            <div class="d-flex flex-row flex-nowrap">
            @forelse($itemAlternatives as $a)
            <div class="custom-body m-1">
                <div class="card card-default">
                    <div class="card-body p-0">
                        <div class="col-12">
                            <div class="d-flex flex-row">
                                <div class="pt-2 pb-2 pr-1 pl-1">
                                    @php
                                        $img = ($a['item_alternative_image']) ? '/img/' . explode('.', $a['item_alternative_image'])[0].'.jpg' : '/icon/no_img.jpg';
                                        $imgWebp = ($a['item_alternative_image']) ? '/img/' . explode('.', $a['item_alternative_image'])[0].'.webp' : '/icon/no_img.webp';
                                    @endphp
                                    <a href="{{ asset('storage' . $img) }}" data-toggle="lightbox" data-gallery="{{ $a['item_code'] }}" data-title="{{ $a['item_code'] }}">
                                        {{-- <img src="{{ asset('storage/') .''. $img }}" class="rounded" width="80" height="80"> --}}
                                        @if(!Storage::disk('public')->exists('/img/'.explode('.', $a['item_alternative_image'])[0].'.webp'))
                                            <img src="{{ asset('storage/') .''. $img }}" class="rounded" width="80" height="80">
                                        @elseif(!Storage::disk('public')->exists('/img/'.$a['item_alternative_image']))
                                            <img src="{{ asset('storage/') .''. $img }}" class="rounded" width="80" height="80">
                                        @else
                                            <picture>
                                                <source srcset="{{ asset('storage'.$imgWebp) }}" type="image/webp" class="rounded" width="80" height="80">
                                                <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="rounded" width="80" height="80">
                                                <img src="{{ asset('storage'.$img) }}" class="rounded" width="80" height="80">
                                            </picture>
                                        @endif
                                    </a>
                                </div>
                                <a href="#" class="view-item-details text-dark" data-item-code="{{ $a['item_code'] }}" data-item-classification="{{ $itemDetails->item_classification }}">
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
<style>
    .table-highlight{
        border: 2px solid rgba(0, 31, 63, 0.3) !important;
    }

    .highlight-row{
        background-color: #001F3F;
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
<script>
     var divs = $('.divs>div');
    var now = 0; // currently shown div
    divs.hide().first().show();
    $("#btn-next").click(function (e) {
        divs.eq(now).hide();
        now = (now + 1 < divs.length) ? now + 1 : 0;
        divs.eq(now).show(); // show next
    });
    $("#btn-prev").click(function (e) {
        divs.eq(now).hide();
        now = (now > 0) ? now - 1 : divs.length - 1;
        divs.eq(now).show(); // or .css('display','block');
    });
</script>