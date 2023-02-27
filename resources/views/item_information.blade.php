@php
    $package_weight = $item_details->package_weight && $item_details->package_weight > 0 ? trim($item_details->package_weight).' '.$item_details->weight_uom : '-';
    $package_length = $item_details->package_length && $item_details->package_length > 0 ? trim($item_details->package_length).' '.$item_details->package_dimension_uom : '-';
    $package_width = $item_details->package_width && $item_details->package_width > 0 ? trim($item_details->package_width).' '.$item_details->package_dimension_uom : '-';
    $package_height = $item_details->package_height && $item_details->package_height > 0 ? trim($item_details->package_height).' '.$item_details->package_dimension_uom : '-';
    $weight_per_unit = $item_details->weight_per_unit && $item_details->weight_per_unit > 0 ? trim($item_details->weight_per_unit).' '.$item_details->weight_uom : '-';
    $length = $item_details->length && $item_details->length > 0 ? trim($item_details->length).' '.$item_details->package_dimension_uom : '-';
    $width = $item_details->width && $item_details->width > 0 ? trim($item_details->width).' '.$item_details->package_dimension_uom : '-';
    $thickness = $item_details->thickness && $item_details->thickness > 0 ? trim($item_details->thickness).' '.$item_details->package_dimension_uom : '-';
@endphp
<dl class="ml-3">
    <dt class="responsive-item-code" style="font-size: 14pt;">{{ $item_details->name.' '.$item_details->brand }}</dt>
    <dd class="responsive-description" style="font-size: 11pt;" class="text-justify mb-2">{!! $item_details->description !!}</dd>
</dl>
<dl class="ml-3">
    <dt style="font-size: 10pt;">Package Dimension</dt>
    <dd style="font-size: 9pt;" class="text-justify mb-2">
        Net Weight: {{ $weight_per_unit }},
        Length: {{ $package_length }},
        Width: {{ $package_width }},
        Height: {{ $package_height }}
        @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
            &nbsp;<i class="fa fa-edit" data-toggle="modal" data-target='#item-information-modal' style="color: #0069D9"></i>
        @endif
    </dd>
    {{-- <dt style="font-size: 10pt;">Product Dimension</dt>
    <dd style="font-size: 9pt;" class="text-justify mb-2">
        Weight: {{ $weight_per_unit }},
        Length: {{ $length }},
        Width: {{ $width }},
        Thickness: {{ $thickness }}
        @if (!in_array(Auth::user()->user_group, ['User', 'Promodiser']))
            &nbsp;<i class="fa fa-edit" data-toggle="modal" data-target='#item-information-modal-2' style="color: #0069D9"></i>
        @endif
    </dd> --}}
</dl>
<div class="modal fade" id="item-information-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Package Dimension</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-8 mx-auto" style="font-size: 9pt;">
                    <form action="/save_item_information/{{ $item_details->name }}" class="item-information-form" data-modal-container="#item-information-modal" method="post">
                        @csrf
                        <div class="form-group">
                            <label>Net Weight</label>
                            <input type="text" name="weight_per_unit" id="weight_per_unit" class="form-control" value="{{ $item_details->weight_per_unit ? trim($item_details->weight_per_unit) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Package Length</label>
                            <input type="text" name="package_length" id="package_length" class="form-control" value="{{ $item_details->package_length ? trim($item_details->package_length) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Package Width</label>
                            <input type="text" name="package_width" id="package_width" class="form-control" value="{{ $item_details->package_width ? trim($item_details->package_width) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Package Height</label>
                            <input type="text" name="package_height" id="package_height" class="form-control" value="{{ $item_details->package_height ? trim($item_details->package_height) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Package Dimension UoM</label>
                            <input type="text" name="package_dimension_uom" id="package_dimension_uom" class="form-control" value="{{ $item_details->package_dimension_uom }}" required>
                        </div>
                        <center>
                            <button type="submit" class="btn btn-primary" style="font-size: 12pt;"><i class="fa fa-save"></i> Save</button>
                        </center>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- <div class="modal fade" id="item-information-modal-2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Product Dimension</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-8 mx-auto" style="font-size: 9pt;">
                    <form action="/save_item_information/{{ $item_details->name }}" class="item-information-form" data-modal-container="#item-information-modal-2" method="post">
                        @csrf
                        <div class="form-group">
                            <label>Weight</label>
                            <input type="text" name="weight_per_unit" class="form-control" value="{{ $item_details->weight_per_unit ? number_format($item_details->weight_per_unit) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Length</label>
                            <input type="text" name="length" class="form-control" value="{{ $item_details->length ? number_format($item_details->length) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Width</label>
                            <input type="text" name="width" class="form-control" value="{{ $item_details->width ? number_format($item_details->width) : null }}" required>
                        </div>
                        <div class="form-group">
                            <label>Thickness</label>
                            <input type="text" name="thickness" class="form-control" value="{{ $item_details->thickness ? number_format($item_details->thickness) : null }}" required>
                        </div>
                        <center>
                            <button type="submit" class="btn btn-primary" style="font-size: 12pt;"><i class="fa fa-save"></i> Save</button>
                        </center>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> --}}
<script>
    $(document).ready(function (){
        $('.item-information-form').submit(function(e){
            e.preventDefault();
            var modal = $(this).data('modal-container');

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response){
                    if (response.success) {
                        load_item_information();
                        showNotification("success", response.message, "fa fa-check");
                        $(modal).modal('hide');
                    }else{
                        showNotification("danger", response.message, "fa fa-info");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    showNotification("danger", 'An error occured. Please try again.', "fa fa-info");
                }
            });
        });
    });
</script>