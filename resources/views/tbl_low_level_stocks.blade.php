<table class="table table-bordered">
    <col style="width: 5%;">
    <col style="width: 37%;">
    <col style="width: 28%;">
    <col style="width: 15%;">
    <col style="width: 15%;">
    <thead>
        <th class="text-center">No.</th>
        <th class="text-center">Item Description</th>
        <th class="text-center">Warehouse</th>
        <th class="text-center">Min. Stock Qty</th>
        <th class="text-center">Actual Qty</th>
    </thead>
    <tbody>
        @forelse ($low_level_stocks as $n => $row)
        <tr>
            <td class="text-center">{{ $n + 1 }}</td>
            <td class="text-justify">{{ $row['item_code'] . ' - ' . $row['description'] }} </td>
            <td class="text-center">{{ $row['warehouse'] }}</td>
            <td class="text-center">{{ $row['warehouse_reorder_level'] * 1 . ' ' . $row['stock_uom'] }}</td>
            <td class="text-center">
                <span class="badge badge-{{ ($row['actual_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ $row['actual_qty'] * 1 . ' ' . $row['stock_uom'] }}</span>
            </td>
        </tr>
        @empty
            <tr>
                <td colspan="5">No Record(s) found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="card-footer clearfix" id="low-level-stocks-pagination" style="font-size: 12pt;">
	{{ $low_level_stocks->links() }}
</div>