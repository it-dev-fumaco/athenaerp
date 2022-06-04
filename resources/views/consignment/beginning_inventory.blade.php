@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
        <div class="container">
            <div class="row pt-3">
                <div class="col-md-12 p-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header">
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <span class="font-responsive">{{ Auth::user()->full_name }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <h6 class="font-weight-bold text-center m-1 text-uppercase">Beginning Inventory</h6>
                            <h5 class="text-center mt-1">{{ \Carbon\Carbon::now()->format('F d, Y') }}</h5>
                        </div>
                        <div class="card-body p-1">
                            <div class="row">
                                <div class="col-12 col-md-4 mx-auto">
                                    <select name="branch" id="selected-branch" class="form-control">
                                        <option value="" disabled {{ !$null_store ? 'selected' : null }}>&#xf09b; SELECT A STORE</option>
                                        @foreach ($assigned_consignment_store as $store)
                                            <option value="{{ $store }}" {{ $null_store == $store ? 'selected' : null }}>{{ $store }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="beginning-inventory"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('style')
    <style>
        table {
            table-layout: fixed;
            width: 100%;   
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $('#selected-branch').change(function(){
                var branch = $(this).val();
                get_inv_record(branch);
            });

            get_inv_record($('#selected-branch').val());
            function get_inv_record(branch){
                $.ajax({
                    type: 'GET',
                    url: '/beginning_inv_items/' + branch,
                    success: function(response){
                        $('#beginning-inventory').html(response);
                    }
                });
            }
        });
    </script>
@endsection