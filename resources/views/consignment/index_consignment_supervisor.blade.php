@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container p-0">
            <div class="row p-0 m-0">
                <div class="col-6 col-md-3 p-1">
                    <div class="info-box bg-gradient-primary m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text font-responsive m-0">Sales Report Submission</span>
                            <span class="info-box-number font-responsive m-0">{{ $sales_report_submission_percentage }}%</span>
                            <span class="progress-description font-responsive" style="font-size: 7pt;">{{ $duration }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <div class="info-box bg-gradient-info m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text font-responsive">Stock Transfer Request</span>
                            <span class="info-box-number font-responsive">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <div class="info-box bg-gradient-warning m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text font-responsive">Damaged Item Report</span>
                            <span class="info-box-number font-responsive">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 p-1">
                    <div class="info-box bg-gradient-secondary m-0">
                        <div class="info-box-content p-1">
                            <span class="info-box-text font-responsive">Stock Adjustments</span>
                            <span class="info-box-number font-responsive">0</span>
                            <div class="progress">
                                <div class="progress-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <h6 class="font-weight-bold text-center text-uppercase">Pending Submission of Sales Report</h6>
                        </div>
                        <div class="card-body p-1">
                            <form action="#" method="GET" autocomplete="off">
                                <div class="row p-1 mt-1 mb-1">
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <button class="btn btn-primary w-100">Search</button>
                                    </div>
                                </div>
                            </form>
                            <table class="table table-bordered" style="font-size: 10pt;">
                                <thead>
                                    <th class="font-responsive text-center">#</th>
                                    <th class="font-responsive text-center">Branch</th>
                                    <th class="font-responsive text-center">Assigned Promodiser(s)</th>
                                    <th class="font-responsive text-center">Cutoff Period</th>
                                    <th class="font-responsive text-center">Pending Date(s)</th>
                                </thead>
                            </table>
                            <div class="float-right mt-4">
                                {{-- pagination here --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            <h6 class="font-weight-bold text-center text-uppercase">Pending and Delivered Item(s) Report</h6>
                        </div>
                        <div class="card-body p-1">
                            <form action="#" method="GET" autocomplete="off">
                                <div class="row p-1 mt-1 mb-1">
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <input type="text" class="form-control" />
                                    </div>
                                    <div class="col-3">
                                        <button class="btn btn-primary w-100">Search</button>
                                    </div>
                                </div>
                            </form>
                            <table class="table table-bordered" style="font-size: 10pt;">
                                <thead>
                                    <th class="font-responsive text-center">#</th>
                                    <th class="font-responsive text-center">Branch</th>
                                    <th class="font-responsive text-center">Assigned Promodiser(s)</th>
                                    <th class="font-responsive text-center">Cutoff Period</th>
                                    <th class="font-responsive text-center">Pending Date(s)</th>
                                </thead>
                            </table>
                            <div class="float-right mt-4">
                                {{-- pagination here --}}
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