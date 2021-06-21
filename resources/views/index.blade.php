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
												<h6 class="text-uppercase text-center mb-2 font-italic">Check In Item(s)</h6>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
												<a href="/returns" class="text-dark">
												<div class="info-box bg-primary p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-undo"></i></span>
													<div class="info-box-content pt-1 pb-1">
														<span class="info-box-text font-weight-bold text-uppercase">Sales Returns</span>
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
												</a>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/production_to_receive" class="text-dark">
												<div class="info-box bg-info p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="far fa-check-circle"></i></span>
													<div class="info-box-content pt-1 pb-1">
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
												<div class="info-box bg-secondary p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-exchange-alt"></i></span>
													<div class="info-box-content pt-1 pb-1">
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
												<a href="/receipts" class="text-dark">
												<div class="info-box bg-maroon p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-boxes"></i></span>
													<div class="info-box-content pt-1 pb-1">
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
												</a>
											</div>
										</div>
									</div>
								</div>	
								<div class="col-md-12 col-xl-6 col-lg-12">
									<div class="container pr-0 pl-0">
										<div class="row">
											<div class="col-md-12 col-xl-10 col-lg-12 pr-4 pl-4">
												<h6 class="text-uppercase text-center mb-2 font-italic">Check Out Item(s)</h6>
											</div>
											<div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
												<a href="/material_transfer_for_manufacture" class="text-dark">
												<div class="info-box bg-olive p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-tasks"></i></span>
														<div class="info-box-content text-truncate d-inline-block pt-1 pb-1">
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
												<div class="info-box bg-indigo p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-dolly"></i></span>
													<div class="info-box-content pb-1 pt-1 text-truncate d-inline-block">
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
												<div class="info-box bg-teal p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-truck"></i></span>
													<div class="info-box-content pt-1 pb-1 text-truncate d-inline-block">
														<span class="info-box-text font-weight-bold text-uppercase">Deliveries</span>
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
												<a href="/replacements" class="text-dark">
												<div class="info-box bg-orange p-0">
													<span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-retweet"></i></span>
													<div class="info-box-content pt-1 pb-1">
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
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-10 offset-md-1">
									<div class="row">
										<div class="col-xl-8">
											<div class="card card-danger card-outline">
												<div class="card-header d-flex p-0">
													<ul class="nav nav-pills p-2">
													  <li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab"><i class="fas fa-exclamation-triangle"></i> Stock Level Alert</a></li>
													  <li class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab"><i class="fas fa-list-alt"></i> Stock Movement(s)</a></li>
													</ul>
												</div>
												<div class="card-body p-0">
													<div class="tab-content">
													  <div class="tab-pane active" id="tab_1">
														<div id="low-level-stock-table" style="margin-top: -32px;"></div>
													  </div>
													  
													  <div class="tab-pane" id="tab_2">
														<div id="athena-logs-table"></div>
														<ul class="pagination pagination-month justify-content-center mt-3" id="athena-logs-pagination">
															@for ($i = 1; $i < 13; $i++)
															@if($i == 1)
															<li class="page-item prev {{ (now()->month == 1) ? 'disabled' : '' }}"><a class="page-link" href="#">«</a></li>
															@endif
															<li class="page-item month {{ (now()->month == $i) ? 'active' : '' }}">
																<a class="page-link" href="#" data-month="{{ $i }}">
																	<p class="page-month">{{ date("M", mktime(0, 0, 0, $i, 1, now()->year)) }}</p>
																	<p class="page-year">{{ now()->year }}</p>
																</a>
															</li>
															@if($i == 12)
															<li class="page-item next {{ (now()->month == 12) ? 'disabled' : '' }}"><a class="page-link" href="#">»</a></li>
															@endif
															@endfor
														</ul>
													  </div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-4">
											<div class="row">
												<div class="col-xl-12">
													<div class="card card-info card-outline">
														<div class="card-header">
															<h3 class="card-title font-weight-bold">Inventory Accuracy</h3>
															<div class="card-tools">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														<div class="card-body p-2">
															<div class="box">
																<div class="text-center">Monthly Inventory Accuracy: 
																	<select style="width: 15%;" id="monthly-inv-month" class="filter-inv-accuracy">
																		<option value="">-</option>
																		<option value="01" {{ date('m') == '01' ? 'selected' : '' }}>Jan</option>
																		<option value="02" {{ date('m') == '02' ? 'selected' : '' }}>Feb</option>
																		<option value="03" {{ date('m') == '03' ? 'selected' : '' }}>Mar</option>
																		<option value="04" {{ date('m') == '04' ? 'selected' : '' }}>Apr</option>
																		<option value="05" {{ date('m') == '05' ? 'selected' : '' }}>May</option>
																		<option value="06" {{ date('m') == '06' ? 'selected' : '' }}>Jun</option>
																		<option value="07" {{ date('m') == '07' ? 'selected' : '' }}>Jul</option>
																		<option value="08" {{ date('m') == '08' ? 'selected' : '' }}>Aug</option>
																		<option value="09" {{ date('m') == '09' ? 'selected' : '' }}>Sept</option>
																		<option value="10" {{ date('m') == '10' ? 'selected' : '' }}>Oct</option>
																		<option value="11" {{ date('m') == '11' ? 'selected' : '' }}>Nov</option>
																		<option value="12" {{ date('m') == '12' ? 'selected' : '' }}>Dec</option>
																	</select>
																	<select style="width: 15%;" id="monthly-inv-year" class="filter-inv-accuracy">
																		<option value="2018" {{ date('Y') == 2018 ? 'selected' : '' }}>2018</option>
																		<option value="2019" {{ date('Y') == 2019 ? 'selected' : '' }}>2019</option>
																		<option value="2020" {{ date('Y') == 2020 ? 'selected' : '' }}>2020</option>
																		<option value="2021" {{ date('Y') == 2021 ? 'selected' : '' }}>2021</option>
																		<option value="2022" {{ date('Y') == 2022 ? 'selected' : '' }}>2022</option>
																	</select>
																</div>
																<table class="table table-bordered mt-2" id="monthly-inv-chart">
																	<col style="width: 30%;">
																	<col style="width: 30%;">
																	<col style="width: 20%;">
																	<col style="width: 20%;">
																	<thead>
																		<tr>
																			<th class="text-center pr-0 pl-0 align-middle">Classification</th>
																			<th class="text-center pr-0 pl-0 align-middle">Warehouse</th>
																			<th class="text-center pr-0 pl-0 align-middle">Accuracy</th>
																			<th class="text-center pr-0 pl-0 align-middle">Target</th>
																		</tr>
																	</thead>
																	<tbody class="item-classification"></tbody>
																</table>
															</div>
														</div>
													</div>
												</div>
												<div class="col-xl-12">
													<div class="card card-success card-outline">
														<div class="card-header">
															<h3 class="card-title font-weight-bold">Recently Added Items</h3>
															<div class="card-tools">
																<button type="button" class="btn btn-tool" data-card-widget="collapse">
																	<i class="fas fa-minus"></i>
																</button>
																<button type="button" class="btn btn-tool" data-card-widget="remove">
																	<i class="fas fa-times"></i>
																</button>
															</div>
														</div>
														<div class="card-body pt-0 pb-0 pl-1 pr-1" id="recently-added-items-div"></div>
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
		recently_added_items();
		function recently_added_items(){
				$.ajax({
					type: "GET",
					url: "/get_recently_added_items",
					success: function (data) {
						$('#recently-added-items-div').html(data);
					}
				});
			}

		$('.filter-inv-accuracy').on('change', function(){
      monthlyInvAccuracyTbl();
   });


		monthlyInvAccuracyTbl();
   function monthlyInvAccuracyTbl(){
      var year = $('#monthly-inv-year').val();
      var month = $('#monthly-inv-month').val();

      $('#monthly-inv-chart .item-classification').empty();
      $.ajax({
         url: "/inv_accuracy/"+ year,
         method: "GET",
         success: function(data) {
            var row = '';
            $.each(data, function(i, d){
               if (d.month_no == month) {
                  if (d.audit_per_month.length > 0) {

                     $.each(d.audit_per_month, function(i, v){
                        var target = parseFloat(v.percentage_sku);
                        var percentage = parseFloat(v.average_accuracy_rate);
                        stat = (percentage >= v.percentage_sku) ? 'fa-thumbs-up' : 'fa-thumbs-down';
                        color = (percentage >= v.percentage_sku) ? 'green' : 'red';
                        row += '<tr>' +
                           '<td>' + v.item_classification + '</td>' +
                           '<td>' + v.warehouse + '</td>' + 
                           '<td class="text-center"><i class="fa '+stat+'" style="color:'+color+';"></i> ' + percentage.toFixed(2) + '%</td>' + 
                           '<td class="text-center">' + target.toFixed(2) + '%</td>' +
                           '</tr>';
                     });
                  }else{
                     row += '<tr>' +
                           '<td colspan="4" class="text-center">No Records Found.</td>' +
                           '</tr>';

                  }
               }
            });

            $('#monthly-inv-chart .item-classification').append(row);
         },
         error: function(data) {
            alert('Error fetching data!');
         }
      });
   }
	});
</script>

@endsection