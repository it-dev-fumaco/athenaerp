<div class="table-responsive p-0" style="height: 650px;">
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
                    <td class="text-center align-middle">
                        <span class="d-block font-weight-bold">{{ \Carbon\Carbon::parse($row->transaction_date)->format('M-d-Y h:i:A') }}</span>
                        @if (strpos($row->transaction_type, 'Out'))
                        <span class="badge badge-warning" style="font-size: 0.7rem;">{{ $row->transaction_type }}</span>
                        @elseif (strpos($row->transaction_type, 'In'))
                        <span class="badge badge-success" style="font-size: 0.7rem;">{{ $row->transaction_type }}</span>
                        @elseif ($row->transaction_type == 'Stock Reconciliation')
                        <span class="badge badge-secondary" style="font-size: 0.7rem;">Stock Adjustment</span>
                        @else
                        <span class="badge badge-danger" style="font-size: 0.7rem;">Unknown</span>
                        @endif
                    </td>
                    <td class="text-justify align-middle">
                        <span class="font-weight-bold">{{ $row->item_code }}</span> - {{ str_limit($row->description, $limit = 130, $end = '...') }}</td>
                    <td class="text-center align-middle">{{ $row->warehouse }}</td>
                    <td class="text-center align-middle font-weight-bold" style="font-size: 0.9rem;">{{ $row->qty * 1 }}</td>
                    <td class="text-center align-middle">
                        <span class="d-block">{{ $row->reference_no }}</span>
                        @if ($row->transaction_type != 'Stock Reconciliation')
                        <span class="d-block">{{ $row->reference_parent }}</span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        @if ($row->transaction_type != 'Stock Reconciliation')
                        {{ $row->user }}
                        @else
                        {{ ucwords(str_replace('.', ' ', explode('@', $row->user)[0])) }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center align-middle">No Record(s) Found</td>
                </tr>
                @endforelse
            </tbody>
    </table>
  </div>