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
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/inventory_audit" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Inventory Report Item(s)</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            <h5 class="font-responsive font-weight-bold text-center m-1 text-uppercase d-block">{{ $store }}</h5>
                            <h6 class="text-center mt-2 font-weight-bolder font-responsive">{{ $duration }}</h6>

                            <table class="table" style="font-size: 8pt;">
                                <thead class="border-top">
                                    <th class="text-center align-middle p-1" style="width: 33%;">ITEM CODE</th>
                                    <th class="text-center align-middle p-1" style="width: 26%;">PREVIOUS QTY</th>
                                    <th class="text-center align-middle p-1" style="width: 21%;">AUDIT QTY</th>
                                </thead>
                                <tbody>
                                    @foreach ($item_classification as $item_class => $items)
                                    <tr>
                                        <td colspan="3" class="p-0">
                                            <div class="bg-navy p-2">
                                                <span style="font-weight: bold; font-size: 10pt;">{{ $item_class }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @forelse ($items as $row)
                                    <tr style="border-bottom: 0 !important;">
                                        <td class="text-justify p-1 align-middle" style="border-bottom: 0 !important;">
                                            <div class="d-flex flex-row justify-content-start align-items-center">
                                                <div class="p-0 text-left">
                                                    <a href="{{ asset("storage/".$row['img']) }}" class="view-images" data-item-code="{{ $row['item_code'] }}">
                                                        <img src="{{ asset("storage/".$row['img']) }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $row['img'])[0], '-') }}" width="40" height="40">
                                                    </a>
                                                </div>
                                                <div class="p-1 m-0">
                                                    <span class="font-weight-bold">{{ $row['item_code'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold" style="border-bottom: 0 !important;">
                                            <span class="d-block">{{ $row['previous_qty'] }}</span>
                                        </td>
                                        <td class="text-center p-1 align-middle font-weight-bold" style="border-bottom: 0 !important;">
                                            <span class="d-block">{{ $row['audit_qty'] }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="border-top: 0 !important;" class="pt-0 pb-2 pl-2 prl-2"><div class="item-description">{!! strip_tags($row['description']) !!}</div></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold text-uppercase text-muted" colspan="4">No item(s) found</td>
                                    </tr> 
                                    @endforelse
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="m-2">
                                <span class="d-block font-responsive">Total: <b>{{ count($list) }}</b></span>
                                @php
                                    $promodiser = collect($list)->pluck('promodiser')->first();
                                    $transaction_date = collect($list)->pluck('transaction_date')->first();
                                    $transaction_date = $transaction_date ? Carbon\Carbon::parse($transaction_date)->format('M d, Y') : null;
                                @endphp
                                <small class="d-block mt-3">Submitted By: <b>{{ $promodiser.' - '.$transaction_date }}</b></small>
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