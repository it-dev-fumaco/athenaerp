@php
    $isPromodiser = Auth::user()->user_group == 'Promodiser' ?? 0;
    $owner = explode('.', explode('@', $stockEntry->owner)[0]);

    $stockEntry->owner = ucfirst($owner[0]) . ' ' . ucfirst($owner[1]);
    $stockEntry->creation = Carbon\Carbon::parse($stockEntry->creation)->format('M. d, Y h:i a');

    switch ($stockEntry->status) {
        case 'Pending':
            $badge = 'primary';
            break;
        case 'Partially Issued':
        case 'Issued':
        case 'Completed':
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


<div class="modal-body text-left" style="max-height: 70vh; overflow: auto">
    <div class="row">
        <div class="col-6">
            <span class="d-block">ID: <b>{{ $stockEntry->name }}</b></span>
            <span class="d-block">Branch: <b>{{ $stockEntry->target_warehouse }}</b></span>
        </div>
        <div class="col-6">
            <span class="d-block">Requested by: <b>{{ $stockEntry->owner }}</b></span>
            <span class="d-block">Requested on: <b>{{ $stockEntry->creation }}</b></span>
        </div>
    </div>
    <div class="row pt-2">
        <form id="form-{{ $stockEntry->name }}" action="/consignment/replenish/{{ $stockEntry->name }}/approve" method="post">
            <table class="table table-bordered table-striped">
                <colgroup>
                    <col style="width: 30%">
                    <col style="width: 20%">
                    <col style="width: 20%">
                    <col style="width: 10%">
                    <col style="width: 10%">
                    <col style="width: 5%">
                    <col style="width: 5%">
                </colgroup>
                <tr>
                    <th class="text-center">Item</th>
                    <th class="text-center">Source Warehouse</th>
                    <th class="text-center">Target Warehouse</th>
                    <th class="text-center">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Issue</th>
                </tr>
                @foreach ($stockEntry->items as $item)
                    @php
                        $itemCode = $item->item_code;
                        $image = isset($itemImages[$itemCode]) ? "img/".$itemImages[$itemCode] : '/icon/no_img.png';

                        if(Storage::disk('public')->exists(explode('.', $image)[0].'.webp')){
                            $image = explode('.', $image)[0].'.webp';
                        }

                        $item->price = '₱ '.number_format($item->price, 2);
                        $item->qty = number_format($item->qty);

                        $warehouses = isset($inventory[$itemCode]) ? $inventory[$itemCode] : [];

                        switch ($item->status) {
                            case 'Pending':
                                $badge = 'primary';
                                break;
                            case 'Issued':
                            case 'Completed':
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
                    <tr>
                        <td class="text-center p-1">
                            <div class="row">
                                <div class="col-2 p-1">
                                    <img src="{{ asset("storage/$image") }}" class="w-100">
                                </div>
                                <div class="col-10 text-left">
                                    <b>{{ $itemCode }}</b> - <span>{{ strip_tags($item->item_description) }}</span>
                                    <div class="container-fluid mt-2">
                                        <div class="p-0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center select-parent-container">
                            <select name="items[{{ $itemCode }}][source_warehouse]" class="warehouse-select form-control" style="font-size: 9pt">
                                <option value="" disabled selected>Select Source Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->warehouse }}">
                                        {{ $warehouse->warehouse }} - {{ "$warehouse->available_qty $item->uom" }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">{{ $stockEntry->target_warehouse }}</td>
                        <td class="text-center"><b>{{ $item->price }}</b></td>
                        <td class="text-center">
                            <b class="d-block">{{ $item->qty }}</b>
                            <span>{{ $item->uom }}</span>
                        </td>
                        <td class="text-center"><span class="badge badge-{{ $badge }}">{{ $item->status }}</span></td>
                        <td class="text-center">
                            <input type="checkbox" name="items[{{ $itemCode }}][issue]">
                        </td>
                    </tr>
                @endforeach
            </table>
        </form>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-sm btn-secondary close-modal" data-target="#view-{{ $stockEntry->name }}">Close</button>
    @if ($stockEntry->status == 'Pending')
        <button type="button" class="btn btn-sm btn-success submit-once submit-btn" data-id="{{ $stockEntry->name }}" data-issue="selected"><i class="fas fa-check"></i> Issue Selected Items</button>
        <button type="button" class="btn btn-sm btn-success submit-once submit-btn" data-id="{{ $stockEntry->name }}" data-issue="all"><i class="fas fa-check"></i> Issue All Items</button>
    @endif
</div>

<script>
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
    $(document).off('click', '.submit-btn').on('click', '.submit-btn', function (e) {
        e.preventDefault();
        $('.submit-btn').attr('disabled', true);

        const btn = $(this);
        const issue = btn.data('issue');
        const id = btn.data('id');
        const form = $(`#form-${id}`);

        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        if (issue === 'all') {
            let isValid = true;
            
            form.find('select').each(function () {
                if ($(this).val() === '' || $(this).val() === null) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                showNotification("danger", "Please select a source warehouse for all items.", "fa fa-info");
                $('.submit-btn').removeAttr('disabled');
                return;
            }
        }

        $('<input>').attr({
            type: 'hidden',
            name: 'issue',
            value: issue
        }).appendTo(form);

        $.ajax({
            type: 'post',
            url: `/consignment/replenish/${id}/approve`,
            data: form.serialize(),
            success: (response) => {
                console.log(response);
            },
            error: (xhr, textStatus, errorThrown) => {
                showNotification("danger", xhr.responseJSON.message, "fa fa-info");
            },
            complete: (response) => {
                $('.submit-btn').removeAttr('disabled');
            }
        });
    });

</script>
