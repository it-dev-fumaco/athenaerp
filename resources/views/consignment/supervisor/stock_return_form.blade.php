@extends('layout', [
    'namePage' => 'Stock Transfers Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-3">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/stocks_report/list';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-7 col-lg-6 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Return Form</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body">
                            <form action="/stock_transfer/submit" id="stock-transfer-form" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-12 col-xl-6" style="font-size: 10pt;">
                                        <label>Source Warehouse</label>
                                        <select name="source_warehouse" id="source-warehouse" class="form-control" required style="border: 1px solid red !important">
                                            <option value="" selected>Select source warehouse</option>
                                            @foreach ($consignment_warehouses as $consignment)
                                                <option value="{{ $consignment->name }}">{{ $consignment->name }}</option>
                                            @endforeach
                                        </select>
                                        <small id="source-warehouse-err" class="d-none" style="color: #DC3545">Please select source warehouse</small>
                                    </div>
                                    <div class="col-12 col-xl-6" style="font-size: 10pt;">
                                        <label>Target Warehouse</label>
                                        <select name="target_warehouse" id="target-warehouse" class="form-control" required>
                                            <option value="" selected>Select target warehouse</option>
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->name }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        <small id="target-warehouse-err" class="d-none" style="color: #DC3545">Please select target warehouse</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-2 offset-10 p-2">
                                        <button type="button" class="btn btn-sm btn-primary w-100" id="items-modal-btn" data-target="#addItemsModal">
                                            <i class="fas fa-plus"></i> Add an Item
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-none">
                                            <input type="text" class="form-control form-control-sm" value="Material Transfer" readonly>
                                            <input type="text" class="form-control form-control-sm" name="transfer_as" value="For Return" readonly>
                                        </div>
                                        <table class="table table-striped table-bordered" id="items-table" style="font-size: 10pt;">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Item Details</th>
                                                    <th class="text-center">Current Qty</th>
                                                    <th class="text-center">Qty to Return</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="placeholder-row">
                                                    <td class="text-center" colspan=3>
                                                        Please select an item
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-12">
                                        <textarea name="remarks" placeholder="Remarks..." rows="5" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12 pt-2">
                                        <button type="button" class="btn btn-primary w-100" id="submit-btn" disabled>Submit</button>
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

<!-- Modal -->
<div class="modal fade" id="addItemsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title">Add an Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: #fff">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row p-2">
                    <select id="item-selection" class="form-control"></select>
                </div>
                <div class="row d-none" id="items-container">
                    <table class="table table-bordered table-striped" style="font-size: 10pt;">
                        <col style="width: 33%;">
                        <col style="width: 33%;">
                        <col style="width: 33%;">
                        <tr>
                            <th class="text-center">Item</th>
                            <th class="text-center">Current Qty</th>
                            <th class="text-center">Qty to Return</th>
                        </tr>
                        <tr>
                            <td class="text-center">
                                <div class="row">
                                    <div class="col-3 p-0">
                                        <picture>
                                            <source srcset="" id="new-src-img-webp" type="image/webp">
                                            <source srcset="" id="new-src-img" type="image/jpeg">
                                            <img src="" alt="" id="new-img" class="img-responsive" style="width: 40px; height: 40px;">
                                        </picture>
                                        <div class="d-none">
                                            <span id="new-img-txt"></span>
                                            <span id="new-webp-txt"></span>
                                        </div>
                                    </div>
                                    <div class="col-8" style="display: flex; justify-content: center; align-items: center;">
                                        <b id="new-item-code" class="placeholder-text"></b>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <b id="current-qty"></b><br><span id="uom"></span>
                            </td>
                            <td class="text-center">
                                <div class="input-group p-1 mx-auto">
                                    <div class="input-group-prepend p-0">
                                        <button type="button" class="btn btn-outline-danger btn-xs qtyminus" data-reference-id=".new-item-qty" style="padding: 0 5px 0 5px;" type="button">-</button>
                                    </div>
                                    <div class="custom-a p-0">
                                        <input type="text" class="form-control form-control-sm new-item-qty" id="new-qty-to-transfer" value="0" style="text-align: center; width: 47px">
                                    </div>
                                    <div class="input-group-append p-0">
                                        <button type="button" class="btn btn-outline-success btn-xs qtyplus" data-reference-id=".new-item-qty" style="padding: 0 5px 0 5px;" type="button">+</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-justify" colspan=3>
                                <span id="new-description" class="placeholder-text"></span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id='add-item' disabled>Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title"><i class="fa fa-info"></i>&nbsp;Error</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: #fff">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 9pt;">
                Please remove the item(s) without quantity: <br>
                You can remove an item by clicking (&times;) 
                <ul class="err-items"></ul>
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
        $(document).ready(function(){
            $('#source-warehouse').select2();
            $('#target-warehouse').select2();

            $(document).on('select2:select', '#source-warehouse', function(e){
                $('#item-selection').select2({
                    templateResult: formatState,
                    // templateSelection: formatState,
                    placeholder: 'Select an Item',

                    ajax: {
                        url: '/beginning_inv/get_received_items/' + $('#source-warehouse').val(),
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

                $("#item-selection").empty().trigger('change');
                $('#items-container').addClass('d-none');
                $('#add-item').prop('disabled', true);
                $('.item-rows').remove();
                validate_submit();
            });

            $(document).on('select2:select', '#item-selection', function(e){
                $('#uom').text(e.params.data.uom); // uom
                $('#new-img-txt').text(e.params.data.img); // image
                $('#new-webp-txt').text(e.params.data.webp); // webp
                $('#new-img').attr('src', e.params.data.img); // image
                $('#new-item-code').text(e.params.data.id); // item code
                $('#current-qty').text(e.params.data.max); // current qty
                $('#new-src-img').attr('src', e.params.data.img); // image
                $('#new-src-img-webp').attr('src', e.params.data.webp); // webp
                $('#new-description').text(e.params.data.description); // description

                $('#add-item').prop('disabled', false);
                $('#items-container').removeClass('d-none');
            });

            $(document).on('click', '#items-modal-btn', function (){
                if($('#source-warehouse').val() == ''){
                    $('#source-warehouse-err').removeClass('d-none');
                    $('#target-warehouse-err').addClass('d-none');
                }else if($('#target-warehouse').val() == ''){
                    $('#target-warehouse-err').removeClass('d-none');
                    $('#source-warehouse-err').addClass('d-none');
                }else{
                    $('#source-warehouse-err').addClass('d-none');
                    $('#target-warehouse-err').addClass('d-none');
                    open_modal($(this).data('target'));
                }
            });

            function reset_placeholders(){
                $('#uom').text(''); // uom
                $('#new-img-txt').text(''); // image
                $('#new-webp-txt').text(''); // webp
                $('#new-img').attr('src', ''); // image
                $('#current-qty').text(''); // current qty
                $('#new-item-code').text(''); // item code
                $('#new-src-img').attr('src', ''); // image
                $('#new-description').text(''); // description
                $('#new-src-img-webp').attr('src', ''); // webp
            }

            function formatState (opt) {
                if (!opt.id) {
                    return opt.text;
                }

                var optimage = opt.webp;
                if(optimage.indexOf('/icon/no_img') != -1){
                    optimage = opt.img;
                }

                if(!optimage){
                    return opt.text;
                } else {
                    var $opt = $(
                    '<span><img src="' + optimage + '" width="40px" /> ' + opt.text + '</span>'
                    );
                    return $opt;
                }
            };

            $(document).on('click', '.remove-item', function (){
                var item_code = $(this).data('item-code');
                $(item_code).remove();

                validate_submit();
            });

            $(document).on('click', '.qtyplus', function(){
                var qty = $(this).data('reference-id');
                var val = $.isNumeric($(qty).val()) ? parseInt($(qty).val()) : 0;
                $(qty).val(val + 1);
                validate_submit();
            });

            $(document).on('click', '.qtyminus', function(){
                var qty = $(this).data('reference-id');
                if($.isNumeric($(qty).val())){
                    var val = parseInt($(qty).val());
                    if(val > 0){
                        $(qty).val(val - 1);
                    }else{
                        $(qty).val(0);
                    }
                }
                validate_submit();
            });

            $(document).on('click', '#submit-btn', function(e){
                e.preventDefault();
                validate_submit(1);
            });

            validate_submit();
            function validate_submit(submit){
                var counter = $('.items').length;

                var err = 0;
                var row = '';
                $('.current-qty').each(function (){
                    var item_code = $(this).data('item-code');
                    var reference = $(this).data('reference-qty');
                    var current_qty = $.isNumeric($(this).text()) ? parseInt($(this).text()) : 0;
                    var qty_to_transfer = $.isNumeric($(reference).val()) ? parseInt($(reference).val()) : 0;

                    if(current_qty <= 0 || qty_to_transfer > current_qty || qty_to_transfer <= 0){
                        $(reference).css('border', '1px solid red');
                        row += '<li>' + item_code + '</li>';
                        err = 1;
                    }else{
                        $(reference).css('border', '1px solid #aaa');
                    }
                });

                if(counter <= 0){
                    $('#placeholder-row').removeClass('d-none');
                    $('#submit-btn').prop('disabled', true);
                }else{
                    $('#placeholder-row').addClass('d-none');
                    $('#submit-btn').prop('disabled', false);
                }

                if(submit == 1){
                    if(err == 1){
                        $('.err-items').empty();
                        $('.err-items').append(row);
                        $('#errorModal').modal('show');
                    }else{
                        $('#stock-transfer-form').submit();
                    }
                }
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

            $('#add-item').click(function (){
                var uom = $('#uom').text();
                var img = $('#new-img-txt').text();
                var alt = $('#new-img-txt').text();
                var webp = $('#new-webp-txt').text();
                var qty = $('#new-qty-to-transfer').val();
                var item_code = $('#new-item-code').text();
                var current_qty = $('#current-qty').text();
                var description = $('#new-description').text();

                var existing = $('#items-table').find('.' + item_code).eq(0).length;
                if (existing) {
                    showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
					return false;
                }

                var row = '<tr class="' + item_code + ' items item-rows">' +
                    '<td class="text-center">' +
                        '<div class="row">' +
                            '<div class="col-3 p-0">' +
                                '<picture>' +
                                    '<source srcset="' + webp + '" type="image/webp">' +
                                    '<source srcset="' + img + '" type="image/jpeg">' +
                                    '<img src="' + img + '" alt="' + img + '" class="img-responsive" style="width: 40px; height: 40px;">' +
                                '</picture>' +
                            '</div>' +
                            '<div class="col-8" style="display: flex; justify-content: center; align-items: center;">' +
                                '<b>' + item_code + '</b>' +
                                '<input class="d-none" name="item_code[]" value="' + item_code + '"/>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<b class="current-qty" data-reference-qty=".' + item_code + '-qty" data-item-code="' + item_code + '">' + current_qty + '</b><br><span>' + uom + '</span>' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<div class="row">' +
                            '<div class="offset-4 col-4">' +
                                '<div class="input-group">' +
                                    '<div class="input-group-prepend p-0">' +
                                        '<button type="button" class="btn btn-outline-danger btn-xs qtyminus" data-reference-id=".' + item_code + '-qty" style="padding: 0 5px 0 5px;" type="button">-</button>' +
                                    '</div>' +
                                    '<div class="custom-a p-0">' +
                                        '<input type="text" class="form-control form-control-sm qty item-stock ' + item_code + '-qty" name="item[' + item_code + '][transfer_qty]" id="qty-to-transfer" value="' + qty + '" style="text-align: center; width: 47px">' +
                                    '</div>' +
                                    '<div class="input-group-append p-0">' +
                                        '<button type="button" class="btn btn-outline-success btn-xs qtyplus" data-reference-id=".' + item_code + '-qty" style="padding: 0 5px 0 5px;" type="button">+</button>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="offset-2 col-2 remove-item" data-item-code=".' + item_code + '" style="display: flex; justify-content: center; align-items: center; cursor: pointer;">' +
                                '<i class="fas fa-remove" style="color: #DC3545"></i>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                '</tr>' +
                '<tr class="' + item_code + ' item-rows">' +
                    '<td colspan=3 class="text-left p-2">' +
                        '<span>' + description + '</span>' +
                    '</td>' +
                '</tr>';

                $("#item-selection").empty().trigger('change');
                $('#items-container').addClass('d-none');
                $('#add-item').prop('disabled', true);
                $('#items-table tbody').prepend(row);
                $('#placeholder-row').addClass('d-none');
                
                close_modal('#addItemsModal');
                reset_placeholders();
                validate_submit();
            });
        });
    </script>
@endsection