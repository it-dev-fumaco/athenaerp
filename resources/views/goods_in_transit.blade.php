@extends('layout', [
	'namePage' => 'Items in Transit',
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
											<th scope="col" class="text-center d-none d-xl-table-cell" style="width: 15%">Feedback Date</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 10%">Duration in Transit</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 40%">Item Description</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 15%">Qty</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 10%">Action</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="x in mi_filtered = (mi | filter: fltr)">
											<td class="text-center">
												<div class="row">
													<div class="col-6 col-md-4 col-xl-12">
														<h6 class="font-weight-bold mt-1">@{{ x.reference }}</h6>
														<span class="mt-1">@{{ x.name }}</span>
													</div>
													<div class="col-6 col-md-4 d-flex d-lg-none justify-content-center align-items-center">
														<div>
															<h6 class="font-weight-bold">@{{ x.qty | number:2 }}</h6>
															<span>@{{ x.uom }}</span>
														</div>
													</div>
													<div class="d-none d-md-block d-lg-none col-4">
														<div class="w-25 mx-auto">
															<img src="dist/img/icon.png" class="img-circle w-75 update-item checkout" data-id="@{{ x.sted_name }}" ng-if="x.status === 'For Checking'">
															<img src="dist/img/check.png" class="img-circle w-75" ng-if="x.status === 'Received'">
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
														<span>@{{ x.description }}</span>
													</div>
													<div class="text-left mt-2">
														<div class="row">
															<div class="col-6">
																<span><b>Created by:</b> @{{ x.owner }}</span><br>
																<span><b>Stocks in Transit:</b> <span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty | number:2 }} @{{ x.uom }}</span></span>
															</div>
															<div class="col-6" ng-if="x.status === 'Received'">
																<span><b>Duration in Transit: </b>@{{ x.duration_in_transit + ' Day(s)' }}</span><br>
																<span><b>Date Received: </b>@{{ x.date_confirmed }}</span>
															</div>
														</div>
													</div>
												</div>
											</td>
											<td class="text-center d-none d-xl-table-cell">
												<span class="d-block font-weight-bold">@{{ x.feedback_date }}</span>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<div ng-if="x.status === 'Received'">
													<b style="font-size: 12pt;">@{{ x.duration_in_transit + ' Day(s)' }}</b>
													<br><br>
													<span>Date Received:</span><br>
													<small><b>@{{ x.date_confirmed }}</b></small>
												</div>
											</td>
											<td class="text-justify d-none d-lg-table-cell">
												<div class="d-block font-weight-bold">
													<span class="view-item-details font-weight-bold" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
												</div>
												<span class="d-block">@{{ x.description }}</span>
												<small class="d-block mt-2" ng-hide="x.owner == null"><b>Created by:</b> @{{ x.owner }}</small>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<p><span style="font-size: 14pt;">@{{ x.qty | number:2 }}</span>  <span>@{{ x.uom }}</span></p>
												<span class="d-block mt-2" style="font-size: 10pt;">Stocks in Transit:</span>
												<span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty | number:2 }} @{{ x.uom }}</span>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<img src="dist/img/icon.png" class="img-circle update-item checkout" data-id="@{{ x.sted_name }}" ng-if="x.status === 'For Checking'">
												<img src="dist/img/check.png" class="img-circle" ng-if="x.status === 'Received'">
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

<div class="modal fade" id="ste-modal">
	<form method="POST" action="/receive/in_transit">
		@csrf
		<div class="modal-dialog" style="min-width: 35% !important;"></div>
	</form>
</div>

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
		var id = $(this).data('id');
		$.ajax({
			type: 'GET',
			url: '/get_ste_details/' + id,
			success: (response) => {
				$('#ste-modal').modal('show');
				$('#ste-modal .modal-dialog').html(response);
			},
			error: (e) => {
				showNotification("danger", e.responseJSON.message, "fa fa-info");
			}
		});
	});

	$(document).on('click', '#btn-check-out', function(e){
		e.preventDefault();
		$('#ste-modal form').submit();
	});

	app.controller('stockCtrl', function($scope, $http, $interval, $window, $location) {
		$scope.loadData = () => {
			$scope.custom_loading_spinner = true;
			$http.get("/feedbacked_in_transit?arr=1").then((response) => {
				$scope.mi = response.data.records;
				$scope.custom_loading_spinner = false;
			}).catch((error) => {
				showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
			});
		}
		
		$scope.loadData();

		$('#ste-modal form').validate({
			rules: {
				barcode: {
					required: true,
				},
				qty: {
					required: true,
				},
			},
			messages: {
				barcode: {
					required: "Please enter barcode",
				},
				qty: {
					required: "Please enter quantity",
				},
			},
			errorElement: 'span',
			errorPlacement: function (error, element) {
				error.addClass('invalid-feedback');
				element.closest('.form-group').append(error);
			},
			highlight: function (element, errorClass, validClass) {
				$(element).addClass('is-invalid');
			},
			unhighlight: function (element, errorClass, validClass) {
				$(element).removeClass('is-invalid');
			},
			submitHandler: function(form) {
				$('#btn-check-out').prop('disabled', 'true');
				const sted_id = $('input[name="child_tbl_id"]').val()
				$.ajax({
					type: 'POST',
					url: '/receive/' + sted_id,
					data: $(form).serialize(),
					success: (response) => {
						if (response.success) {
							showNotification("success", response.message, "fa fa-check");
							$scope.loadData();
							$('#ste-modal').modal('hide');
							$('#btn-check-out').removeAttr('disabled');
						}else{
							showNotification("danger", response.message, "fa fa-info");
							$('#btn-check-out').removeAttr('disabled');
						}
					},
					error: (jqXHR, textStatus, errorThrown) => {
						showNotification("danger", errorThrown, "fa fa-info");
						$('#btn-check-out').removeAttr('disabled');
					}
				});
			}
		});
	});
</script>
@endsection