@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
        <div class="container">
            <div class="row pt-3">
                <div class="col-md-12 p-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <span class="font-responsive">{{ Auth::user()->full_name }}</span>
                            <span id="branch-name" class="font-weight-bold d-block">{{ $branch }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                        </div>
                        <div class="card-body p-1">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="font-responsive text-center" style="width: 50%">Item</th>
                                    <th class="font-responsive text-center d-none d-sm-table-cell">Opening Stock</th>
                                    <th class="font-responsive text-center d-none d-sm-table-cell">Price</th>
                                </tr>
                                @forelse ($inventory as $inv)
                                    <tr>
                                        <td class="font-responsive">
                                            {!! '<b>'.$inv->item_code.'</b> - '.$inv->item_description !!}
                                            <div class="d-block d-md-none">
                                                <div class="row">
                                                    <div class="col-6 text-center p-1 border border-secondary"><b>Opening Stock</b></div>
                                                    <div class="col-6 text-center p-1 border border-secondary"><b>Price</b></div>
                                                    <div class="col-6 text-center p-1 border border-secondary">{{ $inv->opening_stock * 1 }}</div>
                                                    <div class="col-6 text-center p-1 border border-secondary">₱ {{ number_format($inv->price * 1, 2) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-responsive d-none d-sm-table-cell">
                                            {{ $inv->opening_stock * 1 }}
                                        </td>
                                        <td class="font-responsive d-none d-sm-table-cell">
                                            ₱ {{ number_format($inv->price * 1, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="font-responsive text-center" colspan=3>
                                            No available item(s) / All items for this branch are approved.
                                        </td>
                                    </tr>
                                @endforelse
                            </table>
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
        table {
            table-layout: fixed;
            width: 100%;   
        }
    </style>
@endsection