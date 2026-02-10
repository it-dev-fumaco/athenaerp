@extends('layout', [
    'namePage' => 'Stock Transfers Report',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-3">
                            <div style="margin-bottom: -43px;">
                                @php
                                    $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                                @endphp
                                <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-7 col-lg-6 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Stock Transfers</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-0">
                            @if(session()->has('error'))
                                <div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">
                                    {{ session()->get('error') }}
                                </div>
                            @endif
                            <div id="supervisor-stock-transfer-report"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="success-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body p-3" style="font-size: 11px;">
                <form class="d-none"></form>
                <div class="d-flex flex-row align-items-center">
                    <div class="col-12 text-center">
                        <center>
                            <p class="text-success text-center mb-0" style="font-size: 4rem;">
                                <i class="fas fa-check-circle"></i>
                            </p>
                        </center>
                        <h6 class="d-block">Stock Entry has been created.</h6>
                        <span class="d-block">Reference Stock Entry: <a class="text-dark font-weight-bold" href="#" id="reference-stock-entry-text"></a></span>
                        <button class="btn btn-secondary btn-sm mt-3" type="button" id="success-modal-btn">&times; Close</button>
                    </div>
                </div>
    
            </div>
        </div>
    </div>
</div>

<style>
    .morectnt span {
        display: none;
    }
    .modal{
        background-color: rgba(0,0,0,0.4);
    }
    table {
        table-layout: fixed;
        width: 100%;   
    }

    @media (max-width: 575.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
  	@media (max-width: 767.98px) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
    }
	@media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
        #second-row{
            width: 30%;
        }
        .select2-container--default .select2-selection--single{
            padding: 5px !important;
            font-size: 10pt !important;
        }
	}
</style>
@endsection

@section('script')
<script>
    (function() {
        function showNotification(color, message, icon) {
            if (typeof $.notify === 'function') {
                $.notify({ icon: icon, message: message }, { type: color, timer: 500, z_index: 1060, placement: { from: 'top', align: 'center' } });
            }
        }
        $(document).on('submit', '.generate-stock-entry-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            $.ajax({
                type: 'POST',
                url: '/generate_stock_transfer_entry',
                data: $form.serialize(),
                success: function(response) {
                    if (response && response.data) {
                        $('#success-modal').modal('show');
                        $('#reference-stock-entry-text').attr('href', response.data.link).text(response.data.stock_entry_name);
                    }
                },
                error: function() {
                    showNotification("danger", 'An error occured. Please contact your system administrator.', "fa fa-info");
                }
            });
        });
        $('#success-modal-btn').on('click', function(e) {
            e.preventDefault();
            $('.modal').modal('hide');
        });
    })();
</script>
@endsection