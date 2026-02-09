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
                                    {{-- <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Status</th> --}}
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Promodiser Name</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 40%;">Assigned Store</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 10%;">Opening</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 20%;">Last Login</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 5%;">Enable</th>
                                    <th class="text-center font-responsive p-2 align-middle" style="width: 5%;">Action</th>
                                </thead>
                                <tbody>
                                    @forelse ($result as $row)
                                    <tr>
                                        {{-- <td></td> --}}
                                        <td class="text-center p-1 align-middle">
                                            {{ $row['promodiser_name'] }}
                                            <br>
                                            {{-- <span class="badge badge-{{ $row['enabled'] ? 'primary' : 'secondary' }}">{{ $row['enabled'] ? 'Active' : 'Inactive' }}</span> --}}
                                            <small>{{ $row['id'] }}</small>
                                        </td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block {{ count($row['stores']) > 1 ? 'border-bottom' : null }} p-1">{{ $store }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-center p-0 align-middle">
                                            @foreach ($row['stores'] as $store)
                                            <span class="d-block border-bottom p-1 {{ in_array($store, array_keys($storesWithBeginningInventory)) ? 'bg-success' : 'bg-gray' }}">
                                                {!! array_key_exists($store, $storesWithBeginningInventory) ? \Carbon\Carbon::parse($storesWithBeginningInventory[$store])->format('m-d-Y') : '&nbsp;' !!}
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
                                            <label class="switch">
                                                <input type="checkbox" class="toggle" name="status" {{ $row['enabled'] ? 'checked' : '' }} value="{{ $row['id'] }}"/>
                                                <span class="slider round"></span>
                                            </label>
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
                            <div class="float-left m-2">Total: <b>{{ $totalPromodisers }}</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 30px;
        height: 16px;
    }

    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 10px;
        width: 10px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(16px);
        -ms-transform: translateX(16px);
        transform: translateX(16px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>
@endsection

@section('script')
<script>
    $(document).ready(function (){
        const showNotification = (color, message, icon) => {
            $.notify({
                icon: icon,
                message: message
            },{
                type: color,
                timer: 500,
                z_index: 1060,
                placement: {
                from: 'top',
                align: 'center'
                }
            });
        }

        $(document).on('change', ".toggle", function(){
            const btn = $(this);

            const id = $(this).val()
            const data = {
                'enabled': $(this).prop('checked') == true ? 1 : 0,
                'enabled': $(this).val(),
                '_token': "{{ csrf_token() }}",
            }
            $.ajax({
                type:'POST',
                url:'/edit_promodiser_submit/' + id,
                data: data,
                success: (response) => {
                    console.log('success');
                },
                error: (error) => {
                    btn.prop('checked', false)
                    showNotification('danger', 'An error occured while updating user.', 'fa fa-info')
                }
            });
        });    
    })
</script>

@endsection