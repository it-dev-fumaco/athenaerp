<br>
<table class="table table-bordered text-left">
    <tr>
        <th class="font-responsive" style="width: 40%">Item Code</th>
        <th class="font-responsive">Opening Stock</th>
        <th class="font-responsive">Price</th>
    </tr>
    @forelse ($items as $item)
        <tr>
            <td class="font-responsive">
                {!! $item->item_code.' - '.substr($item->description, 0, 50).'...' !!}
                <input type="text" name="item_code[]" id="" class="d-none" value="{{ $item->item_code }}" />
            </td>
            <td class="font-responsive">
                <input class="form-control" type="number" name="opening_stock[]" value="{{ isset($inventory[$item->item_code]) ? $inventory[$item->item_code][0]->opening_stock * 1 : 0 }}" min="1" step=".01" required/>
            </td>
            <td class="font-responsive">
                <input type="text" name="price[]" class="form-control validate" value="{{ isset($inventory[$item->item_code]) ? $inventory[$item->item_code][0]->price * 1 : 0 }}" required/>
            </td>
        </tr>
    @empty
        <tr colspan=5>
            <td class="text-center font-responsive" colspan="3">No item(s) available.</td>
        </tr>
    @endforelse
</table>
<button type="submit" id="submit-btn" class="btn btn-primary font-responsive mx-auto">Submit</button>

<div class="d-none">
    <input type="text" name="branch" value="{{ $branch }}">
</div>
<script>
    $(document).ready(function(){
        $('.validate').keyup(function(){
            if(parseInt($(this).val()) < 0){
                $(this).css('border', '1px solid red');
                $('#submit-btn').prop('disabled', true);
            }else{
                $(this).css('border', '1px solid #CED4DA');
                $('#submit-btn').prop('disabled', false);
            }
        });
    });
</script>