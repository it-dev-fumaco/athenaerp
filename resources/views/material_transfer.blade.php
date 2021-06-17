@extends('layout', [
    'namePage' => 'Internal Transfer',
    'activePage' => 'material-transfer',
    'nameDesc' => 'Incoming'
])

@section('content')
<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-primary card-outline">
						<div class="card-header p-0 pt-1 border-bottom-0">
							<div class="row m-1">
								<div class="col-xl-4 d-md-none d-lg-none d-xl-inline-block">
									<h5 class="card-title m-1 font-weight-bold">Internal Transfer</h5>
								</div>
								<div class="col-xl-1 col-lg-2 col-md-2">
									<button type="button" class="btn btn-block btn-primary" ng-click="loadData()"><i class="fas fa-sync-alt"></i> Refresh</button>
								</div>
								<div class="col-xl-3 col-lg-5 col-md-5">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Search" ng-model="fltr" autofocus>
									</div>
								</div>
								<div class="col-xl-2 col-lg-2 col-md-2">
									<div class="form-group">
										<select class="form-control" ng-model="searchText">
											<option></option>
											<option ng-repeat="y in wh">@{{ y.name }}</option>
										</select>
									</div>
								</div>
								<div class="col-xl-2 col-lg-3 col-md-3">
									<div class="text-center m-1">
									   <span class="font-weight-bold">TOTAL RESULT:</span>
									   <span class="badge bg-info" style="font-size: 12pt;">@{{ mt_filtered.length }}</span>
									</div>

								  </div>
	
							</div>

					
						</div>

            <div class="alert m-3 text-center" ng-show="custom_loading_spinner">
							<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
						</div>
            <div class="card-body p-0">
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <col style="width: 17%;">
                    <col style="width: 43%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 10%;">
                    <thead>
                      <tr>
                        <th scope="col" class="text-center">Transaction</th>
                        <th scope="col" class="text-center">Item Description</th>
                        <th scope="col" class="text-center">Qty</th>
                        <th scope="col" class="text-center">Ref. No.</th>
                        <th scope="col" class="text-center">Actions</th>
                      </tr>
                    </thead>
                  <tbody>
                    <tr ng-repeat="x in mt_filtered = (mt | filter:searchText | filter: fltr)">
                        <td class="text-center">
                          <span class="d-block font-weight-bold">@{{ x.creation }}</span>
                          <small class="d-block mt-1">@{{ x.parent }}</small>
                     </td>
                      <td class="text-justify">
                        <div class="d-block font-weight-bold">
                          <span class="view-item-details font-weight-bold" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
                          <span class="badge badge-success mr-2" ng-if="x.status === 'Issued'">@{{ x.status }}</span>
                          <span class="badge badge-warning mr-2" ng-if="x.status === 'For Checking'">@{{ x.status }}</span>
                          <span>@{{ x.s_warehouse }}</span>
                          <i class="fas fa-arrow-right ml-2 mr-2"></i> 
                          <span>@{{ x.t_warehouse }}</span>
                        </div>
                        <span class="d-block">@{{ x.description }}</span>
                        <span class="d-block mt-3" ng-hide="x.part_nos == ''"><b>Part No(s):</b> @{{ x.part_nos }}</span>
                        <span class="d-block mt-2" ng-hide="x.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ x.owner }}</span>
                      </td>
                      <td class="text-center">
                        <span class="d-block" style="font-size: 14pt;">@{{ x.qty | number:2 }}</span>
                        <span class="d-block mt-3" style="font-size: 10pt;">Available Stock:</span>
                        <span class="badge badge-@{{ x.balance > 0 ? 'success' : 'danger' }}">@{{ x.balance | number:2 }}</span>
                      </td>
                      <td class="text-center">
                          @{{ x.sales_order_no }}
                        <span class="d-block">@{{ x.transfer_as }}</span>
                      </td>
                      <td class="text-center">
                        <img src="dist/img/icon.png" class="img-circle update-item checkout" data-id="@{{ x.name }}">
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
    $http.get("/get_parent_warehouses").then(function (response) {
      $scope.wh = response.data.wh;
    });
		
		$scope.loadData = function(){
      $scope.custom_loading_spinner = true;
			$http.get("/material_transfer?arr=1").then(function (response) {
        $scope.mt = response.data.records;
        $scope.custom_loading_spinner = false;
      });
    }
	 
		$scope.loadData();
	 });
</script>
@endsection