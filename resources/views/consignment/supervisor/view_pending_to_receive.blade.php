<div class="card-body p-2 col-12">
    <div class="p-2">
        <table class="table table-bordered table-striped" style="font-size: 9pt;">
            <thead class="text-uppercase">
                <th class="text-center align-middle">Reference</th>
                <th class="text-center align-middle">Branch / Store</th>
                <th class="text-center align-middle">MREQ No.</th>
                <th class="text-center align-middle">Delivery Date</th>
                <th class="text-center align-middle">Status</th>
                <th class="text-center align-middle">Action</th>
            </thead>
            <tbody>
                @forelse ($result as $r)
                    @php
                        $mreq = isset($r->mreq->name) ? $r->mreq->name : '-';
                        $target_warehouse = $r->to_warehouse ?? collect($r->items)->pluck('t_warehouse')->first();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">
                            {{ $r->name }} <br>
                            {{ $r->created_by }}
                        </td>
                        <td class="text-center align-middle">{{ $target_warehouse }}</td>
                        <td class="text-center align-middle">{{ $mreq }}</td>
                        <td class="text-center align-middle">{{ $r->delivery_date }}</td>
                        <td class="text-center align-middle">
                            @if ($r->status == 'Received')
                            <span class="badge badge-success" style="font-size: 8pt;">{{ $r->status }}</span>
                            @else
                            <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            <a href="#" data-toggle="modal" data-target="#{{ $r->name }}-modal">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-uppercase text-muted">No record(s) found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex flex-row justify-content-between">
            <div class="m-0" id="to-receive-pagination">
                {{ $list->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
            <div class="m-0">
                Total Records: <b>{{ $list->total() }}</b>
            </div>
        </div>
        @foreach ($result as $r)
        @php
            $mreq = isset($r->mreq->name) ? $r->mreq->name : '-';
        @endphp
        <div class="modal fade" id="{{ $r->name }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document" style="font-size: 10pt;">
                <div class="modal-content">
                    <div class="modal-header bg-navy">
                        <h6 class="modal-title">Received Item(s)</h6>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="/promodiser/receive/{{ $r->name }}" id="{{ $r->name }}-form" class="deliveries-form" data-modal-container="#{{ $r->name }}-modal" method="get">
                            <h5 class="text-center font-responsive font-weight-bold m-0">{{ $r->warehouse }}</h5>
                            <div class="row mt-2 mb-2">
                                <div class="col-6 pl-5">
                                    <p class="m-1 font-details">Reference STE: <span class="font-weight-bold">{{ $r->name }}</span></p>
                                    <p class="m-1 font-details">Delivery Date: <span class="font-weight-bold">{{ $r->delivery_date }}</span></p>
                                    <p class="m-1 font-details">Created By: <span class="font-weight-bold">{{ $r->created_by }}</span></p>
                                </div>
                                <div class="col-6 pl-5" >
                                    <p class="m-1 font-details">MREQ No.: <span class="font-weight-bold">{{ $mreq }}</span></p>
                                    <p class="m-1 font-details">Status: <span class="badge badge-{{ $r->status == 'Received' ? 'success' : 'warning' }}" style="font-size: 8pt;">{{ $r->status == 'Received' ? $r->status : 'To Receive' }}</span> 
                                    </p>
                                </div>
                            </div>
                            <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                <thead>
                                    <th class="text-center text-uppercase p-1 align-middle" style="width: 55%">Item Code</th>
                                    <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Delivered Qty</th>
                                    <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Rate</th>
                                    <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Amount</th>
                                </thead>
                                <tbody>
                                    @foreach ($r->items as $i)
                                    @php
                                        $target = $r->name.'-'.$i->item_code;
                                    @endphp
                                    <tr>
                                        <td class="text-left p-1 align-middle">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ asset("storage/$i->image") }}" data-toggle="mobile-lightbox" data-gallery="{{ $i->item_code }}" data-title="{{ $i->item_code }}">
                                                        <img src="{{ asset("storage/$i->image") }}" alt="{{ Str::slug($i->description, '-') }}" width="40" height="40">
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="d-block"><b>{{ $i->item_code }}</b> {{ strip_tags($i->description) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <span class="d-block font-weight-bold">{{ number_format($i->transfer_qty) }}</span>
                                            <small>{{ $i->stock_uom }}</small>
                                            <input type="text" name="item_codes[]" value="{{ $i->item_code }}" class="d-none">
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <div class="row">
                                                @if ($r->status == 'Received')
                                                    <div class="col-6 mx-auto text-center">
                                                        <span class="font-weight-bold">
                                                            ₱ {{ number_format($i->price, 2) }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="col-1 d-flex flex-row justify-content-center align-items-center">
                                                        <span class="w-100 text-right">₱</span></div>
                                                    <div class="col-10">
                                                        <input type="text" value="{{ number_format($i->price, 2) }}" name="price[{{ $i->item_code }}]" class="form-control text-center price-input {{ $r->name }}-price" data-target="amount-{{ $target }}" data-qty="{{ number_format($i->transfer_qty) }}">
                                                    </div>  
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <span class="d-block font-weight-bold">₱ <span id="amount-{{ $target }}">{{ number_format($i->amount, 2) }}</span></span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($r->status != 'Received')
                                <input type="checkbox" name="receive_delivery" class="d-none" checked>
                                <button type="button" class="btn btn-primary w-100 submit-btn" data-reference="{{ $r->name }}">Receive</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>  
        @endforeach
    </div>
</div>

<script>
    $('#consignment-store-select').select2({
        placeholder: "Select Store",
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
    
    $(document).on('keyup', '.price-input', function (){
        var target = $(this).data('target');
        var price = $(this).val().replace(/,/g, '');
        var qty = parseInt($(this).data('qty'));
        if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
            var total_amount = price * qty;

            const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
            $('#' + target).text(amount);
        }else{
            $('#' + target).text('0');
        }
    });
</script>