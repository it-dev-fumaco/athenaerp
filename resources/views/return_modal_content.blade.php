<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title">Sales Return <small class="badge {{ ($data['status'] == 'For Checking') ? 'badge-warning' : 'badge-success'  }}">{{ $data['status'] }}</small></h4>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="box-header with-border">
                    <h4 class="box-title">
                        <span>{{ $data['t_warehouse'] }}</span>
                    </h4>
                </div>
                <input type="hidden" name="child_tbl_id" value="{{ $data['name'] }}">
                <input type="hidden" name="is_stock_entry" value="1">
                <input type="hidden" name="has_reservation" value="0">
                <div class="box-body" style="font-size: 12pt;">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Barcode</label>
                            <input type="text" class="form-control" name="barcode" placeholder="Barcode" value="{{ $data['validate_item_code'] }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Qty</label>
                            <input type="text" class="form-control" name="qty" placeholder="Qty" value="{{ $data['qty'] }}" required>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4 mt-3">
                                    @php
                                        $img = ($data['img']) ? "/img/" . $data['img'] : "/icon/no_img.png";
                                    @endphp
                                    <a href="{{ asset('storage/') . '' . $img }}" data-toggle="lightbox" data-gallery="{{ $data['item_code'] }}" data-title="{{ $data['item_code'] }}">
                                        <img class="display-block img-thumbnail" src="{{ asset('storage/') }}{{ $img }}" style="width: 100%;" class="item_image">
                                    </a>
                                </div>
                                <div class="col-md-8 mt-3">
                                    <span class="d-block font-weight-bold">{{ $data['item_code'] }}</span>
                                    <small class="d-block text-justify">{{ $data['description'] }}</small>
                                    <dl>
                                        <dt>Actual Qty</dt>
                                        <dd><span style="font-size: 12pt;" class="badge {{ ($data['available_qty'] > 0) ? 'badge-success' : 'badge-danger' }}">{{ $data['available_qty'] . ' ' . $data['stock_uom'] }}</span></dd>
                                        <dt class="mt-1">Reference No:</dt>
                                        <dd>{{ $data['ref_no'] }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="deduct_reserve" value="0">
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
        <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> CHECK IN</button>
    </div>
</div>

