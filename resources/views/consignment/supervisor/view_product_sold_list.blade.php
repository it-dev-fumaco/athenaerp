@extends('layout', [
    'namePage' => 'Sales Report List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content" style='min-height: 90vh'>
	<div class="content-header p-0">
        <div class="col-12 mx-auto">
            <div class="row pt-1">
                <div class="col-md-12 p-0 m-0">
                    <div class="row">
                        <div class="col-2">
                            <div style="margin-bottom: -43px;">
                                <a href="/" class="btn btn-secondary" style="width: 80px;"><i class="fas fa-arrow-left"></i></a>
                            </div>
                        </div>
                        <div class="col-8 col-lg-8 p-0">
                            <h4 class="text-center font-weight-bold m-2 text-uppercase">Sales Report List</h4>
                        </div>
                    </div>
                    <div class="card card-secondary card-outline">
                        <!-- Nav tabs -->
					<ul class="nav nav-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" data-toggle="tab" href="#list">List</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="tab" href="#report">Report</a>
						</li>
					</ul>
				  
					<!-- Tab panes -->
					<div class="tab-content"> 
						<div id="list" class="container-fluid tab-pane active" style="padding: 8px 0 0 0;">
                            <div class="card-body p-2 col-12 col-xl-8 mx-auto">
                                <form id="product-sold-form" method="GET">
                                    <div class="d-flex flex-row align-items-center mt-2">
                                        <div class="p-1 col-6">
                                            <select class="form-control product-sold-filter" name="store" id="consignment-store-select">
                                                <option value="">Select Store</option>
                                            </select>
                                        </div>
                                        <div class="p-1 col-4 col-xl-2">
                                            <select class="form-control product-sold-filter" name="year">
                                                @foreach ($select_year as $year)
                                                    <option value="{{ $year }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="p-1 col-2">
                                            <a href="/view_sales_report" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
                                        </div>
                                    </div>
                                </form>
                                <div id="list-el" class="p-2"></div>
                            </div>
                        </div>
						<div id="report" class="container-fluid tab-pane" style="padding: 8px 0 0 0;">
                            <div class="row">
                                <div id="report-container" class="container-fluid p-3"></div>
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
        $(document).on('change', '.product-sold-filter', function(e) {
            e.preventDefault();
            loadSalesReportList();
        });

        loadSalesReportList();
        function loadSalesReportList(page) {
            $.ajax({
                type: "GET",
                url: "/get_product_sold_list?page=" + page,
                data: $('#product-sold-form').serialize(),
                success: function (data) {
                    $('#list-el').html(data);
                }
            });
        }

        $('#test').click(function(){
            loadSalesReport();
        });

        loadSalesReport();
        function loadSalesReport() {
            $.ajax({
                type: "GET",
                url: "/sales_report",
                success: function (data) {
                    $('#report-container').html(data);
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

        $(document).on('click', '#product-sold-pagination a', function(event){
            event.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            loadSalesReportList(page);
        });
    });
</script>
@endsection