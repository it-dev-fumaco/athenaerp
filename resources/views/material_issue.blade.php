@extends('layout', [
    'namePage' => 'Material Issue',
    'activePage' => 'material-issue',
])

@section('content')
<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h2>Material Issue</h2>
				</div>
				<div class="col-sm-1">
					<button type="button" class="btn btn-block btn-primary" ng-click="loadData()"><i class="fas fa-sync-alt"></i> Refresh</button>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						<input type="text" class="form-control" placeholder="Search" ng-model="fltr" autofocus>
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-group">
						<select class="form-control" ng-model="searchText">
							<option></option>
							<option ng-repeat="y in wh">@{{ y.name }}</option>
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-info card-outline">
						<div class="card-header">
							  <h5 class="card-title m-0 font-weight-bold">Material Issue</h5>
							  <div class="card-tools">
								<div class="pull-right">
									<span class="font-weight-bold m-1">TOTAL RESULT:</span>
									<span class="badge bg-info" style="font-size: 14pt;">@{{ mi_filtered.length }}</span>
								</div>
							 </div>
						</div>
						<div class="alert m-3 text-center" ng-show="custom_loading_spinner">
							<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
						  </div>
						<div class="card-body table-responsive p-0">
							<table class="table table-hover">
								<col style="width: 10%;">
								<col style="width: 17%;">
								<col style="width: 32%;">
								<col style="width: 10%;">
								<col style="width: 15%;">
								<col style="width: 8%;">
								<col style="width: 8%;">
								<thead>
									<tr>
										<th scope="col" class="text-center">STE No.</th>
										<th scope="col" class="text-center">Source Warehouse</th>
										<th scope="col" class="text-center">Item Description</th>
										<th scope="col" class="text-center">Qty</th>
										<th scope="col" class="text-center">Ref. No.</th>
										<th scope="col" class="text-center">Status</th>
										<th scope="col" class="text-center">Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr ng-repeat="x in mi_filtered = (mi | filter:searchText | filter: fltr)">
										<td class="text-center">@{{ x.parent }}</td>
										<td class="text-center">@{{ x.s_warehouse }}</td>
										<td class="text-justify">
											<span class="view-item-details font-weight-bold d-block" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
											<span class="d-block">@{{ x.description }}</span>
											<span class="d-block mt-3" ng-hide="x.part_nos == ''"><b>Part No(s):</b> @{{ x.part_nos }}</span>
											<span class="d-block mt-2 font-italic" ng-hide="x.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ x.owner }} - @{{ x.creation }}</span>
										</td>
										<td class="text-center">
											<span class="d-block">@{{ x.qty | number:2 }}</span>
											<span class="d-block mt-3" style="font-size: 10pt;">Available Stock:</span>
											<span class="badge badge-@{{ x.balance > 0 ? 'success' : 'danger' }}">@{{ x.balance | number:2 }}</span>
										</td>
										<td class="text-center">@{{ x.sales_order_no }}</td>
										<td class="text-center" ng-if="x.status === 'Issued'"><span class="badge badge-success">@{{ x.status }}</span></td>
										<td class="text-center" ng-if="x.status === 'For Checking'"><span class="badge badge-warning">@{{ x.status }}</span></td>
										<td class="text-center"><img src="dist/img/icon.png" class="img-circle update-item checkout" data-id="@{{ x.name }}"></td>
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
@endsection

@section('script')
<script>
	var app = angular.module('myApp', []);
	app.controller('stockCtrl', function($scope, $http, $interval, $window, $location) {
		$http.get("/get_parent_warehouses").then(function (response) {
			$scope.wh = response.data.wh;
		});
		
		$scope.loadData = function(){
			$scope.custom_loading_spinner = true;
			$http.get("/material_issue?arr=1").then(function (response) {
				$scope.mi = response.data.records;
				$scope.custom_loading_spinner = false;
			});
		}
		
		$scope.loadData();
	});
</script>
@endsection