@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
        <div class="container">
            <div class="row pt-3">
                <div class="col-md-12 p-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center font-weight-bold">Assigned Consignment Branch</div>
                        <div class="card-body p-1">

                            <table class="table table-bordered" style="font-size: 8pt;">
                                <thead>
                                    <th class="text-center p-1">Branch Name</th>
                                    <th class="text-center p-1">Action</th>
                                </thead>
                                <tbody>
                                    @forelse ($assigned_consignment_store as $branch)
                                    <tr>
                                        <td class="text-justify p-2 align-middle">{{ $branch }}</td>
                                        <td class="text-center p-2"><a href="/view_calendar_menu/{{ $branch }}" class="btn btn-primary btn-xs"><i class="fas fa-search"></i></a></td>
                                    </tr> 
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold" colspan="2">No assigned consignment branch</td>
                                    </tr> 
                                    @endforelse
                                </tbody>
                            </table>
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