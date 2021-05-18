<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ERP Inventory</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{--  <!-- Google Font: Source Sans Pro -->  --}}
  <link rel="stylesheet" href="{{ asset('/updated/custom/font.css') }}">
  {{--  <!-- Font Awesome Icons -->  --}}
  <link rel="stylesheet" href="{{ asset('/updated/plugins/fontawesome-free/css/all.min.css') }}">
  {{--  <!-- Ekko Lightbox -->  --}}
  <link rel="stylesheet" href="{{ asset('/updated/plugins/ekko-lightbox/ekko-lightbox.css') }}">
  {{--  <!-- Theme style -->  --}}
  <link rel="stylesheet" href="{{ asset('/updated/dist/css/adminlte.min.css') }}">
  <!-- Select2 -->
  <link rel="stylesheet" href="{{ asset('/updated/plugins/select2/css/select2.min.css') }}">
  <!-- bootstrap datepicker -->
  <link rel="stylesheet" href="{{ asset('/updated/plugins/datepicker/datepicker3.css') }}">
</head>
<body class="hold-transition layout-top-nav">
	<div class="wrapper">
		{{--  <!-- Navbar -->  --}}
		<nav class="main-header navbar navbar-expand-md navbar-light navbar-navy">
			<div class="container-fluid">
				<form role="search" method="GET" action="/">
					<div class="d-flex flex-grow-1">
						<div class="col-md-3 text-center">
							<a href="/" class="navbar-brand">
								<span class="brand-text text-white" style="font-size: 28pt;"><b>ERP</b>Inventory</span>
							</a>
						</div>
						<div class="col-md-6">
							<div class="row">
								<div class="col-md-1 div-reset" style="min-height: 40px;">
									<button class="btn btn-default d-inline-block" type="button" onclick="document.getElementById('searchid').value = ''">
										<i class="fas fa-sync"></i>
									</button>
								</div>
								<div class="col-md-7 div-search-box" style="min-height: 40px;">
									<input type="text" class="form-control" placeholder="Search Item..." name="searchString" id="searchid" autocomplete="off" value="{{ request('searchString') }}">
									<div id="suggesstion-box"></div>
								</div>
								<div class="col-md-4">
									<div class="row">
										<div class="col-md-6 div-cb-remove text-white" style="min-height: 40px;">
											<label style="font-size: 8pt;">
												<div class="d-inline-block">
													<input type="checkbox" name="check_qty" {{ (request('check_qty')) ? 'checked' : null }} style="width: 15px; height: 15px;">
												</div>
												<div style="width: 70%;" class="cb_remove_zero_qty d-inline-block text-center">Remove zero-qty items</div>
											</label>
										</div>
										<div class="col-md-6 div-search" style="min-height: 40px;">
											<button class="btn btn-block btn-default" type="submit" name="search">
												<i class="fas fa-search"></i> Search
											</button>
										</div>
									</div>
								</div>
								<div class="col-md-4 div-select1" style="min-height: 40px;">
									<select class="form-control" id="group" name="group" style="width: 100%;"></select>
								</div>
								<div class="col-md-4 div-select2" style="min-height: 40px;">
									<select class="form-control" id="classification" name="classification" style="width: 100%;"></select>
								</div>
								<div class="col-md-4 div-select3" style="min-height: 40px;">
									<select class="form-control" id="warehouse-search" name="wh" style="width: 100%;"></select>
								</div>
							</div>
						</div>
						<div class="col-md-3 text-center">
							<img src="dist/img/avatar04.png" class="img-circle" alt="User Image" width="30" height="30">
							<span class="text-white" style="font-size: 13pt;">{{ Auth::user()->full_name }}</span>
							<a href="/logout" class="btn btn-default ml-1"><i class="fas fa-sign-out-alt"></i> Sign out</a>
						</div>
					</div>
				</form>
			</div>
		</nav>
		{{--  <!-- /.navbar -->  --}}

	<style>
		.col-md-13 {
			width: 19%;
			margin: 0.5%;
		}
		.imgPreview {
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 5px;
		}
			
		.upload-btn{
			padding: 6px 12px;
		}
		
		.fileUpload {
			position: relative;
			overflow: hidden;
			font-size: 9pt;
		}
		
		.fileUpload input.upload {
			position: absolute;
			top: 0;
			right: 0;
			margin: 0;
			padding: 0;
			cursor: pointer;
			opacity: 0;
			filter: alpha(opacity=0);
		}
		
		#btn
		{
			display:inline-block;
			border:0;
			position: relative;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1); 
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1); 
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1);
		}

		#btn:hover
		{
			box-shadow: 0px 0px 50px #000000;
			z-index: 2;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1);
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1.5);   
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1.2);
		}

		#suggesstion-box {
			position:absolute;
			width:95%;
			display:none;
			overflow:hidden;
			border:1px #CCC solid;
			background-color: white;
			display: block;
			z-index: 11;
		}

		.div-select1{
			padding: 3px 5px 0 0 ;
		}
		.div-select2{
			padding: 3px 5px 0 5px;
		}
		.div-select3{
			padding: 3px 0 0 5px;
		}
		.div-search-box{
			padding: 0 5px 0 0;
		}
			.div-search{
			padding: 0; 
		}
		.div-reset{
			padding: 0; 
		}
		.div-cb-remove{
			padding: 0;
		}
	
		@media only screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape) {
			/* For landscape layouts only */
			.cb_remove_zero_qty{
				font-size: 0.75em;
			}
			.div-search, .div-cb-remove{
				padding: 0; 
			}
			#suggesstion-box{
				width:98%;
			}
			.div-select1{
				padding: 3px 5px 0 0 ;
			}
			.div-select2{
				padding: 3px 5px 0 5px;
			}
			.div-select3{
				padding: 3px 0 0 5px;
			}
			.div-search-box{
				padding: 0 5px 0 0;
			}
			.ste-purpose-txt{
				font-size: 14pt;
			}
			.div-ste{
				padding-right: 0;
			}
		}

		#d {
			display: inline-block;
			border: 0;
			position: relative;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1);
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1);
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1);
		}

		#d:hover {
			box-shadow: 0px 0px 50px #000000;
			z-index: 2;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1);
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1.5);
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1.2);
		}

		.active_dash {
			-moz-box-shadow: 0 0 5px 5px #888;
			-webkit-box-shadow: 0 0 5px 5px#888;
			box-shadow: 0 0 5px 5px #888;
		}

		.checkout {
			display: inline-block;
			border: 0;
			position: relative;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1);
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1);
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1);
		}
	
		.checkout:hover {
			box-shadow: 0px 0px 50px #000000;
			z-index: 2;
			-webkit-transition: all 200ms ease-in;
			-webkit-transform: scale(1);
			-ms-transition: all 200ms ease-in;
			-ms-transform: scale(1.5);
			-moz-transition: all 200ms ease-in;
			-moz-transform: scale(1);
			transition: all 200ms ease-in;
			transform: scale(1.2);
		}
	</style>

  	<div class="content-wrapper">
		<div class="content-header pb-0">
			<div class="container-fluid m-0">
				<div class="row text-uppercase">
					<div class="col-md-13">
						<div class="info-box {{ ($activePage == 'material-issue') ? 'active_dash' : '' }}" onclick="location.href='/material_issue';" style="cursor: pointer;">
							<span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Material Issue</span>
								<span class="info-box-number" id="material-issue" style="font-size: 30pt;">0</span>
							</div>
						</div>
					</div>
					<div class="col-md-13">
						<div class="info-box {{ ($activePage == 'material-transfer-for-manufacture') ? 'active_dash' : '' }}" onclick="location.href='/material_transfer_for_manufacture';" style="cursor: pointer;">
							<span class="info-box-icon bg-red"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content d-block text-truncate text-nowrap">
								<span class="info-box-text">Material Transfer for Manufacture</span>
								<span class="info-box-number" id="material-manufacture" style="font-size: 30pt;">0</span>
							</div>
						</div>
					</div>
					<div class="col-md-13">
						<div class="info-box {{ ($activePage == 'material-transfer') ? 'active_dash' : '' }}" onclick="location.href='/material_transfer';" style="cursor: pointer;">
							<span class="info-box-icon bg-green"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Material Transfer</span>
								<span class="info-box-number" id="material-transfer" style="font-size: 30pt;">0</span>
							</div>
						</div>
					</div>
					<div class="col-md-13">
						<div class="info-box {{ ($activePage == 'picking-slip') ? 'active_dash' : '' }}" onclick="location.href='/picking_slip';" style="cursor: pointer;">
							<span class="info-box-icon bg-yellow"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">Picking Slip</span>
								<span class="info-box-number" id="picking-slip" style="font-size: 30pt;">0</span>
							</div>
						</div>
					</div>
					<div class="col-md-13">
						<div class="info-box {{ ($activePage == 'material-receipt') ? 'active_dash' : '' }}" onclick="location.href='/production_to_receive';" style="cursor: pointer;">
							<span class="info-box-icon" style="background-color: #605ca8;"><i class="fas fa-shopping-cart"></i></span>
							<div class="info-box-content">
								<span class="info-box-text">To Receive</span>
								<span class="info-box-number" id="material-receipt" style="font-size: 30pt;">0</span>
							</div>
						</div>
					</div>
				</div>
			</div>
    	</div>
		 <!-- /.content-header -->
	 
    	<!-- Main content -->
		@yield('content')
		<!-- /.content -->
		
	</div>
	<!-- /.content-wrapper -->

  	@if($activePage != 'picking-slip')
	<div class="modal fade" id="update-item-modal">
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
							<input type="hidden" value="-" id="full_name" name="user">
							<input type="hidden" class="transfer_as" id="transfer_as" name="transfer_as">
							<input type="hidden" class="id" id="name_id" name="sted_id">
							<input type="hidden" class="s_warehouse" name="s_warehouse" id="s_warehouse">
							<input type="hidden" class="total_issued_qty" id="aqty" name="balance">
							<input type="hidden" class="item_code" id="item_code" name="item_code">
							<input type="hidden" name="production_order">
							<input type="hidden" name="purpose">
							<input type="hidden" name="ste_name">
							<input type="hidden" name="requested_qty">
							<div class="col-md-12">
								<div class="box-header with-border">
									<h4 class="box-title">
										<span class="s_warehouse_txt"></span>
										<span class="transfer_to_div"><i class="fas fa-angle-double-right mr-2 ml-2"></i><span class="t_warehouse_txt"></span></span>
									</h4>
								</div>
								<div class="box-body" style="font-size: 12pt;">
									<div class="row">
										<div class="col-md-6">
											<label>Barcode</label>
											<input type="text" class="form-control barcode" id="barcode" name="barcode" placeholder="Barcode" required>
										</div>
										<div class="col-md-6">
											<label>Qty</label>
											<input type="text" class="form-control qty" id="qty" name="qty" placeholder="Qty">
										</div>
										<div class="col-md-12">
											<div class="row">
												<div class="col-md-5 mt-3">
													<a class='sample item_image_link' data-height='720' data-lighter='samples/sample-01.jpg' data-width='1280' href="#">
														<img src="{{ asset('storage/icon/no_img.png') }}" style="width: 100%;" class="item_image">
													</a>
												</div>
												<div class="col-md-7 mt-3">
													<span class="item_code_txt d-block font-weight-bold"></span>
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
												<dt class="pt-2">Status:</dt>
												<dd class="status"></dd>
											</dl>
										</div>
										<div class="col-md-7 mt-2">
											<dl>
												<dt>Requested by:</dt>
												<dd class="owner"></dd>
												<dt class="pt-2">Remarks:</dt>
												<dd>
													<textarea class="form-control remarks" rows="2" placeholder="Remarks" name="remarks" id="remarks"></textarea>
												</dd>
											</dl>
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
	@endif

	<div class="modal fade" id="view-item-details-modal" tabindex="-1" role="dialog" aria-labelledby="ItemDetails">
		<div class="modal-dialog" role="document" style="min-width: 70%;">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Item Inquiry</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form></form>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div class="nav-tabs-custom">
								<ul class="nav nav-tabs" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" data-toggle="pill" href="#tab_1" role="tab" aria-controls="custom-tabs-three-1" aria-selected="true">Overview</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_2" role="tab" aria-controls="custom-tabs-three-2" aria-selected="false">Athena Transactions</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_3" role="tab" aria-controls="custom-tabs-three-3" aria-selected="false">ERP Submitted Transaction Histories</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_4" role="tab" aria-controls="custom-tabs-three-4" aria-selected="false">Stock Reservations</a>
									</li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane active" id="tab_1">
										<div id="item-detail-content"></div>
									</div>
									<div class="tab-pane" id="tab_2">
										<div class="row">
											<div class="col-md-12">
												<div class="box-body table-responsive no-padding" id="athena-transactions-table"></div>
											</div>
										</div>
									</div>
									<div class="tab-pane" id="tab_3">
										<div class="row">
											<div class="col-md-12">
												<div class="box-body table-responsive no-padding" id="stock-ledger-table"></div>
											</div>
										</div>
									</div>
									<div class="tab-pane" id="tab_4">
										<div class="row">
											<div class="col-md-12">
												<div class="float-right m-2">
													<button class="btn btn-primary" id="add-stock-reservation-btn">New Stock Reservation</button>
												</div>
												<div class="box-body table-responsive no-padding" id="stock-reservation-table"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel">Modal title</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<form></form>
				<div class="modal-body">
					<p id="desc"></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade" id="myModal1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="myModalLabel1">Modal title</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<form></form>
				<div class="modal-body">
					<p id="desc1"></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
			
	<div class="modal fade" id="upload-image-modal" tabindex="-1" role="dialog" aria-labelledby="Upload Image">
		<form method="POST" action="/upload_item_image" enctype="multipart/form-data">
			@csrf
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Upload Image</h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-group" id="upload_edit_form">
									<div style="text-align: center;">
								<input type="hidden" name="item_code">
								
											<div>
											<img src="{{ asset('storage/icon/no_img.png') }}" width="250" height="250" class="imgPreview" id="image-preview">
											</div>
											<div class="fileUpload btn btn-warning upload-btn" style="margin-top: 8px;">
											<span>Choose File..</span>
											<input type="file" name="item_image" class="upload" id="browse-img" />
										</div>                  
									</div>
								</div>
							</div>
						</div>
						
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary">Upload</button>
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</form>
	</div>

	<style>
		.select2{
			width: 100% !important;
		}
		.select2-selection__rendered {
			line-height: 31px !important;
		}
		.select2-container .select2-selection--single {
			height: 37px !important;
			padding-top: 1.5%;
		}
		.select2-selection__arrow {
			height: 36px !important;
		}
	</style>

	<div class="modal fade" id="add-stock-reservation-modal">
		<form id="stock-reservation-form" method="POST" action="/create_reservation" autocomplete="off">
			@csrf
			<div class="modal-dialog" style="min-width: 40%;">
		  		<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">New Stock Reservation</h4>
			  			<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<div class="row m-2">
							<div class="col-md-6">
								<div class="form-group">
									<label for="">Item Code</label>
									<input type="text" class="form-control" name="item_code" id="item-code-c">
								</div>
								<div class="form-group">
									<label for="">Description</label>
									<textarea rows="4" name="item_description" class="form-control" style="height: 124px;" id="description-c"></textarea>
								</div>
								<div class="form-group">
									<label for="">Notes</label>
									<textarea rows="4" class="form-control" name="notes" style="height: 124px;"></textarea>
								</div>
							</div>
							<div class="col-md-6">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Warehouse</label>
											<select class="form-control" name="warehouse" id="select-warehouse-c"></select>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label for="">Reserve Qty</label>
											<input type="text" name="reserve_qty" class="form-control" value="0">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label for="">Stock UoM</label>
											<input type="text" name="stock_uom" class="form-control" id="stock-uom-c">
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Reservation Type</label>
											<select name="type" class="form-control" id="select-type-c">
												<option value="">Select Type</option>
												<option value="In-house">In-house</option>
												<option value="Online Shop">Online Shop</option>
											</select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-online-shop-type d-none">
											<label>Valid until</label>
											<input type="text" name="valid_until" class="form-control" id="date-valid-until-c">
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label for="">Sales Person</label>
											<select class="form-control" name="sales_person" id="select-sales-person-c"></select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label for="">Project</label>
											<select class="form-control" name="project" id="select-project-c"></select>
										</div>
									</div>
								</div>                                        
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> SAVE</button>
						<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
					</div>
				</div>
			</div>
		</form>
	</div>

	<div class="modal fade" id="cancel-stock-reservation-modal">
		<form id="cancel-reservation-form" method="POST" action="/cancel_reservation" autocomplete="off">
			@csrf
			<div class="modal-dialog" style="min-width: 40%;">
		  		<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Cancel Stock Reservation</h4>
			  			<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<input type="hidden" name="stock_reservation_id">
						<h5 class="text-center">Cancel Stock Reservation No. <span class="font-weight-bold reservation-id">-</span>?</h5>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> CONFIRM</button>
						<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
					</div>
				</div>
			</div>
		</form>
	</div>

  <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="float-right d-none d-sm-inline">
		<a href="https://adminlte.io">AdminLTE.io</a></strong> Version 3.1.0
    </div>
    <!-- Default to the left -->
    <strong>Copyright &copy; 2020 <a href="http://fumaco.com">FUMACO Inc</a>.</strong> All rights reserved.
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="{{ asset('/updated/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('/updated/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- Ekko Lightbox -->
<script src="{{ asset('/updated/plugins/ekko-lightbox/ekko-lightbox.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('/updated/dist/js/adminlte.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('/updated/plugins/select2/js/select2.full.min.js') }}"></script>
<!-- bootstrap datepicker -->
<script src="{{ asset('/updated/plugins/datepicker/bootstrap-datepicker.js') }}"></script>

<script src="{{ asset('/js/angular.min.js') }}"></script>

	@yield('script')

	<script>
		$(document).ready(function(){
			$(document).on('click', '.cancel-stock-reservation-btn', function(e){
				e.preventDefault();

				var reservation_id = $(this).data('reservation-id');

				$('#cancel-stock-reservation-modal .reservation-id').text(reservation_id);
				$('#cancel-stock-reservation-modal input[name="stock_reservation_id"]').val(reservation_id);

				$('#cancel-stock-reservation-modal').modal('show');
			});

			$('#stock-reservation-form').submit(function(e){
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
							get_stock_reservation($('#selected-item-code').text());
							$('#myModal1').modal('show'); 
							$('#myModalLabel1').html(response.modal_title);
							$('#desc1').html(response.modal_message);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
						console.log(textStatus);
						console.log(errorThrown);
					}
				});
			});

			$('#cancel-reservation-form').submit(function(e){
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
							get_stock_reservation($('#selected-item-code').text());
							$('#myModal1').modal('show'); 
							$('#myModalLabel1').html(response.modal_title);
							$('#desc1').html(response.modal_message);
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
						console.log(textStatus);
						console.log(errorThrown);
					}
				});
			});

			$('#add-stock-reservation-btn').click(function(e){
				e.preventDefault();

				$.ajax({
					type: "GET",
					url: "/get_item_details/" + $('#selected-item-code').text() + "?json=true",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#item-code-c').val(data.name);
						$('#description-c').val(data.description);
						$('#stock-uom-c').val(data.stock_uom);
						
						$('#add-stock-reservation-modal').modal('show');
					}
				});
			});

			$('#select-warehouse-c').select2({
				placeholder: 'Select Warehouse',
				ajax: {
					url: '/warehouses',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							q: data.term // search term
						};
					},
					processResults: function (response) {
						return {
							results:response
						};
					},
					cache: true
				}
			});

			$('#select-type-c').change(function(){
				if($(this).val()) {
					if($(this).val() == 'In-house') {
						$('.for-in-house-type').removeClass('d-none');
						$('.for-online-shop-type').addClass('d-none');
					} else {
						$('.for-in-house-type').addClass('d-none');
						$('.for-online-shop-type').removeClass('d-none');
					}
				}
			});

			$('#select-project-c').select2({
				placeholder: 'Select Project',
				ajax: {
					url: '/projects',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							q: data.term // search term
						};
					},
					processResults: function (response) {
						return {
							results:response
						};
					},
					cache: true
				}
			});

			$('#select-sales-person-c').select2({
				placeholder: 'Select Sales Person',
				ajax: {
					url: '/sales_persons',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							q: data.term // search term
						};
					},
					processResults: function (response) {
						return {
							results:response
						};
					},
					cache: true
				}
			});

			$('#date-valid-until-c').datepicker({
				autoclose: true
			});

			$(document).on('click', '[data-toggle="lightbox"]', function(event) {
                event.preventDefault();
                $(this).ekkoLightbox({
					showArrows: true,
				});
			});
			
			$.ajaxSetup({
				headers: {
				  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				}
			});

				count_ste_for_issue('Material Issue', '#material-issue');
				count_ste_for_issue('Material Transfer', '#material-transfer');
				count_ste_for_issue('Material Transfer for Manufacture', '#material-manufacture');
				count_ps_for_issue();
				count_production_to_receive();

			// setInterval(function () {
			// 	count_ste_for_issue('Material Issue', '#material-issue');
			// 	count_ste_for_issue('Material Transfer', '#material-transfer');
			// 	count_ste_for_issue('Material Transfer for Manufacture', '#material-manufacture');
			// 	count_ps_for_issue();
			// 	count_production_to_receive();
			// }, 60000);
			
			function count_ste_for_issue(purpose, div){
				$.ajax({
					type: "GET",
					url: "/count_ste_for_issue/" + purpose,
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$(div).text(data);
					}
				});
			}

			function count_production_to_receive(){
				$.ajax({
					type: "GET",
					url: "/count_production_to_receive",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#material-receipt').text(data);
					}
				});
			}

			function count_ps_for_issue(){
				$.ajax({
					type: "GET",
					url: "/count_ps_for_issue",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#picking-slip').text(data);
					}
				});
			}

			function load_suggestion_box(){
				var search_string = $('#searchid').val();
				$.ajax({
					type: "GET",
					url: "/load_suggestion_box",
					data: {search_string: search_string},
					success: function (data) {
						$("#suggesstion-box").show();
						$("#suggesstion-box").html(data);
						$("#searchid").css("background", "#FFF");
					}
				});
			}

			get_select_filters();
			function get_select_filters(){
				$('#group').empty();
				$('#classification').empty();
				$('#wh').empty();

				var group = '<option value="">All Item Groups</option>';
				var classification = '<option value="">All Item Classification</option>';
				var wh = '<option value="">All Warehouse</option>';
				$.ajax({
					url: "/get_select_filters",
					type:"GET",
					success: function(data){
						$.each(data.warehouses, function(i, v){
							wh += '<option value="' + v + '">' + v + '</option>';
						});

						$.each(data.item_groups, function(i, v){
							group += '<option value="' + v + '">' + v + '</option>';
						});

						$.each(data.item_classification, function(i, v){
							classification += '<option value="' + v + '">' + v + '</option>';
						});

						$('#group').append(group);
						$('#classification').append(classification);
						$('#warehouse-search').append(wh);

						$('#group').val('{{ request("group") }}');
						$('#classification').val('{{ request("classification") }}');
						$('#warehouse-search').val('{{ request("wh") }}');
					}
				});
			}

			$("#searchid").keyup(function () {
				load_suggestion_box();
		  	});

			$('body').click(function () {
					$("#suggesstion-box").hide();
			});

			$(document).on('click', '.selected-item', function(e){
				$("#searchid").val($(this).data('val'));
				$("#suggesstion-box").hide();
			});

			// update item modal
			$(document).on('click', '.update-item', function(){
				var id = $(this).data('id');
				$.ajax({
				  type: 'GET',
				  url: '/get_ste_details/' + id,
				  success: function(response){
					$('#update-item-modal input[name="ste_name"]').val(response.parent);
					$('#update-item-modal input[name="production_order"]').val(response.production_order);
					$('#update-item-modal input[name="purpose"]').val(response.purpose);

					$('#update-item-modal input[name="requested_qty"]').val(response.qty);

					$('#update-item-modal .parent').text(response.parent);
					$('#update-item-modal .purpose').text(response.purpose);
			
					$('#update-item-modal .transfer_as').val(response.transfer_as);
					$('#update-item-modal .id').val(response.name);
					$('#update-item-modal .s_warehouse').val(response.s_warehouse);
					$('#update-item-modal .total_issued_qty').val(response.total_issued_qty);
					$('#update-item-modal .item_code').val(response.item_code);
			
					$('#update-item-modal .s_warehouse_txt').text(response.s_warehouse);
					$('#update-item-modal .t_warehouse_txt').text(response.t_warehouse);
			
					var barcode_value = (response.transfer_as == 'For Return') ? '' : response.validate_item_code;
					var img = (response.img) ? '/img/' + response.img : '/icon/no_img.png';
					img = "{{ asset('storage/') }}" + img;

					$('#update-item-modal .item_image').attr('src', img);
					$('#update-item-modal .item_image_link').removeAttr('href').attr('href', img);
				
					// hide "transfer to" field
					if (response.purpose != 'Material Issue') {
					  $('#update-item-modal .transfer_to_div').show();
					}else{
					  $('#update-item-modal .transfer_to_div').hide();
					}
			
					$('#update-item-modal .qty').val(Number(response.qty));
					$('#update-item-modal .item_code_txt').text(response.item_code);
					$('#update-item-modal .description').text(response.description);
					$('#update-item-modal .owner').text(response.owner);
					$('#update-item-modal .t_warehouse').val(response.t_warehouse);
					$('#update-item-modal .barcode').val(barcode_value);
					$('#update-item-modal .ref_no').text(response.ref_no);
					$('#update-item-modal .status').text(response.status);
			
					if (response.total_issued_qty <= 0) {
					  $('#update-item-modal .lbl-color').addClass('badge-danger').removeClass('badge-success');
					}else{
					  $('#update-item-modal .lbl-color').addClass('badge-success').removeClass('badge-danger');
					}
			
					$('#update-item-modal .total_issued_qty_txt').text(response.total_issued_qty);
					$('#update-item-modal .stock_uom').text(response.stock_uom);
					$('#update-item-modal .remarks').text(response.remarks);

					$('#update-item-modal').modal('show');
				  }
				});
			
				
			});

			$('#update-ste-form').submit(function(e){
				e.preventDefault();

				$.ajax({
					type: 'POST',
					url: '/checkout_ste_item',
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
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
						console.log(textStatus);
						console.log(errorThrown);
					}
				});
			});

			$('#myModal1').on('hide.bs.modal', function(){
				$('#update-item-modal').modal('hide');
				$('#update-item-return-modal').modal('hide');
				$('#add-stock-reservation-modal').modal('hide');
				$('#cancel-stock-reservation-modal').modal('hide');
			});
			
			$('#myModal').on("hidden.bs.modal", function () {
				$("body").addClass("modal-open");
			});
		
			$('.modal').on("hidden.bs.modal", function () {
				$(this).find('form')[0].reset();
			});

			$(document).on('click', '.view-item-details', function(e){
				e.preventDefault();

				var item_code = $(this).data('item-code');

				view_item_details(item_code);
			});

			function view_item_details(item_code){
				$.ajax({
					type: 'GET',
					url: '/get_item_details/' + item_code,
					success: function(response){
						$('#item-detail-content').html(response);
						$('#view-item-details-modal').modal('show');
					}
				});

				get_athena_transactions(item_code);
				get_stock_ledger(item_code);
				get_stock_reservation(item_code)
			}

			function get_athena_transactions(item_code, page){
				$.ajax({
					type: 'GET',
					url: '/get_athena_transactions/' + item_code + '?page=' + page,
					success: function(response){
						$('#athena-transactions-table').html(response);
					}
				});
			}

			function get_stock_reservation(item_code, page){
				$.ajax({
					type: 'GET',
					url: '/get_stock_reservation/' + item_code + '?page=' + page,
					success: function(response){
						$('#stock-reservation-table').html(response);
					}
				});
			}

			$(document).on('click', '#athena-transactions-pagination a', function(event){
				event.preventDefault();
				var item_code = $(this).closest('div').data('item-code');
				var page = $(this).attr('href').split('page=')[1];
				get_athena_transactions(item_code, page);
			});

			$(document).on('click', '#stock-reservations-pagination a', function(event){
				event.preventDefault();
				var item_code = $(this).closest('div').data('item-code');
				var page = $(this).attr('href').split('page=')[1];
				get_stock_reservation(item_code, page);
			});

			function get_stock_ledger(item_code, page){
				$.ajax({
					type: 'GET',
					url: '/get_stock_ledger/' + item_code + '?page=' + page,
					success: function(response){
						$('#stock-ledger-table').html(response);
					}
				});
			}

			$(document).on('click', '#stock-ledger-pagination a', function(event){
				event.preventDefault();
				var item_code = $(this).closest('div').data('item-code');
				var page = $(this).attr('href').split('page=')[1];
				get_stock_ledger(item_code, page);
			});

			$(document).on('click', '.upload-item-image', function(e){
				e.preventDefault();
				
				var item_code = $(this).data('item-code');
				
				$('#upload-image-modal input[name="item_code"]').val(item_code);
				$('#image-preview').attr('src', $(this).data('image'));
				$('#upload-image-modal').modal('show');
			});

			$("#browse-img").change(function () {
				if (this.files && this.files[0]) {
					var reader = new FileReader();
					reader.onload = function (e) {
						 $('#image-preview').attr('src', e.target.result);
					}
					reader.readAsDataURL(this.files[0]);
				}
			});

			$('#upload-image-modal form').submit(function(e){
				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: $(this).attr('action'),
					data: new FormData(this),
					cache: false,
					contentType: false,
					processData: false,
					success: function(response){
						$('#myModal').modal('show'); 
						$('#myModalLabel').html('Message');
						$('#desc').html(response.message);

						$('#upload-image-modal').modal('hide');
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
						console.log(textStatus);
						console.log(errorThrown);
					}
				});
			});

			$(document).on('show.bs.modal', '.modal', function (event) {
				var zIndex = 1040 + (10 * $('.modal:visible').length);
				$(this).css('z-index', zIndex);
				setTimeout(function() {
					$('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
				}, 0);
			});
			
			$('#view-item-details-modal').on("hidden.bs.modal", function () {
				$('#item-tabs a[href="#tab_1"]').tab('show');
			});
		});
	</script>
</body>
</html>

