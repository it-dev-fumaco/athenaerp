<table class="table" style="font-size: 9pt;">
	<thead>
		<th class="p-1 text-center align-middle d-none d-lg-table-cell">Date</th>
		<th class="p-1 text-center align-middle mobile-first">Store</th>
		<th class="p-1 text-center align-middle d-none d-lg-table-cell">Total items</th>
		<th class="p-1 text-center align-middle d-none d-lg-table-cell">Amount</th>
		<th class="p-1 text-center align-middle d-none d-lg-table-cell">Submitted by</th>
		<th class="p-1 text-center align-middle d-none d-lg-table-cell">Status</th>
		<th class="p-1 text-center align-middle last-row">Action</th>
	</thead>
	<tbody>
	@forelse ($invArr as $inv)
		@php
			$badge = 'secondary';
			if($inv['status'] == 'For Approval'){
				$badge = 'primary';
			}else if($inv['status'] == 'Approved'){
				$badge = 'success';
			}else if($inv['status'] == 'Cancelled'){
				$badge = 'secondary';
			}
			$branchLabel = $inv['branch_warehouse'] ?? $inv['branch'] ?? '';
			$modalForm = in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv['status'] == 'For Approval' ? '/approve_beginning_inv/'.$inv['name'] : '/stock_adjust/submit/'.$inv['name'];
		@endphp
		<tr>
			<td class="p-2 text-center align-middle d-none d-lg-table-cell">
				<span style="white-space: nowrap">{{ $inv['transaction_date'] }}</span>
			</td>
			<td class="p-2 text-left align-middle text-xl-center">
				<span class="d-block">{{ $branchLabel }}</span>
				<div class="d-lg-none">
					<small>Created By: {{ $inv['owner'] }}</small> <br>
					<small>Approved By: {{ $inv['approved_by'] ? $inv['approved_by'] : '-' }}</small> <br>
					<small>Date: {{ Carbon\Carbon::parse($inv['date_approved'])->format('M d, Y h:i A') }}</small>
				</div>
				<div class="row p-0 d-lg-none">
					<div class="col-4"><small><b>Qty: </b>{{ number_format($inv['qty']) }}</small></div>
					<div class="col-8"><small><b>Amount: </b>₱ {{ number_format($inv['amount'], 2) }}</small></div>
				</div>
			</td>
			<td class="p-2 text-center align-middle d-none d-lg-table-cell">{{ number_format($inv['qty']) }}</td>
			<td class="p-2 text-center align-middle d-none d-lg-table-cell">₱ {{ number_format($inv['amount'], 2) }}</td>
			<td class="p-2 text-center align-middle d-none d-lg-table-cell">{{ $inv['owner'] }}</td>
			<td class="p-2 text-center align-middle d-none d-lg-table-cell">
				<span class="badge badge-{{ $badge }}">{{ $inv['status'] }}</span>
			</td>
			<td class="text-center align-middle p-2">
				@if($inv['status'] == 'For Approval')
					<a href="/beginning_inventory/{{ $inv['name'] }}">View Items</a>
				@else
					<a href="#" data-toggle="modal" data-target="#{{ $inv['name'] }}-Modal">View Items</a>
				@endif
				<span class="badge badge-{{ $badge }} d-xl-none">{{ $inv['status'] }}</span>

				<div class="modal fade" id="{{ $inv['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
						<div class="modal-content">
							<form action="{{ $modalForm }}" id="{{ $inv['name'] }}-form" method="post">
								@csrf
								<div class="modal-header bg-navy">
									<div class="row text-left">
										<div class="col-12">
											<h5>Beginning Inventory</h5>
										</div>
										<div class="col-12">
											<h6 class="font-responsive">{{ $branchLabel }}</h6>
										</div>
									</div>
									<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>
								<div class="modal-body p-2">
									<span class="d-block text-left">Inventory Date:<b>{{ $inv['transaction_date'] }}</b></span>
									<span class="d-block text-left">Submitted By:<b>{{ $inv['owner'] }}</b></span>

									<input type="text" class="form-control mt-2 mb-2 item-search-input" name="search" placeholder="Search" style="font-size: 9pt"/>

									<table class="table mt-2 items-table-in-modal" style="font-size: 9pt;">
										<thead>
											<th class="text-center p-1 align-middle">Item Code</th>
											<th class="text-center p-1 align-middle">Opening Stock</th>
											<th class="text-center p-1 align-middle">Price</th>
										</thead>
										<tbody>
											@forelse ($inv['items'] as $item)
											<tr>
												<td class="text-center p-1 align-middle">
													<div class="d-none">{{ strip_tags($item['item_description']) }}</div>
													<div class="d-flex flex-row justify-content-start align-items-center">
														<div class="p-1 text-left">
															<a href="{{ $item['image'] }}" class="view-images" data-item-code="{{ $item['item_code'] }}">
																<img src="{{ $item['image'] }}" alt="{{ Illuminate\Support\Str::slug(strip_tags($item['item_description']), '-') }}" width="40" height="40">
															</a>
														</div>
														<div class="p-1 m-0">
															<span class="font-weight-bold">{{ $item['item_code'] }}</span>
														</div>
													</div>
												</td>
												<td class="text-center p-1 align-middle">
													<b id="{{ $inv['name'].'-'.$item['item_code'] }}-qty">{!! $item['opening_stock'] !!}</b>
													@if ($inv['status'] == 'Approved')
													<input id="{{ $inv['name'].'-'.$item['item_code'] }}-new-qty" type="text" class="form-control text-center d-none" name="item[{{ $item['item_code'] }}][qty]" value={{ $item['opening_stock'] }} style="font-size: 10pt;"/>
													@endif
													<small class="d-block">{{ $item['uom'] }}</small>
												</td>
												<td class="text-center p-1 align-middle">
													<div class="row p-0">
														<div class="col-9 p-0" style="white-space: nowrap">
															@if (in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv['status'] == 'For Approval')
															₱ <input type="text" name="price[{{ $item['item_code'] }}][]" value="{{ number_format($item['price'], 2) }}" style="text-align: center; width: 60px" required/>
															@elseif ($inv['status'] == 'Approved')
															<input id="{{ $inv['name'].'-'.$item['item_code'] }}-new-price" type="text" class="form-control text-center d-none" name="item[{{ $item['item_code'] }}][price]" value={{ $item['price'] }} style="font-size: 10pt;"/>
															<span id="{{ $inv['name'].'-'.$item['item_code'] }}-price">₱ {{ number_format($item['price'], 2) }}</span>
															<br>
															@else
															₱ {{ number_format($item['price'], 2) }}
															@endif
														</div>
														@php
															$allowedUsers = ['jave.kulong@fumaco.local', 'albert.gregorio@fumaco.local', 'clynton.manaois@fumaco.local', 'arjie.villanueva@fumaco.local', 'jefferson.ignacio@fumaco.local'];
														@endphp
														@if (in_array(Auth::user()->wh_user, $allowedUsers) || in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
															<div class="col-3 p-0 {{ $inv['status'] != 'Approved' ? 'd-none' : null }}">
																<button type="button" class="btn btn-primary btn-xs allow-edit" data-inv="{{ $inv['name'] }}" data-target="{{ $inv['name'].'-'.$item['item_code'] }}"><i class="fa fa-edit"></i></button>
															</div>
														@endif
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="4" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
													<div class="w-100 item-description">{!! $item['item_description'] !!}</div>
													<span class="d-none">{{ $item['item_code'] }}</span>
												</td>
											</tr>
											@empty
											<tr>
												<td class="text-center text-uppercase text-muted" colspan="4">No Item(s)</td>
											</tr>
											@endforelse
										</tbody>
									</table>
								</div>
								<div class="container text-left">
									<label style="font-size: 9pt;"></label>
									<textarea rows="5" class="form-control" style="font-size: 9pt;" readonly>{{ $inv['remarks'] }}</textarea>
								</div>
								@if ($inv['status'] == 'Approved')
								<div class="modal-footer">
									<div class="container-fluid">
										<button type='button' class="btn btn-info w-100 mb-2 update-btn d-none" id="{{ $inv['name'] }}-update" data-form="#{{ $inv['name'] }}-form">Update</button>
										<button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $inv['name'] }}-Modal">Cancel</button>
										<div class="modal fade" id="cancel-{{ $inv['name'] }}-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
											<div class="modal-dialog" role="document">
												<div class="modal-content">
													<div class="modal-header bg-navy">
														<h6 id="exampleModalLabel">Cancel Beginning Inventory?</h6>
														<button type="button" class="close">
															<span aria-hidden="true" style="color: #fff" onclick="close_modal('#cancel-{{ $inv['name'] }}-Modal')">&times;</span>
														</button>
													</div>
													<div class="modal-body">
														<div class="callout callout-danger text-justify">
															<i class="fas fa-info-circle"></i> Canceling beginning inventory record will also cancel submitted product sold records.
														</div>
													</div>
													<div class="modal-footer">
														<a href="/cancel/approved_beginning_inv/{{ $inv['name'] }}" class="btn btn-primary w-100 submit-once">Confirm</a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								@endif
							</form>
						</div>
					</div>
				</div>
			</td>
		</tr>
	@empty
		<tr>
			<td class="text-center text-uppercase text-muted" colspan="7">No submitted beginning inventory</td>
		</tr>
	@endforelse
	</tbody>
</table>
<div class="float-right mt-4">
	{{ $beginningInventory->appends(request()->query())->links('pagination::bootstrap-4') }}
</div>
