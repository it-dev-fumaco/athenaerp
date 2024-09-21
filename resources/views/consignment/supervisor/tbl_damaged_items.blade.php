<table class="table table-striped" style="font-size: 11px;">
    <thead class="text-uppercase">
        <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%;">Date</th>
        <th class="text-center p-2 align-middle" style="width: 35%;">Item Description</th>
        <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 10%;">Qty</th>
        <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%;">Store</th>
        <th class="text-center p-2 align-middle d-none d-xl-table-cell" style="width: 20%;">Damage Description</th>
    </thead>
    @forelse ($result as $i => $item)
        <tr>
            <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                {{ $item['creation'] }}<br/>
                <span class="badge badge-success {{ !$item['item_status'] ? 'd-none' : null }}">{{ $item['item_status'] }}</span>
            </td>
            <td class="p-1 text-justify align-middle">
                <div class="d-flex flex-row align-items-center">
                    <div class="p-1">
                        <img src="{{ $item['image'] }}" alt="{{ $item['image_slug'] }}" width="70">
                    </div>
                    <div class="p-1">
                        <span class="d-block font-weight-bold">{{ $item['item_code'] }}</span>
                        <small class="d-block item-description">{!! strip_tags($item['description']) !!}</small>

                        <small class="d-block mt-2">Created by: <b>{{ $item['promodiser'] }}</b></small>
                    </div>
                </div>
                <div class="d-block d-xl-none" style="font-size: 9pt;">
                    <b>Damaged Qty: </b>{{ $item['damaged_qty'] }}&nbsp;<small>{{ $item['uom'] }}</small> <br>
                    <b>Store: </b> {{ $item['store'] }} <br>
                    <b>Damage Description: </b> {{ $item['damage_description'] }} <br>
                    <b>Date: </b> {{ $item['creation'] }}
                </div>
            </td>
            <td class="p-1 text-center align-middle d-none d-xl-table-cell">
                <span class="d-block font-weight-bold">{{ number_format($item['damaged_qty']) }}</span>
                <small>{{ $item['uom'] }}</small>
            </td>
            <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['store'] }}</td>
            <td class="p-1 text-center align-middle d-none d-xl-table-cell">{{ $item['damage_description'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">No record(s) found.</td>
        </tr>
    @endforelse
</table>
<div class="float-left m-2">Total: <b>{{ $list->total() }}</b></div>
<div class="float-right m-2" id="damaged-items-pagination">{{ $list->links('pagination::bootstrap-4') }}</div>