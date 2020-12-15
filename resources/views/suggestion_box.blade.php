<ul id="item-list" class="ul_list">
    @forelse($q as $item)
    <li class="truncate selected-item" data-val="{{ $item->name }}">
      @php
      if ($item->item_image_path) { 
        $img = "/img/" . $item->item_image_path;
      }else{
        $img = "/icon/no_img.png";
      }
          
      @endphp
            <img src="{{ asset('storage') }}{{ $img }}" style="float:left;width: 60px; height: 60px;margin-right: 10px;">
        <b>{{ $item->name }}</b>
        <br>{{ $item->description }}
        <a class="pull-right btn btn-default" href="/?searchString={{ $item->name }}&search=">
            <i class="glyphicon glyphicon-circle-arrow-right"></i></a>
    </li>
    @empty
    <li class="no-hover" style="padding: 10px;">
        <center><b>No results found.</b></center>	
        
    </li>
    @endforelse 

        </ul>

        <style type="text/css">
          .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-bottom: 1px solid #ccc;
            padding: 10px;
            text-decoration: none;
            cursor: pointer;
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
            margin: 5px;
            font-size: 12pt;
            list-style-type: none;
            text-align: left;
          }
          
          </style>