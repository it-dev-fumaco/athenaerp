<ul class="products-list product-list-in-card pl-2 pr-2">
    @forelse ($list as $item)
    <li class="item">
        <div class="product-img">
            <a href="{{ $item['image'] }}" data-toggle="lightbox" data-gallery="{{ $item['item_code'] }}" data-title="{{ $item['item_code'] }}">
                <img src="{{ $item['image'] }}" class="img-size-50">
            </a>
        </div>
        <div class="product-info">
            <div class="row">
                <div class="col-7 float-left bg-white">
                    <a href="#" class="view-item-details" data-item-code="{{ $item['item_code'] }}" data-item-classification="{{ $item['item_classification'] }}">
                        <span class="d-block font-weight-bold text-dark item-code">{{ $item['item_code'] }}</span>
                    </a>
                    <small class="d-block font-italic">{{ \Illuminate\Support\Str::limit($item['description'], $limit = 25, $end = '...') }}</small>
                </div>
                <div class="col-5 float-right text-center bg-white">
                    <span class="font-italic font-weight-bold text-right">
                        <small><b>{{ $item['qty'] }}</b></small> <span style="font-size: 10px;">{{ $item['stock_uom'] }}</span>
                    </span>
                    <span class="font-italic" style="font-size: 10px;"><br/>{{ $item['warehouse'] }}</span>
                </div>
            </div>
        </div>
    </li>
    @empty
    <li class="item">
        <h5 class="text-center">No Record(s) found.</h5>
    </li>
    @endforelse 
</ul>
<div class="col-md-10 clearfix" id="reserved-items-pagination">
    {{ $list->links() }}
</div>