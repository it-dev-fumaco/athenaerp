<table class="table" style='font-size: 10pt;'>
    <tbody>
        @forelse ($steArr as $ste)
        <tr>
            <td class="p-2">
                <a href="#" data-toggle="modal" data-target="#{{ $ste->name }}-Modal">{{ $ste->to_warehouse }}</a>
                <small class="d-block"><b>{{ $ste->name }}</b> | <b>Delivery Date:</b> {{ Carbon\Carbon::parse($ste->delivery_date)->format('M d, Y').' - '.Carbon\Carbon::parse($ste->posting_time)->format('h:i a') }}</small>
                <span class="badge badge-{{ $ste->status == 'Pending' ? 'warning' : 'success' }}">{{ $ste->status }}</span>

                @if ($ste->status == 'Delivered')
                    <span class="badge badge-{{ $ste->consignment_status !== 'Received' ? 'warning' : 'success' }}">{{ $ste->consignment_status !== 'Received' ? 'To Receive' : 'Received' }}</span>
                @endif

                <div class="modal fade" id="{{ $ste->name }}-Modal" tabindex="-1" role="dialog"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <form action="/promodiser/receive/{{ $ste->name }}" id="receive-{{ $ste->name }}-form" class="receive-form"
                                data-modal-container="#{{ $ste->name }}-Modal" method="get">
                                <div class="modal-header bg-navy">
                                    <h6 class="modal-title">Incoming Item(s)</h6>
                                    <button type="button" class="close text-white" data-dismiss="modal"
                                        aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <h5 class="text-center font-responsive font-weight-bold m-0">{{
                                        $ste->target_warehouse }}</h5>
                                    <small class="d-block text-center mb-2">{{ $ste->name }} | Delivery Date: {{
                                        Carbon\Carbon::parse($ste->delivery_date)->format('M d, Y').' -
                                        '.Carbon\Carbon::parse($ste->posting_time)->format('h:i a') }}</small>
                                    <div class="callout callout-info text-center">
                                        <small><i class="fas fa-info-circle"></i> Once items are received, stocks will
                                            be automatically added to your current inventory.</small>
                                    </div>
                                    <table class="table" style="font-size: 9pt;">
                                        <thead>
                                            <th class="text-center p-1 align-middle" style="width: 40%">Item Code</th>
                                            <th class="text-center p-1 align-middle" style="width: 30%">Delivered Qty
                                            </th>
                                            <th class="text-center p-1 align-middle" style="width: 30%">Rate</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($ste->items as $item)
                                            @php
                                            $id = $ste->name.'-'.$item->item_code;
                                            $img = Storage::disk(upcloud)->url($item->image");
                                            @endphp
                                            <tr>
                                                <td class="text-left p-1 align-middle"
                                                    style="border-bottom: 0 !important;">
                                                    <div class="d-flex flex-row justify-content-start align-items-center">
                                                        <div class="p-1 text-left">
                                                            <a href="{{ $img }}" class="view-images" data-item-code="{{ $item->item_code }}">
                                                                <img src="{{ $img }}" alt="{{ Illuminate\Support\Str::slug($item->description, '-') }}" width="40" height="40">
                                                            </a>
                                                        </div>
                                                        <div class="p-1 m-0">
                                                            <span class="font-weight-bold">{{ $item->item_code }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center p-1 align-middle">
                                                    <span class="d-block font-weight-bold">{{ $item->transfer_qty }}</span>
                                                    <span class="d-none font-weight-bold" id="{{ $item->item_code }}-qty">{{ $item->transfer_qty }}</span>
                                                    <small>{{ $item->stock_uom }}</small>
                                                </td>
                                                <td class="text-center p-1 align-middle">
                                                    <input type="text" name="item_codes[]" class="d-none"
                                                        value="{{ $item->item_code }}" />
                                                    <input type="text" value='{{ $item->price > 0 ?
                                                    number_format($item->price, 2) : null }}' class='form-control
                                                    text-center price' name='price[{{ $item->item_code }}]'
                                                    data-item-code='{{ $item->item_code }}' placeholder='0' required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-justify pt-0 pb-1 pl-1 pr-1"
                                                    style="border-top: 0 !important;">
                                                    <span class="item-description">{!! strip_tags($item->description)
                                                        !!}</span> <br>
                                                    Amount: ₱ <span id="{{ $item->item_code }}-amount"
                                                        class='font-weight-bold amount'>{{
                                                        number_format($item->delivered_qty * $item->price, 2)
                                                        }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    @if ($ste->status == 'Delivered' && $ste->delivery_status == 0)
                                    <input type="checkbox" name="receive_delivery" class="d-none" checked readonly>
                                    <button type="submit" class="btn btn-primary w-100 submit-btn submit-once" data-form="#receive-{{ $ste->name }}-form" data-modal-container="#{{ $ste->name }}-Modal">Receive</button>
                                    @else
                                    <button type="submit" class="btn btn-info w-100" data-form="#receive-{{ $ste->name }}-form">Update Prices</button>
                                    <input type="checkbox" class="d-none" name="update_price" checked readonly>
                                    <button type="button" class="btn btn-secondary w-100 submit-once" data-toggle="modal"
                                        data-target="#cancel-{{ $ste->name }}-Modal">
                                        Cancel
                                    </button>

                                    <div class="modal fade" id="cancel-{{ $ste->name }}-Modal" tabindex="-1"
                                        role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-navy">
                                                    <h5 class="modal-title" id="exampleModalLabel">Cancel</h5>
                                                    <button type="button" class="close"
                                                        onclick="close_modal('#cancel-{{ $ste->name }}-Modal')">
                                                        <span aria-hidden="true" style="color: #fff;">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    Cancel {{ $ste->name }}?
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="/promodiser/cancel/received/{{ $ste->name }}"
                                                        class="btn btn-primary w-100 submit-once">Confirm</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td class="text-center text-uppercase text-muted align-middle">No incoming deliveries</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="mt-3" id="delivery-report-pagination" style="font-size: 9pt">
    {{ $deliveryReport->appends(request()->query())->links('pagination::bootstrap-4') }}
</div>
<script>
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

    $('.price').keyup(function(){
        var item_code = $(this).data('item-code');
        var price = $(this).val().replace(/,/g, '');
        if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
            var qty = parseInt($('#'+item_code+'-qty').text());
            var total_amount = price * qty;

            const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
            $('#'+item_code+'-amount').text(amount);
        }else{
            $('#'+item_code+'-amount').text('0');
        }
    });

    var showTotalChar = 150, showChar = "Show more", hideChar = "Show less";
    $('.item-description').each(function() {
      var content = $(this).text();
      if (content.length > showTotalChar) {
          var con = content.substr(0, showTotalChar);
          var hcon = content.substr(showTotalChar, content.length - showTotalChar);
          var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
          $(this).html(txt);
      }
    });

    $(".show-more").click(function(e) {
      e.preventDefault();
      if ($(this).hasClass("sample")) {
          $(this).removeClass("sample");
          $(this).text(showChar);
      } else {
          $(this).addClass("sample");
          $(this).text(hideChar);
      }

      $(this).parent().prev().toggle();
      $(this).prev().toggle();
      return false;
    });
</script>