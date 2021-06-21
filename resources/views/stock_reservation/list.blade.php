<div id="webTable">
<div class="col-md-12" ><p><b>Website Stock Reservations</b></p></div>
<table class="table table-striped" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center">Transaction</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Issued Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Status</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($webList as $row)<!-- Web -->
        @php
            if ($row->status == 'Active') {
                $badge = 'badge-primary';
            }elseif ($row->status == 'Expired') {
                $badge = 'badge-warning';
            }elseif ($row->status == 'Cancelled') {
                $badge = 'badge-danger';
            } else {
                $badge = 'badge-success';
            }
        @endphp
        <tr>
            <td class="text-center align-middle">
                <span class="d-block font-weight-bold">{{ date('M-d-Y', strtotime($row->creation)) }}</span>
                <small>{{ $row->name }}</small>
            </td>
            <td class="text-center align-middle text-break">{{ $row->reserve_qty . ' ' . $row->stock_uom }}</td>
            <td class="text-center align-middle text-break">{{ round($row->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row->warehouse }}</td>
            <td class="text-center align-middle">
                @if($row->reserve_qty == round($row->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">Issued</span>
                @elseif(round($row->consumed_qty) > 0)                    
                    <span class="badge badge-info" style="font-size: 10pt;">Partially Issued</span>
                @elseif($row->status == 'Cancelled')
                    <span class="badge badge-danger" style="font-size: 10pt;">Cancelled</span>
                @else
                    <span class="badge badge-primary" style="font-size: 10pt;">Active</span>
                @endif
            </td>
            <td class="text-center align-middle">{{ $row->created_by }}</td>
            <td class="text-center align-middle">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                    $attr_cancelled = ($row->status == 'Cancelled') ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info edit-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Update</button>
                <button type="button" class="btn btn-danger cancel-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Cancel</button>
            </td>
        </tr>
        @empty
        <script>
            $(document).ready(function(){
                $('#webTable').hide();
            });
        </script>
        @endforelse<!-- Web -->
    </tbody>
</table>
</div><!-- webTable -->
<div class="col-md-12"><p><b>In-house Reservations</b></p></div>
<table class="table table-striped" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center">Transaction</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Issued Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Sales Person</th>
            <th class="text-center">Validity</th>
            <th class="text-center">Status</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($inhouseList as $row2)<!-- In-house -->
        @php
            if ($row2->status == 'Active') {
                $badge = 'badge-primary';
            }elseif ($row2->status == 'Expired') {
                $badge = 'badge-warning';
            }elseif ($row2->status == 'Cancelled') {
                $badge = 'badge-danger';
            } else {
                $badge = 'badge-success';
            }
        @endphp
        <tr>
            <td class="text-center align-middle">
                <span class="d-block font-weight-bold">{{ date('M-d-Y', strtotime($row2->creation)) }}</span>
                <small>{{ $row2->name }}</small>
            </td>
            <td class="text-center align-middle text-break">{{ $row2->reserve_qty . ' ' . $row2->stock_uom }}</td>
            <td class="text-center align-middle text-break">{{ round($row2->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row2->warehouse }}</td>
            <td class="text-center align-middle text-break">{{ $row2->sales_person }}</td>
            <td class="text-center align-middle text-break">{{ ($row2->valid_until) ? $row2->valid_until : '-' }}</td>
            <td class="text-center align-middle">
                @if($row2->reserve_qty == round($row2->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">Issued</span>
                @elseif($row2->valid_until < Carbon\Carbon::today())
                    <span class="badge badge-warning" style="font-size: 10pt;">Expired</span>
                @elseif(round($row2->consumed_qty) > 0)                    
                    <span class="badge badge-info" style="font-size: 10pt;">Partially Issued</span>
                @elseif($row2->status == 'Cancelled')
                    <span class="badge badge-danger" style="font-size: 10pt;">Cancelled</span>
                @else
                    <span class="badge badge-primary" style="font-size: 10pt;">Active</span>
                @endif
            </td>
            <td class="text-center align-middle">{{ $row2->created_by }}</td>
            <td class="text-center align-middle">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info edit-stock-reservation-btn" data-reservation-id="{{ $row2->name }}" {{ $attr }}>Edit</button>
                <button type="button" class="btn btn-danger cancel-stock-reservation-btn" data-reservation-id="{{ $row2->name }}" {{ $attr }}>Cancel</button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="12" class="text-center">No record(s) found.</td>
        </tr>
        @endforelse<!-- In-house -->
    </tbody>
 </table>
<div class="box-footer clearfix" id="stock-reservations-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $list->links() }}
</div>