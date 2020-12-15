@extends('layout', [
    'namePage' => 'To Receive',
    'activePage' => 'material-receipt',
])

@section('content')


<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h2>Items <small>To Receive</small></h2>
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
						<div class="card-header p-0 pt-1 border-bottom-0">
							<ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="custom-tabs-three-home-tab" data-toggle="pill" href="#custom-tabs-three-1" role="tab" aria-controls="custom-tabs-three-home" aria-selected="true">Production Order(s) - Feedback</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="custom-tabs-three-profile-tab" data-toggle="pill" href="#custom-tabs-three-2" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Items for Return</a>
                </li>
              </ul>
						</div>
						<div class="card-body p-0">
							<div class="tab-content" id="custom-tabs-three-tabContent">
								<div class="tab-pane fade show active" id="custom-tabs-three-1" role="tabpanel" aria-labelledby="custom-tabs-three-home-tab">
									<div class="row m-0 p-0">
										<div class="col-md-4 offset-md-8 p-1" style="margin-top: -40px;">
										  <div class="text-right">
											 <span class="font-weight-bold">TOTAL RESULT:</span>
											 <span class="badge bg-info" style="font-size: 12pt;">@{{ mt_filtered.length }}</span>
										  </div>
										</div>
										<div class="col-md-12 m-0 p-0">
											<div class="alert m-3 text-center" ng-show="custom_loading_spinner_1">
												<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
											  </div>
											<div class="table-responsive p-0">
												<!-- Production Order to Receive -->
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
															<th scope="col" class="text-center">Production Order</th>
															<th scope="col" class="text-center">Source</th>
															<th scope="col" class="text-center">Target Warehouse</th>
															<th scope="col">Item Description</th>
															<th scope="col" class="text-center">Qty</th>
															<th scope="col" class="text-center">Ref. No.</th>
															<th scope="col" class="text-center">Status</th>
															<th scope="col" class="text-center">Actions</th>
														</tr>
													</thead>
													<tbody>
														<tr ng-repeat="x in mt_filtered = (pr | filter:searchText | filter: fltr)">
															<td class="text-center">
																<span class="production-order">@{{ x.production_order }}</span>
															</td>
															<td class="text-center">
																<span>@{{ x.operation_name }}</span>
															</td>
															<td class="text-center">
																<span class="target-warehouse">@{{ x.fg_warehouse }}</span>
															</td>
															<td class="text-justify">
																<span class="view-item-details item-code" data-item-code="@{{ x.item_code }}"><b>@{{ x.item_code }}</b></span>
																<span class="description" style="display: block;">@{{ x.description }}</span>
																<br>
																<span style="display: block; font-size: 10pt;"><b>Created by:</b> @{{ x.owner }} - @{{ x.created_at }}</span>
															</td>
															<td class="text-center">
																<span class="qty">@{{ x.qty_to_receive }}</span>
															</td>
															<td class="text-center">
																<span class="reference-no">@{{ x.sales_order_no }}@{{ x.material_request }}</span>
																<br>
																<span class="customer">@{{ x.customer }}</span>
															</td>
															<td class="text-center"><span class="badge badge-warning">To Receive</span></td>
															<td class="text-center">
																<img src="dist/img/check.png" class="img-circle receive-item checkout" data-ste="@{{ x.ste_no }}">
															</td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
								<div class="tab-pane fade" id="custom-tabs-three-2" role="tabpanel" aria-labelledby="custom-tabs-three-profile-tab">
									<div class="row m-0 p-0">
										<div class="col-md-4 offset-md-8 p-1" style="margin-top: -40px;">
										  <div class="text-right">
											 <span class="font-weight-bold">TOTAL RESULT:</span>
											 <span class="badge bg-info" style="font-size: 12pt;">@{{ return_filtered.length }}</span>
										  </div>
										</div>
										<div class="col-md-12 m-0 p-0">
											<div class="alert m-3 text-center" ng-show="custom_loading_spinner_2">
												<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
											  </div>
											<div class="table-responsive p-0">
												<!-- Items for Return -->
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
															<th scope="col" class="text-center">STE No.</th>
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
														<tr ng-repeat="y in return_filtered = (return_items | filter:searchText | filter: fltr)">
														<td class="text-center">@{{ y.parent }}</td>
														<td class="text-center">@{{ y.s_warehouse }}</td>
														<td class="text-center">@{{ y.t_warehouse }}</td>
														<td class="text-justify">
															<span class="d-block font-weight-bold">@{{ y.item_code }}</span>
															<span class="d-block">@{{ y.description }}</span>
															<span class="d-block mt-3" ng-hide="y.part_nos == ''"><b>Part No(s):</b> @{{ y.part_nos }}</span>
															<span class="d-block mt-2" ng-hide="y.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ y.owner }}</span>
														</td>
														<td class="text-center">@{{ y.qty * 1 }}</td>
														<td class="text-center">@{{ y.sales_order_no }}</td>
														<td class="text-center" ng-if="y.status === 'Returned'"><span class="badge badge-primary">@{{ y.status }}</span></td>
														<td class="text-center" ng-if="y.status === 'Issued'"><span class="badge badge-success">@{{ y.status }}</span></td>
														<td class="text-center" ng-if="y.status === 'For Checking'"><span class="badge badge-warning">@{{ y.status }}</span></td>
														<td class="text-center">
															<img src="dist/img/icon.png" class="img-circle update-item checkout" data-id="@{{ y.name }}">
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




<div class="modal fade" id="receive-item-modal">
	<form id="submit-receive-form" method="POST" action="#">
		@csrf
		<input type="hidden" name="production_order">
		<input type="hidden" name="ste_no">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Production Order</h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div class="box-body" style="font-size: 12pt;">
								<div class="row">
									<div class="col-xs-6">
										<label>Received Qty</label>
										<input type="text" class="form-control qty" name="qty" placeholder="Qty">
									</div>
									<div class="col-xs-6">
										<label>Target Warehouse</label>
										<input type="text" class="form-control target-warehouse" placeholder="Target Warehouse" readonly>
									</div>
									<div class="col-xs-12">
										<div class="row">
											<div class="col-xs-12" style="margin-top: 2%;">
												<span class="item-code" style="display: block; font-weight: bold;"></span>
												<p class="description" style="text-align: justify;"></p>
											</div>
											<div class="col-xs-4" style="margin-top: 2%;">
												<dl>
													<dt>Reference No</dt>
													<dd class="reference-no"></dd>
												</dl>
											</div>
											<div class="col-xs-8" style="margin-top: 2%;">
												<dl>
													<dt>Customer</dt>
													<dd class="customer"></dd>
												</dl>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> SUBMIT</button>
					<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
				</div>
			</div>
		</div>
	</form>
</div>


<div class="modal fade" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="Notif Modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Modal title</h4>
			</div>
			<form></form>
			<div class="modal-body">
				<p style="font-size: 12pt; text-align: center;"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

@endsection

@section('script')

<script>
	$(document).ready(function(){
		$.ajaxSetup({
			headers: {
			  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		$(document).on('click', '.receive-item', function(e){
			e.preventDefault();
			var $row = $(this).closest('tr');
	
			var production_order = $row.find('.production-order').text();
			var target_warehouse = $row.find('.target-warehouse').text();
			var item_code = $row.find('.item-code').text();
			var description = $row.find('.description').text();
			var qty = $row.find('.qty').text();
			var reference_no = $row.find('.reference-no').text();
			var customer = $row.find('.customer').text();

			$('#receive-item-modal input[name="production_order"]').val(production_order);
			$('#receive-item-modal input[name="ste_no"]').val($(this).data('ste'));
			$('#receive-item-modal .modal-title').text(production_order);
			$('#receive-item-modal .qty').val(qty);
			$('#receive-item-modal .target-warehouse').val(target_warehouse);
			$('#receive-item-modal .item-code').text(item_code);
			$('#receive-item-modal .description').text(description);
			$('#receive-item-modal .reference-no').text(reference_no);
			$('#receive-item-modal .customer').text(customer);
	
			$('#receive-item-modal').modal('show');
		});
		
		$('#submit-receive-form').submit(function(e){
			e.preventDefault();

			$.ajax({
				url: '/update_stock_entry',
				type: "POST",
				data: $(this).serialize(),
				success: function(response){
					console.log(response);
					$('#modal-notification').modal('show'); 
					$('#modal-notification .modal-title').html(response.modal_title);
					$('#modal-notification p').html(response.modal_message);
				}
			});
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
			$http.get("/production_to_receive?arr=1").then(function (response) {
				$scope.pr = response.data.records;
				$scope.custom_loading_spinner_1 = false;
        
			});

			$http.get("/get_items_for_return").then(function (response) {
				$scope.return_items = response.data.return;
				$scope.custom_loading_spinner_2 = false;
			  });  
		 }
	 
		$scope.loadData();

	 });
</script>
@endsection