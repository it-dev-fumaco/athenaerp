<table class="table table-striped">
    <thead>
        <tr>
            <th class="text-center">ID</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Reservation Type</th>
            <th class="text-center">Date Reserved</th>
            <th class="text-center">Validity</th>
            <th class="text-center">Status</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($list as $row)
        @php
            if ($row->status == 'Active') {
                $badge = 'badge-primary';
            }elseif ($row->status == 'Expired') {
                $badge = 'badge-warning';
            }elseif ($row->status == 'Cancelled') {
                $badge = 'badge-danger';
            } else {
                $badge = 'badge-success';
            }
        @endphp
        <tr>
            <td class="text-center align-middle">{{ $row->name }}</td>
            <td class="text-center align-middle">{{ $row->reserve_qty . ' ' . $row->stock_uom }}</td>
            <td class="text-center align-middle">{{ $row->warehouse }}</td>
            <td class="text-center align-middle">{{ $row->type }}</td>
            <td class="text-center align-middle">{{ date('Y-m-d', strtotime($row->creation)) }}</td>
            <td class="text-center align-middle">{{ ($row->valid_until) ? $row->valid_until : '-' }}</td>
            <td class="text-center align-middle">
                <span class="badge {{ $badge }}" style="font-size: 10pt;">{{ $row->status }}</span>
            </td>
            <td class="text-center align-middle">{{ $row->created_by }}</td>
            <td class="text-center align-middle">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                    $attr_cancelled = ($row->status == 'Cancelled') ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info edit-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Update</button>
                <button type="button" class="btn btn-danger cancel-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Cancel</button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center">No record(s) found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="box-footer clearfix" id="stock-reservations-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $list->links() }}
</div>