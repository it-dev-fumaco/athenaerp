<div class="col-md-12">
    <div class="row">
        <div class="col-md-12" style="margin-left:10%;margin-right:10%;">
            <h3><b>AthenaERP - List of promodisers who did not submit their inventory audit</b></h3>
            <br>
            <p style="display:block;line-height: 15px; font-size: 12pt;">Cutoff Dates: <b>{{ $cutoffDates }}</b></p>
            <table style="border: 1px solid black;border-collapse: collapse; font-size: 10pt;">
                <thead>
                    <tr style="text-align:center">
                        <th style="border: 1px solid black !important; width: 33% !important;">Promodiser</th>
                        <th style="border: 1px solid black !important; width: 33% !important;">Assigned Consignment Warehouse</th>
                        <th style="border: 1px solid black !important; width: 33% !important;">Last Submission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user => $details)
                        @php
                            $i = 1;
                        @endphp
                        <tr>
                            <th rowspan={{ count($details) * 2 }} style="border: 1px solid black !important; width: 33% !important;">
                                {{ $user }}
                            </th>
                        </tr>
                            @foreach ($details as $detail)
                                <tr>
                                    <td style="border: 1px solid black !important;text-align:justify; width: 33% !important;">
                                        {{ $detail['warehouse'] }}
                                    </td>
                                    <td style="border: 1px solid black !important;text-align:justify; width: 33% !important;">
                                        {{ $detail['last_audit'] }}
                                    </td>
                                <tr>
                            @endforeach
                    @empty
                        <tr>
                            <td colspan=2 style="text-align: center">
                                No result(s) found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <p style="display:block;line-height: 15px; font-size: 12pt;">Total: <b>{{ count($users->keys()) }}</b></p>

            <br>
            <hr>
            <b>Fumaco, Inc. / AthenaERP - Consignment </b><br></br><small>Auto Generated E-mail from AthenaERP - DO NOT REPLY
            </small>
        </div>

    </div>
</div>
<style>
    table,
    th,
    td {
        border: 1px solid black;
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 10px;
    }

    .col-md-12 {
        width: 90%;
    }
</style>