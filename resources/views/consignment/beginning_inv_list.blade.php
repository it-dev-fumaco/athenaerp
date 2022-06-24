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
                    <div class="card card-lightblue">
                        <div class="card-header p-2">
                            @if(session()->has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size: 10pt;">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert" style="font-size: 10pt;">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <div class="d-flex flex-row align-items-center justify-content-between" style="font-size: 9pt;">
                                <div class="p-0">
                                    <span class="font-responsive font-weight-bold text-uppercase m-0 p-0">Beginning Inventory</span>
                                </div>
                                <div class="p-0">
                                    <a href="/beginning_inventory" class="btn btn-sm btn-primary m-0"><i class="fas fa-plus"></i> Create</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead class="text-uppercase">
                                    <th class="font-responsive text-center p-2">Date</th>
                                    <th class="font-responsive text-center p-2" style="width: 65%;">Branch Warehouse</th>
                                </thead>
                                @forelse ($beginning_inventory as $store)
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
                                            <small class="d-block">{{ Carbon\Carbon::parse($store->transaction_date)->format('F d, Y') }}</small>
                                            <span class="badge badge-{{ $badge }}">{{ $store->status }}</span>
                                        </td>
                                        <td class="font-responsive p-2 align-middle">
                                            <a href="/beginning_inventory{{ ($store->status == 'For Approval' ? '/' : '_items/').$store->name }}">{{ $store->branch_warehouse }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted text-uppercase font-responsive">No record(s) found</td>
                                    </tr>
                                @endforelse
                            </table>
                            <div class="mt-3 ml-3 clearfix pagination" style="display: block;">
                                <div class="float-right">
                                    {{ $beginning_inventory->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection