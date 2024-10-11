@extends('layout', [
    'namePage' => 'Consignment Order',
    'activePage' => 'beginning_inventory',
])

@section('content')
    <div class="content">
        <div class="content-header p-0">
            <div class="container">
                <div class="row pt-1">
                    <div class="col-md-12 p-0 m-0">
                        <div class="card card-lightblue">
                            <div class="card-header text-center p-2" id="report">
                                <span class="font-responsive font-weight-bold text-uppercase d-inline-block">Consignment Orders</span>
                            </div>
                            <div class="card-body p-0">
                                @if(session()->has('success'))
                                    <div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                        {{ session()->get('success') }}
                                    </div>
                                @endif
                                @if(session()->has('error'))
                                    <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                        {{ session()->get('error') }}
                                    </div>
                                @endif
                                <div class="container-fluid">
                                    @php
                                        $statuses = ['Draft', 'Pending', 'Partially Issued', 'Completed', 'Cancelled'];
                                    @endphp
                                    <div class="row">
                                        <div class="col-12 p-2">
                                            <div class="row">
                                                <div class="col-8 p-2">
                                                    <input type="text" name="search" placeholder="Search..." class="form-control form-control-sm">
                                                </div>
                                                <div class="col-4 p-2">
                                                    <button class="btn btn-sm btn-primary search w-100"><i class="fa fa-search"></i> Search</button>
                                                </div>
                                            </div>
                                            
                                            <div class="row additional-filters" style='display: none'>
                                                <div class="col-8 p-2">
                                                    <select name="branch" class="form-control form-control-sm">
                                                        <option value="" disabled selected>Select a Branch</option>
                                                        @foreach ($assigned_consignment_stores as $store)
                                                            <option value="{{ $store }}">{{ $store }}</option>
                                                        @endforeach 
                                                    </select>
                                                </div>
                                                <div class="col-4 p-2">
                                                    <select name="status" class="form-control form-control-sm">
                                                        <option value="" disabled selected>Status</option>
                                                        @foreach ($statuses as $status)
                                                            <option value="{{ $status }}">{{ $status }}</option>
                                                        @endforeach 
                                                    </select>
                                                </div>
                                            </div>
                                        
                                            <div class="row">
                                                <div class="col-12 p-2">
                                                    <a id="toggle-filters" class="text-primary text-underline" style="font-size: 9pt">Advanced Filters...</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="replenish-tbl" class="col-12">
                                            
                                        </div>
                                    </div>
                                </div>
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
        input[type=number] {
            -moz-appearance: textfield;
        }
        .morectnt span {
            display: none;
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

            const load = (page = 1) => {
                const branch = $('select[name="branch"]').val()
                const status = $('select[name="status"]').val()
                const search = $('input[name="search"]').val()

                $.ajax({
					type: 'GET',
					url: '/consignment/replenish',
                    data: {
                        page, branch, status, search
                    },
					success: (response) => {
						$('#replenish-tbl').html(response);
					},
                    error: (xhr, textStatus, errorThrown) => {
						showNotification("danger", xhr.responseJSON.message, "fa fa-info");
					}
				});
            }

            load()

            $(document).on('click', '#pagination a', function(event){
				event.preventDefault();
				var page = $(this).attr('href').split('page=')[1];
				load(page);
			});

            $(document).on('click', '.search', function (e){
                e.preventDefault()
                load()
            })

            $(document).on('click', '#toggle-filters', function() {
                $('.additional-filters').slideToggle()
            });
        });
    </script>
@endsection