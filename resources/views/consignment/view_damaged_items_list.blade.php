@extends('layout', [
    'namePage' => 'Damaged Item List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container p-0">
            <div class="row mt-1">
                <div class="col-md-12">
                   
                    <div style="margin-bottom: -43px;">
                        <a href="/" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i> </a>

                    </div>
                    <h3 class="text-center font-weight-bold m-2 text-uppercase">Damaged Item List</h3>
                    <div class="card card-info card-outline">
                        <div class="card-body p-2">
                            <form action="/damage_report/list" method="GET">
                                <div class="row p-1 mt-1 mb-1">
                                    <div class="col-3">
                                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search Item" />
                                    </div>
                                    <div class="col-3">
                                        @php
                                            $statuses = ['For Approval', 'Approved', 'Cancelled'];
                                        @endphp
                                        <select class="form-control" name="store" id="consignment-store-select">
                                            <option value="">Select Store</option>
                                            @foreach ($statuses as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-1">
                                        <button class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                            <table class="table table-bordered table-striped" style="font-size: 10pt;">
                                <thead>
                                    <th class="text-center" style="width: 10%;">Date</th>
                                    <th class="text-center" style="width: 35%;">Item Description</th>
                                    <th class="text-center" style="width: 10%;">Qty</th>
                                    <th class="text-center" style="width: 20%;">Store</th>
                                    <th class="text-center" style="width: 20%;">Damage Description</th>
                                    <th class="text-center" style="width: 5%;">-</th>
                                </thead>
                                @forelse ($items_arr as $i => $item)
                                    <tr>
                                        <td class="p-1 text-center align-middle">{{ $item['creation'] }}</td>
                                        <td class="p-1 text-justify align-middle">
                                            <div class="d-flex flex-row align-items-center">
                                                <div class="p-1">
                                                    <picture>
                                                        <source srcset="{{ asset('storage/'.$item['webp']) }}" type="image/webp">
                                                        <source srcset="{{ asset('storage'.$item['image']) }}" type="image/jpeg">
                                                        <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ str_slug(explode('.', $item['image'])[0], '-') }}" width="70">
                                                    </picture>
                                                </div>
                                                <div class="p-1">
                                                    <span class="d-block font-weight-bold">{{ $item['item_code'] }}</span>
                                                    <small class="d-block item-description">{!! strip_tags($item['description']) !!}</small>

                                                    <small class="d-block mt-2">Created by: <b>{{ $item['promodiser'] }}</b></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-1 text-center align-middle">
                                            <span class="d-block font-weight-bold">{{ $item['damaged_qty'] }}</span>
                                            <small>{{ $item['uom'] }}</small>
                                        </td>
                                        <td class="p-1 text-center align-middle">{{ $item['store'] }}</td>
                                        <td class="p-1 text-center align-middle">{{ $item['damage_description'] }}</td>
                                        <td class="p-1 text-center align-middle">
                                            <a href="#" class="btn btn-primary btn-sm"><i class="fas fa-retweet"></i></a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No record(s) found.</td>
                                    </tr>
                                @endforelse
                            </table>
                            <div class="float-left m-2">Total: <b>{{ $damaged_items->total() }}</b></div>
                            <div class="float-right m-2" id="beginning-inventory-list-pagination">{{ $damaged_items->links('pagination::bootstrap-4') }}</div>
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
    var showTotalChar = 110, showChar = "Show more", hideChar = "Show less";
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

    
    $('#consignment-store-select').select2({
        placeholder: "Select Store",
        ajax: {
            url: '/consignment_stores',
            method: 'GET',
            dataType: 'json',
            data: function (data) {
                return {
                    q: data.term // search term
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
</script>
@endsection