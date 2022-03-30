@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'search_results',
])

@section('content')
<div class="content p-0 m-0">
	<div class="content-header p-0 m-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="row">
						<div class="col-12">
							<div id="accordion" class="col-12 card card-gray card-outline m-0 p-0">
								<div class="card m-0">
									<div class="row">
										<div class="col-8">
											@php
												$promodiser_restriction = Auth::user()->user_group == 'Promodiser' ? 1 : 0;
											@endphp
											<button class="btn text-left pt-0 d-inline d-xl-none" data-toggle="modal" data-target="#mobile-filters-modal">
												<p class="card-title mt-2 ml-4 font-weight-bold" style="font-size: 10pt !important">
													<i class="fa fa-bars"></i>&nbsp;Filters
												</p>
											</button>

											<button class="btn text-left pt-0" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
												<p class="card-title mt-2 ml-4 font-weight-bold" style="font-size: 10pt !important">
													<i class="fa fa-plus"></i>&nbsp;Advanced Filters
												</p>
											</button>

											<!-- Filters Modal -->
											<div class="modal left fade" id="mobile-filters-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-body">
															<label class="text-center" style="font-size: 9pt;">Category Filter</label>
															<div>
																@foreach ($itemClass as $itemClass1)
																	@php
																	$abbr = explode(' - ', $itemClass1->item_classification)[0];
																	$name = explode(' - ', $itemClass1->item_classification)[1];
																@endphp
																	<div class="p-2">
																		<a class="" href="{!! count($itemClass) > 1 ?  request()->fullUrlWithQuery(['classification' => $itemClass1->item_classification]) : request()->fullUrlWithQuery(['searchString' => null, 'group' => null, 'wh' => null, 'classification' => $itemClass1->item_classification]) !!}">
																			<div class="btn-group w-100 category-btn" role="group">
																				<div class="btn btn-sm w-25" style="background-color: #001F3F; color: #fff;">{{ $abbr }}</div>
																				<div class="btn btn-sm w-75 border border-outline-secondary">{{ $name }}</div>
																			</div>
																		</a>
																	</div>
																@endforeach
															</div>
														</div>

													</div><!-- modal-content -->
												</div><!-- modal-dialog -->
											</div><!-- modal -->
										</div>
										<div class="col-4 text-right">
											<p class="font-weight-bold m-1 font-responsive d-inline">TOTAL: <span class="badge badge-info font-responsive">{{ number_format($items->total()) }}</span></p>
										</div>
									</div>
									
									<div id="collapseOne" class="collapse border border-outline-secondary" aria-labelledby="headingOne" data-parent="#accordion">
										<div class="card-body p-0">
											<div class="col-12 col-xl-10 mx-auto">
												<div class="row pt-2">
													<div class="col-12 col-md-8 col-xl-4 mx-auto filter-container">
														<label class="mt-2 font-responsive" style="display: inline-block">Select Item Group&nbsp;</label>
														<div class="input-group-append text-left float-right" id="item-group-filter-parent" style="font-size: 11pt;display: inline-block">
															<select id="item-group-filter" class="btn btn-default"></select>
														</div>
													</div>
													<div class="col-12 col-md-8 col-xl-4 mx-auto filter-container">
														<label class="mt-2 font-responsive">Select Warehouse&nbsp;</label>
														<div class="form-group text-left m-0 float-right" id="warehouse-filter-parent" style="font-size: 11pt;">
															<select name="warehouse" id="warehouse-filter" class="form-control"></select>
														</div>
													</div>
													<div class="col-12 col-md-8 col-xl-{{ $promodiser_restriction ? 2 : 4 }} mx-auto checkbox-container">
														<div class="row">
															<div class="form-group m-0r col-12 m-0">
																<label>
																	<input type="checkbox" class="minimal cb-2" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
																	
																	<span style="font-size: 12px;">Remove zero-qty items</span>
																</label>
															</div>
															@if ($promodiser_restriction)
																<div class="form-group m-0r col-12 d-block d-xl-none m-0">
																	<label>
																		<input type="checkbox" class="minimal" id="promodiser-warehouse" {{ (request('promodiser_warehouse')) ? 'checked' : null }} >
																		
																		<span style="font-size: 12px;">Warehouse Assigned to Me</span>
																	</label>
																</div>
															@endif
														</div>
													</div>
													@if ($promodiser_restriction)
														<div class="col-8 col-xl-2 d-none d-xl-block mx-auto text-center">
															<div class="form-group m-0r">
																<label>
																	<input type="checkbox" class="minimal" id="promodiser-warehouse" {{ (request('promodiser_warehouse')) ? 'checked' : null }} >
																	
																	<span style="font-size: 12px;">Warehouse Assigned to Me</span>
																</label>
															</div>
														</div>
													@endif
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-2 d-none d-xl-block">
											<div class="card mb-3">
												@php
													$item_class = collect($itemClass)->chunk(80);
												@endphp
												<label class="text-center p-2">Category Filter</label>
												@if (count($item_class) > 1)
													<ul class="nav nav-tabs" role="tablist">
														@foreach ($item_class as $i => $item)
															<li class="nav-item">
																<a class="nav-link {{ $loop->first ? 'active' : null }}" data-toggle="tab" href="#class-category-{{ $i + 1 }}">{{ $i + 1 }}</a>
															</li>
														@endforeach
													</ul>
												@endif
												<div class="tab-content">
													@for($i = 0; $i < count($item_class); $i++)
														<div id="class-category-{{ $i + 1 }}" class="container tab-pane {{ $i == 0 ? 'active' : null }}" style="padding: 8px 0 0 0;">
															@foreach ($item_class[$i] as $itemClass1)
																@php
																	$abbr = explode(' - ', $itemClass1->item_classification)[0];
																	$name = explode(' - ', $itemClass1->item_classification)[1];
																@endphp
																<div class="p-2">
																	<a class="" href="{!! count($itemClass) > 1 ?  request()->fullUrlWithQuery(['classification' => $itemClass1->item_classification]) : request()->fullUrlWithQuery(['searchString' => null, 'group' => null, 'wh' => null, 'classification' => $itemClass1->item_classification]) !!}">
																		<div class="btn-group w-100 category-btn" role="group">
																			<div class="btn btn-sm w-25" style="background-color: #001F3F; color: #fff;">{{ $abbr }}</div>
																			<div class="btn btn-sm w-75 border border-outline-secondary">{{ $name }}</div>
																		</div>
																	</a>
																</div>
															@endforeach
														</div>
													@endfor
												</div>
											</div>
										</div>
										<div class="col-12 col-xl-10">
											<div class="container-fluid m-0">
												@forelse ($item_list as $row)
													<div class="d-none d-xl-block border border-outline-secondary"><!-- Desktop -->
														<div class="row">
															<div class="col-1 p-2">
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
															<div class="col-5">
																<div class="col-md-12 p-2 text-justify">
																	<span class="font-italic item-class">{{ $row['item_classification'] }} - {!! $row['item_group'] !!}</span><br/>
																	<span class="text-justify item-name"><b>{{ $row['name'] }}</b> - {!! $row['description'] !!}</span>
																	@if ($row['part_nos'])
																		<span class="text-justify item-name"><b>Part No(s)</b> {{ $row['part_nos'] }} </span>
																	@endif
																</div>
															</div>
															<div class="col-5">
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
																	@if(Auth::user()->user_group != 'Promodiser' and count($row['consignment_warehouses']) > 0)
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
																</div><!-- View Consignment Warehouse -->
															</div>
															<div class="col-1"><!-- Price -->
															</div>
														</div>
													</div>
													<div class="d-block d-xl-none border border-outline-secondary"><!-- Mobile/Tablet -->
														<div class="row">
															<div class="col-3 col-lg-2 col-xl-3">
																@php
																	$img = ($row['item_image_paths']) ? "/img/" . explode('.',$row['item_image_paths'][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
																@endphp
																<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="mobile-lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
																	<img src="{{ asset('storage/') .''. $img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}" class="search-img img-responsive hover w-100">
																</a>
				
																<div class="modal fade" id="mobile-{{ $row['name'] }}-images-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
																								<img class="d-block w-100" id="mobile-{{ $row['name'] }}-image" src="{{ asset('storage/').$img }}" alt="{{ Illuminate\Support\Str::slug(explode('.', $img)[0], '-') }}">
																							</div>
																							<span class='d-none' id="mobile-{{ $row['name'] }}-image-data">0</span>
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
																<br><br>
																<a href="#" class="view-item-details" data-item-code="{{ $row['name'] }}" data-item-classification="{{ $row['item_classification'] }}">
																	<div class="btn btn-sm btn-primary w-100">
																		<i class="fa fa-file font-responsive"></i> <span class="d-inline font-responsive">View</span>
																	</div>
																</a>
															</div>
															<div class="col-9 col-lg-10 col-xl-9">
																<span class="font-italic item-class">{{ $row['item_classification'] }} - {!! $row['item_group'] !!}</span><br/>
																<span class="text-justify item-name"><span style="font-weight: 900 !important">{{ $row['name'] }}</span> - {!! $row['description'] !!}</span>
																@if ($row['part_nos'])
																	<span class="text-justify item-name"><b>Part No(s)</b> {{ $row['part_nos'] }} </span>
																@endif
																<div class="d-none d-md-block">
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
																	<div class="container-fluid mb-2"><!-- View Consignment Warehouse -->
																		@if(Auth::user()->user_group != 'Promodiser' and count($row['consignment_warehouses']) > 0)
																		<div class="text-center">
																			<a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#tablet-vcw{{ $row['name'] }}" style="font-size: 11px;">View Consignment Warehouse</a>
																		</div>
							
																		<div class="modal fade" id="tablet-vcw{{ $row['name'] }}" tabindex="-1" role="dialog">
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
																	</div><!-- View Consignment Warehouse -->
																</div>
															</div>
														</div>
														<div class="row d-block d-md-none">
															<div class="container-fluid mt-1 mb-3">
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
																<div class="container-fluid"><!-- View Consignment Warehouse -->
																	@if(Auth::user()->user_group != 'Promodiser' and count($row['consignment_warehouses']) > 0)
																	<div class="text-center">
																		<a href="#" class="btn btn-primary uppercase p-1" data-toggle="modal" data-target="#mobile-vcw{{ $row['name'] }}" style="font-size: 11px;">View Consignment Warehouse</a>
																	</div>
						
																	<div class="modal fade" id="mobile-vcw{{ $row['name'] }}" tabindex="-1" role="dialog">
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
																</div><!-- View Consignment Warehouse -->
															</div>
														</div>
													</div>
												@empty
													<div class="col-md-12 text-center" style="padding: 25px;">
														<h5>No result(s) found.</h5>
													</div>
												@endforelse
											</div><!-- new table -->
				
											<div class="mt-3 ml-3 clearfix pagination" style="display: block;">
												<div class="col-md-4 float-right">
													{{ $items->links() }}
												</div>
											</div>
										</div>
									</div>
									
								</div><!-- Card End -->
							</div>
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
	.category-btn{
		transition: .4s;
	}
	.category-btn:hover{
		box-shadow: #001F3F 2px 2px 8px;
	}
	.custom-border{
		box-shadow: 8px 1px 12px #001F3F;
	}

	.modal.left .modal-dialog{
		position: fixed;
		margin: auto;
		width: 320px;
		height: 100%;
		-webkit-transform: translate3d(0%, 0, 0);
		    -ms-transform: translate3d(0%, 0, 0);
		     -o-transform: translate3d(0%, 0, 0);
		        transform: translate3d(0%, 0, 0);
	}

	.modal.left .modal-content{
		height: 100%;
		overflow-y: auto;
	}
	
	.modal.left .modal-body{
		padding: 15px 15px 80px;
	}

	/*Left*/
	.modal.left.fade .modal-dialog{
		-webkit-transition: opacity 0.3s linear, left 0.3s ease-out;
		   -moz-transition: opacity 0.3s linear, left 0.3s ease-out;
		     -o-transition: opacity 0.3s linear, left 0.3s ease-out;
		        transition: opacity 0.3s linear, left 0.3s ease-out;
	}
	
	.modal.left.fade.in .modal-dialog{
		left: 0;
	}

	.filter-container{
		text-align: right;
	}

	#warehouse-filter-parent, #item-group-filter-parent{
		width: 55%;
	}

	.checkbox-container{
		text-align: center;
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
		.filter-container{
			text-align: left !important;
		}
		#warehouse-filter-parent, #item-group-filter-parent{
			width: 57% !important;
		}
		.checkbox-container{
			text-align: left !important;
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
		.filter-container{
			text-align: left !important;
		}
		#warehouse-filter-parent, #item-group-filter-parent{
			width: 57% !important;
		}
		.checkbox-container{
			text-align: left !important;
		}
    }
	@media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
		.modal.left .modal-dialog{
			width: 240px;
		}
		.filter-container{
			text-align: left !important;
		}
		#warehouse-filter-parent, #item-group-filter-parent{
			width: 65% !important;
		}
		.checkbox-container{
			text-align: left !important;
		}
	}
</style>
<script>
	function nextImg(item_code){
		if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
			var current_img = $('#mobile-'+item_code+'-image-data').text();
		}else{ // desktop
			var current_img = $('#'+item_code+'-image-data').text();
		}
		$.ajax({
			type: "GET",
			url: "/search_results_images",
			data: { 
				img_key: parseInt(current_img) + 1,
				item_code: item_code,
				dir: 'next'
			},
			success: function (data) {
				if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { //mobile/tablet
					$('#mobile-'+data.item_code+'-image').attr('src', data.image_path);
					$('#mobile-'+data.item_code+'-image').prop('alt', data.alt);
					$('#mobile-'+data.item_code+'-image-data').text(data.current_img_key);
				}else{ // desktop
					$('#'+data.item_code+'-image').attr('src', data.image_path);
					$('#'+data.item_code+'-image').prop('alt', data.alt);
					$('#'+data.item_code+'-image-data').text(data.current_img_key);
				}
			}
		});
	}

	function prevImg(item_code){
		if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
			var current_img = $('#mobile-'+item_code+'-image-data').text();
		}else{ // desktop
			var current_img = $('#'+item_code+'-image-data').text();
		}
		$.ajax({
			type: "GET",
			url: "/search_results_images",
			data: { 
				img_key: parseInt(current_img) - 1,
				item_code: item_code,
				dir: 'prev'
			},
			success: function (data) {
				if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { //mobile/tablet
					$('#mobile-'+data.item_code+'-image').attr('src', data.image_path);
					$('#mobile-'+data.item_code+'-image').prop('alt', data.alt);
					$('#mobile-'+data.item_code+'-image-data').text(data.current_img_key);
				}else{ // desktop
					$('#'+data.item_code+'-image').attr('src', data.image_path);
					$('#'+data.item_code+'-image').prop('alt', data.alt);
					$('#'+data.item_code+'-image-data').text(data.current_img_key);
				}
			}
		});
	}
</script>
@endsection