<table class="table table-bordered table-striped" style="font-size: 10pt !important;">
    <thead class="border-top">
        <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">No.</th>
        <th class="text-center font-responsive p-2 align-middle" style="width: 40%;">Branch</th>
        <th class="text-center font-responsive p-2 align-middle" style="width: 15%;">Status</th>
        <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Requested By</th>
        <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Action</th>
    </thead>
    <tbody>
        @forelse ($result as $row)
        @php
            $erp_url = env('ERP_API_BASE_URL');
            $id = $row['name'];
            switch ($row['status']) {
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
            <td class="text-center p-1 align-middle">
                <a href="{{ "$erp_url/app/material-request/$id" }}" class="text-dark" target="_blank">
                    {{ $row['name'] }}
                    <i class="fa fa-arrow-right"></i>
                </a>
            </td>
            <td class="text-center p-1 align-middle">{{ $row['branch_warehouse'] }}</td>
            <td class="text-center p-1 align-middle">
                <span class=" badge badge-{{ $badge }}">{{ $row['status'] }}</span>
            </td>
            <td class="text-center p-1 align-middle">
                <span class="d-block">{{ $row['owner'] }}</span>
                <small>{{ $row['creation'] }}</small>
            </td>
            <td class="text-center p-1 align-middle">
                <a href="/consignment_order/{{ $row['name'] }}/edit" class="btn btn-dark btn-xs"><i class="fa fa-edit"></i> Edit</a>
            </td>
        </tr>
        @empty
        <tr>
            <td class="text-center font-weight-bold text-uppercase text-muted" colspan="5">No record(s) found</td>
        </tr> 
        @endforelse
    </tbody>
</table>

<div id="pagination">
    {{ $list->links() }}
</div>