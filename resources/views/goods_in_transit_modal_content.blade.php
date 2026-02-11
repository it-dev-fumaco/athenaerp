@php
    $submitUrl = $data['status'] == 'For Checking' ? '/in_transit/receive/'.$data['name'] : '/in_transit/transfer/'.$data['name'];
    $refdoc = explode('-', $data['ref_no'])[0];
@endphp
<form method="POST" action="{{ $submitUrl }}">
    @csrf
    <input type="hidden" value="{{ $refdoc }}" name="reference_doctype">
    <div class="modal-dialog modal-generic-narrow" style="min-width: 35%; max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header {{ $data['status'] == 'For Checking' ? 'bg-primary' : 'bg-info' }}">
                @if ($data['status'] == 'For Checking')
                <h5 class="modal-title">Receive Feedbacked Item</h5>
                @else
                <h5 class="modal-title">Transfer Item to FG Warehouse</h5>
                @endif
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="box-header with-border">
                            <h5 class="box-title">
                                @if ($data['status'] == 'For Checking')
                                    <span>{{ $data['t_warehouse'] }}</span>
                                @else
                                    <span>Goods in Transit - FI</span>
                                    <i class="fas fa-angle-double-right mr-2 ml-2"></i>
                                    <span>Finished Goods - FI</span>
                                @endif
                            </h5>
                        </div>
                        <div class="d-none">
                            <input name="child_tbl_id" value="{{ $data['name'] }}">
                            <input id="ref-no" name="ref_no" value="">
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
                                            <a href="{{ $data['img'] }}" data-toggle="lightbox" data-gallery="{{ $data['item_code'] }}" data-title="{{ $data['item_code'] }}">
                                                <img class="display-block img-thumbnail item_image w-100" src="{{ $data['img'] }}">
                                            </a>
                                        </div>
                                        <div class="col-8 mt-3">
                                            <span class="d-block font-weight-bold">{{ $data['item_code'] }}</span>
                                            <small class="d-block text-justify">{!! $data['description'] !!}</small>
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