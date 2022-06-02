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
                            @if(session()->has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session()->get('success') }}
                                </div>
                            @endif
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <span class="font-responsive">{{ Auth::user()->full_name }}</span>
                        </div>
                        <div class="card-header text-center font-weight-bold">{{ Carbon\Carbon::now()->format('M d, Y') }} - Beginning Inventory</div>
                        <div class="card-body p-1">
                            <div class="row">
                                <div class="col-12 col-md-4 mx-auto">
                                    <select name="branch" id="selected-branch" class="form-control">
                                        <option value="" disabled selected>SELECT A STORE</option>
                                        @foreach ($assigned_consignment_store as $store)
                                            <option value="{{ $store }}">{{ $store }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <form action="/save_beginning_inventory" method="post" class="text-center">
                                @csrf
                                <div id="beginning-inventory"></div>
                            </form>
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
                $.ajax({
                    type: 'GET',
                    url: '/beginning_inv_items/' + branch,
                    success: function(response){
                        $('#beginning-inventory').html(response);
                    }
                });
            });
        });
    </script>
@endsection