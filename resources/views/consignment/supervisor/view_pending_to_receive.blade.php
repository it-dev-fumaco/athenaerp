<div class="card-body p-2 col-12">
    <div class="p-2">
        <table class="table table-bordered table-striped" style="font-size: 9pt;">
            <thead class="text-uppercase">
                <th class="text-center align-middle">Reference</th>
                <th class="text-center align-middle">Branch / Store</th>
                <th class="text-center align-middle">MREQ No.</th>
                <th class="text-center align-middle">Delivery Date</th>
                <th class="text-center align-middle">Status</th>
                <th class="text-center align-middle">Action</th>
            </thead>
            <tbody>
                @forelse ($result as $r)
                <tr>
                    <td class="text-center align-middle">
                        {{ $r['name'] }} <br>
                        {{ $r['created_by'] }}
                    </td>
                    <td class="text-center align-middle">{{ $r['warehouse'] }}</td>
                    <td class="text-center align-middle">{{ $r['mreq_no'] }}</td>
                    <td class="text-center align-middle">{{ $r['delivery_date'] }}</td>
                    <td class="text-center align-middle">
                        @if ($r['status'] == 'Received')
                        <span class="badge badge-success" style="font-size: 8pt;">{{ $r['status'] }}</span>
                        @else
                        <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        <a href="#" data-toggle="modal" data-target="#{{ $r['name'] }}-modal">View</a>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-uppercase text-muted">No record(s) found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex flex-row justify-content-between">
            <div class="m-0" id="to-receive-pagination">
                {{ $list->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
            <div class="m-0">
                Total Records: <b>{{ $list->total() }}</b>
            </div>
        </div>
        @foreach ($result as $r)
        <div class="modal fade" id="{{ $r['name'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document" style="font-size: 10pt;">
                <div class="modal-content">
                    <div class="modal-header bg-navy">
                        <h6 class="modal-title">Received Item(s)</h6>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form></form>
                        <h5 class="text-center font-responsive font-weight-bold m-0">{{ $r['warehouse'] }}</h5>
                        <div class="row mt-2 mb-2">
                            <div class="col-6 pl-5">
                                <p class="m-1 font-details">Reference STE: <span class="font-weight-bold">{{ $r['name'] }}</span></p>
                                <p class="m-1 font-details">Delivery Date: <span class="font-weight-bold">{{ $r['delivery_date'] }}</span></p>
                                <p class="m-1 font-details">Created By: <span class="font-weight-bold">{{ $r['created_by'] }}</span></p>
                            </div>
                            <div class="col-6 pl-5" >
                                <p class="m-1 font-details">MREQ No.: <span class="font-weight-bold">{{ $r['mreq_no'] }}</span></p>
                                <p class="m-1 font-details">Status: <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                                </p>
                            </div>
                        </div>
                        <table class="table table-bordered table-striped" style="font-size: 9pt;">
                            <thead>
                                <th class="text-center text-uppercase p-1 align-middle" style="width: 55%">Item Code</th>
                                <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Delivered Qty</th>
                                <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Rate</th>
                                <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Amount</th>
                            </thead>
                            <tbody>
                                @foreach ($r['items'] as $i)
                                <tr>
                                    <td class="text-left p-1 align-middle">
                                        <div class="d-flex flex-row justify-content-start align-items-center">
                                            <div class="p-1 text-left">
                                                <a href="{{ asset('storage/') }}{{ $i['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $i['item_code'] }}" data-title="{{ $i['item_code'] }}">
                                                <picture>
                                                    <source srcset="{{ asset('storage'.$i['img_webp']) }}" type="image/webp" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                    <source srcset="{{ asset('storage'.$i['img']) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                    <img src="{{ asset('storage'.$i['img']) }}" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                </picture>
                                                </a>
                                            </div>
                                            <div class="p-1 m-0">
                                                <span class="d-block"><b>{{ $i['item_code'] }}</b> {{ strip_tags($i['description']) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center p-1 align-middle">
                                        <span class="d-block font-weight-bold">{{ number_format($i['transfer_qty']) }}</span>
                                        <small>{{ $i['stock_uom'] }}</small>
                                    </td>
                                    <td class="text-center p-1 align-middle">
                                        <span class="d-block font-weight-bold">{{ '₱ ' . number_format($i['price'], 2) }}</span>
                                    </td>
                                    <td class="text-center p-1 align-middle">
                                        <span class="d-block font-weight-bold">{{ '₱ ' . number_format($i['amount'], 2) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>  
        @endforeach
    </div>
</div>

<script>
    $('#consignment-store-select').select2({
        placeholder: "Select Store",
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
</script>