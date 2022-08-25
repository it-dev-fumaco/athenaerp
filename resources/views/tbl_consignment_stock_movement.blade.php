<table class="table table-bordered table-striped" style="font-size: 9pt;" border="1">
    <thead>
        <th class="text-center p-1 text-uppercase">Transaction Date</th>
        <th class="text-center p-1 text-uppercase">Branch / Store</th>
        <th class="text-center p-1 text-uppercase">Qty</th>
        <th class="text-center p-1 text-uppercase">Transaction Type</th>
        <th class="text-center p-1 text-uppercase">Reference</th>
        <th class="text-center p-1 text-uppercase">Created By</th>
    </thead>
    <tbody>
        @forelse ($result as $s)
        <tr>
            <td class="p-1 text-center">{{ \Carbon\Carbon::parse($s['transaction_date'])->format('M. d, Y') }}</td>
            <td class="p-1 text-center">{{ $s['branch_warehouse'] }}</td>
            <td class="p-1 text-center">
                <span class="{{ $s['type'] == 'Product Sold' ? 'text-danger' : '' }}">{{ $s['qty'] }}</span>
            </td>
            <td class="p-1 text-center">{{ $s['type'] }}</td>
            <td class="p-1 text-center">{{ $s['reference'] }}</td>
            <td class="p-1 text-center">{{ $s['owner'] }}</td>
        </tr>
        @empty
        <tr>
            <td class="text-muted text-uppercase text-center" colspan="6">{{ 'No Transaction(s) Found' }}</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="box-footer clearfix" id="consignment-stock-movement-pagination" style="font-size: 10pt;">
    {{ $result->links() }}
</div>
