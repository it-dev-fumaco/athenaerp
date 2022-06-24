@extends('layout', [
    'namePage' => 'Request Stock Transfer',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <h6 class="text-center mt-1 font-weight-bold">Stock Transfer Request</h6>
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">{{ \Carbon\Carbon::now()->format('F d, Y') }}</span>
                        </div>
                        <div class="card-body p-1">
                            <form action="/stock_transfer/submit" method="post">
                                @csrf
                                <div class="row p-1" style="font-size: 9pt">
                                    @php
                                        $purpose = ['Store Transfer', 'For Return', 'Sales Return'];
                                    @endphp
                                    <div class="col-2 pt-2">
                                        <label for="transfer_as">Purpose</label>
                                    </div>
                                    <div class="col-10 pt-1">
                                        <select name="transfer_as" id='transfer-as' class="form-control" required style="font-size: 9pt">
                                            <option value="" disabled selected>Select Purpose</option>
                                            @foreach ($purpose as $p)
                                                <option value="{{ $p }}">{{ $p }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row p-1" id="source" style="font-size: 9pt">
                                    <div class="col-2 pt-2">
                                        <label for="source_warehouse">From</label>
                                    </div>
                                    <div class="col-10">
                                        <select name="source_warehouse" id='src-warehouse' class="form-control" required style="font-size: 9pt">
                                            <option value="" disabled selected>Select Source Warehouse</option>
                                            @foreach ($assigned_consignment_stores as $store)
                                                <option value="{{ $store }}">{{ $store }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row p-1 mt-2" id="target" style="font-size: 9pt; display: none">
                                    <div class="col-2 pt-2">
                                        <label for="target_warehouse">To</label>
                                    </div>
                                    <div class="col-10">
                                        <input type="text" name="default_warehouse" id="wh-for-return" class="form-control" value="Fumaco - Plant 2" readonly style="font-size: 10pt">
                                        <div id="target-warehouse-container">
                                            <select name="target_warehouse" id="target-warehouse" class="form-control" disabled style="font-size: 9pt"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row p-1 mt-2" id="items-to-return" style="display: none">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-8">
                                                <select id="received-items" class="form-control" style="font-size: 9pt;"></select>
                                            </div>
                                            <div class="col-4">
                                                <button type="button" class="btn btn-primary w-100" id="add-item" style="font-size: 10pt" disabled><i class="fa fa-plus"></i> Add item</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="container-fluid mt-2">
                                        <table class="table" id='items-table' style="font-size: 9pt">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 40%">Item</th>
                                                    <th class="text-center" style="width: 25%">Stocks</th>
                                                    <th class="text-center" id="transfer-text">Qty to Transfer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="placeholder">
                                                    <td colspan=3 class='text-center'>Please Select an Item</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="container-fluid mt-2 text-center">
                                        <button type="submit" id="submit-btn" class="btn btn-primary d-none">Submit</button>
                                    </div>

                                    <div class="d-none"><!-- hidden values, acts as placeholder -->
                                        <span id="item-code"></span>
                                        <span id="description"></span>
                                        <span id="img"></span>
                                        <span id="webp"></span>
                                        <span id="alt"></span>
                                        <span id="stocks"></span>
                                        <span id="uom"></span>
                                        <span id="counter">0</span>
                                    </div>
                                </div>
                            </form>
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
        table {
            table-layout: fixed;
            width: 100%;   
        }
        .morectnt span {
            display: none;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            $('#transfer-as').change(function (){
                $('#target').slideDown();
                var src = $('#src-warehouse').val();
                if($(this).val() == 'Store Tranfer'){ // Stock Transfers
                    if($('#source').is(':hidden')){
                        $('#source').slideDown();
                    }
                    $('#wh-for-return').addClass('d-none');

                    $('#target-warehouse-container').removeClass('d-none');
                    $('#target-warehouse').prop('required', true);
                    if($('#src-warehouse').val() == ''){
                        $('#target-warehouse').attr("disabled", true);
                    }

                    $('#src-warehouse').prop('required', true);
                    $('#transfer-text').text('Qty to Transfer');
                }else if($(this).val() == 'For Return'){ // For Return
                    if($('#source').is(':hidden')){
                        $('#source').slideDown();
                    }
                    $('#wh-for-return').removeClass('d-none');

                    $('#target-warehouse').prop('required', false);
                    $('#target-warehouse-container').addClass('d-none');

                    $('#src-warehouse').prop('required', true);
                    $('#transfer-text').text('Qty to Transfer');

                    $('#items-to-return').slideDown();
                }else{ // sales returns
                    $('#wh-for-return').addClass('d-none');

                    $('#target-warehouse-container').removeClass('d-none');
                    $('#target-warehouse').prop('required', true);
                    $('#target-warehouse').attr("disabled", false);

                    $('#src-warehouse').prop('required', false);
                    $('#transfer-text').text('Qty Returned');

                    if($('#source').is(':visible')){
                        $('#source').slideUp();
                    }

                    src = null;
                }

                $("#target-warehouse").empty().trigger('change');
                $("#received-items").empty().trigger('change');
                get_received_items(src);
                reset_placeholders();
            });

            $('#src-warehouse').change(function(){
                var src = $(this).val();
                get_received_items(src);

                $('#target-warehouse').attr("disabled", false);

                $('#placeholder').removeClass('d-none');
                $("#received-items").empty().trigger('change');

                $('.items-list').each(function() {
                    var item_code = $(this).val();
                    remove_items(item_code);
                });
                
                reset_placeholders();
                validate_submit();
            });

            $('#target-warehouse').select2({
                placeholder: 'Select Target Warehouse',
                allowClear: true,
                ajax: {
                    url: '/consignment_stores',
                    method: 'GET',
                    dataType: 'json',
                    data: function (data) {
                        return {
                            q: data.term, // search term
                            assigned_to_me: $('#transfer-as').val() == 'Sales Return' ? 1 : 0
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

            $(document).on('select2:select', '#target-warehouse', function(e){
                $('#items-to-return').slideDown();
                if($('#transfer-as').val() == 'Sales Return'){
                    var warehouse = e.params.data.text;
                    get_received_items(warehouse);
                    
                    $('.items-list').each(function() {
                        var item_code = $(this).val();
                        remove_items(item_code);
                    });
                    validate_submit();
                    reset_placeholders();

                    $('#placeholder').removeClass('d-none');
                }
            });

            function get_received_items(branch){
                $('#received-items').select2({
                    placeholder: 'Select an Item',
                    allowClear: true,
                    ajax: {
                        url: '/beginning_inv/get_received_items/' + branch,
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
            }
            
            validate_submit();
            function validate_submit(){
                var inputs = new Array();
                var max_check = new Array();

                $('.to-return').each(function (){
                    var max = $(this).data('max');
                    var val = $(this).val();

                    if(val <= max){
                        $(this).css('border', '1px solid #CED4DA');
                    }else{
                        $(this).css('border', '1px solid red');
                    }

                    inputs.push(val);
                    max_check.push((max >= val ? 1 : 0));
                });

                var stocks_checker = Math.min.apply(Math, max_check);

                var min = Math.min.apply(Math, inputs);
                if(min > 0){
                    $('#counter').text(inputs.length);
                }else{
                    $('#counter').text(0);
                }

                if(parseInt($('#counter').text()) > 0 && inputs.length > 0 && stocks_checker == 1){
                    $('#submit-btn').prop('disabled', false);
                }else{
                    $('#submit-btn').prop('disabled', true);
                }
            }

            function remove_items(item_code){
                $('#' + item_code).val('');
                $('.row-' + item_code).addClass('d-none');
                $('#qty-' + item_code).removeClass('to-return');
                $('#qty-' + item_code).attr('name', '');
            }

            function reset_placeholders(){
                $('#item-code').text('');
                $('#description').text('');
                $('#img').text('');
                $('#webp').text('');
                $('#alt').text('');
                $('#stocks').text('');
                $('#uom').text('');

                $('#add-item').prop('disabled', true);
            }
            
            $(document).on('select2:select', '#received-items', function(e){
                $('#item-code').text(e.params.data.id);
                $('#description').text(e.params.data.description);
                $('#img').text(e.params.data.img);
                $('#webp').text(e.params.data.webp);
                $('#alt').text(e.params.data.alt);
                $('#stocks').text(e.params.data.max);
                $('#uom').text(e.params.data.uom);
                
                $('#add-item').prop('disabled', false);
            });

            $('#add-item').click(function (){
                var item_code = $('#item-code').text();
                var description = $('#description').text();
                var img = $('#img').text();
                var webp = $('#webp').text();
                var alt = $('#alt').text();
                var stocks = $('#stocks').text();
                var uom = $('#uom').text();

                var row = '<tr class="row-' + item_code + '">' +
                    '<td colspan=3 class="text-center p-0">' +
                        '<div class="row">' +
                            '<input name="item_code[]" class="items-list d-none" value="' + item_code + '" id="' + item_code + '" />' +
                            '<div class="p-1 col-2 text-center">' +
                                '<picture>' +
                                    '<source srcset="' + webp + '" type="image/webp">' +
                                    '<source srcset="' + img + '" type="image/jpeg">' +
                                    '<img src="' + img + '" alt="' + alt + '" class="img-thumbnail" alt="User Image" width="40" height="40">' +
                                '</picture>' +
                            '</div>' +
                            '<div class="p-1 col-2 m-0" style="display: flex; justify-content: center; align-items: center;">' +
                                '<span class="font-weight-bold">' + item_code + '</span>' +
                            '</div>' +
                            '<div class="col-3 offset-1" style="display: flex; justify-content: center; align-items: center; height: 44px">' +
                                '<span><b>' + stocks + '</b></span>&nbsp;<small>' + uom + '</small>' +
                            '</div>' +
                            '<div class="col p-0">' +
                                '<div class="input-group p-1 ml-2">' +
                                    '<div class="input-group-prepend p-0">' +
                                        '<button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>' +
                                    '</div>' +
                                    '<div class="custom-a p-0">' +
                                        '<input type="text" class="form-control form-control-sm qty validate to-return" id="qty-' + item_code + '" value="0" data-item-code="' + item_code + '" name="item[' + item_code + '][transfer_qty]" data-max="' + stocks + '" style="text-align: center; width: 40px">' +
                                    '</div>' +
                                    '<div class="input-group-append p-0">' +
                                        '<button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-1 text-center remove-item" data-item-code="' + item_code + '">' +
                                '<i class="fa fa-remove" style="color: red"></i>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                '</tr>' +
                '<tr class="row-' + item_code + '">' +
                    '<td colspan=3 class="text-justify p-2" style="font-size: 10pt;">' +
                        '<div class="item-description">' + description + '</div>' +
                    '</td>' +
                '</tr>';

                $('#items-table tbody').prepend(row);
                $('#submit-btn').removeClass('d-none');
                $('#placeholder').addClass('d-none');

                $("#received-items").empty().trigger('change');
                reset_placeholders();
                cut_text();
            });

            $('table#items-table').on('keyup', '.validate', function (e){
                validate_submit();
            })

            $('table#items-table').on('click', '.remove-item', function (e){
                e.preventDefault();
                var item_code = $(this).data('item-code');

                remove_items(item_code);
                validate_submit();
            });

            $('table#items-table').on('click', '.qtyplus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                var max = fieldName.data('max');
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If is not undefined
                if (!isNaN(currentVal)) {
                    // Increment
                    if (currentVal < max) {
                        fieldName.val(currentVal + 1);
                    }
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }

                validate_submit();
            });

            // This button will decrement the value till 0
            $('table#items-table').on('click', '.qtyminus', function(e){
                // Stop acting like a button
                e.preventDefault();
                // Get the field name
                var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
                // Get its current value
                var currentVal = parseInt(fieldName.val());
                // If it isn't undefined or its greater than 0
                if (!isNaN(currentVal) && currentVal > 0) {
                    // Decrement one
                    fieldName.val(currentVal - 1);
                } else {
                    // Otherwise put a 0 there
                    fieldName.val(0);
                }

                validate_submit();
            });

            cut_text();
            function cut_text(){
                var showTotalChar = 90, showChar = "Show more", hideChar = "Show less";
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
            }
        });
    </script>
@endsection