@extends('layout', [
    'namePage' => 'Stock Transfer Request Form',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card">
                        <div class="card-header text-center text-uppercase bg-info p-2">
                            @switch($action)
                                @case('For Return')
                                    <h6 class="text-center p-1 m-0 font-weight-bold">Item Pull Out Request</h6>
                                    @break
                                @default
                                    <h6 class="text-center p-1 m-0 font-weight-bold">Store-to-Store Transfer Request</h6>
                                    @break
                            @endswitch
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <form action="/stock_transfer/submit" method="POST">
                                @csrf
                                @switch($action)
                                    @case('For Return')
                                        <input type="hidden" name="transfer_as" value="Pull Out">
                                        <div class="row p-1" id="source" style="font-size: 9pt">
                                            <div class="container">
                                                <label for="source_warehouse">Source Warehouse</label>
                                                <select name="source_warehouse" id='src-warehouse' class="form-control" required style="font-size: 9pt">
                                                    <option value="" disabled {{ count($assignedConsignmentStores) > 1 ? 'selected' : null }}>Select Source Warehouse</option>
                                                    @foreach ($assignedConsignmentStores as $store)
                                                        <option value="{{ $store }}" {{ count($assignedConsignmentStores) == 1 ? 'selected' : null }}>{{ $store }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row p-1 mt-2" id="target" style="font-size: 9pt">
                                            <div class="container">
                                                <label for="source_warehouse">Target Warehouse</label>
                                                <input type="text" class="form-control" style="font-size: 9pt;" value="Fumaco - Plant 2" readonly>
                                                <input type="text" class="form-control d-none" style="font-size: 9pt;" name="default_warehouse" value="Quarantine Warehouse - FI" readonly>
                                            </div>
                                        </div>
                                        @break
                                    @default
                                        <input type="hidden" name="transfer_as" value="Store Transfer">
                                        <div class="row p-1" id="source" style="font-size: 9pt">
                                            <div class="container">
                                                <label for="source_warehouse">Source Warehouse</label>
                                                <select name="source_warehouse" id='src-warehouse' class="form-control" required style="font-size: 9pt">
                                                    <option value="" disabled {{ count($assignedConsignmentStores) > 1 ? 'selected' : null }}>Select Source Warehouse</option>
                                                    @foreach ($assignedConsignmentStores as $store)
                                                        <option value="{{ $store }}" {{ count($assignedConsignmentStores) == 1 ? 'selected' : null }}>{{ $store }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row p-1 mt-2" id="source" style="font-size: 9pt">
                                            <div class="container">
                                                <label for="source_warehouse">Target Warehouse</label>
                                                <select name="target_warehouse" id="target-warehouse" class="form-control" style="font-size: 9pt"></select>
                                            </div>
                                        </div>
                                        @break
                                @endswitch
                                <small class="font-italic text-danger d-none warehouse-err" style="font-size: 8pt;">* Please select a warehouse</small>
                                <div class="row p-1 mt-3" id="items-to-return">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-12 mb-2">
                                                @switch($action)
                                                    @case('For Return')
                                                        <div style="font-size: 14px;" class="font-weight-bold text-uppercase text-center">Item(s) to Pullout</div>
                                                        @break
                                                    @default
                                                        <div style="font-size: 14px;" class="font-weight-bold text-uppercase text-center">Item(s) to Transfer</div>
                                                        @break
                                                @endswitch
                                            </div>
                                            <div class="col-9">
                                                <input type="text" class="form-control form-control-sm" id="item-search" name="search" autocomplete="off" placeholder="Search"/>
                                            </div>
                                            <div class="col-3">
                                                <button type="button" class="btn btn-primary btn-block btn-sm" id="open-item-modal" data-target="#add-item-Modal"><i class="fa fa-plus"></i> Add</button>

                                                <div class="modal fade" id="add-item-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-navy">
                                                                <h5 class="modal-title">Add an Item</h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-1">
                                                                <div class="p-2 mb-3">
                                                                    <select id="received-items" class="form-control" style="font-size: 11px;"></select>
                                                                </div>
                                                                <div class="p-0 d-none" id="items-container">
                                                                    <table class="table" id='items-selection-table' style="font-size: 11px;">
                                                                        <thead class="text-uppercase">
                                                                            <th class="text-center" style="width: 30%">Item Code</th>
                                                                            <th class="text-center" style="width: 35%"><span class='qty-col'>Current Qty</span></th>
                                                                            <th class="text-center transfer-text" style="width: 35%">Transfer Qty</th>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td class="text-center p-0">
                                                                                    <div class="d-flex flex-row justify-content-center align-items-center">
                                                                                        <div class="p-1 col-4 text-center">
                                                                                            <picture>
                                                                                                <source srcset="" id='webp-src-display' type="image/webp">
                                                                                                <source srcset="" id='img-src-display' type="image/jpeg">
                                                                                                <img src="" alt="" id='img-src' alt="" width="40" height="40">
                                                                                            </picture>
                                                                                            <div class="d-none">
                                                                                                <span id="img-text"></span>
                                                                                                <span id="webp-text"></span>
                                                                                                <span id="alt-text"></span>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="p-1 col-8 m-0">
                                                                                            <span id='item-code-text' class="font-weight-bold"></span>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="text-center p-0 align-middle">
                                                                                    <span class="d-block font-weight-bold" id="stocks-text"></span>
                                                                                    <small id="uom-text"></small>
                                                                                </td>
                                                                                <td class="text-center p-2 align-middle">
                                                                                    <input type="text" class="form-control form-control-sm qty" value="0" id="qty-input" data-max="0" style="text-align: center;">
                                                                                </td>
                                                                              
                                                                            </tr>
                                                                            <tr class="border-bottom">
                                                                                <td class="border-top-0 p-1" colspan="3">
                                                                                    <span id="description-text"></span>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                    
                                                                    <div class="mb-2 pr-2 pl-2 pt-0 pb-2 {{ !in_array($action, ['For Return']) ? 'd-none' : null }}">
                                                                        <label for="sales-return-reason" style="font-size: 11px;">Reason <span class="text-danger">*</span></label>
                                                                        @php
                                                                            $salesReturnReason = ['Defective', 'Pull Out Item'];
                                                                        @endphp
                                                                        <select id="sales-return-reason" class="form-control" style="font-size: 11px;" {{ ($action == 'For Return') ? 'required' : '' }}>
                                                                            @foreach ($salesReturnReason as $reason)
                                                                            <option value="{{ $reason }}">{{ $reason }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" id="add-item" class="btn btn-primary w-100" disabled><i class="fas fa-plus"></i> Add item</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="container-fluid mt-2">
                                        <table class="table" id="items-table" style="font-size: 11px;">
                                            <thead class="text-uppercase bg-light">
                                                <th class="text-center p-2" style="width: 35%">Item Code</th>
                                                <th class="text-center p-2" style="width: 30%"><span class='qty-col'>Current Qty</span></th>
                                                <th class="text-center p-2 transfer-text" style="width: 30%">Transfer Qty</th>
                                                <th class="text-center p-2" style="width: 5%">-</th>
                                            </thead>
                                            <tbody>
                                                <tr id="placeholder" class="border-bottom">
                                                    <td colspan="4" class="text-center text-muted text-uppercase">Please Select an Item</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="container-fluid mt-2">
                                        <textarea name="remarks" rows="5" class="form-control" placeholder="Remarks" style="font-size: 11px; font-family: inherit"></textarea>
                                    </div>

                                    <div class="container-fluid mt-3 text-center">
                                        <button type="submit" id="submit-btn" class="btn btn-primary w-100 d-none submit-once"><i class="fas fa-check"></i> Submit</button>
                                    </div>

                                    <span id="counter" class='d-none'>0</span>
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
            var form_purpose = '{{ $action }}';

            const formatState = (opt) => {
                if (!opt.id) {
                    return opt.text;
                }

                var optimage = opt.img;

                if(!optimage){
                    return opt.text;
                } else {                    
                    var $opt = $(
                        '<span style="font-size: 10pt;">' +
                            '<img src="' + optimage + '" width="50px" />  ' + opt.text +
                        '</span>'
                    );
                    return $opt;
                }
            };

            $(document).on('click', '#open-item-modal', function (){
                var modal = $(this).data('target');
                var no_err = true;
                switch (form_purpose) {
                    case 'For Return':
                        if($('#src-warehouse').val() != null && $('#src-warehouse').val() != ''){
                            no_err = true;
                        }else{
                            no_err = false;
                        }
                        break;
                    case 'Sales Return':
                        if($('#target-warehouse').val() != null && $('#target-warehouse').val() != ''){
                            no_err = true;
                        }else{
                            no_err = false;
                        }
                        break;
                    default:
                        if($('#src-warehouse').val() != null && $('#src-warehouse').val() != ''
                        && $('#target-warehouse').val() != null && $('#target-warehouse').val() != ''){
                            no_err = true;
                        }else{
                            no_err = false;
                        }
                        break;
                }
                if(no_err){
                    open_modal(modal);
                    $('.warehouse-err').addClass('d-none');
                }else{
                    $('.warehouse-err').removeClass('d-none');
                }
            });

            get_received_items($('#src-warehouse').val());

            $('#src-warehouse').change(function(){
                var src = $(this).val();
                get_received_items(src);

                $('#target-warehouse').attr("disabled", false);

                $('#placeholder').removeClass('d-none');
                $('#items-container').addClass('d-none');
                $("#received-items").empty().trigger('change');

                $('.items-list').each(function() {
                    var item_code = $(this).val();
                    remove_items(item_code);
                });
                
                $('#open-item-modal').prop('disabled', false);

                reset_placeholders();
                validate_submit();

                items_array = [];
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
                            assigned_to_me: form_purpose == 'Sales Return' ? 1 : 0
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
                if(form_purpose == 'Sales Return'){
                    var warehouse = e.params.data.id;
                    get_received_items(warehouse);
                    
                    $('.items-list').each(function() {
                        var item_code = $(this).val();
                        remove_items(item_code);
                    });
                    items_array = [];

                    validate_submit();
                    reset_placeholders();

                    $('#placeholder').removeClass('d-none');
                    $('#open-item-modal').prop('disabled', false);
                }
            });

            function get_received_items(branch){
                $('#received-items').select2({
                    templateResult: formatState,
                    placeholder: 'Select an Item',
                    allowClear: true,
                    ajax: {
                        url: '/beginning_inv/get_received_items/' + branch,
                        method: 'GET',
                        dataType: 'json',
                        data: function (data) {
                            return {
                                q: data.term, // search term
                                excluded_items: items_array,
                                purpose: form_purpose
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

                $('.validate.qty.to-return').each(function (){
                    var max = $(this).data('max');
                    var val = $(this).val().replace(/,/g, '');

                    if($.isNumeric(val) && parseInt(val) > 0){
                        $(this).css('border', '1px solid #CED4DA');
                        inputs.push(1);
                        if(form_purpose != 'Sales Return' && parseInt(val) > parseInt(max)){
                            $(this).css('border', '1px solid red');
                            inputs.push(0);    
                        }
                    }else{
                        $(this).css('border', '1px solid red');
                        inputs.push(0);
                    }
                });

                var stocks_check = inputs.length > 0 ? Math.min.apply(Math, inputs) : 0;

                if(parseInt($('#counter').text()) > 0 && stocks_check == 1){
                    $('#submit-btn').prop('disabled', false);
                }else{
                    $('#submit-btn').prop('disabled', true);
                }
            }

            function remove_items(item_code){
                $('.row-' + item_code).remove();
                var val = parseInt($('#counter').text()) - 1;

                items_array = jQuery.grep(items_array, function(value) {
                    return value != item_code;
                });

                val = val > 0 ? val : 0;
                $('#counter').text(val);
                if (val <= 0) {
                    $('#placeholder').removeClass('d-none');
                }
            }

            function reset_placeholders(){
                $('#qty-input').val(0);
                $('#img-text').text(null);
                $('#alt-text').text(null);
                $('#uom-text').text(null);
                $('#webp-text').text(null);
                $('#stocks-text').text(null);
                $('#qty-input').data('max', 0);
                $('#img-src').attr('src', null);
                $('#item-code-text').text(null);
                $('#description-text').text(null);
                $('#img-src-display').attr('src', null);
                $('#webp-src-display').attr('src', null);
            }
            
            $(document).on('select2:select', '#received-items', function(e){
                $('#img-text').text(e.params.data.img);
                $('#alt-text').text(e.params.data.alt);
                $('#uom-text').text(e.params.data.uom);
                $('#webp-text').text(e.params.data.webp);
                $('#stocks-text').text(e.params.data.max);
                $('#item-code-text').text(e.params.data.id);
                $('#img-src').attr('src', e.params.data.img);
                $('#qty-input').data('max', e.params.data.max);
                $('#img-src-display').attr('src', e.params.data.img);
                $('#webp-src-display').attr('src', e.params.data.webp);
                $('#description-text').text(e.params.data.description);
                
                $('#add-item').prop('disabled', false);
                $('#items-container').removeClass('d-none');
            });

            $("#item-search").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#items-table tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            var items_array = new Array();
            $('#add-item').click(function (){
                var img = $('#img-text').text();
                var alt = $('#alt-text').text();
                var qty = $('#qty-input').val();
                var uom = $('#uom-text').text();
                var webp = $('#webp-text').text();
                var stocks = $('#stocks-text').text();
                var item_code = $('#item-code-text').text();
                var description = $('#description-text').text();
                var selected_reason = $("#sales-return-reason").val();

                var existing = $('#items-table').find('.' + item_code).eq(0).length;
                if (existing) {
                    showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
					return false;
                }

                var row = '<tr class="row-' + item_code + ' ' + item_code + '">'+
                    '<td class="text-center p-0">'+
                        '<input type="hidden" name="item_code[]" class="items-list d-none" value="' + item_code + '" id="' + item_code + '">'+
                        '<div class="d-flex flex-row justify-content-center align-items-center">'+
                            '<div class="p-1 col-4 text-center">'+
                                '<picture>'+
                                    '<source srcset="' + webp + '" type="image/webp">'+
                                    '<source srcset="' + img + '" type="image/jpeg">'+
                                    '<img src="' + img + '" alt="' + alt + '" width="40" height="40">'+
                                '</picture>'+
                            '</div>'+
                            '<div class="p-1 col-8 m-0">'+
                                '<span class="font-weight-bold">' + item_code + '</span>'+
                            '</div>'+
                        '</div>'+
                    '</td>'+
                    '<td class="text-center p-0 align-middle">'+
                        '<span class="d-block font-weight-bold">' + stocks + '</span>'+
                        '<small>' + uom + '</small>'+
                    '</td>'+
                    '<td class="text-center p-2 align-middle">'+
                        '<input type="text" class="form-control form-control-sm validate qty to-return" id="qty-' + item_code + '" value="' + qty + '" data-item-code="' + item_code + '" name="item[' + item_code + '][transfer_qty]" data-max="85" style="text-align: center;">'+
                    '</td>'+
                    '<td class="text-center p-0 align-middle">'+
                        '<a href="#" class="btn btn-danger btn-xs remove-item" data-item-code="' + item_code + '"><i class="fa fa-remove"></i></a>'+
                   '</td>'+
                '</tr>'+
                '<tr class="border-top-0 border-bottom row-' + item_code + ' ' + item_code + '">' +
                    '<td class="border-top-0 p-1" colspan="4">' + 
                        description
                    '</td>' +
                '</tr>';

                if($.inArray(form_purpose, ['For Return']) !== -1){
                    row += '<tr class="border-top-0 border-bottom  row-' + item_code + '">' + 
                        '<td colspan="4" class="text-center p-0">' +
                            '<div class="d-none">' + item_code + '</div>' + // reference for search
                            '<div class="d-none">' + description + '</div>' +
                            '<label class="d-block text-left mb-0 mt-1">Reason <span class="text-danger">*</span></label>' +
                            '<select class="form-control mb-2" name="item[' + item_code + '][reason]" style="font-size: 10pt;" required>' +
                                @foreach ($salesReturnReason as $reason)
                                    '<option value="{{ $reason }}" ' + (selected_reason == '{{ $reason }}' ? 'selected' : '') + '>{{ $reason }}</option>' + 
                                @endforeach
                            '</select>' +
                        '</td>' +
                    '</tr>';
                }

                if(jQuery.inArray(item_code, items_array) === -1){
                    items_array.push(item_code);
                }

                $('#counter').text(parseInt($('#counter').text()) + 1);
                $("#received-items").empty().trigger('change');
                $('#items-container').addClass('d-none');
                $('#submit-btn').removeClass('d-none');
                $('#add-item').prop('disabled', true);
                $('#items-table tbody').prepend(row);
                $('#placeholder').addClass('d-none');

                close_modal('#add-item-Modal');
                reset_placeholders();
                validate_submit();
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

            cut_text();
            var showTotalChar = 90, showChar = "Show more", hideChar = "Show less";
            function cut_text(){
                $('.item-description').each(function() {
                    var content = $(this).text();
                    if (content.length > showTotalChar) {
                        var con = content.substr(0, showTotalChar);
                        var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                        var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                        $(this).html(txt);
                    }
                });
            }

            $('table#items-table').on('click', '.show-more', function(e){
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