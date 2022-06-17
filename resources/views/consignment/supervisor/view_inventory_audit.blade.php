@extends('layout', [
    'namePage' => 'Inventory Audit List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
        <div class="container">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div style="margin-bottom: -43px;">
                        <a href="/" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i> </a>
                    </div>
                    <h3 class="text-center font-weight-bold m-2 text-uppercase">Inventory Audit List</h3>
                    <div class="card card-secondary card-outline">
                        <div class="card-body p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a class="nav-link active font-responsive" id="pending-tab" data-toggle="pill" href="#pending-content" role="tab" href="#">Pending for Submission</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link font-responsive" id="inventory-audit-history-tab" data-toggle="pill" href="#inventory-audit-history-content" role="tab" href="#">Inventory Audit History</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="pending-content" role="tabpanel" aria-labelledby="pending-tab">
                                    <div class="p-2">
                                        <h5 class="text-center m-2 d-block font-responsive text-uppercase">Pending for Submission</h5>
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <th class="text-center font-responsive align-middle p-2">Store</th>
                                                <th class="text-center font-responsive align-middle p-2">Promodiser</th>
                                                <th class="text-center font-responsive align-middle p-2">Period</th>
                                                <th class="text-center font-responsive align-middle p-2">Action</th>
                                            </thead>
                                            <tbody>
                                                @forelse ($pending_cutoff_inv_audit_arr as $row)
                                                <tr>
                                                    <td class="text-left font-responsive align-middle p-2">{{ $row['store'] }}</td>
                                                    <td class="text-center font-responsive align-middle p-2">{{ $row['promodiser'] }}</td>
                                                    <td class="text-center font-responsive align-middle p-2">{{ $row['start'] . ' - ' . $row['end'] }}</td>
                                                    <td class="text-center font-responsive align-middle p-2">
                                                        <a href="/view_inventory_audit_form/{{ $row['store'] }}/{{ $row['cutoff_date'] }}" class="btn btn-primary btn-sm" style="width: 70px;"><i class="fas fa-plus"></i></a>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center font-responsive text-uppercase text-muted p-2">No record(s) found</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                        <div class="float-left m-2">Total: <b>{{ $pending_cutoff_inv_audit_arr->total() }}</b></div>
                                        <div class="float-right m-2">{{ $pending_cutoff_inv_audit_arr->links('pagination::bootstrap-4') }}</div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="inventory-audit-history-content" role="tabpanel" aria-labelledby="inventory-audit-history-tab">
                                    <form id="inventory-audit-history-form" method="GET">
                                        <div class="d-flex flex-row align-items-center mt-2">
                                            <div class="p-1 col-6">
                                                <select class="form-control inventory-audit-history-filter" name="store" id="consignment-store-select">
                                                   
                                                </select>
                                            </div>
                                            <div class="p-1 col-2">
                                                <select class="form-control inventory-audit-history-filter" name="year">
                                                    @foreach ($select_year as $year)
                                                    <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="p-1 col-2">
                                                <a href="#" class="btn btn-secondary inventory-audit-history-refresh"><i class="fas fa-sync"></i></a>
                                            </div>
                                        </div>
                                    </form>
                                    <div id="submitted-inventory-audit-el" class="p-2"></div>
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

@section('script')
<script>
    $(function () {
        $(document).on('change', '.inventory-audit-history-filter', function(e) {
            e.preventDefault();
            loadSubmittedInventoryAudit();
        });

        $(document).on('click', '.inventory-audit-history-refresh', function(e) {
            e.preventDefault();
            loadSubmittedInventoryAudit();
        });

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