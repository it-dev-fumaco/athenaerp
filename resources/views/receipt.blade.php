@extends('layout', [
	'namePage' => 'PO Receipts',
    'activePage' => 'receipts',
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
									<h5 class="card-title m-1 font-weight-bold">PO Receipts</h5>
								</div>
								<div class="col-xl-1 col-lg-2 col-md-2">
									<button type="button" class="btn btn-block btn-primary" ng-click="loadData()">
										<i class="fas fa-sync-alt"></i> Refresh
									</button>
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
								<table class="table table-hover">
									<col style="width: 10%;">
									<col style="width: 17%;">
									<col style="width: 43%;">
									<col style="width: 15%;">
									<col style="width: 15%;">
									<thead>
										<tr>
											<th scope="col" class="text-center">Purchase Receipt</th>
											<th scope="col" class="text-center">Transaction Date</th>
											<th scope="col" class="text-center">Item Description</th>
											<th scope="col" class="text-center">Qty</th>
											<th scope="col" class="text-center">Ref. No.</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="x in mi_filtered = (mi | filter:searchText | filter: fltr)">
											<td class="text-center"><span class="d-block mt-1">@{{ x.parent }}</span></td>
											<td class="text-center">
												<span class="d-block font-weight-bold">@{{ x.creation }}</span>
											</td>
											<td class="text-justify">
												<span class="view-item-details font-weight-bold" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
												<span class="badge badge-success mr-2" ng-if="x.status === 'Received'">@{{ x.status }}</span>
												<span class="badge badge-warning mr-2" ng-if="x.status === 'To Receive'">@{{ x.status }}</span>
							  <i class="fas fa-arrow-right ml-2 mr-2"></i> 
							  <span>@{{ x.warehouse }}</span>
							</div>
							<span class="d-block">@{{ x.description }}</span>
							<span class="d-block mt-3" ng-hide="x.part_nos == ''"><b>Part No(s):</b> @{{ x.part_nos }}</span>
							<span class="d-block mt-2" ng-hide="x.owner == null" style="font-size: 10pt;"><b>Created by:</b> @{{ x.owner }}</span>
											</td>
											<td class="text-center">
												<span class="d-block" style="font-size: 1.15rem;">@{{ x.qty | number:2 }}</span>
												<span class="d-block mt-3" style="font-size: 10pt;">Available Stock:</span>
												<span class="badge badge-@{{ x.balance > 0 ? 'success' : 'danger' }}">@{{ x.balance | number:2 }}</span>
											</td>
											<td class="text-center">@{{ x.ref_no }}</td>
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


<div class="modal fade" id="receive-item-modal">
	<form id="update-ste-forqm" method="POST" action="/update_received_item">
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
							  <input type="hidden" class="requested_qty" name="requested_qty">
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
													<label>Received Qty</label>
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
																	<dt>Actual Qty</dt>
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

$(document).on('click', '.receive-item', function(){
			var id = $(this).data('id');
			$.ajax({
			  type: 'GET',
			  url: '/get_purchase_receipt_details/' + id,
			  success: function(response){
					$('#receive-item-modal .parent').text(response.parent);
					$('#receive-item-modal .purpose').text('Purchase Receipt');
			
					$('#receive-item-modal .id').val(response.name);
					$('#receive-item-modal .total_issued_qty').val(response.total_issued_qty);
					$('#receive-item-modal .item_code').val(response.item_code);
			
					$('#receive-item-modal .t_warehouse_txt').text(response.t_warehouse);
			
					var barcode_value = '';
					var img = (response.img) ? '/img/' + response.img : '/icon/no_img.png';
					img = "{{ asset('storage/') }}" + img;
			
					$('#receive-item-modal .item_image').attr('src', img);
					$('#receive-item-modal .item_image_link').removeAttr('href').attr('href', img);
			
					$('#receive-item-modal .qty').val(Number(response.qty));
					$('#receive-item-modal .item_code_txt').text(response.item_code);
					$('#receive-item-modal .description').text(response.description);
					$('#receive-item-modal .owner').text(response.owner);
					$('#receive-item-modal .t_warehouse').val(response.t_warehouse);
					$('#receive-item-modal .barcode').val(barcode_value);
					$('#receive-item-modal .ref_no').text(response.ref_no);
					$('#receive-item-modal .status').text(response.status);

					$('#receive-item-modal input[name="requested_qty"]').val(response.qty);
			
					if (response.qty <= 0) {
					$('#receive-item-modal .lbl-color').addClass('badge-danger').removeClass('badge-success');
					}else{
					$('#receive-item-modal .lbl-color').addClass('badge-success').removeClass('badge-danger');
					}
			
					$('#receive-item-modal .total_issued_qty_txt').text(Number(response.qty));
					$('#receive-item-modal .stock_uom').text(response.stock_uom);
					$('#receive-item-modal .remarks').text(response.remarks);
	
					$('#receive-item-modal').modal('show');
				}
			});
		});



	var app = angular.module('myApp', []);
	app.controller('stockCtrl', function($scope, $http, $interval, $window, $location) {
		$http.get("/get_parent_warehouses").then(function (response) {
			$scope.wh = response.data.wh;
		});
		
		$scope.loadData = function(){
			$scope.custom_loading_spinner = true;
			$http.get("/receipts?arr=1").then(function (response) {
				$scope.mi = response.data.records;
				$scope.custom_loading_spinner = false;
			});
		}
		
		$scope.loadData();
	});
</script>
@endsection