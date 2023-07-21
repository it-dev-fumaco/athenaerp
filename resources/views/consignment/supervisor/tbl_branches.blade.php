<table class="table table-striped" style="font-size: 9pt;">
    <thead>
        <tr>
            <th class="p-2">Warehouse</th>
            <th class="p-2 text-center">Total Items</th>
            <th class="p-2 text-center">Total Qty</th>
            <th class="p-2 text-center">Total Inventory Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($branches as $i => $branch)
            @php
                $items_arr = isset($items[$branch->name]) ? $items[$branch->name] : [];
                $promodisers_arr = isset($promodisers[$branch->name]) ? $promodisers[$branch->name] : [];
            @endphp
            <tr>
                <td class="p-2">
                    <span class="text-{{ $promodisers_arr ? 'success' : 'secondary' }}">●</span>
                    <a href="#" data-toggle="modal" data-target="#modal-{{ $i }}">{{ $branch->name }}</a>

                    <div class="modal fade" id="modal-{{ $i }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                          <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Item list in {{ $branch->name }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-4">
                                                <p><b>Total Items</b>: {{ number_format(count($items_arr)) }}</p>
                                                <p><b>Total Qty</b>: {{ number_format(collect($items_arr)->sum('consigned_qty')) }}</p>
                                            </div>
                                            <div class="col-4">
                                                <p><b>Total Inventory Value</b>: ₱ {{ number_format(collect($items_arr)->sum('amount'), 2) }}</p>
                                            </div>
                                            <div class="col-4">
                                                <p><b>Assigned Promodiser(s)</b>:</p>
                                                <ul>
                                                    @foreach ($promodisers_arr as $promodiser)
                                                        <li>{{ $promodiser->full_name }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="container-fluid overflow-auto" style="max-height: 70vh">
                                        <div class="row">
                                            <div class="col-8 pt-2 pb-2 text-center border">
                                                <b>Item</b>
                                            </div>
                                            <div class="col-4">
                                                <div class="row">
                                                    <div class="col-4 pt-2 pb-2 text-center border">
                                                        <b style="white-space: nowrap">In-Store Qty</b>
                                                    </div>
                                                    <div class="col-4 pt-2 pb-2 text-center border">
                                                        <b style="white-space: nowrap">ERP Actual Qty</b>
                                                    </div>
                                                    <div class="col-4 pt-2 pb-2 text-center border">
                                                        <b>Price</b>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @forelse ($items_arr as $item)
                                            <div class="row">
                                                <div class="col-8 pt-2 pb-2 text-center border" style="font-size: 10pt;">
                                                    <div class="row">
                                                        <div class="col-2">
                                                            @php
                                                                $image = isset($images[$item->item_code]) ? $images[$item->item_code][0]->image_path : "/icon/no_img.png";
                                                                $webp = explode('.', $image)[0].'.webp';
                                                                $image_path = $image;
                                                                if(!Storage::disk('public')->exists('/img/'.$image)){
                                                                    if (Storage::disk('public')->exists('/img/'.explode('.', $image)[0].'.jpg')) {
                                                                        $image_path = explode('.', $image)[0].'.jpg';
                                                                    }elseif (Storage::disk('public')->exists('/img/'.explode('.', $image)[0].'.jpeg')) {
                                                                        $image_path = explode('.', $image)[0].'.jpeg';
                                                                    }
                                                                }
                                                            @endphp
                                                            <center>
                                                                @if(!Storage::disk('public')->exists('/img/'.$webp))
                                                                    <img class="w-100" src="{{ asset('storage/img/'.$image_path) }}">
                                                                @elseif(!Storage::disk('public')->exists('/img/'.$image_path))
                                                                    <img class="w-100" src="{{ asset('storage/img/'.$webp) }}">
                                                                @else
                                                                    <picture>
                                                                        <source srcset="{{ asset('storage/img/'.$webp) }}" type="image/webp">
                                                                        <source srcset="{{ asset('storage/img/'.$image_path) }}" type="image/jpeg">
                                                                        <img src="{{ asset('storage/img/'.$image_path) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $image_path)[0], '-') }}" class="img-responsive w-100 hover">
                                                                    </picture>
                                                                @endif
                                                            </center>
                                                        </div>
                                                        <div class="col-10 text-justify">
                                                            <b>{{ $item->item_code }}</b> - {{ strip_tags($item->description) }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="row h-100">
                                                        <div class="col-4 pt-2 pb-2 d-flex justify-content-center align-items-center border">
                                                            <p class="text-center">
                                                                <b>{{ number_format($item->consigned_qty) }}</b> <br>
                                                                <small class="text-muted">{{ $item->stock_uom }}</small>
                                                            </p>
                                                        </div>
                                                        <div class="col-4 pt-2 pb-2 d-flex justify-content-center align-items-center border">
                                                            <p class="text-center">
                                                                <b>{{ number_format($item->actual_qty) }}</b> <br>
                                                                <small class="text-muted">{{ $item->stock_uom }}</small>
                                                            </p>
                                                        </div>
                                                        <div class="col-4 pt-2 pb-2 d-flex justify-content-center align-items-center border">
                                                            <p class="text-center">
                                                                ₱ {{ number_format($item->consignment_price, 2) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                            </div>
                                        @empty
                                            <div class="col-12 p-2 text-center">
                                                <p>No result(s) found.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="/consignment/export/{{ $branch->name }}" class="btn btn-primary btn-sm"><i class="fa fa-file"></i> Export</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="p-2 text-center">{{ number_format(count($items_arr)) }}</td>
                <td class="p-2 text-center">{{ number_format(collect($items_arr)->sum('consigned_qty')) }}</td>
                <td class="p-2 text-center">₱ {{ number_format(collect($items_arr)->sum('amount'), 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div id="pagination">
    {{ $branches->links('pagination::bootstrap-4') }}
</div>
<style>
    .modal-xl {
        max-width: 80% !important;
    }
</style>