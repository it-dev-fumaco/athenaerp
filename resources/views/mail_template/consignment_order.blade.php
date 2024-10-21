@php
    $owner = ucwords(str_replace('.', ' ', explode('@', $owner)[0]));
@endphp
<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
            <h3><b>Consignment Order Alert</b></h3>
            <br>
            <p style="display:block;line-height: 15px; font-size: 12pt;">MREQ ID: <b>{{ $name }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 12pt;">Branch: <b>{{ $branch }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 12pt;">Promodiser: <b>{{ $owner }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 12pt;">Transaction Date: <b>{{ Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</b></p>
            <br>
            <table style="border: 1px solid black;border-collapse: collapse; font-size: 10pt;">
                <thead>
                    <tr style="text-align:center">
                        <th style="border: 1px solid black !important; width: 33% !important; padding: 10px">Item</th>
                        <th style="border: 1px solid black !important; width: 33% !important; padding: 10px">Price</th>
                        <th style="border: 1px solid black !important; width: 33% !important; padding: 10px">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr style="border: 1px solid #000 !important">
                            <td style="width: 33% !important; text-align: center">
                                <b>{{ $item['item_code'] }}</b>
                            </td>
                            <td style="width: 33% !important; text-align: center">
                                ₱ {{ number_format($item['rate'], 2) }}
                            </td>
                            <td style="width: 33% !important; text-align: center">
                                {{ number_format($item['qty']) }} <br>
                                <small>{{ $item['uom'] }}</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan=3 style="padding: 10px">
                                <p style="display:block;line-height: 13px; font-size: 10pt;">{{ strip_tags($item['description']) }}</p>
                                <p style="display:block;line-height: 13px; font-size: 10pt;">Reason: <b>{{ $item['consignment_reason'] }}</b></p>
                                @if ($item['remarks'])
                                    <p style="display:block;line-height: 13px; font-size: 10pt;">Remarks: <b>{{ $item['remarks'] }}</b></p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan=2 style="text-align: center">
                                No result(s) found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <br>
            <p style="display:block;line-height:8px;">For more details, proceed to <a href="https://athena.fumaco.org" target="_blank">AthenaERP Inventory</a></p>
            <p style="display:block;line-height:8px;">Created By: <i>{{ $owner }}</i> | Created On: <i>{{ Carbon\Carbon::parse($creation)->format('F d, Y') }}</i</p>
            <hr>
            <b>Fumaco, Inc. / AthenaERP - Consignment </b><br></br><small>Auto Generated E-mail from AthenaERP - DO NOT REPLY
            </small>
        </div>
    </div>
</div>
<style>
    .col-md-12 {
        width: 90%;
    }
</style>