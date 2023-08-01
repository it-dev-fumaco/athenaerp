<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
            <h1><b>Inventory Audit Alert</b></h1>
            <br>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Cutoff Dates: <b>{{ $cutoff_period }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Audit Dates: <b>{{ $audit_period }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Branch Warehouse: <b>{{ $branch_warehouse }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Promodiser: <b>{{ Auth::user()->full_name }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Transaction Date: <b>{{ Carbon\Carbon::parse($transaction_date)->format('F d, Y') }}</b></p>
            <p style="display:block;line-height: 15px; font-size: 14pt;">Reference Number: <b>{{ $reference }}</b></p>
            <br>
            <p style="display:block;line-height:8px;">For more details, please log in to <a href="https://athena.fumaco.org" target="_blank">https://athena.fumaco.org</a></p>
            <p style="display:block;line-height:8px;">Created By: <i>{{ str_replace('.local', '.com', Auth::user()->wh_user) }}</i></p>
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