@extends('layout', [
    'namePage' => 'Sales Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-lightblue">
                        <div class="card-header text-center p-1">
                            <div class="d-flex flex-row align-items-center">
                                <div class="p-0 col-2 text-left">
                                    <a href="/" class="btn btn-secondary m-0" style="width: 60px;"><i class="fas fa-arrow-left"></i></a>
                                </div>
                                <div class="p-1 col-8">
                                    <span class="font-weight-bolder d-block font-responsive text-uppercase">Monthly Sales Report</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                                <div class="row" style="font-size: 9pt;">
                                    <div class="col">
                                        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                                            {{ session()->get('success') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div id="sales-report-list"
                                data-branch="{{ $branch }}"
                                data-years='@json($years)'
                                data-current-year="{{ $currentYear }}">
                            </div>
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