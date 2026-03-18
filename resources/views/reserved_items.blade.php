<table class="table table-bordered table-hover m-0">
    <col class="low-lvl-stk-tbl-item-desc"><!-- Item Description -->
    <col style="width: 14%;"><!-- Warehouse -->
    <col style="width: 10%;"><!-- Reserved Qty -->
    <col style="width: 12%;"><!-- Reservation Type -->
    <col style="width: 14%;"><!-- Sales Person -->
    <col style="width: 16%;"><!-- Project -->
    <thead style="font-size: 0.82rem;">
        <th class="text-center align-middle">Item Description</th>
        <th class="text-center align-middle d-none d-lg-table-cell">Warehouse</th>
        <th class="text-center align-middle d-none d-lg-table-cell">Reserved Qty</th>
        <th class="text-center align-middle d-none d-lg-table-cell">Reservation Type</th>
        <th class="text-center align-middle d-none d-lg-table-cell">Sales Person</th>
        <th class="text-center align-middle d-none d-lg-table-cell">Project</th>
    </thead>
    <tbody>
        @forelse ($list as $item)
            <tr>
                <td class="text-justify p-2 align-middle font-responsive">
                    <div class="row">
                        <div class="col-2 position-relative">
                            <a href="{{ $item['image'] }}" data-toggle="lightbox" data-gallery="{{ $item['item_code'] }}" data-title="{{ $item['item_code'] }}">
                                <img src="{{ $item['image'] }}" class="img w-100" alt="Item image" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('d-none');">
                                <img src="{{ Storage::disk('upcloud')->url('icon/no-img.png') }}" class="d-none reserved-no-image img w-100 rounded" alt="" style="min-height: 60px; object-fit: contain; background: #f8f9fa;">
                            </a>
                        </div>
                        <div class="col-10">
                            <a href="/get_item_details/{{ $item['item_code'] }}" target="_blank" data-item-classification="{{ $item['item_classification'] }}">
                                <span class="d-block font-weight-bold text-dark item-code">{{ $item['item_code'] }}</span>
                                <span class="d-none item-description">{{ $item['description'] }}</span>
                            </a>
                            <small class="font-italic">{!! \Illuminate\Support\Str::limit($item['description'], 50, '...') !!}</small>
                            <div class="d-block d-lg-none mt-1">
                                <div><b>Warehouse:</b> <small>{{ $item['warehouse'] }}</small></div>
                                <div><b>Reserved:</b> <small>{{ number_format($item['qty'] * 1) }} {{ $item['stock_uom'] }}</small></div>
                                <div><b>Type:</b> <small>{{ $item['reservation_type'] ?? '--' }}</small></div>
                                <div><b>Sales Person:</b> <small>{{ $item['sales_person'] ?? '--' }}</small></div>
                                <div><b>Project:</b> <small>{{ $item['project'] ?? '--' }}</small></div>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="text-center p-1 align-middle d-none d-lg-table-cell">
                    <small class="warehouse">{{ $item['warehouse'] }}</small>
                </td>
                <td class="text-center p-1 align-middle d-none d-lg-table-cell" style="font-size: 12px;">
                    {{ number_format($item['qty'] * 1) }} <small>{{ $item['stock_uom'] }}</small>
                </td>
                <td class="text-center p-1 align-middle d-none d-lg-table-cell">
                    <small>{{ $item['reservation_type'] ?? '--' }}</small>
                </td>
                <td class="text-center p-1 align-middle d-none d-lg-table-cell">
                    <small>{{ $item['sales_person'] ?? '--' }}</small>
                </td>
                <td class="text-center p-1 align-middle d-none d-lg-table-cell">
                    <small>{{ $item['project'] ?? '--' }}</small>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center font-weight-bold">No Record(s) found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="card-footer clearfix" id="reserved-items-pagination" style="font-size: 12pt;">
    {{ $list->links() }}
</div>