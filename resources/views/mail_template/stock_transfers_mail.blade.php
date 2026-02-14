@php
    $ste = isset($data['ste_details']) ? $data['ste_details'] : [];
    $items = isset($data['items']) ? $data['items'] : [];
    if ($ste['docstatus'] == 1) {
        if($ste['purpose'] == 'Material Receipt'){
            $status = 'Returned';
        }else if($ste['status']){
            $status = 'Received';
        }else{
            $status = 'To Receive';
        }
    }else{
        $status = 'For Approval';
    }

    if($ste['purpose'] == 'Material Receipt'){
        $purpose = 'Sales Return';
    }else{
        if ($ste['transfer_as'] == 'Store Transfer') {
            $purpose = 'Stock Transfer Request';
        }else{
            $purpose = 'For Return';
        }
    }
@endphp
<div class="col-md-12 border border-secondary">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
        <h3><b>{{ $purpose }}</b></h3>
        <br>
        <table style="font-size: 9pt; width: 100%">
            <tr>
                <td style="width: 50%;">  
                    <p style="display:block;line-height:8px;">Reference No.: <b>{{ $ste['id'] }}</b></p>
                    <p style="display:block;line-height:8px;">Status: <b>{{ $status }}</b></p>
                    <p style="display:block;line-height:8px;">Date: <b>{{ Carbon\Carbon::parse($ste['transaction_date'])->format('F d, Y - h:i A') }}</b></p>
                </td>
                <td style="width: 50%;">
                    @if ($purpose == 'Sales Return')
                        <p style="display:block;line-height:8px;">Warehouse: <b>{{ $ste['target_warehouse'] }}</b></p>
                    @else
                        <p style="display:block;line-height:8px;">Source Warehouse: <b>{{ $ste['source_warehouse'] }}</b></p>
                        <p style="display:block;line-height:8px;">Target Warehouse: <b>{{ $ste['target_warehouse'] }}</b></p>
                    @endif
                </td>
            </tr>
        </table>
        <br>
        <table class="bordered" style="border: 1px solid black;border-collapse: collapse; width: 100%; font-size: 9pt;">
            <thead>
                <colgroup>
                    <col style="width: 70%;">
                    <col style="width: 30%;">
                </colgroup>
                <tr style="text-align:center">
                    <th style="border: 1px solid black;">Item Details</th>
                    <th style="border: 1px solid black;">Transfer Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td style="border: 1px solid black;text-align:justfy;">
                            <div style="display: inline-block; float: left; width: 10%;">
                                <img src="{{ Storage::disk('upcloud')->url('img/'.$item['image']) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $item['image'])[0], '-') }}" alt="User Image" style="width: 100%;">
                            </div>
                            <div style="display: inline-block; float: right; width: 89%;">
                                <b>{{ $item['item_code'] }}</b> - {!! $item['description'] !!}
                                @if ($purpose == 'Sales Return')
                                    <br><br>
                                    Reason: {{ $item['return_reason'] }}
                                @endif  
                            </div>
                        </td>
                        <td style="border: 1px solid black;text-align:center;">
                            <b>{{ $item['transfer_qty'] }}</b> <br>
                            {{ $item['uom'] }}
                        </td>
                    </tr>    
                @endforeach
            </tbody>
        </table>
        
        <p style="display:block;line-height:8px;">For more details, please log in to http://10.0.0.83</p>
        <p style="display:block;line-height:8px;">Submitted By: <i>{{ $ste['user'] }}</i></p>
        <br>
        <hr>
        <b>Fumaco Inc / AthenaERP for Consignment - Stock Transfer </b><br></br><small>Auto Generated E-mail from AthenaERP - NO REPLY </small>
        </div>
    
    </div>
</div>
<style>
.bordered,.bordered th,.bordered td {
  border: 1px solid black;
  border-collapse: collapse;
}
.bordered th, .bordered td {
  padding: 10px;
}
</style>