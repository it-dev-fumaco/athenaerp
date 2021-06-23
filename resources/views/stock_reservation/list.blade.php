@if(count($webList) > 0)
<h6 class="font-weight-bold text-uppercase"><i class="fas fa-box"></i> Website Stock Reservations</h6>
<table class="table table-hover" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center p-1">Transaction</th>
            <th class="text-center p-1">Reserved Qty</th>
            <th class="text-center p-1">Issued Qty</th>
            <th class="text-center p-1">Warehouse</th>
            <th class="text-center p-1">Status</th>
            <th class="text-center p-1">Created by</th>
            <th class="text-center p-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($webList as $row)
        <tr>
            <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ date('M-d-Y', strtotime($row->creation)) }}</span>
                <small>{{ $row->name }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row->reserve_qty) }}</span>
                <small>{{ $row->stock_uom }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row->consumed_qty) }}</span>
                <small>{{ $row->stock_uom }}</small>
            </td>
            <td class="text-center align-middle p-1">{{ $row->warehouse }}</td>
            <td class="text-center align-middle p-1">
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
            <td class="text-center align-middle p-1">{{ $row->created_by }}</td>
            <td class="text-center align-middle p-1">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                    $attr_cancelled = ($row->status == 'Cancelled') ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Update</button>
                <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" data-reservation-id="{{ $row->name }}" {{ $attr }} {{ $attr_cancelled }}>Cancel</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="box-footer clearfix" id="stock-reservations-pagination-1" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $webList->links() }}
</div>
@endif

@iF(count($consignmentList) > 0)
<h6 class="font-weight-bold text-uppercase"><i class="fas fa-box"></i> Consignment Reservations</h6>
<table class="table table-hover" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center p-1">Transaction</th>
            <th class="text-center p-1">Reserved Qty</th>
            <th class="text-center p-1">Issued Qty</th>
            <th class="text-center p-1">Warehouse</th>
            <th class="text-center p-1">Branch</th>
            <th class="text-center p-1">Status</th>
            <th class="text-center p-1">Created by</th>
            <th class="text-center p-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($consignmentList as $row1)
        <tr>
            <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ date('M-d-Y', strtotime($row1->creation)) }}</span>
                <small>{{ $row1->name }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row1->reserve_qty) }}</span>
                <small>{{ $row1->stock_uom }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row1->consumed_qty) }}</span>
                <small>{{ $row1->stock_uom }}</small>
            </td>
            <td class="text-center align-middle p-1">{{ $row1->warehouse }}</td>
            <td class="text-center align-middle text-break p-1">{{ $row1->consignment_warehouse }}</td>
            <td class="text-center align-middle p-1">
                @if($row1->reserve_qty == round($row1->consumed_qty))
                    <span class="badge badge-secondary" style="font-size: 10pt;">{{ $row1->status }}</span>
                @elseif($row1->valid_until < Carbon\Carbon::today())
                    <span class="badge badge-warning" style="font-size: 10pt;">{{ $row1->status }}</span>
                @elseif(round($row1->consumed_qty) > 0)                    
                    <span class="badge badge-info" style="font-size: 10pt;">{{ $row1->status }}</span>
                @elseif($row1->status == 'Cancelled')
                    <span class="badge badge-danger" style="font-size: 10pt;">{{ $row1->status }}</span>
                @else
                    <span class="badge badge-primary" style="font-size: 10pt;">{{ $row1->status }}</span>
                @endif
            </td>
            <td class="text-center align-middle p-1">{{ $row1->created_by }}</td>
            <td class="text-center align-middle p-1">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" data-reservation-id="{{ $row1->name }}" {{ $attr }}>Edit</button>
                <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" data-reservation-id="{{ $row1->name }}" {{ $attr }}>Cancel</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="box-footer clearfix" id="stock-reservations-pagination-2" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $consignmentList->links() }}
</div>
@endif

<h6 class="font-weight-bold text-uppercase"><i class="fas fa-box"></i> In-house Reservations</h6>
<table class="table table-hover" style="font-size: 11pt;">
    <thead>
        <tr>
            <th class="text-center p-1">Transaction</th>
            <th class="text-center p-1">Reserved Qty</th>
            <th class="text-center p-1">Issued Qty</th>
            <th class="text-center p-1">Warehouse</th>
            <th class="text-center p-1">Sales Person</th>
            <th class="text-center p-1">Validity</th>
            <th class="text-center p-1">Status</th>
            <th class="text-center p-1">Created by</th>
            <th class="text-center p-1">Action</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($inhouseList as $row2)<!-- In-house -->
        <tr>
            <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ date('M-d-Y', strtotime($row2->creation)) }}</span>
                <small>{{ $row2->name }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row2->reserve_qty) }}</span>
                <small>{{ $row2->stock_uom }}</small>
            </td>
            <td class="text-center align-middle text-break p-1">
                <span class="font-weight-bold">{{ number_format($row2->consumed_qty) }}</span>
                <small>{{ $row2->stock_uom }}</small>
            </td>
            <td class="text-center align-middle p-1">{{ $row2->warehouse }}</td>
            <td class="text-center align-middle text-break p-1">{{ $row2->sales_person }}</td>
            <td class="text-center align-middle text-break p-1">{{ ($row2->valid_until) ? $row2->valid_until : '-' }}</td>
            <td class="text-center align-middle p-1">
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
            <td class="text-center align-middle p-1">{{ $row2->created_by }}</td>
            <td class="text-center align-middle p-1">
                @php
                    $attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
                @endphp
                <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" data-reservation-id="{{ $row2->name }}" {{ $attr }}>Edit</button>
                <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" data-reservation-id="{{ $row2->name }}" {{ $attr }}>Cancel</button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="12" class="text-center">No record(s) found.</td>
        </tr>
        @endforelse<!-- In-house -->
    </tbody>
</table>

<div class="box-footer clearfix" id="stock-reservations-pagination-3" data-item-code="{{ $item_code }}" style="font-size: 16pt;">
	{{ $inhouseList->links() }}
</div>