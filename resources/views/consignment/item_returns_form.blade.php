@extends('layout', [
    'namePage' => 'Item Return',
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
                            <h6 class="text-center mt-1 font-weight-bold">Item Return</h6>
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">{{ \Carbon\Carbon::now()->format('F d, Y') }}</span>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            @if(session()->has('success'))
                                <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            <form id="submit-form" action="/item_return/submit" method="post">
                                @csrf
                                <input type="hidden" name="transfer_as" value="Sales Return">
                                <div class="row p-1" style="font-size: 9pt">
                                    <div class="container">
                                        <label for="target-warehouse">Branch Warehouse</label>
                                        <div id="target-warehouse-container">
                                            <select name="target_warehouse" id="target-warehouse" class="form-control" style="font-size: 9pt">
                                                <option value="">Select a Branch</option>
                                                @foreach ($assigned_consignment_store as $store)
                                                    <option value="{{ $store }}">{{ $store }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <small class="font-italic text-danger d-none warehouse-err" style="font-size: 8pt;">* Please select a warehouse</small>
                                <div class="row p-1 mt-2" id="items-to-return">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-7">
                                                <input type="text" class="form-control form-control-sm" id="item-search" name="search" autocomplete="off" placeholder="Search"/>
                                            </div>
                                            <div class="col-5">
                                                <button type="button" class="btn btn-primary w-100" id="open-item-modal" style="font-size: 10pt;" data-target="#add-item-Modal"><i class="fa fa-plus"></i> Add item</button>

                                                <div class="modal fade" id="add-item-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-navy">
                                                                <h5 class="modal-title" id="exampleModalLabel">Add an Item</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true" style="color: #fff">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <select id="received-items" class="form-control" style="font-size: 9pt;"></select>
                                                                <br><br>
                                                                <div class="container-fluid d-none" id="items-container" style="font-size: 9pt;">
                                                                    <div class="item-list-warning alert alert-warning p-2 text-center d-none">
                                                                        <i class="fa fa-warning"></i> Item is already in the list.
                                                                    </div>
                                                                    <table class="table" id='items-selection-table' style="font-size: 9pt;">
                                                                        <colgroup>
                                                                            <col style="width: 40%"/>
                                                                            <col style="width: 30%"/>
                                                                            <col style="width: 30%"/>
                                                                        </colgroup>
                                                                        <tr>
                                                                            <th class="text-center p-1">ITEM</th>
                                                                            <th class="text-center p-1">CURRENT QTY</th>
                                                                            <th class="text-center p-1">RETURN QTY</th>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="3">
                                                                                <div class="row">
                                                                                    <div class="p-0 col-2 text-center">
                                                                                        <picture>
                                                                                            <source srcset="" id='webp-src-display' type="image/webp">
                                                                                            <source srcset="" id='img-src-display' type="image/jpeg">
                                                                                            <img src="" alt="" id='img-src' class="img-thumbnailm" alt="User Image" width="40" height="40">
                                                                                        </picture>
                                                                                        <div class="d-none">
                                                                                            <span id="img-text"></span>
                                                                                            <span id="webp-text"></span>
                                                                                            <span id="alt-text"></span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="p-1 col-3 m-0" style="display: flex; justify-content: center; align-items: center;">
                                                                                        <span id='item-code-text' class="font-weight-bold"></span>
                                                                                    </div>
                                                                                    <div class="col-3" style="display: flex; justify-content: center; align-items: center; height: 44px">
                                                                                        <div class="text-center">
                                                                                            <div>
                                                                                                <b><span id="stocks-text"></span></b><br>
                                                                                                <small><span id="uom-text"></span></small>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="col-3 offset-1 p-0">
                                                                                        <input type="number" min=0 pattern="[0-9]*" id="qty-input" inputmode="numeric" class="form-control text-center qty" value="0" style="font-size: 9pt;">
                                                                                    </div>
                                                                                    <div class="col-12 text-justify">
                                                                                        <span id="description-text"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    @php
                                                                        $sales_return_reason = ['Defective', 'Change Item'];
                                                                    @endphp
                                                                    <select id="sales-return-reason" class="form-control" style="font-size: 10pt;">
                                                                        <option value="">Select a reason</option>
                                                                        @foreach ($sales_return_reason as $reason)
                                                                            <option value="{{ $reason }}">{{ $reason }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" id="add-item" class="btn btn-primary w-100" disabled>Add item</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="container-fluid p-1 mt-2">
                                        <table class="table table-striped" id='items-table' style="font-size: 9pt">
                                            <colgroup>
                                                <col style="width: 35%">
                                                <col style="width: 30%">
                                                <col style="width: 35%">
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <th class="text-center p-1">ITEM</th>
                                                    <th class="text-center p-1">CURRENT QTY</th>
                                                    <th class="text-center p-1">RETURN QTY</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="placeholder">
                                                    <td colspan=3 class='text-center'>Please Select an Item</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="container-fluid mt-2">
                                        <textarea name="remarks" rows="5" class="form-control" placeholder="Remarks" style="font-size: 10pt; font-family: inherit"></textarea>
                                    </div>

                                    <div class="container-fluid mt-2 text-center">
                                        <button type="submit" id="submit-btn" class="btn btn-primary w-100 d-none submit-once">Submit</button>
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
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            $(document).on('click', '#open-item-modal', function (){
                if(!$('#target-warehouse').val()){
                    $('.warehouse-err').removeClass('d-none');
                    return false;
                }
                var warehouse = $('#target-warehouse').val();
                get_received_items(warehouse);
                
                $('.warehouse-err').addClass('d-none');
                $('#add-item-Modal').modal('show');
            });

            $(document).on('change', '#target-warehouse', function(e){
                $.each(items_array, function(key, item_code) {
                    remove_items(item_code);
                });
                items_array = [];

                validate_submit();
                reset_placeholders();

                $('#placeholder').removeClass('d-none');
                $('#items-container').addClass('d-none');
                $('#open-item-modal').prop('disabled', false);
            });

            $(document).on('focus', "input[type='number']", function() {
                $(this).select();
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
                    '<span style="font-size: 10pt;"><img src="' + optimage + '" width="50px" /> ' + opt.text + '</span>'
                    );
                    return $opt;
                }
            };

            validate_submit();
            function validate_submit(){
                var form = $('#submit-form');
                var reportValidity = form[0].reportValidity();

                if(parseInt($('#counter').text()) > 0 && reportValidity){
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
                if(!val){
                    $('#placeholder').removeClass('d-none');
                }else{
                    $('#placeholder').addClass('d-none');
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
                $('#sales-return-reason').val(null);
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

                $('#items-container').removeClass('d-none');
                if($.inArray(e.params.data.id, items_array) !== -1){
                    $('.item-list-warning').removeClass('d-none');
                    return false;
                }

                $('.item-list-warning').addClass('d-none');
                $('#add-item').prop('disabled', false);
            });

            $("#item-search").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#items-table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            var items_array = new Array();
            $(document).on('click', '#add-item', function (){
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

                var row = '<tr class="row-' + item_code + '">' +
                    '<td class="text-center p-1">' +
                        '<div class="row mt-1">' +
                            '<div class="col-4">' +
                                '<picture>' +
                                    '<source srcset="' + webp + '" type="image/webp">' +
                                    '<source srcset="' + img + '" type="image/jpeg">' +
                                    '<img src="' + img + '" alt="' + alt + '" alt="User Image" width="40" height="40">' +
                                '</picture>' +
                            '</div>' +
                            '<div class="col-8 d-flex justify-content-center align-items-center">' +
                                '<b>' + item_code + '</b>' +
                            '</div>' +
                        '</div>' +
                        '<div class="d-none">' +
                            description + 
                        '</div>' +
                    '</td>' +
                    '<td class="text-center d-flex justify-content-center align-items-center">' +
                        '<span>' +
                            '<b>' + stocks + '</b><br>' +
                            '<small>' + uom + '</small>' +
                        '</span>' +
                    '</td>' +
                    '<td class="text-center p-1">' +
                        '<div class="row mt-1">' +
                            '<div class="col-9 offset-1">' +
                                '<input type="number" min=0 pattern="[0-9]*" name="item[' + item_code + '][qty]" id="qty-input" inputmode="numeric" class="form-control text-center qty" value="' + qty + '" style="font-size: 9pt;" required>' +
                            '</div>' +
                            '<div class="col-2 d-flex justify-content-center align-items-center remove-item" data-item-code="' + item_code + '">' +
                                '<i class="fa fa-remove text-danger"></i>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                '</tr>' +
                '<tr class="row-' + item_code + '">' +
                    '<td colspan=3 class="text-justify p-2">' +
                        '<div class="d-none">' + item_code + '</div>' +
                        '<div class="item-description">' +
                            description + 
                        '</div>' +
                        '<select class="form-control mt-2" name="item[' + item_code + '][reason]" style="font-size: 10pt;" required>' +
                            @foreach ($sales_return_reason as $reason)
                                '<option value="{{ $reason }}" ' + (selected_reason == '{{ $reason }}' ? 'selected' : '') + '>{{ $reason }}</option>' +
                            @endforeach
                        '</select>' +
                    '</td>' +
                '</tr>';

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