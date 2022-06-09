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
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <span class="font-weight-bold d-block font-responsive">{{ $branch }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold p-1">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                        </div>
                        <div class="card-body p-0">
                            <span class="float-right mr-3" style="font-size: 10pt;">Total items: {{ count($inventory) }}</span>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="font-responsive text-center p-2" style="width: 50%">Item Description</th>
                                    <th class="font-responsive text-center d-none d-sm-table-cell p-2">Opening Stock</th>
                                    <th class="font-responsive text-center d-none d-sm-table-cell p-2">Price</th>
                                </tr>
                                @forelse ($inventory as $inv)
                                    <tr>
                                        <td class="font-responsive p-2">
                                            {!! '<b>'.$inv->item_code.'</b> - '.$inv->item_description !!}
                                            <div class="d-block d-md-none m-1">
                                                <div class="row">
                                                    <div class="col-6 text-center p-1 border border-secondary"><b>Opening Stock</b></div>
                                                    <div class="col-6 text-center p-1 border border-secondary"><b>Price</b></div>
                                                    <div class="col-6 text-center p-1 border border-secondary">{{ $inv->opening_stock * 1 }}</div>
                                                    <div class="col-6 text-center p-1 border border-secondary">₱ {{ number_format($inv->price * 1, 2) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="font-responsive d-none d-sm-table-cell p-2">
                                            {{ $inv->opening_stock * 1 }}
                                        </td>
                                        <td class="font-responsive d-none d-sm-table-cell p-2">
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