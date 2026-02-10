@extends('layout', [
    'namePage' => $purpose == 'Material Transfer' ? 'Stock Transfers List' : 'Sales Returns List',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-2">
                            <div class="d-flex flex-row align-items-center justify-content-between">
                                <div class="p-0 col-8 mx-auto text-center" style="display: flex; justify-content: center; align-items: center;">
                                    <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">Stock Transfers List</span>
                                </div>
                                @if (Auth::user()->user_group == 'Promodiser')
                                    <!-- Tablet/Desktop -->
                                    <div class="dropdown d-none d-md-block">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer" style="color: #000 !important">Store Transfer Request</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return" style="color: #000 !important">Item Pull Out Request</a>
                                            <a class="dropdown-item" href="/item_return/form" style="color: #000 !important">Item Return Entry</a>
                                        </div>
                                    </div>
                                    <!-- Tablet/Desktop -->
                                    <!-- Mobile -->
                                    <div class="dropdown dropleft d-md-none">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Create
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                            <a class="dropdown-item" href="/stock_transfer/form?action=Store Transfer" style="color: #000 !important">Store Transfer Request</a>
                                            <a class="dropdown-item" href="/stock_transfer/form?action=For Return" style="color: #000 !important">Item Pull Out Request</a>
                                            <a class="dropdown-item" href="/item_return/form" style="color: #000 !important">Item Return Entry</a>
                                        </div>
                                    </div>
                                    <!-- Mobile -->
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                                <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <div id="consignment-stock-transfer-list"></div>
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
        .morectnt span {
            display: none;
        }
        .modal{
            background-color: rgba(0,0,0,0.4);
        }
        @media (max-width: 575.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media (max-width: 767.98px) {
            .mobile-first-row{
                width: 70%;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
            .mobile-first-row{
                width: 70%;
            }
        }

    </style>
@endsection
