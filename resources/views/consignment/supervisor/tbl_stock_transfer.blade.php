<table class="table table-striped" style="font-size: 11px;">
    <thead class="text-uppercase">
        <th class="text-center p-2 align-middle" style="width: 18%;">Reference</th>
        @if ($purpose == 'Item Return')
            <th class="text-center p-2 align-middle" style="width: 20%;">Warehouse</th>
        @else
            <th class="text-center p-2 align-middle" style="width: 20%;">Source Warehouse</th>
            <th class="text-center p-2 align-middle" style="width: 20%;">Target Warehouse</th>
        @endif
        <th class="text-center p-2 align-middle" style="width: 20%;">Created by</th>
        <th class="text-center p-2 align-middle" style="width: 12%;">Status</th>
        <th class="text-center p-2 align-middle" style="width: 10%;">Action</th>
    </thead>
    <tbody>
        @forelse ($result as $ste)
        @php
            $badge = 'secondary';
            if ($purpose == 'Item Return'){
                $status = $ste->status == 'Cancelled' ? $ste->status : 'Completed';
            }else{
                $status = $ste->status;
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
            <td class="text-center p-2 align-middle">
                <span class="font-weight-bold d-block">{{ $ste->name }}</span>
                <small class="text-muted">{{ Carbon\Carbon::parse($ste->creation)->format('M. d, Y h:i A') }}</small>
            </td>
            @if ($purpose == 'Item Return')
                <td class="text-center p-2 align-middle">{{ $ste->target_warehouse }}</td>
            @else
                <td class="text-center p-2 align-middle">{{ $ste->source_warehouse ? $ste->source_warehouse : '-' }}</td>
                <td class="text-center p-2 align-middle">{{ $ste->target_warehouse }}</td>
            @endif
            <td class="text-center p-2 align-middle">{{ $ste->submitted_by }}</td>
            <td class="text-center p-2 align-middle">
                <span class="badge badge-{{ $badge }}" style="font-size: 10px;">
                    {{ $status }}
                </span>
            </td>
            <td class="text-center p-2 align-middle">
                <a href="#" data-toggle="modal" data-target="#{{ $ste->name }}-Modal" class="btn btn-info btn-xs"><i class="fas fa-eye"></i> View</a>
                <!-- Modal -->
                <div class="modal fade" id="{{ $ste->name }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-navy">
                                <h6 class="modal-title">
                                    {{ $ste->purpose .' - '. $ste->name }} <span class="badge badge-{{ $badge }} d-inline-block ml-2">{{ $status }}</span>
                                </h6>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                @if (!in_array($status, ['Cancelled', 'Completed']) && $purpose != 'Item Return')
                                    @if (isset($ste->stockEntry->name))
                                        @php
                                            $stockEntry = $ste->stockEntry;
                                        @endphp
                                        @if ($stockEntry->docstatus == 0)
                                            <div class="row">
                                                <div class="col-8 offset-2">
                                                    <div class="callout callout-warning text-center mt-2">
                                                        <i class="fas fa-info-circle"></i> A <b>DRAFT</b> Stock Entry has been created. <br> To submit the stock entry, please login to ERP and click <a class="text-dark" target="_blank" href="http://10.0.0.83/app/stock-entry/{{ $stockEntry->name }}">here</a>.
                                                        <span class="d-block mt-3">Reference Stock Entry: <b>{{ $stockEntry->name }}</b></span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            @if ($ste->purpose == 'Pull Out')
                                                <div class="row">
                                                    <div class="col-8 offset-2">
                                                        <div class="callout callout-success text-center mt-2">
                                                            <i class="fas fa-check"></i> Transaction Completed
                                                            <span class="d-block mt-3">Reference Stock Entry: <b>{{ $stockEntry->name }}</b></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($ste->purpose == 'Store Transfer')
                                                @if ($stockEntry->consignment_status != 'Received')
                                                    <div class="row">
                                                        <div class="col-8 offset-2">
                                                            <div class="callout callout-info text-center mt-2">
                                                                <i class="fas fa-info-circle"></i> Stock Entry has been <b>SUBMITTED</b>. <br> Awaiting for the promodiser of the target store / branch <br> to receive the items.
                                                                <span class="d-block mt-3">Reference Stock Entry: <b>{{ $stockEntry->name }}</b></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="row">
                                                        <div class="col-8 offset-2">
                                                            <div class="callout callout-success text-center mt-2">
                                                                <i class="fas fa-check"></i> Transaction Completed. <br> Stocks have been <b>TRANSFERRED</b>.
                                                                <p class="mt-2 p-0">Received by: {{ $stockEntry->consignment_received_by . ' ' . \Carbon\Carbon::parse($stockEntry->consignment_date_received)->format('Y-m-d h:i A') }}</p>
                                                                <span class="d-block mt-3">Reference Stock Entry: <b>{{ $stockEntry->name }}</b></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        @endif
                                    @else
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="callout callout-info text-center mt-2">
                                                    <i class="fas fa-info-circle"></i>  A stock entry should be created in ERP to transfer / pull out items from a specific store / branch. <br>To create stock entry for this request, click <b>"Generate Stock Entry"</b>.
                                                </div>
                                            </div>
                                            <div class="col-6 mx-auto">
                                                <form class="generate-stock-entry-form" method="POST"> 
                                                    @csrf
                                                    <input type="hidden" name="cste" value="{{ $ste->name }}">
                                                    <button type="submit" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Generate Stock Entry</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                    <hr class="mt-3 p-2 mb-0">
                                @endif
                                <div class="row pb-0 mb-3">
                                    <div class="pt-0 pr-2 pl-2 pb-0 col-6 text-left m-0">
                                        @if ($purpose == 'Item Return')
                                            <dl class="row p-0 m-0">
                                                <dt class="col-12 col-xl-3 col-lg-2 p-1 m-0">Branch:</dt>
                                                <dd class="col-12 col-xl-9 col-lg-10 p-1 m-0">{{ $ste->target_warehouse }}</dd>
                                            </dl>
                                        @else
                                            <dl class="row p-0 m-0">
                                                <dt class="col-12 col-xl-3 col-lg-2 p-1 m-0">Source:</dt>
                                                <dd class="col-12 col-xl-9 col-lg-10 p-1 m-0">{{ $ste->source_warehouse }}</dd>
                                                <dt class="col-12 col-xl-3 col-lg-2 p-1 m-0">Target:</dt>
                                                <dd class="col-12 col-xl-9 col-lg-10 p-1 m-0">{{ $ste->target_warehouse }}</dd>
                                            </dl>
                                        @endif
                                    </div>
                                    <div class="pt-0 pr-2 pl-2 pb-0 col-6 text-left m-0">
                                        <dl class="row p-0 m-0">
                                            <dt class="col-12 col-xl-4 col-lg-6 p-1 m-0">Transaction Date:</dt>
                                            <dd class="col-12 col-xl-8 col-lg-6 p-1 m-0">{{ $ste->creation }}</dd>
                                            <dt class="col-12 col-xl-4 col-lg-6 p-1 m-0">Submitted by:</dt>
                                            <dd class="col-12 col-xl-8 col-lg-6 p-1 m-0">{{ $ste->submitted_by }}</dd>
                                        </dl>   
                                    </div>
                                </div>
                                <table class="table table-bordered" style="font-size: 11px;">
                                    <thead class="text-uppercase">
                                        <th class="text-center p-2 align-middle" style="width: 60%;">Item Code</th>
                                        @if ($status != 'Completed')
                                        <th class="text-center p-2 align-middle" style="width: 20%;">Current Qty</th>
                                        @endif
                                        <th class="text-center p-2 align-middle" style="width: 20%;">Qty to Transfer</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($ste->items as $item)
                                        <tr>
                                            <td class="text-center p-1 align-middle">
                                                <div class="d-flex flex-row justify-content-start align-items-center">
                                                    <div class="p-2 text-left">
                                                        <a href="{{ asset("$item->image") }}" class="view-images" data-item-code="{{ $item->item_code }}">
                                                            <img src="{{ Storage::disk(upcloud)->url($item->image") }}" alt="{{ Str::slug($item->item_description, '-') }}" width="60" height="60">
                                                        </a>
                                                    </div>
                                                    <div class="p-2 text-left">
                                                        <span class="d-block"><b>{{ $item->item_code }}</b> - {!! $item->item_description !!}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            @if ($status != 'Completed')
                                            <td class="text-center p-1 align-middle">
                                                <span class="d-block font-weight-bold">{{ $item->consigned_qty * 1 }}</span>
                                                <small class="text-muted">{{ $item->uom }}</small>
                                            </td>
                                            @endif
                                            <td class="text-center p-1 align-middle">
                                                <span class="d-block font-weight-bold">{{ $item->qty }}</span>
                                                <small class="text-muted">{{ $item->uom }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-uppercase text-muted p-2">No record(s) found</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="float-left m-2">Total: <b>{{ $list->total() }}</b></div>
<div class="float-right m-2" id="consignment-stock-entry-pagination">{{ $list->links('pagination::bootstrap-4') }}</div>

<script>
    $(document).ready(function (){
        $('#consignment-stock-entry-pagination a').attr('data-consignment-purpose', '{{ $purpose }}');
    });
</script>