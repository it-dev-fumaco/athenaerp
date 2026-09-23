
@extends('layout', [
    'namePage' => 'Item Cost Updating',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-3">
				<div class="col-sm-12">
                    <h6 class="title m-1">Item Variants of <b>{{ $variantOf }}</b></h6>
                    @if ($canEditFixedPrice)
                        <div class="alert alert-info d-inline-block py-2 px-3 m-1 mb-2" role="note">
                            <div>
                                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                                <strong>Standard Selling Price (Override)</strong> – If a value is entered, it will be displayed as the selling price in the Item Profile and will override the system-computed Cost and Minimum Selling Price.
                            </div>
                            <div class="mt-1">
                                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
                                <strong>Athena Price List Override</strong> – The selling price can also be overridden through the “Standard Price List - Athena Display” price list.
                            </div>
                        </div>
                    @endif
                    <div style="position: absolute; right: 70px; top: -10px;">
                        <img src="{{ Storage::disk('upcloud')->url('/icon/arrow.png') }}" style="width: 35px; cursor: pointer;" id="back-btn">
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-0">
                            <div class="row m-0">
                                <div class="col-md-12 p-2">
                                    @if(session()->has('error'))
                                    <div class="col">
                                        <div class="alert alert-danger fade show text-center" role="alert">
                                            {{ session()->get('error') }}
                                        </div>
                                    </div>
                                    @endif
                                    @if(session()->has('success'))
                                    <div class="col">
                                        <div class="alert alert-success fade show text-center" role="alert">
                                            {{ session()->get('success') }}
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <form action="/update_rate" method="POST" autocomplete="off">
                                        @csrf
                                        <div class="table-responsive responsive-table-wrap" id="example">
                                            <table class="table table-bordered table-hover table-striped table-sm" style="font-size: 9pt;">
                                                <thead>
                                                    <th class="text-center align-middle p-1">Item Code</th>
                                                    @foreach ($attributeNames as $attrName)
                                                    <th class="text-center align-middle p-1 variants-th-attr">{{ $attrName }}</th>
                                                    @endforeach
                                                    <th class="text-center align-middle p-1 variants-th-price">Cost</th>
                                                    <th class="text-center align-middle p-1 variants-th-price">Min. Selling Price</th>
                                                    <th class="text-center align-middle p-1 variants-th-price">Standard Price</th>
                                                    <th class="text-center align-middle p-1 variants-th-price">Standard Selling Price (Override)</th>
                                                </thead>
                                                <tbody>
                                                    @forelse ($itemCodes as $itemCode)
                                                    <tr>
                                                        <td class="text-center align-middle p-1 font-weight-bold">{{ $itemCode }}</td>
                                                        @foreach ($attributeNames as $attr)
                                                        @php
                                                        $attrVal = data_get($attributes, "{$itemCode}.{$attr}");
                                                        @endphp
                                                        <td class="text-center align-middle p-1">{{ $attrVal }}</td>
                                                        @endforeach
                                                        <td class="text-center text-nowrap align-middle p-2">
                                                            @if ($prices[$itemCode]['rate'] > 0)
                                                            {{ '₱ ' . number_format($prices[$itemCode]['rate'], 2, '.', ',') }}
                                                            @else
                                                            <center>
                                                                <div class="form-group m-0 text-center input-group-price">
                                                                    <input type="text" class="form-control form-control-sm" name="price[{{ $itemCode }}]" placeholder="0.00">
                                                                </div>
                                                            </center>
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-nowrap align-middle p-1">
                                                            @if ($prices[$itemCode]['minimum'] > 0)
                                                            {{ '₱ ' . number_format($prices[$itemCode]['minimum'], 2, '.', ',') }}
                                                            @else
                                                            --
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-nowrap align-middle p-1">
                                                            @if ($prices[$itemCode]['standard'] > 0)
                                                            {{ '₱ ' . number_format($prices[$itemCode]['standard'], 2, '.', ',') }}
                                                            @else
                                                            --
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-nowrap align-middle p-2">
                                                            @php
                                                                $fixedStandardPrice = $fixedStandardPrices[$itemCode] ?? 0;
                                                            @endphp
                                                            @if ($canEditFixedPrice)
                                                            <center>
                                                                <div class="form-group m-0 text-center input-group-price">
                                                                    <input type="text" class="form-control form-control-sm" name="fixed_price[{{ $itemCode }}]" placeholder="0.00" value="{{ $fixedStandardPrice > 0 ? number_format($fixedStandardPrice, 2, '.', '') : '' }}">
                                                                </div>
                                                            </center>
                                                            @elseif ($fixedStandardPrice > 0)
                                                            {{ '₱ ' . number_format($fixedStandardPrice, 2, '.', ',') }}
                                                            @else
                                                            --
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td class="text-center font-weight-bold align-middle p-3" colspan="{{ 5 + count($attributeNames) }}">No item(s) found.</td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="float-right">
                                            <button class="btn btn-primary" type="submit">Update Cost</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
    .variants-th-attr { min-width: 100px; }
    .variants-th-price { min-width: 90px; }
    .input-group-price { width: 100px; max-width: 100%; }
    #example tr > *:first-child {
        position: -webkit-sticky;
        position: sticky;
        left: 0;
        min-width: 7rem;
        z-index: 1;
        background-color: #CCD1D1;
    }

    #example tr > *:first-child::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: -1;
    }
</style>
@endsection

@section('script')
<script>
    $('#back-btn').on('click', function(e){
        e.preventDefault();
        window.history.back();
    });
</script>
@endsection