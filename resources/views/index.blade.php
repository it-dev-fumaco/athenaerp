@extends('layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'index',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-12">
					<div class="card card-gray card-outline">
						<div class="card-header">
							<h5 class="card-title m-0 font-weight-bold">List of Item(s)</h5>
							<div class="card-tools">
								<div class="pull-right">
									<span class="font-weight-bold m-1">TOTAL:</span>
									<span class="badge bg-info" style="font-size: 14pt;">{{ number_format($items->total()) }}</span>
								</div>
							</div>
						</div>
						<div class="card-body table-responsive p-0">
							<table class="table table-sm" border="1">
								<col style="width: 15%;">
								<col style="width: 45%;">
								<col style="width: 32%;">
								<col style="width: 8%;">
								<thead class="bg-light">
									<tr>
										<th scope="col" class="text-center">IMAGE</th>
										<th scope="col" class="text-center">ITEM DESCRIPTION</th>
										{{--  <th scope="col" class="text-center">ITEM GROUP</th>
										<th scope="col" class="text-center">UOM</th>  --}}
										<th scope="col" class="text-center">STOCK LEVEL</th>
										<th scope="col" class="text-center">ACTION</th>
									</tr>
								</thead>
								@forelse ($item_list as $row)
								@php
									$count_wh = count($row['item_inventory']);
									$rowspan = ($count_wh > 0) ? ($count_wh + 2) : 3;
								@endphp
								<tbody>
									<tr>
										<td rowspa1n="{{ $rowspan }}" class="text-center">
											@php
											$img = ($row['item_image_path']) ? "/img/" . $row['item_image_path'] : "/icon/no_img.png";
											@endphp
											{{--  <div class="row">
												<div class="col-md-9 offset-md-1 p-0">
													<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
														<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail m-0" width="200">
													</a>
												</div>
												<div class="col-md-3 offset-md-1 p-0">
													<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
														<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail m-0" width="60">
													</a>
												</div>
												<div class="col-md-3 p-0">
													<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
														<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail" width="60">
													</a>
												</div>
												<div class="col-md-3 p-0">
													<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
														<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail" width="60">
													</a>
												</div>
											</div>  --}}
											
											<a href="{{ asset('storage/') }}{{ $img }}" data-toggle="lightbox" data-gallery="{{ $row['name'] }}" data-title="{{ $row['name'] }}">
												<img src="{{ asset('storage/') }}{{ $img }}" class="img-thumbnail" width="200">
											</a>
											
										 </td>
										 <td rowsp1an="{{ $rowspan }}">
											
											<dl class="row">
												<dt class="col-sm-12">{{ $row['name'] }}</dt>
												<dd class="col-sm-12 text-justify">{!! $row['description'] !!}</dd>
												{{--  <dt class="col-sm-3 pb-1">Stock UoM</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['stock_uom'] }}</dd>
												<dt class="col-sm-3 pb-1">Item Group</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['item_group'] }}</dd>  --}}
												<dt class="col-sm-3 pb-1">Item Classification</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['item_classification'] }}</dd>
												<dt class="col-sm-3 pb-1">Part No(s)</dt>
												<dd class="col-sm-9 pb-1 m-0">{{ $row['part_nos'] }}</dd>
											</dl>
										</td>
										<td class="p-0">
											<table class="table table-sm">
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
										<td class="text-center">{{ $inv['e_commerce_reserve_qty'] * 1 }}  {{ $inv['stock_uom'] }}</td>
										<td class="text-center">{{ $inv['actual_qty'] * 1 }} {{ $inv['stock_uom'] }}</td>
									</tr>
									@empty
									<tr><td class="text-center" colspan="3">NO WAREHOUSE ASSIGNED</td></tr>
										
									@endforelse
									
											</table>
										</td>
										<td class="text-center">
											<a href="#" class="btn btn-app">

												<i class="fa fa-file"></i> Info
              
											</a>
											<a href="#" class="btn btn-app">

												<i class="fa fa-barcode"></i> Barcode
              
											</a>
											{{--  <div class="imgButton m-1">
												<img src="{{ asset('storage/icon/barcode.png') }}" class="img-circle mr-2" id="btn" name="barcode" value="Print Barcode" onClick="javascript:void window.open('/print_barcode/{{ $row['name'] }}','1445905018294','width=450,height=700,toolbar=0,menubar=0,location=0,status=1,scrollbars=1,resizable=1,left=0,top=0');return false;" width="40px">
												<img src="{{ asset('storage/icon/report.png') }}" class="img-circle view-item-details mr-2" name="history" id="btn" data-item-code="{{ $row['name'] }}" width="40px">
												<img src="{{ asset('storage/icon/upload.png') }}" class="img-circle upload-item-image" name="upload" id="btn" value="Upload Image" data-item-code="{{ $row['name'] }}" width="40px" data-image="{{ asset('storage/') }}{{ $img }}">
											</div>  --}}
										</td>
										 {{--  <td class="text-center" rowspan="{{ $rowspan }}">{{ $row['item_group'] }}</td>  --}}
										 {{--  <td class="text-center">{{ $row['stock_uom'] }}</td>
										 <td class="text-center">{{ $row['item_classification'] }}</td>  --}}
									</tr>
									{{--  <tr>
										<th class="text-center">Warehouse</th>
										<th class="text-center">Reserved Qty</th>
										<th class="text-center">Available Qty</th>
									</tr>
									@forelse($row['item_inventory'] as $inv)
									<tr>
										<td>{{ $inv['warehouse'] }}</td>
										<td>{{ $inv['warehouse'] }}</td>
										<td class="text-center">{{ $inv['actual_qty'] * 1 }} {{ $inv['stock_uom'] }}</td>
									</tr>
									@empty
									<tr><td class="text-center" colspan="3">NO WAREHOUSE ASSIGNED</td></tr>
										
									@endforelse
									  --}}
								  </tbody>
								  <tr class="nohover">
									<td colspan="5">&nbsp;</td>
								  </tr>
								@empty
								<tr class="nohover">
									<td colspan="5" style="text-align: center;"><br><label style="font-size: 16pt;">No result(s) found.</label><br>&nbsp;</td>
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
	tbody:hover,
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