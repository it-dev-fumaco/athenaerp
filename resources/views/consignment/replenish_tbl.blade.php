<table class="table table-hover table-bordered table-striped" style="font-size: 9pt">
    <tr>
        <th>ID</th>
        <th class="d-none d-sm-table-cell">Branch</th>
        <th class="d-none d-sm-table-cell">Status</th>
        <th class="d-none d-sm-table-cell">Requested By</th>
        <th class='text-center' style="width: 23%">Action</th>
    </tr>
    @forelse ($list->items() as $stock_entry)
        @php
            $is_promodiser = Auth::user()->user_group == 'Promodiser' ?? 0;
            $owner = explode('.', explode('@', $stock_entry->owner)[0]);

            $stock_entry->owner = ucfirst($owner[0]) . ' ' . ucfirst($owner[1]);
            $stock_entry->creation = Carbon\Carbon::parse($stock_entry->creation)->format('M. d, Y h:i a');

            switch ($stock_entry->status) {
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
        <tr>
            <td>
                {{ $stock_entry->name }}  <span class="d-inline d-lg-none badge badge-{{ $badge }}" style="font-size: 7pt">{{ $stock_entry->status }}</span>
                <div class="d-block d-lg-none" style="font-size: 9pt">
                    <b>{{ $stock_entry->target_warehouse }}</b>
                    <span class="d-block" style="font-size: 8pt">{{ $stock_entry->owner }} | {{ $stock_entry->creation }}</span>
                </div>
            </td>
            <td class="d-none d-sm-table-cell ">{{ $stock_entry->target_warehouse }}</td>
            <td class="d-none d-sm-table-cell "><span class="badge badge-{{ $badge }}">{{ $stock_entry->status }}</span></td>
            <td class="d-none d-sm-table-cell ">
                <span>{{ $stock_entry->owner }}</span><br>
                <small>{{ $stock_entry->creation }}</small>
            </td>
            <td class="text-center">
                @if ($is_promodiser)
                    <a href="/consignment/replenish/{{ $stock_entry->name }}" style="font-size: 9pt">
                        <i class="fa fa-edit"></i> View
                    </a>
                @else
                    <a href="#" class="open-modal" style="font-size: 9pt" data-target="#view-{{ $stock_entry->name }}" data-id="{{ $stock_entry->name }}">
                        <i class="fa fa-edit"></i> View
                    </a>

                    <div class="modal fade" id="view-{{ $stock_entry->name }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document" style="max-width: 95% !important">
                            <div class="modal-content">
                                <div class="modal-header d-flex justify-content-between align-items-center">
                                    <h5 class="modal-title">{{ $stock_entry->name }}</h5>
                                    
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-{{ $badge }}" style="font-size: 10pt">{{ $stock_entry->status }}</span>
                                        <button type="button" class="close close-modal" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="content-{{ $stock_entry->name }}" class="text-left">
                                    <div class="d-flex justify-content-center align-items-center p-5">
                                        <div class="spinner-border"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
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