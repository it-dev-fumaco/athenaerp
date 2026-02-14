@extends('layout', [
    'namePage' => 'Item Attribute',
    'activePage' => 'item_profile',
])

@section('content')
    <div class="container p-1 p-md-3" style="max-width: 100%;">
        <div class="row mb-2">
            <div class="col-12">
                <a href="{{ url('/get_item_details/' . $itemCode) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Item Profile
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                
                @if ($itemDetails)
                <div class="card card-info card-outline">
                    <div class="card-body" style="font-size: 14px;">
                        <form action="/save_item_info" method="POST" id="updateItemInfo">
                            @csrf
                            <h5 class="text-muted mb-3">Basic Information</h5>
                            <div class="form-group">
                                <label for="item_name">Item Code</label>
                                <input type="text" class="form-control" readonly id="item-code-input" value="{{ $itemCode }}">
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="item_code" value="{{ $itemCode }}">
                                <label for="item_name">Item Name</label>
                                <textarea name="item_name" id="item_name" class="form-control" placeholder="Item Name" rows="4" required>{{ $itemDetails->item_name }}</textarea>
                            </div>
                            <div class="form-group mt-3">
                                <label for="item-description">Description</label>
                                <textarea name="description" id="item-description" class="form-control" placeholder="Description" rows="7" required>{!! $itemDetails->description !!}</textarea>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="is-stock-item" value="1" name="is_stock_item" {{ $itemDetails->is_stock_item ? 'checked' : '' }}>
                                    <label for="is-stock-item" class="custom-control-label">Is Stock Item</label>
                                </div>
                            </div>
                            <div class="border-top text-center pt-3">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-8">
                <div class="card card-info card-outline">
                    <div class="card-body" style="min-height: 627px;">
                        <h5 class="text-muted mb-3">Item Attribute(s)</h5>
                        <div id="itemAttributes">
                            <h5 class="text-center text-muted text-uppercase my-5">Please select an item to update</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Select Attribute Modal -->
    <div class="modal fade" id="selectAttributeModal" tabindex="-1" role="dialog" aria-labelledby="selectAttributeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="selectAttributeModalLabel">Select value for <span id="attributeName" class="font-weight-bold"></span></h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-2" id="search-attribute-values" placeholder="Seach..." name="search" autocomplete="off">
                    <input type="hidden" id="selectedAttributeId">
                    <div id="itemAttributeValDiv"></div>
                </div>
                <div class="modal-fo1oter">
                    <div class="d-flex justify-content-between border-top p-3">
                        <small>Attribute value not found? <a href="#" data-toggle="modal" data-target="#addAttributeModal" id="add-attribute-btn">Add Value.</a></small>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Attribute Modal -->
    <div class="modal fade" id="deleteAttributeModal" tabindex="-1" role="dialog" aria-labelledby="deleteAttributeModalLabel" aria-hidden="true">
        <form action="/delete_item_attribute" method="POST" id="delete-attribute-form">
            @csrf
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger">
                        <h6 class="modal-title" id="deleteAttributeModalLabel">Delete attribute value for <span id="deleteAttributeName" class="font-weight-bold"></span></h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="attribute_value_name" id="deleteAttributeValueName">
                        <p class="text-center">Delete attribute value <span id="delete-attribute-value-name" class="font-weight-bold"></span> of this item?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-outline-primary">Confirm</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Add Attribute Modal -->
    <div class="modal fade" id="addAttributeModal" tabindex="-1" role="dialog"
        aria-labelledby="addAttributeModalLabel" aria-hidden="true">
        <form action="/save_item_attribute" method="POST" id="add-attribute-form">
            @csrf
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info">
                        <h6 class="modal-title" id="addAttributeModalLabel">Add attribute value for <span id="addAttributeName" class="font-weight-bold"></span></h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="font-size: 13px;">
                        <input type="text" name="attribute" id="addAttributeNameInput" class="d-none">
                        <div class="form-group">
                            <label for="attribute-value">Attribute Value *</label>
                            <input type="text" class="form-control" id="attribute-value" name="attribute_value" placeholder="Attribute Value" required>
                        </div>
                        <div class="form-group">
                        <label for="abbreviation">Abbreviation *</label>
                        <input type="text" class="form-control" id="abbreviation" aria-describedby="abbrHelp" name="abbreviation" placeholder="Abbreviation" required>
                        <small id="abbrHelp" class="form-text text-muted">This will be appended to the Item Code of the variant. For example, if your abbreviation is "SM", and the item code is "T-SHIRT", the item code of the variant will be "T-SHIRT-SM"</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-outline-primary">Save</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <style>
        textarea {
            font-size: 12px !important;
        }
    </style>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            loadItemAttribute()

            $(document).on('click', '.edit-attribute-btn', function(e) {
                e.preventDefault();

                const attributeName = $(this).data('attribute-name')

                $('#attributeName').text(attributeName)

                $("#selectedAttributeId").val($(this).data('attribute-id'))

                loadItemAttributeValues(attributeName)
            });
            
            $(document).on('keyup', '#search-attribute-values', function (e){
                e.preventDefault();
                loadItemAttributeValues($('#attributeName').text())
            });

            function loadItemAttributeValues(attribute, page){
                $.ajax({
                    type: 'GET',
                    url: '/item_attribute_values/' + attribute,
                    data: {
                        search: $('#search-attribute-values').val(),
                        page
                    },
                    success: function(response) {
                        $('#itemAttributeValDiv').html(response);
                    }
                });
            }

            function loadItemAttribute(){
                const item_code = $('#item-code-input').val()
                $.ajax({
                    type: 'GET',
                    url: '/item_attribute/' + item_code,
                    success: function(response) {
                        $('#itemAttributes').html(response);
                    }
                });
            }

            $('#selectAttributeModal').on('hide.bs.modal', function (e) {
                $('#search-attribute-values').val("")
            });

            $(document).on('click', '#attribute-values-pagination a', function(event){
                event.preventDefault();
                var page = $(this).attr('href').split('page=')[1];
                loadItemAttributeValues($('#attributeName').text(), page);
            });

            $(document).on('click', '#add-attribute-btn', function(e) {
                e.preventDefault();

                const attributeName = $('#attributeName').text()

                $('#addAttributeName').text(attributeName)
                $('#addAttributeNameInput').val(attributeName)
            });

            $('#add-attribute-form').submit(function(e){
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function(response){
                        if (response.error) {
                            showNotification("danger", response.message, "fa fa-info");
                        }else{
                            loadItemAttributeValues($('#addAttributeName').text())
                            showNotification("success", response.message, "fa fa-check");
                            $('#addAttributeModal').modal('hide');
                        }
                    }
                });
            });

            $('#delete-attribute-form').submit(function(e){
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function(response){
                        if (response.error) {
                            showNotification("danger", response.message, "fa fa-info");
                        }else{
                            loadItemAttribute()
                            showNotification("success", response.message, "fa fa-check");
                            $('#deleteAttributeModal').modal('hide');
                        }
                    }
                });
            });

            $('#updateItemInfo').submit(function(e){
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function(response){
                        if (response.error) {
                            showNotification("danger", response.message, "fa fa-info");
                        } else {
                            showNotification("success", response.message, "fa fa-check");
                        }
                    }
                });
            });

            $(document).on('click', '.delete-attribute-btn', function(e) {
                e.preventDefault();

                const id = $(this).data('attribute-id')
                const attriName = $(this).data('attribute-name')
                const attriVal = $(this).data('attribute-value')

                $('#delete-attribute-value-name').text(attriVal)
                $('#deleteAttributeName').text(attriName)

                $('#deleteAttributeValueName').val(id)
            });

            $(document).on('click', '.select-attribute-value-btn', function(e) {
                e.preventDefault();
                const attrValue = $(this).data('name')
                const selectVariantId = $("#selectedAttributeId").val()
                
                $.ajax({
                    type: 'POST',
                    url: '/update_item_variant',
                    data: {attribute_value: attrValue, name: selectVariantId, item_code: '{{ $itemCode }}'},
                    success: function(response){
                        if (response.error) {
                            showNotification("danger", response.message, "fa fa-info");
                        } else {
                            loadItemAttribute()
                            showNotification("success", response.message, "fa fa-check");
                            $('#selectAttributeModal').modal('hide');
                        }
                    }
                });
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
        });
    </script>
@endsection
