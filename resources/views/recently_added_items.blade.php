<ul class="products-list product-list-in-card pl-2 pr-2">
    @forelse ($list as $item)
    <li class="item">
        <div class="product-img">
            @php
                $img = ($item['image']) ? "/img/" . $item['image'] : "/icon/no_img.png";
            @endphp
            <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $item['item_code'] }}" data-title="{{ $item['item_code'] }}">
                <img src="{{ asset('storage/') }}{{ $img }}" class="img-size-50">
            </a>
        </div>
        <div class="product-info">
            <div class="col-md-8 float-left bg-white" style="display: inline-block">
                <span class="font-weight-bold product-title">{{ $item['item_code'] }}</span>
                <small class="d-block font-italic">{{ str_limit($item['description'], $limit = 30, $end = '...') }}</small>
            </div>

            <div class="col-md-4 float-right text-center bg-white" style="display: inline-block">
                <span class="font-italic font-weight-bold text-right">
                    <small><b>{{ $item['qty'] }}</b></small> <span style="font-size: 10px;">{{ $item['stock_uom'] }}</span>
                </span>
                <span class="font-italic" style="font-size: 10px;"><br/>{{ $item['warehouse'] }}</span>
            </div>
        </div>
    </li>
    @empty
    <li class="item">
        <h5 class="text-center">No Record(s) found.</h5>
    </li>
    @endforelse 
</ul>

<div class="col-md-10 clearfix" id="reserved-items-pagination" style="font-size: 12pt;">
    {{ $list->links() }}
</div>