<ul id="sortable" class="list-group">
    @foreach ($attributes as $attr)
        <li class="list-group-item p-1">
            <div class="row">
                <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                    <i class="fas fa-arrows-alt" style="font-size: inherit;"></i>
                </div>
                <div class="col-11">
                    <input type="text" class="form-control p-1" name="attribute[{{ $attr->name }}]" value="{{ $attr->attr_name ? $attr->attr_name : $attr->attribute }}" style="font-size: inherit !important;">
                    <input type="hidden" name="current_attribute[{{ $attr->name }}]" value="{{ $attr->attribute }}">
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
    });
</script>