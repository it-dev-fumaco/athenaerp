<div class="float-left consignment-total">
    Total: <span class="badge badge-info" style="font-size: 10pt;">{{ $consignmentStocks->total() }}</span>
</div>
<table class="table table-bordered m-0 table-striped" style="font-size: 10pt;">
    <thead>
        <tr>
            <th class="text-center" style="width: 55%;">Item Description</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Item Group</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Item Classification</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%; white-space: nowrap">Available Qty</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Price</th>
        </tr>    
    </thead>
    <tbody>
        @forelse ($consignmentStocks as $row)
        <tr>
            <td class="text-justify align-middle">
                <div class="d-flex row">        
                    <div class="col-3 col-xl-1">
                        @php
                            $itemImage = data_get($itemImagePaths, "{$row->item_code}.0.image_path");
                            $imgWebp = ($itemImage) ? "/img/" . explode('.',$itemImage)[0].'.webp' : "/icon/no_img.webp";
                            $img = ($itemImage) ? "/img/" . $itemImage : "/icon/no_img.png";
                            $stockQty = $row->actual_qty * 1;

                            $rate = data_get($priceListRates, "{$row->item_code}.0.price_list_rate");
                            $priceListRate = $rate ? '₱ ' . number_format($rate, 2, '.', ',') : '-';
                        @endphp
                        <a href="{{ asset('storage/') }}{{ $img }}" class="view-images" data-item-code="{{ $row->item_code }}">
                            @if(!Storage::disk('public')->exists('/img/'.explode('.', $itemImage)[0].'.webp'))
                                <img class="w-100" src="{{ asset('storage/') }}{{ $img }}">
                            @elseif(!Storage::disk('public')->exists('/img/'.$itemImage))
                                <img class="w-100" src="{{ asset('storage/') }}{{ $imgWebp }}">
                            @else
                                <picture>
                                    <source srcset="{{ asset('storage'.$imgWebp) }}" type="image/webp" class="w-100">
                                    <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="w-100">
                                    <img src="{{ asset('storage'.$img) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" class="w-100">
                                </picture>
                            @endif
                        </a>
                    </div>
                    <div class="col-9 d-block d-sm-none">
                        <table class="w-100">
                            <tr>
                                <th class="p-1 text-center" style="white-space: nowrap">Available Qty</th>
                                <th class="p-1 text-center">Price</th>
                            </tr>
                            <tr>
                                <td class="p-1 text-center">
                                    @if ($stockQty > 0)
                                        <span class="badge badge-success">{{ number_format($stockQty) . ' ' . $row->stock_uom }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ number_format($stockQty) . ' ' . $row->stock_uom }}</span>
                                    @endif
                                </td>
                                <td class="p-1 text-center font-weight-bold">{{ $priceListRate }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-12 col-sm-9 col-xl-11">
                        <span class="font-weight-bold"><a href="/get_item_details/{{ $row->item_code }}" style="color: inherit !important" target="_blank">{{ $row->item_code }}</a></span>
                        <span class="d-inline d-sm-none"><br>{{ $row->item_classification.' - '.$row->item_group }}</span>
                        <span class="d-block">{!! $row->description !!}</span>
                    </div>
                </div>
            </td>
            <td class="text-center align-middle font-weight-bold d-none d-sm-table-cell">{{ $row->item_group }}</td>
            <td class="text-center align-middle font-weight-bold d-none d-sm-table-cell">{{ $row->item_classification }}</td>
            <td class="text-center align-middle d-none d-sm-table-cell" style="font-size: 13pt;">
                @if ($stockQty > 0)
                <span class="badge badge-success">{{ number_format($stockQty) . ' ' . $row->stock_uom }}</span>
                @else
                <span class="badge badge-danger">{{ number_format($stockQty) . ' ' . $row->stock_uom }}</span>
                @endif
            </td>
            <td class="text-center align-middle d-none d-sm-table-cell font-weight-bold" style="white-space: nowrap !important">{{ $priceListRate }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="text-center text-uppercase font-weight-bold">No item(s) found</td>
        </tr>
        @endforelse
    </tbody> 
</table>

<div class="mt-3 c-store-pagination" data-el="{{ Illuminate\Support\Str::slug($warehouse, '-') }}" data-warehouse="{{ $warehouse }}">
    {{ $consignmentStocks->links() }}
</div>

<script>
    $(document).ready(function(){
        $('#item-qty-{{ Illuminate\Support\Str::slug($warehouse, "-") }}').text('{{ number_format($consignmentStocks->total(), 0) }}');
        $('#stock-qty-{{ Illuminate\Support\Str::slug($warehouse, "-") }}').text('{{ number_format($totalStocks, 0) }}');
    });
</script>