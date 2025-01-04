@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-2">
				<div class="col-sm-12">
					@if (Auth::user()->user_group == 'Director')
					<ul class="nav nav-pills mb-2 mt-0">
						<li class="nav-item p-0">
							<a class="nav-link active font-responsive text-center" href="/">In House Warehouse Transaction</a>
						</li>
						<li class="nav-item p-0">
							<a class="nav-link font-responsive text-center" href="/consignment_dashboard">Consignment Dashboard</a>
						</li>
					</ul>
					@endif
					<div class="card bg-light">
						<div class="card-body p-0" style="min-height: 900px;">
							<div class="row pt-2 m-0">
								<div class="col-md-12 col-xl-6 col-lg-12">
									<div class="container pr-0 pl-0">
										<div class="row">
											<div class="col-md-12 col-xl-10 col-lg-12 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<h6 class="text-uppercase text-center mb-2 font-italic">Check In Item(s)</h6>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<a href="/returns" class="text-dark">
												<div class="info-box bg-primary p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-undo"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">Sales Returns</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-returns">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/production_to_receive" class="text-dark">
												<div class="info-box bg-info p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="far fa-check-circle"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">Feedback</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-receipt">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<a href="/material_transfer" class="text-dark">
												<div class="info-box bg-gray-dark p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-exchange-alt"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">Internal Transfers</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-transfer">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/in_transit" class="text-dark">
												<div class="info-box bg-purple p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-boxes"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">In Transit</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-in-transit">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
										</div>
									</div>
								</div>	
								<div class="col-md-12 col-xl-6 col-lg-12">
									<div class="container pr-0 pl-0">
										<div class="row">
											<div class="col-md-12 col-xl-10 col-lg-12 pr-4 pl-4">
												<h6 class="text-uppercase text-center mb-2 font-italic">Check Out Item(s)</h6>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/material_transfer_for_manufacture" class="text-dark">
												<div class="info-box bg-olive p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-tasks"></i></span>
														<div class="info-box-content text-truncate d-inline-block pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase"><span class="d-md-inline-block d-lg-none d-xl-inline-block">Production</span> Withdrawals</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-manufacture">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/material_issue" class="text-dark">
												<div class="info-box bg-indigo p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-dolly"></i></span>
													<div class="info-box-content pb-1 pt-1 text-truncate d-inline-block">
														<span class="info-box-text font-weight-bold text-uppercase">Material Issue</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-issue">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/picking_slip" class="text-dark">
												<div class="info-box bg-navy p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-truck"></i></span>
													<div class="info-box-content pt-1 pb-1 text-truncate d-inline-block">
														<span class="info-box-text font-weight-bold text-uppercase">Deliveries</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="picking-slip">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/replacements" class="text-dark">
												<div class="info-box bg-teal p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-retweet"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">Order Replacement</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-replacements">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-10 offset-md-1">
									<div class="row">
										<div class="col-xl-8">
											<div class="card card-danger card-outline">
												<div class="card-header d-flex p-0 justify-content-between">
													<ul class="nav nav-pills p-2">
													  <li class="nav-item"><a class="font-responsive nav-link active" href="#tab_1-1" data-toggle="tab">
														<i class="fas fa-exclamation-triangle"></i> Stock Level Alert</a>
													</li>
													  <li class="nav-item"><a class="font-responsive nav-link" href="#tab_2-1" data-toggle="tab">
														<i class="fas fa-list-alt"></i> Stock Movement(s)</a>
													</li>
													  <li class="nav-item"><a class="font-responsive nav-link purchase-receipt-trigger" href="#tab_3-1" data-toggle="tab">
														<i class="fas fa-list-alt"></i> Recently Received Item(s)</a>
													</li>
													</ul>
													@if (in_array(Auth::user()->user_group, ['Manager', 'Director']))
													<div class="ml-auto p-3">
														<a href="/search_item_cost" class="btn btn-sm btn-secondary">Register Item Cost</a>
													</div>
													@endif
												</div>
												<div class="card-body p-0">
													<div class="tab-content">
														<div class="tab-pane font-responsive active" id="tab_1-1">
															<div id="low-level-stock-table"></div>
														</div>
														
														<div class="tab-pane font-responsive" id="tab_2-1">
															<div id="athena-logs-table" class="p-2">
																<center>
																	<div class="spinner-border" role="status">
																		<span class="sr-only">Loading...</span>
																	</div>
																</center>
															</div>
															<ul class="pagination pagination-month justify-content-center m-2" id="athena-logs-pagination">
																@php
																	$now = Carbon\Carbon::now();

																	$start = (Clone $now)->subMonth(11);

																	$months = [];
																	while($start->lessThanOrEqualTo($now)){
																		$months[] = Clone $start;
																		$start->addMonth();
																	}
																@endphp
																@foreach ($months as $month)
																	<li class="page-item month {{ $month->eq($now) ? 'active' : null }}" data-month="{{ $month->format('Y-m-d') }}">
																		<a class="page-link" href="#">
																			<p class="page-month" style="font-size: 0.9rem;">{{ $month->format('M') }}</p>
																			<p class="page-year" style="font-size: 0.8rem;">{{ $month->format('Y') }}</p>
																		</a>
																	</li>
																@endforeach
															</ul>
														</div>

														<div class="tab-pane font-responsive" id="tab_3-1">
															<div id="purchase-receipt-table" class="overflow-auto"></div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4">
											<div class="row">
												<div class="col-xl-12">
													<div class="card card-info card-outline font-responsive">
														<div class="card-header">
															<h3 class="card-title font-weight-bold">Inventory Accuracy</h3>
															<div class="card-tools">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														<div class="card-body p-2">
															<div class="box">
																<form autocomplete="off">
																	<div class="text-center">Monthly Inventory Accuracy:
																		<select style="width: 15%;" id="monthly-inv-month" class="filter-inv-accuracy">
																			<option value="">-</option>
																			@for($i = 1; $i <= 12; $i++)
																				<option value="{{ $i }}" {{ (int) $now->format('m') == $i ? 'selected' : '' }}>{{ Carbon\Carbon::createFromDate(0, $i, 1)->format('M') }}</option>
																			@endfor
																		</select>
																		<select style="width: 15%;" id="monthly-inv-year" class="filter-inv-accuracy">
																			@for ($start = 2018; $start <= date('Y'); $start++)
																			<option value="{{ $start }}" {{ date('Y') == $start ? 'selected' : '' }}>{{ $start }}</option>
																			@endfor
																		</select>
																	</div>
																</form>
																<table class="table table-bordered mt-2" id="monthly-inv-chart">
																	<col style="width: 30%;">
																	<col style="width: 30%;">
																	<col style="width: 20%;">
																	<col style="width: 20%;">
																	<thead>
																		<tr>
																			<th class="text-center pr-0 pl-0 align-middle">Classification</th>
																			<th class="text-center pr-0 pl-0 align-middle">Warehouse</th>
																			<th class="text-center pr-0 pl-0 align-middle">Accuracy</th>
																			<th class="text-center pr-0 pl-0 align-middle">Target</th>
																		</tr>
																	</thead>
																	<tbody class="item-classification"></tbody>
																</table>
															</div>
														</div>
													</div>
												</div>
												<div class="col-xl-12">
													<div class="card card-success card-outline font-responsive">
														<div class="card-header">
															<h3 class="card-title font-weight-bold">Reserved Items</h3>
															<div class="card-tools">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														{{-- <div class="card-body pt-0 pb-0 pl-1 pr-1" id="recently-added-items-div"></div> --}}
														<div class="card-body pt-0 pb-0 pl-1 pr-1" id="reserved-items-div"></div>
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
			</div>
		</div>
	</div>
</div>

<style>
	/* Extra small devices (phones, 600px and down) */
@media only screen and (max-width: 600px) {
	.custom-font{
	
	}
}

/* Small devices (portrait tablets and large phones, 600px and up) */
@media only screen and (min-width: 600px) {
	.custom-font{
		
	}
}

/* Medium devices (landscape tablets, 768px and up) */
@media only screen and (min-width: 768px) {
	.custom-font{
		font-size: 1.3rem;
	}
}

/* Large devices (laptops/desktops, 992px and up) */
@media only screen and (min-width: 992px) {
	.custom-font{
		font-size: 1.5rem;
	}
}

/* Extra large devices (large laptops and desktops, 1200px and up) */
@media only screen and (min-width: 1200px) {
	.custom-font{
		font-size: 2rem;
	}
}
</style>


@endsection

@section('script')

<script>
	$(document).ready(function(){

		dashboard_data();
			function showNotification(color, message, icon){
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

			function dashboard_data(purpose, div){
				$.ajax({
					type: "GET",
					url: "/dashboard_data",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#p-in-transit').text(data.goods_in_transit);
						$('#p-replacements').text(data.p_replacements);
					}
				});
			}

				count_ste_for_issue('Material Issue', '#material-issue');
			count_ste_for_issue('Material Transfer', '#material-transfer');
			count_ste_for_issue('Material Receipt', '#p-returns');
			count_ste_for_issue('Material Transfer for Manufacture', '#material-manufacture');
			count_ps_for_issue();
			count_production_to_receive();

			function count_ste_for_issue(purpose, div){
				$.ajax({
					type: "GET",
					url: "/count_ste_for_issue/" + purpose,
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$(div).text(data);
					}
				});
			}

			function count_production_to_receive(){
				$.ajax({
					type: "GET",
					url: "/count_production_to_receive",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#material-receipt').text(data);
					}
				});
			}

			function count_ps_for_issue(){
				$.ajax({
					type: "GET",
					url: "/count_ps_for_issue",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#picking-slip').text(data);
					}
				});
			}

		get_low_stock_level_items();
			get_reserved_items();
			function get_low_stock_level_items(page) {
				$.ajax({
					type: "GET",
					url: "/get_low_stock_level_items?page=" + page,
					success: function (data) {
						$('#low-level-stock-table').html(data);
					}
				});
			}

			function get_reserved_items(page) {
				$.ajax({
					type: "GET",
					url: "/get_reserved_items?page=" + page,
					success: function (data) {
						$('#reserved-items-div').html(data);
					}
				});
			}

			get_athena_logs("{{ Carbon\Carbon::now()->format('F d, Y') }}");
			function get_athena_logs(month) {
				$.ajax({
					type: "GET",
					url: "/get_athena_logs?month=" + month,
					success: (data) => {
						$('#athena-logs-table').html(data);
					},
					error: (data) => {
						const error = data.responseJSON
						showNotification('danger', error.message, 'fa fa-info')
					}
				});
			}

			$('#athena-logs-pagination .month').click(function(e){
				e.preventDefault();
				$('#athena-logs-table').html(`
					<center>
						<div class="spinner-border" role="status">
							<span class="sr-only">Loading...</span>
						</div>
					</center>
				`);
				var month = $(this).data('month');

				$('#athena-logs-pagination li.active').removeClass('active');
				$(this).addClass('active');

				set_prev_next_btn_att(month);
				get_athena_logs(month);
			});

			$('#athena-logs-pagination .prev').click(function(e){
				e.preventDefault();

				if(!$(this).hasClass('disabled')){
					var active = $('#athena-logs-pagination li.active');
					active.removeClass('active');
					active.prev().addClass('active');

					var month = $('#athena-logs-pagination li.active').find('.page-link').eq(0).data('month');
					set_prev_next_btn_att(month);
					get_athena_logs(month);
				
				}
			});

			function set_prev_next_btn_att(n){
				$('#athena-logs-pagination .prev').removeClass('disabled');
				$('#athena-logs-pagination .next').removeClass('disabled');

				if(n == 1) {
					$('#athena-logs-pagination .prev').addClass('disabled');
				}

				if(n == {{ now()->month }}) {
					$('#athena-logs-pagination .next').addClass('disabled');
				}
			}

			$('#athena-logs-pagination .next').click(function(e){
				e.preventDefault();

				if(!$(this).hasClass('disabled')){
					var active = $('#athena-logs-pagination li.active');
					active.removeClass('active');
					active.next().addClass('active');

					var month = $('#athena-logs-pagination li.active').find('.page-link').eq(0).data('month');
					set_prev_next_btn_att(month);
					get_athena_logs(month);
				}
			});





		reserved_items();
		function reserved_items(){
				$.ajax({
					type: "GET",
					url: "/get_reserved_items",
					success: function (data) {
						$('#reserved-items-div').html(data);
					}
				});
			}

		$('.filter-inv-accuracy').on('change', function(){
      	monthlyInvAccuracyTbl();
   });


		monthlyInvAccuracyTbl();
   function monthlyInvAccuracyTbl(){
      var year = $('#monthly-inv-year').val();
      var month = $('#monthly-inv-month').val();

      $('#monthly-inv-chart .item-classification').empty();
      $.ajax({
         url: "/inv_accuracy/"+ year,
         method: "GET",
         success: function(data) {
            var row = '';
            $.each(data, function(i, d){
               if (d.month_no == month) {
                  if (d.audit_per_month.length > 0) {

                     $.each(d.audit_per_month, function(i, v){
                        var target = parseFloat(v.percentage_sku);
                        var percentage = parseFloat(v.average_accuracy_rate);
                        stat = (percentage >= v.percentage_sku) ? 'fa-thumbs-up' : 'fa-thumbs-down';
                        color = (percentage >= v.percentage_sku) ? 'green' : 'red';
                        row += '<tr>' +
                           '<td class="inv-accuracy-tbl-item-class">' + v.item_classification + '</td>' +
                           '<td class="inv-accuracy-tbl-item-class">' + v.warehouse + '</td>' + 
                           '<td class="text-center inv-accuracy-tbl-item-class"><i class="fa '+stat+'" style="color:'+color+';"></i> ' + percentage.toFixed(2) + '%</td>' + 
                           '<td class="text-center inv-accuracy-tbl-item-class">' + target.toFixed(2) + '%</td>' +
                           '</tr>';
                     });
                  }else{
                     row += '<tr>' +
                           '<td colspan="4" class="text-center">No Records Found.</td>' +
                           '</tr>';

                  }
               }
            });

            $('#monthly-inv-chart .item-classification').append(row);
         },
         error: function(data) {
            console.log('Error fetching data!');
         }
      });
   }
	});

	$(document).on('click', '.purchase-receipt-trigger', function(){
		recently_received_items();
	});

	function recently_received_items(){
		$.ajax({
			type: "GET",
			url: "/recently_received_items",
			success: function (data) {
				$('#purchase-receipt-table').html(data);
			}
		});
	}
</script>

@endsection