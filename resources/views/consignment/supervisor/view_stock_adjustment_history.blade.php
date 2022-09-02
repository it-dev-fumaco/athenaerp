<table class="table table-bordered text-center">
    <tr>
        <th>
            <span class="d-block d-xl-none">Details</span>
            <span class="d-none d-xl-block">Reference ID</span>
        </th>
        <th class="d-none d-sm-table-cell">Branch Warehouse</th>
        <th class="d-none d-sm-table-cell">Created By</th>
        <th class="d-none d-sm-table-cell">Created At</th>
        <th class="d-none d-sm-table-cell">Status</th>
        <th>Action</th>
    </tr>
    @forelse ($stock_adjustments_array as $stock)
        <tr>
            <td class="text-justify text-xl-center">
                <b>{{ $stock['name'] }}</b>
                <div class="d-block d-xl-none text-left">
                    <span>{{ $stock['warehouse'] }}</span> <br>
                    <span><b>Created by:</b> {{ $stock['created_by'] }}</span> <br>
                    <span><b>Created at:</b> {{ Carbon\Carbon::parse($stock['creation'])->format('M d, Y - h:i A') }}</span>
                </div>
            </td>
            <td class="d-none d-sm-table-cell">{{ $stock['warehouse'] }}</td>
            <td class="d-none d-sm-table-cell">{{ $stock['created_by'] }}</td>
            <td class="d-none d-sm-table-cell">{{ Carbon\Carbon::parse($stock['creation'])->format('M d, Y - h:i A') }}</td>
            <td class="d-none d-sm-table-cell">
                <span class="badge badge-{{ $stock['status'] == 'Submitted' ? 'success' : 'secondary' }}">{{ $stock['status'] }}</span>
            </td>
            <td>
                <a href="#" data-toggle="modal" data-target="#{{ $stock['name'] }}-Modal" style="white-space: nowrap">View Items</a>
                <span class="d-block d-xl-none badge badge-{{ $stock['status'] == 'Submitted' ? 'success' : 'secondary' }}">{{ $stock['status'] }}</span>

                <div class="modal fade" id="{{ $stock['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog " role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Stock Adjustment History</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <h5 class="text-center font-responsive font-weight-bold m-0">{{ $stock['warehouse'] }}</h5>
                                <small class="d-block text-center mb-2">{{ $stock['name'] }} | Transaction Date: {{ $stock['transaction_date'] }}</small>
                                <div class="row p-0">
                                    <div class="col-4 p-2 font-weight-bold border">Item</div>
                                    <div class="col-4 p-2 font-weight-bold border">Stocks</div>
                                    <div class="col-4 p-2 font-weight-bold border">Price</div>
                                </div>
                                @foreach ($stock['items'] as $item)
                                    <div class="row p-0">
                                        <div class="col-4 border p-2 font-weight-bold">
                                            <div class="row">
                                                <div class="col-5">
                                                    <picture>
                                                        <source srcset="{{ asset($item['webp']) }}" class="webp-src" type="image/webp">
                                                        <source srcset="{{ asset($item['image']) }}" class="image-src" type="image/jpeg">
                                                        <img src="{{ asset($item['image']) }}" class="image" alt="" width="50" height="50">
                                                    </picture>
                                                </div>
                                                <div class="col-7" style="display: flex; justify-content: center; align-items: center;">
                                                    {{ $item['item_code'] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4 border p-2 font-weight-bold" style="display: flex; justify-content: center; align-items: center;">
                                            <div class="container-fluid">
                                                <span><b>{{ number_format($item['new_qty']).' '.$item['uom'] }}</b></span>
                                                @if ($item['new_qty'] != $item['previous_qty'])
                                                    <br>
                                                    <small>Previous: {{ number_format($item['previous_qty']).' '.$item['uom'] }}</small>
                                                @endif
                                            </div>
                                            
                                        </div>
                                        <div class="col-4 border p-2 font-weight-bold" style="display: flex; justify-content: center; align-items: center;">
                                            <div class="container-fluid">
                                                <span><b>₱ {{ number_format($item['new_price'], 2) }}</b></span>
                                                @if ($item['new_price'] != $item['previous_price'])
                                                    <br>
                                                    <small>Previous: ₱ {{ number_format($item['previous_price'], 2) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12 border p-2">
                                            <div class="item-description text-justify">{{ strip_tags($item['item_description']) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($stock['status'] != 'Cancelled')
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $stock['name'] }}-Modal" {{ $stock['has_transactions'] == 1 ? 'disabled' : null }}>Cancel</button>
                                    @if ($stock['has_transactions'] == 1)
                                        <span>Cannot cancel stock adjustments with existing transactions.</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal -->
                <div class="modal fade" id="cancel-{{ $stock['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Confirm Cancel</h5>
                                <button type="button" class="close" onclick="$('#cancel-{{ $stock['name'] }}-Modal').modal('hide')" style="color: #fff">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Cancel {{ $stock['name'] }}?
                            </div>
                            <div class="modal-footer">
                                <a href="/cancel_stock_adjustment/{{ $stock['name'] }}" class="btn btn-primary w-100">Confirm</a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Cancel Modal -->
            </td>
        </tr>
    @empty
        <tr>
            <td colspan=6 class="text-center">
                No record(s) found.
            </td>
        </tr>
    @endforelse
</table>