@extends('item_attrib_layout', [
    'namePage' => 'ERPInventory',
    'activePage' => 'dashboard',
])

@section('content')
    <div class="container-fluid align-center p-4">
        <div class="modal fade" id="preloader-modal" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <h6 class="text-center m-0"><i class="fas fa-spinner"></i> Adding attribute to items. Please wait.</h6>
                        <button type="button" class="btn btn-default mt-3 d-none btn-sm" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 align-middle">
                <h4 class="text-left m-1 pl-5">Template Item: <span class="font-weight-bold">{{ $itemParent->name }}</span> <small style="bordeR:">{{ $itemParent->description }}</small></h4>
            </div>
            <div class="col-md-4 form-inline">
                <div class="form-group col-8">
                    <select class="form-control" id="selec-item-attr"></select>
                </div>
                <button type="button" class="btn btn-primary ml-2" id="add-column"><i class="fas fa-plus"></i> Add</button>
                <button type="button" class="btn btn-secondary ml-2" id="reset-column"><i class="fas fa-redo"></i> Reset</button>
            </div>
            <div class="col-md-12 mt-3">
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Note:</h5>
                    Adding new attributes and value  to an existing variant code, will require to update as well the other variants codes on the same parent code.
                  </div>
                @if (\Session::has('message'))
                <div class="alert alert-success text-center mb-3 ml-3 mr-3">
                    <span>{!! \Session::get('message') !!}</span>
                </div>
                @endif
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h5 class="card-title m-0">Item Variant(s) <span class="badge badge-info">{{ collect($itemVariantsArr)->count() }}</span></h5>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                              <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                              <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <form action="/insert_attribute" method="POST" id="form-add">
                            @csrf
                            <input type="hidden" name="parentItem" value="{{ $itemParent->name }}">
                            <table class="table table-bordered table-hover" id="variants-table">
                                <thead>
                                    <tr>
                                        <th class="text-center align-middle">Item Code</th>
                                        @foreach ($itemAttributes as $itemAttribute)
                                        <th class="text-center align-middle">{{ $itemAttribute }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($itemVariantsArr as $row)
                                    <input type="hidden" name="itemCode[]" value="{{ $row['item_code'] }}">
                                    <tr>
                                        <td class="text-center align-middle">{{ $row['item_code'] }}</td>
                                        @foreach ($row['attributes'] as $attr)
                                        <td class="text-center align-middle">{{ $attr->attribute_value }}</td>
                                        @endforeach
                                        <input type="hidden" name="idx[]" value="{{ $attr->idx }}">
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-3">
                <div class="card collapsed-card card-danger card-outline">
                    <div class="card-header">
                        <h5 class="card-title m-0">Item Variants with Incomplete Attribute(s) <span class="badge badge-danger">{{ collect($itemsIncompleteAttr)->count() }}</span></h5>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                              <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-tool" data-card-widget="remove">
                              <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <table class="table table-bordered table-hover overflow-auto">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Item Code</th>
                                    @foreach ($itemAttributes as $itemAttribute)
                                    <th class="text-center align-middle">{{ $itemAttribute }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($itemsIncompleteAttr as $row)
                                <tr>
                                    <td class="text-center align-middle">{{ $row['item_code'] }}</td>
                                    @foreach ($row['attributes'] as $attr)
                                    <td class="text-center align-middle">{{ $attr->attribute_value }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <select id="attributeValues" class="d-none"></select>
    </div>

    <style>
		.select2{
			width: 100% !important;
		}
		.select2-selection__rendered {
			line-height: 27px !important;
		}
		.select2-container .select2-selection--single {
			height: 37px !important;
			/* padding-top: 1.5%; */
		}
		.select2-selection__arrow {
			height: 36px !important;
		}
	</style>

@endsection

@section('script')
    <script>
    $(document).ready(function() {
        $('#add-column').click(function(e){
            e.preventDefault();

            var column_name = $('#selec-item-attr').val();

            if(column_name) {
                var existing_columns = [];
                $('#variants-table tr').find('th').each(function(){
                    existing_columns.push($(this).text());
                });

                if(!existing_columns.includes(column_name)){
                    $('#variants-table').find('tr').each(function(){
                        $(this).find('td').last().after('<td><input type="hidden" name="newAttr[]" value="' + column_name + '"><select class="form-control custom-select2" name="newAttrVal[]" required> ' + $('#attributeValues').html() + '</select></td>');
                        $(this).find('th').last().after('<th class="text-center align-middle">' + column_name + '</th>');
                    });
                }

                $(this).attr('disabled', true);

                $('.custom-select2').select2();
            }
        });

        $('#reset-column').click(function(e){
            e.preventDefault();

            location.reload(); 
        });

        $('#preloader-modal').on('hidden.bs.modal', function (e) {
            location.reload(); 
        });

        $('#selec-item-attr').select2({
            dropdownParent: $('#selec-item-attr').parent(),
            placeholder: 'Select Item Attribute',
            ajax: {
                url: '/getAttributes',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response
                    };
                },
                cache: true
            }
        });

        $(document).on('select2:select', '#selec-item-attr', function(e){
            var data = e.params.data;
            
            $('#attributeValues').empty();
            $.ajax({
                type:"GET",
                url:"{{url('attribute_dropdown')}}?attribute_name="+encodeURIComponent(data.id),
                success:function(res){
                    $('#attributeValues').append('<option value="" selected disabled>- Select Value -</option>');
                    $.each(res,function(key,value){
                        $('#attributeValues').append('<option value="'+value+'">'+value+'</option>');
                    });
                }
            });
        });

        $('#form-add').submit(function(e){
            e.preventDefault();

            $('#preloader-modal').modal('show');
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.status) {
                        $('#preloader-modal h6').html(response.message);
                        $('#preloader-modal button').removeClass('d-none');
                    }else{
                        $('#preloader-modal h6').text(response.message);
                        $('#preloader-modal button').addClass('d-none');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occured.');
                }
            });
        });
    });
    </script>
@endsection