@extends('layout', [
    'namePage' => 'Consignment Order',
    'activePage' => 'beginning_inventory',
])

@section('content')
@php
    $targetWarehouse = null;
    $method = 'post';
    $action = '/consignment/replenish';

    $items = [];
    if($materialRequest){
        $action .= "/$materialRequest->name";
        $method = 'put';
        $targetWarehouse = $materialRequest->branch_warehouse;
        $items = $materialRequest->items;
    }
@endphp
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="card card-lightblue">
                            <div class="card-header text-center p-2" id="report">
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">
                                    Consignment Order Form
                                    @if ($materialRequest)
                                        @php
                                            switch ($materialRequest->consignment_status) {
                                                case 'For Approval':
                                                    $badge = 'warning';
                                                    break;
                                                case 'Approved':
                                                    $badge = 'primary';
                                                    break;
                                                case 'Delivered':
                                                    $badge = 'success';
                                                    break;
                                                case 'Cancelled':
                                                    $badge = 'danger';
                                                    break;
                                                default:
                                                    $badge = 'secondary';
                                                    break;
                                            }
                                        @endphp
                                        <span class="badge badge-{{ $badge }}">{{ $materialRequest->consignment_status }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="card-body p-0">
                                @if(session()->has('success'))
                                    <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                        {{ session()->get('success') }}
                                    </div>
                                @endif
                                @if(session()->has('error'))
                                    <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                        {{ session()->get('error') }}
                                    </div>
                                @endif
                                <form id="replenish-form" action="{{ $action }}" method="post">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $materialRequest ? $materialRequest->name : null }}">
                                    <div class="container">
                                        <div class="row pt-2 pb-2">
                                            <div class="col-8">
                                                <select name="branch" id="branch" class="form-control form-control-sm">
                                                    <option value="" disabled selected>Select a Branch</option>
                                                    @foreach ($assignedConsignmentStores as $store)
                                                        <option value="{{ $store }}" {{ $targetWarehouse && $targetWarehouse == $store ? 'selected' : null }}>{{ $store }}</option>
                                                    @endforeach 
                                                </select>
                                                <small class="text-danger font-italic d-none branch-warning">* Select a branch</small>
                                            </div>
                                            <div class="col-4">
                                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" id='add-modal-btn'>
                                                    <i class="fa fa-plus"></i> Add Item
                                                </button>

                                                <!-- add item modal -->
                                                <div class="modal fade" id="add-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-navy">
                                                                <h5 class="modal-title" id="exampleModalLabel">Select an Item</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <select id="item-selection" class="form-control"></select>

                                                                <table class="table table-striped mt-4 d-none" id="item-selection-table">
                                                                    <thead>
                                                                        <th class="font-responsive text-center p-1 align-middle">Item Code</th>
                                                                        <th class="font-responsive text-center p-1 align-middle">Qty</th>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="text-justify p-1 align-middle" colspan=2>
                                                                                <div class="remove-modal-parent col-12 text-right d-none">
                                                                                    <a href="#" class="text-secondary" data-toggle="modal">
                                                                                        <i class="fa fa-remove"></i>
                                                                                    </a>
                
                                                                                    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                        <div class="modal-dialog" role="document">
                                                                                            <div class="modal-content">
                                                                                                <div class="modal-header">
                                                                                                    <h5 class="modal-title" id="exampleModalLabel">Remove <span class="item-code-display"></span>?</h5>
                                                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                                        <span aria-hidden="true">&times;</span>
                                                                                                    </button>
                                                                                                </div>
                                                                                                <div class="modal-body text-center">
                                                                                                    Remove Item <b class="item-code-display"></b> from the list?
                                                                                                </div>
                                                                                                <div class="modal-footer">
                                                                                                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                                                                                    <button type="button" class="btn btn-sm btn-danger remove-item">Remove Item</button>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="d-flex flex-row justify-content-center justify-content-xl-start align-items-center">
                                                                                    <div class="p-1 col-2 col-xl-1 text-center">
                                                                                        <img src="" alt="" class="img-thumbnail" alt="User Image" width="100%">
                                                                                    </div>
                                                                                    <div class="p-1 col-5 col-xl-7 m-0" style="font-size: 9pt">
                                                                                        <span class="font-weight-bold font-responsive item-code"></span>
                                                                                        <div class="p-1 d-none item-description  d-xl-block" style="font-size: 9.5pt !important;"></div>
                                                                                    </div>
                                                                                    <div class="p-0 col-4 col-xl-2">
                                                                                        <div class="input-group number-control">
                                                                                            <div class="input-group-prepend">
                                                                                                <button class="btn btn-outline-danger decrement" type="button">-</button>
                                                                                            </div>
                                                                                            <input type="text" class="form-control number-input text-center item-qty text-center" value="1">
                                                                                            <div class="input-group-append">
                                                                                                <button class="btn btn-outline-success increment" type="button">+</button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="p-1 item-description d-xl-none" style="font-size: 9.5pt !important;"></div>
                                                                                <div class="p-1">
                                                                                    <select class="form-control reason my-2" style="font-size: 9.5pt" required>
                                                                                        <option value="">Select a reason</option>
                                                                                        <option value="Customer Order">Customer Order</option>
                                                                                        <option value="Stock Replenishment">Stock Replenishment</option>
                                                                                    </select>
                                                                                    <textarea class="form-control remarks" placeholder='Remarks...' rows=5 style="font-size: 9.5pt !important;"></textarea>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-primary" id='add-item'>Confirm</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- add item modal -->

                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="responsive-table-wrap w-100">
                                            <table class="table table-striped" id="selected-items-table" style="font-size: 9pt;">
                                                <thead>
                                                    <th class="font-responsive text-center p-1 align-middle">Item Code</th>
                                                    <th class="font-responsive text-center p-1 align-middle">Qty</th>
                                                </thead>
                                                <tbody>
                                                    @forelse ($items as $item)
                                                        @php
                                                            $itemCode = $item->item_code;
                                                            $image = isset($itemImages[$itemCode]) ? "img/".$itemImages[$itemCode] : '/icon/no_img.png';

                                                            if(Storage::disk('public')->exists(explode('.', $image)[0].'.webp')){
                                                                $image = explode('.', $image)[0].'.webp';
                                                            }
                                                        @endphp
                                                        <tr id="row-{{ $itemCode }}">
                                                            <div class="d-none">
                                                                <input type="text" name="items[{{ $itemCode }}][name]" value="{{ $item->name }}">
                                                            </div>
                                                            <td class="text-justify p-1 align-middle" colspan="2">
                                                                <div class="col-12 text-right">
                                                                    <a href="#" class="text-secondary" data-toggle="modal" data-target="#remove-{{ $itemCode }}-modal">
                                                                        <i class="fa fa-remove"></i>
                                                                    </a>

                                                                    <div class="modal fade" id="remove-{{ $itemCode }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                        <div class="modal-dialog" role="document">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title" id="exampleModalLabel">Remove {{ $itemCode }}?</h5>
                                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                        <span aria-hidden="true">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body text-center">
                                                                                    Remove Item <b>{{ $itemCode }}</b> from the list?
                                                                                </div>
                                                                                <div class="modal-footer">
                                                                                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                                                                    <button type="button" class="btn btn-sm btn-danger remove-item">Remove Item</button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex flex-row justify-content-center justify-content-xl-start align-items-center">
                                                                    <div class="p-1 col-2 col-xl-1 text-center">
                                                                        <img src="{{ Storage::disk(upcloud)->url($image") }}" class="img-thumbnail" alt="User Image" width="100%">
                                                                    </div>
                                                                    <div class="p-1 col-5 col-xl-7 m-0" style="font-size: 9pt">
                                                                        <span class="font-weight-bold font-responsive item-code">{{ $itemCode }}</span>
                                                                        <div class="p-1 d-none d-xl-block" style="font-size: 9.5pt !important;">
                                                                            {!! $item->description !!}
                                                                        </div>
                                                                    </div>
                                                                    <div class="p-0 col-4 col-xl-2">
                                                                        @if ($materialRequest->consignment_status == 'Cancelled')
                                                                            <div class="text-center">
                                                                                <b>{{ number_format($item->qty) }}</b><br>
                                                                                <small>{{ $item->uom }}</small>
                                                                            </div>
                                                                        @else
                                                                            <div class="input-group number-control">
                                                                                <div class="input-group-prepend">
                                                                                    <button class="btn btn-outline-danger decrement" type="button">-</button>
                                                                                </div>
                                                                                <input type="text" name="items[{{ $itemCode }}][qty]" class="form-control number-input number-validate text-center item-qty" value="{{ number_format($item->qty) }}" style="font-size: 9pt">
                                                                                <div class="input-group-append">
                                                                                    <button class="btn btn-outline-success increment" type="button">+</button>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="p-1 item-description d-xl-none" style="font-size: 9.5pt !important;">
                                                                    {!! $item->description !!}
                                                                </div>
                                                                <div class="p-1">
                                                                    @php
                                                                        $reasons = ['Customer Order', 'Stock Replenishment'];
                                                                    @endphp
                                                                    <select class="form-control reason my-2" name="items[{{ $itemCode }}][reason]" style="font-size: 9.5pt" required>
                                                                        <option value="">Select a reason</option>
                                                                        @foreach ($reasons as $reason)
                                                                            <option value="{{ $reason }}" {{ $item->consignment_reason == $reason ? 'selected' : null }}>{{ $reason }}</option>
                                                                        @endforeach
                                                                    </select>

                                                                    <textarea class="form-control remarks" name="items[{{ $itemCode }}][remarks]" placeholder='Reason...' rows=5 style="font-size: 9.5pt !important;">
                                                                        {{ $item->remarks }}
                                                                    </textarea>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr id='placeholder'>
                                                            <td colspan=2 class="text-center">
                                                                Please select item(s)
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            </div>
                                            <div class="col-12 text-right">
                                                <div class="m-2">
                                                    @if (!$materialRequest || $materialRequest->consignment_status == 'Draft')
                                                        <button type="button" class="btn btn-sm btn-primary btn-block submit-once submit-btn" data-status="0"><i id="submit-logo" class="fas fa-check"></i> Save as Draft</button>
                                                        <button type="button" class="btn btn-sm btn-success btn-block mb-2" data-toggle="modal" data-target="#submitModal">
                                                            <i id="submit-logo" class="fas fa-check"></i> Submit for Approval
                                                        </button>

                                                        <div class="modal fade" id="submitModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="exampleModalLabel">Submit for Approval</h5>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="modal-body text-center">
                                                                        Submit for Approval?
                                                                        <br>
                                                                        <small class="font-italic">Note: You cannot update this entry after submission</small>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                                                        <button type="button" class="btn btn-sm btn-success submit-once submit-btn" data-status="1"><i id="submit-logo" class="fas fa-check"></i> Submit for Approval</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if ($materialRequest && !$materialRequest->docstatus)
                                                        <button type="button" class="btn btn-sm btn-secondary btn-block" data-toggle="modal" data-target="#deleteModal">
                                                            <i id="submit-logo" class="fas fa-remove"></i> Delete
                                                        </button>
                                                        
                                                        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="exampleModalLabel">Delete {{ $materialRequest->name }}</h5>
                                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="modal-body text-center">
                                                                        Permanently Delete <b>{{ $materialRequest->name }}</b>?
                                                                        <br>
                                                                        <small class="font-italic">Note: * This cannot be undone</small>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                                                        <a href="/consignment/replenish/{{ $materialRequest->name }}/delete" class="btn btn-sm btn-primary">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <input type="number" value='0' id="item-counter" class='d-none'/>
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
        table { width: 100%; }
        input[type=number] {
            -moz-appearance: textfield;
        }
        .morectnt span {
            display: none;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function (){
            const showNotification = (color, message, icon) => {
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

            const formatState = (opt) => {
                if (!opt.id) {
                    return opt.text;
                }

                optimage = opt.img;

                if(!optimage){
                    return opt.text;
                } else {
                    var $opt = $(
                    '<span style="font-size: 10pt;"><img src="' + optimage + '" width="40px" /> ' + opt.text + '</span>'
                    );
                    return $opt;
                }
            };

            const get_items = (branch) => {
				$('#item-selection').select2({
                    templateResult: formatState,
                    placeholder: 'Select an item',
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
                                results:response
                            };
                        },
                        cache: true
                    }
                });
            }

            const showTotalChar = 98, showChar = "Show more", hideChar = "Show less", items_array = new Array();
            const truncate_description = () => {
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

            const parseCurrencyToInteger = (currencyString) => {
                let numericString = currencyString.replace(/[₱,\s]/g, '');

                let numericValue = parseFloat(numericString);

                let integerValue = Math.round(numericValue);

                return integerValue;
            }

            const validateInputs = () => {
                let isValid = true;

                $('.number-validate').each(function() {
                    let value = $(this).val();

                    if ($.isNumeric(value) && parseFloat(value) >= 1) {
                        $(this).removeClass('border-danger');
                    } else {
                        $(this).addClass('border-danger');
                        isValid = false;
                    }
                });

                return isValid;
            }

            truncate_description();

            $(document).on('click', '.show-more', function (e){
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

            $(document).on('click', '#add-modal-btn', function (e){
                e.preventDefault()
                const selected_warehouse = $('select[name="branch"]').val()

                if(selected_warehouse){
                    $('#add-Modal').modal('show')
                    get_items(selected_warehouse)
                    $('.branch-warning').addClass('d-none')
                }else{
                    $('.branch-warning').removeClass('d-none')
                }
            })

            $(document).on('select2:select', '#item-selection', function(e){
                const data = e.params.data
                $('#item-selection-table').removeClass('d-none')
                $('#item-selection-table .item-code').text(data.id)
                $('#item-selection-table .img-thumbnail').attr('src', data.img)
                $('#item-selection-table .item-description').html(data.description)

                truncate_description();
            });

            $(document).on('click', '.increment', function(){
                var input = $(this).closest('.number-control').find('.number-input');
                var currentValue = parseInt(input.val());
                input.val(currentValue + 1);
            });

            $(document).on('click', '.decrement', function(){
                var input = $(this).closest('.number-control').find('.number-input');
                var currentValue = parseInt(input.val());
                if(currentValue > 1) {
                    input.val(currentValue - 1);
                }
            });

            $(document).on('click', '#add-item', function(e){
                $('#item-selection-table').addClass('d-none')
                const item_code = $('#item-selection-table .item-code').text()

                const existing = $('#selected-items-table').find('#row-' + item_code).eq(0).length;
                if (existing) {
                    showNotification("warning", 'Item <b>' + item_code + '</b> already exists in the list.', "fa fa-info");
                    $("#item-selection").empty().trigger('change');
					return false;
                }

                const selected_reason = $('#item-selection-table .reason').val();

                const row = $('#item-selection-table tbody tr').clone();
                row.attr('id', 'row-' + item_code);
                row.find('.item-qty').addClass('number-validate').attr('name', `items[${item_code}][qty]`)
                row.find('.reason').attr('name', `items[${item_code}][reason]`).val(selected_reason);
                row.find('.remarks').attr('name', `items[${item_code}][remarks]`)
                row.find('.remove-item').removeClass('d-none')
                row.find('.remove-modal-parent').removeClass('d-none')
                row.find('a').attr('data-target', `#remove-${item_code}-modal`)
                row.find('.modal').attr('id', `remove-${item_code}-modal`)
                row.find('.item-code-display').text(item_code)

                $('#selected-items-table tbody').prepend(row);

                if(jQuery.inArray(item_code, items_array) === -1){
                    items_array.push(item_code);
                }

                truncate_description();
                $('#add-Modal').modal('hide')
                $("#item-selection").empty().trigger('change');
                $('#item-selection-table .reason').val('');

                $('#item-selection-table textarea').val('')
                $('#item-selection-table .number-input').val(1)
                $('#placeholder').remove()
            });

            $(document).on('click', '.remove-item', function() {
                $('.modal').modal('hide')
                $('.modal').on('hidden.bs.modal', function (e) {
                    $(this).closest('tr').remove();
                })
            });

            $(document).on('click', '.submit-btn', function (e){
                e.preventDefault()
                const status = $(this).data('status');
                if (!validateInputs() && status != 'Cancelled') {
                    e.preventDefault()
                    $('.submit-warning').removeClass('d-none').text('Please ensure all items have Qty');

                    return false;
                }

                $('<input>').attr({
                    type: 'hidden',
                    name: 'status',
                    value: status
                }).appendTo('#replenish-form')

                $('#replenish-form').submit()

                $('.submit-warning').addClass('d-none')
            })
        });
    </script>
@endsection