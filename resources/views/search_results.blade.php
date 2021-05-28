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
					<div class="card card-gray card-outline">
						<div class="card-header p-0">
							<h5 class="card-title mt-2 ml-4 font-weight-bold">Search result(s) for "{{ request('searchString') }}"</h5>
							<div class="card-tools p-0 m-0">
								<div class="pull-right m-0 p-0">
									<div class="d-flex flex-row bd-highlight p-0">
										<div class="p-1 bd-highlight">
											<div class="form-group m-0 pr-3">
												<label>
												  <input type="checkbox" class="minimal" id="cb-2" {{ (request('check_qty')) ? 'checked' : null }} >
												  Remove zero-qty items
												</label>
											  </div>
										</div>
										<div class="p-1 bd-highlight">
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
													<td class="text-center">{{ $inv['actual_qty'] * 1 }} {{ $inv['stock_uom'] }}</td>
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
											{{--  <div class="imgButton m-1">
												<img src="{{ asset('storage/icon/barcode.png') }}" class="img-circle mr-2" id="btn" name="barcode" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;" width="40px">
												<img src="{{ asset('storage/icon/report.png') }}" class="img-circle view-item-details mr-2" name="history" id="btn" data-item-code="{{ $row['name'] }}" width="40px">
												<img src="{{ asset('storage/icon/upload.png') }}" class="img-circle upload-item-image" name="upload" id="btn" value="Upload Image" data-item-code="{{ $row['name'] }}" width="40px" data-image="{{ asset('storage/') }}{{ $img }}">
											</div>  --}}
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