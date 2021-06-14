<table class="table table-bordered">
    <col style="width: 35%;">
    <col style="width: 10%;">
    <col style="width: 15%;">
    <col style="width: 10%;">
    <col style="width: 10%;">
    <col style="width: 10%;">
    <col style="width: 10%;">
    <thead>
        <th class="text-center">Item Description</th>
        <th class="text-center">Stock UoM</th>
        <th class="text-center">Warehouse</th>
        <th class="text-center">Re-order Qty</th>
        <th class="text-center">Min. Stock Qty</th>
        <th class="text-center">Actual Qty</th>
        <th class="text-center">Action</th>
    </thead>
    <tbody>
        @forelse ($low_level_stocks as $n => $row)
        <tr>
            <td class="text-justify p-2 align-middle">
                @php
                // $img = ($row['image']) ? "/img/" . $item['image'] : "/icon/no_img.png";
                $img = "/icon/no_img.png";
            @endphp
            
            <div class="d-flex flex-row">
                <div class="p-1">
                    <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['item_code'] }}" data-title="{{ $row['item_code'] }}">
                    <img src="{{ asset('storage/') }}{{ $img }}" class="img-size-50 d-inline-block">
                </a></div>
                <div class="p-1"><span class="d-block font-weight-bold">{{ $row['item_code'] }}</span>
                    <small class="font-italic">{{ $row['description'] }}</small></div>
              </div>

                
            </td>
            <td class="text-center p-1 align-middle">
                <small>{{ $row['stock_uom'] }}</small></td>
            <td class="text-center p-1 align-middle">
                <small>{{ $row['warehouse'] }}</small>
            </td>
            <td class="text-center p-1 align-middle">{{ $row['warehouse_reorder_qty'] * 1 }}</td>
            <td class="text-center p-1 align-middle">{{ $row['warehouse_reorder_level'] * 1 }}</td>
            <td class="text-center p-1 align-middle">
                <span class="badge badge-{{ ($row['actual_qty'] > $row['warehouse_reorder_level']) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ $row['actual_qty'] * 1 }}</span>
            </td>
            <td class="text-center p-1 align-middle">
                <button class="btn btn-primary btn-sm">Create MR</button></td>
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