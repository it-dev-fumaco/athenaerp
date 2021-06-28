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

<style>
	@font-face { font-family: 'Montserrat'; src: url({{ asset('font/Montserrat/Montserrat-Regular.ttf') }}); } 
	*:not(i):not(.fa){
		font-family: 'Montserrat' !important;
	}

	.blink-reservation {
		animation: blinker 1.10s linear infinite;
	}
	
	@keyframes blinker {
		50% {
			opacity: 0;
		}
	}
</style>

<body class="hold-transition layout-top-nav">
	<div class="wrapper">
		<nav class="navbar p-0 navbar-expand-lg navbar-light navbar-navy">
			<div class="container-fluid">
				<div class="d-flex flex-grow-1">
					<div class="row w-100">
						<div class="col-xl-8 col-lg-9 col-md-9">
							<div class="row">
								<div class="col-md-12 col-xl-6 col-lg-4 text-center">
									<a href="/" class="navbar-brand">
										<span class="brand-text text-white" style="font-size: 1.7rem;">Athena<b>ERP </b><span class="d-md-inline-block d-lg-none d-xl-inline-block"> Inventory</span></span>
									</a>
								</div>
								<div class="col-md-12 col-xl-6 col-lg-8 align-middle">
									<form role="search" method="GET" action="/search_results" id="search-form">
										<input type="checkbox" id="cb-1" name="check_qty" hidden>
										<input type="hidden" name="wh" id="wh-1" value="{{ request('wh') }}">
										<div class="input-group p-1">
											<input type="text" class="form-control" autocomplete="off" placeholder="Search" name="searchString" id="searchid" value="{{ request('searchString') }}">
											<div class="input-group-append">
												<button class="btn btn-default" type="submit">
													<i class="fas fa-search"></i> <span class="d-md-none d-lg-none d-xl-inline-block">Search</span>
												</button>
											</div>
										</div>
									</form>
									<div id="suggesstion-box" class="mr-2 ml-2"></div>
								</div>
							</div>
						</div>
						<div class="col-xl-4 col-lg-3 col-md-3 align-middle pb-0">
							<ul class="order-1 order-md-3 navbar-nav navbar-no-expand mb-0 align-middle">
								<li class="nav-item dropdown col-8 text-right">
									<a class="nav-link text-white" data-toggle="dropdown" href="#">
										<img src="dist/img/avatar04.png" class="img-circle" alt="User Image" width="30" height="30">
										<span class="text-white d-md-none d-lg-none d-xl-inline-block" style="font-size: 13pt;">{{ Auth::user()->full_name }}</span> <i class="fas fa-caret-down ml-2"></i>
									</a>
								  	<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
										<span class="dropdown-header">Warehouse Access</span>
										<div id="allowed-warehouse-div">
											<div class="dropdown-divider"></div>
											<a href="#" class="dropdown-item text-center">
												<i class="fas fa-info-circle mr-2"></i>	No warehouse assigned	  
											</a>
										</div>
									</div>
								</li>
								<li class="nav-item dropdown text-right">
									<a href="/logout" class="btn btn-default m-1"><i class="fas fa-sign-out-alt"></i> <span class="d-md-none d-lg-none d-xl-inline-block">Sign Out</span></a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</nav>


		<div class="content-wrapper">
			@if(!in_array($activePage, ['search_results', 'dashboard']))
			<div class="row m-0 pb-0">
				<div class="col-xl-5 p-3">
					<h2><a href="/" class="btn btn-default float-left pt-2 pb-2 pr-3 pl-3 mr-2 ">
						<i class="fas fa-home"></i>
					</a>{{ $namePage }} <small class="text-muted">{{ $nameDesc }}</small></h2>
				</div>
				<div class="col-xl-7 pr-4 pt-2 text-right">
				<a class="btn btn-app bg-primary" href="/returns">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="p-returns">-</span>
					<i class="fas fa-undo"></i> Returns
					</a>
					<a class="btn btn-app bg-info" href="/production_to_receive">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="material-receipt">-</span>
					<i class="far fa-check-circle"></i> Feedback
					</a>
					<a class="btn btn-app bg-secondary" href="/material_transfer">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="material-transfer">-</span>
					<i class="fas fa-exchange-alt"></i> Transfer
					</a>
					<a class="btn btn-app bg-maroon" href="/receipts">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="p-purchase-receipts">-</span>
					<i class="fas fa-boxes"></i> PO Receipts
					</a>
					<a class="btn btn-app bg-olive" href="/material_transfer_for_manufacture">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="material-manufacture">-</span>
					<i class="fas fa-tasks"></i> Withdrawals
					</a>
					<a class="btn btn-app bg-indigo" href="/material_issue">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="material-issue">-</span>
					<i class="fas fa-dolly"></i> Material Issue
					</a>
					<a class="btn btn-app bg-navy" href="/picking_slip">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="picking-slip">-</span>
					<i class="fas fa-truck"></i> Deliveries
					</a>
					<a class="btn btn-app bg-orange" href="/replacements">
					<span class="badge bg-warning font-weight-bold" style="font-size: 1rem;" id="p-replacements">-</span>
					<i class="fas fa-retweet"></i> Replacements
					</a>
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

	<div class="modal fade" id="view-item-details-modal" tabindex="-1" role="dialog" aria-labelledby="ItemDetails">
		<div class="modal-dialog" role="document" style="min-width: 90%;">
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
								<ul class="nav nav-tabs" id="item-tabs" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" data-toggle="pill" href="#tab_1" role="tab" aria-controls="custom-tabs-three-1" aria-selected="true">Item Info</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_2" role="tab" aria-controls="custom-tabs-three-2" aria-selected="false">Athena Transactions</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_3" role="tab" aria-controls="custom-tabs-three-3" aria-selected="false">ERP Submitted Transaction Histories</a>
									</li>
									@if(in_array(Auth::user()->user_group, ['Inventory Manager']))
									<li class="nav-item">
										<a class="nav-link" data-toggle="pill" href="#tab_4" role="tab" aria-controls="custom-tabs-three-4" aria-selected="false">Stock Reservations</a>
									</li>
									@endif
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
												@php
													$attr = (!in_array(Auth::user()->user_group, ['Inventory Manager'])) ? 'disabled' : '';
												@endphp
												<div class="float-right m-2">
													<button class="btn btn-primary" id="add-stock-reservation-btn" {{ $attr }}>New Stock Reservation</button>
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
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						<button type="submit" class="btn btn-primary btn-lg">Upload</button>
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
									<input type="text" class="form-control" name="item_code" id="item-code-c" readonly>
								</div>
								<div class="form-group">
									<label for="">Description</label>
									<textarea rows="4" name="description" class="form-control" style="height: 124px;" id="description-c" readonly></textarea>
								</div>
								<div class="form-group">
									<label for="">Notes</label>
									<textarea rows="4" class="form-control" name="notes" style="height: 124px;"></textarea>
								</div>
								<div class="form-group for-in-house-type d-none">
									<label for="validity-c">Validity in Day(s)</label>
									<input type="number" class="form-control" id="validity-c" min="0" value="0">
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
											<label for="" class="d-block">Available Qty</label>
											<span class="badge badge-danger">
												<span id="available-qty-c-text">0</span>
												<span id="stock-uom-c-text"></span>
											</span>
											<input type="hidden" class="form-control" id="available-qty-c" value="0">
										</div>
									</div>
									<div class="col-md-6 d-none">
										<div class="form-group">
											<label for="">Stock UoM</label>
											<input type="hidden" name="stock_uom" class="form-control" id="stock-uom-c" readonly>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Reservation Type</label>
											<select name="type" class="form-control" id="select-type-c">
												<option value="">Select Type</option>
												<option value="In-house">In-house</option>
												<option value="Consignment">Consignment</option>
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
										<div class="form-group for-consignment d-none">
											<label for="">Branch Warehouse</label>
											<select class="form-control" name="consignment_warehouse" id="select-branch-warehouse-c"></select>
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
						<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
						<button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> SAVE</button>
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
									<input type="text" class="form-control" name="item_code" id="item-code-e" readonly>
								</div>
								<div class="form-group">
									<label for="">Description</label>
									<textarea rows="4" name="description" class="form-control" style="height: 124px;" id="description-e" readonly></textarea>
								</div>
								<div class="form-group">
									<label for="">Notes</label>
									<textarea rows="4" class="form-control" name="notes" id="notes-e" style="height: 124px;"></textarea>
								</div>
								<div class="form-group for-in-house-type d-none">
									<label for="validity-e">Validity in Day(s)</label>
									<input type="number" class="form-control" id="validity-e" min="0" value="0">
								</div>
							</div>
							<div class="col-md-6">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Warehouse</label>
											<select class="form-control" id="select-warehouse-e" readonly></select>
											<input type="hidden" class="form-control" name="warehouse" id="warehouse-e">
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
											<label for="" class="d-block">Available Qty</label>
											<span class="badge badge-danger">
												<span id="available-qty-e-text">0</span>
												<span id="stock-uom-e-text"></span>
											</span>
											<input type="hidden" class="form-control" name="available_qty" id="available-qty-e" value="0" readonly>
										</div>
									</div>
									<div class="col-md-6 d-none">
										<div class="form-group">
											<label for="">Stock UoM</label>
											<input type="text" name="stock_uom" class="form-control" id="stock-uom-e" readonly>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label for="">Reservation Type</label>
											<select name="type" class="form-control" id="select-type-e" disabled>
												<option value="">Select Type</option>
												<option value="In-house">In-house</option>
												<option value="Consignment">Consignment</option>
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
										<div class="form-group for-consignment d-none">
											<label for="">Branch Warehouse</label>
											<select class="form-control" name="consignment_warehouse" id="select-branch-warehouse-e"></select>
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
						<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
						<button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> UPDATE</button>
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
						<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
						<button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> CONFIRM</button>
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
<!-- jquery-validation -->
<script src="{{ asset('/updated/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/updated/plugins/jquery-validation/additional-methods.min.js') }}"></script>

	@yield('script')

	<script>
		$(document).ready(function(){
			$(document).on('click', '.create-mr-btn', function(e){
				e.preventDefault();

				$.ajax({
					type: 'GET',
					url: '/create_material_request/' + $(this).data('id'),
					success: function(response){
						if (response.status) {
							showNotification("success", response.message, "fa fa-check");
							get_low_stock_level_items();
						}else{
							showNotification("danger", response.message, "fa fa-info");
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
					}
				});
			});

			$(document).on('select2:select', '#select-warehouse-c', function(e){
				var data = e.params.data;
				var warehouse = data.id;
				var item_code = $('#item-code-c').val();

				$.ajax({
					type: 'GET',
					url: '/get_available_qty/' + item_code + '/' + warehouse,
					success: function(response){
						$('#available-qty-c').val(response);
						var badge_color = (response > 0) ? 'badge-success' : 'badge-danger';
						$('#available-qty-c-text').text(response).parent().removeClass('badge-danger badge-success').addClass(badge_color);
					}
				});
			});

			$(document).on('select2:select', '#select-warehouse-e', function(e){
				var data = e.params.data;
				var warehouse = data.id;
				var item_code = $('#item-code-e').val();

				$.ajax({
					type: 'GET',
					url: '/get_available_qty/' + item_code + '/' + warehouse,
					success: function(response){
						$('#available-qty-e').val(response);
						var badge_color = (response > 0) ? 'badge-success' : 'badge-danger';
						$('#available-qty-e-text').text(response).parent().removeClass('badge-danger badge-success').addClass(badge_color);
					}
				});
			});

			dashboard_data();
			setInterval(function () {
				dashboard_data();
			}, 60000);

			function dashboard_data(purpose, div){
				$.ajax({
					type: "GET",
					url: "/dashboard_data",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#d-material-receipt').text(data.d_feedbacks);
						$('#d-purchase-receipts').text(data.d_purchase_receipts);
						$('#p-purchase-receipts').text(data.p_purchase_receipts);
						$('#d-replacements').text(data.d_replacements);
						$('#p-replacements').text(data.p_replacements);
						$('#d-material-transfer').text(data.d_internal_transfers);
						$('#d-picking-slips').text(data.d_picking_slips);
						$('#d-withdrawals').text(data.d_withdrawals);
						$('#d-material-issues').text(data.d_material_issues);
					}
				});
			}

			$('#warehouse-filter').select2({
				dropdownParent: $('#warehouse-filter-parent'),
				placeholder: 'Select Warehouse',
				ajax: {
					url: '/get_select_filters',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							q: data.term // search term
						};
					},
					processResults: function (response) {
						return {
							results: response.warehouses
						};
					},
					cache: true
				}
			});

			$(document).on('select2:select', '#warehouse-filter', function(e){
				var data = e.params.data;

				$('#wh-1').val(data.id);
				$('#search-form').submit();
			});

			allowed_parent_warehouses();
			function allowed_parent_warehouses(){
				$.ajax({
					type: 'GET',
					url: '/allowed_parent_warehouses',
					success: function(response){
						var r = '';
						$.each(response, function(i, d){
							r += '<div class="dropdown-divider"></div>' +
								'<a href="#" class="dropdown-item">' +
								'<i class="fas fa-warehouse mr-2"></i>' + d + '</a>'
						});

						$('#allowed-warehouse-div').html(r);
					}
				});
			}

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

			get_athena_logs({{ now()->month }});
			function get_athena_logs(month) {
				$.ajax({
					type: "GET",
					url: "/get_athena_logs?month=" + month,
					success: function (data) {
						$('#athena-logs-table').html(data);
					}
				});
			}

			$('#athena-logs-pagination .month').click(function(e){
				e.preventDefault();
				var month = $(this).find('.page-link').eq(0).data('month');
				$('#athena-logs-pagination li.active').removeClass('active');
				$(this).addClass('active');

				set_prev_next_btn_att(month);
				get_athena_logs(month);
			});

			$('#athena-logs-pagination .prev').click(function(e){
				e.preventDefault();

				if(!$(this).hasClass('disabled')){
					var active = $('#athena-logs-pagination li.active');
					active.removeClass('active');
					active.prev().addClass('active');

					var month = $('#athena-logs-pagination li.active').find('.page-link').eq(0).data('month');
					set_prev_next_btn_att(month);
					get_athena_logs(month);
				
				}
			});

			function set_prev_next_btn_att(n){
				$('#athena-logs-pagination .prev').removeClass('disabled');
				$('#athena-logs-pagination .next').removeClass('disabled');

				if(n == 1) {
					$('#athena-logs-pagination .prev').addClass('disabled');
				}

				if(n == {{ now()->month }}) {
					$('#athena-logs-pagination .next').addClass('disabled');
				}
			}

			$('#athena-logs-pagination .next').click(function(e){
				e.preventDefault();

				if(!$(this).hasClass('disabled')){
					var active = $('#athena-logs-pagination li.active');
					active.removeClass('active');
					active.next().addClass('active');

					var month = $('#athena-logs-pagination li.active').find('.page-link').eq(0).data('month');
					set_prev_next_btn_att(month);
					get_athena_logs(month);
				}
			});
			
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
							view_item_details($('#selected-item-code').text());
							showNotification("success", response.modal_message, "fa fa-check");
							$('#edit-stock-reservation-modal').modal('hide');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
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
							view_item_details($('#selected-item-code').text());
							showNotification("success", response.modal_message, "fa fa-check");
							$('#add-stock-reservation-modal').modal('hide');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
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
					}
				});
			});

			$('#add-stock-reservation-btn').click(function(e){
				e.preventDefault();

				$('#select-warehouse-c').val(null).trigger('change');
				$('#select-sales-person-c').val(null).trigger('change');
				$('#select-branch-warehouse-c').val(null).trigger('change');
				$('#select-project-c').val(null).trigger('change');

				$("#date-valid-until-c").datepicker("update", new Date());

				$.ajax({
					type: "GET",
					url: "/get_item_details/" + $('#selected-item-code').text() + "?json=true",
					dataType: 'json',
					contentType: 'application/json',
					success: function (data) {
						$('#item-code-c').val(data.name);
						$('#description-c').val(data.description);
						$('#stock-uom-c').val(data.stock_uom);
						$('#stock-uom-c-text').text(data.stock_uom);
						
						$('#add-stock-reservation-modal').modal('show');
					}
				});
			});

			$('#select-warehouse-e').select2({
				dropdownParent: $('#edit-stock-reservation-modal'),
				placeholder: 'Select Warehouse',
				ajax: {
					url: '/warehouses_with_stocks',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							item_code: $('#item-code-e').val(),
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
						$('.for-consignment').addClass('d-none');
					} else if ($(this).val() == 'Consignment') {
						$('.for-in-house-type').addClass('d-none');
						$('.for-online-shop-type').addClass('d-none');
						$('.for-consignment').removeClass('d-none');
					} else {
						$('.for-in-house-type').addClass('d-none');
						$('.for-online-shop-type').removeClass('d-none');
						$('.for-consignment').addClass('d-none');
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

			$('#select-branch-warehouse-e').select2({
				dropdownParent: $('#edit-stock-reservation-modal'),
				placeholder: 'Select Branch',
				ajax: {
					url: '/consignment_warehouses',
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
					url: '/warehouses_with_stocks',
					method: 'GET',
					dataType: 'json',
					data: function (data) {
						return {
							item_code: $('#item-code-c').val(),
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

			$('#select-branch-warehouse-c').select2({
				dropdownParent: $('#add-stock-reservation-modal'),
				placeholder: 'Select Branch',
				ajax: {
					url: '/consignment_warehouses',
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
						$('.for-consignment').addClass('d-none');
					} else if ($(this).val() == 'Consignment') {
						$('.for-in-house-type').addClass('d-none');
						$('.for-online-shop-type').addClass('d-none');
						$('.for-consignment').removeClass('d-none');
					} else {
						$('.for-in-house-type').addClass('d-none');
						$('.for-online-shop-type').removeClass('d-none');
						$('.for-consignment').addClass('d-none');
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

			$("#validity-c").bind('keyup mouseup', function () {
				var newdate = new Date();
				newdate.setDate(newdate.getDate() + parseInt($(this).val()));
				$('#date-valid-until-c').datepicker('setDate', newdate);
			});

			$("#validity-e").bind('keyup mouseup', function () {
				var newdate = new Date();
				newdate.setDate(newdate.getDate() + parseInt($(this).val()));
				$('#date-valid-until-e').datepicker('setDate', newdate);
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
						selected_warehouse.select2({disabled:'readonly'});

						if(data.consignment_warehouse) {
							var selected_branch = $('#select-branch-warehouse-e');
							var selected_branch_option = new Option(data.consignment_warehouse, data.consignment_warehouse, true, true);
							selected_branch.append(selected_branch_option).trigger('change');
							selected_branch.select2({disabled:'readonly'});
						}

						if(data.sales_person) {
							var selected_sales_person = $('#select-sales-person-e');
							var selected_sales_person_option = new Option(data.sales_person, data.sales_person, true, true);
							selected_sales_person.append(selected_sales_person_option).trigger('change');
						}

						if(data.project) {
							var selected_project = $('#select-project-e');
							var selected_project_option = new Option(data.project, data.project, true, true);
							selected_project.append(selected_project_option).trigger('change');
						}

						if(data.type == 'In-house') {
							$('.for-in-house-type').removeClass('d-none');
							$('.for-online-shop-type').addClass('d-none');
							$('.for-consignment').addClass('d-none');
						} else if (data.type == 'Consignment') {
							$('.for-in-house-type').addClass('d-none');
							$('.for-online-shop-type').addClass('d-none');
							$('.for-consignment').removeClass('d-none');
						} else {
							$('.for-in-house-type').addClass('d-none');
							$('.for-online-shop-type').removeClass('d-none');
							$('.for-consignment').addClass('d-none');
						}

						$.ajax({
							type: 'GET',
							url: '/get_available_qty/' + data.item_code + '/' + data.warehouse,
							success: function(response){
								var available_qty = parseInt(response) + (data.reserve_qty - data.consumed_qty);
								var badge_color = (available_qty > 0) ? 'badge-success' : 'badge-danger';
								$('#available-qty-e-text').text(available_qty).parent().removeClass('badge-danger badge-success').addClass(badge_color);
								$('#available-qty-e').val(available_qty);
							}
						});

						$('#stock-reservation-id-e').val(data.name);
						$('#warehouse-e').val(data.warehouse);
						$('#item-code-e').val(data.item_code);
						$('#description-e').val(data.description);
						$('#stock-uom-e').val(data.stock_uom);
						$('#stock-uom-e-text').text(data.stock_uom);
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
			count_ste_for_issue('Material Receipt', '#p-returns');
			count_ste_for_issue('Material Transfer for Manufacture', '#material-manufacture');
			count_ps_for_issue();
			count_production_to_receive();

			setInterval(function () {
				count_ste_for_issue('Material Issue', '#material-issue');
				count_ste_for_issue('Material Transfer', '#material-transfer');
				count_ste_for_issue('Material Receipt', '#p-returns');
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
						$('#material-receipt').text(0);
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

			$('#myModal1').on('hide.bs.modal', function(){
				$('#update-item-modal').modal('hide');
				$('#update-ps-modal').modal('hide');
				$('#update-item-return-modal').modal('hide');
				$('#add-stock-reservation-modal').modal('hide');
				$('#cancel-stock-reservation-modal').modal('hide');
				$('#edit-stock-reservation-modal').modal('hide');
				$('#confirmation-modal').modal('hide');
				$('#sales-return-modal').modal('hide');
			});
			
			$('#myModal').on("hidden.bs.modal", function () {
				$("body").addClass("modal-open");
			});

			$('.modal').on("hidden.bs.modal", function () {
				$(this).find('form')[0].reset();
				$('.for-in-house-type').addClass('d-none');
				$('.for-online-shop-type').addClass('d-none');
				$('.for-consignment').addClass('d-none');
			});

			$(document).on('click', '.view-item-details', function(e){
				e.preventDefault();

				var item_code = $(this).data('item-code');
				var item_classification = $(this).data('item-classification');

				$('#view-item-details-modal .modal-title').text(item_code + " [" + item_classification + "]");

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

			$(document).on('click', '#stock-reservations-pagination-1 a', function(event){
				event.preventDefault();
				var item_code = $(this).closest('div').data('item-code');
				var page = $(this).attr('href').split('page=')[1];
				get_stock_reservation(item_code, page);
			});

			$(document).on('click', '#stock-reservations-pagination-2 a', function(event){
				event.preventDefault();
				var item_code = $(this).closest('div').data('item-code');
				var page = $(this).attr('href').split('page=')[1];
				get_stock_reservation(item_code, page);
			});

			$(document).on('click', '#stock-reservations-pagination-3 a', function(event){
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

