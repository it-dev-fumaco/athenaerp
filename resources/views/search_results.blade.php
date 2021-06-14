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
													<select name="warehouse" id="warehouse-filter" class="form-control">
														
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
						
						<div class="card-body table-responsive p-0">
						<!-- Column 1 -->
							<table class="table table-sm table-bordered" id="item-list-table" style="width: 50% !important; display: inline-block !important; float: left !important;">
								<!-- <col style="width: 15%;">
								<col style="width: 45%;">
								<col style="width: 32%;">
								<col style="width: 8%;"> -->

								<!-- <col style="width: 15%;"> -->
								<!-- <col style="width: 100%;"> -->
								<col style="width: 20%;">
								<col style="width: 80%;">
								<!-- <col style="width: 15%;"> -->

								<thead class="bg-light">
									<tr>
										<!-- <th scope="col" class="text-center" style="font-size: 13px;">IMAGE</th> -->
										<th scope="col"></th>
										<th scope="col" class="text-center" style="font-size: 13px;">ITEM DESCRIPTION</th>
										<!-- <th scope="col" class="text-center">STOCK LEVEL</th> -->
										<!-- <th scope="col" class="text-center" style="font-size: 13px;">ACTION</th> -->
									</tr>
								</thead>
								@forelse ($itemList1 as $row)
								<tbody class="tbl-custom-hover">
									<tr>
										<!-- Image <td class="text-center align-middle" style="padding: 10px;">
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
										 </td> -->
										<td style="height: 400px; padding: 10px;">
											<dl class="row">
												<dd class="col-sm-12 text-center align-middle">
													@forelse ($row['item_image_paths'] as $item_image)
														@php
															$img = ($item_image->image_path) ? "/img/" . $item_image->image_path : "/icon/no_img.png";
														@endphp
														<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}" class="{{ (!$loop->first) ? 'd-none' : '' }}">
															<img src="{{ asset('storage/') }}{{ $img }}" style="display:block">
														</a>
														@empty
														<a href="{{ asset('storage/icon/no_img.png') }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
															<img src="{{ asset('storage/icon/no_img.png') }}" class="img-thumbnail" width="200">
														</a>
													@endforelse
													<div style="margin: 1px;"><br/>
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
												</dd>
											</dl><!-- ! -->
										</td><!-- ! -->	

										<td><!-- ! -->
											<dl class="row"><!-- ! -->
												<dd class="col-sm-12 text-justify" style="font-size: 12px;">
													<b>{{ $row['name'] }}</b> {!! $row['description'] !!}<br/>
													<b>Item Classification</b> {{ $row['item_classification'] }}<br/>
													<b>Part No(s)</b> {{ ($row['part_nos']) ? $row['part_nos'] : '-' }}
												</dd>
												<!-- <dt class="col-sm-4 pb-1">Item Classification</dt>
												<dd class="col-sm-4 pb-1 m-0">{{ $row['item_classification'] }}</dd>
												<dt class="col-sm-4 pb-1">Part No(s)</dt>
												<dd class="col-sm-4 pb-1 m-0">{{ ($row['part_nos']) ? $row['part_nos'] : '-' }}</dd>
												@forelse($row['item_inventory'] as $inv)
												<dt class="col-sm-8 pb-1">{{ $inv['warehouse'] }}</dt>
													<dd class="col-sm-4 pb-1 m-0">
														<span class="badge badge-{{ ($inv['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 11pt;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
													</dd>
													@empty
													<dd class="col-sm-12 pb-1 m-0 text-center">NO WAREHOUSE ASSIGNED</dd>
												@endforelse -->
												<dd class="col-sm-12" style="height: 10px;"></dd>
												<dt class="col-sm-5 pb-1 qtyBorder boldTxt">&nbsp;Warehouse</dt>
												<dt class="col-sm-3 pb-1 qtyBorder boldTxt">&nbsp;Reserved Qty</dt>
												<dt class="col-sm-4 pb-1 qtyBorder boldTxt rightB">&nbsp;Available Qty</dt>
												@forelse($row['item_inventory'] as $inv)
													<dt class="col-sm-5 pb-1 qtyBorder">&nbsp;{{ $inv['warehouse'] }}</dt>
													<dt class="col-sm-3 pb-1 qtyBorder text-center">&nbsp;{{ $inv['reserved_qty'] * 1 }}  {{ $inv['stock_uom'] }}</dt>
													<dt class="col-sm-4 pb-1 m-0 qtyBorder rightB" style="padding: 5px;">
														<span class="badge badge-{{ ($inv['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 10px; margin: 0 auto;">{{ $inv['available_qty'] * 1 . ' ' . $inv['stock_uom'] }}</span>
													</dt>
													@empty
													<dd class="col-sm-12 pb-1 m-0 text-center">NO WAREHOUSE ASSIGNED</dd>
												@endforelse
												<dd class="col-sm-12" style="height: 10px;"></dd>
												
												<!-- <dt class="col-sm-3 pb-1">Item Classification</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['item_classification'] }}</dd>
												<dt class="col-sm-3 pb-1">Part No(s)</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ ($row['part_nos']) ? $row['part_nos'] : '-' }}</dd> -->
											</dl>
										</td>

										<!--<td class="text-center align-middle" style="padding: 1px;">
											Original <a href="#" class="btn btn-app view-item-details bg-info" data-item-code="{{ $row['name'] }}">
												<i class="fa fa-file"></i> Details
											</a>
											<a href="#" class="btn btn-app bg-warning" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
												<i class="fa fa-qrcode"></i> QR
											</a> -->
											<!-- <a href="#" class="cLink view-item-details" data-item-code="{{ $row['name'] }}">
												<div class="detailsBtn actionBtn">
													<i class="fa fa-file"></i>
													<br/>Details
												</div>
											</a>
											<br/><br/>
											<a href="#" class="cLink" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
												<div class="qrBtn actionBtn">
													<i class="fa fa-qrcode"></i>
													<br/>QR
												</div>
											</a> -->
											<!-- <dd class="col-sm-3 text-center">
												<a href="#" class="cLink view-item-details" data-item-code="{{ $row['name'] }}">
													<div class="btn btn-primary" style="min-width: 80px; height: 80px; padding: 18px;">
														<i class="fa fa-file"style="font-size: 18px;"><br/>Details</i>
													</div>
												</a>
											</dd>
											<dd class="col-sm-3 text-center">
												<a href="#" class="cLink" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
													<div class="btn btn-warning" style="min-width: 90px; height: 80px; padding: 20px;">
														<i class="fa fa-qrcode" style="font-size: 18px;"><br/>QR</i>
													</div>
												</a>
											</dd>
										</td> -->
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
							<!-- Column 1 -->

							<!-- Column 2 -->
							<table class="table table-sm table-bordered" id="item-list-table" style="width: 50% !important; display: inline-block !important;">
								<col style="width: 20%;">
								<col style="width: 80%;">
								<thead class="bg-light">
									<tr>
										<th scope="col" class="text-center" style="font-size: 13px;"></th>
										<th scope="col" class="text-center" style="font-size: 13px;">ITEM DESCRIPTION</th>
									</tr>
								</thead>
								@forelse ($itemList2 as $row2)
								<tbody class="tbl-custom-hover">
									<tr>
										<td style="height: 400px; padding: 10px;">
											<dl class="row">
												<dd class="col-sm-12 text-center align-middle">
													@forelse ($row2['item_image_paths'] as $item_image2)
														@php
															$img = ($item_image2->image_path) ? "/img/" . $item_image2->image_path : "/icon/no_img.png";
														@endphp
														<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row2['name'] }}" data-title="{{ $row2['name'] }}" class="{{ (!$loop->first) ? 'd-none' : '' }}">
															<img src="{{ asset('storage/') }}{{ $img }}" style="display:block">
														</a>
														@empty
														<a href="{{ asset('storage/icon/no_img.png') }}" data-toggle="lightbox" data-gallery="{{ $row2['name'] }}" data-title="{{ $row2['name'] }}">
															<img src="{{ asset('storage/icon/no_img.png') }}" class="img-thumbnail" width="200">
														</a>
													@endforelse
													<div style="margin: 1px;"><br/>
														<a href="#" class="cLink view-item-details" data-item-code="{{ $row2['name'] }}">
															<div class="btn btn-primary">
																<i class="fa fa-file"></i>
															</div>
														</a>
														<a href="#" class="cLink" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row2['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;">
															<div class="btn btn-warning">
																<i class="fa fa-qrcode"></i>
															</div>
														</a>
													</div>
												</dd>
											</dl>
										</td>

										<td>
											<dl class="row">
												<dd class="col-sm-12 text-justify" style="font-size: 12px;">
													<b>{{ $row2['name'] }}</b> {!! $row2['description'] !!}<br/>
													<b>Item Classification</b> {{ $row2['item_classification'] }}<br/>
													<b>Part No(s)</b> {{ ($row2['part_nos']) ? $row2['part_nos'] : '-' }}
												</dd>
												<dd class="col-sm-12" style="height: 10px;"></dd>
												<dt class="col-sm-5 pb-1 qtyBorder boldTxt">&nbsp;Warehouse</dt>
												<dt class="col-sm-3 pb-1 qtyBorder boldTxt">&nbsp;Reserved Qty</dt>
												<dt class="col-sm-4 pb-1 qtyBorder boldTxt rightB">&nbsp;Available Qty</dt>
												@forelse($row2['item_inventory'] as $inv2)
													<dt class="col-sm-5 pb-1 qtyBorder">&nbsp;{{ $inv2['warehouse'] }}</dt>
													<dt class="col-sm-3 pb-1 qtyBorder text-center">&nbsp;{{ $inv2['reserved_qty'] * 1 }}  {{ $inv2['stock_uom'] }}</dt>
													<dt class="col-sm-4 pb-1 m-0 qtyBorder rightB" style="padding: 5px;">
														<span class="badge badge-{{ ($inv2['available_qty'] > 0) ? 'success' : 'danger' }}" style="font-size: 10px; margin: 0 auto;">{{ $inv2['available_qty'] * 1 . ' ' . $inv2['stock_uom'] }}</span>
													</dt>
													@empty
													<dd class="col-sm-12 pb-1 m-0 text-center">NO WAREHOUSE ASSIGNED</dd>
												@endforelse
												<dd class="col-sm-12" style="height: 10px;"></dd>
											</dl>
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
							<!-- Column 2 -->	
						</div>
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
		min-height: 100px;
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
		padding: 2px;
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