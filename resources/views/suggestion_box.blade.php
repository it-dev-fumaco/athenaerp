<ul id="item-list" class="ul_list" style="border: 1px solid #ccc;">
  @forelse($q as $item)
  <li class="truncate selected-item border-bottom" data-val="{{ $item->name }}">
    @php
    // $img = ($item->item_image_path) ? "/img/" . explode('.', $item->item_image_path)[0].'.webp' : "/icon/no_img.webp";
      $img = ($item->item_image_path) ? "/img/" . $item->item_image_path : "/icon/no_img.png";
      $img_webp = ($item->item_image_path) ? "/img/" . explode('.',$item->item_image_path)[0].'.webp' : "/icon/no_img.webp";
    @endphp
    <div class="d-flex flex-row">
      <div class="p-2 text-truncate d-inline-block" style="width: 90%;">
        {{-- <img src="{{ asset('storage') }}{{ $img }}" style="float:left;width: 60px; height: 60px;margin-right: 10px;"> --}}
        <picture>
          <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" style="float:left;width: 60px; height: 60px;margin-right: 10px;">
          <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" style="float:left;width: 60px; height: 60px;margin-right: 10px;">
          <img src="{{ asset('storage'.$img) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" style="float:left;width: 60px; height: 60px;margin-right: 10px;">
        </picture>
        <b>{{ $item->name }}</b>
        <br>{{ $item->description }}
      </div>
      <div class="p-2 align-middle text-center" style="width: 10%;">
          <a class="btn btn-default" href="?searchString={{ $item->name }}&search=">
            <i class="fa fa-arrow-right"></i>
          </a>
        </div>
    </div>
  </li>
  @empty
    <li class="no-hover text-center font-weight-bold p-3 text-uppercase">No results found</li>
  @endforelse

</ul>

    <style type="text/css">
      .truncate:hover{
        background-color: #DCDCDC;
        color: #373D3F;
      }
      
      .no-hover:hover{
        background-color: #fff;
      }

      .ul_list{
        padding: 0;
        margin: 0;
        font-size: 12pt;
        list-style-type: none;
        text-align: left;
      } 
      </style>