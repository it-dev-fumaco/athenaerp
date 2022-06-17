@extends('layout', [
    'namePage' => 'Beginning Inventory',
    'activePage' => 'beginning_inventory',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            @if(session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show font-responsive" role="alert">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Beginning Inventory Entry</span>
                        </div>
                        <div class="card-header text-center font-weight-bold">
                            <h5 class="text-center mt-1 font-weight-bold">
                                {{ \Carbon\Carbon::now()->format('F d, Y') }} <span class="badge badge-success float-right {{ $inv_record ? null : 'd-none' }}">{{ $inv_record ? $inv_record->status : null }}</span>
                            </h5>
                        </div>
                        <div class="card-body p-1">
                            @if (!$inv_record)
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
                            @endif
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
        .select2-selection__rendered {
            line-height: 34px !important;
            text-align: left !important;
        }
        .select2-container .select2-selection--single {
            height: 37px !important;
            text-align: left !important;
        }
        .select2-selection__arrow {
            height: 35px !important;
            text-align: left !important;
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
            
            var selected_branch = '{{ $branch ? $branch : "none" }}';
            selected_branch = selected_branch ? selected_branch : $('#selected-branch').val();

            get_inv_record(selected_branch);
            function get_inv_record(branch){
                var inv_record = '{{ $inv_record ? $inv_record->name : null }}';
                var link = inv_record ? 'update/' + branch + '/{{ $inv }}' : 'new/' + branch;

                $.ajax({
                    type: 'GET',
                    url: '/beginning_inv_items/' + link,
                    success: function(response){
                        $('#beginning-inventory').html(response);
                    }
                });
            }
        });
    </script>
@endsection