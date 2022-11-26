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
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-10 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Promodiser(s) List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <div class="row">
                                <div class="col-8 offset-2">
                                    <span class="font-weight-bolder d-block font-responsive">Assigned Store Promodiser(s) List</span>
                                </div>
                                <div class="col-2">
                                    <a href="/add_promodiser" class="btn btn-primary" style="font-size: 10pt;"><i class="fa fa-plus"></i> Add Promodiser</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            @if(session()->has('success'))
                                <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            <table class="table table-bordered table-striped" style="font-size: 9pt;">
                                <thead class="border-top">
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Promodiser Name</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 45%;">Assigned Store</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Opening</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Last Login</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 5%;">Action</th>
                                </thead>
                                <tbody>
                                    @forelse ($result as $row)
                                    <tr>
                                        <td class="text-center p-1 align-middle">
                                            {{ $row['promodiser_name'] }}<br><span class="badge badge-{{ $row['enabled'] ? 'primary' : 'secondary' }}">{{ $row['enabled'] ? 'Active' : 'Inactive' }}</span>
                                        </td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block {{ count($row['stores']) > 1 ? 'border-bottom' : null }} p-1">{{ $store }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block border-bottom p-1 {{ in_array($store, array_keys($stores_with_beginning_inventory)) ? 'bg-success' : 'bg-gray' }}">
                                                {!! array_key_exists($store, $stores_with_beginning_inventory) ? \Carbon\Carbon::parse($stores_with_beginning_inventory[$store])->format('m-d-Y') : '&nbsp;' !!}
                                            </span>
                                            @endforeach
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            @if ($row['login_status'])
                                            {!! $row['login_status'] !!}
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center p-1 align-middle">
                                            <a href="/edit_promodiser/{{ $row['id'] }}" class="btn btn-primary btn-xs"><i class="fa fa-edit" style="font-size: 9pt;"></i></a>
                                        </td>
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