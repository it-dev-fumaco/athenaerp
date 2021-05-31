@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-3">
				<div class="col-sm-12">
					<div class="card bg-light">
						<div class="card-body p-0" style="min-height: 900px;">
							<div class="row pt-2 m-0">
								<div class="col-md-5 offset-md-1 col-xl-6 col-lg-5 offset-xl-0 offset-lg-1">
									<div class="container pr-0 pl-0">

										

										<h5 class="text-uppercase text-center mb-2 font-italic">Incoming Stocks</h5>

										<div class="row m-0 pl-2 pr-2">
											<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-primary" style="width: 35%;"><i class="fas fa-undo"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Returns</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-returns">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-returns">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-info" style="width: 35%;"><i class="far fa-check-circle"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Feedback</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-receipt">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-receipt">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-secondary" style="width: 35%;"><i class="fas fa-exchange-alt"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Internal Transfers</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-transfer">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-transfer">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-maroon" style="width: 35%;"><i class="fas fa-boxes"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">PO Receipts</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-purchase-receipts">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-purchase-receipts">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
											</div>
										</div>

								</div>
									
									
								</div>
								<div class="col-md-5 col-xl-6 col-lg-5">
									<div class="container pr-0 pl-0">
										<h5 class="text-uppercase text-center mb-2 font-italic">Outgoing Stocks</h5>
										<div class="row m-0 pl-2 pr-2">
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-olive" style="width: 35%;"><i class="fas fa-tasks"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase"><span class="d-md-inline-block d-lg-none d-xl-inline-block">Production</span> Withdrawals</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-manufacture">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-withdrawals">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
											
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-indigo" style="width: 35%;"><i class="fas fa-dolly"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Material Issue</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-issue">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
														
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-issues">-</span></span>
															</h5>
														</div>
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
												
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-teal" style="width: 35%;"><i class="fas fa-truck"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Picking / For Delivery</span>
													  <div class="d-flex flex-row flex-wrap">
														
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="picking-slip">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-picking-slips">-</span></span>
															</h5>
														
														</div>
														
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
												
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="info-box">
													<span class="info-box-icon bg-orange" style="width: 35%;"><i class="fas fa-retweet"></i></span>
									  
													<div class="info-box-content">
													  <span class="info-box-text font-weight-bold text-uppercase">Order Replacement</span>
													  <div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle align-self-center w-100">
															<h3 class="custom-font m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-replacements">-</span>
															</h3>
															<h5 class="mb-1">
																<span class="pr-2 pl-2">Pending</span>
															</h5>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle align-self-center w-100">
															<h5 class="mb-1">
																<span class="pr-3 pl-3 badge badge-pill badge-success">Done:<span class="ml-3" id="d-replacements">-</span></span>
															</h5>
														</div>
														
													</div>
													</div>
													<!-- /.info-box-content -->
												</div>
												
											</div>
										</div>
									</div>
									
								</div>
							</div>
							<div class="row">
								<div class="col-md-12 pl-5 pr-5 pt-3">
									<h5 class="text-uppercase text-cent1er mb-3 font-weight-bold">Low Stock Level</h5>
									<div class="card card-secondary card-outline">
										<div class="card-body p-0" id="low-level-stock-table">
											
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

<style>
	/* Extra small devices (phones, 600px and down) */
@media only screen and (max-width: 600px) {
	.custom-font{
	
	}
}

/* Small devices (portrait tablets and large phones, 600px and up) */
@media only screen and (min-width: 600px) {
	.custom-font{
		
	}
}

/* Medium devices (landscape tablets, 768px and up) */
@media only screen and (min-width: 768px) {
	.custom-font{
		font-size: 2rem;
	}
}

/* Large devices (laptops/desktops, 992px and up) */
@media only screen and (min-width: 992px) {
	.custom-font{
		font-size: 2rem;
	}
}

/* Extra large devices (large laptops and desktops, 1200px and up) */
@media only screen and (min-width: 1200px) {
	.custom-font{
		font-size: 3rem;
	}
}
</style>


@endsection

@section('script')
<script>
	
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
				$('#d-returns').text(data.d_returns);
				$('#p-returns').text(data.p_returns);
				$('#d-material-transfer').text(data.d_internal_transfers);
				$('#d-picking-slips').text(data.d_picking_slips);
				$('#d-withdrawals').text(data.d_withdrawals);
				$('#d-material-issues').text(data.d_material_issues);
			}
		});
	}
</script>

@endsection