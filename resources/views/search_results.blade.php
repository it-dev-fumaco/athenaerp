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
						@foreach($itemClass as $itemClass)
							<!-- <button onclick="redirectClass(this)" class="itemClassBubble" value="{{$itemClass->item_classification}}">{{$itemClass->item_classification}}</button> -->
							<a class="itemClassBubble" href="/search_results?wh=&searchString={{$itemClass->item_classification}}">
								<div class="col-md-2 col-sm-6 col-xs-12 infoBoxContainer">
									<div class="infoBox">
									<!-- <div class="info-box infoBox"> -->
										<span class="info-box-icon abbrBG" style="display: inline-block !important;">
											<i>{{substr($itemClass->item_classification, 0, 2)}}</i>
										</span>
										<div class="info-box-content" style="display: inline-block !important;"> 
											<span class="info-box-text">
												{{substr($itemClass->item_classification, 4)}}
											</span>
										</div>
									</div>
								</div>
							</a>
						@endforeach
					</div>	
					<div class="card card-gray card-outline">
						<div class="card-header p-0">
							<div class="row">
								<div class="col-md-6">
									<h5 class="card-title mt-2 ml-4 font-weight-bold">
										@if(request('searchString') && request('searchString') != '') 
										Search result(s) for "{{ request('searchString') }}"
									@else
										Item List
									@endif</h5>
								</div>
								<div class="col-md-6">
									<div class="row">
										<div class="col-md-6 p-1">
												<div class="float-right form-group m-0 w-75" id="warehouse-filter-parent">
													<select name="warehouse" id="warehouse-filter" class="form-control">
														
													</select>
												  </div>
										</div>
										<div class="col-md-3 p-1 text-center">
											<div class="form-group m-0">
												<label>
												  <input type="checkbox" class="minimal" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
												  Remove zero-qty items
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
						
						<div class="card-body table-responsive p-0">
							<table class="table table-sm table-bordered" id="item-list-table">
								<col style="width: 15%;">
								<col style="width: 45%;">
								<col style="width: 32%;">
								<col style="width: 8%;">
								<thead class="bg-light">
									<tr>
										<th scope="col" class="text-center">IMAGE</th>
										<th scope="col" class="text-center">ITEM DESCRIPTION</th>
										<th scope="col" class="text-center">STOCK LEVEL</th>
										<th scope="col" class="text-center">ACTION</th>
									</tr>
								</thead>
								@forelse ($item_list as $row)
								<tbody class="tbl-custom-hover">
									<tr>
										<td class="text-center align-middle">
											@forelse ($row['item_image_paths'] as $item_image)
											@php
											$img = ($item_image->image_path) ? "/img/" . $item_image->image_path : "/icon/no_img.png";
											@endphp
											<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}" class="{{ (!$loop->first) ? 'd-none' : '' }}">
												<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail" width="200">
											</a>
											@empty
											<a href="{{ asset('storage/icon/no_img.png') }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
												<img src="{{ asset('storage/icon/no_img.png') }}" class="img-thumbnail" width="200">
											</a>
											@endforelse
										 </td>
										 <td>
											<dl class="row">
												<dt class="col-sm-12">{{ $row['name'] }}</dt>
												<dd class="col-sm-12 text-justify">{!! $row['description'] !!}</dd>
												<dt class="col-sm-3 pb-1">Item Classification</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['item_classification'] }}</dd>
												<dt class="col-sm-3 pb-1">Part No(s)</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ ($row['part_nos']) ? $row['part_nos'] : '-' }}</dd>
											</dl>
										</td>
										<td class="p-0">
											<table class="table table-sm m-0">
												<col style="width: 40%;">
												<col style="width: 30%;">
												<col style="width: 30%;">
												<tr>
													<th class="text-center">Warehouse</th>
													<th class="text-center">Reserved Qty</th>
													<th class="text-center">Available Qty</th>
												</tr>
												@forelse($row['item_inventory'] as $inv)
												<tr>
													<td>{{ $inv['warehouse'] }}</td>
													<td class="text-center">{{ $inv['reserved_qty'] * 1 }}  {{ $inv['stock_uom'] }}</td>
													<td class="text-center">
														<span class="badge badge-{{ ($inv['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
													</td>
												</tr>
												@empty
												<tr>
													<td class="text-center font-italic" colspan="3">NO WAREHOUSE ASSIGNED</td>
												</tr>
												@endforelse
											</table>
											@if($row['consignment_warehouse_count'] > 0)
											<div class="text-center p-2">
												<a href="#" class="uppercase" data-toggle="modal" data-target="#vcw{{ $row['name'] }}">View Consignment Warehouse</a>
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
										</td>
										<td class="text-center align-middle">
											<a href="#" class="btn btn-app view-item-details bg-info" data-item-code="{{ $row['name'] }}">
												<i class="fa fa-file"></i> Details
											</a>
											<a href="#" class="btn btn-app bg-warning" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
												<i class="fa fa-qrcode"></i> QR
											</a>
										</td>
									</tr>
								</tbody>
								<tr class="nohover">
									<td colspan="4">&nbsp;</td>
								</tr>
								@empty
								<tr class="nohover">
									<td colspan="4" class="text-center"><br><label style="font-size: 16pt;">No result(s) found.</label><br>&nbsp;</td>
								</tr>
								@endforelse
							</table>
							<div class="ml-3 clearfix" style="font-size: 16pt;">
								{{ $items->links() }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<style>
	.itemClassContainer{
		min-height: 60px;
		overflow: auto;
		white-space: nowrap;
	}
	.itemClassBubble{
		color: #000;
		text-decoration: none !important;
		text-transform: none !important;
		transition: .4s;
	}
	.itemClassBubble:hover{
		color: #A6A6BF;
	}
	.abbrBG{
		background-color: #00C0EF;
		border-top-left-radius: 2px;
		border-bottom-left-radius: 2px;
		height: 50px !important;
		padding: 15px !important;
	}
	.infoBoxContainer{
		display: inline-block;
		min-height: 50px !important;
		margin-left: 1px;
	}
	.infoBox{
		background-color: #fff;
		padding: 1px !important;
		height: 50px !important;
		min-width: 210px;
		margin: 2px !important;
		box-shadow: 2px 2px 8px #DCDCDC;
		border-radius: 2px;
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
@endsection

@section('script')

@endsection