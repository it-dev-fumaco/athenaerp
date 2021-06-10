@extends('layout', [
    'namePage' => 'Production Withdrawals',
    'activePage' => 'material-transfer-for-manufacture',
    'nameDesc' => 'Outgoing'
])

@section('content')

<div class="content" ng-app="myApp" ng-controller="stockCtrl">
  <div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-olive card-outline">
						<div class="card-header p-0 pt-1 border-bottom-0">
							<div class="row">
								<div class="col-xl-1 offset-xl-4">
									<button type="button" class="btn btn-block btn-primary" ng-click="loadData()"><i class="fas fa-sync-alt"></i> Refresh</button>
								</div>
								<div class="col-xl-3">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Search" ng-model="fltr" autofocus>
									</div>
								</div>
								<div class="col-xl-2">
									<div class="form-group">
										<select class="form-control" ng-model="searchText">
											<option></option>
											<option ng-repeat="y in wh">@{{ y.name }}</option>
										</select>
									</div>
								</div>
								<div class="col-xl-2">
									<div class="text-center m-1 d-none" id="total-result-withdrawals">
									   <span class="font-weight-bold">TOTAL RESULT:</span>
									   <span class="badge bg-info" style="font-size: 12pt;">@{{ mtfm_filtered.length }}</span>
									</div>
                  <div class="text-center m-1 d-none" id="total-result-pending">
                    <span class="font-weight-bold">TOTAL RESULT:</span>
                    <span class="badge bg-info" style="font-size: 12pt;">@{{ pmtfm_filtered.length }}</span>
                  </div>
								  </div>
	
							</div>
							<ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist" style="margin-top: -53px;">
                <li class="nav-item">
                  <a class="nav-link active font-weight-bold" id="withdrawals-tab" data-toggle="pill" href="#custom-tabs-three-home" role="tab" aria-controls="custom-tabs-three-home" aria-selected="true">Production Withdrawals</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link font-weight-bold" id="pending-withdrawals-tab" data-toggle="pill" href="#custom-tabs-three-profile" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Pending Item Request for Issue</a>
                </li>
              </ul>
						</div>
						<div class="card-body p-0">
              <div class="tab-content" id="custom-tabs-three-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-three-home" role="tabpanel" aria-labelledby="withdrawals-tab">
                  <div class="row m-0 p-0">
                    <div class="col-md-12 m-0 p-0">
                      <div class="alert m-3 text-center" ng-show="custom_loading_spinner_1">
                        <h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
                      </div>
                      <!-- Material Transfer for Manufacture -->
                      <div class="table-responsive p-0">
                        <table class="table table-hover">
                          <col style="width: 10%;">
                          <col style="width: 12%;">
                          <col style="width: 12%;">
                          <col style="width: 30%;">
                          <col style="width: 10%;">
                          <col style="width: 10%;">
                          <col style="width: 8%;">
                          <col style="width: 8%;">
                          <thead>
                            <tr>
                              <th scope="col" class="text-center">Prod. Order</th>
                              <th scope="col" class="text-center">Source Warehouse</th>
                              <th scope="col" class="text-center">Target Warehouse</th>
                              <th scope="col">Item Description</th>
                              <th scope="col" class="text-center">Qty</th>
                              <th scope="col" class="text-center">Ref. No.</th>
                              <th scope="col" class="text-center">Status</th>
                              <th scope="col" class="text-center">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr ng-repeat="x in mtfm_filtered = (mtfm | filter:searchText | filter: fltr)">
                              <td class="text-center">@{{ x.production_order }}</td>
                              <td class="text-center">@{{ x.s_warehouse }}</td>
                              <td class="text-center">@{{ x.t_warehouse }}</td>
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
                              <td class="text-center">@{{ x.ref_no }}<br><br><span style="font-size: 10pt;">@{{ x.customer }}</span><br><span style="font-size: 10pt;">Delivery Date: @{{ x.delivery_date }}</span><br><span style="font-size: 10pt;">@{{ x.delivery_status }}</span></td>
                              <td class="text-center" ng-if="x.status === 'Issued'"><span class="badge badge-success">@{{ x.status }}</span></td>
                              <td class="text-center" ng-if="x.status === 'For Checking'"><span class="badge badge-warning">@{{ x.status }}</span></td>
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
                <div class="tab-pane fade" id="custom-tabs-three-profile" role="tabpanel" aria-labelledby="pending-withdrawals-tab">
                  <div class="row m-0 p-0">
                    <div class="col-md-12 m-0 p-0">
                      <div class="alert m-3 text-center" ng-show="custom_loading_spinner_2">
                        <h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
                      </div>
                      <div class="table-responsive p-0">
                        <!-- Pending Item Request for Issue -->
                        <table class="table table-hover">
                          <col style="width: 10%;">
                          <col style="width: 20%;">
                          <col style="width: 30%;">
                          <col style="width: 10%;">
                          <col style="width: 10%;">
                          <col style="width: 10%;">
                          <col style="width: 10%;">
                          <thead>
                            <tr>
                              <th scope="col" class="text-center">Prod. Order</th>
                              <th scope="col" class="text-center">Reference</th>
                              <th scope="col" class="text-center">Item Description</th>
                              <th scope="col" class="text-center">Required Qty</th>
                              <th scope="col" class="text-center">Transferred Qty</th>
                              <th scope="col" class="text-center">Pending Qty</th>
                              <th scope="col" class="text-center">Created by</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr ng-repeat="z in pmtfm_filtered = (pmtfm_items | filter:searchText | filter: fltr)" ng-if="z.balance_qty > 0">
                              <td class="text-center align-middle">
                                <span class="d-block font-weight-bold">@{{ z.production_order }}</span>
                                <span style="font-size: 12pt;" class="badge badge-@{{ z.status === 'In Process' ? 'warning' : 'danger' }}">@{{ z.status }}</span>
                              </td>
                              <td class="text-center align-middle">
                                <span class="d-block font-weight-bold">@{{ z.sales_order }}@{{ z.material_request }}</span>
                                <span class="d-block">@{{ z.customer }}</span>
                              </td>
                              <td class="text-justify align-middle">
                                <span class="d-block font-weight-bold">@{{ z.bom_item }}</span>
                                <span class="d-block">@{{ z.description }}</span>
                              </td>
                              <td class="text-center align-middle" style="font-size: 14pt;">@{{ z.required_qty * 1 }}</td>
                              <td class="text-center align-middle" style="font-size: 14pt;">@{{ z.transferred_qty * 1 }}</td>
                              <td class="text-center align-middle" style="font-size: 14pt;">@{{ z.balance_qty * 1 }}</td>
                              <td class="text-center align-middle">
                                <span class="d-block">@{{ z.created_by }}</span>
                                <span class="d-block font-italic">@{{ z.creation }}</span>
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
			</div>
		</div>
	</div>
</div>

@endsection

@section('script')
<script>

  $(document).ready(function(){
    var active_tab = $("ul.nav-tabs li a.active").attr('id');
    if(active_tab == 'withdrawals-tab'){
      $('#total-result-withdrawals').removeClass('d-none');
      $('#total-result-pending').addClass('d-none');
    }else{
      $('#total-result-withdrawals').addClass('d-none');
      $('#total-result-pending').removeClass('d-none');
    }

    $('ul.nav-tabs li a').click(function(){

    if($(this).attr('id') == 'withdrawals-tab'){
      $('#total-result-withdrawals').removeClass('d-none');
      $('#total-result-pending').addClass('d-none');
    }else{
      $('#total-result-withdrawals').addClass('d-none');
      $('#total-result-pending').removeClass('d-none');
    }
  });
});
  var app = angular.module('myApp', []);
  app.controller('stockCtrl', function($scope, $http, $interval, $window, $location) {
    $http.get("/get_parent_warehouses").then(function (response) {
      $scope.wh = response.data.wh;
    });
    
    $scope.loadData = function(){
      $scope.custom_loading_spinner_1 = true;
      $scope.custom_loading_spinner_2 = true;
      $http.get("/material_transfer_for_manufacture?arr=1").then(function (response) {
        $scope.mtfm = response.data.records;
        $scope.custom_loading_spinner_1 = false;
      });

      $http.get("/get_pending_item_request_for_issue").then(function (response) {
        $scope.pmtfm_items = response.data.pending;
        $scope.custom_loading_spinner_2 = false;
      });
    }

    $scope.loadData();
  });
</script>
@endsection