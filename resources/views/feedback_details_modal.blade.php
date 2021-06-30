<div class="modal-content">
    <div class="modal-header">
        {{-- <h4 class="modal-title">Feedback <small class="badge {{ ($data['status'] == 'For Checking') ? 'badge-warning' : 'badge-success'  }}">{{ $data['status'] }}</small></h4> --}}
        {{-- <h4 class="modal-title">Feedback <small class="badge badge-warning">{{ $data['status'] }}</small></h4> --}}
        {{-- <h4 class="modal-title">Feedback <small class="badge badge-warning">{{ $q->status }}</small></h4> --}}
        <h4 class="modal-title">Feedback <small class="badge badge-warning">To Receive</small></h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="box-header with-border">
                    <span style="font-size: 12pt;" class="box-title">
                        {{-- <span>{{ $q->fg_warehouse }}</span> --}}
                        <span>{{ $q['src_warehouse'] }} <i class="fas fa-angle-double-right mr-2 ml-2"></i> {{ $q['fg_warehouse'] }}</span>
                        {{-- <span>{{ $data['s_warehouse'] }}</span>
                        <i class="fas fa-angle-double-right mr-2 ml-2"></i>
                        <span>{{ $data['t_warehouse'] }}</span> --}}
                    </span>
                </div>
                <input type="hidden" name="child_tbl_id" value="{{ $q['production_order'] }}">
                {{-- <input type="hidden" name="child_tbl_id" value="{{ $q->production_order }}"> --}}
                {{-- <input type="hidden" name="is_stock_entry" value="1">
                <input type="hidden" name="has_reservation" value="{{ ($data['stock_reservation']) ? 1 : 0 }}">
                <input type="hidden" name="deduct_reserve" value="0"> --}}
                <div class="box-body" style="font-size: 12pt;">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Barcode</label>
                            <input type="text" class="form-control col-md-12" name="barcode" placeholder="Barcode" value="" required>
                            {{-- <input type="text" class="form-control" name="barcode" placeholder="Barcode" value="{{ $data['validate_item_code'] }}" required> --}}
                        </div>
                        {{-- <div class="col-md-6 form-group">
                            <label>Qty</label>
                            <input type="text" class="form-control" name="qty" placeholder="Qty" value="{{ $data['qty'] }}" required>
                        </div> --}}
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4 mt-3">
                                    @php
                                        // $img = ($q->img) ? "/img/" . $q->img : "/icon/no_img.png";
                                        $img = ($q['img']) ? "/img/" . $q['img'] : "/icon/no_img.png";
                                    @endphp
                                        {{-- <a href="{{ asset('storage/') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $q->item_code }}" data-title="{{ $q->item_code }}"> --}}
                                        <a href="{{ asset('storage/') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $q['item_code'] }}" data-title="{{ $q['item_code'] }}">
                                        <img class="display-block img-thumbnail" src="{{ asset('storage/') }}{{ $img }}" style="width: 100%;" class="item_image">
                                    </a>
                                </div>
                                <div class="col-md-8 mt-3">
                                    {{-- <span class="d-block font-weight-bold">{{ $q->item_code }}</span> --}}
                                    <span class="d-block font-weight-bold">{{ $q['item_code'] }}</span>
                                    {{-- <small class="d-block text-justify">{{ $q->description }}</small> --}}
                                    <small class="d-block text-justify">{{ $q['description'] }}</small>
                                    <br/>
                                    <label>Received Quantity</label>
                                    <input type="text" class="form-control" name="qty-received" placeholder="Quantity" value="" required>
                                    <br/>
                                </div>
                                <div class="col-md-4 float-left p-1">
                                    <dl>
                                        <dt class="mt-1">Reference No:</dt>
                                        {{-- <dd>{{ $q->sales_order }}{{ $q->material_request }}</dd> --}}
                                        <dd>{{ $q['sales_order'] }}{{ $q['material_request'] }}</dd>
                                        {{-- <dd>{{ $data['sales_order_no'] ? $data['sales_order_no'] : $data['material_request'] }}</dd> --}}
                                    </dl>
                                </div>
                                <div class="col-md-4 float-right p-2">
                                    <dl>
                                        <dt>Feedback Qty</dt>
                                        {{-- <dd>{{ $q->produced_qty - $q->feedback_qty }}</dd> --}}
                                        <dd class="badge {{ ($q['qty_to_receive'] > 0) ? 'badge-success' : 'badge-danger' }}" style="font-size: 12pt;">{{ $q['qty_to_receive'] }} {{ $q['stock_uom'] }}</dd>
                                        {{-- <dd><span style="font-size: 12pt;" class="badge {{ ($data['available_qty'] > 0) ? 'badge-success' : 'badge-danger' }}">{{ $data['available_qty'] . ' ' . $data['stock_uom'] }}</span></dd> --}}
                                    </dl>
                                </div>
                            </div>
                        </div>
                        {{-- @if($data['stock_reservation'])
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
                        @endif --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="deduct_reserve" value="0">
    <div class="modal-footer">
        {{-- <button type="button" class="btn btn-primary">Save changes</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> --}}
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
        <button type="button" class="btn btn-primary btn-lg" id="btn-check-out"><i class="fa fa-check"></i> Submit</button>
    </div>
</div>

