@extends('layout', [
	'namePage' => 'In Transit',
    'activePage' => 'goods_in_transit',
	'nameDesc' => 'Feedbacked'
])

@section('content')
<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-purple card-outline">
						<div class="card-header p-0 pt-1 border-bottom-0">
							<div class="row m-1">
								<div class="order-1 col-6 col-lg-4">
									<h5 class="card-title m-1 font-weight-bold">In Transit</h5>
								</div>
								<div class="order-4 order-lg-3 order-xl-2 col-3 col-lg-1">
									<button type="button" class="btn btn-block btn-primary" ng-click="loadData()">
										<i class="fas fa-sync-alt"></i> <span class="d-none d-md-inline d-lg-none d-xl-inline">Refresh</span>
									</button>
								</div>
								<div class="order-3 order-lg-2 order-xl-3 col-9 col-lg-4">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Search" ng-model="fltr" autofocus>
									</div>
								</div>
								<div class="order-2 order-lg-4 order-xl-4 col-6 col-lg-3">
									<div class="text-center m-1">
									   <span class="font-weight-bold">TOTAL RESULT:</span>
									   <span class="badge bg-info" style="font-size: 12pt;">@{{ mi_filtered.length }}</span>
									</div>
								</div>
							</div>
						</div>
						<div class="alert m-3 text-center" ng-show="custom_loading_spinner">
							<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive p-0">
								<table class="table table-hover dashboard-table font-responsive" style="font-size: 10pt;">
									<thead>
										<tr>
											<th scope="col" class="text-center w-100 d-lg-none">Details</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 10%">Reference Number</th>
											<th scope="col" class="text-center d-none d-xl-table-cell" style="width: 15%">Date Feedbacked</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 10%">Inventory Ageing</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 40%">Item Description</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 15%">Qty</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 10%">Action</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="x in mi_filtered = (mi | filter: fltr)">
											<td class="text-center">
												<div class="row">
													<div class="col-6 col-md-4 col-lg-12">
														<h6 class="font-weight-bold mt-1">@{{ x.reference }}</h6>
														<span class="mt-1">@{{ x.name }}</span> <br>
														<small class="mt-1">@{{ x.customer }}</small>
													</div>
													<div class="col-6 col-md-4 d-flex d-lg-none justify-content-center align-items-center">
														<div>
															<h6 class="font-weight-bold">@{{ x.qty}}</h6>
															<span>@{{ x.uom }}</span>
														</div>
													</div>
													<div class="d-none d-md-block d-lg-none col-4">
														<div class="w-25 mx-auto">
															<img src="dist/img/icon.png" class="img-circle w-75 update-item checkout" data-id="@{{ x.sted_name }}" ng-if="x.status === 'For Checking'">
															<img src="dist/img/check.png" class="img-circle w-75" ng-if="x.status === 'Received'">
														</div>
														<div class="w-100" ng-if="x.status === 'Issued'">
															<span class="font-weight-bold" style="font-size: 12pt;">@{{ x.reference_to_fg }}</span> <br>
															<span class="badge badge-warning">To Receive</span>
														</div>
													</div>
												</div>
												
												<div class="d-block d-lg-none mt-2">
													<div class="text-left font-weight-bold">
														<a href="/get_item_details/@{{ x.item_code }}" target="_blank" style="color: inherit !important">
															@{{ x.item_code }}
														</a>
													</div>
													<div class="text-justify">
														<span ng-bind-html="trustAsHtml(x.description)"></span>
													</div>
													<div class="text-left mt-2">
														<div class="row">
															{{-- <div class="col-6">
																<span><b>Created by:</b> @{{ x.owner }}</span><br>
																<span><b>Stocks in Transit:</b> <span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty | number:2 }} @{{ x.uom }}</span></span>
															</div> --}}
															<div class="col-6" ng-if="x.status === 'Received'">
																<span><b>Inventory Ageing: </b>@{{ x.duration_in_transit + ' Day(s)' }}</span><br>
																<span><b>Date Received: </b>@{{ x.date_confirmed }}</span>
																<span class="d-block">@{{ x.received_by }}</span>
															</div>
														</div>
													</div>
												</div>
											</td>
											<td class="text-center d-none d-xl-table-cell">
												<span class="d-block font-weight-bold">@{{ x.feedback_date }}</span>
												<span class="d-block" style="font-size: 8pt;">@{{ x.feedback_by }}</span>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<div ng-if="['Received', 'Issued'].includes(x.status)">
													<b style="font-size: 12pt;">@{{ x.duration_in_transit }}</b>
													<br><br>
													<span>Date Received:</span><br>
													<span style="font-size: 7pt"><b>@{{ x.date_confirmed }}</b></span>
													<small class="d-block text-muted">@{{ x.received_by }}</small>
												</div>
											</td>
											<td class="text-justify d-none d-lg-table-cell">
												<div class="d-block font-weight-bold">
													<span class="view-item-details font-weight-bold" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span> 
													<span class="badge badge-warning" style="font-size: 8pt;" ng-if="x.status === 'For Checking'">For Checking</span>
													<span class="badge badge-warning" style="font-size: 8pt;" ng-if="x.status === 'Pending to Receive'">Pending to Receive</span>
													<span class="badge badge-success" style="font-size: 8pt;" ng-if="x.status === 'Received'">Received</span>
													<span class="badge badge-info" style="font-size: 8pt;" ng-if="x.status === 'Issued'">Pending Transfer Request</span>
												</div>
												<span class="d-block" ng-bind-html="trustAsHtml(x.description)"></span>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<p><span style="font-size: 14pt;">@{{ x.qty }}</span> <span>@{{ x.uom }}</span></p>
												{{-- <span class="d-block mt-2" style="font-size: 10pt;">Stocks in Transit:</span>
												<span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty }} @{{ x.uom }}</span> --}}
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<img src="dist/img/check.png" class="img-circle update-item checkout" data-id="@{{ x.sted_name }}" data-ref-no="@{{ x.soi_name }}" ng-if="x.status === 'For Checking'">
												<img src="dist/img/check.png" class="img-circle update-item checkout" data-id="@{{ x.sted_name }}" data-ref-no="@{{ x.soi_name }}" ng-if="x.status === 'Pending to Receive'">
												<img src="dist/img/icon.png" class="img-circle update-item checkout" data-id="@{{ x.sted_name }}" data-ref-no="@{{ x.soi_name }}" ng-if="x.status === 'Received'">
												<div ng-if="x.status === 'Issued'">
													<span class="font-weight-bold" style="font-size: 12pt;">@{{ x.reference_to_fg }}</span> <br>
													<span class="badge badge-secondary" style="font-size: 8pt;">DRAFT</span>
												</div>
											</td>
										</tr>
										<tr ng-hide="mi.length">
											<td colspan=7 class="text-center">
												<p class="p-2">No result(s) found.</p>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="ste-modal"></div>

<style>
	@media (max-width: 1199.98px) or
	(max-width: 767.98px) or
	(max-width: 575.98px) or
	(max-width: 369.98px){
		.font-responsive{
			font-size: 9pt !important
		}
	}
</style>

@endsection

@section('script')
<script>
	var app = angular.module('myApp', []);
	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});

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

	$(document).on('click', '.update-item', function(){
		const btn = $(this)
		$.ajax({
			type: 'GET',
			url: '/get_ste_details/' + btn.data('id'),
			success: (response) => {
				$('#ste-modal').html(response);
				$('#ref-no').val(btn.data('ref-no'))
				$('#ste-modal').modal('show');
			},
			error: (e) => {
				showNotification("danger", e.responseJSON.message, "fa fa-info");
			}
		});
	});

	$(document).on('click', '#btn-check-out', function(e){
		e.preventDefault();

		var form = $('#ste-modal form');
		var reportValidity = form[0].reportValidity();

		if(reportValidity){
			$('#ste-modal form').submit();
		}
	});

	app.controller('stockCtrl', function($scope, $http, $interval, $window, $location, $sce) {
		$scope.trustAsHtml = function(html) { return html ? $sce.trustAsHtml(html) : ''; };
		$scope.loadData = () => {
			$scope.custom_loading_spinner = true;
			$http.get("/in_transit?arr=1").then((response) => {
				$scope.mi = response.data.records;
				$scope.custom_loading_spinner = false;
			}).catch((error) => {
				showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
			});
		}
		
		$scope.loadData();

		$(document).on('submit', '#ste-modal form', function (e){
			e.preventDefault()
			const form = $(this)
			$.ajax({
				type: 'POST',
				url: form.attr('action'),
				data: form.serialize(),
				success: (response) => {
					if (response.success) {
						showNotification("success", response.message, "fa fa-check")
						$scope.loadData()
						$('#ste-modal').modal('hide')
					}else{
						showNotification("danger", response.message, "fa fa-info")
					}

					if('email_sent' in repsonse && !response.email_sent){
						showNotification("danger", 'Email not sent!', "fa fa-info")
					}

					$('#btn-check-out').removeAttr('disabled')
				},
				error: (jqXHR, textStatus, errorThrown) => {
					showNotification("danger", errorThrown, "fa fa-info")
					$('#btn-check-out').removeAttr('disabled')
				}
			});
		})
	});
</script>
@endsection