<ul class="products-list product-list-in-card pl-2 pr-2">
    @forelse ($list as $item)
        <li class="item">
            <div class="product-img">
                @php
                    $disk = Storage::disk('upcloud');

                    $hasImage = !empty($item['image']);

                    $originalPath = $hasImage ? 'img/' . $item['image'] : 'icon/no_img.png';

                    $webpPath = $hasImage ? 'img/' . explode('.', $item['image'])[0] . '.webp' : 'icon/no_img.webp';
                @endphp

                <a href="{{ $disk->url($originalPath) }}" data-toggle="lightbox" data-gallery="{{ $item['item_code'] }}"
                    data-title="{{ $item['item_code'] }}">

                    @if ($hasImage && $disk->exists($webpPath))
                        <picture>
                            <source srcset="{{ $disk->url($webpPath) }}" type="image/webp">
                            <source srcset="{{ $disk->url($originalPath) }}" type="image/jpeg">
                            <img src="{{ $disk->url($originalPath) }}"
                                alt="{{ Illuminate\Support\Str::slug(pathinfo($item['image'], PATHINFO_FILENAME), '-') }}"
                                class="img-size-50">
                        </picture>
                    @else
                        <img src="{{ $disk->url($originalPath) }}" class="img-size-50">
                    @endif

                </a>
            </div>
            <div class="product-info">
                <div class="col-md-8 float-left bg-white" style="display: inline-block">
                    {{-- <span class="font-weight-bold product-title">{{ $item['item_code'] }}</span> --}}
                    <a href="#" class="view-item-details" data-item-code="{{ $item['item_code'] }}"
                        data-item-classification="{{ $item['item_classification'] }}">
                        <span class="d-block font-weight-bold text-dark item-code">{{ $item['item_code'] }}</span>
                    </a>
                    <small
                        class="d-block font-italic">{{ \Illuminate\Support\Str::limit($item['description'], $limit = 25, $end = '...') }}</small>
                </div>

                <div class="col-md-4 float-right text-center bg-white" style="display: inline-block">
                    <span class="font-italic font-weight-bold text-right">
                        <small><b>{{ $item['qty'] }}</b></small> <span
                            style="font-size: 10px;">{{ $item['stock_uom'] }}</span>
                    </span>
                    <span class="font-italic" style="font-size: 10px;"><br />{{ $item['warehouse'] }}</span>
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
