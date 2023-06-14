@if (!collect($items)->min('active'))
    <div class="alert alert-warning font-weight-bold text-center w-100">
        <span>Rows highlighted in red are not assigned to any item in ERP. Please assign these barcodes to items in ERP to proceed.</span>
    </div>   
@endif
<div class="row">
    <div class="col-6">
        <p><b>Project:</b> {{ $project }}</p>
        <p><b>Branch Warehouse:</b> {{ $branch }}</p>
    </div>
    <div class="col-6">
        <p><b>Customer Name:</b> {{ $customer }}</p>
        <p><b>Customer's Purchase Order:</b> {{ $customer_purchase_order }}</p>
    </div>
    <div class="col-12">
        <table class="table table-bordered" style="font-size: 9pt;">
            <col style="width: 10%;">
            <col style="width: 60%;">
            <col style="width: 10%;">
            <col style="width: 10%;">
            <col style="width: 10%;">
            <thead>
                <tr>
                    <th class="p-2 text-center">No.</th>
                    <th class="p-2">Description</th>
                    <th class="p-2 text-center">Qty</th>
                    <th class="p-2 text-center">Price</th>
                    <th class="p-2 text-center">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $i = 1;
                @endphp
                @foreach ($items as $item_code => $item)
                    @php
                        $price = $item['sold'] > 0 ? ($item['amount'] / $item['sold']) : 0;
                    @endphp
                    <tr class="{{ !$item['active'] ? 'inactive-item' : null }}">
                        <td class="p-2 text-center">{{ $i++ }}</td>
                        <td class="p-2">
                            <b>{{ $item_code }}</b><br>
                            {!! $item['description'] !!}
                        </td>
                        <td class="p-2 text-center">
                            <b class="d-block">{{ $item['sold'] }}</b>
                            <small>{{ $item['uom'] }}</small>
                        </td>
                        <td class="p-2 text-center">₱ {{ number_format($price, 2) }}</td>
                        <td class="p-2 text-center">₱ {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan=2 class="text-right">Total</td>
                    <td class="text-center font-weight-bold">{{ number_format(collect($items)->sum('sold')) }}</td>
                    <td></td>
                    <td class="text-center font-weight-bold">₱ {{ number_format(collect($items)->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    .inactive-item{
        background-color: rgba(220, 53, 69, .5)
    }
</style>