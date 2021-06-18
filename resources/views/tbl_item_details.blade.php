<div class="row p-1 bg-white">
    <div class="col-md-8">
        <div class="box box-solid mt-2">
            <div class="row">
                @php
                    $img_1 = (array_key_exists(0, $item_images)) ? '/img/' . $item_images[0] : '/icon/no_img.png';
                    $img_2 = (array_key_exists(1, $item_images)) ? '/img/' . $item_images[1] : '/icon/no_img.png';
                    $img_3 = (array_key_exists(2, $item_images)) ? '/img/' . $item_images[2] : '/icon/no_img.png';
                    $img_4 = (array_key_exists(3, $item_images)) ? '/img/' . $item_images[3] : '/icon/no_img.png';
                @endphp
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ asset('storage/') .''. $img_1 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') .''. $img_1 }}" class="img-thumbnail">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') .''. $img_2 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') .''. $img_2 }}" class="img-thumbnail" style="margin: 1px;">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') .''. $img_3 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') .''. $img_3 }}" class="img-thumbnail" style="margin: 1px;">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') .''. $img_4 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <div class="text-white">
                                    <img src="{{ asset('storage/') .''. $img_4 }}" class="img-thumbnail" style="margin: 1px;">
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
                    <dl>
                        <dt style="font-size: 15pt;"><span id="selected-item-code">{{ $item_details->name }}</span> {{ $item_details->brand }}</dt>
                        <dd style="font-size: 13pt;" class="text-justify mb-2">{{ $item_details->description }}</dd>
                    </dl>
                    <div class="card-header border-bottom-0 p-1">
                        <h3 class="card-title m-0"><i class="fa fa-box-open"></i> Stock Level</h3>
                    </div>
                    <div class="box box-solid p-0">
                        <div class="box-header with-border">
                            <div class="box-body table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <th scope="col" class="text-center p-1">Warehouse</th>
                                        <th scope="col" class="text-center p-1">Reserved Qty</th>
                                        <th scope="col" class="text-center p-1">Actual Qty</th>
                                        <th scope="col" class="text-center p-1">Available Qty</th>
                                    </thead>
                                    @forelse ($site_warehouses as $stock)
                                    <tr>
                                        <td class="p-1">{{ $stock['warehouse'] }}</td>
                                        <td class="text-center p-1">{{ number_format((float)$stock['reserved_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</td>
                                        <td class="text-center p-1">{{ number_format((float)$stock['actual_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</td>
                                        <td class="text-center p-1">
                                            <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No Stock(s)</td>
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
																	<td>{{ $con['warehouse'] }}</td>
																	<td class="text-center"><span class="badge badge-{{ ($con['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 15px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span></td>
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
            <div class="box-header">
                <h4 class="box-title"><i class="fas fa-list-alt"></i> Specification</h4>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-sm table-bordered" style="font-size: 11pt;">
                    <thead>
                        <th scope="col" >Attribute</th>
                        <th scope="col" >Attribute Value</th>
                    </thead>
                    @forelse ($item_attributes as $attr)
                    <tr>
                        <td>{{ $attr->attribute }}</td>
                        <td>{{ $attr->attribute_value }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center">No Item Attribute(s)</td>
                    </tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card-header border-bottom-0">
            <h3 class="card-title"><i class="fas fa-filter"></i> Item Alternatives</h3>
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
                                        $img = ($a['item_alternative_image']) ? '/img/' . $a['item_alternative_image'] : '/icon/no_img.png';
                                    @endphp
                                    <a href="{{ asset('storage') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $a['item_code'] }}" data-title="{{ $a['item_code'] }}">
                                        <img src="{{ asset('storage') . '' . $img }}" class="rounded" width="80" height="80">
                                    </a>
                                </div>
                                <a href="#" class="view-item-details text-dark" data-item-code="{{ $a['item_code'] }}" data-item-classification="{{ $item_details->item_classification }}">
                                    <div class="p-1 text-justify">
                                        <span class="font-weight-bold">{{ $a['item_code'] }}</span>
                                        <small class="font-italic">{{ str_limit($a['description'], $limit = 78, $end = '...') }}</small>
                                        <br>
                                        <span class="badge badge-{{ ($a['actual_stocks'] > 0) ? 'success' : 'danger' }}">{{ ($a['actual_stocks'] > 0) ? 'In Stock' : 'Unavailable' }}</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-md-12">
                <h5 class="text-center">No Item Alternative(s)</h5>
            </div>
            @endforelse
        </div>
    </div>
</div>
