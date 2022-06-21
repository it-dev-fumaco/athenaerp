<table class="table table-bordered table-striped" style="font-size: 10pt;">
    <thead>
        <th class="text-center font-responsive align-middle p-2 d-none d-lg-table-cell">Store</th>
        <th class="text-center font-responsive align-middle p-2">Period</th>
        <th class="text-center font-responsive align-middle p-2 d-none d-lg-table-cell">Promodiser(s)</th>
        <th class="text-center font-responsive align-middle p-2 d-none d-lg-table-cell">Total Item(s)</th>
        <th class="text-center font-responsive align-middle p-2">Action</th>
    </thead>
    <tbody>
        @forelse($list as $row)
        <tr>
            <td class="text-left font-responsive align-middle p-2 d-none d-lg-table-cell">{{ $row->branch_warehouse }}</td>
            <td class="text-left text-lg-center font-responsive align-middle p-2">
                {{ \Carbon\Carbon::parse($row->cutoff_period_from)->format('F d, Y') }} - {{ \Carbon\Carbon::parse($row->cutoff_period_to)->format('F d, Y') }}
                <div class="d-block d-lg-none">
                    {{ $row->branch_warehouse }} <br>
                    <b>Promodiser(s): </b> {{ $row->promodisers }} <br>
                    <b>Total Item(s): </b> {{ number_format($row->total_item) }}
                </div>
            </td>
            <td class="text-center font-responsive align-middle p-2 d-none d-lg-table-cell">{{ $row->promodisers }}</td>
            <td class="text-center font-responsive align-middle p-2 d-none d-lg-table-cell">{{ number_format($row->total_item) }}</td>
            <td class="text-center font-responsive align-middle p-2">
                <a href="/view_product_sold_items/{{ $row->branch_warehouse }}/{{ $row->cutoff_period_from }}/{{ $row->cutoff_period_to }}" class="btn btn-info btn-sm" style="width: 70px;"><i class="fas fa-search"></i></a>
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