<div class="container-fluid bg-white" style="font-size: 9pt;">
    <div class="row border border-danger">
        <div class="col-2 p-3 bg-navy border border-danger">
            <b>Branch Warehouse</b>
        </div>
        <div class="col-9 p-3 bg-navy border border-danger">
            <b>Monthly Sales</b>
        </div>
        <div class="col-1 p-3 bg-navy border border-danger">
            <b>Total Amount</b>
        </div>
    </div>
    @foreach ($report as $warehouse => $arr)
    <div class="row border border-danger">
        <div class="col-2 d-flex justify-content-center align-items-center p-2 border border-danger">
            <b>{{ $warehouse }}</b>
        </div>
        <div class="col-9 p-2 border border-danger" style="max-width: 100%; overflow-x: auto;">
            <table class="table table-bordered">
                <tr>
                    @foreach ($arr as $date)
                        <th class="text-center" style="width: 200px;">{{ Carbon\Carbon::parse($date['date'])->format('M-d-Y') }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($arr as $date)
                        <td class="text-center" style="width: 200px; white-space: nowrap">₱ {{ number_format($date['amount'], 2) }}</td>
                    @endforeach
                </tr>
            </table>
        </div>
        <div class="col-1 d-flex justify-content-center align-items-center p-2 border border-danger">
            <b>₱ {{ number_format(collect($arr)->sum('amount'), 2) }}</b>
        </div>
    </div>
    @endforeach
</div>