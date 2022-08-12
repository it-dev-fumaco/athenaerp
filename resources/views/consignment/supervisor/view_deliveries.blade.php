@extends('layout', [
    'namePage' => 'Received Item(s) List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-md-10 offset-md-1">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Received Item(s) List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2 col-12">
                            <form method="GET" action="/view_consignment_deliveries">
                                <div class="d-flex flex-row align-items-center mt-2">
                                    <div class="p-1 col-4">
                                        <select class="form-control" name="store" id="consignment-store-select">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                    <div class="p-1">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                                    </div>
                                    <div class="p-1">
                                        <a href="/view_consignment_deliveries" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
                                    </div>
                                </div>
                            </form>
                            <div class="p-2">
                                <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                    <thead class="text-uppercase">
                                        <th class="text-center align-middle">Reference</th>
                                        <th class="text-center align-middle">Branch / Store</th>
                                        <th class="text-center align-middle">MREQ No.</th>
                                        <th class="text-center align-middle">Delivery Date</th>
                                        <th class="text-center align-middle">Promodiser</th>
                                        <th class="text-center align-middle">Status</th>
                                        <th class="text-center align-middle">Received By</th>
                                        <th class="text-center align-middle">Action</th>
                                    </thead>
                                    <tbody>
                                        @forelse ($result as $r)
                                        <tr>
                                            <td class="text-center align-middle">{{ $r['name'] }}</td>
                                            <td class="text-center align-middle">{{ $r['warehouse'] }}</td>
                                            <td class="text-center align-middle">{{ $r['mreq_no'] }}</td>
                                            <td class="text-center align-middle">{{ $r['delivery_date'] }}</td>
                                            <td class="text-center align-middle">{{ $r['promodiser'] }}</td>
                                            <td class="text-center align-middle">
                                                @if ($r['status'] == 'Received')
                                                <span class="badge badge-success" style="font-size: 8pt;">{{ $r['status'] }}</span>
                                                @else
                                                <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                                                @endif
                                            </td>
                                            <td class="text-center align-middle">{{ $r['received_by'] }}</td>
                                            <td class="text-center align-middle">
                                                <a href="#" data-toggle="modal" data-target="#{{ $r['name'] }}-modal">View</a>
                                            </td>
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-uppercase text-muted">No record(s) found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="m-2">
                                    {{ $list->appends(request()->query())->links('pagination::bootstrap-4') }}
                                </div>

                                @foreach ($result as $r)
                                <div class="modal fade" id="{{ $r['name'] }}-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl" role="document" style="font-size: 10pt;">
                                        <div class="modal-content">
                                            <div class="modal-header bg-navy">
                                                <h6 class="modal-title">Received Item(s)</h6>
                                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form></form>
                                                <h5 class="text-center font-responsive font-weight-bold m-0">{{ $r['warehouse'] }}</h5>
                                                <div class="row mt-2 mb-2">
                                                    <div class="col-6 pl-5">
                                                        <p class="m-1 font-details">Reference STE: <span class="font-weight-bold">{{ $r['name'] }}</span></p>
                                                        <p class="m-1 font-details">Delivery Date: <span class="font-weight-bold">{{ $r['delivery_date'] }}</span></p>
                                                        <p class="m-1 font-details">Date Received: <span class="font-weight-bold">{{ $r['date_received'] }}</span></p>
                                                    </div>
                                                    <div class="col-6 pl-5" >
                                                        <p class="m-1 font-details">Status: 
                                                            @if ($r['status'] == 'Received')
                                                            <span class="badge badge-success" style="font-size: 8pt;">{{ $r['status'] }}</span>
                                                            @else
                                                            <span class="badge badge-warning" style="font-size: 8pt;">To Receive</span> 
                                                            @endif
                                                        </p>
                                                        <p class="m-1 font-details">Received By: <span class="font-weight-bold">{{ $r['received_by'] }}</span></p>
                                                    </div>
                                                </div>
                                                <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                                    <thead>
                                                        <th class="text-center text-uppercase p-1 align-middle" style="width: 55%">Item Code</th>
                                                        <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Received Qty</th>
                                                        <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Rate</th>
                                                        <th class="text-center text-uppercase p-1 align-middle" style="width: 15%">Amount</th>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($r['items'] as $i)
                                                        <tr>
                                                            <td class="text-left p-1 align-middle">
                                                                <div class="d-flex flex-row justify-content-start align-items-center">
                                                                    <div class="p-1 text-left">
                                                                        <a href="{{ asset('storage/') }}{{ $i['img'] }}" data-toggle="mobile-lightbox" data-gallery="{{ $i['item_code'] }}" data-title="{{ $i['item_code'] }}">
                                                                        <picture>
                                                                            <source srcset="{{ asset('storage'.$i['img_webp']) }}" type="image/webp" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                                            <source srcset="{{ asset('storage'.$i['img']) }}" type="image/jpeg" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                                            <img src="{{ asset('storage'.$i['img']) }}" alt="{{ str_slug(explode('.', $i['img'])[0], '-') }}" width="40" height="40">
                                                                        </picture>
                                                                        </a>
                                                                    </div>
                                                                    <div class="p-1 m-0">
                                                                        <span class="d-block"><b>{{ $i['item_code'] }}</b> {{ strip_tags($i['description']) }}</span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center p-1 align-middle">
                                                                <span class="d-block font-weight-bold">{{ number_format($i['transfer_qty']) }}</span>
                                                                <small>{{ $i['stock_uom'] }}</small>
                                                            </td>
                                                            <td class="text-center p-1 align-middle">
                                                                <span class="d-block font-weight-bold">{{ '₱ ' . number_format($i['price'], 2) }}</span>
                                                            </td>
                                                            <td class="text-center p-1 align-middle">
                                                                <span class="d-block font-weight-bold">{{ '₱ ' . number_format($i['amount'], 2) }}</span>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>  
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('script')
<script>
    $(function () {
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
    });
</script>
@endsection
{{-- 


<div class="modal fade" id="mobile-{{ $item['item_code'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="/promodiser/receive/{{ $ste['name'] }}" method="get">
        <div class="modal-header">
          <h5 class="modal-title">{{ $item['item_code'] }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="image-container" class="container-fluid">
                    <div id="carouselExampleControls" class="carousel slide" data-interval="false">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <picture>
                                    <source id="mobile-{{ $item['item_code'] }}-webp-image-src" srcset="{{ asset('storage/').$img_webp }}" type="image/webp" class="d-block w-100" style="width: 100% !important;">
                                    <source id="mobile-{{ $item['item_code'] }}-orig-image-src" srcset="{{ asset('storage/').$img }}" type="image/jpeg" class="d-block w-100" style="width: 100% !important;">
                                    <img class="d-block w-100" id="mobile-{{ $item['item_code'] }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
                                </picture>
                            </div>
                            <span class='d-none' id="mobile-{{ $item['item_code'] }}-image-data">0</span>
                        </div>
                        @if ($item['img_count'] > 1)
                        <a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $item['item_code'] }}')" role="button" data-slide="prev" style="color: #000 !important">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $item['item_code'] }}')" role="button" data-slide="next" style="color: #000 !important">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> --}}