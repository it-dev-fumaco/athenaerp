<div class="row p-1 bg-white">
    <div class="col-md-8">
        <div class="box box-solid">
            <div class="box-header">
                <h4 class="box-title"><i class="fas fa-info-circle"></i> Item Info</h4>
            </div>
            <div class="row">
                @php
                    $img_1 = (array_key_exists(0, $item_images)) ? '/img/' . $item_images[0] : '/icon/no_img.png';
                    $img_2 = (array_key_exists(1, $item_images)) ? '/img/' . $item_images[1] : '/icon/no_img.png';
                    $img_3 = (array_key_exists(2, $item_images)) ? '/img/' . $item_images[2] : '/icon/no_img.png';
                    $img_4 = (array_key_exists(3, $item_images)) ? '/img/' . $item_images[3] : '/icon/no_img.png';
                @endphp
                <div class="col-md-4">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ asset('storage/') }}{{ $img_1 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') }}{{ $img_1 }}" class="img-thumbnail">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') }}{{ $img_2 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') }}{{ $img_2 }}" class="img-thumbnail" style="margin: 1px;">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') }}{{ $img_3 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <img src="{{ asset('storage/') }}{{ $img_3 }}" class="img-thumbnail" style="margin: 1px;">
                            </a>
                        </div>
                        <div class="col-md-4 mt-2">
                            <a href="{{ asset('storage/') }}{{ $img_4 }}" data-toggle="lightbox" data-gallery="{{ $item_details->name }}" data-title="{{ $item_details->name }}">
                                <div class="text-white">
                                    <img src="{{ asset('storage/') }}{{ $img_4 }}" class="img-thumbnail" style="margin: 1px;">
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
                <div class="col-md-8">
                    <dl>
                        <dt style="font-size: 15pt;" id="selected-item-code">{{ $item_details->name }}</dt>
                        <dd style="font-size: 13pt;" class="text-justify mb-2">{{ $item_details->description }}</dd>
                        <dt>Classification</dt>
                        <dd>{{ $item_details->item_classification }}</dd>
                        <dt>Brand</dt>
                        <dd>{{ $item_details->brand }}</dd>
                        <dt>Stock UoM</dt>
                        <dd>{{ $item_details->stock_uom }}</dd>
                    </dl>
                </div>
                <div class="col-md-12">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title"><i class="fa fa-box-open"></i> Stock Level</h3>
        
                        {{-- <div class="card-tools">
                            <span class="font-weight-bold m-1">Total Remaining Qty:</span>
                            <span class="badge bg-info" style="font-size: 12pt;">{{ number_format((float)$stock_level->sum('actual_qty'), 2, '.', '') }} {{ $item_details->stock_uom }}</span>
                        </div> --}}
                      </div>
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <div class="box-body table-responsive pr-4 pl-4">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <th scope="col" >Warehouse</th>
                                        <th scope="col" class="text-center">Reserved Qty</th>
                                        <th scope="col" class="text-center">Actual Qty</th>
                                        <th scope="col" class="text-center">Available Qty</th>
                                    </thead>
                                    @forelse ($stock_level as $stock)
                                    <tr>
                                        <td>{{ $stock['warehouse'] }}</td>
                                        <td class="text-center">{{ number_format((float)$stock['reserved_qty'], 2, '.', '') }} {{ $stock['stock_uom'] }}</td>
                                        <td class="text-center">{{ number_format((float)$stock['actual_qty'], 2, '.', '') }} {{ $stock['stock_uom'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2">No stocks.</td>
                                    </tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 pr-4 pl-4">
                    <div class="card-header border-bottom-0">
                        <h3 class="card-title"><i class="fa fa-box-open"></i> Item Alternatives</h3>
                      </div>
                      
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-default">
                    
                                <div class="card-body p-0">
                                  <div class="col-12">
                                    <div class="d-flex flex-row">
                                        <div class="p-2">
                                            <a href="{{ asset('storage/img/1603270517-LC00319.jpg') }}" class="img-thumbnail">
                                                    <img src="{{ asset('storage/img/1603270517-LC00319.jpg') }}" class="img1-size-50 d-inline-block" width="100">
                                            </a>
                                        </div>
                                        <div class="p-2">
                                            <span class="d-block font-weight-bold">LR00124</span>
                                        <small class="d-block font-italic">PHILIPS LED DIMMABLE, LED, 12-75W, 2200K-2700K, 25000h</small>
                                        <small class="d-inline-block">Available Qty</small> <span class="badge badge-success">10 Piece(s)</span>
                                        </div>
                                      </div>
                                  </div>
                                 
                                </div>
                                <!-- /.card-body -->
                              </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-default">
                    
                                <div class="card-body p-0">
                                  <div class="col-12">
                                    <div class="d-flex flex-row">
                                        <div class="p-2">
                                            <a href="{{ asset('storage/img/1603776110-LC00317.jpg') }}" class="img-thumbnail">
                                                    <img src="{{ asset('storage/img/1603776110-LC00317.jpg') }}" class="1img-size-50 d-inline-block" width="100">
                                            </a>
                                        </div>
                                        <div class="p-2">
                                            <span class="d-block font-weight-bold">LR00317</span>
                                        <small class=" d-block font-italic">BRILLIANT MR16, 7.5w, 2700K, 85%, 460lm, 1000h, 12v, 36D</small>
                                        <small class="d-inline-block">Available Qty</small> <span class="badge badge-success">110 Piece(s)</span>
                                        </div>
                                      </div>
                                  </div>
                                 
                                </div>
                                <!-- /.card-body -->
                              </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-default">
                    
                                <div class="card-body p-0">
                                  <div class="col-12">
                                    <div class="d-flex flex-row">
                                        <div class="p-2">
                                            <a href="{{ asset('storage/img/1603270791-LC00318.jpg') }}" class="img-thumbnail">
                                                    <img src="{{ asset('storage/img/1603270791-LC00318.jpg') }}" class="img1-size-50 d-inline-block" width="100">
                                            </a>
                                        </div>
                                        <div class="p-2">
                                            <span class="d-block font-weight-bold">LR00320</span>
                                        <small class="d-block font-italic">Soraa Vivid 2, 7.5w, 10D, 4000K, 1000h, 380lm, 12v, MR16</small>
                                        <small class="d-inline-block">Available Qty</small> <span class="badge badge-success">102 Piece(s)</span>
                                        </div>
                                      </div>
                                  </div>
                                 
                                </div>
                                <!-- /.card-body -->
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
                        <td colspan="2">No item attributes.</td>
                    </tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</div>
