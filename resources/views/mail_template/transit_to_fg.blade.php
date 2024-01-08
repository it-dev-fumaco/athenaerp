<div class="col-md-12 border border-secondary">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
        <h3><b>{{ $purpose }}</b></h3>
        <br>
        <table style="font-size: 9pt; width: 100%">
            <tr>
                <td style="width: 50%;">  
                    <p style="display:block;line-height:8px;">Reference No.: <b>{{ $id }}</b></p>
                    <p style="display:block;line-height:8px;">Status: <b>{{ $status }}</b></p>
                    <p style="display:block;line-height:8px;">Date: <b>{{ Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</b></p>
                </td>
                <td style="width: 50%;">
                    <p style="display:block;line-height:8px;">Source Warehouse: <b>{{ $source_warehouse }}</b></p>
                    <p style="display:block;line-height:8px;">Target Warehouse: <b>{{ $target_warehouse }}</b></p>
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
                <tr>
                    <td style="border: 1px solid black;text-align:justfy;">
                        <div style="display: inline-block; float: left; width: 10%;">
                            <img src="{{ asset('storage/img/'.$image) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $image)[0], '-') }}" alt="User Image" style="width: 100%;">
                        </div>
                        <div style="display: inline-block; float: right; width: 89%;">
                            <b>{{ $item_code }}</b> - {!! $description !!}
                        </div>
                    </td>
                    <td style="border: 1px solid black;text-align:center;">
                        <b>{{ (float)$transfer_qty }}</b> <br>
                        {{ $uom }}
                    </td>
                </tr>    
            </tbody>
        </table>
        
        <p style="display:block;line-height:8px;">For more details, please log in to <a href="http://erp.fumaco.local">http://erp.fumaco.local</a></p>
        <p style="display:block;line-height:8px;">Submitted By: <i>{{ $user }}</i></p>
        <br>
        <hr>
        <b>Fumaco Inc / AthenaERP </b><br></br><small>Auto Generated E-mail from AthenaERP - NO REPLY </small>
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