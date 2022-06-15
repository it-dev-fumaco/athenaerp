<form action="/approve_beginning_inv/{{ $detail->name }}" method="POST" id="beginning-inventory-approval-form">
    @csrf
    <div class="modal-header bg-navy text-white">
        <div class="container-fluid">
            <div class="row">
                <div class="col-8 text-left font-responsive">
                <h4>{{ $detail->branch_warehouse }}</h4>
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
                @endphp
                <tr>
                    <td class="text-center p-1 col-1 align-middle">
                        <picture>
                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp">
                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg">
                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="100%">
                        </picture>
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
    </div>
</form>