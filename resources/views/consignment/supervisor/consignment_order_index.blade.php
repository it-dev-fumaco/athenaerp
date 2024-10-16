@extends('layout', [
    'namePage' => 'Consignment Order List',
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
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Consignment Order(s) List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <form id="filterForm">
                            <div class="card-header text-center">
                                <div class="row">
                                    <div class="col-4">
                                        <input type="text" name="search" placeholder="Search..." class="form-control form-control-sm">
                                    </div>
                                    <div class="col-2 text-left">
                                        <button class="btn btn-primary btn-sm" id="search"><i class="fa fa-search"></i> Search</button>
                                    </div>
                                    <div class="col-3">
                                        <select name="branch" class="form-control form-control-sm filters">
                                            <option value="" selected>Select a Branch</option>
                                            @foreach ($consignmentStores as $store)
                                            <option value="{{ $store }}">{{ $store }}</option>
                                            @endforeach 
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        @php
                                            $statuses = ['Draft', 'For Approval', 'Approved', 'Delivered', 'Cancelled'];
                                        @endphp
                                        <select name="status" class="form-control form-control-sm filters">
                                            <option value="" selected>Status</option>
                                            @foreach ($statuses as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                            @endforeach 
                                        </select>
                                    </div>
                                    <div class="col-1">
                                        <button class="btn btn-secondary btn-sm" id="refresh"><i class="fas fa-undo"></i> Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="card-body p-3">
                            <div id="consignment-orders-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
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

        const load = (page = 1) => {
            const branch = $('select[name="branch"]').val();
            const status = $('select[name="status"]').val();
            const search = $('input[name="search"]').val();

            $.ajax({
                type: 'GET',
                url: '/consignment/replenish',
                data: { page, branch, status, search },
                success: (response) => {
                    $('#consignment-orders-list').html(response);
                },
                error: (xhr, textStatus, errorThrown) => {
                    showNotification("danger", xhr.responseJSON.message, "fa fa-info");
                }
            });
        };

        load();

        // Handle pagination
        $(document).on('click', '#pagination a', function (event) {
            event.preventDefault();
            const page = $(this).attr('href').split('page=')[1];
            load(page);
        });

        $(document).on('click', '#search', function (e) {
            e.preventDefault();
            load();
        });

        $(document).on('click', '#refresh', function (e) {
            e.preventDefault();
            $('#filterForm').trigger("reset");
            load();
        });

        $(document).on('change', '.filters', function (e) {
            e.preventDefault();
            load();
        });

        
    })
</script>

@endsection