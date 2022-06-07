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
                        <div class="card-header">
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Beginning Inventory</span>
                            <a href="/beginning_inventory" class="btn btn-xs btn-outline-primary float-right m-0 p-1"><i class="fas fa-plus"></i> Create</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead class="text-uppercase">
                                    <th class="font-responsive text-center p-2">Details</th>
                                    <th class="font-responsive text-center p-2" style="width: 65%;">Branch Warehouse</th>
                                </thead>
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
                                        <td class="font-responsive text-center p-2 align-middle">
                                            <span class="d-block font-weight-bold">{{ $store->name }}</span>
                                            <small class="d-block">{{ Carbon\Carbon::parse($store->transaction_date)->format('F d, Y') }}</small>
                                            <span class="badge badge-{{ $badge }}">{{ $store->status }}</span>
                                        </td>
                                        <td class="font-responsive p-2 align-middle">
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