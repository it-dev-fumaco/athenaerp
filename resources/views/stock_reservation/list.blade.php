<div id="webTable">
<div class="col-md-12"><p><b>Online Shop Reservations {{date('Y')}}</b></p></div>
<table id="onlineShopTable" class="table table-striped">
    <thead>
        <tr>
            <th class="text-center">ID</th>
            <th class="text-center">Reserved Qty</th>
            <th class="text-center">Issued Qty</th>
            <th class="text-center">Warehouse</th>
            <!-- <th class="text-center">Reservation Type</th> -->
            <th class="text-center">Date Reserved</th>
            <th class="text-center">Validity</th>
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
            <td class="text-center align-middle">{{ $row->name }}</td>
            <td class="text-center align-middle">{{ $row->reserve_qty . ' ' . $row->stock_uom }}</td>
            <td class="text-center align-middle">{{ round($row->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row->warehouse }}</td>
            <!-- <td class="text-center align-middle">{{ $row->type }}</td> -->
            <td class="text-center align-middle">{{ date('Y-m-d', strtotime($row->creation)) }}</td>
            <td class="text-center align-middle">{{ ($row->valid_until) ? $row->valid_until : '-' }}</td>
            <td class="text-center align-middle">
                <!-- <span class="badge {{ $badge }}" style="font-size: 10pt;">{{ $row->status }}</span> -->
                @if($row->reserve_qty == round($row->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">Issued</span>
                @elseif($row->valid_until < Carbon\Carbon::today())
                    <span class="badge badge-warning" style="font-size: 10pt;">Expired</span>
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
                @endphp
                <button type="button" class="btn btn-info edit-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }}>Edit</button>
                <button type="button" class="btn btn-danger cancel-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }}>Cancel</button>
            </td>
        </tr>
        @empty
        <!-- <tr>
            <td colspan="12" class="text-center">No record(s) found.</td>
        </tr> -->
        <script>
            // $(document).on('click', function(){
            //     $('#webTable').css("display", "none");
            // })
            $(document).ready(function(){
                $('#webTable').hide();
            });
        </script>
        @endforelse<!-- Web -->
    </tbody>
</table>
</div><!-- webTable -->
<div class="col-md-12"><p><b>In-house Reservations</b></p></div>
<table id="inHouseTable" class="table table-striped">
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
    <tbody id="lcTable">
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
            <td class="text-center align-middle">{{ $row2->name }}</td>
            <td class="text-center align-middle">{{ $row2->reserve_qty . ' ' . $row2->stock_uom }}</td>
            <td class="text-center align-middle">{{ round($row2->consumed_qty) }}</td>
            <td class="text-center align-middle">{{ $row2->warehouse }}</td>
            <td class="text-center align-middle">{{ date('Y-m-d', strtotime($row2->creation)) }}</td>
            <td class="text-center align-middle">{{ ($row2->valid_until) ? $row2->valid_until : '-' }}</td>
            <td class="text-center align-middle">
                <!-- <span class="badge {{ $badge }}" style="font-size: 10pt;">{{ $row2->status }}</span> -->
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
    <input type="text" id="sortStatus" value="asc" hidden=""/>
 </table>
<div class="box-footer clearfix" id="stock-reservations-pagination" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $list->links() }}
</div>