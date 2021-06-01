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
	<!-- iCheck for checkboxes and radio inputs -->
	<link rel="stylesheet" href="{{ asset('/updated/plugins/iCheck/all.css') }}">
</head>
<body class="hold-transition layout-top-nav">
	<div class="wrapper">
		<nav class="navbar p-0 navbar-expand-lg navbar-light navbar-navy">
			<div class="container-fluid">
				<div class="d-flex flex-grow-1">
					<div class="row w-100">
						<div class="col-xl-9 col-lg-10 col-md-10">
							<div class="row">
								<div class="col-md-12 col-xl-4 col-lg-3 text-center">
									<a href="/" class="navbar-brand">
										<span class="brand-text text-white" style="font-size: 28pt;">Athena<b>ERP</b><span class="d-md-inline-block d-lg-none d-xl-inline-block">Inventory</span></span>
									</a>
								</div>
								<div class="col-md-12 col-xl-8 col-lg-9">
									<form role="search" method="GET" action="/search_results" id="search-form">
										<div class="input-group p-2">
											<input type="text" class="form-control form-control-lg advancedAuto1Complete" autocomplete="off" placeholder="Search" name="searchString" id="searchid" value="{{ request('searchString') }}">
											<div class="input-group-append">
												<button class="btn btn-default btn-lg" type="submit">
													<i class="fas fa-search"></i> <span class="d-md-none d-lg-none d-xl-inline-block">Search</span>
												</button>
											</div>
										</div>
									</form>
									<div id="suggesstion-box" class="mr-2 ml-2"></div>
								</div>
							</div>
						</div>
						<div class="col-xl-3 col-lg-2 col-md-2 p-2 text-center align-middle">
							<img src="dist/img/avatar04.png" class="img-circle" alt="User Image" width="30" height="30">
							<span class="text-white d-md-none d-lg-none d-xl-inline-block" style="font-size: 13pt;">{{ Auth::user()->full_name }}</span>
							<a href="/logout" class="btn btn-default btn-lg ml-1"><i class="fas fa-sign-out-alt"></i> <span class="d-md-none d-lg-none d-xl-inline-block">Sign Out</span></a>
						</div>
					</div>
				</div>
			</div>
		</nav>

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
			width: 95%;
			display:none;
			overflow:hidden;
			padding: 0;
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
		@if(!in_array($activePage, ['search_results', 'dashboard']))
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
		@endif
		 <!-- /.content-header -->
	 
    	<!-- Main content -->
		@yield('content')
		<!-- /.content -->
		
	</div>
	<!-- /.content-wrapper -->

	<style>

.remove{
     position: absolute;
    top: 15%;
    right: 0;
    transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
    background-color: #d9534f;
    color: white;
    font-size: 16px;
    padding: 5px 10px;
    border: none;
    cursor: pointer;
    border-radius: 2px;
    text-align: center;
}
	</style>

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
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Upload Image</h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-group" id="upload_edit_form">
									<input type="hidden" name="item_code" class="item-code">
									<div class="fileUpload btn btn-primary upload-btn mb-3">
										<span>Browse Image(s)</span>
										<input type="file" name="item_image[]" class="upload" id="browse-img" multiple />
									</div>
									<div class="row">
										<div class="col-md-12" id="image-previews"></div>
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
									<textarea rows="4" name="description" class="form-control" style="height: 124px;" id="description-c"></textarea>
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
												<option value="Website Stocks">Website Stocks</option>
											</select>
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
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label>Valid until</label>
											<input type="text" name="valid_until" class="form-control" id="date-valid-until-c">
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

	<div class="modal fade" id="edit-stock-reservation-modal">
		<form id="edit-reservation-form" method="POST" action="/update_reservation" autocomplete="off">
			@csrf
			<div class="modal-dialog" style="min-width: 40%;">
		  		<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Edit Stock Reservation</h4>
			  			<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						<div class="row m-2">
							<div class="col-md-6">
								<div class="form-group">
									<input type="hidden" name="id" id="stock-reservation-id-e">
									<label for="">Item Code</label>
									<input type="text" class="form-control" name="item_code" id="item-code-e">
								</div>
								<div class="form-group">
									<label for="">Description</label>
									<textarea rows="4" name="description" class="form-control" style="height: 124px;" id="description-e"></textarea>
								</div>
								<div class="form-group">
									<label for="">Notes</label>
									<textarea rows="4" class="form-control" name="notes" id="notes-c" style="height: 124px;"></textarea>
								</div>
							</div>
							<div class="col-md-6">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Warehouse</label>
											<select class="form-control" name="warehouse" id="select-warehouse-e"></select>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label for="">Reserve Qty</label>
											<input type="text" name="reserve_qty" class="form-control" value="0" id="reserve-qty-e">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label for="">Stock UoM</label>
											<input type="text" name="stock_uom" class="form-control" id="stock-uom-e">
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Reservation Type</label>
											<select name="type" class="form-control" id="select-type-e">
												<option value="">Select Type</option>
												<option value="In-house">In-house</option>
												<option value="Website Stocks">Website Stocks</option>
											</select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label for="">Sales Person</label>
											<select class="form-control" name="sales_person" id="select-sales-person-e"></select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label for="">Project</label>
											<select class="form-control" name="project" id="select-project-e"></select>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group for-in-house-type d-none">
											<label>Valid until</label>
											<input type="text" name="valid_until" class="form-control" id="date-valid-until-e">
										</div>
									</div>
								</div>                                        
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> UPDATE</button>
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
<script src="{{ asset('/updated/plugins/select2/js/select2.min.js') }}"></script>
<!-- bootstrap datepicker -->
<script src="{{ asset('/updated/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
<!-- iCheck 1.0.1 -->
<script src="{{ asset('/updated/plugins/iCheck/icheck.min.js') }}"></script>
<!-- ChartJS -->
<script src="{{ asset('/updated/plugins/chart.js/Chart.min.js') }}"></script>

<script src="{{ asset('/js/angular.min.js') }}"></script>
<script src="{{ asset('/js/bootstrap-notify.js') }}"></script>

	@yield('script')

	<script>
		$(document).ready(function(){

			 //-------------
    //- DONUT CHART -
    //-------------
    // Get context with jQuery - using jQuery's .get() method.
    var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
    var donutData        = {
      labels: [
          'Chrome',
          'IE',
          'FireFox',
          'Safari',
          'Opera',
          'Navigator',
      ],
      datasets: [
        {
          data: [700,500,400,600,300,100],
          backgroundColor : ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
        }
      ]
    }
    var donutOptions     = {
      maintainAspectRatio : false,
      responsive : true,
	  legend: {
         position: 'right'
      }
    }
    //Create pie or douhnut chart
    // You can switch between pie and douhnut using the method below.
    new Chart(donutChartCanvas, {
      type: 'doughnut',
      data: donutData,
      options: donutOptions
    })


			get_low_stock_level_items();
			function get_low_stock_level_items(page) {
				$.ajax({
					type: "GET",
					url: "/get_low_stock_level_items?page=" + page,
					success: function (data) {
						$('#low-level-stock-table').html(data);
					}
				});
			}
			
			$('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
				checkboxClass: 'icheckbox_minimal-blue',
				radioClass: 'iradio_minimal-blue'
			});

			$('#cb-2').on('ifChecked', function(event){
				$("#cb-1").prop("checked", true);
				$('#search-form').submit();
			});

			$('#cb-2').on('ifUnchecked', function(event){
				$("#cb-1").prop("checked", false);
				$('#search-form').submit();
			});
						
			$(document).on('click', '.cancel-stock-reservation-btn', function(e){
				e.preventDefault();

				var reservation_id = $(this).data('reservation-id');

				$('#cancel-stock-reservation-modal .reservation-id').text(reservation_id);
				$('#cancel-stock-reservation-modal input[name="stock_reservation_id"]').val(reservation_id);

				$('#cancel-stock-reservation-modal').modal('show');
			});

			$('#edit-reservation-form').submit(function(e){
				e.preventDefault();

				$.ajax({
					type: 'POST',
					url: $(this).attr('action'),
					data: $(this).serialize(),
					success: function(response){
						if (response.error) {
							showNotification("danger", response.modal_message, "fa fa-info");
						}else{
							get_stock_reservation($('#selected-item-code').text());
							showNotification("success", response.modal_message, "fa fa-check");
							$('#edit-stock-reservation-modal').modal('hide');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						console.log(jqXHR);
						console.log(textStatus);
						console.log(errorThrown);
					}
				});
			});

			$('#stock-reservation-form').submit(function(e){
				e.preventDefault();

				$.ajax({
					type: 'POST',
					url: $(this).attr('action'),
					data: $(this).serialize(),
					success: function(response){
						if (response.error) {
							showNotification("danger", response.modal_message, "fa fa-info");
						}else{
							get_stock_reservation($('#selected-item-code').text());
							showNotification("success", response.modal_message, "fa fa-check");
							$('#add-stock-reservation-modal').modal('hide');
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
							showNotification("danger", response.modal_message, "fa fa-info");
						}else{
							get_stock_reservation($('#selected-item-code').text());
							showNotification("success", response.modal_message, "fa fa-check");
							$('#cancel-stock-reservation-modal').modal('hide');
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

			$('#select-warehouse-e').select2({
				dropdownParent: $('#edit-stock-reservation-modal'),
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

			$('#select-type-e').change(function(){
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

			$('#select-project-e').select2({
				dropdownParent: $('#edit-stock-reservation-modal'),
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

			$('#select-sales-person-e').select2({
				dropdownParent: $('#edit-stock-reservation-modal'),
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

			$('#select-warehouse-c').select2({
				dropdownParent: $('#add-stock-reservation-modal'),
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
				dropdownParent: $('#add-stock-reservation-modal'),
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
				dropdownParent: $('#add-stock-reservation-modal'),
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
				startDate: new Date(),
				format: 'yyyy-mm-dd',
				autoclose: true
			});

			$('#date-valid-until-e').datepicker({
				startDate: new Date(),
				format: 'yyyy-mm-dd',
				autoclose: true
			});

			$(document).on('click', '.edit-stock-reservation-btn', function(e){
				e.preventDefault();

				$.ajax({
					type: "GET",
					url: "/get_stock_reservation_details/" + $(this).data('reservation-id'),
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						var selected_warehouse = $('#select-warehouse-e');
						var selected_warehouse_option = new Option(data.warehouse, data.warehouse, true, true);
						selected_warehouse.append(selected_warehouse_option).trigger('change');
						
						var selected_sales_person = $('#select-sales-person-e');
						var selected_sales_person_option = new Option(data.sales_person, data.sales_person, true, true);
						selected_sales_person.append(selected_sales_person_option).trigger('change');

						var selected_project = $('#select-project-e');
						var selected_project_option = new Option(data.project, data.project, true, true);
						selected_project.append(selected_project_option).trigger('change');

						if(data.type == 'In-house'){
							$('#select-sales-person-e').parent().removeClass('d-none');
							$('#select-project-e').parent().removeClass('d-none');
							$('#date-valid-until-e').parent().addClass('d-none');
						}else{
							$('#select-sales-person-e').parent().addClass('d-none');
							$('#select-project-e').parent().addClass('d-none');
							$('#date-valid-until-e').parent().removeClass('d-none');
						}

						$('#stock-reservation-id-e').val(data.name);
						$('#item-code-e').val(data.item_code);
						$('#description-e').val(data.description);
						$('#stock-uom-e').val(data.stock_uom);
						$('#notes-e').val(data.notes);
						$('#select-type-e').val(data.type);
						$('#reserve-qty-e').val(data.reserve_qty);
						$('#status-e').val(data.status);
						$('#date-valid-until-e').val(data.valid_until);

						$('#edit-stock-reservation-modal').modal('show');
					}
				});
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

			setInterval(function () {
				count_ste_for_issue('Material Issue', '#material-issue');
				count_ste_for_issue('Material Transfer', '#material-transfer');
				count_ste_for_issue('Material Transfer for Manufacture', '#material-manufacture');
				count_ps_for_issue();
				count_production_to_receive();
			}, 60000);
			
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
				$('#edit-stock-reservation-modal').modal('hide');
			});
			
			$('#myModal').on("hidden.bs.modal", function () {
				$("body").addClass("modal-open");
			});

			$('.modal').on("hidden.bs.modal", function () {
				$(this).find('form')[0].reset();
				$('.for-in-house-type').addClass('d-none');
				$('.for-online-shop-type').addClass('d-none');
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

			$(document).on('click', '#low-level-stocks-pagination a', function(event){
				event.preventDefault();
				var page = $(this).attr('href').split('page=')[1];
				get_low_stock_level_items(page);
			});

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

				$('.img_upload').remove();
				
				var item_code = $(this).data('item-code');
				
				get_item_images(item_code);
				
				$('#upload-image-modal input[name="item_code"]').val(item_code);
				$('#image-preview').attr('src', $(this).data('image'));
				$('#upload-image-modal').modal('show');
			});

			function get_item_images(item_code){
				var storage = "{{ asset('storage/img/') }}";
				$.ajax({
					type: 'GET',
					url: '/get_item_images/' + item_code,
					success: function(response){
						$.each(response, function(i, d){
							var image_src = storage + '/' + d;
							$("<div class=\"col-md-4 pip img_upload\">" +
							"<input type=\"hidden\" name=\"existing_images[]\" value=\"" + i + "\">" +
							"<img class=\"img-thumbnail\" src=\"" + image_src + "\">" +
							"<span class=\"add-fav remove\">&times;</span>" +
							"</div>").insertAfter("#image-previews");
						});
					}
				});
			}

			$(document).on('click', '.remove', function(){
				$(this).parent(".pip").remove();
			});

			if (window.File && window.FileList && window.FileReader) {
				$("#browse-img").on("change", function(e) {
					var files = e.target.files,
					filesLength = files.length;
					for (var i = 0; i < filesLength; i++) {
						var f = files[i]
						var fileReader = new FileReader();
						fileReader.onload = (function(e) {
							var file = e.target;
							$("<div class=\"col-md-4 pip img_upload\">" +
								"<input type=\"hidden\" name=\"existing_images[]\">" +
							"<img class=\"img-thumbnail\" src=\"" + e.target.result + "\">" +
							"<span class=\"add-fav remove\">&times;</span>" +
							"</div>").insertAfter("#image-previews");
							$(".remove").click(function(){
								$(this).parent(".pip").remove();
							});
						});
						fileReader.readAsDataURL(f);
					}
				});
			} else {
				alert("Your browser doesn't support to File API");
			}

			$('#upload-image-modal form').submit(function(e){
				e.preventDefault();
				var item_code = $(this).find('.item-code').eq(0).val();
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

						view_item_details(item_code);

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

			$(document).on('hidden.bs.modal', '.modal', function () {
				$('.modal:visible').length && $(document.body).addClass('modal-open');
			});

			setInterval(updateClock, 1000);
			function updateClock(){
				var currentTime = new Date();
				var currentHours = currentTime.getHours();
				var currentMinutes = currentTime.getMinutes();
				var currentSeconds = currentTime.getSeconds();
				// Pad the minutes and seconds with leading zeros, if required
				currentMinutes = (currentMinutes < 10 ? "0" : "") + currentMinutes;
				currentSeconds = (currentSeconds < 10 ? "0" : "") + currentSeconds;
				// Choose either "AM" or "PM" as appropriate
				var timeOfDay = (currentHours < 12) ? "AM" : "PM";
				// Convert the hours component to 12-hour format if needed
				currentHours = (currentHours > 12) ? currentHours - 12 : currentHours;
				// Convert an hours component of "0" to "12"
				currentHours = (currentHours === 0) ? 12 : currentHours;
				currentHours = (currentHours < 10 ? "0" : "") + currentHours;
				// Compose the string for display
				var currentTimeString = currentHours + ":" + currentMinutes + " " + timeOfDay;

				$("#current-time").html(currentTimeString);
			}

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
	</script>
</body>
</html>

