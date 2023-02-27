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
                                    <div class="p-1">
                                        <button type="button" class="btn btn-secondary remove-filter"><i class="fas fa-undo"></i></button>
                                    </div>
                                </div>
                            <div class="p-2" id="delivery-report-container"></div>
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
                        showNotification("danger", response.message, "fa fa-info");
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