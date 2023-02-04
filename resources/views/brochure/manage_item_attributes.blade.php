<ul id="sortable" class="list-group">
    @foreach ($attributes as $attr)
        <li class="list-group-item p-1">
            <div class="row">
                <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                    <i class="fas fa-arrows-alt" style="font-size: inherit;"></i>
                </div>
                <div class="col-10 p-0">
                    <input type="text" class="form-control p-1" name="attribute[{{ $attr->name }}]" value="{{ $attr->attr_name ? $attr->attr_name : $attr->attribute }}" style="font-size: inherit !important;">
                    <input type="hidden" name="current_attribute[{{ $attr->name }}]" value="{{ $attr->attribute }}">
                </div>
                <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                    <i class="attrib-toggle far fa-eye{{ $attr->hide_in_brochure ? '-slash' : null }}" data-attrib-hidden="{{ $attr->hide_in_brochure ? 1 : 0 }}" data-attribute="{{ $attr->attribute }}" style="font-size: 10pt;"></i>
                    <input type="hidden" class="hidden-attrib-val" name="hidden_attributes[]" value="{{ $attr->hide_in_brochure ? $attr->attribute : null }}">
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
<script>
    $(document).ready(function(){
        $('#sortable').sortable();

        $(document).on('click', '.attrib-toggle', function (){
            var val = '';
            if($(this).data('attrib-hidden') == 1){
                $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                $(this).data('attrib-hidden', 0);
            }else{
                val = $(this).data('attribute');
                $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                $(this).data('attrib-hidden', 1);
            }
            $(this).next('.hidden-attrib-val').val(val);
        });
    });
</script>