<ul class="list-group">
@foreach ($attributes as $attr)
    <li class="list-group-item p-2">
        <input type="text" class="form-control p-2" name="attribute[{{ $attr->name }}]" value="{{ $attr->attr_name ? $attr->attr_name : $attr->attribute }}" style="font-size: 13px !important;">
        <input type="hidden" name="current_attribute[{{ $attr->name }}]" value="{{ $attr->attribute }}">
    </li>
@endforeach
</ul>
<div class="d-flex flex-row justify-content-center mt-3">
    <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Preview Changes</button>
</div>