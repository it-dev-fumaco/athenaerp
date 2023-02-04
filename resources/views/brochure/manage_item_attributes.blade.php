<ul id="sortable" class="list-group">
    @foreach ($attributes as $attr)
        <li class="list-group-item p-1 {{ $attr->hide_in_brochure ? 'hidden-attrib' : null }}">
            <div class="row">
                <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                    <i class="fas fa-arrows-alt" style="font-size: inherit;"></i>
                </div>
                <div class="col-9 p-0">
                    <input type="text" class="form-control p-1" name="attribute[{{ $attr->name }}]" value="{{ $attr->attr_name ? $attr->attr_name : $attr->attribute }}" style="font-size: inherit !important; background-color: inherit">
                    <input type="hidden" name="current_attribute[{{ $attr->name }}]" value="{{ $attr->attribute }}">
                </div>
                <div class="col-2" style="display: flex; justify-content: center; align-items: center;">
                    <div class="text-center">
                        <input type="checkbox" class="hidden-attributes" data-attribute="{{ $attr->attribute }}" {{ $attr->hide_in_brochure ? 'checked' : null }}>
                        <input type="hidden" name="hidden_attributes[]" value="{{ $attr->hide_in_brochure ? $attr->attribute : null }}"> <br>
                        <small class="text-muted">{{ $attr->hide_in_brochure ? 'Unhide' : 'Hide' }}</small>
                    </div>
                </div>
            </div>
        </li>
    @endforeach
</ul>
<div class="form-group mt-3">
    <label>Remarks</label>
    <textarea name="remarks" rows="3" class="form-control p-2" placeholder="Enter Remarks" style="font-size: 9pt !important;">
        {{ $remarks }}
    </textarea>
</div>
<div class="d-flex flex-row justify-content-center mt-3">
    <input type="hidden" name="item_code" value="{{ $item_code }}">
    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Preview Changes</button>
</div>
<style>
    .hidden-attrib{
        background-color: #E6E6E6;
    }
</style>
<script>
    $(document).ready(function(){
        $('#sortable').sortable();

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
    });
</script>