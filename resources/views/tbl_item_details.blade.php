<div class="row p-1 bg-white">
    <div class="col-md-8">
        <div class="box box-solid mt-2">
            <div class="row">
                @php
                    $img_1 = (array_key_exists(0, $item_images)) ? '/img/' . explode('.', $item_images[0])[0].'.webp' : '/icon/no_img.webp';
                    $img_1_alt = (array_key_exists(0, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_1)[0], '-') : null;
                    $img_2 = (array_key_exists(1, $item_images)) ? '/img/' . explode('.', $item_images[1])[0].'.webp' : '/icon/no_img.webp';
                    $img_2_alt = (array_key_exists(1, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_2)[0], '-') : null;
                    $img_3 = (array_key_exists(2, $item_images)) ? '/img/' . explode('.', $item_images[2])[0].'.webp' : '/icon/no_img.webp';
                    $img_3_alt = (array_key_exists(2, $item_images)) ? Illuminate\Support\Str::slug(explode('.', $img_3)[0], '-') : null;
                    $img_4 = (array_key_exists(3, $item_images)) ? '/img/' . explode('.', $item_images[3])[0].'.webp' : '/icon/no_img.webp';
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
                            <a href="{{ asset('storage/') . $img_2 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') .''. $img_2 }}" alt="{{ $img_2_alt }}" class="img-responsive hover" style="width: 100% !important;">
                            </a>
                        </div>
                        <div class="col-4 mt-2"> 
                            <a href="{{ asset('storage/') . $img_3 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') .''. $img_3 }}" alt="{{ $img_3_alt }}" class="img-responsive hover" style="width: 100% !important;">
                            </a>
                        </div>
                        <div class="col-4 mt-2">
                            <a href="{{ asset('storage/') . $img_4 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <div class="text-white">
                                    <img src="{{ asset('storage/') .''. $img_4 }}" alt="{{ $img_4_alt }}" class="img-responsive hover" style="width: 100% !important;">
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
                    @if ($user_pricelist && $price_list_rate != '-')
                    <p class="mt-2 mb-2 text-center">
                        <span class="d-block font-weight-bold" style="font-size: 17pt;">{{ '₱ ' . number_format($price_list_rate, 2, '.', ',') }}</span>
                        <span class="d-block" style="font-size: 12pt;">{{ $user_pricelist }}</span>
                    </p>
                    @endif
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
                                            <th scope="col" rowspan=2 class="font-responsive text-center p-1">Warehouse</th>
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
                                            <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'secondary' }} font-responsive" style="font-size: 11pt;">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
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
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
    <div class="col-md-4">
        <div class="box box-solid">
            <div class="box-body table-responsive no-padding">
                <table class="table table-sm table-bordered" style="font-size: 11pt;">
                    <thead>
                        <th scope="col" colspan ="2"  class="text-center responsive-description"><i class="fas fa-list-alt"></i> Specification</th>
                    </thead>
                    @forelse ($item_attributes as $attr)
                    <tr>
                        <td class="font-responsive">{{ $attr->attribute }}</td>
                        <td class="text-center font-responsive">{{ $attr->attribute_value }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center font-responsive">No Item Attribute(s)</td>
                    </tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card-header border-bottom-0">
            <h3 class="card-title font-responsive"><i class="fas fa-filter"></i> Item Alternatives</h3>
        </div>

        <style>
        .custom-body {
            min-width: 406px;
            max-width: 406px;
        }
        </style>
        <div class="d-flex flex-row flex-nowrap overflow-auto">
            @forelse($item_alternatives as $a)
            <div class="custom-body m-1">
                <div class="card card-default">
                    <div class="card-body p-0">
                        <div class="col-12">
                            <div class="d-flex flex-row">
                                <div class="pt-2 pb-2 pr-1 pl-1">
                                    @php
                                        $img = ($a['item_alternative_image']) ? '/img/' . explode('.', $a['item_alternative_image'])[0].'.webp' : '/icon/no_img.webp';
                                    @endphp
                                    <a href="{{ asset('storage') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $a['item_code'] }}" data-title="{{ $a['item_code'] }}">
                                        <img src="{{ asset('storage/') .''. $img }}" class="rounded" width="80" height="80">
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
