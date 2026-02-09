@php
    $packageWeight = $itemDetails->package_weight && $itemDetails->package_weight > 0 ? trim($itemDetails->package_weight).' '.$itemDetails->weight_uom : '-';
    $packageLength = $itemDetails->package_length && $itemDetails->package_length > 0 ? trim($itemDetails->package_length).' '.$itemDetails->package_dimension_uom : '-';
    $packageWidth = $itemDetails->package_width && $itemDetails->package_width > 0 ? trim($itemDetails->package_width).' '.$itemDetails->package_dimension_uom : '-';
    $packageHeight = $itemDetails->package_height && $itemDetails->package_height > 0 ? trim($itemDetails->package_height).' '.$itemDetails->package_dimension_uom : '-';
    $weightPerUnit = $itemDetails->weight_per_unit && $itemDetails->weight_per_unit > 0 ? trim((float)$itemDetails->weight_per_unit).' '.$itemDetails->weight_uom : '-';
@endphp
<dl class="ml-3 {{ $packageWeight == '-' && $packageLength == '-' && $packageWidth == '-' && $packageHeight == '-' && $weightPerUnit == '-' ? 'd-none' : null }}">
    <dt style="font-size: 9pt;">Package Dimension</dt>
    <dd style="font-size: 8pt;" class="text-justify mb-2 pt-1">
        <p class="mb-1">
            <span class="text-muted">Net Weight:</span> <b>{{ $weightPerUnit }}</b>, <span class="text-muted">Length:</span> <b>{{ $packageLength }}</b>, <span class="text-muted">Width:</span>  <b>{{ $packageWidth }}</b>, <span class="text-muted">Height:</span> <b>{{ $packageHeight }}</b> 
        </p>
    </dd>
</dl>
<div class="modal fade" id="item-information-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="min-width: 30%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Package Dimension</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="/save_item_information/{{ $itemDetails->name }}" class="item-information-form" data-modal-container="#item-information-modal" method="post" autocomplete="off">
                    @csrf
                    <div class="row" style="font-size: 9pt;">
                    
                        <div class="form-group col-6">
                            <label>Package Length</label>
                            <input type="text" name="package_length" id="package_length" placeholder="Length" class="form-control" value="{{ $itemDetails->package_length ? trim($itemDetails->package_length) : null }}" required style="font-size: 9pt;">
                        </div>
                        <div class="form-group col-6">
                            <label>Package Width</label>
                            <input type="text" name="package_width" id="package_width" placeholder="Width" class="form-control" value="{{ $itemDetails->package_width ? trim($itemDetails->package_width) : null }}" required style="font-size: 9pt;">
                        </div>
                        <div class="form-group col-6">
                            <label>Package Height</label>
                            <input type="text" name="package_height" id="package_height" placeholder="Height" class="form-control" value="{{ $itemDetails->package_height ? trim($itemDetails->package_height) : null }}" required style="font-size: 9pt;">
                        </div>

                        <div class="form-group col-6" id="package-dimension-uom-div">
                            <label>Package Dimension UoM</label>
                            <select name="package_dimension_uom" id="package_dimension_uom" class="form-control" required style="font-size: 9pt;">
                                <option value="">Select Dimension UOM</option>
                                @foreach ($uoms as $uom)
                                <option value="{{ $uom }}" {{ $itemDetails->package_dimension_uom == $uom ? 'selected' : '' }}>{{ $uom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label>Net Weight</label>
                            <input type="text" name="weight_per_unit" id="weight_per_unit" class="form-control" placeholder="Net Weight" value="{{ $itemDetails->weight_per_unit ? trim($itemDetails->weight_per_unit) : null }}" required style="font-size: 9pt;">
                        </div>
                        <div class="col-4 offset-4 mt-3">
                            <button type="submit" class="btn btn-block btn-primary"><i class="fa fa-save"></i> Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    #package-dimension-uom-div .select2{
        width: 100% !important;
        outline: none !important;
        font-size: 9pt;
    }
    #package-dimension-uom-div .select2-selection__rendered {
        line-height: 33px !important;
        outline: none !important;
    }
    #package-dimension-uom-div .select2-container .select2-selection--single {
        height: 37px !important;
        padding-top: 1.5%;
        outline: none !important;
    }
    #package-dimension-uom-div .select2-selection__arrow {
        height: 35px !important;
    }
</style>

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

        $('#package_dimension_uom').select2();
    });
</script>