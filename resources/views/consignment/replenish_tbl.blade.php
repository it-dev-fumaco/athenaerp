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
            $owner = explode('.', explode('@', $stock_entry->owner)[0]);
            $parsedOwner = ucfirst($owner[0]) . ' ' . ucfirst($owner[1]);

            switch ($stock_entry->status) {
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
            <td>
                {{ $stock_entry->name }}  <span class="d-inline d-lg-none badge badge-{{ $badge }}" style="font-size: 7pt">{{ $stock_entry->status }}</span>
                <div class="d-block d-lg-none" style="font-size: 9pt">
                    <b>{{ $stock_entry->target_warehouse }}</b>
                    <span class="d-block" style="font-size: 8pt">{{ $parsedOwner }} | {{ Carbon\Carbon::parse($stock_entry->creation)->format('M. d, Y h:i a') }}</span>
                </div>
            </td>
            <td class="d-none d-sm-table-cell ">{{ $stock_entry->target_warehouse }}</td>
            <td class="d-none d-sm-table-cell "><span class="badge badge-{{ $badge }}">{{ $stock_entry->status }}</span></td>
            <td class="d-none d-sm-table-cell ">
                <span>{{ $parsedOwner }}</span><br>
                <small>{{ Carbon\Carbon::parse($stock_entry->creation)->format('M. d, Y h:i a') }}</small>
            </td>
            <td class="text-center">
                <a href="/consignment/replenish/{{ $stock_entry->name }}" style="font-size: 9pt">
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
    truncate_description();
</script>