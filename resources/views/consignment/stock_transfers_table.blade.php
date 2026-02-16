<table class="table" style="font-size: 11px;">
    <thead class="border-top text-uppercase">
        <th class="text-center p-1 d-none d-lg-table-cell">Reference</th>
        <th class="text-center p-1 d-none d-lg-table-cell">Date</th>
        <th class="text-center p-1 mobile-first-row">
            <span class="d-none d-lg-inline">{!! $purpose != 'Item Return' ? 'From&nbsp;' : null !!}Warehouse</span>
            <span class="d-inline d-lg-none">Details</span>
        </th>
        @if ($purpose != 'Item Return')
            <th class="text-center p-1 d-none d-lg-table-cell">To Warehouse</th>
        @endif
        <th class="text-center p-1 d-none d-lg-table-cell">Submitted By</th>
        <th class="text-center p-1 d-none d-lg-table-cell">Status</th>
        <th class="text-center p-1">Action</th>
    </thead>
    <tbody>
    @forelse ($steArr as $ste)
    @php
        $badge = 'secondary';
        $status = 'Unknown';
        // if ($ste['status'] == 'Pending') {
        //     $badge = 'warning';
        //     $status = $ste['status'];
        // } elseif ($ste['status'] == 'Completed') {
        //     $badge = 'success';
        //     $status = $ste['status'];
        // } else {
        //     $badge = 'danger';
        //     $status = $ste['status'];
        // }

        if ($purpose == 'Item Return'){
                $status = $ste['status'] == 'Cancelled' ? $ste['status'] : 'Completed';
            }else{
                $status = $ste['status'];
            }
                    
            if ($purpose == 'Item Return') {
                if (in_array($status, ['Completed', 'Pending'])){
                    $badge = 'success';
                } else {
                    $badge = 'danger';
                }
            }else{
                if($status == 'Pending'){
                    $badge = 'warning';
                }elseif ($status == 'Completed'){
                    $badge = 'success';
                } else {
                    $badge = 'danger';
                }
            }
    @endphp
    <tr>
        <td class="text-center p-1 d-none d-lg-table-cell"><span class="font-weight-bold">{{ $ste['title'] ? $ste['title'] : $ste['name'] }}</span></td>
        <td class="text-center p-1 d-none d-lg-table-cell">{{ Carbon\Carbon::parse($ste['date'])->format('M d, Y - h:i a') }}</td>
        <td class="p-1 align-middle">
            <div class="d-none d-lg-inline text-center">
                {{ $purpose == 'Item Return' ? $ste['to_warehouse'] : $ste['from_warehouse'] }}
            </div>
            <div class="d-inline d-lg-none text-left">
                <span class="font-weight-bold">{{ $ste['name'] }}</span>&nbsp;<span class="badge badge-{{ $badge }}">{{ $status }}</span>
            </div>
        </td>
        @if ($purpose != 'Item Return')
            <td class="d-none p-1 d-lg-table-cell">{{ $ste['transfer_type'] == 'Pull Out' ? 'Fumaco - Plant 2' : $ste['to_warehouse'] }}</td>
        @endif
        <td class="text-center p-1 d-none d-lg-table-cell">{{ $ste['owner'] }}</td>
        <td class="text-center p-1 d-none d-lg-table-cell">
            <span class="badge badge-{{ $badge }}">{{ $status }}</span>
        </td>
        <td class="text-center p-1 align-middle">
            <a href="#" class="btn btn-info btn-xs" data-toggle="modal" data-target="#{{ $ste['name'] }}-Modal"><i class="fas fa-eye"></i> View</a>
            <!-- Modal -->
            <div class="modal fade" id="{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-navy">
                            <h6 class="modal-title"><b>{{ $ste['transfer_type'] == 'Store Transfer' ? 'Store-to-Store Transfer' : 'Item Pull Out' }}</b>&nbsp;<span class="badge badge-{{ $badge }}">{{ $status }}</span></h6>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-2">
                            @if ($purpose == 'Item Return')
                                <span class="d-block text-left"><b>Warehouse: </b> {{ $ste['to_warehouse'] }}</span>
                            @else
                                <span class="d-block text-left"><b>From: </b> {{ $ste['from_warehouse'] }}</span>
                                <span class="d-block text-left"><b>To: </b> {{ $ste['to_warehouse'] }}</span>
                            @endif
                            <small class="d-block text-left mb-2">{{ $ste['owner'] }} - {{ Carbon\Carbon::parse($ste['date'])->format('M d, Y - h:i a') }}</small>
                            @if ($ste['transfer_type'] == 'Store Transfer')
                            <div class="callout callout-info text-center">
                                <span><i class="fas fa-info-circle"></i> Stocks will be deducted once the store recipient has received the item.</span>
                            </div>
                            @endif
                            <table class="table" style="font-size: 11px;">
                                <thead class="text-uppercase">
                                    <th class="text-center p-1 align-middle">Item Code</th>
                                    <th class="text-center p-1 align-middle">Current Qty</th>
                                    <th class="text-center p-1 align-middle">Qty to Transfer</th>
                                </thead>
                                @foreach ($ste['items'] as $item)
                                    <tr>
                                        <td class="text-center p-1">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ $item['image'] }}" class="view-images" data-item-code="{{ $item['item_code'] }}">
                                                        <img src="{{ $item['image'] }}" alt="{{ Illuminate\Support\Str::slug(strip_tags($item['description']), '-') }}" width="40" height="40">
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="font-weight-bold">{{ $item['item_code'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <span class="d-block font-weight-bold">{{ $item['consigned_qty'] * 1 }}</span>
                                            <small>{{ $item['uom'] }}</small>
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <span class="d-block font-weight-bold">{{ $item['transfer_qty'] * 1 }}</span>
                                            <small>{{ $item['uom'] }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
                                            <span class="item-description">{!! $item['description'] !!}</span>
                                        </td>
                                    </tr>
                                    @if (in_array($ste['transfer_type'], ['Pull Out']))
                                        <tr>
                                            <td class="border-top-0 text-left p-1" colspan="4">
                                                Reason: <b>{{ $item['return_reason'] ? $item['return_reason'] : '-' }}</b>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                            <div class="container-fluid text-left p-1" style="font-size: 10pt;">
                                @php
                                    $remarks = str_replace('Generated in AthenaERP. ', '', $ste['remarks']);
                                @endphp
                                @if ($remarks)
                                    Remarks: {{ $remarks }}
                                @endif
                            </div>
                            <div class="text-center m-2">
                                <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $ste['name'] }}-Modal" {{ in_array($status, ['Completed', 'Cancelled']) ? 'disabled' : null }}>
                                    <i class="fas fa-ban"></i> Cancel Request
                                </button>
                            </div>
                            <div class="modal fade" id="cancel-{{ $ste['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-navy">
                                            <h6 class="modal-title">{{ $ste['transfer_type'] == 'Store Transfer' ? 'Store-to-Store Transfer' : 'Item Pull Out' }}&nbsp;<span class="badge badge-{{ $badge }}">{{ $status }}</span></h6>
                                            <button type="button" class="close text-white" onclick="close_modal('#cancel-{{ $ste['name'] }}-Modal')">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body p-2">
                                            <form></form>
                                            <h6 class="m-3">Cancel <b>{{ $ste['transfer_type'] == 'Store Transfer' ? 'Store-to-Store Transfer' : 'Item Pull Out' }}</b> Request?</h6>
                                            <hr class="m-1 p-1">
                                            <div class="d-flex flex-row pb-2">
                                                <div class="text-center col-6">
                                                    <a href="/stock_transfer/cancel/{{ $ste['name'] }}" class="btn btn-primary btn-block submit-once"><i class="fas fa-check"></i> Confirm</a>
                                                </div>
                                                <div class="col-6">
                                                    <button class="btn btn-secondary btn-block" type="button" onclick="close_modal('#cancel-{{ $ste['name'] }}-Modal')"><i class="fas fa-times"></i> Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
        </td>
    </tr>
    <tr class="d-lg-none">
        <td colspan="2" class="p-1 border-top-0 border-bottom" style="font-size: 8pt;">
            @if ($purpose == 'Item Return')
                <b>Warehouse: </b>{{ $ste['to_warehouse'] }} <br>
                <small>{{ $ste['owner'] }} - {{ Carbon\Carbon::parse($ste['date'])->format('M d, Y - h:i a') }}</small> 
            @else
                <b>From: </b>{{ $ste['from_warehouse'] }} <br>
                <b>To: </b>{{ $ste['transfer_type'] == 'Pull Out' ? 'Fumaco - Plant 2' : $ste['to_warehouse'] }} <br>
                <small>{{ $ste['owner'] }} - {{ Carbon\Carbon::parse($ste['date'])->format('M d, Y - h:i a') }}</small> 
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6"><span class="d-block text-center text-uppercase text-muted">No record(s) found</span></td>
    </tr>
    @endforelse
    </tbody>
</table>
<div class="container-fluid">
    <span class="float-right p-2" style="font-size: 10pt;"><b>Total: </b>{{ $stockTransfers->total() }}</span>
</div>
<div id="transfers-pagination" class="mt-3 ml-3 clearfix pagination d-block">
    {{ $stockTransfers->links() }}
</div>