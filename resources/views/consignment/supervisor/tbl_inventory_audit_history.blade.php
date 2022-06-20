<table class="table table-bordered table-striped">
    <thead>
        <th class="text-center font-responsive align-middle p-2">Store</th>
        <th class="text-center font-responsive align-middle p-2">Period</th>
        <th class="text-center font-responsive align-middle p-2">Total Item(s)</th>
        <th class="text-center font-responsive align-middle p-2">Sold Qty</th>
        <th class="text-center font-responsive align-middle p-2">Total Value</th>
        <th class="text-center font-responsive align-middle p-2">Action</th>
    </thead>
    <tbody>
        @forelse($list as $row)
        <tr>
            <td class="text-left font-responsive align-middle p-2">{{ $row->branch_warehouse }}</td>
            <td class="text-center font-responsive align-middle p-2">{{ \Carbon\Carbon::parse($row->audit_date_from)->format('F d, Y') }} - {{ \Carbon\Carbon::parse($row->audit_date_to)->format('F d, Y') }}</td>
            <td class="text-center font-responsive align-middle p-2">{{ number_format($row->total_item) }}</td>
            <td class="text-center font-responsive align-middle p-2">{{ number_format($row->total_qty) }}</td>
            <td class="text-center font-responsive align-middle p-2">{{ '₱ ' . number_format($row->total_value, 2) }}</td>
            <td class="text-center font-responsive align-middle p-2">
                <a href="/view_inventory_audit_items/{{ $row->branch_warehouse }}/{{ $row->audit_date_from }}/{{ $row->audit_date_to }}" class="btn btn-info btn-sm" style="width: 70px;"><i class="fas fa-search"></i></a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center font-responsive text-uppercase text-muted p-2">No record(s) found</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="float-left m-2">Total: <b>{{ $list->total() }}</b></div>
<div class="float-right m-2" id="inventory-audit-history-pagination">{{ $list->links('pagination::bootstrap-4') }}</div>