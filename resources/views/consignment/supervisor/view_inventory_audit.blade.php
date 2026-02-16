@extends('layout', [
    'namePage' => 'Inventory Audit List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-2 offset-1">
                    <div style="margin-bottom: -43px;">
                        @php
                            $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                        @endphp
                        <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-center font-weight-bold m-2 text-uppercase">Inventory Report List</h4>
                </div>
                @if (Auth::check() && in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
                <div class="col-2">
                    <div class="float-right pt-2">
                        <a href="/consignment_ledger" class="btn btn-info btn-sm">View Stock Movement</a>
                    </div>
                </div>
                @endif
            </div>
            <div class="row">
                <div class="col-12" id="supervisor-inventory-audit-list"
                    data-displayed-data='@json($displayedData)'
                    data-select-year='@json($selectYear)'
                    data-promodisers='@json($promodisers)'>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
        .select2{
			width: 100% !important;
			outline: none !important;
		}
		.select2-selection__rendered {
			line-height: 25px !important;
			outline: none !important;
		}
		.select2-container .select2-selection--single {
			height: 31px !important;
			padding-top: 1.5%;
			outline: none !important;
		}
		.select2-selection__arrow {
			height: 36px !important;
		}
</style>
@endsection

@section('script')
@endsection