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
                <b>{{ $stock->title ? $stock->title : $stock->name }}</b>
                <div class="d-block d-xl-none text-left">
                    <span>{{ $stock->warehouse }}</span> <br>
                    <span><b>Created by:</b> {{ $stock->created_by }}</span> <br>
                    <span><b>Created at:</b> {{ Carbon\Carbon::parse($stock->creation)->format('M d, Y - h:i A') }}</span>
                </div>
            </td>
            <td class="d-none d-sm-table-cell">{{ $stock->warehouse }}</td>
            <td class="d-none d-sm-table-cell">{{ $stock->created_by }}</td>
            <td class="d-none d-sm-table-cell">{{ Carbon\Carbon::parse($stock->creation)->format('M d, Y - h:i A') }}</td>
            <td class="d-none d-sm-table-cell">
                <span class="badge badge-{{ $stock->status == 'Submitted' ? 'success' : 'secondary' }}">{{ $stock->status }}</span>
            </td>
            <td>
                <a href="#" data-toggle="modal" data-target="#{{ $stock->name }}-Modal" style="white-space: nowrap">View Items</a>
                <span class="d-block d-xl-none badge badge-{{ $stock->status == 'Submitted' ? 'success' : 'secondary' }}">{{ $stock->status }}</span>

                <div class="modal fade" id="{{ $stock->name }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Stock Adjustment History</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <h5 class="text-center font-responsive font-weight-bold m-0">{{ $stock->warehouse }}</h5>
                                <small class="d-block text-center mb-2">{{ $stock->title ? $stock->title : $stock->name }} | Transaction Date: {{ Carbon\Carbon::parse($stock->transaction_date)->format('M d, Y h:i:s A') }}</small>
                                <div class="row border w-100" style="font-size: 9pt;">
                                    <div class="col-9 text-uppercase text-center">
                                        <div class="row p-0 m-0 w-100">
                                            <div class="col-8 p-2 text-uppercase text-center">
                                                <b>Item Description</b>
                                            </div>
                                            <div class="col-2 p-2 text-uppercase text-center">
                                                <b>Qty</b>
                                            </div>
                                            <div class="col-2 p-2 text-uppercase text-center">
                                                <b>Price</b>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-3 p-2 text-uppercase text-center">
                                        <b>Reason for Adjustment</b>
                                    </div>
                                </div>
                                @foreach ($stock->items as $item)
                                    <div class="row border w-100 items ' + item_code + '"  id="row-SA-' + item_code + '" data-item-code="' + item_code + '" style="font-size: 9pt;">
                                        <div class="col-9">
                                            <div class="row p-0 m-0 w-100">
                                                <div class="col-2 d-flex justify-content-center align-items-center text-center">
                                                    <img src="{{ asset("storage/$item->image") }}" class="image w-75" alt="">
                                                </div>
                                                <div class="col-6 d-flex justify-content-center align-items-center text-center">
                                                    <div class="row w-100 p-1">
                                                        <b>{{ $item->item_code }}</b>
                                                        <div class="col-12 p-0 mb-2" style="text-align: justify">
                                                            {{ strip_tags($item->item_description) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-2 d-flex justify-content-center align-items-center">
                                                    <div class="text-center">
                                                        <span><b>{{ number_format($item->new_qty) }}</b> <small>{{ $item->uom }}</small></span>
                                                        @if ($item->new_qty != $item->previous_qty)
                                                            <br>
                                                            <small>Previous: {{ number_format($item->previous_qty).' '.$item->uom }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="col-2 d-flex justify-content-center align-items-center">
                                                    <div class="text-center">
                                                        <span><b>₱ {{ number_format($item->new_price, 2) }}</b></span>
                                                        <br>
                                                        @if ($item->new_price != $item->previous_price)
                                                            <small>Previous: ₱ {{ number_format($item->previous_price, 2) }}</small>
                                                        @else
                                                            <small>Previous: Not Set</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-3 d-flex justify-content-center align-items-center">
                                            <div class="text-justify w-100">
                                                {{ $item->reason }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($stock->remarks)
                                <div class="container text-left p-2">
                                    <label>Notes</label><br>
                                    {{ $stock->remarks }}
                                </div>
                            @endif
                            @if ($stock->status != 'Cancelled')
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $stock->name }}-Modal" {{ $stock->has_transactions == 1 ? 'disabled' : null }}>Cancel</button>
                                    @if ($stock->has_transactions == 1)
                                        <span>Cannot cancel stock adjustments with existing transactions.</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal -->
                <div class="modal fade" id="cancel-{{ $stock->name }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h5 class="modal-title" id="exampleModalLabel">Confirm Cancel</h5>
                                <button type="button" class="close" onclick="$('#cancel-{{ $stock->name }}-Modal').modal('hide')" style="color: #fff">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                Cancel {{ $stock->name }}?
                            </div>
                            <div class="modal-footer">
                                <a href="/cancel_stock_adjustment/{{ $stock->name }}" class="btn btn-primary w-100">Confirm</a>
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
<div class="float-right" id="stock-adjustment-history-pagination" style="font-size: 10pt;">
	{{ $stock_adjustments->links() }}
</div>