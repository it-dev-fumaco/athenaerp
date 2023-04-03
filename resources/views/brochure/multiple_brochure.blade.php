@extends('layout', [
    'namePage' => 'Item Profile',
    'activePage' => 'item_profile',
])

@section('content')
<div class="container-fluid bg-white">
    <form action="/add_to_brochure_list" id="add-to-brochure-form" method="post">
        @csrf
        <input type="checkbox" class="d-none" name="generate_page" checked/>
        <input type="checkbox" class="d-none" name="save" checked/>
        <div class="row p-3">
            <div class="col-6">
                <table class="table table-bordered">
                    <tr>
                        <th>Project</th>
                        <td class="p-1"><input type="text" class="form-control p-1" name="project" value="{{ $project }}" placeholder="Enter Project Name" required></td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td class="p-1"><input type="text" class="form-control p-1" name="customer" value="{{ $customer }}" placeholder="Enter Customer Name"></td>
                    </tr>
                </table>
            </div>
            @if ($content)
                <div class="col-12">
                    <table class="table table-bordered w-100" id="brochures-table">
                        <thead>
                            <colgroup>
                                <col style="width: 15%;">
                                <col style="width: 15%;">
                                <col style="width: 20%;">
                                <col style="width: 35%;">
                                <col style="width: 15%;">
                            </colgroup>
                            <tr>
                                <th class="text-center">Location</th>
                                <th class="text-center">Fitting Type / Reference</th>
                                <th class="text-center">Item Name</th>
                                <th class="text-center">Description</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="sortable"> 
                            @foreach ($content as $i => $item)
                                <tr id="{{ $item['key'] }}">
                                    <td>
                                        <div class="row">
                                            <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                                                <i class="fas fa-arrows-alt" style="font-size: inherit;"></i>
                                            </div>
                                            <div class="col-11">
                                                <input type="text" class="form-control p-1" name="location[{{ $item['key'] }}]" value="{{ $item['location'] }}" placeholder="Enter Location">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control p-1" name="fitting_type[{{ $item['key'] }}]" value="{{ $item['reference'] }}" placeholder="Enter Fitting Type">
                                    </td>
                                    <td>
                                        <input type="hidden" name="item_codes[]" value="{{ $item['item_code'] }}">
                                        <input type="hidden" name="id_arr[]" value="{{ $item['key'] }}">
                                        <div class="row">
                                            <div class="col-3" style="display: flex; justify-content: center; align-items: center;">
                                                <label>{{ $item['item_code'] }}</label>
                                            </div>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="item_name[{{ $item['item_code'] }}]" value="{{ $item['item_name'] }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control p-1" name="description[{{ $item['item_code'] }}]" value="{{ $item['description'] }}" placeholder="Enter Description">
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col-10">
                                                <button type="button" class="btn btn-sm btn-primary w-100 open-attributes-modal" data-item-code="{{ $item['item_code'] }}" style="font-size: 9pt;"><i class="fa fa-edit"></i> Attributes</button>
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-secondary remove-confirmation" data-target="#remove-item-modal" data-item-code="{{ $item['item_code'] }}" style="font-size: 9pt;" data-item-name="{{ $item['item_name'] }}" data-key="{{ $item['key'] }}"><i class="fa fa-trash"></i> </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary preview-brochure" data-target="#brochure-preview-modal"><i class="fa fa-save"></i>&nbsp;Generate Brochure</button>
                </div>  
            @else
                <div class="col-12 text-center mt-2">
                    <h5>No saved item(s)</h5>
                </div>
            @endif
        </div>
    </form>
</div>

<div class="modal fade" id="attributes-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title" id="exampleModalLabel">Edit Attributes - <span id="form-item-code"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="brochure-attribute-form" action="/update_brochure_attributes" method="POST" autocomplete="off">
                    @csrf
                    <div id="brochure-item-attribute-div"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="remove-item-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title" id="exampleModalLabel">Remove <b id="remove-item-code"></b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="font-size: 12pt;">
                Remove <b id="remove-name"></b> from the list?
                <span id="remove-key" class="d-none"></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger remove-row">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="brochure-preview-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header bg-navy">
                <h5 class="modal-title" id="exampleModalLabel">Generate Brochure</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="brochure-preview-container"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .hidden-attrib{
        background-color: #E6E6E6;
    }
    table input, .modal, table{
        font-size: 9pt !important;
    }
</style>
@endsection
@section('script')
    <script>
        $(document).ready(function (){
            $('.sortable').sortable();

            $(document).on('click', '.preview-brochure', function (e){
                e.preventDefault();
				$('#add-to-brochure-form').submit();
            });

            $(document).on('click', '.upload-image-placeholder', function(e) {
                e.preventDefault();
                var idx = $(this).data('idx');
                var item_code = $(this).data('item-code');
                $('#item-image-container-id').val($(this).attr('id'));

                $.ajax({
                    type: 'get',
                    url: '/generate_brochure',
                    data:  {
                        item_code: item_code,
                        get_images: 1
                    },
                    success: function(response){
                        $('#brochure-images-container').html(response);
                        $('#item-image-order').val(idx);
                        $('#item-code').val(item_code);
                        $('input[name="item_code"]').val(item_code);
                        $('#item-image-order-1').val(idx);
                    },
                });

                $('#select-file-modal').modal('show');
            });

            $(document).on('click', '.remove-image-btn', function(e) {
                e.preventDefault();

                var el = $(this);
                var details = {
                    'id': $(this).data('id'),
                    'item_image_id': $(this).data('item-image-id'),
                    '_token': '{{ csrf_token() }}'
                }

                $.ajax({
                    url: '/remove_image',
                    type: 'POST',
                    data: details,
                    success: function(response){
                        if(response.status == 0){
                            showNotification("danger", response.message, "fa fa-info");
                        }else{
                            $('#' + details.item_image_id + '-actual').addClass('d-none');
                            $('#' + details.item_image_id).removeClass('d-none');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
                    }
                });
            });

            $(document).on('click', '.open-attributes-modal', function (e){
                var item_code = $(this).data('item-code');

                $('#form-item-code').text(item_code);

                $.ajax({
					type: 'GET',
					url: '/get_item_attributes/' + item_code,
					success: function(response){
						$('#brochure-item-attribute-div').html(response);
                        $('#attributes-modal').modal('show');
					}
				});
            });

            $(document).on('click', '.remove-confirmation', function (e){
                e.preventDefault();
                var item_code = $(this).data('item-code');
                var item_name = $(this).data('item-name');
                var key = $(this).data('key');

                $($(this).data('target')).modal('show');
                $('#remove-item-code').text(item_code);
                $('#remove-name').text(item_name);
                $('#remove-key').text(key);
            });

            $(document).on('click', '.remove-row', function (e){
                e.preventDefault();
                var item_code = $('#remove-item-code').text();
                var key = $('#remove-key').text();
                $('.modal').modal('hide');
                $('#' + key).remove();

                $.ajax({
					type: 'get',
					url: '/remove_from_brochure_list/' + key,
					success: function(response){
                        count_brochures();
					},
					error: function(jqXHR, textStatus, errorThrown) {
					}
				});
            });

            $(document).on('submit', '#brochure-attribute-form', function(e) {
				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: $(this).attr('action'),
					data: $(this).serialize(),
					success: function(response){
						showNotification("success", 'Attributes Updated.', "fa fa-info");
                        $('#attributes-modal').modal('hide');
					},
					error: function(jqXHR, textStatus, errorThrown) {
					}
				});
			});

            $(document).on('click', '.hidden-attributes', function (){
                var val = '';
                if($(this).is(':checked')){
                    val = $(this).data('attribute');
                    $(this).next().next().next('small').text('Unhide');
                    $(this).closest('li').addClass('hidden-attrib');
                }else{
                    $(this).closest('li').removeClass('hidden-attrib');
                    $(this).next().next().next('small').text('Hide');
                }

                $(this).next('input').val(val);
            });

            function showNotification(color, message, icon){
				$.notify({
                    icon: icon,
                    message: message
				},{
                    type: color,
                    timer: 500,
                    z_index: 1060,
                    placement: {
                        from: 'top',
                        align: 'center'
                    }
				});
			}

            function count_brochures(){
				$.ajax({
					type: 'GET',
					url: '/count_brochures',
					success: function(response){
						if(parseInt(response.count) > 0){
							$('.brochures-icon').removeClass('d-none').addClass('d-inline');
							$('.brochure-arr-count').text(response.count);
						}else{
							$('.brochures-icon').addClass('d-none').removeClass('d-inline');
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						// showNotification("danger", 'Something went wrong. Please contact your system administrator.', "fa fa-info");
					}
				});
			}
        });
    </script>
@endsection