<table class="table table-striped table-bordered table-hover responsive-description" style="font-size: 11pt;">
    <thead>
        <tr>
            <th scope="col" rowspan="2" class="font-responsive text-center p-1 align-middle">Warehouse</th>
            <th scope="col" colspan="4" class="font-responsive text-center p-1">Quantity</th>
        </tr>
        <tr>
            <th scope="col" class="font-responsive text-center p-1 text-muted">Reserved</th>
            <th scope="col" class="font-responsive text-center p-1 text-muted">Issued</th>
            <th scope="col" class="font-responsive text-center p-1">Actual</th>
            <th scope="col" class="font-responsive text-center p-1">Available</th>
        </tr>
    </thead>
    @forelse ($siteWarehouses as $sw => $stock)
    <tr>
        <td class="p-1 font-responsive align-middle">
            {{ $stock['warehouse'] }}
            @if ($stock['location'])
                <small class="text-muted font-italic"> - {{ $stock['location'] }}</small>
            @endif
        </td>
        <td class="text-center p-1 font-responsive align-middle">
            @include('partials.qty_cell', [
                'qty' => $stock['reserved_qty'] ?? null,
                'uom' => $stock['stock_uom'],
                'style' => 'muted',
                'decimals' => 2,
                'dashWhenZero' => true,
            ])
        </td>
        <td class="text-center p-1 font-responsive align-middle">
            @include('partials.qty_cell', [
                'qty' => $stock['issued_qty'] ?? null,
                'uom' => $stock['stock_uom'],
                'style' => 'muted',
                'decimals' => 2,
                'dashWhenZero' => true,
            ])
        </td>
        <td class="text-center p-1 font-responsive align-middle">
            @include('partials.qty_cell', [
                'qty' => $stock['actual_qty'] ?? null,
                'uom' => $stock['stock_uom'],
                'style' => 'plain',
                'decimals' => 2,
                'dashWhenZero' => true,
            ])
        </td>
        <td class="text-center p-1 align-middle">
            @include('partials.qty_cell', [
                'qty' => $stock['available_qty'] ?? null,
                'uom' => $stock['stock_uom'],
                'style' => 'badge',
                'badgeMode' => 'binary',
                'decimals' => 2,
                'badgeFontSize' => '10pt',
                'badgeExtraClass' => 'responsive-description',
                'badgeWrapUomInSmall' => false,
                'dashWhenZero' => true,
            ])
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="5" class="text-center font-responsive">No Stock(s)</td>
    </tr>
    @endforelse
</table>
@if(count($consignmentWarehouses) > 0)
    <div class="text-center">
        <a href="#" class="btn btn-primary uppercase p-1 responsive-description" data-toggle="modal" data-target="#vcww{{ $itemDetails->name }}" style="font-size: 12px;"><i class="fas fa-warehouse"></i> Consignment Warehouse(s)</a>
    </div>

    <div class="modal fade" id="vcww{{ $itemDetails->name }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $itemDetails->name }} - Consignment Warehouse(s) </h4>
                    <button type="button" class="close" onclick="close_modal('#vcww{{ $itemDetails->name }}')" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form></form>
                <div class="modal-body">
                    <table class="table table-hover table-bordered table-striped m-0">
                        <col style="width: 60%;">
                        <col style="width: 30%;">
                        <thead>
                            <th class="text-center responsive-description p-1 align-middle">Warehouse</th>
                            <th class="text-center responsive-description p-1 align-middle">Available Qty ({{ collect($consignmentWarehouses)->sum('actual_qty') }})</th>
                        </thead>
                        @forelse($consignmentWarehouses as $con)
                        <tr>
                            <td class="responsive-description p-1 align-middle">
                                {{ $con['warehouse'] }}
                                @if ($con['location'])
                                    <small class="text-muted font-italic"> - {{ $con['location'] }}</small>
                                @endif
                            </td>
                            <td class="text-center responsive-description">
                                @include('partials.qty_cell', [
                                    'qty' => $con['actual_qty'] ?? null,
                                    'uom' => $con['stock_uom'],
                                    'colorQty' => $con['available_qty'] ?? null,
                                    'style' => 'badge',
                                    'badgeMode' => 'binary',
                                    'badgeFontSize' => '15px',
                                    'badgeWrapUomInSmall' => false,
                                    'dashWhenZero' => true,
                                ])
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
                        </tr>
                        @endforelse
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" onclick="close_modal('#vcww{{ $itemDetails->name }}')">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif