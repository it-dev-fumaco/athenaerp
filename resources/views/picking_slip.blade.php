@extends('layout', [
	'namePage' => 'Deliveries',
	'activePage' => 'picking-slip',
	'nameDesc' => 'Outgoing'
])

@section('content')
<div class="content" ng-app="myApp" ng-controller="stockCtrl" id="anglrCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-navy card-outline">
						<div class="card-header p-0 pt-1 border-bottom-0">
							<div class="row m-1">
								<div class="col-xl-4 d-md-none d-lg-none d-xl-inline-block">
									<h5 class="card-title m-1 font-weight-bold">Deliveries</h5>
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
											<option value="">All Warehouses</option>
											<option ng-repeat="y in wh">@{{ y.name }}</option>
										</select>
									</div>
								</div>
								<div class="col-xl-2 col-lg-3 col-md-3">
									<div class="text-center m-1">
										<span class="font-weight-bold">TOTAL RESULT:</span>
										<span class="badge bg-info" style="font-size: 12pt;">@{{ ps_filtered.length }}</span>
									</div>
								</div>
							</div>
						</div>

						<div class="alert m-3 text-center" ng-show="custom_loading_spinner_1">
							<h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
						</div>

						<div class="card-body p-0">
							<div class="table-responsive p-0">
								<table class="table table-hover dashboard-table" style="font-size: 10pt;">
									<thead>
										<tr>
											<th class="text-center">Transaction</th>
											<th class="text-center d-lg-none">Details</th>
											<th class="text-center d-none d-lg-table-cell">Item Description</th>
											<th class="text-center d-none d-lg-table-cell">Qty</th>
											<th class="text-center d-none d-lg-table-cell">Delivery Date</th>
											<th class="text-center d-none d-lg-table-cell">Ref. No.</th>
											<th class="text-center d-none d-lg-table-cell">Actions</th>
										</tr>
									</thead>
									<tbody>
										<tr ng-repeat="x in ps_filtered = (ps | filter:searchText | filter: fltr) | limitTo:limit">
											<td class="text-center">
												<span class="d-block font-weight-bold">@{{ x.creation }}</span>
												<small class="d-block mt-1">@{{ x.name }}</small>
												<div class="d-block d-lg-none">
													<img src="dist/img/icon.png" ng-if="x.type == 'picking_slip'" class="img-circle checkout update-ps" data-id="@{{ x.id }}">
													<img src="dist/img/icon.png" ng-if="x.type == 'stock_entry'" class="img-circle update-item checkout" data-id="@{{ x.id }}">
												</div>
											</td>
											<td class="text-justify">
												<a href="/get_item_details/@{{ x.item_code }}" target="_blank" style="color: inherit !important">
													<span class="font-weight-bold">@{{ x.item_code }}</span>
												</a>
												<span class="badge badge-success mr-2" ng-if="x.status === 'Issued'">@{{ x.status }}</span>
												<span class="badge badge-warning mr-2" ng-if="x.status === 'For Checking'">@{{ x.status }}</span>
												<i class="fas fa-arrow-right ml-2 mr-2"></i> 
												<span>@{{ x.warehouse }}</span>
												<span class="d-block">@{{ x.description }}</span>
												<span class="d-none d-lg-block mt-3" ng-if="x.part_nos"><b>Part No(s):</b> @{{ x.part_nos }}</span>
												<small class="d-none d-lg-block mt-2" ng-if="x.owner"><b>Requested by:</b> @{{ x.owner }}</small>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<span style="font-size: 14pt;">@{{ x.qty | number:2 }}</span><br>@{{ x.stock_uom }}
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<span class="badge badge-danger" ng-if="x.delivery_status == 'late'" style="font-size: 10pt;">@{{ x.delivery_date }}</span>
												<span ng-if="!x.delivery_status">@{{ x.delivery_date }}</span>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<span>@{{ x.sales_order }}</span>
												<small class="d-block mt-2">@{{ x.customer }}</small>
												<small class="d-block mt-3">@{{ x.classification }}</small>
											</td>
											<td class="text-center d-none d-lg-table-cell">
												<img src="dist/img/icon.png" ng-if="['picking_slip', 'packed_item'].includes(x.type)" class="img-circle checkout update-ps" data-id="@{{ x.id }}" data-type="@{{ x.type }}">
												<img src="dist/img/icon.png" ng-if="x.type == 'stock_entry'" class="img-circle update-item checkout" data-id="@{{ x.id }}">
											</td>
										</tr>
									</tbody>
								</table>

								<!-- Load More Button -->
								<div class="text-center p-3">
                                    <button id="load-more-btn" class="btn btn-primary" ng-click="loadMore()">Load More</button>
                                    <div id="load-more-spinner" class="spinner-border text-primary d-none" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>

							</div>
						</div>

					</div> <!-- card -->
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modals (No changes) -->
<div class="modal fade" id="ste-modal">
	<form method="POST" action="/submit_transaction">
		@csrf
		<div class="modal-dialog" style="min-width: 35% !important;"></div>
	</form>
</div>

<div class="modal fade" id="ps-modal">
	<form method="POST" action="/checkout_picking_slip">
		@csrf
		<div class="modal-dialog" style="min-width: 35% !important;"></div>
	</form>
</div>

<div class="modal fade" id="cancel-ste-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title">Cancel Issued Item</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span>&times;</span>
            </button>
            </div>
            <div class="modal-body">
                Cancel issued item <span id="c-item-code"></span>?
				<div class="d-none">
					<span id="transaction-name"></span>
					<span id="transaction-reference"></span>
				</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal" data-target="#cancel-ste-modal">Close</button>
                <button type="button" class="btn btn-primary cancel-issued-item">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
	$(document).ready(function(){
		$(document).on('click', '.open-cancel-modal', function (e){
			e.preventDefault();
			$('#c-item-code').text($(this).data('item-code'));
			$('#transaction-name').text($(this).data('name'));
			$('#transaction-reference').text($(this).data('reference'));
			open_modal($(this).data('target'));
		});

		$(document).on('click', '.cancel-issued-item', function (e){
			e.preventDefault();
			$.ajax({
				type: 'GET',
				url: '/cancel_issued_item',
				data: {
					name: $('#transaction-name').text(),
					reference: $('#transaction-reference').text()
				},
				success: (response) => {
					if(response.success){
						showNotification("success", response.message, "fa fa-check");
						angular.element('#anglrCtrl').scope().loadData();
						$('#transaction-name').text('');
						$('#transaction-reference').text('');
						close_modal('#cancel-ste-modal');
						close_modal('#ps-modal');
						close_modal('#ste-modal');
					}else{
						showNotification("danger", response.message, "fa fa-check");
					}
				},
				error: (error) => {
					showNotification("danger", 'An error occured. Please try again', "fa fa-check");
				}
			});
		});

		$(document).on('click', '#btn-deduct-res', function(e){
			e.preventDefault();
			$('#ste-modal input[name="deduct_reserve"]').val(1);
      		$('#ste-modal form').submit();
		});

		$(document).on('click', '#btn-check-out', function(e){
			e.preventDefault();
			$('#ste-modal input[name="deduct_reserve"]').val(0);
      		$('#ste-modal form').submit();
		});

		$(document).on('click', '#btn-deduct-res-1', function(e){
			e.preventDefault();
			$('#ps-modal input[name="deduct_reserve"]').val(1);
      		$('#ps-modal form').submit();
		});

		$(document).on('click', '#btn-check-out-1', function(e){
			e.preventDefault();
			$('#ps-modal input[name="deduct_reserve"]').val(0);
      		$('#ps-modal form').submit();
		});
		
		$(document).on('click', '.update-ps', function(){
			var id = $(this).data('id');
			const type = $(this).data('type')
			$.ajax({
				type: 'GET',
				url: '/get_ps_details/' + id,
				data: { type },
				success: (response) => {
					if(response.error){
						showNotification('danger', response.modal_message, 'fa fa-info')
						return false
					}
					$('#ps-modal').modal('show');
					$('#ps-modal .modal-dialog').html(response);
				},
				error: (error) => {
					showNotification('danger', 'An error occured. Please try again.', 'fa fa-info')
				}
			});
		});

		$.ajaxSetup({
			headers: {
			  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		$(document).on('click', '.update-item', function(){
			var id = $(this).data('id');
			$.ajax({
				type: 'GET',
				url: '/get_ste_details/' + id,
				success: (response) => {
					if(response.error){
						showNotification('danger', 'An error occured. Please try again.', 'fa fa-info')
						return false
					}
					$('#ste-modal').modal('show');
					$('#ste-modal .modal-dialog').html(response);
				},
				error: (error) => {
					showNotification('danger', 'An error occured. Please try again.', 'fa fa-info')
				}
			});
		});

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
				$('#ste-modal form button').prop('disabled', 'true');
				$.ajax({
					type: 'POST',
					url: $(form).attr('action'),
					data: $(form).serialize(),
					success: function(response){
						if (response.status) {
							showNotification("success", response.message, "fa fa-check");
							angular.element('#anglrCtrl').scope().loadData();
							$('#ste-modal').modal('hide');
							$('#ste-modal form button').removeAttr('disabled');
						}else{
							showNotification("danger", response.message, "fa fa-info");
							$('#ste-modal form button').removeAttr('disabled');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						$('#ste-modal form button').removeAttr('disabled');
					}
				});
			}
		});

		$('#ps-modal form').validate({
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
				$('#ps-modal form button').prop('disabled', 'true');
				$.ajax({
					type: 'POST',
					url: $(form).attr('action'),
					data: $(form).serialize(),
					success: function(response){
						if (response.status) {
							showNotification("success", response.message, "fa fa-check");
							angular.element('#anglrCtrl').scope().loadData();
							$('#ps-modal').modal('hide');
							$('#ps-modal form button').removeAttr('disabled');
						}else{
							showNotification("danger", response.message, "fa fa-info");
							$('#ps-modal form button').removeAttr('disabled');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						$('#ps-modal form button').removeAttr('disabled');
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
  	});

    $('#load-more-btn').on('click', function() {
        var $btn = $(this);
        var $spinner = $('#load-more-spinner');

        // Disable the button and show the spinner
        $btn.prop('disabled', true);
        $spinner.removeClass('d-none');

        // Call the AngularJS loadMore function
        angular.element('#anglrCtrl').scope().loadMore();

        // Re-enable the button and hide the spinner after data is loaded
        angular.element('#anglrCtrl').scope().$watch('custom_loading_spinner_1', function(newVal, oldVal) {
            if (!newVal) {
                $btn.prop('disabled', false);
                $spinner.addClass('d-none');
            }
        });
    });

    var app = angular.module('myApp', []);
    app.controller('stockCtrl', function($scope, $http) {
        $scope.ps = [];
        $scope.currentPage = 1;
        $scope.hasMore = true;
        $scope.custom_loading_spinner_1 = false;
		$scope.fltr = "";

        $http.get("/get_parent_warehouses").then(function(response) {
            $scope.wh = response.data.wh;
        });

        $scope.loadData = function(loadMore = false) {
            if (!loadMore) {
                $scope.ps = [];
                $scope.currentPage = 1;
                $scope.hasMore = true;
            }
            $scope.custom_loading_spinner_1 = true;

            $http.get("/view_deliveries?arr=1&page=" + $scope.currentPage + "&search=" + encodeURIComponent($scope.fltr)).then(function(response) {
                if (response.data.picking.length > 0) {
                    $scope.ps = $scope.ps.concat(response.data.picking);
                    $scope.currentPage++;
                    $scope.hasMore = $scope.currentPage <= response.data.pagination.last_page;
                } else {
                    $scope.hasMore = false;
                }
                $scope.custom_loading_spinner_1 = false;
            }).catch(function() {
                $scope.custom_loading_spinner_1 = false;
            });
        };

        $scope.loadMore = function() {
            if ($scope.hasMore) {
                $scope.loadData(true);
            }
        };

		$scope.$watch('fltr', function(newVal, oldVal) {
			if (newVal !== oldVal) {
				$scope.loadData(true);
			}
		});

        $scope.loadData(); // Initial load
    });

</script>
@endsection