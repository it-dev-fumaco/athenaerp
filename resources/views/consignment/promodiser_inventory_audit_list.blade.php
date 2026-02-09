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
                    <div class="card card-lightblue">
                        @if (count($pending) > 0)
                        <div class="card-header text-center p-2">
                            <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">Inventory Report List</span>
                        </div>
                        <div class="card-body p-1">
                            <div class="p-1">
                                <span class="text-center mt-1 mb-2 d-block font-responsive text-uppercase">Pending for Submission</span>
                                <div class="p-0 mb-2">
                                    <input type="text" class="form-control" id="pending-for-submission-search" placeholder="Search...">
                                </div>
                                <div id="pending-for-submission-container">
                                    @forelse ($pending as $store => $row)
                                        @if(count($row) > 0)
                                            <div class="row p-0 m-0" style="border-bottom: 1px solid #CED4DA;">
                                                <span class="d-none">{{ $store }}</span>
                                                <div class="col-9 test p-0">
                                                    <span class="d-block m-2 font-weight-bold font-responsive text-left">{{ $store }}</span>
                                                </div>
                                                <div class="col-3 p-0 d-flex flex-row justify-content-center align-items-center text-right">
                                                    @php
                                                        $link = isset($row[0]['beginning_inventory_date']) && !$row[0]['beginning_inventory_date'] ? '/beginning_inventory' : '/view_inventory_audit_form/'.$store.'/'.Carbon\Carbon::now()->format('Y-m-d');
                                                    @endphp
                                                    <div class="p-0">
                                                        <a href="{{ $link }}" class="btn btn-primary btn-sm" style="font-size: 10pt;"><i class="fas fa-plus"></i> Create</a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <div class="d-block text-center font-responsive m-0 text-uppercase text-muted border-top border-bottom pb-2 pt-2">No record(s) found</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="card-header text-center p-2 bg-lightblue border-0 rounded-0">
                            <span class="font-weight-bolder d-block text-uppercase" style="font-size: 11pt;">Inventory Report History</span>
                        </div>
                        <div class="card-body p-1">
                            <form id="inventory-audit-history-form" method="GET">
                                <div class="d-flex flex-row align-items-center mt-2">
                                    <div class="p-0 col-8">
                                        <select class="form-control form-control-sm selection inventory-audit-history-filter" name="store">
                                            <option value="">Select Store</option>
                                            @foreach ($assignedConsignmentStores as $assignedStore)
                                            <option value="{{ $assignedStore }}">{{ $assignedStore }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="p-1 col-3">
                                        <select class="form-control form-control-sm selection inventory-audit-history-filter" name="year">
                                            @foreach ($selectYear as $year)
                                            <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="p-0 col-1">
                                        <a href="#" class="btn btn-sm btn-secondary inventory-audit-history-refresh"><i class="fas fa-sync"></i></a>
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
				url: "/submitted_inventory_audit?page=" + page,
                data: $('#inventory-audit-history-form').serialize(),
				success: function (response) {
                    $('#submitted-inventory-audit-el').html(response);
				}
			});
		}

        $('.selection').select2();

        $("#pending-for-submission-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#pending-for-submission-container .row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        $(document).on('click', '#submitted-inventory-audit-list-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadSubmittedInventoryAudit(page);
        });
    });
</script>
@endsection