@extends('layout', [
    'namePage' => 'Received Item(s) List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-md-10 offset-md-1">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Received Item(s) List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2 col-12">
                            {{-- <form method="GET" action="/view_consignment_deliveries"> --}}
                                <div class="d-flex flex-row align-items-center mt-2">
                                    <div class="p-1 col-4">
                                        <select class="form-control tbl-filter" name="store" id="consignment-store-select">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                    <div class="p-1 col-2">
                                        <select class="form-control tbl-filter" name="status" id="status">
                                            <option value="">Select Status</option>
                                            <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>Received</option>
                                            <option value="To Receive" {{ request('status') == 'To Receive' ? 'selected' : '' }}>To Receive</option>
                                        </select>
                                    </div>
                                    {{-- <div class="p-1">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                                    </div> --}}
                                    <div class="p-1">
                                        <button type="button" class="btn btn-secondary remove-filter"><i class="fas fa-undo"></i></button>
                                    </div>
                                </div>
                            {{-- </form> --}}
                            <div class="p-2" id="delivery-report-container">
                                {{-- <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                    <thead class="text-uppercase">
                                        <th class="text-center align-middle">Reference</th>
                                        <th class="text-center align-middle">Branch / Store</th>
                                        <th class="text-center align-middle">MREQ No.</th>
                                        <th class="text-center align-middle">Created By</th>
                                        <th class="text-center align-middle">Delivery Date</th>
                                        <th class="text-center align-middle">Status</th>
                                        <th class="text-center align-middle">Received By</th>
                                        <th class="text-center align-middle">Date Received</th>
                                        <th class="text-center align-middle">Action</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($result as $r)
                                        <tr>
                                            <td class="text-center align-middle">{{ $r['name'] }}</td>
                                            <td class="text-center align-middle">{{ $r['warehouse'] }}</td>
                                            <td class="text-center align-middle">{{ $r['mreq_no'] }}</td>
                                            <td class="text-center align-middle">{{ $r['created_by'] }}</td>
                                            <td class="text-center align-middle">{{ $r['delivery_date'] }}</td>
                                            <td class="text-center align-middle">
                                                @if ($r['status'] == 'Received')
                                                <span class="badge badge-success" style="font-size: 8pt;">{{ $r['status'] }}</span>
                                                @else
                                                <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">{{ $r['received_by'] }}</td>
                                            <td class="text-center align-middle">{{ $r['date_received'] }}</td>
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
                                    <div class="m-0">
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
                                                <form action="/promodiser/receive/{{ $r['name'] }}" id="{{ $r['name'] }}-form" method="GET">
                                                    <h5 class="text-center font-responsive font-weight-bold m-0">{{ $r['warehouse'] }}</h5>
                                                    <div class="row mt-2 mb-2">
                                                        <div class="col-6 pl-5">
                                                            <p class="m-1 font-details">Reference STE: <span class="font-weight-bold">{{ $r['name'] }}</span></p>
                                                            <p class="m-1 font-details">Delivery Date: <span class="font-weight-bold">{{ $r['delivery_date'] }}</span></p>
                                                            <p class="m-1 font-details">MREQ No.: <span class="font-weight-bold">{{ $r['mreq_no'] }}</span></p>
                                                            <p class="m-1 font-details">Created By: <span class="font-weight-bold">{{ $r['created_by'] }}</span></p>
                                                        </div>
                                                        <div class="col-6 pl-5" >
                                                            <p class="m-1 font-details">Status: 
                                                                @if ($r['status'] == 'Received')
                                                                    <span class="badge badge-success" style="font-size: 8pt;">{{ $r['status'] }}</span>
                                                                @else
                                                                    <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                                                                @endif
                                                            </p>
                                                            <p class="m-1 font-details">Received By: <span class="font-weight-bold">{{ $r['received_by'] }}</span></p>
                                                            <p class="m-1 font-details">Date Received: <span class="font-weight-bold">{{ $r['date_received'] }}</span></p>
                                                        </div>
                                                    </div>
                                                    <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                                        <thead>
                                                            <th class="text-center text-uppercase p-1 align-middle" style="width: 55%">Item Code</th>
                                                            <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Received Qty</th>
                                                            <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Rate</th>
                                                            <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Amount</th>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($r['items'] as $i)
                                                            @php
                                                                $reference = $r['name'].'-'.$i['item_code'];
                                                            @endphp
                                                            <tr>
                                                                <td class="text-left p-1 align-middle">
                                                                    <div class="d-flex flex-row justify-content-start align-items-center">
                                                                        <div class="p-1 text-left">
                                                                            <a href="{{ asset('storage/') }}{{ $i['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $i['item_code'] }}" data-title="{{ $i['item_code'] }}">
                                                                            <picture>
                                                                                <source srcset="{{ asset('storage'.$i['img_webp']) }}" type="image/webp" alt="{{ $i['img_slug'] }}" width="40" height="40">
                                                                                <source srcset="{{ asset('storage'.$i['img']) }}" type="image/jpeg" alt="{{ $i['img_slug'] }} width="40" height="40">
                                                                                <img src="{{ asset('storage'.$i['img']) }}" alt="{{ $i['img_slug'] }} width="40" height="40">
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
                                                                    <span class="d-block font-weight-bold">
                                                                        @if ($r['status'] == 'Received')
                                                                            {{ '₱ ' . number_format($i['price'], 2) }}
                                                                        @else
                                                                            <div class="row">
                                                                                <div class="col-1 offset-2" style="display: flex; justify-content: center; align-items: center;">
                                                                                    <span class="d-inline">₱ </span>
                                                                                </div>
                                                                                <div class="col-7">
                                                                                    <input type="text" class="form-control text-center {{ $r['name'] }}-price price-input" name="price[{{ $i['item_code'] }}]"
                                                                                    value="{{ number_format($i['price']) }}"
                                                                                    data-reference="#{{ $reference }}-amount"
                                                                                    data-qty="{{ number_format($i['transfer_qty']) }}"
                                                                                    >
                                                                                    <div class="d-none">
                                                                                        <input type="checkbox" name="receive_delivery" checked readonly>
                                                                                        <input type="text" name="item_codes[]" value="{{ $i['item_code'] }}">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </span>
                                                                </td>
                                                                <td class="text-center p-1 align-middle">
                                                                    <span class="d-block font-weight-bold" id="{{ $reference }}-amount">{{ '₱ ' . number_format($i['amount'], 2) }}</span>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                    @if ($r['status'] != 'Received')
                                                        <div class="container-fluid">
                                                            <button type="button" class="btn btn-primary w-100 submit-btn" data-reference="{{ $r['name'] }}">Receive</button>
                                                        </div>
                                                    @endif
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>  
                                @endforeach --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@if (session()->has('error'))
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h5 class="modal-title" id="exampleModalLabel"><i class="fa fa-info-circle"></i> Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ session()->get('error') }}
                </div>
            </div>
        </div>
    </div>
@endif
@if (session()->has('success'))
    @php
        $received = session()->get('success');
    @endphp
    <div class="modal fade" id="receivedDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-navy">
                    <h5 class="modal-title" id="exampleModalLabel">
                        @switch($received['action'])
                            @case('received')
                                Item(s) Received
                                @break
                            @case('canceled')
                                Stock Transfer Cancelled
                                @break
                            @default
                                Delivered Item(s)
                        @endswitch
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="color: #fff">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 10pt;">
                    <div class="row">
                        <div class="col-2">
                            <center>
                                <p class="text-success text-center mb-0" style="font-size: 4rem;">
                                    <i class="fas fa-check-circle"></i>
                                </p>
                            </center>
                        </div>
                        <div class="col-10">
                            <span>{{ $received['message'] }}</span> <br>
                            <span>Branch: <b>{{ $received['branch'] }}</b></span> <br>
                            <span>Total Amount: <b>₱ {{ number_format(collect($received)->sum('amount'), 2) }}</b></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@section('script')
<script>
    $('#errorModal').modal('show');
    $('#receivedDeliveryModal').modal('show');

    $(function () {
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

        $(document).on('click', '.submit-btn', function (){
            validate_submit($(this).data('reference'));
        });

        $(document).on('click', '.remove-filter', function (e){
            e.preventDefault();
            $("#consignment-store-select").empty().trigger('change');
            $('#status').val('');
            load_tbl(1);
        });

        $(document).on('change', '.tbl-filter', function (e){
            load_tbl(1);
        });

        $(document).on('click', '#to-receive-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            load_tbl(page);
        });

        $(document).on('click', '.submit-btn', function (){
            $('.' + $(this).data('reference') + '-price').each(function (){
                if($(this).val() == '' || $(this).val() <= 0){
                    $(this).css('border', '1px solid red');
                    err = 1;
                }else{
                    $(this).css('border', '1px solid #DEE2E6');
                }
            });
            
            if(err == 1){
                showNotification("danger", 'Item price cannot be less than or equal to 0.', "fa fa-info");
            }else{
                $('#' + $(this).data('reference') + '-form').submit();
            }
        });

        $(document).on('submit', '.deliveries-form', function (e){
            e.preventDefault();
            var modal = $(this).data('modal-container');
            $.ajax({
                type: 'GET',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if(response.success){
                        load_tbl('', '{{ request()->has("page") ? request()->get("page") : 1 }}');
                        $(modal).modal('hide');
                        showNotification("success", response.message, "fa fa-check");
                    }else{
                        showNotification("danger", response.message, "fa fa-check");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
                }
            });
        });

        load_tbl(1);
        function load_tbl(page){
            $.ajax({
                type: 'GET',
                url: '/view_consignment_deliveries?page='+page,
                data: {
                    page: page,
                    status: $('#status').val(),
                    store: $('#consignment-store-select').val()
                },
                success: function(response){
                    $('#delivery-report-container').html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'Error in getting pending to receive stock entries.', "fa fa-info");
                }
            });
        }

        function showNotification(color, message, icon){
            $.notify({
                icon: icon,
                message: message
            },{
                type: color,
                timer: 500,
                z_index: 1060,
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
        }

        $(document).on('keyup', '.price-input', function (){
            var target = $(this).data('reference');
            var price = $(this).val().replace(/,/g, '');
            var qty = parseInt($(this).data('qty'));
            if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
                var total_amount = price * qty;

                const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
                $(target).text('₱ ' + amount);
            }else{
                $(target).text('₱ 0.00');
            }
        });
    });
</script>
@endsection