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
								<div class="col-md-12 col-xl-6 col-lg-12">
									<div class="container pr-0 pl-0">
										<div class="row">
											<div class="col-md-12 col-xl-10 col-lg-12 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<h5 class="text-uppercase text-center mb-2 font-italic">Incoming Stocks</h5>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<div class="info-box">
													<span class="info-box-icon bg-primary" style="width: 30%;"><i class="fas fa-undo"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Returns</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-returns">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/production_to_receive" class="text-dark">
												<div class="info-box">
													<span class="info-box-icon bg-info" style="width: 30%;"><i class="far fa-check-circle"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Feedback</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-receipt">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<a href="/material_transfer" class="text-dark">
												<div class="info-box">
													<span class="info-box-icon bg-secondary" style="width: 30%;"><i class="fas fa-exchange-alt"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Internal Transfers</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-transfer">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<div class="info-box">
													<span class="info-box-icon bg-maroon" style="width: 30%;"><i class="fas fa-boxes"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">PO Receipts</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-purchase-receipts">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>	
								<div class="col-md-12 col-xl-6 col-lg-12">
									<div class="container pr-0 pl-0">
										<div class="row">
											<div class="col-md-12 col-xl-10 col-lg-12 pr-4 pl-4">
												<h5 class="text-uppercase text-center mb-2 font-italic">Outgoing Stocks</h5>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/material_transfer_for_manufacture" class="text-dark">
												<div class="info-box">
													<span class="info-box-icon bg-olive" style="width: 30%;"><i class="fas fa-tasks"></i></span>
														<div class="info-box-content text-truncate d-inline-block">
														<span class="info-box-text font-weight-bold text-uppercase"><span class="d-md-inline-block d-lg-none d-xl-inline-block">Production</span> Withdrawals</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-manufacture">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/material_issue" class="text-dark">
												<div class="info-box">
													<span class="info-box-icon bg-indigo" style="width: 30%;"><i class="fas fa-dolly"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Material Issue</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-issue">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/picking_slip" class="text-dark">
												<div class="info-box">
													<span class="info-box-icon bg-teal" style="width: 30%;"><i class="fas fa-truck"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Picking / For Delivery</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="picking-slip">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<div class="info-box">
													<span class="info-box-icon bg-orange" style="width: 30%;"><i class="fas fa-retweet"></i></span>
													<div class="info-box-content">
														<span class="info-box-text font-weight-bold text-uppercase">Order Replacement</span>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle align-self-center w-100">
																<h3 class="custom-font m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-replacements">-</span>
																</h3>
																<h5 class="mb-1">
																	<small class="pr-2 pl-2">Pending</small>
																</h5>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12 col-xl-9 col-lg-12 pl-lg-4 pr-lg-4 pr-xl-2 pl-xl-5 mt-2">
									<div class="card card-danger">
										<div class="card-header">
											<h3 class="card-title text-uppercase font-weight-bold">Stock Level Alerts</h3>
							
											<div class="card-tools">
											  <button type="button" class="btn btn-tool" data-card-widget="collapse">
												<i class="fas fa-minus"></i>
											  </button>
											</div>
											<!-- /.card-tools -->
										  </div>
										<div class="card-body p-0" id="low-level-stock-table"></div>
									</div>	
								</div>
								<div class="col-md-12 col-xl-3 col-lg-12 pl-5 pr-5">
									<div class="row mt-3">
										<div class="col-xl-12 col-lg-6 col-md-12 col-sm-12 col-12">
											<div class="info-box bg-info">
												<span class="info-box-icon"><i class="far fa-bookmark"></i></span>
												<div class="info-box-content">
													<span class="info-box-text">Bookmarks</span>
													<span class="info-box-number">41,410</span>

													<div class="progress">
														<div class="progress-bar" style="width: 70%"></div>
													</div>
													<span class="progress-description">70% Increase in 30 Days</span>
												</div>
										<!-- /.info-box-content -->
										</div>
										<!-- /.info-box -->
									</div>
									<!-- /.col -->
									<div class="col-xl-12 col-lg-3 col-md-12 col-sm-12 col-12">
										<div class="info-box bg-success">
										<span class="info-box-icon"><i class="far fa-thumbs-up"></i></span>

										<div class="info-box-content">
											<span class="info-box-text">Likes</span>
											<span class="info-box-number">41,410</span>

											<div class="progress">
											<div class="progress-bar" style="width: 70%"></div>
											</div>
											<span class="progress-description">
											70% Increase in 30 Days
											</span>
										</div>
										<!-- /.info-box-content -->
										</div>
										<!-- /.info-box -->
									</div>
									<div class="col-xl-12  col-lg-3 col-md-12 col-sm-12 col-12">
										<div class="card card-danger">
											<div class="card-header">
											<h3 class="card-title">Donut Chart</h3>
							
											<div class="card-tools">
												<button type="button" class="btn btn-tool" data-card-widget="collapse">
												<i class="fas fa-minus"></i>
												</button>
												<button type="button" class="btn btn-tool" data-card-widget="remove">
												<i class="fas fa-times"></i>
												</button>
											</div>
											</div>
											<div class="card-body">
											<canvas id="donutChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
											</div>
											<!-- /.card-body -->
										</div>
										<!-- /.card -->
							
									</div>
									<!-- /.col -->
									<!-- /.col -->
									<!-- /.col -->
									</div>
									<!-- /.row -->

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
		font-size: 1.3rem;
	}
}

/* Large devices (laptops/desktops, 992px and up) */
@media only screen and (min-width: 992px) {
	.custom-font{
		font-size: 1.5rem;
	}
}

/* Extra large devices (large laptops and desktops, 1200px and up) */
@media only screen and (min-width: 1200px) {
	.custom-font{
		font-size: 2rem;
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