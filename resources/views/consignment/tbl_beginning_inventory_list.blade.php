<table class="table table-bordered table-striped" style="font-size: 10pt;">
    <thead>
        <th class="font-responsive text-center">Date</th>
        <th class="font-responsive text-center">Branch</th>
        <th class="font-responsive text-center">Submitted by</th>
        <th class="font-responsive text-center">Status</th>
        <th class="font-responsive text-center">Action</th>
    </thead>
    @forelse ($beginning_inventory_list as $row)
    @php
        $status = 'secondary';
        if($row->status == 'For Approval'){
            $status = 'primary';
        }else if($row->status == 'Approved'){
            $status = 'success';
        }else if($row->status == 'Cancelled'){
            $status = 'danger';
        }
    @endphp
    <tr>
        <td class="font-responsive text-center">{{ \Carbon\Carbon::parse($row->transaction_date)->format('F d, Y') }}</td>
        <td class="font-responsive text-center">{{ $row->branch_warehouse }}</td>
        <td class="font-responsive text-center">{{ $row->owner }}</td>
        <td class="font-responsive text-center">
            <span class="badge badge-{{ $status }}">{{ $row->status }}</span>
        </td>
        <td class="font-responsive text-center">
            <a href="#" class="view-beginning-inventory-details-btn" data-id="{{ $row->name }}">View Items</a>
        </td>
    </tr>
    @empty
    <tr>
        <td class="font-responsive text-center" colspan="7">No record(s) found.</td>
    </tr>
    @endforelse
</table>
<div class="float-left m-2">Total: <b>{{ $beginning_inventory_list->total() }}</b></div>
<div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $beginning_inventory_list->links('pagination::bootstrap-4') }}</div>