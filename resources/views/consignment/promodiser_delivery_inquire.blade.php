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
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-2">
                            <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">
                                Inquire Delivery
                            </span>
                        </div>
                        <div class="card-body p-1">
                            <div class="row">
                                <div class="col-8 col-xl-10">
                                    <input type="text" id="ste-search" name="ste" class="form-control" placeholder="Enter STE Number" value="{{ request('ste') ? request('ste') : null }}">
                                </div>
                                <div class="col-4 col-xl-2"><button type="button" id="submit-search" class="btn btn-primary w-100"><i class="fa fa-search"></i> Search</button></div>
                            </div>
                            <div id="inquire-form" class="text-center"> 
                                <h5 class="p-2">Please enter the STE number</h5>
                            </div>
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
                $(this).val('');
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

        $(document).on('click', '#submit-search', function (e){
            e.preventDefault();
            $.ajax({
                type: 'GET',
                url: '/promodiser/inquire_delivery',
                data: {
                    ste: $('#ste-search').val(),
                },
                success: function(response){
                    $('#inquire-form').html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
                }
            });
        });

        $(document).on('submit', '#receive-form', function (e){
            e.preventDefault();
            $.ajax({
                type: 'GET',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if(response.success){
                        $('#inquire-form').html('<h5 class="p-2">Please enter the STE number</h5>');
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
    });
</script>
@endsection