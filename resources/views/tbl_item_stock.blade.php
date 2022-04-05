<div class="float-left" style="margin-top: -32px;">
    Total: <span class="badge badge-info" style="font-size: 10pt;">{{ $consignment_stocks->total() }}</span>
</div>
<table class="table table-bordered m-0 table-striped" style="font-size: 10pt;">
    <thead>
        <tr>
            <th class="text-center" style="width: 70%;">Item Description</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%; white-space: nowrap">Available Qty</th>
            <th class="text-center d-none d-sm-table-cell" style="width: 15%;">Price</th>
        </tr>    
    </thead>
    <tbody>
        @forelse ($consignment_stocks as $row)
        <tr>
            <td class="text-justify align-middle">
                <div class="d-flex row">        
                    <div class="col-3 col-xl-1">
                        @php
                            $item_image = null;
                            if (array_key_exists($row->item_code, $item_image_paths)) {
                                $item_image = $item_image_paths[$row->item_code][0];
                            }
                            $img_webp = ($item_image) ? "/img/" . explode('.',$item_image->image_path)[0].'.webp' : "/icon/no_img.webp";
                            $img = ($item_image) ? "/img/" . $item_image->image_path : "/icon/no_img.png";
                            $stock_qty = $row->actual_qty * 1;

                            $price_list_rate = array_key_exists($row->item_code, $price_list_rates) ? '₱ ' . number_format($price_list_rates[$row->item_code][0]->price_list_rate, 2, '.', ',') : '-';
                        @endphp
                        <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row->item_code }}" data-title="{{ $row->item_code }}">
                            <picture>
                                <source srcset="{{ asset('storage/') . $img_webp }}" type="image/webp" class="w-100">
                                <source srcset="{{ asset('storage/') . $img }}" class="w-100">
                                <img src="{{ asset('storage/') . $img }}" alt="{{ str_slug($img, '-') }}" class="w-100">
                            </picture>
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
                                    @if ($stock_qty > 0)
                                        <span class="badge badge-success">{{ number_format($stock_qty) . ' ' . $row->stock_uom }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ number_format($stock_qty) . ' ' . $row->stock_uom }}</span>
                                    @endif
                                </td>
                                <td class="p-1 text-center font-weight-bold">{{ $price_list_rate }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-12 col-sm-9 col-xl-11">
                        <span class="font-weight-bold view-item-details" data-item-code="{{ $row->item_code }}" data-item-classification="{{ $row->item_classification }}" style="cursor:pointer;">{{ $row->item_code }}</span>
                        <span class="d-block">{!! strip_tags($row->description) !!}</span>
                    </div>
                </div>
                <div class="modal fade" id="{{ $row->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div id="image-container" class="container-fluid">
                                    <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                        <div class="carousel-inner">
                                            <div class="carousel-item active">
                                                <img class="d-block w-100" id="{{ $row->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                            </div>
                                            <span class='d-none' id="{{ $row->item_code }}-image-data">0</span>
                                        </div>
                                        <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $row->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="sr-only">Previous</span>
                                        </a>
                                        <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $row->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="sr-only">Next</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
            <td class="text-center align-middle d-none d-sm-table-cell" style="font-size: 13pt;">
                @if ($stock_qty > 0)
                <span class="badge badge-success">{{ number_format($stock_qty) . ' ' . $row->stock_uom }}</span>
                @else
                <span class="badge badge-danger">{{ number_format($stock_qty) . ' ' . $row->stock_uom }}</span>
                @endif
            </td>
            <td class="text-center align-middle d-none d-sm-table-cell font-weight-bold">{{ $price_list_rate }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="text-center text-uppercase font-weight-bold">No item(s) found</td>
        </tr>
        @endforelse
    </tbody> 
</table>

<div class="mt-3 c-store-pagination" data-el="{{ str_slug($warehouse, '-') }}" data-warehouse="{{ $warehouse }}">
    {{ $consignment_stocks->links() }}
</div>