@extends('layout', [
    'namePage' => 'Material Issue',
    'activePage' => 'stock-reservation',
])

@section('content')
<div class="content">
	<div class="content-header pt-0">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-6 offset-md-3">
					<div class="card card-info card-outline">
						<div class="card-header">
							  <h5 class="card-title m-0 font-weight-bold">Stock Reservation Form</h5>
						</div>
						<div class="card-body table-responsive p-0">
                            <form action="/create_reservation" method="POST" id="reservation-form" autocomplete="off">
                                @csrf
                                <div class="row m-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="">Item Code</label>
                                            <select class="form-control" name="item_code" id="select-item-code-c"></select>
                                        </div>
                                        <div class="form-group">
                                            <label for="">Description</label>
                                            <textarea rows="4" name="item_description" class="form-control" style="height: 124px;" id="description-c"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="">Notes</label>
                                            <textarea rows="4" class="form-control" name="notes" style="height: 124px;"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="">Warehouse</label>
                                                    <select class="form-control" name="warehouse" id="select-warehouse-c"></select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="">Reserve Qty</label>
                                                    <input type="text" name="reserve_qty" class="form-control" value="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="">Stock UoM</label>
                                                    <input type="text" name="stock_uom" class="form-control" id="stock-uom-c">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="">Reservation Type</label>
                                                    <select name="type" class="form-control" id="select-type-c">
                                                        <option value="">Select Type</option>
                                                        <option value="In-house">In-house</option>
                                                        <option value="Online Shop">Online Shop</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group for-online-shop-type d-none">
                                                    <label>Valid until</label>
                                                    <input type="text" name="valid_until" class="form-control" id="date-valid-until-c">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group for-in-house-type d-none">
                                                    <label for="">Sales Person</label>
                                                    <select class="form-control" name="sales_person" id="select-sales-person-c"></select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group for-in-house-type d-none">
                                                    <label for="">Project</label>
                                                    <select class="form-control" name="project" id="select-project-c"></select>
                                                </div>
                                            </div>
                                        </div>                                        
                                    </div>
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<style>
    .select2{
        width: 100% !important;
    }
    .select2-selection__rendered {
        line-height: 31px !important;
    }
    .select2-container .select2-selection--single {
        height: 37px !important;
        padding-top: 1.5%;
    }
    .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        $('#stock-reservation-form').submit(function(e){
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.error) {
                        $('#myModal').modal('show'); 
                        $('#myModalLabel').html(response.modal_title);
                        $('#desc').html(response.modal_message);
                        
                        return false;
                    }else{
                        $('#myModal1').modal('show'); 
                        $('#myModalLabel1').html(response.modal_title);
                        $('#desc1').html(response.modal_message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        });
        
        $(document).on('select2:select', '#select-item-code-c', function(e){
            var data = e.params.data;
            $('#stock-uom-c').val(data.stock_uom);
            $('#description-c').val(data.description);
        });
        
        $('#select-type-c').change(function(){
            if($(this).val()) {
                if($(this).val() == 'In-house') {
                    $('.for-in-house-type').removeClass('d-none');
                    $('.for-online-shop-type').addClass('d-none');
                } else {
                    $('.for-in-house-type').addClass('d-none');
                    $('.for-online-shop-type').removeClass('d-none');
                }
            }
        });

        $('#select-item-code-c').select2({
            placeholder: 'Select Item',
            ajax: {
                url: '/items',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results:response
                    };
                },
                cache: true
            }
        });

        $('#select-warehouse-c').select2({
            placeholder: 'Select Warehouse',
            ajax: {
                url: '/warehouses',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results:response
                    };
                },
                cache: true
            }
        });

        $('#select-project-c').select2({
            placeholder: 'Select Project',
            ajax: {
                url: '/projects',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results:response
                    };
                },
                cache: true
            }
        });

        $('#select-sales-person-c').select2({
            placeholder: 'Select Sales Person',
            ajax: {
                url: '/sales_persons',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results:response
                    };
                },
                cache: true
            }
        });

        $('#date-valid-until-c').datepicker({
            autoclose: true
        });
    });
</script>
@endsection