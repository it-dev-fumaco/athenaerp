<ul id="sortable" class="list-group">
@foreach ($attributes as $attr)
    <li class="list-group-item p-2">
        <div class="row">
            <div class="col-1" style="display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-arrows-alt" style="font-size: 10pt;"></i>
            </div>
            <div class="col-11">
                <input type="text" class="form-control p-2" name="attribute[{{ $attr->name }}]" value="{{ $attr->attr_name ? $attr->attr_name : $attr->attribute }}" style="font-size: 13px !important;">
                <input type="hidden" name="current_attribute[{{ $attr->name }}]" value="{{ $attr->attribute }}">
            </div>
        </div>
    </li>
@endforeach
</ul>
<div class="d-flex flex-row justify-content-center mt-3">
    <input type="hidden" name="item_code" value="{{ $item_code }}">
    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Preview Changes</button>
</div>

<script>
    $(document).ready(function(){
        $('#sortable').sortable();
    });
</script>