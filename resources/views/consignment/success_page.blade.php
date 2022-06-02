@extends('layout', [
    'namePage' => 'Success',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-success card-outline">
                        <div class="card-body p-1">
                            @if(session()->has('success'))
                            <p class="text-success text-center mb-0" style="font-size: 8rem;">
                                <i class="fas fa-check-circle"></i>
                            </p>
                            <p class="text-center text-uppercase mt-0 font-weight-bold">{{ session()->get('success') }}</p>

                            <div class="text-center mb-2" style="font-size: 9pt;">
                                <span class="d-block font-weight-bold mt-3">{{ session()->get('no_of_items_updated') }}</span>
                                <small class="d-block">No. of updated Items</small>
                                <span class="d-block font-weight-bold mt-3">{{ \Carbon\Carbon::parse(session()->get('transaction_date'))->format('F d, Y') }}</span>
                                <small class="d-block">Transaction Date</small>
                                <span class="d-block font-weight-bold mt-3">{{ session()->get('branch') }}</span>
                                <small class="d-block">Branch / Store</small>
                            </div>

                            <div class="text-center mb-4 mt-4">
                                <a href="/" class="btn bg-lightblue"><i class="fas fa-home"></i> Homepage</a>
                            </div>
                            @else
                            <script>window.location = "/";</script>
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