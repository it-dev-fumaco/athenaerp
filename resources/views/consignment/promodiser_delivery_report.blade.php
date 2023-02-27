@extends('layout', [
    'namePage' => 'Delivery Report',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="text-right">
                        <small class="text-right">Can't find delivery record? <a href="/promodiser/inquire_delivery">Click Here</a></small>
                    </div>
                    <div class="card card-lightblue">
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
                            <script>
                                $(document).ready(function(){
                                    $('#receivedDeliveryModal').modal('show');
                                });
                            </script>
                        @endif
                        <div class="card-header text-center p-2">
                            <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">
                                @if ($type == 'all')
                                    Delivery Report
                                @else
                                    Incoming Deliveries                                    
                                @endif
                            </span>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('error'))
                            <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{!! session()->get('error') !!}</div>
                            @endif
                            <div id="pending-to-receive-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('style')
    <style>
        .morectnt span {
            display: none;
        }
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }

        /* Firefox */
        input[type=number] {
        -moz-appearance: textfield;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            var showTotalChar = 150, showChar = "Show more", hideChar = "Show less";

            $('.price').keyup(function(){
                var target = $(this).data('target');
                var price = $(this).val().replace(/,/g, '');
                if($.isNumeric($(this).val()) && price > 0 || $(this).val().indexOf(',') > -1 && price > 0){
                    var qty = parseInt($('#'+target+'-qty').text());
                    var total_amount = price * qty;

                    const amount = total_amount.toLocaleString('en-US', {maximumFractionDigits: 2});
                    $('#'+target+'-amount').text(amount);
                }else{
                    $('#'+target+'-amount').text('0');
                    // $(this).val('');
                }
            });

            $('.item-description').each(function() {
                var content = $(this).text();
                if (content.length > showTotalChar) {
                    var con = content.substr(0, showTotalChar);
                    var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                    var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                    $(this).html(txt);
                }
            });

            $(".show-more").click(function(e) {
                e.preventDefault();
                if ($(this).hasClass("sample")) {
                    $(this).removeClass("sample");
                    $(this).text(showChar);
                } else {
                    $(this).addClass("sample");
                    $(this).text(hideChar);
                }

                $(this).parent().prev().toggle();
                $(this).prev().toggle();
                return false;
            });

            $(document).on('submit', '.receive-form', function (e){
                e.preventDefault();
                var modal = $(this).data('modal-container');

                $.ajax({
                    type: 'GET',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function(response){
                        if(response.success){
                            load_pending_to_receive('{{ request()->has("page") ? request()->get("page") : 1 }}');
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

            $(document).on('click', '#delivery-report-pagination a', function(event){
                event.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                load_pending_to_receive(page);
            });

            load_pending_to_receive(1);
            function load_pending_to_receive(page){
                $.ajax({
                    type: 'GET',
                    url: '/promodiser/delivery_report/all?page='+page,
                    success: function(response){
                        $('#pending-to-receive-container').html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showNotification("danger", 'Error in getting pending to receive stock entries.', "fa fa-info");
                    }
                });
            }
        });
    </script>
@endsection