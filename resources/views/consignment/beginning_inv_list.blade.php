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
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                        </div>
                        <div class="card-body p-1">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="font-responsive" style="width: 65%;">Branch Warehouse</th>
                                    <th class="font-responsive">Transaction Date</th>
                                </tr>
                                @foreach ($beginning_inventory as $store)
                                    <tr>
                                        <td class="font-responsive">
                                            <a href="/beginning_inventory_items/{{ $store->name }}">{{ $store->branch_warehouse }}</a>
                                        </td>
                                        <td class="font-responsive">{{ $store->transaction_date }}</td>
                                    </tr>
                                @endforeach
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