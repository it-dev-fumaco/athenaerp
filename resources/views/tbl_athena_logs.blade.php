<div class="table-responsive p-0" style="height: 740px;">
    <table class="table table-bordered" style="font-size: 0.8rem;">
        <col style="width: 10%;">
        <col style="width: 40%;">
        <col style="width: 18%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
        <col style="width: 12%;">
        <thead>
            <tr>
                <th scope="col" class="text-center align-middle">Date</th>
                <th scope="col" class="text-center align-middle">Item Description</th>
                <th scope="col" class="text-center align-middle">Warehouse</th>
                <th scope="col" class="text-center align-middle">Issued Qty</th>
                <th scope="col" class="text-center align-middle">Ref. No.</th>
                <th scope="col" class="text-center align-middle p-1">Transact by</th>
            </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                <tr>
                    <td class="text-center">
                        <span class="d-block font-weight-bold">{{ \Carbon\Carbon::parse($row->transaction_date)->format('M-d-Y h:i:A') }}</span>
                        <small class="d-block">{{ $row->reference_parent }}</small>
                    </td>
                    <td class="text-justify">
                        <span class="font-weight-bold">{{ $row->item_code }}</span> - {{ $row->description }}</td>
                    <td class="text-center">{{ $row->source_warehouse }}</td>
                    <td class="text-center">{{ $row->issued_qty * 1 }}</td>
                    <td class="text-center">{{ $row->reference_no }}</td>
                    <td class="text-center">{{ $row->warehouse_user }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No Records Found.</td>
                </tr>
                @endforelse
            </tbody>
    </table>
  </div>
  <!-- /.card-body -->