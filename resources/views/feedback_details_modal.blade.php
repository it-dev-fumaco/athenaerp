
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title">Feedback <small class="badge badge-warning">To Receive</small></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="box-header with-border">
                    <span style="font-size: 12pt;" class="box-title">
                        <span>{{ $q['src_warehouse'] }} <i class="fas fa-angle-double-right mr-2 ml-2"></i> {{ $q['fg_warehouse'] }}</span>
                    </span>
                </div>
                <div class="box-body" style="font-size: 12pt;">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Barcode</label>
                            <input type="text" class="form-control col-md-12" name="barcode" placeholder="Barcode">
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4 mt-3">
                                    @php
                                        $img = ($q['img']) ? "/img/" . $q['img'] : "/icon/no_img.png";
                                    @endphp
                                        <a href="{{ asset('storage/') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $q['item_code'] }}" data-title="{{ $q['item_code'] }}">
                                        <img class="display-block img-thumbnail" src="{{ asset('storage/') }}{{ $img }}" style="width: 100%;" class="item_image">
                                    </a>
                                </div>
                                <div class="col-md-8 mt-3">
                                    <span class="d-block font-weight-bold">{{ $q['item_code'] }}</span>
                                    <small class="d-block text-justify">{{ $q['description'] }}</small>
                                    <br/>
                                    <label>Received Quantity</label>
                                    <input type="number" class="form-control" name="fg_completed_qty" placeholder="Quantity" required>
                                    <br/>
                                </div>
                                <div class="col-md-4 float-left p-1">
                                    <dl>
                                        <dt class="mt-1">Reference No:</dt>
                                        <dd>{{ $q['sales_order'] }}{{ $q['material_request'] }}</dd>
                                    </dl>
                                </div>
                                <div class="col-md-4 float-right p-2">
                                    <dl>
                                        <dt>Feedback Qty</dt>
                                        <dd class="badge {{ ($q['qty_to_receive'] > 0) ? 'badge-success' : 'badge-danger' }}" style="font-size: 12pt;">{{ $q['qty_to_receive'] }} {{ $q['stock_uom'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="production_order" value="{{ $q['production_order'] }}">
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
        <button type="submit" class="btn btn-primary btn-lg" id="btn-check-out"><i class="fa fa-check"></i> Submit</button>
    </div>
</div>