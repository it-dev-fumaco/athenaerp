@extends('layout', [
    'namePage' => 'Promodisers List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                <a href="/" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-10 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Promodiser(s) List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <span class="font-weight-bolder d-block font-responsive">Assigned Store Promodiser(s) List</span>
                        </div>
                        <div class="card-body p-3">
                            <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                <thead class="border-top">
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 25%;">Promodiser Name</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 45%;">Assigned Store</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Opening</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Last Login</th>
                                </thead>
                                <tbody>
                                    @forelse ($result as $row)
                                    <tr>
                                        <td class="text-center p-1 align-middle">{{ $row['promodiser_name'] }}</td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block border-bottom p-1">{{ $store }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block border-bottom p-1 {{ in_array($store, array_keys($stores_with_beginning_inventory)) ? 'bg-success' : 'bg-gray' }}">
                                                {!! array_key_exists($store, $stores_with_beginning_inventory) ? \Carbon\Carbon::parse($stores_with_beginning_inventory[$store])->format('m-d-Y') : '&nbsp;' !!}
                                            </span>
                                            @endforeach
                                        </td>
                                        <td class="text-center p-1 align-middle">{{ $row['last_login'] ? \Carbon\Carbon::parse($row['last_login'])->format('F d, Y h:i A') : null }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td class="text-center font-weight-bold text-uppercase text-muted" colspan="4">No record(s) found</td>
                                    </tr> 
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="float-left m-2">Total: <b>{{ $total_promodisers }}</b></div>
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