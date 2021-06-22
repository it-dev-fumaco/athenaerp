<div class="float-right mr-3 mt-1 mb-1">
    Total: <span class="badge badge-info">{{ $low_level_stocks->total() }}</span>
</div>
<table class="table table-bordered">
    <col style="width: 33%;">
    <col style="width: 10%;">
    <col style="width: 15%;">
    <col style="width: 11%;">
    <col style="width: 11%;">
    <col style="width: 10%;">
    <col style="width: 10%;">
    <thead style="font-size: 0.82rem;">
        <th class="text-center align-middle">Item Description</th>
        <th class="text-center align-middle">UoM</th>
        <th class="text-center align-middle">Warehouse</th>
        <th class="text-center align-middle p-1">Re-order Qty</th>
        <th class="text-center align-middle p-1">Min. Stock Qty</th>
        <th class="text-center align-middle">Actual Qty</th>
        <th class="text-center align-middle">Action</th>
    </thead>
    <tbody>
        @forelse ($low_level_stocks as $n => $row)
        <tr>
            <td class="text-justify p-2 align-middle">
                @php
                $img = ($row['image']) ? "/img/" . $row['image'] : "/icon/no_img.png";
            @endphp
            
            <div class="d-flex flex-row">
                <div class="p-1">
                    <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['item_code'] }}" data-title="{{ $row['item_code'] }}">
                        <img src="{{ asset('storage/') }}{{ $img }}" class="img-size-50 d-inline-block">
                    </a>
                </div>
                <div class="p-1">
                    <a href="#" class="view-item-details" data-item-code="{{ $row['item_code'] }}" data-item-classification="{{ $row['item_classification'] }}">
                        <span class="d-block font-weight-bold text-dark item-code">{{ $row['item_code'] }}</span>
                        <span class="d-none item-description">{{ $row['description'] }}</span>
                    </a>
                    <small class="font-italic">{{ str_limit($row['description'], $limit = 80, $end = '...') }}</small></div>
              </div>
            </td>
            <td class="text-center p-1 align-middle">
                <small>{{ $row['stock_uom'] }}</small></td>
            <td class="text-center p-1 align-middle">
                <small class="warehouse">{{ $row['warehouse'] }}</small>
            </td>
            <td class="text-center p-1 align-middle reorder-qty" style="font-size:12px">{{ $row['warehouse_reorder_qty'] * 1 }}</td>
            <td class="text-center p-1 align-middle"><strong>{{ $row['warehouse_reorder_level'] * 1 }}</strong></td>
            <td class="text-center p-1 align-middle">
                <span class="badge badge-{{ ($row['actual_qty'] > $row['warehouse_reorder_level']) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ $row['actual_qty'] * 1 }}</span>
            </td>
            <td class="text-center p-1 align-middle">
                @if(!$row['existing_mr'])
                <button class="btn btn-primary btn-sm create-mr-btn" data-id="{{ $row['id'] }}"><i class="fas fa-edit"></i> MR</button>
                @else
                <button class="btn btn-success btn-sm" disabled><i class="fas fa-check"></i> MR</button>
                <small class="d-block mt-1">{{ $row['existing_mr'] }}</small>
                @endif
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