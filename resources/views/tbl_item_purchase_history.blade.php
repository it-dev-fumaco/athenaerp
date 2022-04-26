<h5 class="m-2">Item Purchase Rate History</h5>
<table class="table table-sm table-striped table-bordered" style="font-size: 9pt;">
    <thead>
        <th class="text-center" style="width: 15%;">Transaction Date</th>
        <th class="text-center" style="width: 15%;">Purchase Order No.</th>
        <th class="text-center d-none d-sm-table-cell" style="width: 40%;">Supplier</th>
        <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Supplier Group</th>
        <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Rate</th>
    </thead>
    <tbody>
        @forelse ($list as $row)
        <tr>
            <td class="text-center">{{ \Carbon\Carbon::parse($row->transaction_date)->format('M-d-Y h:i:A') }}</td>
            <td class="text-center">{{ $row->name }}</td>
            <td class="text-center d-none d-sm-table-cell">{{ $row->supplier }}</td>
            <td class="text-center d-none d-sm-table-cell">{{ $row->supplier_group }}</td>
            <td class="text-center d-none d-sm-table-cell">{{ '₱ ' . number_format($row->base_rate, 2, '.', ',') }}</td>
        </tr>
        <tr class="d-md-none">
            <td colspan="5">
                <table class="table">
                    <tr>
                        <th class="p-1">Supplier:</th>
                        <td class="p-1">{{ $row->supplier }}</td>
                    </tr>
                    <tr>
                        <th class="p-1">Supplier Group:</th>
                        <td class="p-1">{{ $row->supplier_group }}</td>
                    </tr>
                    <tr>
                        <th class="p-1">Rate:</th>
                        <td class="p-1">{{ '₱ ' . number_format($row->base_rate, 2, '.', ',') }}</td>
                    </tr>
                </table>
            </td>
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

