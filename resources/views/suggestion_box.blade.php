<div id="item-list" class="container-fluid p-0 ul_list" style="border: 1px solid #ccc;">
  @forelse ($q as $item)
    @php
      $img = data_get($imageCollection, $item->name, $noImg);
    @endphp
    <div class="search-row row w-100 p-2" style="border-bottom: 1px solid #ccc;">
      <div class="text-center p-2 col-2">
        <img src="{{ $img }}" class="img w-100">
      </div>
      <div class="col-8 col-md-9 text-truncate">
        <span style="font-size: 10pt;"><b>{{ $item->name }}</b></span>
        @if (in_array($item->name, $bundledItems))
          &nbsp;<span class="badge badge-info font-italic" style="font-size: 8pt;">Product Bundle&nbsp;</span>
        @endif
        <br><span style="font-size: 10pt;">{{ strip_tags($item->description) }}</span>
      </div>
      <div class="col-2 col-md-1 d-flex align-items-start justify-content-end">
        <a class="btn btn-default" href="/get_item_details/{{ $item->name }}">
          <i class="fa fa-arrow-right"></i>
        </a>
      </div>
    </div>
  @empty
    <div class="row w-100 p-2 text-center">
      <p class="mx-auto">No results found.</p>
    </div>
  @endforelse
</div>
    <style type="text/css">
      .search-row:hover{
        background-color: #DCDCDC;
        color: #373D3F;
      }
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