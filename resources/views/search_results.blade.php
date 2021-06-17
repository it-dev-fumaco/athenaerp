@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'search_results',
])

@section('content')
<div class="content">
	<div class="content-header pt-3">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="container-fluid itemClassContainer overflow-auto">
						<input type="button" id="classFromURL" name="text[]" value="{{ substr(request('classification'), 4) }}" hidden="">
						@foreach($itemClass as $itemClass)
							<!-- <a class="itemClassBubble" href="{{ url()->current().'?'.http_build_query(array_merge(request()->all(),['classification' => $itemClass])) }}"> 
							<a class="itemClassBubble" href="{{ request()->fullUrlWithQuery(['classification' => $itemClass]) }}"> - array	 -->
							<a class="itemClassBubble" href="{{ request()->fullUrlWithQuery(['classification' => $itemClass->item_classification]) }}">	
								<div class="classPanel">
									<div class="classPanelAbbr">
										<i>{{substr($itemClass->item_classification, 0, 2)}}</i>
									</div>
									<div class="classPanelName">
										<!-- <i>{{substr(Illuminate\Support\Str::limit($itemClass->item_classification,15), 4)}}</i> Ellipsis  -->
										<!-- <i>{{substr($itemClass->item_classification, 4)}}</i> -->
										<input type="button" class="classPanelBtn bg-white" name="text[]" value="{{substr($itemClass->item_classification, 4)}}" readonly>
									</div>
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
												<div class="float-right form-group m-0 w-55" id="warehouse-filter-parent">
													<select name="warehouse" id="warehouse-filter" class="form-control" style="width: 200px; font-size: 19px !important;">
														
													</select>
												  </div>
										</div>
										<div class="col-md-3 p-1 text-center">
											<div class="form-group m-0">
												<label>
												  <input type="checkbox" class="minimal" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
												  
												  <span style="font-size: 12px;">Remove zero-qty items</span>
												</label>
											  </div>
										</div>
										<div class="col-md-3 text-right p-1">
											<span class="font-weight-bold m-1">TOTAL:</span>
											<span class="badge bg-info mr-2" style="font-size: 13pt;">{{ number_format($items->total()) }}</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="container-fluid"><!-- new table -->
							<div class="row">
							@forelse ($item_list as $row)
								<div class="col-md-6 border border-color-secondary">
									<div class="col-md-3 display-inline-block float-left p-2 text-center">
										@forelse ($row['item_image_paths'] as $item_image)
											@php
												$img = ($item_image->image_path) ? "/img/" . $item_image->image_path : "/icon/no_img.png";
											@endphp
											<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}" class="{{ (!$loop->first) ? 'd-none' : '' }}">
												<img class="display-block img-thumbnail" src="{{ asset('storage/') }}{{ $img }}" width="150">
											</a>
										@empty
											<a href="{{ asset('storage/icon/no_img.png') }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
												<img src="{{ asset('storage/icon/no_img.png') }}" class="img-thumbnail" width="200">
											</a>
										@endforelse
										<div class="text-center" style="margin: 1px;"><br/>
											<a href="#" class="cLink view-item-details" data-item-code="{{ $row['name'] }}" data-item-classification="{{ $row['item_classification'] }}">
												<div class="btn btn-primary">
													<i class="fa fa-file"></i>
												</div>
											</a>
											<a href="#" class="cLink" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
												<div class="btn btn-warning">
													<i class="fa fa-qrcode"></i>
												</div>
											</a>
										</div>	
									</div>
									<div class="col-md-9 display-inline-block float-right p-2">
										<div class="col-md-12 p-2 text-justify">
											<span class="font-italic" style="font-size: 12px;">{{ $row['item_classification'] }}</span><br/>
											<span class="text-justify" style="font-size: 14px;"><b>{{ $row['name'] }}</b> - {!! $row['description'] !!}</span><br/>
											<b>Part No(s)</b> {{ ($row['part_nos']) ? $row['part_nos'] : '-' }} 
										</div>
										<table class="table table-sm table-bordered">
											<tr>
												<th class="col-sm-6 text-center">Warehouse</th>
												<th class="col-sm-3 text-center">Reserved Qty</th>
												<th class="col-sm-3 text-center">Available Qty</th>
											</tr>
											@forelse($row['item_inventory'] as $inv)
												<tr>
													<td class="text-center">
														{{ $inv['warehouse'] }}
														@if($inv['warehouse'] == $row['default_warehouse'])
															<span class="font-italic"><small>- default</small></span>
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
															<h4 class="modal-title">{{ $row['name'] }} - Consignment Warehouse(s) </h4>
															<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
														</div>
														<form></form>
														<div class="modal-body">
															<table class="table table-hover m-0">
																<col style="width: 70%;">
																<col style="width: 30%;">
																<tr>
																	<th class="text-center">Warehouse</th>
																	<th class="text-center">Available Qty</th>
																</tr>
																@forelse($row['consignment_warehouses'] as $con)
																<tr>
																	<td>{{ $con['warehouse'] }}</td>
																	<td class="text-center"><span class="badge badge-{{ ($inv['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11px; margin: 0 auto;">{{ $con['actual_qty'] * 1 . ' ' . $con['stock_uom'] }}</span></td>
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
							@empty
								<div class="col-md-12 text-center" style="padding: 25px;">
									<h5>No result(s) found.</h5>
								</div>
							@endforelse
							</div>
						</div><!-- new table -->

						<div class="ml-3 clearfix" style="font-size: 15pt; display: block;">
							{{ $items->links() }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<style>
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

	.classPanel{
		min-width: 150px; 
		height: 58px; 
		display: inline-block; 
		margin: 5px; 
		background-color: white;
		padding-right: 5px;
		border-radius: 5px;
	}

	.classPanelAbbr{
		background-color: #001F3F !important;
		min-width: 50px;
		height: 55px; 
		display: inline-block; 
		color: #fff; 
		padding: 11px;
		font-weight: 700;
		font-size: 20px;
		border-top-left-radius: 5px;
		border-bottom-left-radius: 5px;
		margin-right: -2px;
	}

	.classPanelName{
		min-width: 100px;
		min-height: 60px;
		display: inline-block;
		text-align: center;
	}
	
	.classPanelBtn{
		color: #000;
		border: white;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px;
		min-height: 50px;
		min-width: 150px !important;
		display: inline-block !important;
		cursor: pointer;
		transition: .4s;
	}

	.classPanelBtn:hover{
		box-shadow: 8px 1px 12px #001F3F;
	}

	.classPanelBtn:focus{
		outline: none !important;
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
</style>

<script>
	function activeBtn(){
		var values = [];
		$('input:button').each(
			function() {
				if (values.indexOf(this.value) >= 0) {
					$(this).css("box-shadow", "8px 1px 12px #001F3F");
				} else {
					$(this).css("border-color", "");
					values.push(this.value);
				}
			}
		);
	}
	window.onload=activeBtn;
</script>
@endsection

@section('script')

@endsection