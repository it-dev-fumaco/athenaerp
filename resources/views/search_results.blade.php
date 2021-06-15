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
					<div class="container-fluid itemClassContainer">
						<input type="text" id="classFromURL" name="text[]" value="{{ substr(request('classification'), 4) }}" hidden="">
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
										<input type="text" class="classPanelBtn" name="text[]" value="{{substr($itemClass->item_classification, 4)}}" readonly>
									</div>
								</div>
							</a>
 						@endforeach
					</div>	
					<div class="card card-gray card-outline">
						<div class="card-header p-0">
							<div class="row">
								<div class="col-md-6">
									<h5 class="card-title mt-2 ml-4 font-weight-bold" style="font-size: 15px;">
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
													<select name="warehouse" id="warehouse-filter" class="form-control" style="width: 20000px; font-size: 20px !important;">
														
													</select>
												  </div>
										</div>
										<div class="col-md-3 p-1 text-center">
											<div class="form-group m-0">
												<label>
												  <input type="checkbox" class="minimal" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
												  
												  <span style="font-size: 13px;">Remove zero-qty items</span>
												</label>
											  </div>
										</div>
										<div class="col-md-3 text-right p-1">
											<span class="font-weight-bold m-1">TOTAL:</span>
											<span class="badge bg-info mr-2" style="font-size: 14pt;">{{ number_format($items->total()) }}</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<div class="container-fluid"><!-- new table -->
							<div class="row">
							@forelse ($item_list as $row)
								<div class="col-md-6" style="border: 1px solid #CDCDCD;">
									<div class="col-md-3" style="display: inline-block; float: left; padding: 5px;">
										@forelse ($row['item_image_paths'] as $item_image)
											@php
												$img = ($item_image->image_path) ? "/img/" . $item_image->image_path : "/icon/no_img.png";
											@endphp
											<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}" class="{{ (!$loop->first) ? 'd-none' : '' }}">
												<img src="{{ asset('storage/') }}{{ $img }}" style="display:block;" width="150">
											</a>
										@empty
											<a href="{{ asset('storage/icon/no_img.png') }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
												<img src="{{ asset('storage/icon/no_img.png') }}" class="img-thumbnail" width="200">
											</a>
										@endforelse
										<div class="text-center" style="margin: 1px;"><br/>
											<a href="#" class="cLink view-item-details" data-item-code="{{ $row['name'] }}">
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
									<div class="col-md-9" style="display: inline-block; float: right; padding: 5px;">
										<div class="col-md-12" style="padding: 5px;">
											<span style="font-style: italic; font-size: 12px;">{{ $row['item_classification'] }}</span><br/>
											<b>{{ $row['name'] }}</b> {!! $row['description'] !!}<br/>
											<b>Part No(s)</b> {{ ($row['part_nos']) ? $row['part_nos'] : '-' }}
										</div>
										<table class="table table-sm table-bordered">
											<tr>
												<th class="col-sm-6">Warehouse</th>
												<th class="col-sm-3">Reserved Qty</th>
												<th class="col-sm-3">Available Qty</th>
											</tr>
											@forelse($row['item_inventory'] as $inv)
												<tr>
													<td>{{ $inv['warehouse'] }}</td>
													<td class="text-center">{{ $inv['reserved_qty'] * 1 }}  {{ $inv['stock_uom'] }}</td>
													<td class="text-center"><span class="badge badge-{{ ($inv['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 15px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span></td>
												</tr>
											@empty
												<tr>
													<td class="col-sm-6 text-center" style="border: none;">NO WAREHOUSE ASSIGNED</td>
												</tr>
											@endforelse
										</table>
										<div class="col-md-12"><!-- View Consignment Warehouse -->
											@if(count($row['consignment_warehouses']) > 0)
											<div class="text-center">
												<a href="#" class="uppercase" data-toggle="modal" data-target="#vcw{{ $row['name'] }}" style="font-size: 12px;">View Consignment Warehouse</a>
											</div>

											<div class="modal fade" id="vcw{{ $row['name'] }}" tabindex="-1" role="dialog">
												<div class="modal-dialog" role="document">
													<div class="modal-content">
														<div class="modal-header">
															<h4 class="modal-title">{{ $row['name'] }}</h4>
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
																	<td class="text-center">{{ $con['actual_qty'] * 1 }} {{ $con['stock_uom'] }}</td>
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

						<div class="ml-3 clearfix" style="font-size: 16pt; display: block;">
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
		overflow: auto;
		white-space: nowrap;
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
		height: 50px; 
		display: inline-block; 
		margin: 5px; 
		background-color: white;
		padding-right: 5px;
		border-radius: 5px;
	}

	.classPanelAbbr{
		background-color: #001F3F !important;
		min-width: 50px;
		height: 53px; 
		display: inline-block; 
		color: #fff; 
		padding: 11px;
		font-weight: 700;
		font-size: 21px;
		border-top-left-radius: 5px;
		border-bottom-left-radius: 5px;
		margin-right: -2px;
	}

	.classPanelName{
		min-width: 100px;
		min-height: 50px;
		display: inline-block;
		text-align: center;
	}
	
	.classPanelBtn{
		color: #000;
		border: white;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px;
		min-height: 50px;
		min-width: 100px !important;
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

	.qtyBorder{
		/* border-top: 1px solid #DEE2E6; */
		border-bottom: 1px solid #DEE2E6;
		border-right: 1px solid #DEE2E6;
		font-weight: normal;
	}

	.rightB{
		border-right: none;
	}

	.boldTxt{
		font-weight: 700;
	}

	/* .actionBtn{
		width: 50px;
		height: 50px !important;
		padding: 10px;
		font-size: 10px;
		margin: 0 auto;
		border-radius: 5px;
		text-transform: none !important;
		text-decoration: none !important;
		transition: .4s;
	}

	.detailsBtn{
		color: #fff;
		background-color: #17A2B8;
	}

	.detailsBtn:hover{
		background-color: #0A7484;
	}

	.qrBtn{
		color: #000;
		background-color: #FFC107;
	}

	.qrBtn:hover{
		background-color: #C99C13;
	} */

	.cLink{
		text-decoration: none !important;
		text-transform: none !important;
	}

	.select2-selection{
		width: 300px !important;
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
		$('input:text').each(
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