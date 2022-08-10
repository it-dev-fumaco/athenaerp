@extends('layout', [
    'namePage' => 'User Manual',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-2 pl-0 pr-0">
                <div class="col-md-12 m-0 p-0">
                    <h6 class="font-weight-bold text-secondary text-uppercase text-center">User Manual</h6>
                    <div class="card card-info card-outline" style="font-size: 9pt;">
                        <div class="card-body">
                            @if (Auth::user()->user_group == 'Promodiser')
                            <h6 class="font-weight-bold text-info text-uppercase">Promodiser</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item p-2"><a href="/user_manual/beginning_inventory"><i class="fas fa-angle-right"></i> Beginning Inventory</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/product_sold_entry"><i class="fas fa-angle-right"></i> Product Sold Entry</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/stock_transfer"><i class="fas fa-angle-right"></i> Stock Transfer</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/damaged_items"><i class="fas fa-angle-right"></i> Damaged Item Report</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/stock_receiving"><i class="fas fa-angle-right"></i> Receive Incoming Deliveries</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/inventory_audit"><i class="fas fa-angle-right"></i> Inventory Audit</a></li>
                            </ul>
                            @endif
                            @if (Auth::user()->user_group == 'Consignment Supervisor')
                            <h6 class="font-weight-bold text-info text-uppercase">Consignment Supervisor</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item p-2"><a href="/user_manual/consignment_dashboard"><i class="fas fa-angle-right"></i> Dashboard</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/beginning_entries"><i class="fas fa-angle-right"></i> Beginning Entries</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/inventory_report"><i class="fas fa-angle-right"></i> Inventory Report</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/inventory_summary"><i class="fas fa-angle-right"></i> Inventory Summary</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/stock_to_receive"><i class="fas fa-angle-right"></i> Stocks to Receive</a></li>
                                <li class="list-group-item p-2"><a href="/user_manual/consignment_stock_transfer"><i class="fas fa-angle-right"></i> Stock Transfer</a></li>
                            </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection

@section('script')

@endsection