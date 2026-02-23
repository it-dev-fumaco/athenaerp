@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white">
    <div class="content-header pt-0">
        <div class="container-fluid">
            <div class="row pt-2">
                <div class="col-sm-12">
                    <div class="card bg-light border-0 shadow-sm">
                        <div class="card-body p-3 p-md-4" style="min-height: 900px;">
                            <div
                                id="dashboard-app"
                                data-user-group="{{ e(Auth::user()->user_group ?? '') }}"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
