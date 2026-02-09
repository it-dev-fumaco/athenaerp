<div class="col-md-12">
    <div class="row">
        <h3><b>Stock Adjustment Alert</b></h3>
        <br>
        <p style="display:block;line-height:8px;">Reference No.: <b>{{ $referenceNo }}</b></p>
        <p style="display:block;line-height:8px;">Branch: <b>{{ $warehouse }}</b></p>
        <br>
        <br>
        <br>
        <table style="border: 1px solid black;border-collapse: collapse;">
        <thead>
            <tr style="text-align:center">
                <th style="border: 1px solid black !important; width: 50% !important;">Item</th>
                <th style="border: 1px solid black !important; width: 15% !important;">Qty</th>
                <th style="border: 1px solid black !important; width: 15% !important;">Price</th>
                <th style="border: 1px solid black !important; width: 20% !important;">Reason for Adjustment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $item)
                @php
                    $image = isset($images[$item['item_code']]) ? '/img/'.$images[$item['item_code']][0]->image_path : "/icon/no_img.png";
                @endphp
                <tr>
                    <td style="border: 1px solid black !important;text-align:justfy; width: 50% !important;">
                        <div style="width: 10% !important; display: flex; justify-content: center !important; align-items: center !important; float: left !important">
                            <img src="https://athena.fumaco.org/storage/{{ $image }}" style="width: 100%">
                        </div>
                        <div style="width: 89% !important; display: flex; justify-content: center !important; align-items: center !important; float: right !important">
                            <p><b>{{ $item['item_code'] }}</b> - {{ strip_tags($item['item_description']) }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid black !important;text-align:justfy; width: 15% !important; text-align: center">
                        <p style="font-size: 12pt !important;"><b>{{ $item['new_qty'].' '.$item['uom'] }}</b></p>
                        @if ($item['new_qty'] != $item['previous_qty'])
                            <span style="font-size: 10pt !important">Previous Qty: {{ $item['previous_qty'].' '.$item['uom'] }}</span>
                        @endif
                    </td>
                    <td style="border: 1px solid black !important;text-align:justfy; width: 15% !important; text-align: center">
                        <p style="font-size: 12pt !important;"><b>₱ {{ number_format($item['new_price'], 2) }}</b></p>
                        @if ($item['new_price'] != $item['previous_price'])
                            <span style="font-size: 10pt !important">Previous Price: ₱ {{ number_format($item['previous_price'], 2) }}</span>
                        @endif
                    </td>
                    <td style="border: 1px solid black !important;text-align:justfy; width: 15% !important; text-align: center">
                        <p><b>{{ $item['remarks'] }}</p>
                    </td>
                </tr>  
            @endforeach
        </tbody>
        </table>
        <br>
        @if ($notes)
            <p>Additional Notes: {{ $notes }}</p>
        @endif
        <br>
        <br>
        <br>
        <p style="display:block;line-height:8px;">Created by: <b>{{ $createdBy }}</b></p>
        <p style="display:block;line-height:8px;">Created on: <b>{{ $createdAt }}</b></p>
        <p style="display:block;line-height:8px;">For more details, please log in to <a href="https://athena.fumaco.org" target="_blank">https://athena.fumaco.org</a></p>

        <br>
        <hr>
        <b>Fumaco Inc / AthenaERP - Consignment </b><br></br><small>Auto Generated E-mail from AthenaERP - DO NOT REPLY</small>
    
    </div>
</div>
<style>
table, th, td {
    border: 1px solid black;
    width: 100%;
    border-collapse: collapse;
}
th, td {
  padding: 10px;
}

.col-md-12{
    width: 100%;
}
</style>