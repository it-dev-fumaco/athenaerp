@extends('layout', [
    'namePage' => 'Dashboard',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content bg-white">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-2">
				<div class="col-sm-12">
					@if (Auth::user()->user_group == 'Director')
					<ul class="nav nav-pills mb-2 mt-0">
						<li class="nav-item p-0">
							<a class="nav-link active font-responsive text-center" href="/">In House Warehouse Transaction</a>
						</li>
						<li class="nav-item p-0">
							<a class="nav-link font-responsive text-center" href="/consignment_dashboard">Consignment Dashboard</a>
						</li>
					</ul>
					@endif
					<div class="card bg-light">
						<div class="card-body p-0" style="min-height: 900px;">
							<div id="app" data-page="dashboard"></div>
							<div class="row">
								<div class="col-md-10 offset-md-1">
									<div class="row">
										<div class="col-xl-8">
											<div class="card card-danger card-outline">
												<div class="card-header d-flex p-0 justify-content-between">
													<ul class="nav nav-pills p-2">
													  <li class="nav-item"><a class="font-responsive nav-link active" href="#tab_1-1" data-toggle="tab">
														<i class="fas fa-exclamation-triangle"></i> Stock Level Alert</a>
													</li>
													  <li class="nav-item"><a class="font-responsive nav-link" href="#tab_2-1" data-toggle="tab">
														<i class="fas fa-list-alt"></i> Stock Movement(s)</a>
													</li>
													  <li class="nav-item"><a class="font-responsive nav-link purchase-receipt-trigger" href="#tab_3-1" data-toggle="tab">
														<i class="fas fa-list-alt"></i> Recently Received Item(s)</a>
													</li>
													</ul>
													@if (in_array(Auth::user()->user_group, ['Manager', 'Director']))
													<div class="ml-auto p-3">
														<a href="/search_item_cost" class="btn btn-sm btn-secondary">Register Item Cost</a>
													</div>
													@endif
												</div>
												<div class="card-body p-0">
													<div class="tab-content">
														<div class="tab-pane font-responsive active" id="tab_1-1">
															<div id="dashboard-low-stock"></div>
														</div>

														<div class="tab-pane font-responsive" id="tab_2-1">
															<div id="dashboard-athena-logs"></div>
														</div>

														<div class="tab-pane font-responsive" id="tab_3-1">
															<div id="dashboard-recently-received" class="overflow-auto"></div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4 min-width-0">
											<div class="row">
												<div class="col-xl-12 min-width-0">
													<div class="card card-info card-outline font-responsive inv-accuracy-card">
														<div class="card-header d-flex flex-nowrap justify-content-between align-items-center">
															<h3 class="card-title font-weight-bold text-truncate mb-0 mr-2">Inventory Accuracy</h3>
															<div class="card-tools flex-shrink-0">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														<div class="card-body p-2 overflow-x-auto">
															<div id="dashboard-inv-accuracy" data-initial-month="{{ (int) now()->format('n') }}" data-initial-year="{{ date('Y') }}"></div>
														</div>
													</div>
												</div>
												<div class="col-xl-12">
													<div class="card card-success card-outline font-responsive">
														<div class="card-header">
															<h3 class="card-title font-weight-bold">Reserved Items</h3>
															<div class="card-tools">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														{{-- <div class="card-body pt-0 pb-0 pl-1 pr-1" id="recently-added-items-div"></div> --}}
														<div class="card-body pt-0 pb-0 pl-1 pr-1" id="dashboard-reserved-items"></div>
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
	$(document).ready(function(){

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

@endsection