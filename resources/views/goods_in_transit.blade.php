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
								<table class="table table-hover dashboard-table" style="font-size: 10pt;">
									<thead>
										<tr>
											<th scope="col" class="text-center w-100 d-lg-none">Details</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 15%">Reference Number</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 15%">Feedback Date</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 55%">Item Description</th>
											<th scope="col" class="text-center d-none d-lg-table-cell" style="width: 15%">Qty</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="x in mi_filtered = (mi | filter:searchText | filter: fltr)">
											<td class="text-center">
												<div class="row">
													<div class="col-6 col-lg-12">
														<h6 class="font-weight-bold mt-1">@{{ x.reference }}</h6>
														<span class="mt-1">@{{ x.name }}</span>
													</div>
													<div class="col-6 d-flex d-lg-none justify-content-center align-items-center">
														<div>
															<h6 class="font-weight-bold">@{{ x.qty | number:2 }}</h6>
															<span>@{{ x.uom }}</span>
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
														<p><b>Created by:</b> @{{ x.owner }}</p>
												

														<p><b>Available Stock:</b> <span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty | number:2 }} @{{ x.uom }}</span></p>
													</div>
												</div>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<span class="d-block font-weight-bold">@{{ x.feedback_date }}</span>
											</td>
											<td class="text-justify d-none d-lg-table-cell">
												<div class="d-block font-weight-bold">
													<span class="view-item-details font-weight-bold" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
												</div>
												<span class="d-block">@{{ x.description }}</span>
												<small class="d-block mt-2" ng-hide="x.owner == null"><b>Created by:</b> @{{ x.owner }}</small>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<span class="d-block" style="font-size: 14pt;">@{{ x.qty | number:2 }}</span>
												<span class="d-block mt-3" style="font-size: 10pt;">Available Stock:</span>
												<span class="badge badge-@{{ x.available_qty > 0 ? 'success' : 'danger' }}">@{{ x.available_qty | number:2 }} @{{ x.uom }}</span>
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
@endsection

@section('script')
<script>
	var app = angular.module('myApp', []);
	app.controller('stockCtrl', function($scope, $http, $interval, $window, $location) {
		$scope.loadData = function(){
			$scope.custom_loading_spinner = true;
			$http.get("/feedbacked_in_transit?arr=1").then(function (response) {
				$scope.mi = response.data.records;
				$scope.custom_loading_spinner = false;
			});
		}
		
		$scope.loadData();
	});
</script>
@endsection