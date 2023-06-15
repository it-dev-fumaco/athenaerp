@if (!collect($items)->min('active'))
@php
    $unassigned_items = collect($items)->where('active', 0);
@endphp
    <div class="alert alert-warning font-weight-bold text-center w-100" style="font-size: 13px;">
        {{-- <span>Row(s) highlighted in <b>RED</b> are not assigned to any item in ERP. Please assign these barcodes to items in ERP to proceed.</span> --}}
        <span>Row(s) highlighted in <b>RED</b> are not assigned to any item in ERP. <a href="#" class="text-dark text-underline open-modal" data-bs-toggle="modal" data-bs-target="#unassigned-barcodes-modal">Click here assign these barcodes to items in ERP.</a></span>
    </div>

    <div class="modal fade" id="unassigned-barcodes-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Assign Barcodes to ERP</h5>
                    <a href="#" class="close-modal" data-bs-target="#unassigned-barcodes-modal" aria-label="Close"><i class="fa fa-remove"></i></a>
                </div>
                <form action="" id="unassigned-barcodes-form" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <input type="text" class="d-none" name="customer" value="{{ $customer }}">
                            <table class="table table-bordered">
                                <col style="width: 40%;">
                                <col style="width: 60%;">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Item</th>
                                </tr>
                                @foreach ($unassigned_items as $item)
                                    <tr>
                                        <td>
                                            <b>{{ $item['barcode'] }}</b><br>
                                            <span>{{ $item['description'] }}</span>
                                        </td>
                                        <td>
                                            <select name="item[{{ $item['barcode'] }}]" class="form-control item-selection"></select>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal" data-bs-target="#unassigned-barcodes-modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
<div class="alert alert-success font-weight-bold text-center w-100 d-none" style="font-size: 13px;" id="cw-alert-message">
    <span>-</span>
</div>  
<div class="row">
    <div class="col-6">
        <dl class="row">
            <dt class="col-3">Customer Name:</dt>
            <dd class="col-9">{{ $customer }}</dd>
            <dt class="col-3">Branch Warehouse:</dt>
            <dd class="col-9">{{ $branch }}</dd>
        </dl>
    </div>
    <div class="col-6">
        <dl class="row">
            <dt class="col-3">Project:</dt>
            <dd class="col-9">{{ $project }}</dd>
            <dt class="col-3">Customer PO No.:</dt>
            <dd class="col-9">{{ $customer_purchase_order }}</dd>
        </dl>
    </div>
   
    <div class="col-12">
        <table class="table table-bordered" style="font-size: 9pt;">
            <col style="width: 5%;">
            <col style="width: 60%;">
            <col style="width: 10%;">
            <col style="width: 12%;">
            <col style="width: 13%;">
            <thead class="text-uppercase">
                <th class="p-2 text-center">No.</th>
                <th class="p-2 text-center">Item Description</th>
                <th class="p-2 text-center">Qty Sold</th>
                <th class="p-2 text-center">Rate</th>
                <th class="p-2 text-center">Amount</th>
            </thead>
            <tbody>
                @php
                    $i = 1;
                @endphp
                @foreach ($items as $item_code => $item)
                    @php
                        $price = $item['sold'] > 0 ? ($item['amount'] / $item['sold']) : 0;
                    @endphp
                    <tr class="{{ !$item['active'] ? 'inactive-item' : null }}">
                        <td class="p-2 text-center align-middle font-weight-bolder">{{ $i++ }}</td>
                        <td class="p-2 align-middle">
                            <span class="font-weight-bold">{{ $item['item_code'] ? $item['item_code'] : $item['barcode'] }}</span>
                            <br>
                            {!! $item['description'] !!}
                        </td>
                        <td class="p-2 text-center align-middle">
                            <span class="d-block font-weight-bold">{{ $item['sold'] }}</span>
                            <small>{{ $item['uom'] }}</small>
                        </td>
                        <td class="p-2 text-center align-middle">₱ {{ number_format($price, 2) }}</td>
                        <td class="p-2 text-center align-middle">₱ {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right text-uppercase font-weight-bolder">Total Qty Sold</td>
                    <td class="text-center font-weight-bold">{{ number_format(collect($items)->sum('sold')) }}</td>
                    <td class="text-right text-uppercase font-weight-bolder">Total Amount</td>
                    <td class="text-center font-weight-bold">₱ {{ number_format(collect($items)->sum('amount'), 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="col-12 text-right">
        <form action="/generate_sales_order" method="POST" autocomplete="off" id="generate-sales-order-form">
            @csrf
            <input type="hidden" name="customer" value="{{ $customer }}">
            <input type="hidden" name="project" value="{{ $project }}">
            <input type="hidden" name="branch_warehouse" value="{{ $branch }}">
            <input type="hidden" name="po_no" value="{{ $customer_purchase_order }}">
            @php
                $i = 0;
            @endphp
            @foreach ($items as $item_code => $item)
            @php
                $price = $item['sold'] > 0 ? ($item['amount'] / $item['sold']) : 0;
                $index = $i++;
            @endphp
            <input type="hidden" name="items[{{ $index }}][item_code]" value="{{ $item['item_code'] }}">
            <input type="hidden" name="items[{{ $index }}][qty]" value="{{ $item['sold'] }}">
            <input type="hidden" name="items[{{ $index }}][rate]" value="{{ $price }}">
            @endforeach
            <button class="btn btn-primary" type="submit" {{ !collect($items)->min('active') ? 'disabled' : '' }} id="generate-sales-order-btn"><i class="fas fa-check"></i> Generate Sales Order</button>
        </form>
    </div>
</div>

<style>
    .inactive-item{
        background-color: rgba(220, 53, 69, .5)
    }

    .modal{
        background-color: rgba(0,0,0,.4)
    }
</style>

<script>
    $(document).on('click', '.open-modal', function (e){
        e.preventDefault();
        $($(this).data('bs-target')).modal('show');
    });

    $(document).on('click', '.close-modal', function (e){
        e.preventDefault();
        $($(this).data('bs-target')).modal('hide');
    });

    $('.item-selection').select2({
        // templateResult: formatState,
        // templateSelection: formatState,
        placeholder: 'Select an Item',

        ajax: {
            url: '/get_items/{{ $branch }}',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term
                };
            },
            processResults: function (response) {
                return {
                    results: response.items
                };
            },
            cache: true
        }
    });

    

    // function formatState (opt) {
    //     if (!opt.id) {
    //         return opt.text;
    //     }

    //     // if(!optimage){
    //         return opt.id;
    //     // } else {
    //     //     var $opt = $(
    //     //     '<span style="font-size: 10pt; width: 100%">' + opt.text + '</span>'
    //     //     );
    //     //     return $opt;
    //     // }
    // };
</script>