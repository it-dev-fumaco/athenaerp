@extends('layout', [
  'namePage' => 'Picking Slip',
  'activePage' => 'picking-slip',
])

@section('content')

<div class="content" ng-app="myApp" ng-controller="stockCtrl">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h2>Picking Slip</h2>
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
              <option selected></option>
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
                  <a class="nav-link active font-weight-bold" id="custom-tabs-three-home-tab" data-toggle="pill" href="#custom-tabs-three-1" role="tab" aria-controls="custom-tabs-three-home" aria-selected="true">Picking Slip</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link font-weight-bold" id="custom-tabs-three-profile-tab" data-toggle="pill" href="#custom-tabs-three-2" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Return</a>
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
                        <span class="badge bg-info" style="font-size: 12pt;">@{{ ps_filtered.length }}</span>
                      </div>
                    </div>
                    <div class="col-md-12 m-0 p-0">
                      <div class="alert m-3 text-center" ng-show="custom_loading_spinner_1">
                        <h5 class="m-0"><i class="fas fa-sync-alt fa-spin"></i> <span class="ml-2">Loading ...</span></h5>
                      </div>
                      <!-- Picking Slip -->
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
                              <th scope="col" class="text-center">PS No.</th>
                              <th scope="col" class="text-center">Source Warehouse</th>
                              <th scope="col">Item Description</th>
                              <th scope="col" class="text-center">Qty</th>
                              <th scope="col" class="text-center">Ref. No.</th>
                              <th scope="col" class="text-center">Status</th>
                              <th scope="col" class="text-center">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr ng-repeat="x in ps_filtered = (ps | filter:searchText | filter: fltr)">
                              <td class="text-center">@{{ x.name }}</td>
                              <td class="text-center">@{{ x.warehouse }}</td>
                              <td class="text-justify">
                                <span class="d-block font-weight-bold view-item-details" data-item-code="@{{ x.item_code }}">@{{ x.item_code }}</span>
                                <span class="d-block">@{{ x.description }}</span>
                                <span class="d-block mt-3 font-italic" ng-hide="x.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ x.owner }} - @{{ x.creation }}</span>
                              </td>
                              <td class="text-center">@{{ x.qty | number:2 }}</td>
                              <td class="text-center">
                                <span class="d-block">@{{ x.delivery_note }}</span>
                                <span class="d-block">@{{ x.sales_order }}</span>
                                <span class="mt-3" style="font-size: 10pt;">@{{ x.customer }}</span>
                              </td>
                              <td class="text-center" ng-if="x.status === 'Issued'"><span class="badge badge-success">@{{ x.status }}</span></td>
                              <td class="text-center" ng-if="x.status === 'For Checking'"><span class="badge badge-warning">@{{ x.status }}</span></td>
                              <td class="text-center">
                                <img src="dist/img/icon.png" class="img-circle checkout update-ps"  data-id="@{{ x.id }}">
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
                        <span class="badge bg-info" style="font-size: 12pt;">@{{ ret_filtered.length }}</span>
                      </div>
                    </div>
                    <div class="col-md-12 m-0 p-0">
                      <div class="alert m-3 text-center" ng-show="custom_loading_spinner_2">
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
                              <th scope="col" class="text-center">DR No.</th>
                              <th scope="col" class="text-center">Target Warehouse</th>
                              <th scope="col">Item Description</th>
                              <th scope="col" class="text-center">Qty</th>
                              <th scope="col" class="text-center">Ref. No.</th>
                              <th scope="col" class="text-center">Status</th>
                              <th scope="col" class="text-center">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr ng-repeat="r in ret_filtered = (ret | filter:searchText | filter: fltr)">
                              <td class="text-center">@{{ r.name }}</td>
                              <td class="text-center">@{{ r.warehouse }}</td>
                              <td class="text-justify">
                                <span class="d-block font-weight-bold view-item-details" data-item-code="@{{ x.item_code }}">@{{ r.item_code }}</span>
                                <span class="d-block">@{{ r.description }}</span>
                                <span class="d-block mt-3" ng-hide="r.owner == null" style="font-size: 10pt;"><b>Requested by:</b> @{{ r.owner }}</span>
                              </td>
                              <td class="text-center">@{{ r.qty | number:2 }}</td>
                              <td class="text-center">@{{ r.dr_ref_no }}<br>@{{ r.against_sales_order }}<br><br><span style="font-size: 10pt;">@{{ r.customer }}</span></td>
                              <td class="text-center" ng-if="r.item_status === 'Returned'"><span class="badge badge-success">@{{ r.item_status }}</span></td>
                              <td class="text-center" ng-if="r.item_status === 'For Return'"><span class="badge badge-warning">@{{ r.item_status }}</span></td>
                              <td class="text-center">
                                <img src="dist/img/icon.png" class="img-circle checkout update-ret" data-id="@{{ r.c_name }}">
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

<div class="modal fade" id="update-ps-modal">
    <form id="update-ps-form" method="POST" action="/checkout_picking_slip_item">
        @csrf
        <div class="modal-dialog" style="min-width: 35%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><span class="parent"></span></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box-header with-border">
                                <h4 class="box-title"><span class="warehouse"></span></h4>
                            </div>
                            <div class="box-body" style="font-size: 12pt;">
                                <input type="hidden" value="-" name="user">
                                <input type="hidden" class="id" name="psi_id">
                                <input type="hidden" class="item_code" name="item_code">
                                <input type="hidden" class="wh" name="s_warehouse">
                                <input type="hidden" class="actual_qty" name="balance">
                                <input type="hidden" class="is_bundle" name="is_bundle">
                                <input type="hidden" class="dri-name" name="dri_name">
                                <input type="hidden" class="sales-order" name="sales_order">
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
                                            <div class="col-md-4 mt-2">
                                                <a class='sample item_image_link' data-height='720' data-lighter='samples/sample-01.jpg' data-width='1280' href="#">
                                                    <img src="{{ asset('storage/icon/no_img.png') }}" style="width: 100%;" class="item_image">
                                                </a>
                                            </div>
                                            <div class="col-md-8 mt-2">
                                                <span class="item_code_txt font-weight-bold"></span> <span class="badge badge-info product-bundle-badge" style="font-size: 11pt;">Product Bundle</span>
                                                <p class="description"></p>
                                                <dl class="actual-stock-dl">
                                                    <dt>Actual Qty</dt>
                                                    <dd>
                                                        <p class="badge lbl-color" style="font-size: 12pt;">
                                                            <span class="actual"></span> <span class="stock_uom"></span>
                                                        </p>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-2">
                                        <dl>
                                            <dt>Reference No:</dt>
                                            <dd class="ref_no"></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-8 mt-2">
                                        <dl>
                                            <dt>Status:</dt>
                                            <dd class="status"></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                      <h5 class="text-center font-weight-bold text-uppercase">Product Bundle Item(s)</h5>
                                      <table class="table table-sm table-bordered" id="product-bundle-table">
                                        <col style="width: 60%;">
                                        <col style="width: 20%;">
                                        <col style="width: 20%;">
                                        <thead>
                                          <th class="text-center">Item Description</th>
                                          <th class="text-center">Qty</th>
                                          <th class="text-center">Available Qty</th>
                                        </thead>
                                        <tbody></tbody>
                                      </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> CHECK OUT</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="update-ret-modal">
  <form action="/return_dr_item" method="post" id="return-dr-item-form">
  @csrf
  <div class="modal-dialog" style="min-width: 35%;">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><span class="parent"></span></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-12">
            <div class="box-header with-border">
              <h4 class="box-title"><span class="warehouse"></span></h4>
            </div>
            <div class="box-body" style="font-size: 12pt;">
              <input type="hidden" value="-" name="user">
              <input type="hidden" class="id" id="dri_id" name="dri_id">
              <input type="hidden" class="item_code" name="item_code">
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
                    <div class="col-md-4 mt-2">
                      <a class='sample item_image_link' data-height='720' data-lighter='samples/sample-01.jpg' data-width='1280' href="#">
                      <img src="{{ asset('storage/icon/no_img.png') }}" style="width: 100%;" class="item_image">
                    </a>
                    </div>
                    <div class="col-md-8 mt-2">
                      <span class="item_code_txt d-block font-weight-bold"></span>
                      <p class="description"></p>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 mt-2">
                  <dl>
                    <dt>Reference No:</dt>
                    <dd class="ref_no"></dd>
                  </dl>
                </div>
                <div class="col-md-8 mt-2">
                  <dl>
                    <dt>Status:</dt>
                    <dd class="status"></dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" ><i class="fa fa-check"></i> RETURN</button>
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

    $(document).on('click', '.update-ps', function(){
      $.ajax({
        type: 'GET',
        url: '/get_ps_details/' + $(this).data('id'),
        success: function(response){
          if (response.error) {
            $('#myModal').modal('show'); 
            $('#myModalLabel').html(response.modal_title);
            $('#desc').html(response.modal_message);
            
            return false;
          }

          $('#update-ps-modal .id').val(response.id);
          $('#update-ps-modal .wh').val(response.wh);
          $('#update-ps-modal .parent').text(response.name);
          $('#update-ps-modal .warehouse').text(response.wh);
          $('#update-ps-modal .barcode').val(response.barcode);
          $('#update-ps-modal .qty').val(Number(response.qty));
          $('#update-ps-modal .item_code_txt').text(response.item_code);
          $('#update-ps-modal .item_code').val(response.item_code);
          $('#update-ps-modal .description').text(response.description);
          $('#update-ps-modal .ref_no').text(response.delivery_note);
          $('#update-ps-modal .status').text(response.status);
          $('#update-ps-modal .actual_qty').val(response.actual_qty);
          $('#update-ps-modal .actual').text(response.actual_qty);
          $('#update-ps-modal .stock_uom').text(response.stock_uom);
          $('#update-ps-modal .is_bundle').val(response.is_bundle);
          $('#update-ps-modal .dri-name').val(response.dri_name);
          $('#update-ps-modal .sales-order').val(response.sales_order);

          $('#product-bundle-table tbody').empty();

          if(response.is_bundle) {
            $('#update-ps-modal .product-bundle-badge').removeClass('d-none');
            $('#update-ps-modal .actual-stock-dl').addClass('d-none');

            var table_row = '';
            $.each(response.product_bundle_items, function(i, d){
              var badge = (d.available_qty < d.qty) ? 'badge-danger' : 'badge-success';
              table_row += '<tr>' +
                  '<td class="text-justify align-middle"><b>' + d.item_code + '</b> ' + d.description + '</td>' +
                  '<td class="text-center align-middle"><b>' + d.qty + '</b> ' + d.uom + '</td>' +
                  '<td class="text-center align-middle"><span class="badge ' + badge + '"  style="font-size: 11pt;">' + d.available_qty + ' ' + d.uom + '</span>' +
                  '<span class="d-block" style="font-size: 9pt;">' + d.warehouse + '</span></td>' +
                  '</tr>';
            });

            $('#product-bundle-table tbody').append(table_row);
            $('#product-bundle-table').parent().removeClass('d-none');
          } else {
            $('#update-ps-modal .product-bundle-badge').addClass('d-none');
            $('#update-ps-modal .actual-stock-dl').removeClass('d-none');
            $('#product-bundle-table').parent().addClass('d-none');
          }
        
          if (response.actual_qty <= 0) {
              $('#update-ps-modal .lbl-color').addClass('badge-danger').removeClass('badge-success');
          }else{
              $('#update-ps-modal .lbl-color').addClass('badge-success').removeClass('badge-danger');
          }
      
          var img = (response.item_image) ? '/img/' + response.item_image : '/icon/no_img.png';
          img = "{{ asset('storage/') }}" + img;
      
          $('#update-ps-modal .item_image').attr('src', img);
          $('#update-ps-modal .item_image_link').removeAttr('href').attr('href', img);

          $('#update-ps-modal').modal('show');
        }
      });
    });

    $('#update-ps-modal').on('shown.bs.modal', function() {
        $('#update-ps-modal input[name="barcode"]').focus();
    });
    
    $('#update-ret-modal').on('shown.bs.modal', function() {
        $('#update-ret-modal input[name="barcode"]').focus();
    });

    $('#update-ps-form').submit(function(e){
        e.preventDefault();
        
        $.ajax({
          type: 'POST',
          url: '/checkout_picking_slip_item',
          data: $(this).serialize(),
          success: function(response){
            if (response.error) {
              $('#myModal').modal('show'); 
              $('#myModalLabel').html(response.modal_title);
              $('#desc').html(response.modal_message);
              
              return false;
            }else{
              $('#myModal1').modal('show'); 
              $('#myModalLabel1').html(response.modal_title);
              $('#desc1').html(response.modal_message);

              $('#update-ps-modal').modal('hide');
            }
          },
          error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
          }
        });
    });

    $(document).on('click', '.update-ret', function(){
      $.ajax({
        type: 'GET',
        url: '/get_dr_return_details/' + $(this).data('id'),
        success: function(response){
          $('#update-ret-modal .id').val(response.c_name);
          $('#update-ret-modal .parent').text(response.name);
          $('#update-ret-modal .warehouse').text(response.warehouse);
          $('#update-ret-modal .barcode').val(response.barcode_return);
          $('#update-ret-modal .qty').val(Number(response.qty));
          $('#update-ret-modal .item_code_txt').text(response.item_code);
          $('#update-ret-modal .item_code').val(response.item_code);
          $('#update-ret-modal .description').text(response.description);
          $('#update-ret-modal .ref_no').text(response.against_sales_order);
          $('#update-ret-modal .status').text(response.item_status);
    
          var img = (response.item_image) ? '/img/' + response.item_image : '/icon/no_img.png';
    
          $('#update-ret-modal .item_image').removeAttr('src').attr('src', img);
          $('#update-ret-modal .item_image_link').removeAttr('href').attr('href', img);

          $('#update-ret-modal').modal('show');
        }
      });
    });

    $('#return-dr-item-form').submit(function(e){
      e.preventDefault();

      $.ajax({
        type: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function(response){
          if (response.error) {
            $('#myModal').modal('show'); 
            $('#myModalLabel').html(response.modal_title);
            $('#desc').html(response.modal_message);
            
            return false;
          }else{
            $('#myModal1').modal('show'); 
            $('#myModalLabel1').html(response.modal_title);
            $('#desc1').html(response.modal_message);

            $('#update-ret-modal').modal('hide'); 
          }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          console.log(jqXHR);
          console.log(textStatus);
          console.log(errorThrown);
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
        $http.get("/picking_slip?arr=1").then(function (response) {
          $scope.ps = response.data.picking;
          $scope.custom_loading_spinner_1 = false;
        });

        $http.get("/get_dr_return").then(function (response) {
          $scope.ret = response.data.return;
          $scope.custom_loading_spinner_2 = false;
        });
      }
    
      $scope.loadData();
	 });
</script>
@endsection