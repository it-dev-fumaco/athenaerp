@extends('layout', [
    'namePage' => 'Inventory Report User Manual',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-2 pl-0 pr-0">
                <div class="col-md-12 m-0 p-0">
                    <div class="card card-info card-outline" style="font-size: 9pt;">
                        <div class="card-header p-1">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb ml-2">
                                  <li class="breadcrumb-item"><a href="/">Home</a></li>
                                  <li class="breadcrumb-item"><a href="/user_manual">User Manuals</a></li>
                                  <li class="breadcrumb-item active" aria-current="page">Inventory Report</li>
                                </ol>
                              </nav>
                        </div>
                        <div class="card-body">
                            <h6 class="font-weight-bold text-info text-uppercase">Inventory Report</h6>
                            <p>This page contains all of the inventory audit submitted by the promodisers and the list of stores with pending inventory audit.</p>
                            <div class="d-flex justify-content-center">
                                <img src="{{ isset($images['cs_14']) ? $images['cs_14'] : $images['no_img'] }}" style="width: 100%; margin-bottom: 30px;">
                            </div>
                            <ol class="mx-auto">
                                <li class="mb-2"><b>Inventory Report</b> – List of submitted inventory entries per store and cutoff</li>
                                <li class="mb-2"><b>Filters</b> – You can filter the list by store and year</li>
                                <li class="mb-2"><b>Pending for Submission</b> – List of stores without inventory audit entry for the current cutoff</li>
                                <li class="mb-2"><b>View Summary</b> – Summary page of the selected entry</li>
                                <li class="mb-2"><b>Back button</b> – Go back to dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

@endsection
