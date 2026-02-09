@extends('layout', [
    'namePage' => 'Inventory Audit List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container-fluid">
            <div class="row pt-1">
                <div class="col-2 offset-1">
                    <div style="margin-bottom: -43px;">
                        @php
                            $redirecthref = Auth::user()->user_group == 'Director' ? '/consignment_dashboard' : '/';
                        @endphp
                        <a href="{{ $redirecthref }}" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-center font-weight-bold m-2 text-uppercase">Inventory Report List</h4>
                </div>
                @if (Auth::check() && in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
                <div class="col-2">
                    <div class="float-right pt-2">
                        <a href="/consignment_ledger" class="btn btn-info btn-sm">View Stock Movement</a>
                    </div>
                </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-8 offset-md-1">
                    <div class="card card-info card-outline">
                        <div class="card-body p-2">
                            <div class="d-flex flex-row">
                                <div class="p-1 col-3">
                                    <small class="d-block">Recent Period:</small>
                                    <span class="d-block font-weight-bold text-center">{{ $displayedData['recent_period'] }}</span>
                                </div>
                                <div class="p-1 col-3" style="border-left: 10px solid #2E86C1;">
                                    <small class="d-block" style="font-size: 8pt;">Stores Submitted</small>
                                    <h5 class="d-block font-weight-bold m-0">{{ $displayedData['stores_submitted'] }}</h5>
                                </div>
                                <div class="p-1 col-3" style="border-left: 10px solid #E67E22;">
                                    <small class="d-block" style="font-size: 8pt;">Stores Pending</small>
                                    <h5 class="d-block font-weight-bold m-0">{{ $displayedData['stores_pending'] }}</h5>
                                </div>
                                <div class="p-1 col-3" style="border-left: 10px solid #27AE60;">
                                    <small class="d-block">Total Sales</small>
                                    <h4 class="d-block font-weight-bold m-0">{{ $displayedData['total_sales'] }}</h4>
                                </div>
                            </div>
                            <form id="inventory-audit-history-form" method="GET">
                                <div class="d-flex flex-row align-items-center mt-2">
                                    <div class="p-1 col-5">
                                        <select class="form-control inventory-audit-history-filter store form-control-sm" name="store" id="consignment-store-select-history">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                    <div class="p-1 col-3 col-lg-3">
                                        <select class="form-control inventory-audit-history-filter year form-control-sm" name="year">
                                            @foreach ($selectYear as $year)
                                            <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="p-1 col-3 col-lg-3">
                                        <select class="form-control inventory-audit-history-filter promodiser form-control-sm" name="promodiser">
                                            <option value="">Select a Promodiser</option>
                                            @foreach ($promodisers as $promodiser)
                                                <option value="{{ $promodiser }}">{{ $promodiser }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="p-1 col-1">
                                        <a href="#" class="btn btn-secondary inventory-audit-history-refresh btn-sm"><i class="fas fa-undo"></i></a>
                                    </div>
                                </div>
                            </form>
                            <div id="submitted-inventory-audit-el" class="p-1" style="height: 852px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card card-warning card-outline">
                        <div class="card-body p-2">
                            <h6 class="text-center font-weight-bolder text-uppercase">Pending for Submission</h6>
                            <form action="#" id="pending-inventory-audit-filter-form">
                                <div class="row p-1 mt-1 mb-1">
                                    <div class="col-10">
                                        <select class="form-control form-control-sm" name="store" id="consignment-store-select">
                                            <option value="">Select Store</option>
                                        </select>
                                    </div>
                                    <div class="col-2 p-0">
                                        <a href="#" class="btn btn-secondary consignment-store-refresh m-0 btn-sm"><i class="fas fa-undo"></i></a>
                                    </div>
                                </div>
                            </form>
                            <div id="beginning-inventory-list-el" style="height: 850px; overflow-y: auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>

<style>
        .select2{
			width: 100% !important;
			outline: none !important;
		}
		.select2-selection__rendered {
			line-height: 25px !important;
			outline: none !important;
		}
		.select2-container .select2-selection--single {
			height: 31px !important;
			padding-top: 1.5%;
			outline: none !important;
		}
		.select2-selection__arrow {
			height: 36px !important;
		}
</style>
@endsection

@section('script')
<script>
    $(function () {
        $(document).on('change', '.inventory-audit-history-filter', function(e) {
            e.preventDefault();
            loadSubmittedInventoryAudit();
        });

        $(document).on('change', "#consignment-store-select", function(e) {
            e.preventDefault();
            get_pending_inventory_audit();
        });

        $(document).on('click', '.inventory-audit-history-refresh', function(e) {
            e.preventDefault();
            $(".inventory-audit-history-filter.store").empty().trigger('change');
            $(".inventory-audit-history-filter.promodiser").val('').trigger('change');
            $('.inventory-audit-history-filter.year').val('{{ Carbon\Carbon::now()->format("Y") }}').trigger('change');
            loadSubmittedInventoryAudit();
        });

        $(document).on('click', '.consignment-store-refresh', function(e) {
            e.preventDefault();
            $("#consignment-store-select").empty().trigger('change');
        });

        get_pending_inventory_audit();
        function get_pending_inventory_audit(page) {
            $.ajax({
                type: "GET",
                url: "/pending_submission_inventory_audit?page=" + page,
                data: $('#pending-inventory-audit-filter-form').serialize(),
                success: function (data) {
                    $('#beginning-inventory-list-el').html(data);
                }
            });
        }

        loadSubmittedInventoryAudit();
        function loadSubmittedInventoryAudit(page) {
			$.ajax({
				type: "GET",
				url: "/submitted_inventory_audit?page=" + page ,
                data: $('#inventory-audit-history-form').serialize(),
				success: function (response) {
                    $('#submitted-inventory-audit-el').html(response);
				}
			});
		}

        $('#consignment-store-select').select2({
            placeholder: "Select Store",
            allowClear: true,
            ajax: {
                url: '/consignment_stores',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });

        
        $('#consignment-store-select-history').select2({
            placeholder: "Select Store",
            allowClear: true,
            ajax: {
                url: '/consignment_stores',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });

        $(document).on('click', '#inventory-audit-history-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadSubmittedInventoryAudit(page);
        });
    });
</script>
@endsection