<h5 class="m-2">Item Purchase Rate History</h5>
<table class="table table-sm table-striped table-bordered">
    <thead>
        <th class="text-center" style="width: 15%;">Transaction Date</th>
        <th class="text-center" style="width: 15%;">Purchase Order No.</th>
        <th class="text-center" style="width: 40%;">Supplier</th>
        <th class="text-center" style="width: 15%;">Supplier Group</th>
        <th class="text-center" style="width: 15%;">Rate</th>
    </thead>
    <tbody>
        @forelse ($list as $row)
        <tr>
            <td class="text-center">{{ \Carbon\Carbon::parse($row->transaction_date)->format('M-d-Y h:i:A') }}</td>
            <td class="text-center">{{ $row->name }}</td>
            <td class="text-center">{{ $row->supplier }}</td>
            <td class="text-center">{{ $row->supplier_group }}</td>
            <td class="text-center">{{ '₱ ' . number_format($row->base_rate, 2, '.', ',') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center font-weight-bold text-muted">No record(s) found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="box-footer clearfix" id="purchase-history-pagination" style="font-size: 16pt;">
    {{ $list->links() }}
</div>

