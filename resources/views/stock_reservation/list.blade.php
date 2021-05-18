<table class="table table-striped">
    <thead>
        <tr>
            <th class="text-center">ID.</th>
            <th class="text-center">Item Code</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Reservation Type</th>
            <th class="text-center">Validity</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($list as $row)
        <tr>
            <td class="text-center align-middle">{{ $row->name }}</td>
            <td class="text-center align-middle">{{ $row->item_code }}</td>
            <td class="text-center align-middle">{{ $row->reserve_qty . ' ' . $row->stock_uom }}</td>
            <td class="text-center align-middle">{{ $row->warehouse }}</td>
            <td class="text-center align-middle">{{ $row->type }}</td>
            <td class="text-center align-middle">{{ ($row->valid_until) ? $row->valid_until : '-' }}</td>
            <td class="text-center align-middle">{{ $row->created_by }}</td>
            <td class="text-center align-middle"><button class="btn btn-danger">Cancel</button></td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center">No record(s) found.</td>
        </tr>
        @endforelse
        
    </tbody>
</table>