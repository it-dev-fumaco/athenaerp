@php
$filename = Carbon\Carbon::now()->format('M-d-Y').' '.$branch.'.xls';
header("Content-Disposition: attachment; filename=".$filename);
header("Content-Type: application/vnd.ms-excel");
@endphp
<table border='1'>
    <tr>
        <th class="border">Warehouse</th>
        <td class="border">{{ $branch }}</td>
    </tr>
    <tr>
        <th class="border">Item Code</th>
        <th class="border">Item Description</th>
        <th class="border">UoM</th>
        <th class="border">ERP Actual Qty</th>
        <th class="border">In-Store Qty</th>
        <th class="border">Price</th>
    </tr>
    @foreach ($items as $item)
        <tr>
            <td class="border">{{ $item->item_code }}</td>
            <td class="border">{{ strip_tags($item->description) }}</td>
            <td class="border">{{ $item->stock_uom  }}</td>
            <td class="border">{{ number_format($item->actual_qty) }}</td>
            <td class="border">{{ number_format($item->consigned_qty) }}</td>
            <td class="border">{{ number_format($item->consignment_price, 2) }}</td>
        </tr>
    @endforeach
</table>

<style>
    .border{
        border: 1px solid #000
    }
</style>
