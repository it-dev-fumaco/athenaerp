@php
    $submit_url = $data['status'] == 'For Checking' ? '/in_transit/receive/'.$data['name'] : '/in_transit/transfer/'.$data['name'];
@endphp
<form method="POST" action="{{ $submit_url }}">
    @csrf
    <div class="modal-dialog" style="min-width: 35% !important;">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6F42C1; color: #fff">
                <h5 class="modal-title">Items in Transit <small class="badge {{ $data['status'] == 'For Checking' ? 'badge-warning' : 'badge-success'  }}">{{ $data['status'] }}</small></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-header with-border">
                            <h5 class="box-title">
                                @if ($data['status'] == 'For Checking')
                                    <span>{{ $data['s_warehouse'] }}</span>
                                    <i class="fas fa-angle-double-right mr-2 ml-2"></i>
                                    <span>{{ $data['t_warehouse'] }}</span>
                                @else
                                    <span>Goods in Transit - FI</span>
                                    <i class="fas fa-angle-double-right mr-2 ml-2"></i>
                                    <span>Finished Goods - FI</span>
                                @endif
                            </h5>
                        </div>
                        <div class="d-nones">
                            <input name="child_tbl_id" value="{{ $data['name'] }}">
                            <input name="reference" value="{{ $data['status'] == 'Received' ? $data['ref_no'] : null }}">
                        </div>
                        <div class="box-body" style="font-size: 12pt;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Barcode</label>
                                    <input type="text" class="form-control" name="barcode" placeholder="Barcode" value="{{ $data['validate_item_code'] }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Qty</label>
                                    <input type="text" class="form-control" name="qty" placeholder="Qty" value="{{ $data['qty'] }}" required readonly>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-4 mt-3">
                                            @php
                                                $img = ($data['img']) ? "/img/" . $data['img'] : "/icon/no_img.png";
                                                $img_webp = ($data['img']) ? "/img/" . explode('.', $data['img'])[0].'.webp' : "/icon/no_img.webp";
                                            @endphp
                                            <a href="{{ asset('storage/') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $data['item_code'] }}" data-title="{{ $data['item_code'] }}">
                                                @if(!Storage::disk('public')->exists('/img/'.explode('.', $data['img'])[0].'.webp'))
                                                    <img class="display-block img-thumbnail item_image w-100" src="{{ asset('storage/') }}{{ $img }}">
                                                @elseif(!Storage::disk('public')->exists('/img/'.$data['img']))
                                                    <img class="display-block img-thumbnail item_image w-100" src="{{ asset('storage/') }}{{ $img_webp }}">
                                                @else
                                                    <picture>
                                                        <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                                                        <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                                                        <img src="{{ asset('storage'.$img) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" class="display-block img-thumbnail item_image w-100">
                                                    </picture>
                                                @endif
                                            </a>
                                        </div>
                                        <div class="col-8 mt-3">
                                            <span class="d-block font-weight-bold">{{ $data['item_code'] }}</span>
                                            <small class="d-block text-justify">{{ strip_tags($data['description']) }}</small>
                                            <dl>
                                                <dt>Available Qty</dt>
                                                <dd><span style="font-size: 12pt;" class="badge {{ ($data['available_qty'] > 0) ? 'badge-success' : 'badge-danger' }}">{{ $data['available_qty'] . ' ' . $data['stock_uom'] }}</span></dd>
                                                <dt class="mt-1">Reference No:</dt>
                                                <dd>{{ $data['ref_no'] }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                @if($data['stock_reservation'])
                                <div class="col-md-12 mt-2 p-2">
                                    <div class="callout callout-info p-1 m-0">
                                        <h6 class="m-2 font-weight-bold blink-reservation text-info"><i class="icon fas fa-info-circle"></i> Reservation found on this item</h6>
                                        <dl class="row p-0 m-0" id="sr-d">
                                            <dt class="col-sm-4">Sales Person</dt>
                                            <dd class="col-sm-8">{{ $data['stock_reservation']->sales_person }}</dd>
                                            <dt class="col-sm-4">Project</dt>
                                            <dd class="col-sm-8">{{ $data['stock_reservation']->project }}</dd>
                                            <dt class="col-sm-4">Reserved Qty</dt>
                                            <dd class="col-sm-8">{{ $data['stock_reservation']->reserve_qty - $data['stock_reservation']->consumed_qty }} {{ $data['stock_reservation']->stock_uom }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="ml-auto">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
                    <button type="button" class="btn btn-primary btn-lg" id="btn-check-out"><i class="fa fa-check"></i> {{ $data['status'] === 'For Checking' ? 'CONFIRM' : 'TRANSFER' }}</button>
                </div>
            </div>
        </div>
    </div>
</form>