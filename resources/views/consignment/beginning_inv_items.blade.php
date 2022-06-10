<form action='/save_beginning_inventory' method="post" class="text-center {{ $branch != 'none' ? null : 'd-none' }}">
    @csrf
    <div class="row">
        <div class="col-8">
            <input type="text" class="form-control mt-2 mb-2" id="item-search" name="search" placeholder="Search" style="font-size: 9pt"/>
        </div>
        <div class="col-4" style="display: flex; justify-content: center; align-items: center;">
            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#add-item-Modal" style="font-size: 9pt;">
                <i class="fa fa-plus"></i> Add Items
            </button>
              
              <!-- Modal -->
            <div class="modal fade" id="add-item-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color: #001F3F; color: #fff;">
                            <h5 class="modal-title" id="exampleModalLabel">Add an item</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff;">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <select id="item-selection" class="form-control"></select>
                            <div class="row mt-3 d-none" id="item-to-add">
                                <table class="table table-bordered" id="new-item-table">
                                    <thead>
                                        <th class="font-responsive text-center p-1 align-middle" style="width: 42%">Item Code</th>
                                        <th class="font-responsive text-center p-1 align-middle">Opening Stock</th>
                                        <th class="font-responsive text-center p-1 align-middle">Price</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-justify p-1 align-middle" colspan="3">
                                                <div class="d-flex flex-row justify-content-center align-items-center">
                                                    <div class="p-1 col-2 text-center">
                                                        <picture>
                                                            <source srcset="" id="new-src-img-webp" type="image/webp">
                                                            <source srcset="" id="new-src-img" type="image/jpeg">
                                                            <img src="" alt="" id="new-img" class="img-thumbna1il" alt="User Image" width="40" height="40">
                                                        </picture>
                                                    </div>
                                                    <div class="p-1 col m-0">
                                                        <span class="font-weight-bold font-responsive"><span id="new-item-code"></span></span>
                                                    </div>
                                                    <div class="p-0 col-4">
                                                        <div class="input-group p-1 ml-3">
                                                            <div class="input-group-prepend p-0">
                                                                <button class="btn btn-outline-danger btn-xs new-item-qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>
                                                            </div>
                                                            <div class="custom-a p-0">
                                                                <input type="text" class="form-control form-control-sm qty new-item-validate new-item-stock" id="new-item-stock" value="0" style="text-align: center; width: 47px">
                                                            </div>
                                                            <div class="input-group-append p-0">
                                                                <button class="btn btn-outline-success btn-xs new-item-qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="p-1 col">
                                                        <div class="input-group p-1">
                                                            <div class="p-0">
                                                                <input type="text" class="form-control form-control-sm qty new-item-validate new-itemprice" id="new-item-price" value="" placeholder="0" style="text-align: center;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="p-1" style="font-size: 9.5pt !important;">
                                                    <span class="font-italic" id="new-classification"></span> <br>
                                                    <span id="new-description"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="d-none">
                                    <span id="new-img-txt"></span>
                                    <span id="new-webp-txt"></span>
                                    <span id="new-alt-txt"></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="add-item-btn" class="btn btn-primary d-none">Add item</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
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
                            <div class="p-0 col-4">
                                <div class="input-group p-1 ml-3">
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
                        <div class="p-1 item-description" style="font-size: 9.5pt !important;">
                            {!! strip_tags($item['item_description']) !!}
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

    <div class="w-100 text-center d-none p-2" id="add-item-success" style="position: absolute; top: 0; left: 0">
        <div class="alert alert-success alert-dismissible fade show font-responsive p-1" role="alert">
            New item added.
        </div>
    </div>
</form>
<style>
    .morectnt span {
        display: none;
    }
</style>
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

        // $('.remove-item').click(function(){
        $('table#items-table').on('click', '.remove-item', function(){
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

        // $('.stock').on('keyup', function(){
        $('table#items-table').on('keyup', '.stock', function(e){
            var item_code = $(this).data('item-code');
            if(parseInt($(this).val()) > 0 && parseInt($('#'+item_code+'-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#'+item_code+'-price').val() == ''){
                $('#'+item_code+'-price').prop('required', true);
                $('#'+item_code+'-price').css('border', '1px solid red');
            }else{
                $('#'+item_code+'-price').prop('required', false);
                $('#'+item_code+'-price').css('border', '1px solid #CED4DA');
            }
        });

        // $('.price').on('keyup', function(){
        $('table#items-table').on('keyup', '.price', function(e){
            var item_code = $(this).data('item-code');
            if(parseInt($(this).val()) <= 0 && parseInt($('#'+item_code+'-stock').val()) > 0 || parseInt($(this).val()) == '' && $('#'+item_code+'-stock').val() > 0){
                $(this).prop('required', true);
                $(this).css('border', '1px solid red');
            }else{
                $(this).prop('required', false);
                $(this).css('border', '1px solid #CED4DA');
            }
        });

        // $('.qtyplus').click(function(e){
        $('table#items-table').on('click', '.qtyplus', function(e){
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

            if(parseInt($(this).val()) > 0 && parseInt($('#'+item_code+'-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#'+item_code+'-price').val() == ''){
                $('#'+item_code+'-price').prop('required', true);
                $('#'+item_code+'-price').css('border', '1px solid red');
            }else{
                $('#'+item_code+'-price').prop('required', false);
                $('#'+item_code+'-price').css('border', '1px solid #CED4DA');
            }
        });

        // This button will decrement the value till 0
        // $('.qtyminus').click(function(e){
        $('table#items-table').on('click', '.qtyminus', function(e){
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

            if(parseInt($(this).val()) > 0 && parseInt($('#'+item_code+'-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#'+item_code+'-price').val() == ''){
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
        
        var showTotalChar = 98, showChar = "Show more", hideChar = "Show less";
        $('.item-description').each(function() {
            var content = $(this).text();
            if (content.length > showTotalChar) {
                var con = content.substr(0, showTotalChar);
                var hcon = content.substr(showTotalChar, content.length - showTotalChar);
                var txt = con + '<span class="dots">...</span><span class="morectnt"><span>' + hcon + '</span>&nbsp;&nbsp;<a href="#" class="show-more">' + showChar + '</a></span>';
                $(this).html(txt);
            }
        });

        $(".show-more").click(function(e) {
            e.preventDefault();
            if ($(this).hasClass("sample")) {
                $(this).removeClass("sample");
                $(this).text(showChar);
            } else {
                $(this).addClass("sample");
                $(this).text(hideChar);
            }

            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });

        $('#item-selection').select2({
            placeholder: 'Select an Item',

            ajax: {
                url: '/get_items/{{ $branch }}',
                method: 'GET',
                dataType: 'json',
                data: function (data) {
                    return {
                        q: data.term // search term
                    };
                },
                processResults: function (response) {
                    return {
                        results: response.items
                    };
                },
                cache: true
            }
        });

        $(document).on('select2:select', '#item-selection', function(e){
            $('#new-item-code').text(e.params.data.id); // item code
            $('#new-description').text(e.params.data.description); // description
            $('#new-classification').text(e.params.data.classification); // classification
            $('#new-src-img').attr('src', e.params.data.image); // image
            $('#new-src-img-webp').attr('src', e.params.data.image_webp); // webp
            $('#new-img').attr('src', e.params.data.image); // image

            $('#new-img-txt').text(e.params.data.image); // image
            $('#new-webp-txt').text(e.params.data.image_webp); // webp
            $('#new-alt-txt').text(e.params.data.alt); // alt text

            $('#add-item-btn').removeClass('d-none');
            $('#item-to-add').removeClass('d-none');
        });

        $('#add-item-btn').click(function (){
            add_item('#items-table tbody');
            $('#add-item-Modal').modal('hide')

            $('#item-count').val(parseInt($('#item-count').val()) + 1);

            // Reset values
            $('#new-item-code').text('');
            $('#new-description').text('');
            $('#new-classification').text('');
            $('#new-src-img').attr('src', '');
            $('#new-src-img-webp').attr('src', '');
            $('#new-img').attr('src', '');

            $('#new-img-txt').text('');
            $('#new-webp-txt').text('');
            $('#new-alt-txt').text('');

            $('#new-item-stock').val(0);
            $('#new-item-price').val('');

            $('#add-item-btn').addClass('d-none');
            $('#item-to-add').addClass('d-none');

            $('#add-item-success').removeClass('d-none');
            $('#add-item-success').fadeOut(3000, 'linear', 'complete');

        });

        function add_item(table){
            var item_code = $('#new-item-code').text();
            var description = $('#new-description').text();
            var classification = $('#new-classification').text();
            var image = $('#new-img-txt').text();
            var webp = $('#new-webp-txt').text();
            var alt = $('#new-alt-txt').text();

            var stock = $('#new-item-stock').val();
            var price = $('#new-item-price').val();

			var row = '<tr id="' + item_code + '">' +
                '<td class="text-justify p-1 align-middle" colspan="3">' +
                    '<input type="text" name="item_code[]" id="' + item_code + '-id" class="d-none" value="' + item_code + '" />' +
                    '<div class="d-flex flex-row justify-content-center align-items-center">' +
                        '<div class="p-1 col-2 text-center">' +
                            '<picture>' +
                                '<source srcset="' + webp + '" type="image/webp" class="img-thumbna1il" alt="User Image" width="40" height="40">' +
                                '<source srcset="' + image + '" type="image/jpeg" class="img-thumbna1il" alt="User Image" width="40" height="40">' +
                                '<img src="' + image + '" alt="' + alt + '" class="img-thumbna1il" alt="User Image" width="40" height="40">' +
                            '</picture>' +
                        '</div>' +
                        '<div class="p-1 col m-0">' +
                            '<span class="font-weight-bold font-responsive">' + item_code + '</span>' +
                        '</div>' +
                        '<div class="p-0 col-4">' +
                            '<div class="input-group p-1 ml-3">' +
                                '<div class="input-group-prepend p-0">' +
                                    '<button class="btn btn-outline-danger btn-xs qtyminus" style="padding: 0 5px 0 5px;" type="button">-</button>' +
                                '</div>' +
                                '<div class="custom-a p-0">' +
                                    '<input type="text" class="form-control form-control-sm qty validate stock" id="' + item_code + '-stock" value="' + stock + '" data-item-code="' + item_code + '" name="opening_stock[' + item_code + ']" style="text-align: center; width: 47px">' +
                                '</div>' +
                                '<div class="input-group-append p-0">' +
                                    '<button class="btn btn-outline-success btn-xs qtyplus" style="padding: 0 5px 0 5px;" type="button">+</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-1 col">' +
                            '<div class="input-group p-1">' +
                                '<div class="p-0">' +
                                    '<input type="text" class="form-control form-control-sm qty validate price" id="' + item_code + '-price" data-item-code="' + item_code + '" placeholder="0" value="' + price + '" name="price[' + item_code + ']" style="text-align: center;">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-1 font-responsive" style="width: 15px !important; color: red; cursor: pointer"><i class="fa fa-remove remove-item" data-id="' + item_code + '"></i></div>' +
                    '</div>' +
                    '<div class="p-1 item-description" style="font-size: 9.5pt !important;">' +
                        description
                    '</div>' +
                '</td>' +
            '</tr>';

			$(table).prepend(row);
		}

        // separate controls for 'Add Item' modal 
        // to prevent events from firing multiple times e.g., Clicking '+' on stocks adds 2 instead of 1
        $('table#new-item-table').on('keyup', '.new-item-validate', function(){
            if(parseInt($(this).val()) < 0){
                $(this).css('border', '1px solid red');
                $('#submit-btn').prop('disabled', true);
                $('#add-item-btn').prop('disabled', true);
            }else{
                $(this).css('border', '1px solid #CED4DA');
                $('#submit-btn').prop('disabled', false);
                $('#add-item-btn').prop('disabled', false);
            }
        });

        $('.new-item-qtyplus').click(function(e){
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

            if(parseInt($(this).val()) > 0 && parseInt($('#new-item-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#new-item-price').val() == ''){
                $('#new-item-price').prop('required', true);
                $('#new-item-price').css('border', '1px solid red');
            }else{
                $('#new-item-price').prop('required', false);
                $('#new-item-price').css('border', '1px solid #CED4DA');
            }
        });

        // This button will decrement the value till 0
        $('.new-item-qtyminus').click(function(e){
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

            if(parseInt($(this).val()) > 0 && parseInt($('#new-item-price').val()) <= 0 || parseInt($(this).val()) > 0 && $('#new-item-price').val() == ''){
                $('#new-item-price').prop('required', true);
                $('#new-item-price').css('border', '1px solid red');
            }else{
                $('#new-item-price').prop('required', false);
                $('#new-item-price').css('border', '1px solid #CED4DA');
            }
        });
    });
</script>