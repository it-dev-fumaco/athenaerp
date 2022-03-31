<ul id="item-list" class="ul_list" style="border: 1px solid #ccc;">
  @forelse($q as $item)
  <li class="truncate selected-item border-bottom" data-val="{{ $item->name }}">
    @php
    $img = ($item->item_image_path) ? "/img/" . explode('.', $item->item_image_path)[0].'.webp' : "/icon/no_img.webp";
    @endphp
    <div class="d-flex flex-row">
      <div class="p-2 text-truncate d-inline-block" style="width: 90%;">
        <img src="{{ asset('storage') }}{{ $img }}" style="float:left;width: 60px; height: 60px;margin-right: 10px;">
        <b>{{ $item->name }}</b>
        <br><span style="font-size: 10pt;">{{ $item->description }}</span>
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