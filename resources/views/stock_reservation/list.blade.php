<div id="webTable">
<div class="col-md-12" ><p><b>Website Stock Reservations</b></p></div>
<table class="table table-striped" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center">ID</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Issued Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Date Reserved</th>
            <th class="text-center">Status</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($webList as $row)<!-- Web -->
        <tr>
            <td class="text-center align-middle">{{ $row->name }}</td>
            <td class="text-center align-middle text-break">{{ $row->reserve_qty . ' ' . $row->stock_uom }}</td>
            <td class="text-center align-middle text-break">{{ round($row->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row->warehouse }}</td>
            <td class="text-center align-middle text-break">{{ date('Y-m-d', strtotime($row->creation)) }}</td>
            <td class="text-center align-middle">
                @if($row->reserve_qty == round($row->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">{{ $row->status }}</span>
                @elseif(round($row->consumed_qty) > 0)                    
                    <span class="badge badge-info" style="font-size: 10pt;">{{ $row->status }}</span>
                @elseif($row->status == 'Cancelled')
                    <span class="badge badge-danger" style="font-size: 10pt;">{{ $row->status }}</span>
                @else
                    <span class="badge badge-primary" style="font-size: 10pt;">{{ $row->status }}</span>
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
            <th class="text-center">ID</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Issued Qty</th>
            <th class="text-center">Warehouse</th>
            <th class="text-center">Date Reserved</th>
            <th class="text-center">Validity</th>
            <th class="text-center">Status</th>
            <th class="text-center">Created by</th>
            <th class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($inhouseList as $row2)<!-- In-house -->
        <tr>
            <td class="text-center align-middle">{{ $row2->name }}</td>
            <td class="text-center align-middle text-break">{{ $row2->reserve_qty . ' ' . $row2->stock_uom }}</td>
            <td class="text-center align-middle text-break">{{ round($row2->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row2->warehouse }}</td>
            <td class="text-center align-middle text-break">{{ date('Y-m-d', strtotime($row2->creation)) }}</td>
            <td class="text-center align-middle text-break">{{ ($row2->valid_until) ? $row2->valid_until : '-' }}</td>
            <td class="text-center align-middle">
                @if($row2->reserve_qty == round($row2->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">{{ $row2->status }}</span>
                @elseif($row2->valid_until < Carbon\Carbon::today())
                    <span class="badge badge-warning" style="font-size: 10pt;">{{ $row2->status }}</span>
                @elseif(round($row2->consumed_qty) > 0)                    
                    <span class="badge badge-info" style="font-size: 10pt;">{{ $row2->status }}</span>
                @elseif($row2->status == 'Cancelled')
                    <span class="badge badge-danger" style="font-size: 10pt;">{{ $row2->status }}</span>
                @else
                    <span class="badge badge-primary" style="font-size: 10pt;">{{ $row2->status }}</span>
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