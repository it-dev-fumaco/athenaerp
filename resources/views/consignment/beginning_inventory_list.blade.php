@extends('layout', [
    'namePage' => 'Stock Adjustments List',
    'activePage' => 'dashboard',
])

@section('content')
<div class="content">
	<div class="content-header p-0">
		<div class="container">
			<div class="row pt-1">
				<div class="col-md-12 p-0 m-0">
					<div class="card card-lightblue">
						<div class="card-header p-2">
                            <div class="d-flex flex-row align-items-center justify-content-between" style="font-size: 9pt;">
                                <div class="p-0">
                                    <span class="font-responsive font-weight-bold text-uppercase m-0 p-0">Beginning Inventory List</span>
                                </div>
                                <div class="p-0">
                                    <a href="/beginning_inventory" class="btn btn-sm btn-primary m-0"><i class="fas fa-plus"></i> Create</a>
                                </div>
                            </div>
                        </div>
						<div class="card-body p-0">
							@if(session()->has('success'))
							<div class="callout callout-success font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{!! session()->get('success') !!}</div>
							@endif
							@if(session()->has('error'))
							<div class="callout callout-danger font-responsive text-center pr-1 pl-1 pb-3 pt-3 m-2">{!! session()->get('error') !!}</div>
							@endif
							<div id="accordion">
								<button type="button" class="btn btn-link border-bottom btn-block text-left" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-size: 10pt;">
									<i class="fa fa-filter"></i> Filters
								</button>
								<div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
									<div class="card-body p-0">
										<form action="/beginning_inv_list" method="get">
											<div class="row p-2">
												<div class="col-12 col-lg-4 col-xl-4">
													<input type="text" class="form-control filters-font" name="search" value="{{ request('search') ? request('search') : null }}" placeholder="Search"/>
												</div>
												<div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
													<select name="store" class="form-control filters-font">
														<option value="" disabled {{ !request('store') ? 'selected' : null }}>Select a store</option>
														@foreach ($consignment_stores as $store)
														<option value="{{ $store }}" {{ request('store') == $store ? 'selected' : null }}>{{ $store }}</option>
														@endforeach
													</select>
												</div>
												<div class="col-12 col-lg-4 col-xl-2 mt-2 mt-lg-0">
													<input type="text" name="date" id="date-filter" class="form-control filters-font" value="" />
												</div>
												<div class="col-12 col-lg-2 col-xl-1 mt-2 mt-lg-0">
													<button type="submit" class="btn btn-primary filters-font w-100"><i class="fas fa-search"></i> Search</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
							
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
								@forelse ($inv_arr as $inv)
									@php
										$badge = 'secondary';
										if($inv['status'] == 'For Approval'){
											$badge = 'primary';
										}else if($inv['status'] == 'Approved'){
											$badge = 'success';
										}else if($inv['status'] == 'Cancelled'){
											$badge = 'secondary';
										}

										$modal_form = in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']) && $inv['status'] == 'For Approval' ? '/approve_beginning_inv/'.$inv['name'] : '/stock_adjust/submit/'.$inv['name'];
									@endphp
									<tr>
										<td class="p-2 text-center align-middle d-none d-lg-table-cell">
											<span style="white-space: nowrap">{{ $inv['transaction_date'] }}</span>
										</td>
										<td class="p-2 text-left align-middle text-xl-center">
											<span class="d-block">{{ $inv['branch'] }}</span>
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
														<form action="{{ $modal_form }}" id="{{ $inv['name'] }}-form" method="post">
															@csrf
															<div class="modal-header bg-navy">
																<div class="row text-left">
																	<div class="col-12">
																		<h5>Beginning Inventory</h5>
																	</div>
																	<div class="col-12">
																		<h6 class="font-responsive">{{ $inv['branch'] }}</h6>
																	</div>
																</div>
																<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																</button>
															</div>
															<div class="modal-body p-2">
																<span class="d-block text-left">Inventory Date:<b>{{ $inv['transaction_date'] }}</b></span>
																<span class="d-block text-left">Submitted By:<b>{{ $inv['owner'] }}</b></span>

																<input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
																
																<table class="table mt-2" id="items-table" style="font-size: 9pt;">
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
																						$allowed_users = ['jave.kulong@fumaco.local', 'albert.gregorio@fumaco.local', 'clynton.manaois@fumaco.local', 'arjie.villanueva@fumaco.local', 'jefferson.ignacio@fumaco.local'];
																					@endphp
																					@if (in_array(Auth::user()->wh_user, $allowed_users) || in_array(Auth::user()->user_group, ['Consignment Supervisor', 'Director']))
																						<div class="col-3 p-0 {{ $inv['status'] != 'Approved' ? 'd-none' : null }}">
																							<button type="button" class="btn btn-primary btn-xs allow-edit" data-inv="{{ $inv['name'] }}" data-target="{{ $inv['name'].'-'.$item['item_code'] }}"><i class="fa fa-edit"></i></button>
																						</div>
																					@endif
																				</div>
																			</td>
																		</tr>
																		<tr>
																			<td colspan="4" class="text-justify pt-0 pb-1 pl-1 pr-1" style="border-top: 0 !important;">
																				<div class="w-100 item-description">{{ strip_tags($item['item_description']) }}</div>
																				<span class="d-none">
																					{{ $item['item_code'] }}
																				</span>
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
																<textarea id="" rows="5" class="form-control" style="font-size: 9pt;" readonly>{{ $inv['remarks'] }}</textarea>
															</div>
															{{-- Update button for approved records --}}
															@if ($inv['status'] == 'Approved')
															<div class="modal-footer">
																<div class="container-fluid">
																	<button type='button' class="btn btn-info w-100 mb-2 update-btn d-none" id="{{ $inv['name'] }}-update" data-form="#{{ $inv['name'] }}-form">Update</button>
																	<button type="button" class="btn btn-secondary w-100" data-toggle="modal" data-target="#cancel-{{ $inv['name'] }}-Modal">
																		Cancel
																	</button>
																	  
																	  <!-- Modal -->
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
																					{{-- @if ($inv['sold'])  --}}
																						<div class="callout callout-danger text-justify">
																							<i class="fas fa-info-circle"></i> Canceling beginnning inventory record will also cancel submitted product sold records of the following:
																						</div>
																						<div class="container-fluid" id="cancel-{{ $inv['name'] }}-container">
																							<table class="table">
																								<tr>
																									<th class="text-center" style='width: 60%;'>Item</th>
																									<th class="text-center" style="width: 20%;">Qty</th>
																									<th class="text-center" style="width: 20%;">Amount</th>
																								</tr>
																							</table>
																						</div>
																					{{-- @else --}}
																						<div class="callout callout-danger text-justify">
																							<i class="fas fa-info-circle"></i> Canceling beginnning inventory record will also cancel submitted product sold records.
																						</div>
																					{{-- @endif --}}
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
								{{ $beginning_inventory->appends(request()->query())->links('pagination::bootstrap-4') }}
						  </div>
						
						  </div>
						</div>
              
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@section('style')
    <style>
        .morectnt span {
            display: none;
        }
        .last-row{
            width: 15% !important;
        }
		.mobile-first{
			width: 35% !important;
		}
        .filters-font{
            font-size: 13px !important;
        }
        .item-code-container{
            text-align: justify;
            padding: 10px;
        }
		.modal{
			background-color: rgba(0,0,0,0.4);
		}
        @media (max-width: 575.98px) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media (max-width: 767.98px) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
        @media only screen and (min-device-width : 768px) and (max-device-width : 1024px) and (orientation : portrait) {
			.mobile-first{
				width: 50% !important;
			}
            .last-row{
                width: 20%;
            }
            .filters-font{
                font-size: 9pt;
            }
            .item-code-container{
                 display: flex;
                 justify-content: center;
                 align-items: center;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            var from_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[0])->format("Y-M-d") : $earliest_date }}';
            var to_date = '{{ request("date") ? Carbon\Carbon::parse(explode(" to ", request("date"))[1])->format("Y-M-d") : Carbon\Carbon::now()->format("Y-M-d") }}';

            $('#date-filter').daterangepicker({
                opens: 'left',
                startDate: from_date,
                endDate: to_date,
                locale: {
                    format: 'YYYY-MMM-DD',
                    separator: " to "
                },
            });

            $(document).on('click', '.show-more', function(e) {
                e.preventDefault();
                if ($(this).hasClass("sample")) {
                    $(this).removeClass("sample");
                    $(this).text(showChar);
                } else {
                    $(this).addClass("sample");
                    $(this).text(hideChar);
                }

                $(this).parent().prev().toggle();
                $(this).prev().toggle();
                return false;
            });

            var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
            $('.item-description').each(function() {
                var content = $(this).text();
                if (content.length > showTotalChar) {
                    var con = content.substr(0, showTotalChar);
                    var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                    var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                    $(this).html(txt);
                }
            });

            // always show filters on pc, allow collapse of filters on mobile
            if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) { // mobile/tablet
				$('#headingOne').removeClass('d-none');
                $('#collapseOne').removeClass('show');
			}else{ // desktop
                $('#headingOne').addClass('d-none');
                $('#collapseOne').addClass('show');
			}

			$(document).on('click', '.allow-edit', function (){
				var target = $(this).data('target');
				var inventory = $(this).data('inv');
				// users allowed to edit qty and price
				var allowed_users = ['jave.kulong@fumaco.local', 'albert.gregorio@fumaco.local', 'clynton.manaois@fumaco.local', 'arjie.villanueva@fumaco.local', 'jefferson.ignacio@fumaco.local'];
				var allowed_user_group = ['Director', 'Consignment Supervisor'];
				var user = '{{ Auth::user()->wh_user }}';
				var user_group = '{{ Auth::user()->user_group }}';
				if(allowed_users.indexOf(user) > -1 || allowed_user_group.indexOf(user_group) > -1){
					$('#' + target + '-qty').addClass('d-none');
					$('#' + target + '-new-qty').removeClass('d-none');

					$('#' + target + '-price').addClass('d-none');
					$('#' + target + '-new-price').removeClass('d-none');

					$('#' + inventory + '-update').removeClass('d-none');
				}
			});

			$(document).on('click', '.update-btn', function (){
				var form = $(this).data('form');
				$(form).submit();
			});

			$("#item-search").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				$("#items-table tr").filter(function() {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
			});
        });
    </script>
@endsection