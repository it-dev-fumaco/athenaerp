@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/beginning_inventory_list" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Beginning Inventory Item(s)</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0 text-center">
                            <span class="font-weight-bold d-block font-responsive text-center mt-2">{{ $beginningInventory->branch_warehouse }}</span>
                            @php
                                $badge = null;
                                if($beginningInventory->status == 'Approved'){
                                    $badge = 'success';
                                }else if($beginningInventory->status == 'Cancelled'){
                                    $badge = 'secondary';
                                }else{
                                    $badge = 'primary';
                                }
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ $beginningInventory->status }}</span>
                            <div class="d-flex flex-row mt-2 pl-2" style="font-size: 9pt;">
                                <div class="p-0 col-7 text-left">
                                    <span class="d-block">Date: <b>{{ Carbon\Carbon::parse($beginningInventory->transaction_date)->format('F d, Y - h:i A') }}</b></span>
                                </div>
                                <div class="p-0 col-5">
                                    <span class="d-block">Total item(s): <b>{{ count($inventory) }}</b></span>
                                </div>
                            </div>
                            <div class="d-flex flex-row pl-2" style="font-size: 9pt;">
                                <div class="p-0 col-12 text-left">
                                    @if ($beginningInventory->status == 'Approved')
                                    <span class="d-block">Approved by: <b>{{ $beginningInventory->approved_by }}</b></span>
                                    <span class="d-block">Date Approved: <b>{{ Carbon\Carbon::parse($beginningInventory->date_approved)->format('M d, Y - h:i A') }}</b></span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
                            </div>
                            <table class="table" id="items-table" style="font-size: 8pt;">
                                <thead class="border-top">
                                    <th class="text-uppercase text-center p-1 align-middle">Item Description</th>
                                    <th class="text-uppercase text-center p-1 align-middle">Opening Stock</th>
                                    <th class="text-uppercase text-center p-1 align-middle">Price</th>
                                </thead>
                                <tbody>
                                    @forelse ($inventory as $inv)
                                    @php
                                        $img = isset($itemImage[$inv->item_code]) ? "/img/" . $itemImage[$inv->item_code][0]->image_path : "/icon/no_img.png";
                                        $imgWebp = isset($itemImage[$inv->item_code]) ? "/img/" . explode('.',$itemImage[$inv->item_code][0]->image_path)[0].'.webp' : "/icon/no_img.webp";

                                        $imgCount = array_key_exists($inv->item_code, $itemImage) ? count($itemImage[$inv->item_code]) : 0;
                                    @endphp 
                                    <tr style="border-bottom: 0 !important;">
                                        <td class="text-center p-1">
                                            <span class="d-none">{{ strip_tags($inv->item_description) }}</span>
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-1 text-left">
                                                    <a href="{{ Storage::disk('upcloud')->url($img) }}" class="view-images" data-item-code="{{ $inv->item_code }}">
                                                        <picture>
                                                            <source srcset="{{ Storage::disk('upcloud')->url($imgWebp) }}" type="image/webp" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <source srcset="{{ Storage::disk('upcloud')->url($img) }}" type="image/jpeg" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <img src="{{ Storage::disk('upcloud')->url($img) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="font-weight-bold">{{ $inv->item_code }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold">{{ number_format($inv->opening_stock) }}</td>
                                        <td class="text-center p-1 align-middle font-weight-bold">₱ {{ number_format($inv->price * 1, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-justify pt-0 pb-1 pl-2 pr-1 align-middle" style="border-top: 0 !important;">
                                            <span class="d-none">{{ $inv->item_code }}</span><!-- for search -->
                                            <span class="item-description">{{ strip_tags($inv->item_description) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="font-responsive text-center" colspan=3>
                                            No available item(s) / All items for this branch are approved.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                            <div class="container text-left pt-4">
                                <label style="font-size: 9pt;">Remarks</label>
                                <textarea cols="30" rows="10" class="form-control" style="font-size: 9pt;" readonly>{{ $beginningInventory->remarks }}</textarea>
                            </div>
                            @if ($beginningInventory->status != 'Cancelled')
                            <div class="container p-2">
                                <button class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-Modal">Cancel</button>

                                <!-- Modal -->
                                <div class="modal fade" id="cancel-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-navy">
                                                <h6 id="exampleModalLabel">Cancel Beginning Inventory?</h6>
                                                <button type="button" class="close">
                                                <span aria-hidden="true" style="color: #fff" onclick="close_modal('#cancel-Modal')">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                @if ($productsSold)
                                                    <div class="callout callout-danger text-justify" style="font-size: 9pt !important;">
                                                        <i class="fas fa-info-circle"></i> Canceling beginnning inventory record will also cancel submitted product sold records of the following:
                                                    </div>
                                                    <div class="container-fluid" id="cancel-container">
                                                        <table class="table" style="font-size: 9pt;">
                                                            <tr>
                                                                <th class="text-center" style='width: 60%;'>Item</th>
                                                                <th class="text-center" style="width: 20%;">Qty</th>
                                                                <th class="text-center" style="width: 20%;">Amount</th>
                                                            </tr>
                                                            @foreach($productsSold as $item)
                                                                <tr>
                                                                    <td class="p-0" colspan=3>
                                                                        <div class="p-0 row">
                                                                            <div class="col-6">
                                                                                <div class="row">
                                                                                    <div class="col-4">
                                                                                        <picture>
                                                                                            <source srcset="{{ Storage::disk('upcloud')->url($item['webp']) }}" type="image/webp">
                                                                                            <source srcset="{{ Storage::disk('upcloud')->url($item['image']) }}" type="image/jpeg">
                                                                                            <img src="{{ Storage::disk('upcloud')->url($item['image']) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $item['image'])[0], '-') }}" width="40" height="40">
                                                                                        </picture>
                                                                                    </div>
                                                                                    <div class="col-8" style="display: flex; justify-content: center; align-items: center;">
                                                                                        <b>{{ $item['item_code'] }}</b>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-3 pt-2">
                                                                                <b>{{ number_format($item['qty']) }}</b> <br>
                                                                                <small>{{ $item['uom'] }}</small>
                                                                            </div>
                                                                            <div class="col-3" style="display: flex; justify-content: center; align-items: center;">
                                                                                ₱ {{ number_format($item['price'], 2) }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-justify item-description">
                                                                            {{ $item['description'] }}
                                                                        </div>
                                                                        <div class="text-justify pt-1 pb-2">
                                                                            <b>Transaction Date:</b>&nbsp;{{ Carbon\Carbon::parse($item['date'])->format('F d, Y') }}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="callout callout-danger text-justify">
                                                        <i class="fas fa-info-circle"></i> Canceling beginnning inventory record will also cancel submitted product sold records.
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <a href="/cancel/approved_beginning_inv/{{ $beginningInventory->name }}" class="btn btn-primary w-100 submit-once">Confirm</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('style')
<style>
    .morectnt span {
        display: none;
    }
</style>
@endsection

@section('script')
<script>
    var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
    $('.item-description').each(function() {
        var content = $(this).text();
        if (content.length > showTotalChar) {
            var con = content.substr(0, showTotalChar);
            var hcon = content.substr(showTotalChar, content.length - showTotalChar);
            var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
            $(this).html(txt);
        }
    });

    $(".show-more").click(function(e) {
        e.preventDefault();
        if ($(this).hasClass("sample")) {
            $(this).removeClass("sample");
            $(this).text(showChar);
        } else {
            $(this).addClass("sample");
            $(this).text(hideChar);
        }

        $(this).parent().prev().toggle();
        $(this).prev().toggle();
        return false;
    });

    $("#item-search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#items-table tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
</script>
@endsection