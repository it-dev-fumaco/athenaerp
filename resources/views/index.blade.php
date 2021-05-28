@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row pt-3">
				<div class="col-sm-12">
					<div class="card card-outline">
						<div class="card-body p-0" style="min-height: 900px;">
							<div class="row pt-2">
								<div class="col-md-6">
									<div class="container pr-0 pl-0">
										<h5 class="text-uppercase text-center mb-3 font-italic">Incoming Stocks</h5>
									<div class="row m-0 pl-2 pr-2">
										<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
											<div class="card card-info card-outline">
												<div class="card-header h5 pt-1 pb-1 pl-4">Returns</div>
												<div class="card-body p-2">
													<div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle text-center align-self-center w-50">
															<img src="{{ asset('storage/icon-dashboard/return.jpg') }}" style="width: 40%;">
														</div>
														<div class="p-0 align-middle text-center align-self-center w-50">
															<h3 class="display-4 m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-returns">-</span>
															</h3>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h5 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-returns">-</span></span>
															</h5>
														</div>
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h3 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
															</h3>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
											<div class="card card-info card-outline">
												<div class="card-header h5 pt-1 pb-1 pl-4">Feedback</div>
												<div class="card-body p-2">
													<div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle text-center align-self-center w-50">
															<img src="{{ asset('storage/icon-dashboard/feedback.png') }}" style="width: 35%;">
														</div>
														<div class="p-0 align-middle text-center align-self-center w-50">
															<h3 class="display-4 m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-receipt">-</span>
															</h3>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h5 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-receipt">-</span></span>
															</h5>
														</div>
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h3 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
															</h3>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
											<div class="card card-info card-outline">
												<div class="card-header h5 pt-1 pb-1 pl-4">Internal Transfers</div>
												<div class="card-body p-2">
													<div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle text-center align-self-center w-50">
															<img src="{{ asset('storage/icon-dashboard/transfer.png') }}" style="width: 40%;">
														</div>
														<div class="p-0 align-middle text-center align-self-center w-50">
															<h3 class="display-4 m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-transfer">-</span>
															</h3>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h5 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-transfer">-</span></span>
															</h5>
														</div>
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h3 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
															</h3>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-12 col-xl-6 col-lg-12 pl-5 pr-md-3 pr-lg-4 pr-xl-5">
											<div class="card card-info card-outline">
												<div class="card-header h5 pt-1 pb-1 pl-4">PO Receipts</div>
												<div class="card-body p-2">
													<div class="d-flex flex-row flex-wrap">
														<div class="p-0 align-middle text-center align-self-center w-50">
															<img src="{{ asset('storage/icon-dashboard/receive_po.png') }}" style="width: 40%;">
														</div>
														<div class="p-0 align-middle text-center align-self-center w-50">
															<h3 class="display-4 m-0 p-1">
																<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-purchase-receipts">-</span>
															</h3>
														</div>
													</div>
													<div class="d-flex flex-row flex-wrap">
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h5 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-purchase-receipts">-</span></span>
															</h5>
														</div>
														<div class="p-1 align-middle text-center align-self-center w-50">
															<h3 class="mb-1">
																<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
															</h3>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
									
									
								</div>
								<div class="col-md-6">
									<div class="container pr-0 pl-0">
										<h5 class="text-uppercase text-center mb-3 font-italic">Outgoing Stocks</h5>
										<div class="row m-0 pl-2 pr-2">
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="card card-info card-outline">
													<div class="card-header h5 pt-1 pb-1 pl-4">Production Withdrawals</div>
													<div class="card-body p-2">
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle text-center align-self-center w-50">
																<img src="{{ asset('storage/icon-dashboard/production.png') }}" style="width: 23%;">
															</div>
															<div class="p-0 align-middle text-center align-self-center w-50">
																<h3 class="display-4 m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-manufacture">-</span>
																</h3>
															</div>
														</div>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h5 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-withdrawals">-</span></span>
																</h5>
															</div>
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
																</h3>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="card card-info card-outline">
													<div class="card-header h5 pt-1 pb-1 pl-4">Material Issue</div>
													<div class="card-body p-2">
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle text-center align-self-center w-50">
																<img src="{{ asset('storage/icon-dashboard/issue.png') }}" style="width: 38%;">
															</div>
															<div class="p-0 align-middle text-center align-self-center w-50">
																<h3 class="display-4 m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="material-issue">-</span>
																</h3>
															</div>
														</div>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h5 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-material-issues">-</span></span>
																</h5>
															</div>
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
																</h3>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="card card-info card-outline">
													<div class="card-header h5 pt-1 pb-1 pl-4">Picking / For Delivery</div>
													<div class="card-body p-2">
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle text-center align-self-center w-50">
																<img src="{{ asset('storage/icon-dashboard/delivery.jpg') }}" style="width: 50%;">
															</div>
															<div class="p-0 align-middle text-center align-self-center w-50">
																<h3 class="display-4 m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="picking-slip">-</span>
																</h3>
															</div>
														</div>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h5 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-picking-slips">-</span></span>
																</h5>
															</div>
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
																</h3>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="col-md-12 col-xl-6 col-lg-12 pr-5 pl-md-3 pl-lg-4 pl-xl-5">
												<div class="card card-info card-outline">
													<div class="card-header h5 pt-1 pb-1 pl-4">Order Replacement</div>
													<div class="card-body p-2">
														<div class="d-flex flex-row flex-wrap">
															<div class="p-0 align-middle text-center align-self-center w-50">
																<img src="{{ asset('storage/icon-dashboard/replacement.png') }}" style="width: 30%;">
															</div>
															<div class="p-0 align-middle text-center align-self-center w-50">
																<h3 class="display-4 m-0 p-1">
																	<span class="ml-3" style="font: Arial; font-weight: 900;" id="p-replacements">-</span>
																</h3>
															</div>
														</div>
														<div class="d-flex flex-row flex-wrap">
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h5 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-success">Done:<span class="ml-3" id="d-replacements">-</span></span>
																</h5>
															</div>
															<div class="p-1 align-middle text-center align-self-center w-50">
																<h3 class="mb-1">
																	<span class="pr-4 pl-4 badge badge-pill badge-warning">Pending</span>
																</h3>
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