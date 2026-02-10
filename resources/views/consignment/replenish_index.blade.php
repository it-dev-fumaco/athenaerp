@extends('layout', [
    'namePage' => 'Consignment Order',
    'activePage' => 'beginning_inventory',
])

@section('content')
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="card card-lightblue">
                            <div class="card-header d-flex justify-content-between align-items-center p-2">
                                <div class="flex-grow-1 text-center">
                                    <span class="font-responsive font-weight-bold text-uppercase">Consignment Orders</span>
                                </div>
                                @if (Auth::user()->user_group == 'Promodiser')
                                    <a href="/consignment/replenish/form" class="btn btn-sm btn-primary">+ Add</a>
                                @endif
                            </div>
                            <div class="card-body p-0">
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
                                <div id="consignment-replenish" data-stores='@json($assignedConsignmentStores)' data-statuses='@json(["Draft", "For Approval", "Approved", "Delivered", "Cancelled"])'></div>
                                </div>
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
        table { width: 100%; }
        input[type=number] {
            -moz-appearance: textfield;
        }
        .morectnt span {
            display: none;
        }
    </style>
@endsection

@section('script')
@endsection