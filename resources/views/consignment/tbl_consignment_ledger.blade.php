<h5 class="text-center text-uppercase font-weight-bold p-2 m-0">{{ $branchWarehouse }}</h5>
<table class="table table-bordered table-striped" style="font-size: 9pt;">
    <tbody>
        @forelse ($result as $itemCode => $rows)
        @php
            $itemDescription = array_key_exists($itemCode, $itemDescriptions) ? $itemDescriptions[$itemCode] : null;
            $itemDescription = explode(",", strip_tags($itemDescription));
            $description = array_key_exists(0, $itemDescription) ? $itemDescription[0] : '';
            $description .= array_key_exists(1, $itemDescription) ? $itemDescription[1] : '';
            $description .= array_key_exists(2, $itemDescription) ? $itemDescription[2] : '';
            $description .= array_key_exists(3, $itemDescription) ? $itemDescription[3] : '';
        @endphp
        <tr class="bg-gray-dark">
            <td colspan="5" class="p-2">
                <span class="font-weight-bold">{{ $itemCode }}</span> - {!! strip_tags($description) !!}
            </td>
        </tr>
        <tr>
            <th class="text-center p-1 text-uppercase">Transaction Date</th>
            <th class="text-center p-1 text-uppercase">Qty</th>
            <th class="text-center p-1 text-uppercase">Transaction Type</th>
            <th class="text-center p-1 text-uppercase">Reference</th>
            <th class="text-center p-1 text-uppercase">Created By</th>
        </tr>
        @php
            $list = collect($rows)->sortBy('transaction_date')->toArray();
        @endphp
        @foreach ($list as $transactionDate => $r)
        @foreach ($r as $s)
        <tr>
            <td class="p-1 text-center">{{ \Carbon\Carbon::parse($transactionDate)->format('M. d, Y') }}</td>
            <td class="p-1 text-center">
                <span class="{{ $s['type'] == 'Product Sold' ? 'text-danger' : '' }}">{{ $s['qty'] }}</span>
            </td>
            <td class="p-1 text-center">{{ $s['type'] }}</td>
            <td class="p-1 text-center">{{ $s['reference'] }}</td>
            <td class="p-1 text-center">{{ $s['owner'] }}</td>
        </tr>
        @endforeach
        @endforeach
        @empty
        <tr>
            <td class="text-muted text-uppercase text-center">{{ $branchWarehouse ? 'No Transaction(s) Found' : 'Select Branch / Store' }}</td>
        </tr>
        @endforelse
    </tbody>
</table>
