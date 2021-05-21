@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row mt-3">
				<div class="col-sm-12">
					<div class="card card-gray card-outline">
						<div class="card-header p-0">
							<div class="row m-0">
								<div class="col-md-6 p-2">
									<h2 class=" m-0 font-weight-bold text-uppercase">Warehouse Dashboard</h2>
								</div>
								<div class="col-md-6 p-2">
									<div class="float-right m-0 p-0">
										<span class="h4 mr-4">{{ date('l, M d, Y') }}</span>
										<span class="h4 mr-3" id="current-time">--:--:-- --</span>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body p-0" style="min-height: 900px;">
							<div class="nav-tabs-custom m-3">
								<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" id="pills-1-tab" data-toggle="pill" href="#pills-1" role="tab" aria-controls="pills-1" aria-selected="true">Task(s) Today</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="pills-2-tab" data-toggle="pill" href="#pills-2" role="tab" aria-controls="pills-2" aria-selected="false">Stock Level Alert</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="pills-3-tab" data-toggle="pill" href="#pills-3" role="tab" aria-controls="pills-3" aria-selected="false">Stock Adjustments</a>
									</li>
								</ul>
								
								  <div class="tab-content" id="pills-tabContent">
									<div class="tab-pane fade show active" id="pills-1" role="tabpanel" aria-labelledby="pills-1-tab">
										<div class="row">
											<div class="col-md-6 border-right">
												<div class="container pr-5 pl-5">
													<h3 class="text-uppercase text-center mb-3">Incoming Stocks</h3>
												<div class="row">
													<div class="col-md-6 pr-5 pl-5">
														<div class="card card-info card-outline">
															<div class="card-header h5 pt-1 pb-1 pl-4">Returns</div>
															<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="p-returns">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-returns">-</span></span>
																</h4>
															</div>
														</div>
													</div>
													<div class="col-md-6 pr-5 pl-5">
														<div class="card card-info card-outline">
															<div class="card-header h5 pt-1 pb-1 pl-4">Feedback</div>
															<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="material-receipt">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-receipt">-</span></span>
																</h4>
															</div>
														</div>
													</div>
													<div class="col-md-6 pr-5 pl-5 pt-4">
														<div class="card card-info card-outline">
															<div class="card-header h5 pt-1 pb-1 pl-4">Internal Transfers</div>
															<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="material-transfer">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-transfer">-</span></span>
																</h4>
															</div>
														</div>
													</div>
													<div class="col-md-6 pr-5 pl-5 pt-4">
														<div class="card card-info card-outline">
															<div class="card-header h5 pt-1 pb-1 pl-4">PO Receipts</div>
															<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="p-purchase-receipts">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-purchase-receipts">-</span></span>
																</h4>
															</div>
														</div>
													</div>
												</div>
												</div>
												
												
											</div>
											<div class="col-md-6 border-left">
												<div class="container pr-5 pl-5">
													<h3 class="text-uppercase text-center mb-3">Outgoing Stocks</h3>
													<div class="row">
														<div class="col-md-6 pr-5 pl-5">
															<div class="card card-info card-outline">
																<div class="card-header h5 pt-1 pb-1 pl-4">Production Withdrawals</div>
																<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="material-manufacture">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-withdrawals">-</span></span>
																</h4>
															</div>
															</div>
														</div>
														<div class="col-md-6 pr-5 pl-5">
															<div class="card card-info card-outline">
																<div class="card-header h5 pt-1 pb-1 pl-4">Material Issue</div>
																<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="material-issue">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-issues">-</span></span>
																</h4>
															</div>
															</div>
														</div>
														<div class="col-md-6 pr-5 pl-5 pt-4">
															<div class="card card-info card-outline">
																<div class="card-header h5 pt-1 pb-1 pl-4">Picking / For Delivery</div>
																<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="picking-slip">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-picking-slips">-</span></span>
																</h4>
															</div>
															</div>
														</div>
														<div class="col-md-6 pr-5 pl-5 pt-4">
															<div class="card card-info card-outline">
																<div class="card-header h5 pt-1 pb-1 pl-4">Order Replacement</div>
																<div class="card-body pt-2 pb-2">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 pt-2 pb-2 badge badge-pill badge-warning">Pending:<span class="ml-3" id="p-replacements">-</span></span>
																</h3>
																<h4 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-replacements">-</span></span>
																</h4>
															</div>
															</div>
														</div>
													</div>
												</div>
												
											</div>
										</div>
									</div>
									<div class="tab-pane fade" id="pills-2" role="tabpanel" aria-labelledby="pills-2-tab">...</div>
									<div class="tab-pane fade" id="pills-3" role="tabpanel" aria-labelledby="pills-3-tab">...</div>
								  </div>
							
					
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