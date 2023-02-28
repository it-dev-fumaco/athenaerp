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
    @forelse ($site_warehouses as $sw => $stock)
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
    @empty
    <tr>
        <td colspan="5" class="text-center font-responsive">No Stock(s)</td>
    </tr>
    @endforelse
</table>
@if(count($consignment_warehouses) > 0)
    <div class="text-center">
        <a href="#" class="btn btn-primary uppercase p-1 responsive-description" data-toggle="modal" data-target="#vcww{{ $item_details->name }}" style="font-size: 12px;"><i class="fas fa-warehouse"></i> Consignment Warehouse(s)</a>
    </div>

    <div class="modal fade" id="vcww{{ $item_details->name }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $item_details->name }} - Consignment Warehouse(s) </h4>
                    <button type="button" class="close" onclick="close_modal('#vcww{{ $item_details->name }}')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form></form>
                <div class="modal-body">
                    <table class="table table-hover table-bordered table-striped m-0">
                        <col style="width: 70%;">
                        <col style="width: 30%;">
                        <thead>
                            <th class="text-center responsive-description p-1 align-middle">Warehouse</th>
                            <th class="text-center responsive-description p-1 align-middle">Available Qty</th>
                        </thead>
                        @forelse($consignment_warehouses as $con)
                        <tr>
                            <td class="responsive-description p-1 align-middle">
                                {{ $con['warehouse'] }}
                                @if ($con['location'])
                                    <small class="text-muted font-italic"> - {{ $con['location'] }}</small>
                                @endif
                            </td>
                            <td class="text-center responsive-description">
                                <span class="badge badge-{{ ($con['available_qty'] > 0) ? 'success' : 'secondary' }}" style="font-size: 15px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
                        </tr>
                        @endforelse
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="close_modal('#vcww{{ $item_details->name }}')">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif