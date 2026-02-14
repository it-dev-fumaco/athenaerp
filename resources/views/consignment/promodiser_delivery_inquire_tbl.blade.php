@if (count($deliveryReport) > 0)
@php
    $steDetails = collect($deliveryReport)->first();
    $status = "Pending";
    if($steDetails && $steDetails->item_status == 'Issued' && now() > $steDetails->delivery_date){
        $status = 'Delivered';
    }

    $deliveryStatus = collect($deliveryReport)->map(function($q){
        return $q->consignment_status ? 1 : 0;
    })->min();
@endphp
<div class="row">
<form action="/promodiser/receive/{{ $steDetails->name }}" id="receive-form" class="w-100" method="get">
    <div class="container text-center">
        <br>
        <h5 class="text-center font-responsive font-weight-bold m-0">{{ $steDetails->t_warehouse }}</h5>
        <small class="d-block text-center mb-2">{{ $steDetails->name }} | Delivery Date: {{ Carbon\Carbon::parse($steDetails->delivery_date)->format('M d, Y') }}</small>
        @if ($steDetails->consignment_status == 'Received')
            <small class="d-block"><b>Date Received:</b> {{ Carbon\Carbon::parse($steDetails->consignment_date_received)->format('M d, Y - h:i a') }}</small>
        @endif
        <div class="callout callout-info text-center">
            <small><i class="fas fa-info-circle"></i> Once items are received, stocks will be automatically added to your current inventory.</small>
        </div>
        <table class="table" style="font-size: 9pt;">
            <thead>
                <th class="text-center p-1 align-middle" style="width: 40%">Item Code</th>
                <th class="text-center p-1 align-middle" style="width: 30%">Delivered Qty</th>
                <th class="text-center p-1 align-middle" style="width: 30%">Rate</th>
            </thead>
            <tbody>
                @foreach ($deliveryReport as $item)
                @php
                    $img = isset($itemImages[$item->item_code]) ? $itemImages[$item->item_code] : $itemImages['no_img'];
                @endphp
                <tr>
                    <td class="text-left p-1 align-middle" style="border-bottom: 0 !important;">
                        <div class="d-flex flex-row justify-content-start align-items-center">
                            <div class="p-1 text-left">
                                <a href="{{ $img }}" class="view-images" data-item-code="{{ $item->item_code }}">
                                    <img src="{{ $img }}" alt="{{ Illuminate\Support\Str::slug(strip_tags($item->description), '-') }}" width="40" height="40">
                                </a>
                            </div>
                            <div class="p-1 m-0">
                                <span class="font-weight-bold">{{ $item->item_code }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center p-1 align-middle">
                        <span class="d-block font-weight-bold">{{ number_format($item->transfer_qty * 1) }}</span>
                        <span class="d-none font-weight-bold" id="{{ $item->item_code }}-qty">{{ $item->transfer_qty * 1 }}</span>
                        <small>{{ $item->stock_uom }}</small>
                    </td>
                    <td class="text-center p-1 align-middle">
                        <input type="text" name="item_codes[]" class="d-none" value="{{ $item->item_code }}"/>
                        <input type="text" value='{{ $item->basic_rate > 0 ? number_format($item->basic_rate, 2) : null }}' class='form-control text-center price price-input' name='price[{{ $item->item_code }}]' data-target='{{ $item->item_code }}' data-qty="{{ $item->transfer_qty * 1 }}" placeholder='0' required>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
                        <span class="item-description">{!! $item->description !!}</span> <br>
                        Amount: ₱ <span id="{{ $item->item_code }}-amount" min='1' class='font-weight-bold amount'>{{ number_format($item->transfer_qty * $item->basic_rate, 2) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="row p-2">
            @if ($status == 'Delivered' && $deliveryStatus == 0)
                <input type="checkbox" name="update_price" class="d-none" readonly>
                <input type="checkbox" name="receive_delivery" class="d-none" checked readonly>
                <button type="submit" class="btn btn-primary w-100 submit-once">Receive</button>
            @else
                <input type="checkbox" name="update_price" class="d-none" checked readonly>
                <input type="checkbox" name="receive_delivery" class="d-none" readonly>
                <button type="submit" class="btn btn-info w-100 submit-once mb-2">Update Prices</button>
                <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-Modal">
                    Cancel
                </button>
                    
                <div class="modal fade" id="cancel-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Cancel</h5>
                                <button type="button" class="close" onclick="close_modal('#cancel-Modal')">
                                <span aria-hidden="true" style="color: #fff;">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Cancel {{ $steDetails->name }}?
                            </div>
                            <div class="modal-footer">
                                <a href="/promodiser/cancel/received/{{ $steDetails->name }}" class="btn btn-primary w-100 submit-once">Confirm</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>
</div>
@else
    <h5 class="p-2">Please enter the STE number</h5>
    <script>
        $(document).ready(function(){
            showNotification("danger", 'STE not found.', "fa fa-info");

            function showNotification(color, message, icon){
                $.notify({
                    icon: icon,
                    message: message
                },{
                    type: color,
                    timer: 500,
                    z_index: 1060,
                    placement: {
                        from: 'top',
                        align: 'center'
                    }
                });
            }
        });
    </script>
@endif

<script>
    $(document).on('keyup', '.price-input', function (){
        var target = $(this).data('target');
        var price = $(this).val().replace(/,/g, '');
        var qty = parseInt($(this).data('qty'));
        if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
            var total_amount = price * qty;
            console.log(total_amount);

            const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
            $('#' + target + '-amount').text(amount);
        }else{
            $('#' + target + '-amount').text('0');
        }
    });
</script>