<table class="table">
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
        <th scope="col" class="text-center"></th>
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
            @if($user_group->user_group == 'Inventory Manager')
                <td class="text-center">
                    <button type="button" id="cancel-btn" class="btn btn-danger btn-sm cancel-transaction" data-toggle="modal" data-target="#cancel-transaction-modal-{{ $row['reference_parent'] }}" {{ $row['status'] == 'DRAFT' ? '' : 'disabled' }}>
                        Cancel
                    </button>
                    <div class="modal fade cancel-modal" id="cancel-transaction-modal-{{ $row['reference_parent'] }}" tabindex="999" aria-labelledby="cancel-transaction" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="/cancel_transaction" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="cancel-transaction-label">Confirm Cancel</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body text-center">
                                        Cancel <b>{{ $row['reference_parent'] }}</b> Transaction?
                                        <input type="text" name="athena_transaction_number" value="{{ $row['reference_parent'] }}" required hidden readonly/>
                                        <input type="text" name="athena_reference_name" value="{{ $row['reference_name'] }}" required hidden readonly/>
                                        <input type="text" name="itemCode" value="{{ $row['item_code'] }}" required hidden readonly/>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Yes</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;">No Records Found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="box-footer clearfix" id="athena-transactions-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
    {{ $list->links() }}
</div>
<style>
    .cancel-modal{
        background: rgba(0, 0, 0, .7);
    }
</style>