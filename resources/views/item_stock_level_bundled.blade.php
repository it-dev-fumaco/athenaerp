<table class="table table-bordered table-hover responsive-description" style="font-size: 11pt;">
    <colgroup>
        <col style="width: 40%;">
    </colgroup>
    <thead>
        <tr>
            <th scope="col" rowspan="2" class="font-responsive text-center p-1 align-middle">Item</th>
            <th scope="col" rowspan="2" class="font-responsive text-center p-1 align-middle">Warehouse</th>
            <th scope="col" colspan="3" class="font-responsive text-center p-1">Quantity</th>
        </tr>
        <tr>
            <th scope="col" class="font-responsive text-center p-1 text-muted">Reserved</th>
            <th scope="col" class="font-responsive text-center p-1">Actual</th>
            <th scope="col" class="font-responsive text-center p-1">Available</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($stocks as $item_code => $item)
            @foreach ($item['site_warehouses'] as $i => $stock)
                <tr class="{{ $loop->parent->index % 2 === 0 ? 'even' : 'odd' }}">
                    @if ($loop->first)
                        <td rowspan={{ count($item['site_warehouses']) }} class="text-left p-1 justify-content-start align-items-center" style="font-size: 10pt;">
                            <div class="container-fluid d-flex justify-content-center align-items-center" style="min-height: 100% !important">
                                <a href="/get_item_details/{{ $item_code }}" class="text-decoration-none text-transform-none" style="color: inherit">
                                    <span>
                                        &nbsp;<i class="fas fa-external-link-alt"></i>&nbsp;<b>{{ $item_code }}</b> - {{ strip_tags($item['description']) }}
                                    </span>
                                </a>
                            </div>
                        </td>
                    @endif
                    <td class="p-1 font-responsive align-middle">
                        {{ $stock['warehouse'] }}
                        @if ($stock['location'])
                            <small class="text-muted font-italic"> - {{ $stock['location'] }}</small>
                        @endif
                    </td>
                    <td class="text-center p-1 font-responsive align-middle">
                        <span class="text-muted">
                            {{ number_format((float)$stock['reserved_qty'], 2, '.', '') }}<br/>
                            {{ $stock['stock_uom'] }}
                        </span>
                    </td>
                    <td class="text-center p-1 font-responsive align-middle">
                        {{ number_format((float)$stock['actual_qty'], 2, '.', '') }} <br/>
                        {{ $stock['stock_uom'] }}
                    </td>
                    <td class="text-center p-1 align-middle">
                        <span class="badge badge-{{ ($stock['available_qty'] > 0) ? 'success' : 'secondary' }} responsive-description" style="font-size: 10pt;">
                            {{ number_format((float)$stock['available_qty'], 2, '.', '') }} <br/>
                            {{ $stock['stock_uom'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
    
</table>

<style>
    .table > tbody > tr > td {
        vertical-align: middle !important;
    }

    td{
        border: 1px solid #a8a8a8
    }

    .even > td{
        border: 2px solid #fff;
        transition: .4s
    }

    .even{
        background-color: #dadada !important
    }
</style>