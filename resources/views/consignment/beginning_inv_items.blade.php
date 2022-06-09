<form action='/save_beginning_inventory' method="post" class="text-center {{ $branch != 'none' ? null : 'd-none' }}">
    @csrf
    <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search"/>
    <table class="table table-bordered text-left" id="items-table"> 
        <thead>
            <th class="font-responsive text-center p-1 align-middle" style="width: 42%">Item Code</th>
            <th class="font-responsive text-center p-1 align-middle">Opening Stock</th>
            <th class="font-responsive text-center p-1 align-middle">Price</th>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr id="{{ $item['item_code'] }}">
                    @php
                        $img = isset($item_images[$item['item_code']]) ? "/img/" . $item_images[$item['item_code']][0]->image_path : "/icon/no_img.png";
                        $img_webp = isset($item_images[$item['item_code']]) ? "/img/" . explode('.',$item_images[$item['item_code']][0]->image_path)[0].'.webp' : "/icon/no_img.webp";
                    @endphp 
                    <td class="text-justify p-1 align-middle" colspan="3">
                        <input type="text" name="item_code[]" id="{{ $item['item_code'] }}-id" class="d-none" value="{{ $item['item_code'] }}" />
                        <div class="d-flex flex-row justify-content-center align-items-center">
                            <div class="p-1 col-2 text-center">
                                <picture>
                                    <source srcset="{{ asset('storage'.$img_webp) }}" type="image/webp" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                    <source srcset="{{ asset('storage'.$img) }}" type="image/jpeg" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                    <img src="{{ asset('storage'.$img) }}" alt="{{ str_slug(explode('.', $img)[0], '-') }}" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                </picture>
                            </div>
                            <div class="p-1 col m-0">
                                <span class="font-weight-bold font-responsive">{{ $item['item_code'] }}</span>
                            </div>
                            <div class="p-0 col">
                                <div class="input-group p-1">
                                    <div class="input-group-prepend p-0">
                                        <button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                    </div>
                                    <div class="custom-a p-0">
                                        <input type="text" class="form-control form-control-sm qty validate stock" id="{{ $item['item_code'] }}-stock" value="{{ $item['opening_stock'] }}" data-item-code="{{ $item['item_code'] }}" name="opening_stock[{{ $item['item_code'] }}]" style="text-align: center; width: 47px">
                                    </div>
                                    <div class="input-group-append p-0">
                                        <button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-1 col">
                                <div class="input-group p-1">
                                    <div class="p-0">
                                        <input type="text" class="form-control form-control-sm qty validate price" id="{{ $item['item_code'] }}-price" data-item-code="{{ $item['item_code'] }}" placeholder="{{ $item['price'] }}" value="" name="price[{{ $item['item_code'] }}]" style="text-align: center;">
                                    </div>
                                </div>
                            </div>
                            <div class="p-1 font-responsive" style="width: 15px !important; color: red; cursor: pointer"><i class="fa fa-remove remove-item" data-id="{{ $item['item_code'] }}"></i></div>
                        </div>
                        <div class="p-1" id="{{ $item['item_code'] }}-description" style="font-size: 9.5pt !important; height: 43px; overflow: hidden;">
                            {!! strip_tags($item['item_description']) !!}
                        </div>
                        <div class="col-12 text-center">
                            <span class="btn btn-xs btn-outline-primary show-more" id="{{ $item['item_code'] }}-show-more" data-item-code="{{ $item['item_code'] }}">Show More</span>
                            <span class="btn btn-xs btn-outline-primary show-less d-none" id="{{ $item['item_code'] }}-show-less" data-item-code="{{ $item['item_code'] }}">Show Less</span>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center font-responsive" colspan="3">
                        No item(s) available.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="col-12 text-right">
        <span style="font-size: 15px;">Total items: {{ count($items) }}</span>
    </div>
    <button type="submit" id="submit-btn" class="btn btn-primary font-responsive mx-auto">Submit</button>

    <div class="d-none">
        {{-- values to save --}}
        <input type="text" name="branch" value="{{ $branch }}">
        <input type="text" name="inv_name" value="{{ $inv_name }}">

        {{-- used as a reference --}}
        <input type="text" id="item-count" value="{{ count($items) }}">
    </div>
</form>

<script>
    $(document).ready(function(){
        var branch = '{{ $branch }}';

        $(document).on('keyup', '.validate', function(){
            if(parseInt($(this).val()) < 0){
                $(this).css('border', '1px solid red');
                $('#submit-btn').prop('disabled', true);
            }else{
                $(this).css('border', '1px solid #CED4DA');
                $('#submit-btn').prop('disabled', false);
            }
        });

        $('.remove-item').click(function(){
            var item_code = $(this).data('id');

            $('#'+item_code).addClass('d-none');
            $('#'+item_code+'-id').val('');
            $('#item-count').val(parseInt($('#item-count').val()) - 1);

            if(parseInt($('#item-count').val()) <= 0){
                $('#submit-btn').prop('disabled', true);
            }else{
                $('#submit-btn').prop('disabled', false);
            }
        });

        $('.stock').on('keyup', function(){
            var item_code = $(this).data('item-code');
            if(parseInt($(this).val()) > 0 && parseInt($('#'+item_code+'-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#'+item_code+'-price').val() == ''){
                $('#'+item_code+'-price').prop('required', true);
                $('#'+item_code+'-price').css('border', '1px solid red');
            }else{
                $('#'+item_code+'-price').prop('required', false);
                $('#'+item_code+'-price').css('border', '1px solid #CED4DA');
            }
        });

        $('.price').on('keyup', function(){
            var item_code = $(this).data('item-code');
            if(parseInt($(this).val()) <= 0 && parseInt($('#'+item_code+'-stock').val()) > 0 || parseInt($(this).val()) == '' && $('#'+item_code+'-stock').val() > 0){
                $(this).prop('required', true);
                $(this).css('border', '1px solid red');
            }else{
                $(this).prop('required', false);
                $(this).css('border', '1px solid #CED4DA');
            }
        });

        $('.qtyplus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If is not undefined
            if (!isNaN(currentVal)) {
                // Increment
                fieldName.val(currentVal + 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }

            if(parseInt(fieldName.val()) > 0){
                $('#'+item_code+'-price').prop('required', true);
                $('#'+item_code+'-price').css('border', '1px solid red');
            }else{
                $('#'+item_code+'-price').prop('required', false);
                $('#'+item_code+'-price').css('border', '1px solid #CED4DA');
            }
        });

        // This button will decrement the value till 0
        $('.qtyminus').click(function(e){
            // Stop acting like a button
            e.preventDefault();
            // Get the field name
            var fieldName = $(this).parents('.input-group').find('.qty').eq(0);
            var item_code = fieldName.data('item-code');
            // Get its current value
            var currentVal = parseInt(fieldName.val());
            // If it isn't undefined or its greater than 0
            if (!isNaN(currentVal) && currentVal > 0) {
                // Decrement one
                fieldName.val(currentVal - 1);
            } else {
                // Otherwise put a 0 there
                fieldName.val(0);
            }

            if(parseInt(fieldName.val()) > 0){
                $('#'+item_code+'-price').prop('required', true);
                $('#'+item_code+'-price').css('border', '1px solid red');
            }else{
                $('#'+item_code+'-price').prop('required', false);
                $('#'+item_code+'-price').css('border', '1px solid #CED4DA');
            }
        });

        $("#item-search").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#items-table tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        $('.show-more').click(function(){
            var item_code = $(this).data('item-code');

            $('#'+item_code+'-description').css('height', 'auto');
            $(this).addClass('d-none');
            $('#'+item_code+'-show-less').removeClass('d-none');
        });

        $('.show-less').click(function(){
            var item_code = $(this).data('item-code');

            $('#'+item_code+'-description').css('height', '43px');
            $(this).addClass('d-none');
            $('#'+item_code+'-show-more').removeClass('d-none');
        });
    });
</script>