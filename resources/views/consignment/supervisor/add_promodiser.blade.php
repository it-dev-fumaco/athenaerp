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
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/view_promodisers';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-10 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Add a Promodiser</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-header text-center">
                            <span class="font-weight-bolder d-block font-responsive"></span>
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
                            <form action="/add_promodiser_submit" method="post" style="font-size: 10pt;" id="promodiser-form">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <select name="user" id="user-selection" class="form-control selection" required>
                                            <option value="" disabled selected>Select a User</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->name }}">{{ $user->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <input type="text" class="form-control" id="username" value="" placeholder="Username" readonly style="font-size: 10pt;">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control" id="full_name" value="" placeholder="Full Name" readonly style="font-size: 10pt;">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-3">
                                        <div class="form-group">
                                            <input type="checkbox" name="enabled" checked>
                                            <label for="customCheck1">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <input type="checkbox" name="roving">
                                            <label for="customCheck1">Is roving promodiser</label>
                                        </div>
                                    </div>
                                    <div class="col-6 text-right p-2">
                                        <button type="button" id="add-warehouse" class="btn btn-primary btn-xs p-2"><i class="fa fa-plus"></i> Add Warehouse</button> <br>
                                        <small class="error-msg d-none" style="color: red">* Please select a warehouse</small>
                                    </div>
                                    <div class="d-none">
                                        <select class="form-control warehouse-selection selection w-100" id="warehouses-select">
                                            <option value="" disabled selected>Select a warehouse</option>
                                            @foreach ($consignment_stores as $store)
                                                <option value="{{ $store }}">{{ $store }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <table id="assigned-warehouses-table" class="table table-striped" style="font-size: 10pt;">
                                        <col style="width: 80%;">
                                        <col style="width: 20%;">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Warehouse</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="placeholder">
                                                <td class="text-center" colspan="2">
                                                    Please select a warehouse
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <span id='warehouse-count' class="d-none">0</span>
                                </div>
                                <div class="row">
                                    <button type="button" id="submit-btn" class="btn btn-primary w-100">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
<style>
    .error-btn{
        border: 1px solid red;
    }
</style>
@endsection

@section('script')
<script>
    $(document).ready(function(){
        $(document).on('click', '#add-warehouse', function (){
            var clone = $('#warehouses-select').html();

            var row = '<tr>' +
                '<td>' +
                    '<select name="warehouses[]" class="form-control warehouse-selection w-100" required>' + clone + '</select>' +
                '</td>' +
                '<td class="text-center"><button class="btn btn-outline-danger btn-xs remove-row">Remove</button></td>' +
            '</tr>';

            $('#assigned-warehouses-table tbody').append(row)
            $('.selection').select2();
            var warehouse_count = parseInt($('#warehouse-count').text());
            $('#placeholder').remove();
            $('#warehouse-count').text(warehouse_count + 1);
        });

        $(document).on('click', '.remove-row', function (){
            $(this).closest('tr').remove();
            var warehouse_count = parseInt($('#warehouse-count').text());
            $('#warehouse-count').text(warehouse_count - 1);
        });

        $('.selection').select2();

        $(document).on('select2:select', '#user-selection', function(e){
            var username = $('#user-selection').find(":selected").val();
            var name = $('#user-selection').find(":selected").text();
            $('#username').val(username);
            $('#full_name').val(name);
        });

        $(document).on('click', '#submit-btn', function(){
            validate_submit();
        });
        
        function validate_submit(){
            var warehouse_count = parseInt($('#warehouse-count').text());
            if(warehouse_count > 0){
                $('#add-warehouse').removeClass('error-btn');
                $('.error-msg').addClass('d-none');
                var form = $('#promodiser-form');
                var reportValidity = form[0].reportValidity();

                if(reportValidity){
                    form.submit();
                }
            }else{
                $('#add-warehouse').addClass('error-btn');
                $('.error-msg').removeClass('d-none');
            }
        }
    });
</script>
@endsection