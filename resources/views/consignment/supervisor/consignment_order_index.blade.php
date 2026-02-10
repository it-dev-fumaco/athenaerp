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
                        <div class="card-body p-3">
                            <div id="consignment-orders-supervisor" data-stores='@json($consignmentStores)' data-statuses='@json(["Draft", "For Approval", "Approved", "Delivered", "Cancelled"])'></div>
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
        };

        let success_notification = '{{ session()->has("success") ? session()->get("success") : null }}'
        let error_notification = '{{ session()->has("error") ? session()->get("error") : null }}'

        if(success_notification){
            showNotification("success", success_notification, "fa fa-info");
        }

        if(error_notification){
            showNotification("danger", error_notification, "fa fa-info");
        }
    })
</script>
@endsection