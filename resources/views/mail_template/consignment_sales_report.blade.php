<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
            <h1><b>Sales Report Alert</b></h1>
            <br>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Sales Report for: <b>{{ $month.'-'.$year }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Branch Warehouse: <b>{{ $warehouse }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Total Amount: <b>₱ {{ number_format($total_amount, 2) }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Promodiser: <b>{{ Auth::user()->full_name }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Transaction Date: <b>{{ Carbon\Carbon::parse($date_submitted)->format('F d, Y') }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Submission Status: <b>{{ $submission_status }}</b></p>
            @if ($remarks)
                <p style="display:block;line-height: 15px; font-size: 14pt;">Remarks: <b>{{ $remarks }}</b></p>
            @endif
            <br>
            <br>
            <p style="display:block;line-height:8px;">For more details, please log in to <a href="https://athena.fumaco.org" target="_blank">https://athena.fumaco.org</a></p>
            <p style="display:block;line-height:8px;">Created By: <i>{{ Auth::user()->wh_user }}</i></p>
            <br>
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