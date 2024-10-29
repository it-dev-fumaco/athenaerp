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
                            @if ($generic_manuals)
                                <h6 class="font-weight-bold text-info text-uppercase my-3">User Manuals</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach ($generic_manuals as $manual)
                                        <li class="list-group-item p-2"><a href="{{ asset("storage/Manuals/$manual") }}" class="text-dark" target="_blank"><i class="fas fa-angle-right"></i> {{ $manual }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($consignment_supervisor_manuals && in_array(Auth::user()->user_group, ['Director', 'Consignment Supervisor']))
                                <h6 class="font-weight-bold text-info text-uppercase my-3">Consignment Supervisor Manuals</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach ($consignment_supervisor_manuals as $manual)
                                        <li class="list-group-item p-2"><a href="{{ asset("storage/Manuals/$manual") }}" class="text-dark" target="_blank"><i class="fas fa-angle-right"></i> {{ $manual }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($consignment_promodiser_manuals && in_array(Auth::user()->user_group, ['Director', 'Promodiser']))
                                <h6 class="font-weight-bold text-info text-uppercase my-3">Promodiser Manuals</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach ($consignment_promodiser_manuals as $manual)
                                        <li class="list-group-item p-2"><a href="{{ asset("storage/Manuals/$manual") }}" class="text-dark" target="_blank"><i class="fas fa-angle-right"></i> {{ $manual }}</a></li>
                                    @endforeach
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