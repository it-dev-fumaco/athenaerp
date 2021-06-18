@extends('layout', [
    'namePage' => 'Sales Returns',
    'activePage' => 'returns',
	'nameDesc' => 'Incoming'
])

@section('content')
@include('modal.sales_return')
<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-primary card-outline">
						<div class="card-header p-0 pt-1 border-bottom-0">
							<div class="row m-1">
								<div class="col-xl-4 d-md-none d-lg-none d-xl-inline-block">
									<h5 class="card-title m-1 font-weight-bold">Sales Returns</h5>
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
									   <span class="badge bg-info" style="font-size: 12pt;">@{{ return_filtered.length + mr_ret_filtered.length}}</span>
									</div>
								</div>
							</div>
						</div>
						<div class="alert m-3 text-center" ng-show="custom_loading_spinner_1">
							<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
						</div>
						<div class="card-body p-0">
							<div class="table-responsive p-0">
								<!-- Items for Return -->
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
										<tr ng-repeat="r in mr_ret_filtered = (mr_ret | filter:searchText | filter: fltr)">
										  	<td class="text-center">
											  	<span class="d-block font-weight-bold">@{{ r.creation }}</span>
											  	<small class="d-block mt-1">@{{ r.name }}</small>
											</td>
										  	<td class="text-justify">
												<div class="d-block font-weight-bold">
													@{{ r.item_code }}
													<span class="badge badge-success" ng-if="r.status === 'Returned'">@{{ r.status }}</span>
													<span class="badge badge-warning" ng-if="r.status === 'For Checking'">@{{ r.status }}</span>
													<i class="fas fa-arrow-right ml-3 mr-2"></i> @{{ r.t_warehouse }}
												</div>
												<span class="d-block">@{{ r.description }}</span>
												<span class="d-block mt-2" ng-hide="r.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ r.owner }}</span>
											</td>
											<td class="text-center" style="font-size: 14pt;">@{{ r.transfer_qty | number:2 }}</td>
											<td class="text-center">
												<span class="d-block">@{{ r.sales_order_no }}</span>
												<span style="font-size: 10pt;">@{{ r.so_customer_name }}</span>
											</td>
											<td class="text-center">
												<img src="dist/img/icon.png" class="img-circle checkout edit-sales-return" data-id="@{{ r.stedname }}">
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
	$(document).ready(function(){
		

		$(document).on('click', '.edit-sales-return', function(){
			var id = $(this).data('id');
			$.ajax({
			  type: 'GET',
			  url: '/get_ste_details/' + id,
			  success: function(response){
					$('#sales-return-modal').modal('show');

					var statuses = ['Received', 'Issued'];
					var badge = (statuses.includes(response.status)) ? 'badge badge-success' : 'badge badge-warning';
					$('#sales-return-modal .sales-return-status').text(response.status).removeClass('badge badge-success badge-warning').addClass(badge);
			
					$('#sales-return-modal input[name="id"]').val(response.name);
					$('#sales-return-modal .target-warehouse-display').text(response.t_warehouse);

					var img = (response.img) ? '/img/' + response.img : '/icon/no_img.png';
					img = "{{ asset('storage/') }}" + img;
			
					$('#sales-return-modal .item_image').attr('src', img);
					$('#sales-return-modal .item_image_link').removeAttr('href').attr('href', img);
					$('#sales-return-modal input[name="returned_qty"]').val(Number(response.qty));
					$('#sales-return-modal .item-code-display').text(response.item_code);
					$('#sales-return-modal .item-description-display').text(response.description);
					$('#sales-return-modal .ref_no').text(response.ref_no);
					
					if (response.qty <= 0) {
						$('#sales-return-modal .lbl-color').addClass('badge-danger').removeClass('badge-success');
					}else{
						$('#sales-return-modal .lbl-color').addClass('badge-success').removeClass('badge-danger');
					}
			
					$('#sales-return-modal .for-return-qty-display').text(Number(response.qty));
					$('#sales-return-modal .stock-uom-display').text(response.stock_uom);
					$('#sales-return-modal .remarks').text(response.remarks);
				}
			});
		});

		$('#sales-return-form').validate({
				rules: {
					barcode: {
						required: true,
					},
				},
				messages: {
					barcode: {
						required: "Please enter barcode",
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
					$.ajax({
						type: 'POST',
						url: $(form).attr('action'),
						data: $(form).serialize(),
						success: function(response){
							if (response.status < 1) {
								showNotification("danger", response.message, "fa fa-info");
							}else{
								showNotification("success", response.message, "fa fa-check");
								$('#sales-return-modal').modal('show');
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							console.log(jqXHR);
							console.log(textStatus);
							console.log(errorThrown);
						}
					});
				}
			});

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


		$.ajaxSetup({
			headers: {
			  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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

			  $http.get("/get_mr_sales_return").then(function (response) {
				$scope.mr_ret = response.data.mr_return;
				$scope.custom_loading_spinner_1 = false;
			  });
		 }
	 
		$scope.loadData();

	 });
</script>
@endsection