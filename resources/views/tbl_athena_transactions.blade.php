<table class="table" style="font-size: 11pt;">
    <thead>
    <tr>
        <th scope="col" class="text-center">Transaction No.</th>
        <th scope="col" class="text-center">From Warehouse</th>
        <th scope="col" class="text-center">To Warehouse</th>
        <th scope="col" class="text-center">Transaction</th>
        <th scope="col" class="text-center">Issued Qty</th>
        <th scope="col" class="text-center">Ref. No.</th>
        <th scope="col" class="text-center">Date</th>
        <th scope="col" class="text-center">Transact by</th>
    </tr>
    </thead>
    <tbody>
        @forelse ($list as $row)
        @php
            if(in_array($row['status'], ['CANCELLED', 'DELETED'])){
                $label = 'badge-danger';
            }elseif($row['status'] == 'DRAFT'){
                $label = 'badge-warning';
            }else{
                $label = 'badge-primary';
            }
        @endphp
        <tr>
            <td class="text-center">
                <span class="d-block">{{ $row['reference_parent'] }}</span>
                <span class="badge {{ $label }}">{{ $row['status'] }}</span>
            </td>
            <td class="text-center">{{ $row['source_warehouse'] }}</td>
            <td class="text-center">{{ $row['target_warehouse'] }}</td>
            <td class="text-center">{{ $row['reference_type'] }}</td>
            <td class="text-center">{{ $row['issued_qty'] }}</td>
            <td class="text-center">{{ $row['reference_no'] }}</td>
            <td class="text-center">{{ $row['transaction_date'] }}</td>
            <td class="text-center">{{ $row['warehouse_user'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;">No Records Found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="box-footer clearfix" id="athena-transactions-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $logs->links() }}
</div>