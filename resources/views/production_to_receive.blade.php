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
                  <a class="nav-link active font-weight-bold" id="custom-tabs-three-home-tab" data-toggle="pill" href="#custom-tabs-three-1" role="tab" aria-controls="custom-tabs-three-home" aria-selected="true">Production Order(s) - Feedback</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link font-weight-bold" id="custom-tabs-three-profile-tab" data-toggle="pill" href="#custom-tabs-three-2" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Internal Return</a>
				</li>
				<li class="nav-item">
					<a class="nav-link font-weight-bold" id="custom-tabs-three-mrsr-tab" data-toggle="pill" href="#custom-tabs-three-3" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Sales Return</a>
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
								<div class="tab-pane fade" id="custom-tabs-three-3" role="tabpanel" aria-labelledby="custom-tabs-three-mrsr-tab">
									<div class="row m-0 p-0">
									  <div class="col-md-4 offset-md-8 p-1" style="margin-top: -40px;">
										<div class="text-right">
										  <span class="font-weight-bold">TOTAL RESULT:</span>
										  <span class="badge bg-info" style="font-size: 12pt;">@{{ mr_ret_filtered.length }}</span>
										</div>
									  </div>
									  <div class="col-md-12 m-0 p-0">
										<div class="alert m-3 text-center" ng-show="custom_loading_spinner_3">
										  <h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
										</div>
										<div class="table-responsive p-0">
										  <table class="table table-hover">
											<col style="width: 10%;">
											<col style="width: 15%;">
											<col style="width: 33%;">
											<col style="width: 10%;">
											<col style="width: 16%;">
											<col style="width: 8%;">
											<col style="width: 8%;">
											<thead>
											  <tr>
												<th scope="col" class="text-center">STE No.</th>
												<th scope="col" class="text-center">Target Warehouse</th>
												<th scope="col">Item Description</th>
												<th scope="col" class="text-center">Qty</th>
												<th scope="col" class="text-center">Ref. No.</th>
												<th scope="col" class="text-center">Status</th>
												<th scope="col" class="text-center">Actions</th>
											  </tr>
											</thead>
											<tbody>
											  <tr ng-repeat="r in mr_ret_filtered = (mr_ret | filter:searchText | filter: fltr)">
												<td class="text-center">@{{ r.name }}</td>
												<td class="text-center">@{{ r.t_warehouse }}</td>
												<td class="text-justify">
												  <span class="d-block font-weight-bold view-item-details" data-item-code="@{{ x.item_code }}">@{{ r.item_code }}</span>
												  <span class="d-block">@{{ r.description }}</span>
												  <span class="d-block mt-3" ng-hide="r.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ r.owner }}</span>
												</td>
												<td class="text-center">@{{ r.transfer_qty | number:2 }}</td>
												<td class="text-center">
												  <span class="d-block">@{{ r.sales_order_no }}</span>
												  <span style="font-size: 10pt;">@{{ r.so_customer_name }}</span>
												</td>
												<td class="text-center" ng-if="r.status === 'Returned'"><span class="badge badge-success">@{{ r.status }}</span></td>
												<td class="text-center" ng-if="r.status === 'For Checking'"><span class="badge badge-warning">@{{ r.status }}</span></td>
												<td class="text-center">
												  <img src="dist/img/icon.png" class="img-circle checkout update-item-return" data-id="@{{ r.stedname }}">
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

<div class="modal fade" id="update-item-return-modal">
	<form id="update-ste-form" method="POST" action="/checkout_ste_item">
		 @csrf
		 <div class="modal-dialog" style="min-width: 35%;">
			  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title"><span class="parent"></span> <small class="purpose"></small></h4>
						 <button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						 <div class="row">
							  <input type="hidden" value="1" name="is_material_receipt">
							  <input type="hidden" value="-" name="user">
							  <input type="hidden" class="transfer_as" value="For Return" name="transfer_as">
							  <input type="hidden" class="id" name="sted_id">
							  <input type="hidden" class="total_issued_qty" name="balance">
							  <input type="hidden" class="item_code" name="item_code">
							  <div class="col-md-12">
									<div class="box-header with-border">
										 <h4 class="box-title"><span class="t_warehouse_txt"></span></h4>
									</div>
									<div class="box-body" style="font-size: 12pt;">
										 <div class="row">
											  <div class="col-md-6">
													<label>Barcode</label>
													<input type="text" class="form-control barcode" name="barcode" placeholder="Barcode" required>
											  </div>
											  <div class="col-md-6">
													<label>Qty</label>
													<input type="text" class="form-control qty" name="qty" placeholder="Qty">
											  </div>
											  <div class="col-md-12">
													<div class="row">
														 <div class="col-md-5 mt-2">
															  <a class='sample item_image_link' data-height='720' data-lighter='samples/sample-01.jpg' data-width='1280' href="#">
																	<img src="{{ asset('storage/icon/no_img.png') }}" style="width: 100%;" class="item_image">
															  </a>
														 </div>
														 <div class="col-md-7 mt-2">
															  <span class="item_code_txt" style="display: block; font-weight: bold;"></span>
															  <p class="description"></p>
															  <dl>
																	<dt>Return Qty</dt>
																	<dd>
																		 <p class="badge lbl-color" style="font-size: 12pt;">
																			  <span class="total_issued_qty_txt"></span> <span class="stock_uom"></span>
																		 </p>
																	</dd>
															  </dl>
														 </div>
													</div>
											  </div>
											  <div class="col-md-5 mt-2">
													<dl>
														 <dt>Reference No:</dt>
														 <dd class="ref_no"></dd>
														 <dt style="padding-top: 2%;">Status:</dt>
														 <dd class="status"></dd>
													</dl>
											  </div>
											  <div class="col-md-7 mt-2">
													<dl>
														 <dt>Requested by:</dt>
														 <dd class="owner"></dd>
														 <dt style="padding-top: 2%;">Remarks:</dt>
														 <dd>
															  <textarea class="form-control remarks" rows="2" placeholder="Remarks" name="remarks"></textarea>
														 </dd>
													</dl>
											  </div>
										 </div>
									</div>
							  </div>
						 </div>
					</div>
					<div class="modal-footer">
						 <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> RETURN</button>
						 <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
					</div>
			  </div>
		 </div>
	</form>
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

		$(document).on('click', '.update-item-return', function(){
			var id = $(this).data('id');
			$.ajax({
			  type: 'GET',
			  url: '/get_ste_details/' + id,
			  success: function(response){
					$('#update-item-return-modal .parent').text(response.parent);
					$('#update-item-return-modal .purpose').text('Sales Return');
			
					$('#update-item-return-modal .id').val(response.name);
					$('#update-item-return-modal .total_issued_qty').val(response.total_issued_qty);
					$('#update-item-return-modal .item_code').val(response.item_code);
			
					$('#update-item-return-modal .t_warehouse_txt').text(response.t_warehouse);
			
					var barcode_value = '';
					var img = (response.img) ? '/img/' + response.img : '/icon/no_img.png';
					img = "{{ asset('storage/') }}" + img;
			
					$('#update-item-return-modal .item_image').attr('src', img);
					$('#update-item-return-modal .item_image_link').removeAttr('href').attr('href', img);
			
					$('#update-item-return-modal .qty').val(Number(response.qty));
					$('#update-item-return-modal .item_code_txt').text(response.item_code);
					$('#update-item-return-modal .description').text(response.description);
					$('#update-item-return-modal .owner').text(response.owner);
					$('#update-item-return-modal .t_warehouse').val(response.t_warehouse);
					$('#update-item-return-modal .barcode').val(barcode_value);
					$('#update-item-return-modal .ref_no').text(response.ref_no);
					$('#update-item-return-modal .status').text(response.status);
			
					if (response.qty <= 0) {
					$('#update-item-return-modal .lbl-color').addClass('badge-danger').removeClass('badge-success');
					}else{
					$('#update-item-return-modal .lbl-color').addClass('badge-success').removeClass('badge-danger');
					}
			
					$('#update-item-return-modal .total_issued_qty_txt').text(Number(response.qty));
					$('#update-item-return-modal .stock_uom').text(response.stock_uom);
					$('#update-item-return-modal .remarks').text(response.remarks);
	
					$('#update-item-return-modal').modal('show');
				}
			});
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
			$scope.custom_loading_spinner_3 = true;
			$http.get("/production_to_receive?arr=1").then(function (response) {
				$scope.pr = response.data.records;
				$scope.custom_loading_spinner_1 = false;
        
			});

			$http.get("/get_items_for_return").then(function (response) {
				$scope.return_items = response.data.return;
				$scope.custom_loading_spinner_2 = false;
			  });  

			  $http.get("/get_mr_sales_return").then(function (response) {
				$scope.mr_ret = response.data.mr_return;
				$scope.custom_loading_spinner_3 = false;
			  });
		 }
	 
		$scope.loadData();

	 });
</script>
@endsection