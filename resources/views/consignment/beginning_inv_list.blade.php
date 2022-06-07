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
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6><br>
                            <a href="/beginning_inventory" class="btn btn-xs btn-outline-primary float-right m-0 p-1">Create Inventory Entry</a>
                        </div>
                        <div class="card-body p-1">
                            <table class="table table-bordered">
                                <tr>
                                    <th class="font-responsive text-center">Details</th>
                                    <th class="font-responsive text-center" style="width: 65%;">Branch Warehouse</th>
                                </tr>
                                @foreach ($beginning_inventory as $store)
                                    @php
                                        $badge = 'secondary';
                                        if($store->status == 'Approved'){
                                            $badge = 'success';
                                        }else if($store->status == 'For Approval'){
                                            $badge = 'primary';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="font-responsive text-center">
                                            {{ $store->name }}<br/>
                                            {{ Carbon\Carbon::parse($store->transaction_date)->format('F d, Y') }}<br/>
                                            <span class="badge badge-{{ $badge }}">{{ $store->status }}</span>
                                        </td>
                                        <td class="font-responsive">
                                            <a href="/beginning_inventory{{ ($store->status == 'For Approval' ? '/' : '_items/').$store->name }}">{{ $store->branch_warehouse }}</a>
                                        </td>
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