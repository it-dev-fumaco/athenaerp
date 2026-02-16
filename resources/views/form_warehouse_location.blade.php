<form action="/edit_warehouse_location" method="POST" autocomplete="off" id="edit-warehouse-location-form">
    @csrf
    <input type="hidden" name="item_code" value="{{ $itemCode }}">
    <table class="table table-striped table-bordered" style="font-size: 9pt;">
        <thead class="text-uppercase">
            <th class="p-2 text-center">Warehouse</th>
            <th class="p-2 text-center">Location</th>
        </thead>
        <tbody>
        @forelse ($warehouses as $warehouse)
            <tr>
                <td class="p-2 align-middle">{{ $warehouse->warehouse }}</td>
                <td class="p-1">
                    <input type="text" class="form-control" name="location[{{ $warehouse->warehouse }}]" value="{{ $warehouse->location }}" placeholder="Location" style="font-size: 9pt;">
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center text-muted">No Available stock on all warehouse</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="text-center m-2">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
    </div>
</form>