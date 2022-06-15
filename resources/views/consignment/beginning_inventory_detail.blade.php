<form action="/approve_beginning_inv/{{ $detail->name }}" method="POST" id="beginning-inventory-approval-form">
    @csrf
    <div class="modal-header bg-navy text-white">
        <div class="container-fluid">
            <div class="row">
                <div class="col-8 text-left font-responsive">
                <h4>{{ $detail->branch_warehouse }}
                    @if ($detail->status == 'Approved')
                        <span class="badge badge-success">Approved</span>
                    @endif
                </h4>
                <span class="d-block">Inventory Date: <b>{{ \Carbon\Carbon::parse($detail->transaction_date)->format('F d, Y') }}</b></span>
                <span class="d-block">Submitted By: <b>{{ $detail->owner }}</b></span>
            </div>
                @if ($detail->status == 'For Approval')
                    <div class="col-4 w-100">
                        @php
                            $status_selection = [
                                ['title' => 'Approve', 'value' => 'Approved'],
                                ['title' => 'Cancel', 'value' => 'Cancelled']
                            ];
                        @endphp
                        
                        <div class="input-group pt-2">
                            <select class="custom-select font-responsive" name="status" required>
                                <option value="" selected disabled>Select a status</option>
                                @foreach ($status_selection as $status)
                                    <option value="{{ $status['value'] }}">{{ $status['title'] }}</option>                                                                                
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit" style="color: #fff">Submit</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true" style="color: #fff">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <table class="table table-bordered table-striped">
            <thead>
                <th class="text-center" style="width: 60%; font-size: 10pt" colspan="2">Item Description</th>
                <th class="text-center" style="width: 20%; font-size: 10pt">Opening Stock</th>
                <th class="text-center" style="width: 20%; font-size: 10pt">Price</th>
            </thead>
            <tbody>
            @forelse ($items as $item)
                @php
                    $img = array_key_exists($item->item_code, $item_images) ? "/img/" . $item_images[$item->item_code][0]->image_path : "/icon/no_img.png";
                    $img_webp = array_key_exists($item->item_code, $item_images) ? "/img/" . explode('.',$item_images[$item->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";

                    $img_count = array_key_exists($item->item_code, $item_images) ? count($item_images[$item->item_code]) : 0;
                @endphp
                <tr>
                    <td class="text-center p-1 col-1 align-middle">
                        <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $item->item_code }}" data-title="{{ $item->item_code }}">
                        <picture>
                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="80">
                        </picture>
                        </a>

                        
                        <div class="modal fade" id="mobile-{{ $item->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $item->item_code }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    </div>
                                    <div class="modal-body">
                                        <form></form>
                                        <div id="image-container" class="container-fluid">
                                            <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                                                <div class="carousel-inner">
                                                    <div class="carousel-item active">
                                                        <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $item->item_code }}" data-title="{{ $item->item_code }}">
                                                        <picture>
                                                            <source id="mobile-{{ $item->item_code }}-webp-image-src" srcset="{{ asset('storage/').$img_webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                            <source id="mobile-{{ $item->item_code }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                            <img class="d-block w-100" id="mobile-{{ $item->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                                        </picture>
                                                        </a>
                                                    </div>
                                                    <span class='d-none' id="mobile-{{ $item->item_code }}-image-data">0</span>
                                                </div>
                                                @if ($img_count > 1)
                                                <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $item->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="sr-only">Previous</span>
                                                </a>
                                                <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $item->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="sr-only">Next</span>
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-justify p-1 align-middle" style="font-size: 10pt">
                        {!! '<b>'.$item->item_code.'</b> - '.strip_tags($item->item_description)!!}
                    </td>
                    <td class="text-center p-1 align-middle" style="font-size: 10pt">{!! '<b>'.number_format($item->opening_stock).'</b> '.$item->stock_uom !!}</td>
                    <td class="text-center p-1 align-middle" style="font-size: 10pt">
                        @if ($detail->status == 'For Approval')
                            ₱ <input type="text" name="price[{{ $item->item_code }}][]" value="{{ number_format($item->price, 2) }}" style="text-align: center; width: 80px;" required/>
                        @else
                            ₱ {{ number_format($item->price, 2) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center font-weight-bold" colspan="4">No Item(s)</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if ($detail->status == 'Approved')
        <small class="d-block font-responsive">Date Approved: <b>{{ \Carbon\Carbon::parse($detail->modified)->format('F d, Y') }}</b></small>
        <small class="d-block font-responsive">Approved by: <b>{{ $detail->modified_by }}</b></small>
        @endif
    </div>
</form>