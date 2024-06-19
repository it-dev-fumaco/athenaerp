<table class="table table-striped table-bordered table-hover responsive-description" style="font-size: 11pt;">
    <thead>
        <tr>
            <th scope="col" rowspan="2" class="font-responsive text-center p-1 align-middle">Warehouse</th>
            <th scope="col" colspan="3" class="font-responsive text-center p-1">Quantity</th>
        </tr>
        <tr>
            <th scope="col" class="font-responsive text-center p-1 text-muted">Reserved</th>
            <th scope="col" class="font-responsive text-center p-1">Actual</th>
            <th scope="col" class="font-responsive text-center p-1">Available</th>
        </tr>
    </thead>
    @foreach ($stocks as $item_code => $item)
        <tr>
            <td colspan=4 class="text-left p-1" style="background-color: #001F3F; color: #fff">
                <a href="/get_item_details/{{ $item_code }}" class="text-decoration-none text-transform-none" style="color: inherit">
                    <span>
                        &nbsp;<i class="fas fa-external-link-alt"></i>&nbsp;<b>{{ $item_code }}</b> - {{ strip_tags($item['description']) }}
                    </span>
                </a>
            </td>
        </tr>
        @foreach ($item['site_warehouses'] as $stock)
            <tr>
                <td class="p-1 font-responsive align-middle">
                    {{ $stock['warehouse'] }}
                    @if ($stock['location'])
                        <small class="text-muted font-italic"> - {{ $stock['location'] }}</small>
                    @endif
                </td>
                <td class="text-center p-1 font-responsive align-middle">
                    <span class="text-muted">{{ number_format((float)$stock['reserved_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</span>
                </td>
                <td class="text-center p-1 font-responsive align-middle">{{ number_format((float)$stock['actual_qty'], 2, '.', '') .' '. $stock['stock_uom'] }}</td>
                <td class="text-center p-1 align-middle">
                    <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'secondary' }} responsive-description" style="font-size: 10pt;">{{ number_format((float)$stock['available_qty'], 2, '.', '') . ' ' . $stock['stock_uom'] }}</span>
                </td>
            </tr>
        @endforeach
    @endforeach
</table>