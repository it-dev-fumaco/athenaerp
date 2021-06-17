<table class="table table-hover" style="font-size: 11pt;">
    <thead>
      <tr>
        <th scope="col" class="col-xs-2" style="text-align:center;">Transaction No.</th>
        <th scope="col" class="col-xs-2" style="text-align:center;">Warehouse</th>
        <th scope="col" class="col-xs-3" style="text-align:center;">Transaction</th>
        <th scope="col" class="col-xs-1" style="text-align:center;">Qty</th>
        <th scope="col" class="col-xs-1" style="text-align:center;">Balance Qty</th>
        <th scope="col" class="col-xs-1" style="text-align:center;">Ref. No.</th>
        <th scope="col" class="col-xs-1" style="text-align:center;">Date</th>
        <th scope="col" class="col-xs-2" style="text-align:center;">Transact by</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($list as $row)
    <tr>
        <td style="text-align:center;">{{ $row['voucher_no'] }}</td>
        <td style="text-align:center;">{{ $row['warehouse'] }}</td>
        <td style="text-align:center;">{{ $row['transaction'] }}</td>
        <td style="text-align:center;">{{ $row['actual_qty'] }}</td>
        <td style="text-align:center;">{{ $row['qty_after_transaction'] }}</td>
        <td style="text-align:center;">{{ $row['ref_no'] }}</td>
        <td style="text-align:center;">{{ $row['date_modified'] }}</td>
        <td style="text-align:center;">{{ $row['session_user'] }}</td>
      </tr>
    @empty
    <tr>
      <td colspan="8" style="text-align:center;">No Records Found.</td>
  </tr>
    @endforelse
      

    </tbody>
  </table>
  <div class="box-footer clearfix" id="stock-ledger-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
    {{ $logs->links() }}
  </div>