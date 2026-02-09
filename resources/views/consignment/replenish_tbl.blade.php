<table class="table table-hover table-bordered table-striped" style="font-size: 9pt">
    <tr>
        <th>ID</th>
        <th class="d-none d-sm-table-cell">Branch</th>
        <th class="d-none d-sm-table-cell">Status</th>
        <th class="d-none d-sm-table-cell">Requested By</th>
        <th class='text-center' style="width: 23%">Action</th>
    </tr>
    @forelse ($list->items() as $materialRequest)
        @php
            $isPromodiser = Auth::user()->user_group == 'Promodiser' ?? 0;
            $owner = explode('.', explode('@', $materialRequest->owner)[0]);

            $materialRequest->owner = ucfirst($owner[0]) . ' ' . ucfirst($owner[1]);
            $materialRequest->creation = Carbon\Carbon::parse($materialRequest->creation)->format('M. d, Y h:i a');

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
        <tr>
            <td>
                {{ $materialRequest->name }}  <span class="{{ $materialRequest->consignment_status ? 'd-inline' : 'd-none' }} d-lg-none badge badge-{{ $badge }} " style="font-size: 7pt">{{ $materialRequest->consignment_status }}</span>
                <div class="d-block d-lg-none" style="font-size: 9pt">
                    <b>{{ $materialRequest->branch_warehouse }}</b>
                    <span class="d-block" style="font-size: 8pt">{{ $materialRequest->owner }} | {{ $materialRequest->creation }}</span>
                </div>
            </td>
            <td class="d-none d-sm-table-cell">{{ $materialRequest->branch_warehouse }}</td>
            <td class="d-none d-sm-table-cell"><span class="badge badge-{{ $badge }} {{ $materialRequest->consignment_status ?? 'd-none' }}">{{ $materialRequest->consignment_status }}</span></td>
            <td class="d-none d-sm-table-cell">
                <span>{{ $materialRequest->owner }}</span><br>
                <small>{{ $materialRequest->creation }}</small>
            </td>
            <td class="text-center">
                <a href="/consignment/replenish/form/{{ $materialRequest->name }}" style="font-size: 9pt">
                    <i class="fa fa-edit"></i> View
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class='text-center'>
                No record(s) found.
            </td>
        </tr>
    @endforelse
</table>
<div class="mt-3 ml-3 clearfix" style="display: block;">
    <div class="container-fluid d-flex justify-content-end align-items-center" id="pagination">
        {{ $list->links() }}
    </div>
</div>
<script>
    const showTotalChar = 98, showChar = "Show more", hideChar = "Show less"
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

    $(document).on('change', 'input[type="checkbox"][name^="items"]', function() {
        const itemCode = $(this).attr('name').match(/items\[(.*?)\]\['Issue'\]/)[1];
        const warehouseSelect = $(`select[name="items[${itemCode}]['source_warehouse']"]`);

        if ($(this).is(':checked')) {
            warehouseSelect.attr('required', 'required');
        } else {
            warehouseSelect.removeAttr('required');
        }
    });
    truncate_description();
</script>