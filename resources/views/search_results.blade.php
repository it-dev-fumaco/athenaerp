@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'search_results',
])

@section('content')
<div id="overlay">
	<div class="spinner-border" role="status" id="preload-spinner">
		<span class="sr-only">Loading...</span>
	</div>
</div>
<div class="content p-0 m-0">
	<div class="content-header pt-3 p-0 m-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="container-fluid itemClassContainer overflow-auto p-0">
						@foreach($itemClass as $itemClass1)
							@php
								$item_class = explode('-', $itemClass1->item_classification);
								$abbr = $item_class[0];
								$name = $item_class[1];
							@endphp
							<a class="itemClassBubble" href="{!! count($itemClass) > 1 ?  request()->fullUrlWithQuery(['classification' => $itemClass1->item_classification]) : request()->fullUrlWithQuery(['searchString' => null, 'group' => null, 'wh' => null, 'classification' => $itemClass1->item_classification]) !!}">	
								<div class="btn-group category-btn-grp {{ request('classification') == $itemClass1->item_classification ? 'custom-border' : '' }} mb-2" role="group">
									<button type="button" class="btn category-abbr-btn font-italic"><b>{{ $abbr }}</b></button>
									<button type="button" class="btn category-name-btn">{{ $name }}</button>
								</div>
							</a>
 						@endforeach
					</div>
					<div class="card card-gray card-outline">
						<div class="card-header p-0">
							<div class="row">
								<div class="col-md-6">
									<h5 class="card-title mt-2 ml-4 font-weight-bold" style="font-size: 14px;">
										@if(request('searchString') && request('searchString') != '') 
										Search result(s) for "{{ request('searchString') }}"
									@else
										Item List
									@endif</h5>
								</div>
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-6 p-1">
											<div class="form-group m-0 w-55" id="warehouse-filter-parent" style="font-size: 11pt;">
												<select name="warehouse" id="warehouse-filter" class="form-control">
													
												</select>
											</div>
										</div>
										<div class="col-7 col-md-3 p-1 text-center">
											<div class="form-group m-0r">
												<label>
													<input type="checkbox" class="minimal" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
													
													<span style="font-size: 12px;">Remove zero-qty items</span>
												</label>
											</div>
										</div>
										<div class="col-5 col-md-3 text-right p-1">
											<span class="font-weight-bold m-1 font-responsive">TOTAL:</span>
											<span class="badge bg-info mr-2 font-responsive" style="font-size: 13pt;">{{ number_format($items->total()) }}</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="container-fluid"><!-- new table -->
							<div class="row">
							@forelse ($item_list as $row)
								<div class="col-md-6 border border-color-secondary">
									<div class="row">
										<div class="col-md-3 display-inline-block p-2 text-center">
											@php
												$img = ($row['item_image_paths']) ? "/img/" . explode('.',$row['item_image_paths'][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
											@endphp
											<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
												<img src="{{ asset('storage/') .''. $img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" class="search-img img-responsive hover">
											</a>

											<div class="modal fade" id="{{ $row['name'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-header">
														<button type="button" class="close" data-dismiss="modal" aria-label="Close">
															<span aria-hidden="true">&times;</span>
														</button>
														</div>
														<div class="modal-body">
															<div id="image-container" class="container-fluid">
																<div id="carouselExampleControls" class="carousel slide" data-interval="false">
																	<div class="carousel-inner">
																		<div class="carousel-item active">
																			<img class="d-block w-100" id="{{ $row['name'] }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
																		</div>
																		<span class='d-none' id="{{ $row['name'] }}-image-data">0</span>
																	</div>
																	<a class="carousel-control-prev" href="#carouselExampleControls" onclick="prevImg('{{ $row['name'] }}')" role="button" data-slide="prev" style="color: #000 !important">
																		<span class="carousel-control-prev-icon" aria-hidden="true"></span>
																		<span class="sr-only">Previous</span>
																	</a>
																	<a class="carousel-control-next" href="#carouselExampleControls" onclick="nextImg('{{ $row['name'] }}')" role="button" data-slide="next" style="color: #000 !important">
																		<span class="carousel-control-next-icon" aria-hidden="true"></span>
																		<span class="sr-only">Next</span>
																	</a>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
	
											<div class="text-center" style="margin: 1px;"><br/>
												<a href="#" class="view-item-details" data-item-code="{{ $row['name'] }}" data-item-classification="{{ $row['item_classification'] }}">
													<div class="btn btn-primary">
														<i class="fa fa-file"></i> <span class="d-inline d-md-none" style="font-size: 10pt">View Item Details</span>
													</div>
												</a>
												<a href="#" class="cLink d-none d-xl-inline" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
													<div class="btn btn-warning">
														<i class="fa fa-qrcode"></i>
													</div>
												</a>
											</div>	
										</div>
										<div class="col-md-9 display-inline-block p-2">
											<div class="col-md-12 p-2 text-justify">
												<span class="font-italic item-class">{{ $row['item_classification'] }} - {!! $row['item_group'] !!}</span><br/>
												<span class="text-justify item-name"><b>{{ $row['name'] }}</b> - {!! $row['description'] !!}<br/>
												<b>Part No(s)</b> {{ ($row['part_nos']) ? $row['part_nos'] : '-' }} </span>
											</div>
											<div class="d-none d-lg-block">
												<table class="table table-sm table-bordered warehouse-table">
													<tr>
														<th class="text-center wh-cell">Warehouse</th>
														<th class="text-center qtr-cell">Reserved Qty</th>
														<th class="text-center qtr-cell">Available Qty</th>
													</tr>
													@forelse($row['item_inventory'] as $inv)
														<tr>
															<td class="text-center" >
																{{ $inv['warehouse'] }}
																@if ($inv['location'])
																	<small class="text-muted font-italic"> - {{ $inv['location'] }}</small>
																@endif
															</td>
															<td class="text-center">{{ $inv['reserved_qty'] * 1 }}  {{ $inv['stock_uom'] }}</td>
															<td class="text-center">
																@if($inv['available_qty'] == 0)
																	<span class="badge badge-danger" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
																@elseif($inv['available_qty'] <= $inv['warehouse_reorder_level'])
																	<span class="badge badge-warning" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
																@else
																	<span class="badge badge-success" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
																@endif
															</td>
														</tr>
													@empty
														<tr>
															<td colspan="12" class="text-center" style="border: none;">NO WAREHOUSE ASSIGNED</td>
														</tr>
													@endforelse
												</table>
												<div class="col-md-12"><!-- View Consignment Warehouse -->
													@if(count($row['consignment_warehouses']) > 0)
													<div class="text-center">
														<a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#vcw{{ $row['name'] }}" style="font-size: 11px;">View Consignment Warehouse</a>
													</div>
		
													<div class="modal fade" id="vcw{{ $row['name'] }}" tabindex="-1" role="dialog">
														<div class="modal-dialog" role="document">
															<div class="modal-content">
																<div class="modal-header">
																	<h4 class="modal-title consignment-head">{{ $row['name'] }} - Consignment Warehouse(s) </h4>
																	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
																</div>
																<form></form>
																<div class="modal-body">
																	<table class="table table-hover m-0">
																		<col style="width: 70%;">
																		<col style="width: 30%;">
																		<tr>
																			<th class="consignment-th text-center">Warehouse</th>
																			<th class="consignment-th text-center">Available Qty</th>
																		</tr>
																		@forelse($row['consignment_warehouses'] as $con)
																		<tr>
																			<td class="consignment-name">
																				{{ $con['warehouse'] }}
																				@if ($con['location'])
																					<small class="text-muted font-italic">- {{ $con['location'] }}</small>
																				@endif
																			</td>
																			<td class="text-center">
																				<span class="badge badge-{{ ($con['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 15px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span>
																			</td>
																		</tr>
																		@empty
																		<tr>
																			<td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
																		</tr>
																		@endforelse
																	</table>
																</div>
																<div class="modal-footer">
																	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
																</div>
															</div>
														</div>
													</div>
													@endif
												</div>
											</div><!-- View Consignment Warehouse -->
										</div>
									</div>
									
									<div class="col-12 d-block d-lg-none">
										<table class="table table-sm table-bordered warehouse-table">
											<tr>
												<th class="text-center wh-cell">Warehouse</th>
												<th class="text-center qtr-cell">Reserved Qty</th>
												<th class="text-center qtr-cell">Available Qty</th>
											</tr>
											@forelse($row['item_inventory'] as $inv)
												<tr>
													<td class="text-center" >
														{{ $inv['warehouse'] }}
														{{-- @if($inv['warehouse'] == $row['default_warehouse'])
															<span class="font-italic"><small>- default</small></span>
														@endif --}}
													</td>
													<td class="text-center">{{ $inv['reserved_qty'] * 1 }}  {{ $inv['stock_uom'] }}</td>
													<td class="text-center">
														@if($inv['available_qty'] == 0)
															<span class="badge badge-danger" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
														@elseif($inv['available_qty'] <= $inv['warehouse_reorder_level'])
															<span class="badge badge-warning" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
														@else
															<span class="badge badge-success" style="font-size: 14px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
														@endif
													</td>
												</tr>
											@empty
												<tr>
													<td colspan="12" class="text-center" style="border: none;">NO WAREHOUSE ASSIGNED</td>
												</tr>
											@endforelse
										</table>
										<div class="col-md-12"><!-- View Consignment Warehouse -->
											@if(count($row['consignment_warehouses']) > 0)
											<div class="text-center">
												<a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#mob-vcw{{ $row['name'] }}" style="font-size: 11px;">View Consignment Warehouse</a>
											</div>

											<div class="modal fade" id="mob-vcw{{ $row['name'] }}" tabindex="-1" role="dialog">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h4 class="modal-title consignment-head">{{ $row['name'] }} - Consignment Warehouse(s) </h4>
															<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
														</div>
														<form></form>
														<div class="modal-body">
															<table class="table table-hover m-0">
																<col style="width: 70%;">
																<col style="width: 30%;">
																<tr>
																	<th class="consignment-th text-center">Warehouse</th>
																	<th class="consignment-th text-center">Available Qty</th>
																</tr>
																@forelse($row['consignment_warehouses'] as $con)
																<tr>
																	<td class="consignment-name">{{ $con['warehouse'] }}</td>
																	<td class="text-center">
																		<span class="badge badge-{{ ($con['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 15px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span>
																	</td>
																</tr>
																@empty
																<tr>
																	<td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
																</tr>
																@endforelse
															</table>
														</div>
														<div class="modal-footer">
															<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
														</div>
													</div>
												</div>
											</div>
											@endif
										</div>
									</div>
								</div>
							@empty
								<div class="col-md-12 text-center" style="padding: 25px;">
									<h5>No result(s) found.</h5>
								</div>
							@endforelse
							</div>
						</div><!-- new table -->

						<div class="ml-3 clearfix pagination" style="display: block;">
							{{ $items->links() }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	html,body
{
    width: 100% !important;
    height: 100% !important;
    margin: 0px !important;
    padding: 0px !important;
    overflow-x: hidden !important; 
	position:relative;
}
	.itemClassContainer{
		min-height: 1px;
		/* overflow: auto; */
		white-space: nowrap;
		z-index: -9999;
	}
	.itemClassBubble{
		color: #000;
		text-decoration: none !important;
		text-transform: none !important;
		transition: .4s;
		padding: 1px;
		background-color: rgba(255,255,255, 0);
		border: none;
	}

	.responsive-item-code{
		font-size: 14pt
	}
	.responsive-description{
		font-size: 11pt
	}
	.category-btn-grp{
		transition: .4s;
	}

	.category-btn-grp:hover{
		box-shadow: 8px 1px 12px #001F3F;
	}

	.cLink{
		text-decoration: none !important;
		text-transform: none !important;
	}

	.tbl-custom-hover:hover,
		th.hover,
		td.hover,
		tr.hoverable:hover {
			background-color: #DCDCDC;
		}

		.nohover:hover {
		background-color: #fff;
		}
	
	#warehouse-filter-parent{
		width: 200px;
		float: right;
	}
	.search-img{
		width: 100%;
		max-width: 100%;
	}
	.search-thumbnail{
		width: 200px;
	}
	.item-class{
		font-size: 12px;
	}
	.item-name, .warehouse-table{
		font-size: 13px;
	}
	.wh-cell{
		width: 50%;
	}
	.qty-cell{
		width: 25%;
	}
	.pagination{
		font-size: 15px;
	}
	.category-abbr-btn{
		background-color: #001F3F;
		color: #fff;
		border-radius: 5px 0 0 5px;
		font-size: 20px;
	}
	.category-name-btn{
		background-color: #fff;
		border-radius: 0 5px 5px 0
	}
	.stock-ledger-table-font{
		font-size: 11pt;
	}
	@media (max-width: 575.98px) {
        .font-responsive, .responsive-item-code, .stock-ledger-table-font{
			font-size: 10pt !important;
		}
		.item-class, .item-name{
			font-size: 9pt !important;
		}
		#warehouse-filter-parent{
			width: 90% !important;
			float: none;
			margin-left: 5% !important;
		}
		.search-img, .search-thumbnail{
			max-width: 220px !important;
		}
		.consignment-head{
			font-size: 11pt;
		}
		.wh-cell{
			width: 40% !important;
		}
		.qty-cell{
			width: 30% !important;
		}
		.badge, .consignment-name, .warehouse-table, .consignment-th{
			font-size: 8pt !important;
		}
		.pagination{
			font-size: 9pt !important;
			padding: 0 !important;
			margin: 0 auto !important;
		}
		.page-link{
			padding: 10px !important;
		}
		.category-abbr-btn{
			font-size: 16px;
		}
    }
  	@media (max-width: 767.98px) {
        .font-responsive, .responsive-description, .stock-ledger-table-font{
			font-size: 10pt !important;
		}
		#warehouse-filter-parent{
			width: 90% !important;
			float: none;
			margin-left: 5% !important;
		}
		.search-img, .search-thumbnail{
			max-width: 220px !important;
		}
		.consignment-head{
			font-size: 11pt;
		}
		.wh-cell{
			width: 40% !important;
		}
		.qty-cell{
			width: 30% !important;
		}
		.badge, .consignment-name, .warehouse-table, .consignment-th{
			font-size: 8pt !important;
		}
		.pagination{
			font-size: 9pt !important;
			padding: 0 !important;
			margin: 0 auto !important;
		}
		.page-link{
			padding: 10px !important;
		}
		.category-abbr-btn{
			font-size: 16px;
		}
    }
	.custom-border{
		box-shadow: 8px 1px 12px #001F3F;
	}
	
	#overlay{
		width:100%;
		height:100%;  
		position: absolute;
		top: 0;
		left: 0;
		z-index:1;
		background:rgba(255,255,255, 1);
	}
	#preload-spinner{
		position: absolute;
		top: 50vh !important;
    	left: 48% !important;
		z-index:1;
	}
</style>
<script>
	$("#preload-spinner").delay(2000).fadeOut("slow");
	$("#overlay").delay(2000).fadeOut("slow");

	function nextImg(item_code){
		var current_img = $('#'+item_code+'-image-data').text();
		$.ajax({
			type: "GET",
			url: "/search_results_images",
			data: { 
				img_key: parseInt(current_img) + 1,
				item_code: item_code,
				dir: 'next'
			},
			success: function (data) {
				$('#'+data.item_code+'-image').attr('src', data.image_path);
				$('#'+data.item_code+'-image').prop('alt', data.alt);
				$('#'+data.item_code+'-image-data').text(data.current_img_key);
			}
		});
	}

	function prevImg(item_code){
		var current_img = $('#'+item_code+'-image-data').text();
		$.ajax({
			type: "GET",
			url: "/search_results_images",
			data: { 
				img_key: parseInt(current_img) - 1,
				item_code: item_code,
				dir: 'prev'
			},
			success: function (data) {
				$('#'+data.item_code+'-image').attr('src', data.image_path);
				$('#'+data.item_code+'-image').prop('alt', data.alt);
				$('#'+data.item_code+'-image-data').text(data.current_img_key);
			}
		});
	}
</script>
@endsection