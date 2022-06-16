@extends('layout', [
    'namePage' => 'Inventory Audit Item(s)',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <span class="font-weight-bolder d-block font-responsive">{{ $store }}</span>
                        </div>
                        <div class="card-body p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-3">
                                    <a href="/inventory_audit" class="btn btn-secondary m-0" style="width: 70px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-6">
                                    <h6 class="font-responsive font-weight-bold text-center m-0 text-uppercase d-block">Inventory Audit Item(s)</h6>
                                </div>
                            </div>
                            <h5 class="text-center mt-2 font-weight-bolder font-responsive">{{ $duration }}</h5>
                            <table class="table" style="font-size: 8pt;" id="items-table">
                                <thead class="border-top">
                                    <th class="text-center p-1" style="width: 60%;">ITEM CODE</th>
                                    <th class="text-center p-1" style="width: 40%;">AUDIT QTY</th>
                                </thead>
                                <tbody>
                                    @forelse ($list as $row)
                                    @php
                                        $id = $row->item_code;
                                        $img = array_key_exists($id, $item_images) ? "/img/" . $item_images[$id][0]->image_path : "/icon/no_img.png";
                                        $img_webp = array_key_exists($id, $item_images) ? "/img/" . explode('.',$item_images[$id][0]->image_path)[0].'.webp' : "/icon/no_img.webp";

                                        $img_count = array_key_exists($id, $item_images) ? count($item_images[$id]) : 0;
                                    @endphp
                                    <tr style="border-bottom: 0 !important;">
                                        <td class="text-justify p-1 align-middle" style="border-bottom: 10px !important;">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-0 text-left">
                                                    <a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $id }}" data-title="{{ $id }}">
                                                        <picture>
                                                            <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                            <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" width="40" height="40">
                                                        </picture>
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="font-weight-bold">{{ $id }}</span>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="mobile-{{ $row->item_code }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ $row->item_code }}</h5>
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
                                                                            <picture>
                                                                                <source id="mobile-{{ $row->item_code }}-webp-image-src" srcset="{{ asset('storage/').$img_webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                                                                <source id="mobile-{{ $row->item_code }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                                                                <img class="d-block w-100" id="mobile-{{ $row->item_code }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                                                            </picture>
                                                                        </div>
                                                                        <span class='d-none5' id="mobile-{{ $row->item_code }}-image-data">0</span>
                                                                    </div>
                                                                    @if ($img_count > 1)
                                                                    <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $row->item_code }}')" role="button" data-slide="prev" style="color: #000 !important">
                                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                        <span class="sr-only">Previous</span>
                                                                    </a>
                                                                    <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $row->item_code }}')" role="button" data-slide="next" style="color: #000 !important">
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
                                        <td class="text-center p-1 align-middle font-weight-bold" style="border-bottom: 0 !important;">
                                            <span class="d-block">{{ number_format($row->audit_qty) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="border-top: 0 !important;" class="pt-0 pb-2 pl-2 prl-2"><div class="item-description">{!! strip_tags($row->description) !!}</div></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold text-uppercase text-muted" colspan="4">No item(s) found</td>
                                    </tr> 
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="m-2">
                                Total: <b>{{ count($list) }}</b>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
    .morectnt span {
        display: none;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
        $('.item-description').each(function() {
            var content = $(this).text();
            if (content.length > showTotalChar) {
                var con = content.substr(0, showTotalChar);
                var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="showmoretxt">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $(".showmoretxt").click(function(e) {
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
    });
</script>
@endsection