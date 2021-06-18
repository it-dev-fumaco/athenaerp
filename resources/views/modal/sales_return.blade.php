
<div class="modal fade" id="sales-return-modal">
	<form id="sales-return-form" method="POST" action="/submit_sales_return" autocomplete="off">
		 @csrf
         <input type="hidden" name="id">
		 <div class="modal-dialog" style="min-width: 35%;">
			  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title">Sales Return <small class="sales-return-status"></small></h4>
						<button type="button" class="close" data-dismiss="modal">&times;</button>
					</div>
					<div class="modal-body">
						 <div class="row">
							  <div class="col-md-12">
									<div class="box-header with-border">
										 <h4 class="box-title target-warehouse-display"></h4>
									</div>
									<div class="box-body" style="font-size: 12pt;">
										 <div class="row">
											  <div class="col-md-6 form-group">
													<label>Barcode</label>
													<input type="text" class="form-control" name="barcode" placeholder="Barcode" required>
											  </div>
											  <div class="col-md-6 form-group">
													<label>Qty</label>
													<input type="text" class="form-control" name="returned_qty" placeholder="Qty" required>
											  </div>
											  <div class="col-md-12">
													<div class="row">
														 <div class="col-md-4 mt-2">
															  <a class='sample item_image_link' data-height='720' data-lighter='samples/sample-01.jpg' data-width='1280' href="#">
																	<img src="{{ asset('storage/icon/no_img.png') }}" style="width: 100%;" class="item_image">
															  </a>
														 </div>
														 <div class="col-md-8 mt-2">
															  <span class="item-code-display d-block font-weight-bold">-</span>
															  <p class="item-description-display"></p>
															  <dl>
																	<dt>Return Qty</dt>
																	<dd>
																		 <p class="badge lbl-color" style="font-size: 12pt;">
																			  <span class="for-return-qty-display"></span> <span class="stock-uom-display"></span>
																		 </p>
																	</dd>
															  </dl>
														 </div>
													</div>
											  </div>
											  <div class="col-md-4 mt-2">
													<dl>
														 <dt>Reference No:</dt>
														 <dd class="ref_no"></dd>
													</dl>
											  </div>
											  <div class="col-md-8 mt-2">
													<dl>
														
														 <dt>Remarks:</dt>
														 <dd>
															  <textarea class="form-control remarks" rows="2" placeholder="Remarks" name="remarks"></textarea>
														 </dd>
													</dl>
											  </div>
										 </div>
									</div>
							  </div>
						 </div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> CLOSE</button>
						 <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> RETURN</button>
					</div>
			  </div>
		 </div>
	</form>
 </div>
