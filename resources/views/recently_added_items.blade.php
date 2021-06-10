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
            <span class="font-weight-bold product-title">{{ $item['item_code'] }}</span>
            <small class="d-block font-italic">{{ str_limit($item['description'], $limit = 130, $end = '...') }}</small>
        </div>
    </li>
    @empty
    <li class="item">
        <h5 class="text-center">No Record(s) found.</h5>
    </li>
    @endforelse 
  </ul>