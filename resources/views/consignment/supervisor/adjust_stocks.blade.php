@extends('layout', [
    'namePage' => 'Stock Adjustments',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                <a href="/beginning_inv_list" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Adjustments</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2" id="stock-adjustments-container">
                            @if(session()->has('success'))
                            <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{{ session()->get('success') }}</div>
                            @endif
                            @if(session()->has('error'))
                            <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{{ session()->get('error') }}</div>
                            @endif
                            <form action="/adjust_stocks" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-8 col-lg-10" id="select-warehouse-container">
                                        <select name="warehouse" id="branch-warehouses"></select>
                                        <small class="text-italic d-none" id='empty-warehouse-warning' style="color: red">Please select a branch</small>
                                    </div>
                                    <div class="col-4 col-lg-2">
                                        <button type="button" id="select-items" class="btn btn-primary w-100 p-2" style="font-size: 9pt;"><i class="fa fa-plus"></i> Add Item(s)</button>
                                    </div>
                                </div>

                                <div class="row p-2">
                                    <table id="stock-adjustments-table" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 40%;">Item</th>
                                                <th class="text-center" style="width: 30%;">Qty</th>
                                                <th class="text-center" style="width: 30%;">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="empty-row-placeholder">
                                                <td class="text-center" colspan=3>
                                                    Select an item to adjust
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <br>
                                    <textarea name="notes" rows="5" class="form-control" placeholder="Notes..."></textarea>
                                    <br>&nbsp;
                                    <span id="item-count-stock-adjustment" class="counter d-none">0</span>
                                    <button type="submit" class="btn btn-primary w-100 submit-btn">Submit</button>
                                </div>

                                <!-- Modal -->
                                <div class="modal fade" id="stock-adjustment-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-navy">
                                                <h5 class="modal-title" id="exampleModalLabel">Adjust Stocks</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <select class="items-selection"></select>
                                                <div class="container item-details d-none">
                                                    <table class="table table-bordered">
                                                        <tr>
                                                            <th class="text-center" style='width: 50%;'>Item</th>
                                                            <th class="text-center" style="width: 25%;">Qty</th>
                                                            <th class="text-center" style="width: 25%;">Price</th>
                                                        </tr>
                                                        <tr>
                                                            <td class="p-0" colspan=3>
                                                                <div class="p-0 row">
                                                                    <div class="col-6">
                                                                        <div class="row">
                                                                            <div class="col-4 text-center">
                                                                                <div class="d-none">
                                                                                    <span class="image-placeholder"></span>
                                                                                    <span class="webp-placeholder"></span>
                                                                                </div>
                                                                                <picture>
                                                                                    <source srcset="" class="webp-src" type="image/webp">
                                                                                    <source srcset="" class="image-src" type="image/jpeg">
                                                                                    <img src="" class="image" alt="" width="50" height="50">
                                                                                </picture>
                                                                            </div>
                                                                            <div class="col-8" style="display: flex; justify-content: center; align-items: center;">
                                                                                <b><span class="item-code"></span></b>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3 pt-2" style="display: flex; justify-content: center; align-items: center;">
                                                                        <div class="text-center">
                                                                            <b><span class="qty"></span></b> <br>
                                                                            <small><span class="uom"></span></small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-3" style="display: flex; justify-content: center; align-items: center;">
                                                                        <span class="price"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-justify item-description p-2">
                                                                    <span class="description"></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-primary w-100 add-item"><i class="fa fa-plus"></i> Add Item</button>
                                            </div>
                                        </div>
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
        table .items-table{
            table-layout: fixed;
            width: 100%;   
        }
        .morectnt span {
            display: none;
        }
        .last-row{
            width: 20% !important;
        }
        .filters-font{
            font-size: 13px !important;
        }
        .item-code-container{
            text-align: justify;
            padding: 10px;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
        .undo-replacement{
            cursor: pointer;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .empty-border{
            border: 1px solid red;
        }

        @media (max-width: 575.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media (max-width: 767.98px) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .last-row{
                width: 35%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            var branch_warehouse = null;
            $('#branch-warehouses').select2({
                placeholder: 'Select a Branch',
				dropdownParent: $('#select-warehouse-container'),
				ajax: {
					url: '/get_consignment_warehouses',
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

            $(document).on('change', '#branch-warehouses', function (){
                $('#empty-warehouse-warning').addClass('d-none');
                branch_warehouse = $(this).val();
                clear_stock_adjustment_table();
                validate_stock_adjustment();
            });

            function clear_stock_adjustment_table(){
                $('#stock-adjustments-table .items').each(function (){
                    var target = 'SA-' + $(this).data('item-code');
                    remove_row('stock-adjustment', target);
                });
            }

            $(document).on('click', '#select-items', function(){
                if(branch_warehouse == '' || branch_warehouse == null){
                    $('#empty-warehouse-warning').removeClass('d-none');
                }else{
                    $('#empty-warehouse-warning').addClass('d-none');

                    $('#stock-adjustment-modal').modal('show');
                }

                $('#stock-adjustment-modal .items-selection').select2({
                    templateResult: formatState,
                    placeholder: 'Select an item',
                    ajax: {
                        url: '/beginning_inv/get_received_items/' + branch_warehouse,
                        method: 'GET',
                        dataType: 'json',
                        data: function (data) {
                            return {
                                q: data.term,
                                purpose: 'Stock Adjustment' // search term
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
            });

            $('#stock-adjustment-modal').on('select2:select', '.items-selection', function(e){
                $('#stock-adjustment-modal .item-code').text(e.params.data.id);
                $('#stock-adjustment-modal .description').text(e.params.data.description);
                $('#stock-adjustment-modal .webp-src').attr('src', e.params.data.webp);
                $('#stock-adjustment-modal .image-src').attr('src', e.params.data.img);
                $('#stock-adjustment-modal .image').attr('src', e.params.data.img);

                $('#stock-adjustment-modal .webp-placeholder').text(e.params.data.webp);
                $('#stock-adjustment-modal .image-placeholder').text(e.params.data.img);

                $('#stock-adjustment-modal .qty').text(e.params.data.max);
                $('#stock-adjustment-modal .price').text(e.params.data.price);
                $('#stock-adjustment-modal .uom').text(e.params.data.uom);

                $('#stock-adjustment-modal .item-details').removeClass('d-none');
            });

            function clear_stock_adjustment_modal_placeholders(){
                $('#stock-adjustment-modal .item-code').text('');
                $('#stock-adjustment-modal .description').text('');
                $('#stock-adjustment-modal .webp-src').attr('src', '');
                $('#stock-adjustment-modal .image-src').attr('src', '');
                $('#stock-adjustment-modal .image').attr('src', '');

                $('#stock-adjustment-modal .qty').text('');
                $('#stock-adjustment-modal .price').text('');
                $('#stock-adjustment-modal .uom').text('');
            }

            validate_stock_adjustment();
            function validate_stock_adjustment(){
                var count = parseInt($('#item-count-stock-adjustment').text());
                if(count > 0){
                    $('#stock-adjustments-container .submit-btn').prop('disabled', false);
                    $('#stock-adjustments-container .empty-row-placeholder').addClass('d-none');
                }else{
                    $('#stock-adjustments-container .submit-btn').prop('disabled', true);
                    $('#stock-adjustments-container .empty-row-placeholder').removeClass('d-none');
                }
            }

            $('#stock-adjustments-table').on('click', '.remove-item', function (){
                var target = $(this).data('target');

                remove_row('stock-adjustment', target);
                validate_stock_adjustment();
            });

            $('#stock-adjustment-modal').on('click', '.add-item', function (){
                var qty = $('#stock-adjustment-modal .qty').text();
                var uom = $('#stock-adjustment-modal .uom').text();
                var price = $('#stock-adjustment-modal .price').text().replace('₱ ', '');
                var item_code = $('#stock-adjustment-modal .item-code').text();
                var webp = $('#stock-adjustment-modal .webp-placeholder').text();
                var image = $('#stock-adjustment-modal .image-placeholder').text();
                var description = $('#stock-adjustment-modal .description').text();

                var existing = $('#stock-adjustments-table').find('.' + item_code).eq(0).length;
                if (existing) {
                    showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
					return false;
                }

                var row = '<tr id="row-SA-' + item_code + '" class="items ' + item_code + '" data-item-code="' + item_code + '">' +
                    '<td class="p-0" colspan=5>' +
                        '<div class="row pl-2 pt-2 p-0">' +
                            '<div class="col-5">' +
                                '<div class="row">' +
                                    '<div class="col-4 col-lg-2 text-center">' +
                                        '<picture>' +
                                            '<source srcset="' + webp + '" class="webp-src" type="image/webp">' +
                                            '<source srcset="' + image + '" class="image-src" type="image/jpeg">' +
                                            '<img src="' + image + '" class="image" alt="" width="50" height="50">' +
                                        '</picture>' +
                                    '</div>' +
                                    '<div class="col-8 col-lg-10" style="display: flex; justify-content: center; align-items: center;">' +
                                        '<span class="item-code font-weight-bold">' + item_code + '</span> <br>' +
                                        '<input name="item_codes[]" value="' + item_code + '" class="d-none">' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="col-3 text-center">' +
                                '<input type="text" name="item[' + item_code + '][qty]" class="form-control text-center" value="' + qty + '" style="font-size: 9pt;" required>' +
                                '<span class="uom">' + uom + '</span>' +
                            '</div>' +
                            '<div class="col-4">' +
                                '<div class="row p-0">' +
                                    '<div class="col-10" style="display: flex; justify-content: center; align-items: center;">' +
                                        '₱&nbsp;<input type="text" name="item[' + item_code + '][price]" class="form-control text-center" value="' + price + '" style="font-size: 9pt;" required>' +
                                    '</div>' +
                                    '<div class="col-1 p-0 remove-item" data-item-code="' + item_code + '" data-target="SA-' + item_code + '">' +
                                        '<i class="fa fa-remove" style="font-size: 9pt; color: red;"></i>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="row">' +
                            '<div class="container item-description">' + description + '</div>' +
                        '</div>' +
                    '</td>' +
                '</tr>';

                $('#stock-adjustment-modal .item-details').addClass('d-none');
                $('#item-count-stock-adjustment').text(parseInt($('#item-count-stock-adjustment').text()) + 1);
                $("#stock-adjustment-modal .items-selection").empty().trigger('change');
                $('#stock-adjustment-modal').modal('hide');

                clear_stock_adjustment_modal_placeholders();
                validate_stock_adjustment();

                $('#stock-adjustments-table tbody').prepend(row);
            });

            function remove_row(name, target){
                $('#row-' + target).remove();
                $('#item-count-' + name).text(parseInt($('#item-count-' + name).text()) - 1);
            }
        });
    </script>
@endsection